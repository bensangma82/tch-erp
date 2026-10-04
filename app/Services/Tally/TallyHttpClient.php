<?php

namespace App\Services\Tally;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TallyHttpClient
{
    /**
     * Check whether the configured TallyPrime server is reachable.
     */
    public function ping(): string
    {
        try {
            $response = Http::timeout(
                $this->timeout()
            )->get(
                $this->url()
            );
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Unable to connect to TallyPrime: '
                .$exception->getMessage(),
                previous: $exception
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'TallyPrime returned HTTP '
                .$response->status().'.'
            );
        }

        return trim($response->body());
    }

    /**
     * Send an XML request to TallyPrime.
     */
    public function postXml(string $xml): string
    {
        try {
            $response = Http::timeout(
                $this->timeout()
            )
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=UTF-8',
                    'Accept' => 'text/xml',
                ])
                ->withBody(
                    $xml,
                    'text/xml'
                )
                ->post(
                    $this->url()
                );
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Unable to connect to TallyPrime: '
                .$exception->getMessage(),
                previous: $exception
            );
        }

        $this->ensureSuccessfulHttpResponse(
            $response
        );

        return trim(
            $response->body()
        );
    }

    /**
     * Configured Tally company.
     */
    public function company(): string
    {
        return (string) config(
            'tally.company'
        );
    }

    /**
     * TallyPrime HTTP endpoint.
     */
    private function url(): string
    {
        return rtrim(
            (string) config('tally.url'),
            '/'
        );
    }

    /**
     * HTTP timeout in seconds.
     */
    private function timeout(): int
    {
        return max(
            1,
            (int) config('tally.timeout', 10)
        );
    }

    /**
     * Reject HTTP-level failures.
     */
    private function ensureSuccessfulHttpResponse(
        Response $response
    ): void {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            'TallyPrime returned HTTP '
            .$response->status()
            .'. Response: '
            .trim($response->body())
        );
    }
}
