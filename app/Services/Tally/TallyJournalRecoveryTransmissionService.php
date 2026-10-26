<?php

namespace App\Services\Tally;

use App\Models\TallyExport;
use Illuminate\Validation\ValidationException;
use Throwable;

class TallyJournalRecoveryTransmissionService
{
    public function send(int $exportId, int $userId): array
    {
        $attempt = app(
            TallyJournalRecoveryExecutionService::class
        )->prepareRetry($exportId, $userId);

        $export = TallyExport::with('journalEntry')
            ->findOrFail($exportId);

        $journal = $export->journalEntry;

        if (! $journal) {
            throw ValidationException::withMessages([
                'recovery' => 'Journal not found.',
            ]);
        }

        try {
            $xml = app(TallyJournalXmlBuilder::class)
                ->build($journal, $attempt->remote_id);
        } catch (Throwable $exception) {
            app(TallyJournalRecoveryOutcomeService::class)
                ->recordPreparationFailure($attempt->id, $exception);

            return [
                'status' => 'preparation_failed',
                'attempt_id' => $attempt->id,
                'message' => 'XML preparation failed; no HTTP request was made.',
            ];
        }

        // Persist the transmission marker before the network call.
        \App\Models\TallyJournalExportAttempt::whereKey($attempt->id)
            ->where('status', 'sending')
            ->whereNull('transmitted_at')
            ->update(['transmitted_at' => now()]);

        try {
            $response = app(TallyHttpClient::class)->postXml($xml);
        } catch (Throwable $exception) {
            app(TallyJournalRecoveryOutcomeService::class)
                ->recordTransmissionFailure($attempt->id, $exception);
            return ['status' => 'unknown', 'attempt_id' => $attempt->id,
                'message' => 'HTTP outcome uncertain; do not retry.'];
        }

        // A database failure AFTER a successful HTTP response must never
        // start another transmission or overwrite a confirmed outcome.
        try {
            $result = app(TallyJournalRecoveryOutcomeService::class)
                ->record($attempt->id, $response);
            return ['status' => $result->status, 'attempt_id' => $result->id];
        } catch (Throwable $exception) {
            \Log::error('Tally recovery response processing failed', [
                'attempt_id' => $attempt->id,
                'error' => $exception->getMessage(),
            ]);
            return ['status' => 'unknown', 'attempt_id' => $attempt->id,
                'message' => 'Tally responded but ERP confirmation failed. Reconcile manually.'];
        }
    }
}
