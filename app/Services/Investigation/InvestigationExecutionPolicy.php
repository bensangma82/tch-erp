<?php

namespace App\Services\Investigation;

/** Pure clinical escalation rules; no financial side effects. */
final class InvestigationExecutionPolicy
{
    public function requiresMs(string $status, bool $sampleCollected, bool $hasResult, bool $originallyRequiredMs): bool
    {
        if (! in_array($status, ['ordered', 'in_process', 'completed'], true)) {
            throw new \InvalidArgumentException('Unrecognised investigation state.');
        }

        return $originallyRequiredMs || $status !== 'ordered' || $sampleCollected || $hasResult;
    }
}
