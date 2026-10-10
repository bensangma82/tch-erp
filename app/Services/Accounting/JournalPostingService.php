<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalPostingService
{
    public function post(JournalEntry $journal): JournalEntry
    {
        return DB::transaction(function () use ($journal) {
            $journal = JournalEntry::query()
                ->whereKey($journal->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($journal->status !== 'draft') {
                throw ValidationException::withMessages([
                    'journal' => 'Only draft journals can be posted.',
                ]);
            }

           $lines = $journal->lines()
           ->lockForUpdate()
           ->get();
            if ($lines->count() < 2) {
                throw ValidationException::withMessages([
                    'journal' => 'At least two journal lines are required.',
                ]);
            }

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $account = GeneralLedgerAccount::query()
                    ->whereKey($line->general_ledger_account_id)
                    ->firstOrFail();

                if (! $account->is_active || ! $account->allow_posting) {
                    throw ValidationException::withMessages([
                        'journal' => 'An inactive or non-posting ledger account was selected.',
                    ]);
                }

                $debit = (int) round(((float) $line->debit) * 100);
                $credit = (int) round(((float) $line->credit) * 100);

                if (
                    ($debit <= 0 && $credit <= 0) ||
                    ($debit > 0 && $credit > 0)
                ) {
                    throw ValidationException::withMessages([
                        'journal' => 'Each line must contain either a debit or a credit.',
                    ]);
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            if ($totalDebit !== $totalCredit) {
                throw ValidationException::withMessages([
                    'journal' => 'Journal debits and credits must balance.',
                ]);
            }

            $journal->status = 'posted';
            $journal->posted_at = now();
            $journal->posted_by = auth()->id();
            $journal->save();

            return $journal->fresh('lines');
        });
    }
}