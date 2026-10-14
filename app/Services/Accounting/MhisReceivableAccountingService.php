<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingMhisClaim;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MhisReceivableAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function recognize(
        IpBillingMhisClaim $claim
    ): JournalEntry {
        return DB::transaction(function () use ($claim) {

            if (! auth()->check()) {
                throw new RuntimeException(
                    'An authenticated user is required.'
                );
            }

            // Lock the billing account before the claim.
            $account = $claim->billingAccount()
                ->lockForUpdate()
                ->firstOrFail();

            $claim = IpBillingMhisClaim::query()
                ->whereKey($claim->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $claim->ip_billing_account_id !== $account->id
            ) {
                throw new RuntimeException(
                    'MHIS claim does not match the billing account.'
                );
            }

            if (
                $account->status !== 'finalized'
                || ! $account->final_bill_no
            ) {
                throw new RuntimeException(
                    'The IPD bill must be finalized first.'
                );
            }

            if (
                $claim->status !== 'approved'
                || ! $claim->approval_date
            ) {
                throw new RuntimeException(
                    'Formal MHIS approval is required.'
                );
            }

            $toPaise = static function ($value): int {
                $value = (string) $value;

                if (! preg_match(
                    '/^\d+(?:\.\d{1,2})?$/',
                    $value
                )) {
                    throw new RuntimeException(
                        'Invalid accounting amount.'
                    );
                }

                [$whole, $fraction] = array_pad(
                    explode('.', $value, 2),
                    2,
                    '0'
                );

                return ((int) $whole * 100)
                    + (int) str_pad($fraction, 2, '0');
            };

            $approvedPaise = $toPaise(
                $claim->approved_amount
            );

            $billPaise = $toPaise(
                $account->net_amount
            );

            if (
                $approvedPaise <= 0
                || $approvedPaise > $billPaise
            ) {
                throw new RuntimeException(
                    'MHIS approval exceeds the eligible bill amount.'
                );
            }

            // Require a posted final-bill journal.
            $finalJournal = JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $account->id)
                ->where('source_event', 'finalized')
                ->where('status', 'posted')
                ->first();

            if (! $finalJournal) {
                throw new RuntimeException(
                    'IPD final-bill journal is not posted.'
                );
            }

            // Existing recognition is immutable.
            $existing = JournalEntry::query()
                ->where('source_type', 'ip_billing_mhis_claim')
                ->where('source_id', (string) $claim->id)
                ->where('source_event', 'approved')
                ->first();

            if ($existing) {
                throw new RuntimeException(
                    'This MHIS claim already has a recognition journal. '
                    .'Use the future adjustment workflow for revisions.'
                );
            }

            // Prevent overlapping or unreconciled accounting.
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
                $account->advances->isNotEmpty()
                || $account->refunds->isNotEmpty()
                || $account->mhisReceipts->isNotEmpty()
                || $account->mhisAdjustments->isNotEmpty()
                || $account->staffMedicalBenefitTransactions->isNotEmpty()
                || $account->charityAdjustments->isNotEmpty()
                || $account->mhisClaims->count() !== 1
            ) {
                throw new RuntimeException(
                    'This bill requires extended MHIS reconciliation.'
                );
            }

            // Verify existing patient payment journals.
            foreach ($account->payments as $payment) {
                if ($payment->status !== 'active') {
                    throw new RuntimeException(
                        'Inactive patient payments require reconciliation.'
                    );
                }

                $paymentJournal = JournalEntry::query()
                    ->where('source_type', 'ip_billing_payment')
                    ->where('source_id', (string) $payment->id)
                    ->where('source_event', 'received')
                    ->where('status', 'posted')
                    ->first();

                if (! $paymentJournal) {
                    throw new RuntimeException(
                        'A patient payment has no posted accounting journal.'
                    );
                }
            }

            // Block accounts with other posted receivable adjustments.
            $otherJournals = JournalEntry::query()
                ->where("source_type", "ip_billing_account")
                ->where("source_id", (string) $account->id)
                ->where("status", "posted")
                ->where("source_event", "!=", "finalized")
                ->exists();

            if ($otherJournals) {
                throw new RuntimeException(
                    "Existing IPD adjustments require reconciliation."
                );
            }

            $accounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', ['1200', '1210'])
                ->get()
                ->keyBy('account_code');

            foreach (['1200', '1210'] as $code) {
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

            // Confirm that the original patient receivable
            // equals the amount recognized on the final bill.
            $originalDebitPaise = 0;

            foreach ($finalJournal->lines as $line) {
                if (
                    $line->general_ledger_account_id
                    === $accounts['1200']->id
                ) {
                    $originalDebitPaise += $toPaise(
                        $line->debit
                    );

                    if ($toPaise($line->credit) !== 0) {
                        throw new RuntimeException(
                            'Unexpected final-bill receivable credit.'
                        );
                    }
                }
            }

            if ($originalDebitPaise !== $billPaise) {
                throw new RuntimeException(
                    'Final-bill receivable does not reconcile.'
                );
            }

            // MHIS GL 1200 balance reconciliation.
            $remainingPaise = $originalDebitPaise;

            foreach ($account->payments as $payment) {
                $paymentJournal = JournalEntry::query()
                    ->where('source_type', 'ip_billing_payment')
                    ->where('source_id', (string) $payment->id)
                    ->where('source_event', 'received')
                    ->where('status', 'posted')
                    ->with('lines')
                    ->firstOrFail();

                $paymentCreditPaise = 0;
                $paymentDebitPaise = 0;

                foreach ($paymentJournal->lines as $line) {
                    if ($line->general_ledger_account_id === $accounts['1200']->id) {
                        $paymentCreditPaise += $toPaise($line->credit);
                        $paymentDebitPaise += $toPaise($line->debit);
                    }
                }

                if (
                    $paymentDebitPaise !== 0
                    || $paymentCreditPaise !== $toPaise($payment->amount)
                ) {
                    throw new RuntimeException(
                        'Patient payment journal does not reconcile with GL 1200.'
                    );
                }

                $remainingPaise -= $paymentCreditPaise;
            }

            if (
                $remainingPaise < 0
                || $approvedPaise > $remainingPaise
            ) {
                throw new RuntimeException(
                    'Approved MHIS amount exceeds remaining Patient Receivables.'
                );
            }

            $amount = number_format(
                $approvedPaise / 100,
                2,
                '.',
                ''
            );

            $reference = $claim->claim_no
                ?: $account->final_bill_no;

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-MHIS-'.$claim->id,
                'journal_date' => $claim->approval_date,
                'source_type' => 'ip_billing_mhis_claim',
                'source_id' => (string) $claim->id,
                'source_event' => 'approved',
                'reference_no' => $reference,
                'narration' => 'MHIS entitlement recognized',
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' =>
                    $accounts['1210']->id,
                'department_id' => null,
                'debit' => $amount,
                'credit' => '0.00',
                'reference_no' => $reference,
                'description' => 'Approved MHIS receivable',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,
                'general_ledger_account_id' =>
                    $accounts['1200']->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $amount,
                'reference_no' => $reference,
                'description' =>
                    'Transfer from patient receivable to MHIS',
            ]);

            return $this->postingService->post($journal);
        });
    }
}