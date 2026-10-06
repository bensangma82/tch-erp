<?php

namespace App\Services\Tally;

use RuntimeException;
use SimpleXMLElement;

class TallyQueryService
{
    public function __construct(
        private readonly TallyHttpClient $tallyHttpClient
    ) {}

    /**
     * Retrieve the ledger master from the configured Tally company.
     *
     * This is a read-only Tally request.
     *
     * @return array<int, array{
     *     name: string,
     *     parent: ?string,
     *     closing_balance: ?float
     * }>
     */
    public function ledgers(): array
    {
        $responseXml = $this->tallyHttpClient->postXml(
            $this->ledgerRequestXml()
        );

        return $this->parseLedgers(
            $responseXml
        );
    }

    /**
     * Retrieve Trial Balance data from the configured Tally company.
     *
     * This is a read-only Tally request.
     *
     * @return array<int, array{
     *     name: string,
     *     parent: ?string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     debit: float,
     *     credit: float
     * }>
     */
    public function trialBalance(): array
    {
        $responseXml = $this->tallyHttpClient->postXml(
            $this->trialBalanceRequestXml()
        );

        return $this->parseTrialBalance(
            $responseXml
        );
    }

    /**
     * Build a read-only Tally XML request for Trial Balance data.
     */
    private function trialBalanceRequestXml(): string
    {
        $company = $this->escape(
            $this->tallyHttpClient->company()
        );

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ENVELOPE>
    <HEADER>
        <VERSION>1</VERSION>
        <TALLYREQUEST>Export</TALLYREQUEST>
        <TYPE>Collection</TYPE>
        <ID>ERP Trial Balance Collection</ID>
    </HEADER>

    <BODY>
        <DESC>
            <STATICVARIABLES>
                <SVEXPORTFORMAT>\$\$SysName:XML</SVEXPORTFORMAT>
                <SVCURRENTCOMPANY>{$company}</SVCURRENTCOMPANY>
            </STATICVARIABLES>

            <TDL>
                <TDLMESSAGE>
                    <COLLECTION NAME="ERP Trial Balance Collection">
                        <TYPE>Ledger</TYPE>
                        <FETCH>
                            NAME,
                            PARENT,
                            OPENINGBALANCE,
                            CLOSINGBALANCE
                        </FETCH>
                    </COLLECTION>
                </TDLMESSAGE>
            </TDL>
        </DESC>
    </BODY>
</ENVELOPE>
XML;
    }

    /**
     * Build a read-only Tally XML request for the ledger master.
     */
    private function ledgerRequestXml(): string
    {
        $company = $this->escape(
            $this->tallyHttpClient->company()
        );

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ENVELOPE>
    <HEADER>
        <VERSION>1</VERSION>
        <TALLYREQUEST>Export</TALLYREQUEST>
        <TYPE>Collection</TYPE>
        <ID>ERP Ledger Collection</ID>
    </HEADER>

    <BODY>
        <DESC>
            <STATICVARIABLES>
                <SVEXPORTFORMAT>\$\$SysName:XML</SVEXPORTFORMAT>
                <SVCURRENTCOMPANY>{$company}</SVCURRENTCOMPANY>
            </STATICVARIABLES>

            <TDL>
                <TDLMESSAGE>
                    <COLLECTION NAME="ERP Ledger Collection">
                        <TYPE>Ledger</TYPE>
                        <FETCH>
                            NAME,
                            PARENT,
                            CLOSINGBALANCE
                        </FETCH>
                    </COLLECTION>
                </TDLMESSAGE>
            </TDL>
        </DESC>
    </BODY>
</ENVELOPE>
XML;
    }

    /**
     * Parse Trial Balance data returned by Tally.
     *
     * Tally represents ledger balances as signed amounts.
     * For reporting purposes:
     * - negative closing balance = Debit
     * - positive closing balance = Credit
     *
     * @return array<int, array{
     *     name: string,
     *     parent: ?string,
     *     opening_balance: float,
     *     closing_balance: float,
     *     debit: float,
     *     credit: float
     * }>
     */
    private function parseTrialBalance(
        string $responseXml
    ): array {
        if (blank($responseXml)) {
            throw new RuntimeException(
                'Tally returned an empty Trial Balance response.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Clean Tally XML
        |--------------------------------------------------------------------------
        | Tally may return XML 1.0-invalid numeric control-character entities,
        | such as &#4; before reserved/internal group names.
        |--------------------------------------------------------------------------
        */

        $responseXml = preg_replace_callback(
            '/&#(?:x([0-9A-Fa-f]+)|([0-9]+));/',
            static function (array $matches): string {
                $codePoint = $matches[1] !== ''
                    ? hexdec($matches[1])
                    : (int) $matches[2];

                $validXmlCharacter =
                    $codePoint === 0x09
                    || $codePoint === 0x0A
                    || $codePoint === 0x0D
                    || ($codePoint >= 0x20 && $codePoint <= 0xD7FF)
                    || ($codePoint >= 0xE000 && $codePoint <= 0xFFFD)
                    || ($codePoint >= 0x10000 && $codePoint <= 0x10FFFF);

                return $validXmlCharacter
                    ? $matches[0]
                    : '';
            },
            $responseXml
        );

        if ($responseXml === null) {
            throw new RuntimeException(
                'Unable to clean the Tally Trial Balance response.'
            );
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string(
                $responseXml
            );

            if (! $xml instanceof SimpleXMLElement) {
                throw new RuntimeException(
                    'Unable to parse the Tally Trial Balance response.'
                );
            }

            $ledgerNodes = $xml->xpath(
                '//LEDGER'
            );

            if ($ledgerNodes === false) {
                throw new RuntimeException(
                    'Unable to locate ledgers in the Tally Trial Balance response.'
                );
            }

            $rows = [];

            foreach ($ledgerNodes as $ledger) {
                $name = trim(
                    (string) (
                        $ledger['NAME']
                        ?? $ledger->NAME
                    )
                );

                if ($name === '') {
                    continue;
                }

                $reservedName = trim(
                    (string) $ledger['RESERVEDNAME']
                );

                if (
                    strcasecmp(
                        $reservedName,
                        'Profit & Loss A/c'
                    ) === 0
                ) {
                    continue;
                }

                $parent = trim(
                    (string) $ledger->PARENT
                );

                $openingBalance = $this->parseAmount(
                    trim(
                        (string) $ledger->OPENINGBALANCE
                    )
                ) ?? 0.0;

                $closingBalance = $this->parseAmount(
                    trim(
                        (string) $ledger->CLOSINGBALANCE
                    )
                ) ?? 0.0;

                $rows[] = [
                    'name' => $name,
                    'parent' => $parent !== ''
                        ? $parent
                        : null,
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'debit' => $closingBalance < 0
                        ? abs($closingBalance)
                        : 0.0,
                    'credit' => $closingBalance > 0
                        ? $closingBalance
                        : 0.0,
                ];
            }

            usort(
                $rows,
                static fn (array $a, array $b): int => strcasecmp(
                    $a['name'],
                    $b['name']
                )
            );

            return $rows;
        } finally {
            libxml_clear_errors();

            libxml_use_internal_errors(
                $previous
            );
        }
    }

    /**
     * Parse the ledger collection returned by Tally.
     *
     * @return array<int, array{
     *     name: string,
     *     parent: ?string,
     *     closing_balance: ?float
     * }>
     */
    private function parseLedgers(
        string $responseXml
    ): array {
        if (blank($responseXml)) {
            throw new RuntimeException(
                'Tally returned an empty ledger response.'
            );
        }

        $previous = libxml_use_internal_errors(true);

        try {
            /*
            |--------------------------------------------------------------------------
            | Clean Tally XML
            |--------------------------------------------------------------------------
            | Tally may return XML 1.0-invalid numeric control-character entities
            | such as &#4; in reserved/internal values (for example "Primary").
            | Remove those entities before passing the response to SimpleXML.
            |--------------------------------------------------------------------------
            */

            $responseXml = preg_replace_callback(
                '/&#(?:x([0-9A-Fa-f]+)|([0-9]+));/',
                static function (array $matches): string {
                    $codePoint = $matches[1] !== ''
                        ? hexdec($matches[1])
                        : (int) $matches[2];

                    $validXmlCharacter =
                        $codePoint === 0x09
                        || $codePoint === 0x0A
                        || $codePoint === 0x0D
                        || ($codePoint >= 0x20 && $codePoint <= 0xD7FF)
                        || ($codePoint >= 0xE000 && $codePoint <= 0xFFFD)
                        || ($codePoint >= 0x10000 && $codePoint <= 0x10FFFF);

                    return $validXmlCharacter
                        ? $matches[0]
                        : '';
                },
                $responseXml
            );

            if ($responseXml === null) {
                throw new RuntimeException(
                    'Unable to clean the Tally ledger response.'
                );
            }

            $xml = simplexml_load_string(
                $responseXml
            );

            if (! $xml instanceof SimpleXMLElement) {
                throw new RuntimeException(
                    'Unable to parse the Tally ledger response.'
                );
            }

            $ledgerNodes = $xml->xpath(
                '//LEDGER'
            );

            if ($ledgerNodes === false) {
                throw new RuntimeException(
                    'Unable to locate ledgers in the Tally response.'
                );
            }

            $ledgers = [];

            foreach ($ledgerNodes as $ledger) {
                $name = trim(
                    (string) (
                        $ledger['NAME']
                        ?? $ledger->NAME
                    )
                );

                if ($name === '') {
                    continue;
                }

                $parent = trim(
                    (string) $ledger->PARENT
                );

                $closingBalance = trim(
                    (string) $ledger->CLOSINGBALANCE
                );

                $ledgers[] = [
                    'name' => $name,
                    'parent' => $parent !== ''
                        ? $parent
                        : null,
                    'closing_balance' => $this->parseAmount(
                        $closingBalance
                    ),
                ];
            }

            usort(
                $ledgers,
                static fn (array $a, array $b): int => strcasecmp(
                    $a['name'],
                    $b['name']
                )
            );

            return $ledgers;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(
                $previous
            );
        }
    }

    /**
     * Convert a Tally amount to a PHP float.
     */
    private function parseAmount(
        string $value
    ): ?float {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $negative = false;

        if (
            str_ends_with(
                strtoupper($value),
                ' DR'
            )
        ) {
            $negative = true;
            $value = substr(
                $value,
                0,
                -3
            );
        } elseif (
            str_ends_with(
                strtoupper($value),
                ' CR'
            )
        ) {
            $value = substr(
                $value,
                0,
                -3
            );
        }

        $value = str_replace(
            [',', ' '],
            '',
            trim($value)
        );

        if (! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        return $negative
            ? -abs($amount)
            : $amount;
    }

    /**
     * Escape a value before inserting it into XML.
     */
    private function escape(
        string $value
    ): string {
        return htmlspecialchars(
            $value,
            ENT_XML1 | ENT_COMPAT,
            'UTF-8'
        );
    }
}
