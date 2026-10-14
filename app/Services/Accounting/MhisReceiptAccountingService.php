<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingMhisClaim;
use App\Models\IpBillingMhisReceipt;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MhisReceiptAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function record(
        IpBillingMhisReceipt $receipt
    ): JournalEntry {
        return DB::transaction(function () use ($receipt) {

            if (! auth()->check()) {
                throw new RuntimeException(
                    'An authenticated user is required.'
                );
            }

            // Preserve the existing account -> claim -> receipt
            // lock order used by the controller.
            $account = $receipt->billingAccount()
                ->lockForUpdate()
                ->firstOrFail();

            $claim = IpBillingMhisClaim::query()
                ->whereKey($receipt->ip_billing_mhis_claim_id)
                ->lockForUpdate()
                ->firstOrFail();

            $receipt = IpBillingMhisReceipt::query()
                ->whereKey($receipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $receipt->status !== 'active'
                || (int) $receipt->ip_billing_account_id !== (int) $account->id
                || (int) $claim->ip_billing_account_id !== (int) $account->id
                || (int) $receipt->ip_billing_mhis_claim_id !== (int) $claim->id
            ) {
                throw new RuntimeException(
                    'MHIS receipt, claim or billing account mismatch.'
                );
            }

            if (
                $account->status !== 'finalized'
                || ! $account->final_bill_no
            ) {
                throw new RuntimeException(
                    'A finalized IPD bill is required.'
                );
            }

            // Final reimbursement may have changed claim status
            // from approved to settled.
            if (
                ! in_array(
                    $claim->status,
                    ['approved', 'settled'],
                    true
                )
                || ! $claim->approval_date
            ) {
                throw new RuntimeException(
                    'Formal MHIS approval is required.'
                );
            }

            // Convert monetary amounts to integer paise.
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

            $amountPaise = $toPaise($receipt->amount);

            $approvedPaise = $toPaise(
                $claim->approved_amount
            );

            if (
                $amountPaise <= 0
                || $approvedPaise <= 0
            ) {
                throw new RuntimeException(
                    'MHIS accounting amounts must be positive.'
                );
            }

            // Duplicate journal prevention.
            // Database unique constraints provide an additional
            // layer of protection.
            $existing = JournalEntry::query()
                ->where(
                    'source_type',
                    'ip_billing_mhis_receipt'
                )
                ->where('source_id', (string) $receipt->id)
                ->where('source_event', 'received')
                ->first();

            if ($existing) {
                throw new RuntimeException(
                    'This MHIS receipt already has an accounting journal.'
                );
            }

            // Require the original posted MHIS recognition.
            $recognition = JournalEntry::query()
                ->where(
                    'source_type',
                    'ip_billing_mhis_claim'
                )
                ->where('source_id', (string) $claim->id)
                ->where('source_event', 'approved')
                ->where('status', 'posted')
                ->with('lines')
                ->first();

            if (! $recognition) {
                throw new RuntimeException(
                    'The posted MHIS approval journal is required.'
                );
            }

            $accounts = GeneralLedgerAccount::query()
                ->whereIn(
                    'account_code',
                    ['1130', '1200', '1210']
                )
                ->get()
                ->keyBy('account_code');

            foreach (['1130', '1200', '1210'] as $code) {

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

            // Reconcile recognition attributable to this claim.
            $recognition1210Dr = 0;
            $recognition1210Cr = 0;
            $recognition1200Dr = 0;
            $recognition1200Cr = 0;

            foreach ($recognition->lines as $line) {

                if (
                    (int) $line->general_ledger_account_id
                    === (int) $accounts['1210']->id
                ) {
                    $recognition1210Dr += $toPaise($line->debit);
                    $recognition1210Cr += $toPaise($line->credit);
                }

                if (
                    (int) $line->general_ledger_account_id
                    === (int) $accounts['1200']->id
                ) {
                    $recognition1200Dr += $toPaise($line->debit);
                    $recognition1200Cr += $toPaise($line->credit);
                }
            }

            if (
                $recognition->lines->count() !== 2
                || $recognition1210Dr !== $approvedPaise
                || $recognition1210Cr !== 0
                || $recognition1200Cr !== $approvedPaise
                || $recognition1200Dr !== 0
            ) {
                throw new RuntimeException(
                    'MHIS recognition journal does not reconcile.'
                );
            }

            // Retrieve all receipt records for the claim.
            $receipts = IpBillingMhisReceipt::query()
                ->where(
                    'ip_billing_mhis_claim_id',
                    $claim->id
                )
                ->orderBy('id')
                ->get();

            $priorCreditsPaise = 0;
            $activeReceiptsPaise = 0;
            $foundCurrent = false;

            foreach ($receipts as $previous) {

                if (
                    $previous->status !== 'active'
                    || (int) $previous->ip_billing_account_id
                    !== (int) $account->id
                ) {
                    throw new RuntimeException(
                        'MHIS receipts require reconciliation before further posting.'
                    );
                }

                $previousPaise = $toPaise(
                    $previous->amount
                );

                if ($previousPaise <= 0) {
                    throw new RuntimeException(
                        'Invalid previous MHIS receipt amount.'
                    );
                }

                $activeReceiptsPaise += $previousPaise;

                if (
                    (int) $previous->id
                    === (int) $receipt->id
                ) {
                    $foundCurrent = true;

                    // Current receipt is not yet journaled.
                    continue;
                }

                $priorJournal = JournalEntry::query()
                    ->where(
                        'source_type',
                        'ip_billing_mhis_receipt'
                    )
                    ->where(
                        'source_id',
                        (string) $previous->id
                    )
                    ->where('source_event', 'received')
                    ->with('lines')
                    ->first();

                if (
                    ! $priorJournal
                    || $priorJournal->status !== 'posted'
                ) {
                    throw new RuntimeException(
                        'An earlier MHIS receipt has no posted journal.'
                    );
                }

                $bankDr = 0;
                $bankCr = 0;
                $mhisDr = 0;
                $mhisCr = 0;

                foreach ($priorJournal->lines as $line) {

                    if (
                        (int) $line->general_ledger_account_id
                        === (int) $accounts['1130']->id
                    ) {
                        $bankDr += $toPaise($line->debit);
                        $bankCr += $toPaise($line->credit);
                    }

                    if (
                        (int) $line->general_ledger_account_id
                        === (int) $accounts['1210']->id
                    ) {
                        $mhisDr += $toPaise($line->debit);
                        $mhisCr += $toPaise($line->credit);
                    }
                }

                if (
                    $priorJournal->lines->count() !== 2
                    || $bankDr !== $previousPaise
                    || $bankCr !== 0
                    || $mhisCr !== $previousPaise
                    || $mhisDr !== 0
                ) {
                    throw new RuntimeException(
                        'An earlier MHIS receipt journal does not reconcile.'
                    );
                }

                $priorCreditsPaise += $mhisCr;
            }

            if (! $foundCurrent) {
                throw new RuntimeException(
                    'Current MHIS receipt is missing from the claim.'
                );
            }

            // Outstanding claim-specific GL 1210 balance.
            $outstandingPaise =
                $recognition1210Dr - $priorCreditsPaise;

            if (
                $outstandingPaise <= 0
                || $amountPaise > $outstandingPaise
                || $activeReceiptsPaise > $approvedPaise
            ) {
                throw new RuntimeException(
                    'MHIS reimbursement exceeds the claim-specific GL 1210 balance.'
                );
            }

            // Validate partial versus final settlement.
            $shouldBeSettled =
                $activeReceiptsPaise === $approvedPaise;

            if (
                ($claim->status === 'settled')
                !== $shouldBeSettled
            ) {
                throw new RuntimeException(
                    'MHIS settlement status does not reconcile with active receipts.'
                );
            }

            $amount = number_format(
                $amountPaise / 100,
                2,
                '.',
                ''
            );

            $reference = $receipt->transaction_reference
                ?: $receipt->bank_reference
                ?: $receipt->payment_reference
                ?: $claim->claim_no
                ?: $account->final_bill_no;

            // Create draft journal.
            $journal = JournalEntry::create([
                'journal_no' =>
                    'JRN-MHISRCPT-'.$receipt->id,

                'journal_date' =>
                    $receipt->receipt_date,

                'source_type' =>
                    'ip_billing_mhis_receipt',

                'source_id' =>
                    (string) $receipt->id,

                'source_event' =>
                    'received',

                'reference_no' => $reference,

                'narration' =>
                    'MHIS reimbursement received',

                'status' => 'draft',

                'created_by' => auth()->id(),
            ]);

            // Debit Bank Settlement Account.
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,

                'general_ledger_account_id' =>
                    $accounts['1130']->id,

                'department_id' => null,

                'debit' => $amount,
                'credit' => '0.00',

                'reference_no' => $reference,

                'description' =>
                    'MHIS reimbursement received in bank',
            ]);

            // Credit MHIS Receivables.
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,

                'general_ledger_account_id' =>
                    $accounts['1210']->id,

                'department_id' => null,

                'debit' => '0.00',
                'credit' => $amount,

                'reference_no' => $reference,

                'description' =>
                    'Settlement of MHIS receivable',
            ]);

            // Validate and post the balanced journal.
            return $this->postingService->post($journal);
        });
    }
}
