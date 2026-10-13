<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Models\TallyLedgerMaster;

class TallyJournalExportService
{
    /**
     * Determine whether a posted journal can be
     * safely exported to TallyPrime.
     *
     * @return array{
     *     eligible: bool,
     *     errors: array<int, string>
     * }
     */
    public function checkEligibility(JournalEntry $journal): array
    {
        $errors = [];

        $journal->loadMissing([
            'lines.generalLedgerAccount',
            'financeVoucher.tallyExport',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Tally Ledger Synchronization Freshness
        |--------------------------------------------------------------------------
        */

        $company = trim((string) config('tally.company'));

        if ($company === '') {
            $errors[] = 'Tally company is not configured.';
        } else {
            $latestSync = TallyLedgerMaster::query()
                ->where('tally_company', $company)
                ->max('last_synced_at');

            if (
                ! $latestSync ||
                \Illuminate\Support\Carbon::parse($latestSync)
                    ->lt(now()->subHours(24))
            ) {
                $errors[] = 'Tally Ledger Master is outdated. '
                    .'Synchronize ledgers before exporting journals.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Journal posting status
        |--------------------------------------------------------------------------
        */

        if ($journal->status !== 'posted') {
            $errors[] = 'Only posted journals can be exported.';
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Journal Export
        |--------------------------------------------------------------------------
        | Any existing record blocks automatic retransmission,
        | including failed, sending and unknown exports.
        |--------------------------------------------------------------------------
        */

        if (
            TallyExport::query()
                ->where('journal_entry_id', $journal->id)
                ->exists()
        ) {
            $errors[] = 'This journal already has a Tally export record.';
        }

        /*
        |--------------------------------------------------------------------------
        | Linked Finance Voucher
        |--------------------------------------------------------------------------
        */

        if ($journal->financeVoucher !== null) {
            if ($journal->financeVoucher->origin === 'tally_import') {
                $errors[] = 'The linked voucher originated in Tally.';
            }

            if ($journal->financeVoucher->tallyExport !== null) {
                $errors[] = 'The linked Finance Voucher already has a Tally export record.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Journal Lines
        |--------------------------------------------------------------------------
        */

        if ($journal->lines->count() < 2) {
            $errors[] = 'Journal must contain at least two lines.';
        }

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($journal->lines as $line) {
            $account = $line->generalLedgerAccount;

            if (! $account) {
                $errors[] = 'GL account missing on line '.$line->line_no;
                continue;
            }

            if (! $account->is_active) {
                $errors[] = 'Inactive GL account: '.$account->account_code;
            }

            if (! $account->allow_posting) {
                $errors[] = 'GL account does not allow posting: '.$account->account_code;
            }


if (blank($account->tally_ledger_name)) {
    $errors[] = 'Missing Tally ledger mapping: '
        .$account->account_code;
} else {
    $ledgerName = trim($account->tally_ledger_name);
    $company = trim((string) config('tally.company'));

   $ledgerExists = TallyLedgerMaster::query()
    ->where('tally_company', $company)
    ->whereRaw(
        'LOWER(TRIM(ledger_name)) = LOWER(?)',
        [$ledgerName]
    )
    ->where(
        'last_synced_at',
        TallyLedgerMaster::query()
            ->where('tally_company', $company)
            ->selectRaw('MAX(last_synced_at)')
    )
    ->exists();

    if (! $ledgerExists) {
        $errors[] = 'Tally ledger not found in synchronized master: '
            .$ledgerName
            .' (GL '.$account->account_code.')';
    }
}


            $debit = (int) round((float) $line->debit * 100);
            $credit = (int) round((float) $line->credit * 100);

            if (
                $debit < 0 ||
                $credit < 0 ||
                ($debit > 0 && $credit > 0) ||
                ($debit === 0 && $credit === 0)
            ) {
                $errors[] = 'Invalid debit/credit on line '.$line->line_no;
                continue;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        /*
        |--------------------------------------------------------------------------
        | Journal Balance
        |--------------------------------------------------------------------------
        */

        if ($totalDebit <= 0 || $totalDebit !== $totalCredit) {
            $errors[] = 'Journal debit and credit totals are not balanced.';
        }

        return [
            'eligible' => $errors === [],
            'errors' => $errors,
        ];
    }
}
