<?php

namespace App\Services\Preflight;

final class PreflightResult
{
    /** @var PreflightIssue[] */
    private array $issues = [];

    public function add(PreflightIssue $issue): void
    {
        $this->issues[] = $issue;
    }

    public function hasBlockingIssues(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity === 'blocking') {
                return true;
            }
        }

        return false;
    }

    public function isReady(): bool
    {
        return ! $this->hasBlockingIssues();
    }

    public function issues(): array
    {
        return array_map(
            fn (PreflightIssue $issue) => $issue->toArray(),
            $this->issues
        );
    }

    public function toArray(): array
    {
        return [
            'ready' => $this->isReady(),
            'issues' => $this->issues(),
        ];
    }
}
