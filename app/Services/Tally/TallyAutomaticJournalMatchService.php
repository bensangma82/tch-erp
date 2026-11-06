<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Models\TallyImportedVoucher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Fail-closed, imported-data-only reconciliation. Never contacts TallyPrime. */
final class TallyAutomaticJournalMatchService
{
    public function __construct(
        private readonly TallyVoucherClassificationService $classifier,
        private readonly TallyJournalReconciliationService $comparison,
    ) {}

    /** A read-only exception report. Does not mark missing unless import completeness is externally verified. */
    public function preview(JournalEntry $journal): array
    {
        if ($journal->status !== 'posted') {
            return $this->diagnostic('not_eligible', 'Journal is not posted.');
        }
        $export = TallyExport::query()->where('journal_entry_id', $journal->id)->first();
        $linked = TallyImportedVoucher::query()->where('journal_entry_id', $journal->id)->first();
        if ($linked && $linked->reconciliation_status === 'matched' && $export?->status === 'confirmed') {
            return $this->diagnostic('matched', 'Existing confirmed match is protected.', $linked->id);
        }
        if ($linked) {
            return $this->diagnostic('duplicate_ambiguous', 'An existing journal link requires manual review.', $linked->id);
        }
        if ($export && $export->status !== 'confirmed') {
            return $this->diagnostic('unresolved_transmission', 'Existing export is not confirmed; do not retransmit.');
        }
        $company = trim((string) config('tally.company'));
        if ($company === '') {
            return $this->diagnostic('unresolved_transmission', 'Tally company is not configured.');
        }
        try {
            $type = $this->classifier->classify($journal);
        } catch (RuntimeException $e) {
            return $this->diagnostic('ledger_mismatch', 'Unsupported voucher classification: '.$e->getMessage());
        }
        $journal->loadMissing('lines.generalLedgerAccount');
        $date = $export?->tally_accounting_date?->format('Y-m-d')
            ?? $journal->journal_date?->format('Y-m-d');
        if (!$date) {
            return $this->diagnostic('unresolved_transmission', 'Accounting date is missing.');
        }

        // Identity must originate in the export REMOTEID/GUID or an exact ERP journal reference.
        // Never match solely by voucher number, date, amount, or similar ledger entries.
        $vouchers = TallyImportedVoucher::query()
            ->where('tally_company', $company)
            ->whereRaw('LOWER(voucher_type) = ?', [strtolower(trim($type))])
            ->whereDate('voucher_date', $date)
            ->with('entries')
            ->get()
            ->filter(fn (TallyImportedVoucher $v) => $this->identityMatches($journal, $export, $v))
            ->values();
        if ($vouchers->count() > 1) {
            return $this->diagnostic('duplicate_ambiguous', 'Multiple imported vouchers share the ERP identity.');
        }
        if ($vouchers->isEmpty()) {
            return $this->diagnostic('unverified_import',
                'No uniquely identified imported voucher. Import coverage has not been verified; do not declare Missing in Tally.');
        }
        $voucher = $vouchers->first();
        if (!$voucher->guid || !$voucher->voucher_number) {
            return $this->diagnostic('duplicate_ambiguous', 'Imported voucher lacks durable identity.', $voucher->id);
        }
        // Never reconcile a confirmed ERP export to a conflicting durable Tally identity.
        if ($export && filled($export->remote_id)
            && (string) $export->remote_id !== (string) $voucher->guid
            && (string) $export->remote_id !== (string) $voucher->remote_id) {
            return $this->diagnostic('duplicate_ambiguous',
                'Confirmed export REMOTEID differs from the imported Tally GUID/REMOTEID.', $voucher->id);
        }
        if ($export && filled($export->tally_voucher_id)
            && (string) $export->tally_voucher_id !== (string) $voucher->voucher_number) {
            return $this->diagnostic('duplicate_ambiguous',
                'Confirmed export voucher number differs from imported voucher.', $voucher->id);
        }
        if ($voucher->finance_voucher_id || $voucher->journal_entry_id || $voucher->reconciliation_status === 'matched') {
            return $this->diagnostic('duplicate_ambiguous', 'Imported voucher is already linked.', $voucher->id);
        }
        // An import that is claimed by another confirmed export is never available for reuse.
        if (TallyExport::query()
            ->where('journal_entry_id', '!=', $journal->id)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($voucher, $date) {
                $q->where('remote_id', $voucher->guid);
                if ($voucher->remote_id) $q->orWhere('remote_id', $voucher->remote_id);
                $q->orWhere(function ($q) use ($voucher, $date) {
                    $q->where('tally_voucher_id', $voucher->voucher_number)
                        ->whereDate('tally_accounting_date', $date);
                });
            })->exists()) {
            return $this->diagnostic('duplicate_ambiguous', 'Confirmed export already claims this voucher identity.', $voucher->id);
        }
        $issues = $this->comparison->compareVoucher($journal, $voucher);
        if ($issues !== []) {
            $status = $this->amountTotalsDiffer($journal, $voucher)
                ? 'amount_mismatch'
                : 'ledger_mismatch';

            return $this->diagnostic($status, implode(' ', $issues), $voucher->id);
        }
        // Advance-application journals must not be automatically confirmed independently of their Sales record.
        // The existing manual process remains available for these until source linkage is verified.
        if (str_starts_with((string) $journal->journal_no, 'JRN-IPAPPLY-')) {
            return $this->diagnostic('unresolved_transmission',
                'Advance application requires a verified linked IPD Sales dependency.', $voucher->id);
        }
        return $this->diagnostic('ready', 'Unique identity, date, type and GL entries verified.', $voucher->id);
    }

    /** Confirm one previewed match under row locks; no XML transmission. */
    public function apply(int $journalId, int $voucherId, int $userId): TallyExport
    {
        return DB::transaction(function () use ($journalId, $voucherId, $userId) {
            $journal = JournalEntry::query()->whereKey($journalId)->lockForUpdate()->firstOrFail();
            $voucher = TallyImportedVoucher::query()->whereKey($voucherId)->lockForUpdate()->firstOrFail();
            $decision = $this->preview($journal);
            if ($decision['status'] !== 'ready' || $decision['voucher_id'] !== $voucherId) {
                throw new RuntimeException('Automatic match not eligible: '.$decision['reason']);
            }
            $export = TallyExport::query()->where('journal_entry_id', $journalId)->lockForUpdate()->first();
            if ($export && $export->status !== 'confirmed') {
                throw new RuntimeException('Unresolved transmission must be reviewed manually.');
            }
            if (!$export) {
                $export = TallyExport::create([
                    'journal_entry_id' => $journalId,
                    'finance_voucher_id' => null,
                    'status' => 'confirmed',
                    'export_reference' => 'AUTO-JOURNAL-'.$journalId,
                    'tally_voucher_id' => (string) $voucher->voucher_number,
                    'tally_accounting_date' => $voucher->voucher_date,
                    'confirmed_at' => now(),
                    'exported_at' => null,
                    'exported_by' => $userId,
                    'remarks' => 'AUTO MATCH; no ERP transmission; imported GUID: '.$voucher->guid,
                ]);
            } else {
                // A previously confirmed export is left immutable.
                if ($export->tally_voucher_id && (string) $export->tally_voucher_id !== (string) $voucher->voucher_number) {
                    throw new RuntimeException('Confirmed export voucher number differs.');
                }
            }
            $voucher->forceFill([
                'journal_entry_id' => $journalId,
                'reconciliation_status' => 'matched',
                'match_method' => 'automatic_gl',
                'match_confidence' => 100,
                'reconciliation_notes' => 'Automatically reconciled against imported GL entries; no XML sent.',
                'reconciled_by' => $userId,
                'reconciled_at' => now(),
            ])->save();
            return $export;
        }, 3);
    }

    private function amountTotalsDiffer(
        JournalEntry $journal,
        TallyImportedVoucher $voucher
    ): bool {
        // Missing debit/credit indicators require ledger review,
        // not an inferred monetary mismatch.
        if ($voucher->entries->contains(
            fn ($line) => $line->is_deemed_positive === null
        )) {
            return false;
        }

        $erpDebit = 0;
        $erpCredit = 0;
        foreach ($journal->lines as $line) {
            $erpDebit += (int) round((float) $line->debit * 100);
            $erpCredit += (int) round((float) $line->credit * 100);
        }

        $tallyDebit = 0;
        $tallyCredit = 0;
        foreach ($voucher->entries as $line) {
            $paise = (int) round(abs((float) $line->amount) * 100);
            if ($line->is_deemed_positive) {
                $tallyDebit += $paise;
            } else {
                $tallyCredit += $paise;
            }
        }

        return $erpDebit !== $tallyDebit
            || $erpCredit !== $tallyCredit;
    }

    private function identityMatches(JournalEntry $journal, ?TallyExport $export, TallyImportedVoucher $v): bool
    {
        $remote = trim((string) $export?->remote_id);
        if ($remote !== '' && ($remote === (string) $v->guid || $remote === (string) $v->remote_id)) {
            return true;
        }
        $no = (string) $journal->journal_no;
        return $no !== '' && preg_match('/(?:^|\s)ERP\s+'.preg_quote($no, '/').'(?=\s|\||$)/i', (string) $v->narration) === 1;
    }

    private function diagnostic(string $status, string $reason, ?int $voucherId = null): array
    {
        return ['status' => $status, 'reason' => $reason, 'voucher_id' => $voucherId];
    }
}
