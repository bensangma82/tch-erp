<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function postReceipt(Payment $payment): JournalEntry
    {
        return DB::transaction(function () use ($payment) {

            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Posting
            |--------------------------------------------------------------------------
            */

            $existing = JournalEntry::query()
                ->where('source_type', 'payment')
                ->where('source_id', $payment->id)
                ->where('source_event', 'received')
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw ValidationException::withMessages([
                        'journal' => 'An unposted receipt journal already exists.',
                    ]);
                }

                return $existing;
            }

            /*
            |--------------------------------------------------------------------------
            | Identify Payment Account
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
                throw ValidationException::withMessages([
                    'payment_mode' => 'Unsupported accounting payment mode.',
                ]);
            }

            $debitCode = $accountCodes[$paymentMode];

            /*
            |--------------------------------------------------------------------------
            | Validate Payment Amount
            |--------------------------------------------------------------------------
            */

            $amount = (string) $payment->amount;

            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
                throw ValidationException::withMessages([
                    'amount' => 'Invalid payment amount.',
                ]);
            }

            [$whole, $fraction] = array_pad(
                explode('.', $amount, 2),
                2,
                '0'
            );

            $amountCents = ((int) $whole * 100)
                + (int) str_pad($fraction, 2, '0');

            if ($amountCents <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            $amount = number_format(
                $amountCents / 100,
                2,
                '.',
                ''
            );

            /*
            |--------------------------------------------------------------------------
            | Validate General Ledger Accounts
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
                $account = $accounts->get($code);

                if (
                    ! $account
                    || ! $account->is_active
                    || ! $account->allow_posting
                ) {
                    throw ValidationException::withMessages([
                        'account' => 'GL account '.$code.' is unavailable.',
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Invoice Accounting
            |--------------------------------------------------------------------------
            */

            $invoiceJournal = JournalEntry::query()
                ->where('source_type', 'invoice')
                ->where('source_id', $payment->invoice_id)
                ->where('source_event', 'issued')
                ->where('status', 'posted')
                ->first();

            if (! $invoiceJournal) {
                throw ValidationException::withMessages([
                    'journal' => 'The invoice must be journalized before its receipt.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Receipt Journal
            |--------------------------------------------------------------------------
            */

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-PAY-'.$payment->id,
                'journal_date' => $payment->payment_date,
                'source_type' => 'payment',
                'source_id' => $payment->id,
                'source_event' => 'received',
                'reference_no' => $payment->receipt_no,
                'narration' => 'Payment received: '.$payment->receipt_no,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Debit Cash / UPI / Card Clearing
            |--------------------------------------------------------------------------
            */

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' => $accounts[$debitCode]->id,
                'department_id' => null,
                'debit' => $amount,
                'credit' => '0.00',
                'reference_no' => $payment->receipt_no,
                'description' => strtoupper($paymentMode).' receipt',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Credit Patient Receivables
            |--------------------------------------------------------------------------
            */

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,
                'general_ledger_account_id' => $accounts['1200']->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $amount,
                'reference_no' => $payment->receipt_no,
                'description' => 'Patient receivable settlement',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validate and Post
            |--------------------------------------------------------------------------
            */

            return $this->postingService->post($journal);
        });
    }
}