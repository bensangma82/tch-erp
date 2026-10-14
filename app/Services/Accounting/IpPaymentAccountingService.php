<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingPayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpPaymentAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function postReceipt(
        IpBillingPayment $payment
    ): JournalEntry {
        return DB::transaction(function () use ($payment) {

            $payment = IpBillingPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Posting
            |--------------------------------------------------------------------------
            */

            $existing = JournalEntry::query()
                ->where('source_type', 'ip_billing_payment')
                ->where('source_id', $payment->id)
                ->where('source_event', 'received')
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw new RuntimeException(
                        'An unposted IPD payment journal already exists.'
                    );
                }

                return $existing;
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Payment
            |--------------------------------------------------------------------------
            */

            if ($payment->status !== 'active') {
                throw new RuntimeException(
                    'Only active IPD payments can be journalized.'
                );
            }

            if (! auth()->check()) {
                throw new RuntimeException(
                    'An authenticated user is required.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Finalized IPD Bill
            |--------------------------------------------------------------------------
            */

            $account = $payment->billingAccount()->lockForUpdate()->first();

            if (! $account) {
                throw new RuntimeException(
                    'IPD billing account not found.'
                );
            }

            if ($account->status !== 'finalized') {
                throw new RuntimeException(
                    'The IPD bill must be finalized.'
                );
            }

            $revenueJournal = JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', $account->id)
                ->where('source_event', 'finalized')
                ->where('status', 'posted')
                ->first();

            if (! $revenueJournal) {
                throw new RuntimeException(
                    'The IPD revenue journal must be posted first.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Restrict to Self-Paying Bills
            |--------------------------------------------------------------------------
            */

            $account->load([
                'advances',
                'mhisClaims',
                'mhisReceipts',
                'mhisAdjustments',
                'staffMedicalBenefitTransactions',
                'charityAdjustments',
                'refunds',
            ]);

            $hasMhis = $account->mhisClaims->isNotEmpty();

            $mhisEligible = ! $hasMhis || (
                in_array($account->mhis_category, ["semi_private", "private"], true)
                && $account->mhisClaims->count() === 1
                && $account->mhisClaims->first()->status === "submitted"
                && (float) $account->mhisClaims->first()->expected_amount > 0
            );

            if (
                ! $mhisEligible
                || ($hasMhis && $account->advances->where("status", "active")->isNotEmpty())
                || $account->mhisReceipts->where("status", "active")->isNotEmpty()
                || $account->mhisAdjustments->where("status", "active")->isNotEmpty()
                || $account->staffMedicalBenefitTransactions->where("status", "active")->isNotEmpty()
                || $account->charityAdjustments->where("status", "applied")->isNotEmpty()
                || $account->refunds->where("status", "active")->isNotEmpty()
            ) {
                throw new RuntimeException(
                    "This IPD bill requires an extended accounting workflow."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Determine Collection Account
            |--------------------------------------------------------------------------
            */

            $accountCodes = [
                'cash' => '1100',
                'upi' => '1110',
                'card' => '1120',
            ];

            $paymentMode = strtolower(
                trim((string) $payment->payment_mode)
            );

            if (! isset($accountCodes[$paymentMode])) {
                throw new RuntimeException(
                    'Unsupported IPD payment mode.'
                );
            }

            $debitCode = $accountCodes[$paymentMode];

            /*
            |--------------------------------------------------------------------------
            | Validate Amount
            |--------------------------------------------------------------------------
            */

            $amount = (string) $payment->amount;

            if (! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $amount
            )) {
                throw new RuntimeException(
                    'Invalid IPD payment amount.'
                );
            }

            [$whole, $fraction] = array_pad(
                explode('.', $amount, 2),
                2,
                '0'
            );

            $amountPaise = ((int) $whole * 100)
                + (int) str_pad($fraction, 2, '0');

            if ($amountPaise <= 0) {
                throw new RuntimeException(
                    'IPD payment amount must be positive.'
                );
            }

            $amount = number_format(
                $amountPaise / 100,
                2,
                '.',
                ''
            );

            /*
            |--------------------------------------------------------------------------
            | Validate GL Accounts
            |--------------------------------------------------------------------------
            */

            $accounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', [
                    $debitCode,
                    '1200',
                ])
                ->get()
                ->keyBy('account_code');

            foreach ([$debitCode, '1200'] as $code) {
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

            if ($hasMhis) {
                $expectedCoverage = (float) $account->mhisClaims->first()->expected_amount;
                $patientSharePaise = max(
                    0,
                    (int) round(((float) $account->net_amount - $expectedCoverage) * 100)
                );

                $previousPaymentsPaise = (int) round(
                    (float) $account->payments()
                        ->where('status', 'active')
                        ->whereKeyNot($payment->id)
                        ->sum('amount') * 100
                );

                if ($amountPaise + $previousPaymentsPaise > $patientSharePaise) {
                    throw new RuntimeException(
                        'Payment exceeds the provisional MHIS patient contribution.'
                    );
                }
            }

            // GL 1200 receivable reconciliation
            $paymentIds = $account->payments()->pluck('id');
            $claimIds = $account->mhisClaims->pluck('id');

            $receivablePaise = 0;

            $journals = JournalEntry::query()
                ->where('status', 'posted')
                ->where(function ($query) use ($account, $paymentIds, $claimIds) {
                    $query->where(function ($q) use ($account) {
                        $q->where('source_type', 'ip_billing_account')
                            ->where('source_id', $account->id);
                    })->orWhere(function ($q) use ($paymentIds) {
                        $q->where('source_type', 'ip_billing_payment')
                            ->whereIn('source_id', $paymentIds);
                    })->orWhere(function ($q) use ($claimIds) {
                        $q->where('source_type', 'ip_billing_mhis_claim')
                            ->whereIn('source_id', $claimIds);
                    });
                })
                ->with(['lines' => function ($query) use ($accounts) {
                    $query->where(
                        'general_ledger_account_id',
                        $accounts['1200']->id
                    );
                }])
                ->get();

            foreach ($journals as $postedJournal) {
                foreach ($postedJournal->lines as $line) {
                    $receivablePaise += (int) round(
                        ((float) $line->debit - (float) $line->credit) * 100
                    );
                }
            }

            if ($amountPaise > $receivablePaise) {
                throw new RuntimeException(
                    'Payment exceeds the posted GL 1200 patient receivable balance.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Payment Journal
            |--------------------------------------------------------------------------
            */

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IPPAY-'.$payment->id,
                'journal_date' => $payment->payment_date,
                'source_type' => 'ip_billing_payment',
                'source_id' => $payment->id,
                'source_event' => 'received',
                'reference_no' => $payment->receipt_no,
                'narration' =>
                    'IPD patient payment received: '
                    .$payment->receipt_no,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Debit Cash / UPI / Card
            |--------------------------------------------------------------------------
            */

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' =>
                    $accounts[$debitCode]->id,
                'department_id' => null,
                'debit' => $amount,
                'credit' => '0.00',
                'reference_no' => $payment->receipt_no,
                'description' =>
                    strtoupper($paymentMode)
                    .' IPD patient payment',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Credit Patient Receivables
            |--------------------------------------------------------------------------
            */

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,
                'general_ledger_account_id' =>
                    $accounts['1200']->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $amount,
                'reference_no' => $payment->receipt_no,
                'description' =>
                    'Settlement of IPD patient receivable',
            ]);

            return $this->postingService->post($journal);
        });
    }
}
