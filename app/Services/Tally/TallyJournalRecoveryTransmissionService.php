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
                ->recordTransmissionFailure($attempt->id, $exception);

            return [
                'status' => 'unknown',
                'attempt_id' => $attempt->id,
                'message' => 'XML preparation failed; no HTTP request was made.',
            ];
        }

        try {
            $response = app(TallyHttpClient::class)
                ->postXml($xml);

            $result = app(
                TallyJournalRecoveryOutcomeService::class
            )->record($attempt->id, $response);

            return [
                'status' => $result->status,
                'attempt_id' => $result->id,
            ];

        } catch (Throwable $exception) {
            app(TallyJournalRecoveryOutcomeService::class)
                ->recordTransmissionFailure(
                    $attempt->id,
                    $exception
                );

            return [
                'status' => 'unknown',
                'attempt_id' => $attempt->id,
                'message' => 'Transmission outcome uncertain.',
            ];
        }
    }
}
