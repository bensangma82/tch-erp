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

            // Resolve the posting actor.
// Prefer the authenticated user during normal HTTP requests.
// For explicitly authorised service calls, retain the
// previously assigned posting user.

$authenticatedUserId = auth()->id();

if (
    $authenticatedUserId !== null &&
    $journal->posted_by !== null &&
    (int) $authenticatedUserId !== (int) $journal->posted_by
) {
    throw ValidationException::withMessages([
        'journal' => 'Posting user does not match the authorised verifier.',
    ]);
}

$postingUserId = $authenticatedUserId
    ?? $journal->posted_by;

if (
    $postingUserId === null ||
    ! \App\Models\User::query()->whereKey($postingUserId)->exists()
) {
    throw ValidationException::withMessages([
        'journal' => 'An authorised posting user is required.',
    ]);
}

$journal->posted_by = $postingUserId;
$journal->posted_at = now();
$journal->status = 'posted';
$journal->save();

            return $journal->fresh('lines');
        });
    }
}