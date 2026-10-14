<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyImportedVoucher;
use Illuminate\Support\Collection;

class TallyJournalReconciliationService
{
    /**
     * Read-only reconciliation of one ERP GL journal
     * against previously imported Tally vouchers.
     *
     * This does not query TallyPrime live.
     */
    public function reconcile(JournalEntry $journal): array
    {
        $journal->loadMissing([
            'lines.generalLedgerAccount',
            'tallyExport',
        ]);

        $export = $journal->tallyExport;

        if ($export === null) {
            return $this->result(
                'missing',
                'The journal has no Tally export record.'
            );
        }

        $company = trim(
            (string) config('tally.company')
        );

        $candidates = TallyImportedVoucher::query()
            ->where('tally_company', $company)
            ->whereRaw(
                'LOWER(voucher_type) = ?',
                ['journal']
            )
            ->with('entries')
            ->get()
            ->filter(function ($voucher) use (
                $journal,
                $export
            ) {
                $identityMatch =
                    filled($export->remote_id)
                    && (
                        $voucher->remote_id === $export->remote_id
                        || $voucher->guid === $export->remote_id
                    );

                $narrationMatch = preg_match(
                    '/(?:^|\s)ERP\s+'
                    .preg_quote($journal->journal_no, '/')
                    .'(?=\s|\||$)/i',
                    (string) $voucher->narration
                ) === 1;

                return $identityMatch || $narrationMatch;
            })
            ->values();

        if ($candidates->isEmpty()) {
            return $this->result(
                'missing',
                'No matching journal found in the imported Tally vouchers.'
            );
        }

        if ($candidates->count() > 1) {
            return $this->result(
                'ambiguous',
                'Multiple Tally vouchers match this ERP journal.',
                $candidates
            );
        }

        $voucher = $candidates->first();

        $errors = [];

        /*
        |--------------------------------------------------------------------------
        | Export Identity
        |--------------------------------------------------------------------------
        */

        if (
            $export->status !== 'confirmed'
        ) {
            $errors[] = 'ERP export status is not confirmed.';
        }

        /*
        |--------------------------------------------------------------------------
        | Accounting Date
        |--------------------------------------------------------------------------
        */

        $expectedDate = $export->tally_accounting_date
            ? $export->tally_accounting_date->format('Y-m-d')
            : $journal->journal_date->format('Y-m-d');

        $actualDate = $voucher->voucher_date
            ? $voucher->voucher_date->format('Y-m-d')
            : null;

        if ($expectedDate !== $actualDate) {
            $errors[] = 'Tally accounting date differs from the ERP export date.';
        }

        /*
        |--------------------------------------------------------------------------
        | Ledger-Level Reconciliation
        |--------------------------------------------------------------------------
        */

        $erpEntries = $this->erpEntries($journal);
        $tallyEntries = $this->tallyEntries($voucher);

        if ($erpEntries !== $tallyEntries) {
            $errors[] = 'Debit, credit, or ledger entries differ.';
        }

        if ($errors !== []) {
            return $this->result(
                'difference',
                implode(' ', $errors),
                collect([$voucher])
            );
        }

        return $this->result(
            'matched',
            'Journal identity, accounting date, and ledger entries match.',
            collect([$voucher])
        );
    }

    /**
     * Normalize ERP journal lines to ledger-side-amount keys.
     * Amounts are converted to integer paise.
     */
    private function erpEntries(JournalEntry $journal): array
    {
        $entries = [];

        foreach ($journal->lines as $line) {
            $ledger = trim(
                (string) $line
                    ->generalLedgerAccount
                    ?->tally_ledger_name
            );

            $debit = (int) round(
                (float) $line->debit * 100
            );

            $credit = (int) round(
                (float) $line->credit * 100
            );

            if ($debit > 0) {
                $key = mb_strtolower($ledger).'|debit';

                $entries[$key] = (
                    $entries[$key] ?? 0
                ) + $debit;
            }

            if ($credit > 0) {
                $key = mb_strtolower($ledger).'|credit';

                $entries[$key] = (
                    $entries[$key] ?? 0
                ) + $credit;
            }
        }

        ksort($entries);

        return $entries;
    }

    /**
     * Normalize imported Tally voucher entries.
     *
     * is_deemed_positive=true means debit.
     * is_deemed_positive=false means credit.
     */
    private function tallyEntries(
        TallyImportedVoucher $voucher
    ): array {
        $entries = [];

        foreach ($voucher->entries as $line) {
            $ledger = trim(
                (string) $line->ledger_name
            );

            $side = $line->is_deemed_positive
                ? 'debit'
                : 'credit';

            $amount = (int) round(
                abs((float) $line->amount) * 100
            );

            $key = mb_strtolower($ledger).'|'.$side;

            $entries[$key] = (
                $entries[$key] ?? 0
            ) + $amount;
        }

        ksort($entries);

        return $entries;
    }

    private function result(
        string $status,
        string $reason,
        ?Collection $candidates = null
    ): array {
        return [
            'status' => $status,
            'reason' => $reason,
            'candidate_count' => $candidates?->count() ?? 0,
            'tally_voucher' => (
                $candidates !== null
                && $candidates->count() === 1
            )
                ? $candidates->first()
                : null,
        ];
    }
}
