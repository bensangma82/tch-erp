<?php

namespace App\Services\Accounting;

use App\Models\FinanceAccount;
use App\Models\FinanceVoucher;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinanceTransferAccountingService
{
    public function __construct(
        private readonly JournalPostingService $postingService
    ) {}

    public function post(FinanceVoucher $financeVoucher): JournalEntry
    {
        return DB::transaction(function () use ($financeVoucher) {
            $voucher = FinanceVoucher::query()
                ->whereKey($financeVoucher->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($voucher->voucher_type !== 'transfer'
                || $voucher->status !== 'draft') {
                throw new RuntimeException(
                    'Only draft transfer vouchers can be posted.'
                );
            }

            if (
                $voucher->finance_account_id === null
                || $voucher->destination_account_id === null
                || (int) $voucher->finance_account_id
                    === (int) $voucher->destination_account_id
            ) {
                throw new RuntimeException(
                    'Distinct source and destination accounts are required.'
                );
            }

            $existing = JournalEntry::query()
                ->where('finance_voucher_id', $voucher->id)
                ->exists();

            if ($existing) {
                throw new RuntimeException(
                    'A journal already exists for this Finance Voucher.'
                );
            }

            $accounts = FinanceAccount::with('generalLedgerAccount')
                ->whereIn('id', [
                    $voucher->finance_account_id,
                    $voucher->destination_account_id,
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $source = $accounts->get($voucher->finance_account_id);
            $destination = $accounts->get($voucher->destination_account_id);

            foreach ([$source, $destination] as $account) {
                if (
                    ! $account
                    || ! $account->is_active
                    || ! in_array(
                        strtolower((string) $account->account_type),
                        ['cash', 'bank'],
                        true
                    )
                    || ! $account->generalLedgerAccount
                    || ! $account->generalLedgerAccount->is_active
                    || ! $account->generalLedgerAccount->allow_posting
                ) {
                    throw new RuntimeException(
                        'Transfer account or GL mapping is invalid.'
                    );
                }
            }

            if (
                $source->general_ledger_account_id
                === $destination->general_ledger_account_id
            ) {
                throw new RuntimeException(
                    'Source and destination GL accounts must differ.'
                );
            }

            $value = (string) $voucher->amount;

            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                throw new RuntimeException('Invalid transfer amount.');
            }

            [$whole, $fraction] = array_pad(
                explode('.', $value, 2), 2, '0'
            );

            $paise = ((int) $whole * 100)
                + (int) str_pad($fraction, 2, '0');

            if ($paise <= 0) {
                throw new RuntimeException(
                    'Transfer amount must be positive.'
                );
            }

            $amount = number_format($paise / 100, 2, '.', '');

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-FTR-'.$voucher->id,
                'journal_date' => $voucher->voucher_date,
                'finance_voucher_id' => $voucher->id,
                'source_type' => 'finance_voucher',
                'source_id' => $voucher->id,
                'source_event' => 'transfer_posted',
                'reference_no' => $voucher->voucher_no,
                'narration' => 'Internal fund transfer '.$voucher->voucher_no,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' =>
                    $destination->general_ledger_account_id,
                'department_id' => null,
                'debit' => $amount,
                'credit' => '0.00',
                'reference_no' => $voucher->voucher_no,
                'description' => 'Transfer received: '.$destination->name,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,
                'general_ledger_account_id' =>
                    $source->general_ledger_account_id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $amount,
                'reference_no' => $voucher->voucher_no,
                'description' => 'Transfer sent: '.$source->name,
            ]);

            $this->postingService->post($journal);

            $voucher->status = 'posted';
            $voucher->posted_by = auth()->id();
            $voucher->posted_at = now();
            $voucher->save();

            return $journal->fresh('lines');
        });
    }
}
