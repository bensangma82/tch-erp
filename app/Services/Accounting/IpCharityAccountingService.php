<?php

namespace App\Services\Accounting;

use App\Models\CharityAdjustment;
use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpCharityAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function post(CharityAdjustment $adjustment): ?JournalEntry
    {
        return DB::transaction(function () use ($adjustment) {
            $adjustment = CharityAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($adjustment->status !== 'applied') {
                throw new RuntimeException('Charity must be applied before accounting.');
            }

            if (! $adjustment->ip_billing_account_id) {
                return null;
            }

            $account = IpBillingAccount::query()
                ->whereKey($adjustment->ip_billing_account_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->status !== 'finalized') {
                return null;
            }

            $sales = JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $account->id)
                ->where('source_event', 'finalized')
                ->where('status', 'posted')
                ->first();

            if (! $sales || ! $account->final_bill_no) {
                throw new RuntimeException('Posted IPD Sales journal required.');
            }

            $existing = JournalEntry::query()
                ->where('source_type', 'charity_adjustment')
                ->where('source_id', (string) $adjustment->id)
                ->where('source_event', 'applied')
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw new RuntimeException('Unposted charity journal already exists.');
                }

                return $existing;
            }

            $amount = (string) $adjustment->approved_amount;

            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)
                || (float) $amount <= 0) {
                throw new RuntimeException('Invalid charity amount.');
            }

            $accounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', ['6114', '1200'])
                ->get()
                ->keyBy('account_code');

            foreach (['6114', '1200'] as $code) {
                $gl = $accounts->get($code);

                if (! $gl || ! $gl->is_active || ! $gl->allow_posting) {
                    throw new RuntimeException('GL '.$code.' is unavailable.');
                }
            }

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IPCHAR-'.$adjustment->id,
                'journal_date' => $adjustment->applied_at
                    && $adjustment->applied_at->greaterThan($account->finalized_at)
                        ? $adjustment->applied_at->toDateString()
                        : $account->finalized_at->toDateString(),
                'source_type' => 'charity_adjustment',
                'source_id' => (string) $adjustment->id,
                'source_event' => 'applied',
                'reference_no' => $account->final_bill_no,
                'narration' => 'IPD charity adjustment '.$account->final_bill_no,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ([
                [1, '6114', $amount, '0.00'],
                [2, '1200', '0.00', $amount],
            ] as [$lineNo, $code, $debit, $credit]) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $lineNo,
                    'general_ledger_account_id' => $accounts[$code]->id,
                    'department_id' => null,
                    'debit' => $debit,
                    'credit' => $credit,
                    'reference_no' => $account->final_bill_no,
                    'description' => 'IPD charity / patient assistance',
                ]);
            }

            return $this->postingService->post($journal);
        });
    }

    public function postAppliedForAccount(IpBillingAccount $account): void
    {
        foreach ($account->charityAdjustments()
            ->where('status', 'applied')
            ->orderBy('id')
            ->get() as $adjustment) {
            $this->post($adjustment);
        }
    }
}
