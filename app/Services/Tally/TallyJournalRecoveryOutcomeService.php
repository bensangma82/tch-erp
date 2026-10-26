<?php

namespace App\Services\Tally;

use App\Models\TallyJournalExportAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TallyJournalRecoveryOutcomeService
{
    public function record(
        int $attemptId,
        string $responseXml
    ): TallyJournalExportAttempt {
        return DB::transaction(function () use ($attemptId, $responseXml) {
            $attempt = TallyJournalExportAttempt::query()
                ->whereKey($attemptId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->status !== 'sending') {
                throw ValidationException::withMessages([
                    'recovery' => 'This attempt cannot accept a response.',
                ]);
            }

            if (trim($responseXml) === '' || simplexml_load_string($responseXml, \SimpleXMLElement::class, LIBXML_NONET) === false) {
                throw new \RuntimeException('Invalid Tally response; outcome is unknown.');
            }
            $parsed = app(TallyResponseParser::class)
                ->parse($responseXml);

            $confirmed =
                trim($responseXml) !== ''
                && ($parsed['success'] ?? false)
                && ($parsed['created'] ?? null) === 1
                && ($parsed['altered'] ?? null) === 0
                && ($parsed['ignored'] ?? null) === 0
                && ($parsed['errors'] ?? null) === 0
                && ($parsed['cancelled'] ?? null) === 0;

            // Unconfirmed responses remain uncertain until reconciled.
            $attempt->update([
                'status' => $confirmed ? 'confirmed' : 'unknown',
                'tally_response' => $responseXml,
                'error_message' => $confirmed
                    ? null
                    : ($parsed['message'] ?? 'Tally import not confirmed.'),
                'confirmed_at' => $confirmed ? now() : null,
            ]);

            if ($confirmed) {
                $export = $attempt->tallyExport()
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($export->status !== 'unknown') {
                    throw ValidationException::withMessages([
                        'recovery' => 'Original export status has changed.',
                    ]);
                }

                $export->update([
                    'status' => 'confirmed',
                    'tally_voucher_id' => $parsed['last_voucher_id'] ?? null,
                    'exported_at' => now(),
                    'confirmed_at' => now(),
                    'error_message' => null,
                    'remarks' => 'Journal confirmed through authorized recovery. '
                        .'Recovery attempt ID: '.$attempt->id
                        .'; remote ID: '.$attempt->remote_id,
                ]);
            }

            return $attempt->fresh();
        });
    }

    public function recordPreparationFailure(int $attemptId, \Throwable $exception): TallyJournalExportAttempt
    {
        return DB::transaction(function () use ($attemptId, $exception) {
            $attempt = TallyJournalExportAttempt::whereKey($attemptId)->lockForUpdate()->firstOrFail();
            if ($attempt->status !== 'sending' || $attempt->transmitted_at !== null) {
                throw ValidationException::withMessages(['recovery' => 'Cannot mark a transmitted attempt as a preparation failure.']);
            }
            $attempt->update(['status' => 'preparation_failed', 'error_message' => $exception->getMessage()]);
            return $attempt->fresh();
        });
    }

    public function recordTransmissionFailure(
        int $attemptId,
        \Throwable $exception
    ): TallyJournalExportAttempt {
        return DB::transaction(function () use ($attemptId, $exception) {
            $attempt = TallyJournalExportAttempt::query()
                ->whereKey($attemptId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->status !== 'sending') {
                throw ValidationException::withMessages([
                    'recovery' => 'Attempt is not in sending state.',
                ]);
            }

            $attempt->update([
                'status' => 'unknown',
                'error_message' =>
                    'Transmission outcome uncertain: '
                    .$exception->getMessage(),
            ]);

            return $attempt->fresh();
        });
    }

}
