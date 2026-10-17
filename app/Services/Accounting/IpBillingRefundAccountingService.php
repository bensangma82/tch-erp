<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingRefund;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpBillingRefundAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}


    public function post(IpBillingRefund $refund): JournalEntry
    {
        return DB::transaction(function () use ($refund) {
            $refund = IpBillingRefund::query()
                ->whereKey($refund->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = JournalEntry::query()
                ->where('source_type', 'ip_billing_refund')
                ->where('source_id', (string) $refund->id)
                ->where('source_event', 'paid')
                ->first();

            if ($existing) {
                if ($existing->status === 'posted') {
                    return $existing;
                }

                throw new RuntimeException(
                    'Incomplete refund journal already exists.'
                );
            }

            if ($refund->status !== 'active') {
                throw new RuntimeException(
                    'Only active refunds may be posted.'
                );
            }

            $account = $refund->billingAccount()
                ->lockForUpdate()
                ->firstOrFail();

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
                $account->status !== 'finalized'
                || $account->payments->isNotEmpty()
                || $account->mhisClaims->isNotEmpty()
                || $account->mhisReceipts->isNotEmpty()
                || $account->mhisAdjustments->isNotEmpty()
                || $account->staffMedicalBenefitTransactions->isNotEmpty()
                || $account->charityAdjustments->isNotEmpty()
            ) {
                throw new RuntimeException(
                    'Refund requires extended accounting reconciliation.'
                );
            }

            $advancePaise = 0;

            foreach ($account->advances->where('status', 'active') as $advance) {
                if ($advance->source_type !== null) {
                    throw new RuntimeException(
                        'Source-linked advances require reconciliation.'
                    );
                }

                $journal = JournalEntry::query()
                    ->where('source_type', 'ip_billing_advance')
                    ->where('source_id', (string) $advance->id)
                    ->where('source_event', 'received')
                    ->where('status', 'posted')
                    ->first();

                if (! $journal) {
                    throw new RuntimeException(
                        'Advance receipt journal missing.'
                    );
                }

                $advancePaise += $this->toPaise(
                    (string) $advance->amount
                );
            }

            if ($advancePaise <= 0) {
                throw new RuntimeException(
                    'No accounted patient advance exists.'
                );
            }


            if ($this->toPaise((string) $account->net_amount) > 0) {
                throw new RuntimeException(
                    'Refund requires reconciliation of applied advances.'
                );
            }

            $previousPaise = 0;

            foreach ($account->refunds as $prior) {
                if ($prior->id === $refund->id || $prior->status !== 'active') {
                    continue;
                }

                $posted = JournalEntry::query()
                    ->where('source_type', 'ip_billing_refund')
                    ->where('source_id', (string) $prior->id)
                    ->where('source_event', 'paid')
                    ->where('status', 'posted')
                    ->exists();

                if (! $posted) {
                    throw new RuntimeException(
                        'Previous refund accounting is incomplete.'
                    );
                }

                $previousPaise += $this->toPaise((string) $prior->amount);
            }

            $refundPaise = $this->toPaise((string) $refund->amount);

            if ($refundPaise > $advancePaise - $previousPaise) {
                throw new RuntimeException(
                    'Insufficient unapplied patient advance liability.'
                );
            }

            $creditCode = $this->paymentAccountCode(
                (string) $refund->payment_mode
            );

            $accounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', ['2100', $creditCode])
                ->get()
                ->keyBy('account_code');

            foreach (['2100', $creditCode] as $code) {
                $gl = $accounts->get($code);

                if (! $gl || ! $gl->is_active || ! $gl->allow_posting) {
                    throw new RuntimeException(
                        'GL account '.$code.' is unavailable.'
                    );
                }
            }

            $amount = number_format($refundPaise / 100, 2, '.', '');

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IPREF-'.$refund->id,
                'journal_date' => $refund->refund_date,
                'source_type' => 'ip_billing_refund',
                'source_id' => (string) $refund->id,
                'source_event' => 'paid',
                'reference_no' => $refund->refund_no,
                'narration' => 'IPD advance refund: '.$refund->refund_no,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ([
                [1, '2100', $amount, '0.00'],
                [2, $creditCode, '0.00', $amount],
            ] as [$line, $code, $debit, $credit]) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $line,
                    'general_ledger_account_id' => $accounts[$code]->id,
                    'department_id' => null,
                    'debit' => $debit,
                    'credit' => $credit,
                    'reference_no' => $refund->refund_no,
                    'description' => 'IPD patient advance refund',
                ]);
            }

            return $this->postingService->post($journal);
        });
    }

    private function paymentAccountCode(string $mode): string
    {
        return match (strtolower($mode)) {
            'cash' => '1100',
            'upi' => '1110',
            'card' => '1120',
            default => throw new RuntimeException(
                'Unsupported refund payment mode.'
            ),
        };
    }

    private function toPaise(string $amount): int
    {
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw new RuntimeException('Invalid accounting amount.');
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2), 2, '0'
        );

        $paise = ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');

        if ($paise < 0) {
            throw new RuntimeException('Amount cannot be negative.');
        }

        return $paise;
    }

}
