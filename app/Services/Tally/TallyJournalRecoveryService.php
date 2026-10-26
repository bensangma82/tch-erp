<?php

namespace App\Services\Tally;

use App\Models\TallyExport;
use App\Models\TallyJournalExportAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TallyJournalRecoveryService
{
    public function authorizeRecovery(
        int $exportId,
        int $authorizedBy,
        string $reason,
        string $evidence
    ): TallyJournalExportAttempt {
        if (! config('tally.journal_recovery_enabled', false)) {
            throw ValidationException::withMessages(['recovery' => 'Tally journal recovery is disabled.']);
        }

        $reason = trim($reason);
        $evidence = trim($evidence);

        if ($reason === '' || $evidence === '') {
            throw ValidationException::withMessages([
                'recovery' => 'A reason and reconciliation evidence are required.',
            ]);
        }

        $authorizer = \App\Models\User::findOrFail($authorizedBy);

        if (! $authorizer->hasPermission('tally.journal-recovery.authorize')) {
            throw ValidationException::withMessages([
                'recovery' => 'You are not authorized to approve Tally journal recovery.',
            ]);
        }

        return DB::transaction(function () use (
            $exportId,
            $authorizedBy,
            $reason,
            $evidence
        ) {
            $export = TallyExport::query()
                ->whereKey($exportId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $export->journal_entry_id === null
                || $export->status !== 'unknown'
            ) {
                throw ValidationException::withMessages([
                    'recovery' => 'Only unknown journal exports can be reviewed for recovery.',
                ]);
            }

            $response = (string) $export->tally_response;

            if (
                $response === ''
                || ! str_contains($response, '<LINEERROR>')
                || ! preg_match('/<CREATED>0<\/CREATED>/i', $response)
                || ! preg_match('/<ALTERED>0<\/ALTERED>/i', $response)
                || ! preg_match('/<DELETED>0<\/DELETED>/i', $response)
                || ! preg_match('/<LASTVCHID>0<\/LASTVCHID>/i', $response)
            ) {
                throw ValidationException::withMessages([
                    'recovery' =>
                        'Recovery authorization requires an explicit '
                        .'Tally rejection response showing no voucher '
                        .'was created, altered or deleted.',
                ]);
            }

            $journal = $export->journalEntry;

            if (! $journal) {
                throw ValidationException::withMessages([
                    'recovery' => 'The original journal could not be found.',
                ]);
            }

            $reconciliation = app(
                TallyJournalReconciliationService::class
            )->reconcile($journal);

            if (($reconciliation['status'] ?? null) !== 'missing') {
                throw ValidationException::withMessages([
                    'recovery' => 'Recovery blocked: reconciliation is not missing.',
                ]);
            }

            if (! str_contains(strtolower($evidence), 'day book') || ! str_contains($evidence, $journal->journal_no)) {
                throw ValidationException::withMessages(['recovery' => 'Record the Tally Day Book verification and exact ERP journal number in the evidence.']);
            }

            $previousAttempts = TallyJournalExportAttempt::query()
                ->where('tally_export_id', $export->id);

            if (
                (clone $previousAttempts)
                    ->whereIn('status', [
                        'authorized',
                        'claimed',
                        'sending',
                        'confirmed',
                        'unknown',
                    ])
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'recovery' => 'A recovery authorization already exists.',
                ]);
            }

            $attemptNumber = (int) (
                (clone $previousAttempts)->max('attempt_number') ?? 0
            ) + 1;

            return TallyJournalExportAttempt::create([
                'tally_export_id' => $export->id,
                'original_remote_id' => $export->remote_id,
                'original_export_reference' => $export->export_reference,
                'original_tally_response' => $export->tally_response,
                'tally_company' => (string) config('tally.company'),
                'attempt_number' => $attemptNumber,
                'status' => 'authorized',
                // Snapshot the original export identity and response.
                'remote_id' => $export->remote_id,
                'export_reference' => $export->export_reference,
                'tally_accounting_date' => $export->tally_accounting_date,
                'tally_response' => $export->tally_response,
                'error_message' => $export->error_message,
                'recovery_authorized_by' => $authorizedBy,
                'recovery_authorized_at' => now(),
                'recovery_reason' => $reason,
                'reconciliation_evidence' => $evidence,
            ]);
        });
    }
}
