<?php
namespace App\Services\Tally;

class TallyRecoveryFailurePolicy
{
    public static function connectionNeverEstablished(string $error, ?string $response, $voucherId, $confirmedAt): bool
    {
        return trim((string) $response) === '' && blank($voucherId) && $confirmedAt === null
            && preg_match('/cURL error 7:\s*Failed to connect to\s+[^\r\n]+?\s+port\s+\d+\s+after\s+\d+\s+ms:\s*Could not connect to server/i', $error) === 1
            && ! preg_match('/cURL error (?:28|52|55|56):/i', $error);
    }
}
