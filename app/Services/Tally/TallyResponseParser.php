<?php

namespace App\Services\Tally;

use SimpleXMLElement;

class TallyResponseParser
{
    /**
     * Parse a TallyPrime voucher-import response.
     *
     * @return array{
     *     success: bool,
     *     created: int,
     *     altered: int,
     *     ignored: int,
     *     errors: int,
     *     cancelled: int,
     *     last_voucher_id: string|null,
     *     message: string|null,
     *     raw: string
     * }
     */
    public function parse(string $response): array
    {
        $response = trim($response);

        if ($response === '') {
            return [
                'success' => false,
                'created' => 0,
                'altered' => 0,
                'ignored' => 0,
                'errors' => 1,
                'cancelled' => 0,
                'last_voucher_id' => null,
                'message' => 'TallyPrime returned an empty response.',
                'raw' => $response,
            ];
        }

        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($response);

        if (! $xml instanceof SimpleXMLElement) {
            libxml_clear_errors();

            return [
                'success' => false,
                'created' => 0,
                'altered' => 0,
                'ignored' => 0,
                'errors' => 1,
                'cancelled' => 0,
                'last_voucher_id' => null,
                'message' => 'TallyPrime returned invalid XML.',
                'raw' => $response,
            ];
        }

        libxml_clear_errors();

        $lineError = trim(
            (string) ($xml->LINEERROR ?? '')
        );

        $created = (int) ($xml->CREATED ?? 0);
        $altered = (int) ($xml->ALTERED ?? 0);
        $ignored = (int) ($xml->IGNORED ?? 0);
        $errors = (int) ($xml->ERRORS ?? 0);
        $cancelled = (int) ($xml->CANCELLED ?? 0);

        $lastVoucherId = trim(
            (string) ($xml->LASTVCHID ?? '')
        );

        $success = $lineError === ''
            && $errors === 0
            && ($created > 0 || $altered > 0);

        $message = null;

        if ($lineError !== '') {
            $message = $lineError;
        } elseif ($errors > 0) {
            $message = 'TallyPrime reported '
                .$errors.' import error(s).';
        } elseif (! $success) {
            $message = 'TallyPrime did not report a created or altered voucher.';
        }

        return [
            'success' => $success,
            'created' => $created,
            'altered' => $altered,
            'ignored' => $ignored,
            'errors' => $errors,
            'cancelled' => $cancelled,
            'last_voucher_id' => $lastVoucherId !== ''
                ? $lastVoucherId
                : null,
            'message' => $message,
            'raw' => $response,
        ];
    }
}
