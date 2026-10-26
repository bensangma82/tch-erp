<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Models\TallyJournalExportAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TallyJournalRecoveryExecutionService
{
    public function claim(int $exportId, int $userId): TallyJournalExportAttempt
    {
        if (! config('tally.journal_export_enabled')) {
            throw ValidationException::withMessages([
                'recovery' => 'Tally journal export is disabled.',
            ]);
        }

        $user = \App\Models\User::findOrFail($userId);

        if (! $user->hasPermission('tally.journal-recovery.authorize')) {
            throw ValidationException::withMessages([
                'recovery' => 'You are not authorized to execute Tally recovery.',
            ]);
        }

        return DB::transaction(function () use ($exportId, $userId) {
            $export = TallyExport::query()
                ->whereKey($exportId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $export->journal_entry_id === null
                || $export->status !== 'unknown'
            ) {
                throw ValidationException::withMessages([
                    'recovery' => 'This export is not eligible for recovery.',
                ]);
            }

            $journal = JournalEntry::query()
                ->whereKey($export->journal_entry_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($journal->status !== 'posted') {
                throw ValidationException::withMessages([
                    'recovery' => 'The journal must be posted.',
                ]);
            }

            $reconciliation = app(
                TallyJournalReconciliationService::class
            )->reconcile($journal);

            if (($reconciliation['status'] ?? null) !== 'missing') {
                throw ValidationException::withMessages([
                    'recovery' =>
                        'Recovery blocked: a matching or ambiguous '
                        .'Tally journal may already exist.',
                ]);
            }

            $attempt = TallyJournalExportAttempt::query()
                ->where('tally_export_id', $export->id)
                ->where('status', 'authorized')
                ->lockForUpdate()
                ->first();

            if (! $attempt) {
                throw ValidationException::withMessages([
                    'recovery' => 'No unused recovery authorization exists.',
                ]);
            }

            if ((int) $attempt->recovery_authorized_by !== $userId) {
                throw ValidationException::withMessages([
                    'recovery' => 'Recovery must be executed by its authorizing user.',
                ]);
            }

            $attempt->update([
                'status' => 'claimed',
                'claimed_at' => now(),
            ]);

            return $attempt->fresh();
        });
    }

    public function prepareRetry(
        int $exportId,
        int $userId
    ): TallyJournalExportAttempt {
        return DB::transaction(function () use ($exportId, $userId) {
            $attempt = $this->claim($exportId, $userId);

            $attempt->update([
                'status' => 'sending',
                'remote_id' => (string) \Illuminate\Support\Str::uuid(),
                'export_reference' => 'TALLY-RECOVERY-'
                    . \Illuminate\Support\Str::upper(
                        \Illuminate\Support\Str::uuid()->toString()
                    ),
                'transmitted_at' => null,
            ]);

            return $attempt->fresh();
        });
    }


}
