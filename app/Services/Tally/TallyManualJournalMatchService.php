<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Models\TallyImportedVoucher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Match a previously imported, manually posted Tally voucher; never transmit. */
final class TallyManualJournalMatchService
{
    public function __construct(
        private readonly TallyVoucherClassificationService $classifier,
        private readonly TallyJournalReconciliationService $reconciliation
    ) {}

    public function match(int $journalId, int $voucherId, int $userId, string $notes): TallyExport
    {
        return DB::transaction(function () use ($journalId, $voucherId, $userId, $notes): TallyExport {
            $journal = JournalEntry::query()->whereKey($journalId)->lockForUpdate()->firstOrFail();
            $voucher = TallyImportedVoucher::query()->whereKey($voucherId)->lockForUpdate()->firstOrFail();

            if ($journal->status !== 'posted') {
                throw new RuntimeException('Only posted ERP journals may be reconciled.');
            }
            if (TallyExport::query()->where('journal_entry_id', $journal->id)->exists()) {
                throw new RuntimeException('Journal already has an export/reconciliation record; do not send again.');
            }
            if ($voucher->journal_entry_id !== null || $voucher->finance_voucher_id !== null
                || $voucher->reconciliation_status === 'matched') {
                throw new RuntimeException('Tally voucher has already been linked or matched.');
            }
            if (TallyExport::query()->where('tally_voucher_id', $voucher->voucher_number)
                ->whereNotNull('journal_entry_id')->where('status', 'confirmed')->exists()) {
                // Voucher numbers alone are not globally unique; fail closed until reviewed.
                throw new RuntimeException('This Tally voucher number already appears in a confirmed journal match.');
            }
            $company = trim((string) config('tally.company'));
            if ($company === '' || trim((string) $voucher->tally_company) !== $company) {
                throw new RuntimeException('Tally company does not match the configured company.');
            }
            if (!filled($voucher->guid) || !filled($voucher->voucher_number) || ! $voucher->voucher_date) {
                throw new RuntimeException('Imported Tally GUID, voucher number and accounting date are required.');
            }
            $type = $this->classifier->classify($journal);
            if (strcasecmp(trim((string) $voucher->voucher_type), $type) !== 0) {
                throw new RuntimeException('Tally voucher type does not match ERP classification.');
            }
            $journal->loadMissing('lines.generalLedgerAccount');
            $voucher->loadMissing('entries');
            $issues = $this->reconciliation->compareVoucher($journal, $voucher);
            if ($issues !== []) {
                throw new RuntimeException(implode(' ', $issues));
            }

            // An existing export row is also the duplicate-send lock used by the ERP.
            $export = TallyExport::create([
                'finance_voucher_id' => null,
                'journal_entry_id' => $journal->id,
                'status' => 'confirmed',
                'export_reference' => 'MANUAL-JOURNAL-'.$journal->id,
                'tally_voucher_id' => (string) $voucher->voucher_number,
                'tally_accounting_date' => $voucher->voucher_date,
                'confirmed_at' => now(),
                'exported_at' => null, // No ERP transmission occurred.
                'exported_by' => $userId,
                'remarks' => 'MANUAL MATCH, NOT ERP TRANSMISSION; imported GUID: '.$voucher->guid.'; '.trim($notes),
            ]);
            $voucher->forceFill([
                'journal_entry_id' => $journal->id,
                'reconciliation_status' => 'matched',
                'match_method' => 'manual_gl',
                'match_confidence' => 100,
                'reconciliation_notes' => 'Verified manual GL journal match. '.trim($notes),
                'reconciled_by' => $userId,
                'reconciled_at' => now(),
            ])->save();
            return $export;
        }, 3);
    }
}
