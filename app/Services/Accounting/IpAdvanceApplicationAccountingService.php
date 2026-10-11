<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpAdvanceApplicationAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function apply(
        IpBillingAccount $account
    ): ?JournalEntry {
        return DB::transaction(function () use ($account) {
            $account = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            $sourceType = 'ip_billing_account';
            $sourceEvent = 'advance_applied';

            // Return an existing posted application journal.
            $existing = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $account->id)
                ->where('source_event', $sourceEvent)
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw new RuntimeException(
                        'An unposted advance application journal exists.'
                    );
                }

                return $existing;
            }

            if (
                $account->status !== 'finalized'
                || ! $account->final_bill_no
            ) {
                throw new RuntimeException(
                    'The IPD bill must be finalized.'
                );
            }

            if (! auth()->check()) {
                throw new RuntimeException(
                    'An authenticated user is required.'
                );
            }

            // The revenue journal must already be posted.
            $revenueJournal = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $account->id)
                ->where('source_event', 'finalized')
                ->where('status', 'posted')
                ->first();

            if (! $revenueJournal) {
                throw new RuntimeException(
                    'The IPD revenue journal is not posted.'
                );
            }

            // Restrict the first version to uncomplicated
            // self-paying bills.
            $account->load([
                'advances',
                'payments',
                'refunds',
                'mhisClaims',
                'mhisReceipts',
                'mhisAdjustments',
                'staffMedicalBenefitTransactions',
                'charityAdjustments',
            ]);

            if (
                $account->mhisClaims->isNotEmpty()
                || $account->mhisReceipts->isNotEmpty()
                || $account->mhisAdjustments->isNotEmpty()
                || $account->staffMedicalBenefitTransactions->isNotEmpty()
                || $account->charityAdjustments->isNotEmpty()
                || $account->payments->isNotEmpty()
                || $account->refunds->isNotEmpty()
            ) {
                throw new RuntimeException(
                    'This bill requires an extended accounting workflow.'
                );
            }

            $advances = $account->advances
                ->where('status', 'active');

            if ($advances->isEmpty()) {
                return null;
            }

            // Calculate amounts using integer paise.
            $totalAdvancePaise = 0;

            foreach ($advances as $advance) {
                if ($advance->source_type !== null) {
                    throw new RuntimeException(
                        'Source-linked advances require reconciliation.'
                    );
                }

                $receiptJournal = JournalEntry::query()
                    ->where('source_type', 'ip_billing_advance')
                    ->where('source_id', $advance->id)
                    ->where('source_event', 'received')
                    ->where('status', 'posted')
                    ->first();

                if (! $receiptJournal) {
                    throw new RuntimeException(
                        'Advance receipt journal missing: '
                        .$advance->receipt_no
                    );
                }

                $totalAdvancePaise += $this->toPaise(
                    (string) $advance->amount
                );
            }

            $billPaise = $this->toPaise(
                (string) $account->net_amount
            );

            if ($billPaise <= 0) {
                return null;
            }

            $applicationPaise = min(
                $totalAdvancePaise,
                $billPaise
            );

            if ($applicationPaise <= 0) {
                return null;
            }

            $accounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', ['2100', '1200'])
                ->get()
                ->keyBy('account_code');

            foreach (['2100', '1200'] as $code) {
                $gl = $accounts->get($code);

                if (
                    ! $gl
                    || ! $gl->is_active
                    || ! $gl->allow_posting
                ) {
                    throw new RuntimeException(
                        'GL account '.$code.' is unavailable.'
                    );
                }
            }

            $amount = number_format(
                $applicationPaise / 100,
                2,
                '.',
                ''
            );

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IPAPPLY-'.$account->id,
                'journal_date' => now()->toDateString(),
                'source_type' => $sourceType,
                'source_id' => $account->id,
                'source_event' => $sourceEvent,
                'reference_no' => $account->final_bill_no,
                'narration' => 'IPD advance applied to final bill',
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' => $accounts['2100']->id,
                'department_id' => null,
                'debit' => $amount,
                'credit' => '0.00',
                'reference_no' => $account->final_bill_no,
                'description' => 'Patient advance liability applied',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,
                'general_ledger_account_id' => $accounts['1200']->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $amount,
                'reference_no' => $account->final_bill_no,
                'description' => 'Reduction of patient receivable',
            ]);

            return $this->postingService->post($journal);
        });
    }

    private function toPaise(string $amount): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new RuntimeException(
                'Invalid accounting amount: '.$amount
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');
    }
}
