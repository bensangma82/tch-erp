<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAdvance;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpAdvanceAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function postAdvance(
        IpBillingAdvance $advance
    ): JournalEntry {
        return DB::transaction(function () use ($advance) {

            $advance = IpBillingAdvance::query()
                ->whereKey($advance->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Posting
            |--------------------------------------------------------------------------
            */

            $existing = JournalEntry::query()
                ->where('source_type', 'ip_billing_advance')
                ->where('source_id', $advance->id)
                ->where('source_event', 'received')
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw new RuntimeException(
                        'An unposted IPD advance journal already exists.'
                    );
                }

                return $existing;
            }

            if ($advance->status !== 'active') {
                throw new RuntimeException(
                    'Only active IPD advances can be journalized.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pharmacy-linked Advances
            |--------------------------------------------------------------------------
            | These require reconciliation against pharmacy collections
            | before automatic posting is enabled.
            |--------------------------------------------------------------------------
            */

            if ($advance->source_type !== null) {
                throw new RuntimeException(
                    'Source-linked IPD advances require separate accounting review.'
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
                trim((string) $advance->payment_mode)
            );

            if (! isset($accountCodes[$paymentMode])) {
                throw new RuntimeException(
                    'Unsupported IPD advance payment mode.'
                );
            }

            $debitCode = $accountCodes[$paymentMode];

            /*
            |--------------------------------------------------------------------------
            | Validate Amount
            |--------------------------------------------------------------------------
            */

            $amount = (string) $advance->amount;

            if (! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $amount
            )) {
                throw new RuntimeException(
                    'Invalid IPD advance amount.'
                );
            }

            [$whole, $fraction] = array_pad(
                explode('.', $amount, 2),
                2,
                '0'
            );

            $amountCents = ((int) $whole * 100)
                + (int) str_pad($fraction, 2, '0');

            if ($amountCents <= 0) {
                throw new RuntimeException(
                    'IPD advance amount must be positive.'
                );
            }

            $amount = number_format(
                $amountCents / 100,
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
                    '2100',
                ])
                ->get()
                ->keyBy('account_code');

            foreach ([$debitCode, '2100'] as $code) {
                $account = $accounts->get($code);

                if (
                    ! $account
                    || ! $account->is_active
                    || ! $account->allow_posting
                ) {
                    throw new RuntimeException(
                        'GL account '.$code.' is unavailable.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Create Advance Receipt Journal
            |--------------------------------------------------------------------------
            */

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IPADV-'.$advance->id,
                'journal_date' => $advance->payment_date,
                'source_type' => 'ip_billing_advance',
                'source_id' => $advance->id,
                'source_event' => 'received',
                'reference_no' => $advance->receipt_no,
                'narration' =>
                    'IPD advance received: '.$advance->receipt_no,
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
                'reference_no' => $advance->receipt_no,
                'description' =>
                    strtoupper($paymentMode).' IPD advance received',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Credit Patient Advances Liability
            |--------------------------------------------------------------------------
            */

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,
                'general_ledger_account_id' => $accounts['2100']->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $amount,
                'reference_no' => $advance->receipt_no,
                'description' => 'Patient advance liability',
            ]);

            return $this->postingService->post($journal);
        });
    }
}