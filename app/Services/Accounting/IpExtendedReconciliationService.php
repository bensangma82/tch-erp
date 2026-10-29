<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Preflight\IpAccountingReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class IpExtendedReconciliationService
{
    public function __construct(
        private IpAccountingReconciliationService $inspection
    ) {}


    // IP_EXTENDED_POSTING_V1
    public function reconcile(IpBillingAccount $account): array
    {
        $user = auth()->user();

        if (
            ! $user
            || ! $user->hasPermission('finance.reconciliation.manage')
            || ! $user->hasPermission('ip-billing.manage')
        ) {
            throw ValidationException::withMessages([
                'reconciliation' => 'Finance and IP Billing permissions are required.',
            ]);
        }

        return DB::transaction(function () use ($account, $user) {
            $account = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->status !== 'finalized' || ! $account->final_bill_no) {
                throw ValidationException::withMessages([
                    'reconciliation' => 'A finalized bill is required.',
                ]);
            }

            // Lock the original receipts in a consistent order.
            $advances = $account->advances()
                ->orderBy('id')->lockForUpdate()->get();
            $payments = $account->payments()
                ->orderBy('id')->lockForUpdate()->get();

            $fail = static function (string $message): never {
                throw ValidationException::withMessages([
                    'reconciliation' => $message,
                ]);
            };

            $toPaise = static function ($value) use ($fail): int {
                $value = (string) $value;
                if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                    $fail('Invalid accounting amount.');
                }
                [$whole, $fraction] = array_pad(
                    explode('.', $value, 2), 2, '0'
                );
                return ((int) $whole * 100)
                    + (int) str_pad($fraction, 2, '0');
            };

            // This first controlled repair supports the exact
            // legacy scenario: finalized bill, active receipts,
            // and no previous MHIS recognition or other adjustments.
            if (
                $advances->isEmpty()
                || $payments->isEmpty()
                || $account->mhisClaims()->count() > 1
                || $account->mhisClaims()
                    ->whereNotIn('status', ['pending', 'submitted'])->exists()
                || $account->mhisReceipts()->exists()
                || $account->mhisAdjustments()->exists()
                || $account->staffMedicalBenefitTransactions()->exists()
                || $account->charityAdjustments()->exists()
                || $account->refunds()->exists()
            ) {
                $fail('This account requires a different Finance reconciliation workflow.');
            }

            $receipts = [];
            $totalAdvance = 0;
            $totalPayment = 0;

            foreach ([
                ['records' => $advances, 'source' => 'ip_billing_advance',
                 'credit' => '2100', 'prefix' => 'JRN-IPADV-'],
                ['records' => $payments, 'source' => 'ip_billing_payment',
                 'credit' => '1200', 'prefix' => 'JRN-IPPAY-'],
            ] as $group) {
                foreach ($group['records'] as $receipt) {
                    if ($receipt->status !== 'active') {
                        $fail('An inactive receipt requires investigation.');
                    }

                    if (
                        $group['source'] === 'ip_billing_advance'
                        && $receipt->source_type !== null
                    ) {
                        $fail('Source-linked advances require separate review.');
                    }

                    $modes = ['cash' => '1100', 'upi' => '1110', 'card' => '1120'];
                    $mode = strtolower(trim((string) $receipt->payment_mode));
                    $debit = $modes[$mode] ?? null;

                    if (! $debit || ! $receipt->receipt_no) {
                        $fail('Unsupported or incomplete receipt.');
                    }

                    $amountPaise = $toPaise($receipt->amount);
                    if ($amountPaise <= 0) {
                        $fail('Receipt amount must be positive.');
                    }

                    $journalNo = $group['prefix'].$receipt->id;

                    if (
                        JournalEntry::query()
                            ->where('source_type', $group['source'])
                            ->where('source_id', (string) $receipt->id)
                            ->exists()
                        || JournalEntry::query()
                            ->where('journal_no', $journalNo)->exists()
                        || JournalEntry::query()
                            ->where('reference_no', $receipt->receipt_no)->exists()
                    ) {
                        $fail('An existing journal may already account for receipt '
                            .$receipt->receipt_no.'. No posting performed.');
                    }

                    $receipts[] = [
                        'source' => $group['source'],
                        'id' => $receipt->id,
                        'number' => $receipt->receipt_no,
                        'journal' => $journalNo,
                        'date' => $receipt->payment_date,
                        'debit' => $debit,
                        'credit' => $group['credit'],
                        'paise' => $amountPaise,
                    ];

                    if ($group['source'] === 'ip_billing_advance') {
                        $totalAdvance += $amountPaise;
                    } else {
                        $totalPayment += $amountPaise;
                    }
                }
            }

            $billPaise = $toPaise($account->net_amount);

            // Conservative initial restriction: receipts must
            // exactly settle the bill, without overpayment.
            if (
                $billPaise <= 0
                || $totalAdvance <= 0
                || $totalAdvance + $totalPayment !== $billPaise
            ) {
                $fail('Receipts do not exactly reconcile to the finalized bill.');
            }

            $accountJournals = JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $account->id)
                ->with('lines')
                ->get();

            if (
                $accountJournals->count() !== 1
                || $accountJournals->first()->source_event !== 'finalized'
                || $accountJournals->first()->status !== 'posted'
            ) {
                $fail('The finalized bill or existing adjustments require review.');
            }

            $gl = GeneralLedgerAccount::query()
                ->whereIn('account_code', ['1100', '1110', '1120', '1200', '2100'])
                ->get()->keyBy('account_code');

            $requiredCodes = collect($receipts)
                ->pluck('debit')
                ->merge(['1200', '2100'])
                ->unique();

            foreach ($requiredCodes as $code) {
                if (
                    ! $gl->has($code)
                    || ! $gl[$code]->is_active
                    || ! $gl[$code]->allow_posting
                ) {
                    $fail('Required posting GL '.$code.' is unavailable.');
                }
            }

            $revenueJournal = $accountJournals->first();
            $receivablePaise = 0;

            foreach ($revenueJournal->lines as $line) {
                if ((int) $line->general_ledger_account_id === (int) $gl['1200']->id) {
                    $receivablePaise += $toPaise($line->debit)
                        - $toPaise($line->credit);
                }
            }

            if ($receivablePaise !== $billPaise) {
                $fail('Final bill GL 1200 does not match the invoice amount.');
            }

            // No posted MHIS recognition for this claim.
            $claimIds = $account->mhisClaims()->pluck('id');
            if (
                $claimIds->isNotEmpty()
                && JournalEntry::query()
                    ->where('source_type', 'ip_billing_mhis_claim')
                    ->whereIn('source_id', $claimIds)
                    ->exists()
            ) {
                $fail('MHIS accounting entries require separate reconciliation.');
            }

            if (
                JournalEntry::query()
                    ->where('journal_no', 'JRN-IPAPPLY-'.$account->id)
                    ->exists()
            ) {
                $fail('An advance application journal already exists.');
            }

            $posting = app(JournalPostingService::class);
            $posted = [];

            $createJournal = function (
                string $journalNo,
                string $source,
                int $sourceId,
                string $event,
                string $reference,
                $date,
                string $debit,
                string $credit,
                int $paise
            ) use ($gl, $posting, $user, &$posted) {
                $amount = number_format($paise / 100, 2, '.', '');

                $journal = JournalEntry::create([
                    'journal_no' => $journalNo,
                    'journal_date' => $date,
                    'source_type' => $source,
                    'source_id' => $sourceId,
                    'source_event' => $event,
                    'reference_no' => $reference,
                    'narration' => 'Verified legacy IPD accounting reconciliation: '.$reference,
                    'status' => 'draft',
                    'created_by' => $user->id,
                ]);

                foreach ([
                    [$debit, $amount, '0.00'],
                    [$credit, '0.00', $amount],
                ] as $index => $line) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'line_no' => $index + 1,
                        'general_ledger_account_id' => $gl[$line[0]]->id,
                        'department_id' => null,
                        'debit' => $line[1],
                        'credit' => $line[2],
                        'reference_no' => $reference,
                        'description' => 'Verified historical IPD reconciliation',
                    ]);
                }

                $posting->post($journal);
                $posted[] = $journalNo;
            };

            foreach ($receipts as $receipt) {
                $createJournal(
                    $receipt['journal'],
                    $receipt['source'],
                    $receipt['id'],
                    'received',
                    $receipt['number'],
                    $receipt['date'],
                    $receipt['debit'],
                    $receipt['credit'],
                    $receipt['paise']
                );
            }

            $createJournal(
                'JRN-IPAPPLY-'.$account->id,
                'ip_billing_account',
                $account->id,
                'advance_applied',
                $account->final_bill_no,
                now()->toDateString(),
                '2100',
                '1200',
                $totalAdvance
            );

            return ['posted_journals' => $posted];
        }, 3);
    }

    public function preview(IpBillingAccount $account): array
    {
        $result = $this->inspection->inspect($account);

        if ($account->status !== 'finalized') {
            throw ValidationException::withMessages([
                'reconciliation' => 'The IPD bill must be finalized.',
            ]);
        }

        $entries = [];

        foreach ($result['items'] as $item) {
            if ($item['receipt_status'] !== 'active') {
                continue;
            }

            if ($item['journal_status'] === 'posted') {
                continue;
            }

            if (
                $item['journal_status'] !== 'missing'
                || $item['requires_finance_review']
                || ! $item['proposed_debit_gl']
            ) {
                throw ValidationException::withMessages([
                    'reconciliation' =>
                        'This receipt requires manual Finance investigation.',
                ]);
            }

            $entries[] = [
                'type' => $item['type'],
                'source_id' => $item['id'],
                'reference' => $item['receipt_no'],
                'debit_gl' => $item['proposed_debit_gl'],
                'credit_gl' => $item['proposed_credit_gl'],
                'amount' => $item['amount'],
            ];
        }

        $activeAdvances = $account->advances
            ->where('status', 'active');

        $applicationAmount = number_format(
            min(
                (float) $activeAdvances->sum('amount'),
                (float) $account->net_amount
            ),
            2,
            '.',
            ''
        );

        $applicationPreview = null;

        if (
            $applicationAmount > 0
            && $result['advance_application']['status'] === 'missing'
        ) {
            $applicationPreview = [
                'debit_gl' => '2100',
                'credit_gl' => '1200',
                'amount' => $applicationAmount,
                'reference' => $account->final_bill_no,
            ];
        }

        return [
            'account_id' => $account->id,
            'application_preview' => $applicationPreview,
            'entries' => $entries,
            'advance_application' => $result['advance_application'],
            'posting_enabled' => false,
        ];
    }
}
