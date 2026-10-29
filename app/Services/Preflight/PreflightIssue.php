<?php

namespace App\Services\Preflight;

final class PreflightIssue
{
    public function __construct(
        public readonly string $code,
        public readonly string $severity,
        public readonly string $message,
        public readonly string $correction,
        public readonly ?string $record = null,
    ) {
        if (! in_array($severity, ['blocking', 'warning', 'info'], true)) {
            throw new \InvalidArgumentException(
                'Invalid preflight issue severity.'
            );
        }
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'message' => $this->message,
            'correction' => $this->correction,
            'record' => $this->record,
        ];
    }
}
