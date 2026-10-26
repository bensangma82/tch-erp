<?php

namespace App\Services\Tally;

use App\Models\TallyExport;
use App\Models\TallyJournalExportAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TallyJournalRecoveryResolutionService
{
    public function resolve(int $exportId, int $userId, string $evidence): TallyJournalExportAttempt
    {
        $evidence = trim($evidence);
        if (!config('tally.journal_recovery_enabled', false) || !config('tally.journal_export_enabled', false)
            || !str_contains(strtolower((string) config('tally.company')), '-test')) {
            throw ValidationException::withMessages(['recovery' => 'Recovery is not enabled for an explicitly named test company.']);
        }
        if (!User::findOrFail($userId)->hasPermission('tally.journal-recovery.authorize')) {
            throw ValidationException::withMessages(['recovery' => 'Recovery authorization permission required.']);
        }
        if (!str_contains(strtolower($evidence), 'day book')) {
            throw ValidationException::withMessages(['recovery' => 'Document a fresh live Tally Day Book check.']);
        }

        return DB::transaction(function () use ($exportId, $userId, $evidence) {
            $export = TallyExport::whereKey($exportId)->lockForUpdate()->firstOrFail();
            if (!$export->journal_entry_id || $export->status !== 'unknown') {
                throw ValidationException::withMessages(['recovery' => 'Original export is no longer unknown.']);
            }
            $journal = $export->journalEntry;
            if (!$journal || !str_contains($evidence, $journal->journal_no)) {
                throw ValidationException::withMessages(['recovery' => 'Day Book evidence must contain the exact journal number.']);
            }
            $attempts = TallyJournalExportAttempt::where('tally_export_id', $exportId)->lockForUpdate()->get();
            if ($attempts->whereIn('status', ['authorized','claimed','sending','confirmed'])->isNotEmpty()) {
                throw ValidationException::withMessages(['recovery' => 'Another active or confirmed attempt exists.']);
            }
            $attempt = $attempts->sortByDesc('attempt_number')->first();
            if (!$attempt || $attempt->status !== 'unknown' || $attempt->transmitted_at === null) {
                throw ValidationException::withMessages(['recovery' => 'No transmitted unknown attempt exists to resolve.']);
            }
            $xml = (string) $attempt->tally_response;
            if (!preg_match('~<LINEERROR>.+?</LINEERROR>~si', $xml)
                || !preg_match('~<CREATED>0</CREATED>~i', $xml)
                || !preg_match('~<ALTERED>0</ALTERED>~i', $xml)
                || !preg_match('~<DELETED>0</DELETED>~i', $xml)
                || !preg_match('~<LASTVCHID>0</LASTVCHID>~i', $xml)) {
                throw ValidationException::withMessages(['recovery' => 'Explicit zero-voucher rejection XML is required.']);
            }
            $reconciliation = app(TallyJournalReconciliationService::class)->reconcile($journal);
            if (($reconciliation['status'] ?? null) !== 'missing') {
                throw ValidationException::withMessages(['recovery' => 'ERP reconciliation is not missing; do not retry.']);
            }
            $attempt->update([
                'status' => 'rejected',
                'rejection_resolved_by' => $userId,
                'rejection_resolved_at' => now(),
                'rejection_resolution_evidence' => $evidence,
            ]);
            return $attempt->fresh();
        });
    }
}
