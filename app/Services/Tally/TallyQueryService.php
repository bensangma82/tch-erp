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
     * Retrieve the Tally group master.
     *
     * This is a read-only Tally request.
     *
     * @return array<int, array{
     *     name: string,
     *     parent: ?string,
     *     reserved_name: ?string
     * }>
     */
    public function groups(): array
    {
        $responseXml = $this->tallyHttpClient->postXml(
            $this->groupRequestXml()
        );

        return $this->parseGroups(
            $responseXml
        );
    }

    /**
     * Retrieve accounting vouchers from the configured Tally company.
     *
     * This is a read-only Tally request.
     *
     * Each voucher contains its accounting ledger entries so that
     * cash and bank movements can be analysed independently of the
     * voucher's party ledger.
     *
     * @return array<int, array{
     *     date: ?string,
     *     voucher_type: ?string,
     *     voucher_number: ?string,
     *     narration: ?string,
     *     party_ledger: ?string,
     *     remote_id: ?string,
     *     guid: ?string,
     *     ledger_entries: array<int, array{
     *         ledger_name: string,
     *         amount: float,
     *         is_deemed_positive: ?bool
     *     }>
     * }>
     */
    public function vouchers(): array
    {
        $responseXml = $this->tallyHttpClient->postXml(
            $this->voucherRequestXml()
        );

        return $this->parseVouchers(
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

    /**
     * Retrieve the current Income & Expenditure data from Tally.
     *
     * Ledgers are classified using the Tally group hierarchy so that
     * custom subgroups beneath the standard income/expense groups are
     * handled correctly.
     *
     * @return array{
     *     income: array<int, array{
     *         name: string,
     *         group: ?string,
     *         amount: float
     *     }>,
     *     expenditure: array<int, array{
     *         name: string,
     *         group: ?string,
     *         amount: float
     *     }>,
     *     total_income: float,
     *     total_expenditure: float,
     *     surplus_deficit: float
     * }
     */
    public function incomeAndExpenditure(): array
    {
        $ledgers = $this->ledgers();
        $groups = $this->groups();

        return $this->buildIncomeAndExpenditure(
            $ledgers,
            $groups
        );
    }

    /**
     * Retrieve the current Balance Sheet data from Tally.
     *
     * Ledgers are classified using the Tally group hierarchy so that
     * custom subgroups beneath the standard asset, liability, and
     * capital groups are handled correctly.
     *
     * The current Profit & Loss balance is included separately so that
     * the Balance Sheet reflects the current-period surplus or deficit.
     *
     * @return array{
     *     assets: array<int, array{
     *         name: string,
     *         group: ?string,
     *         amount: float
     *     }>,
     *     liabilities: array<int, array{
     *         name: string,
     *         group: ?string,
     *         amount: float
     *     }>,
     *     current_profit_loss: float,
     *     total_assets: float,
     *     total_liabilities: float,
     *     difference: float
     * }
     */
    public function balanceSheet(): array
    {
        $ledgers = $this->ledgers();
        $groups = $this->groups();

        return $this->buildBalanceSheet(
            $ledgers,
            $groups
        );
    }

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
     * Build a Cash Flow report from Tally voucher transactions.
     *
     * Cash and bank movements are derived from the actual accounting
     * entries rather than from voucher type alone.
     *
     * Tally accounting signs:
     *     negative = debit
     *     positive = credit
     *
     * Therefore for a cash/bank ledger:
     *     negative amount = cash inflow
     *     positive amount = cash outflow
     *
     * @return array{
     *     operating: array,
     *     investing: array,
     *     financing: array,
     *     unclassified: array,
     *     total_operating: float,
     *     total_investing: float,
     *     total_financing: float,
     *     total_unclassified: float,
     *     net_cash_flow: float
     * }
     */
    public function cashFlow(): array
    {
        $vouchers = $this->vouchers();
        $ledgers = $this->ledgers();
        $groups = $this->groups();
        $trialBalance = $this->trialBalance();

        return $this->buildCashFlow(
            $vouchers,
            $ledgers,
            $groups,
            $trialBalance
        );
    }

    /**
     * Determine whether a Tally group belongs to cash and cash equivalents.
     *
     * For now we include:
     *     Cash-in-Hand
     *     Bank Accounts
     *     Any custom subgroup beneath either of them
     *
     * Bank OD A/c is deliberately excluded until its cash-equivalent
     * treatment is explicitly decided.
     *
     * @param  array<string, string|null>  $groupParents
     */
    private function isCashEquivalentGroup(
        ?string $groupName,
        array $groupParents
    ): bool {
        if ($groupName === null || trim($groupName) === '') {
            return false;
        }

        $cashRoots = [
            'cash-in-hand',
            'bank accounts',
        ];

        $current = trim($groupName);
        $visited = [];

        while ($current !== '') {
            $key = strtolower($current);

            if (in_array($key, $cashRoots, true)) {
                return true;
            }

            /*
             * Protect against malformed or circular Tally group hierarchies.
             */
            if (isset($visited[$key])) {
                return false;
            }

            $visited[$key] = true;

            if (! array_key_exists($key, $groupParents)) {
                return false;
            }

            $parent = $groupParents[$key];

            if ($parent === null || trim($parent) === '') {
                return false;
            }

            $current = trim($parent);
        }

        return false;
    }

    /**
     * Classify a non-cash Tally group for Cash Flow reporting.
     *
     * The classification follows the Tally group hierarchy rather than
     * ledger names so that custom subgroups inherit the accounting
     * treatment of their parent group.
     *
     * Groups that cannot be classified safely are deliberately returned
     * as null and will appear under Unclassified.
     *
     * @param  array<string, string|null>  $groupParents
     */
    private function classifyCashFlowGroup(
        ?string $groupName,
        array $groupParents
    ): ?string {
        if ($groupName === null || trim($groupName) === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Cash Flow classification roots
        |--------------------------------------------------------------------------
        */

        $operatingRoots = [
            'direct expenses',
            'indirect expenses',
            'purchase accounts',
            'direct incomes',
            'indirect incomes',
            'sales accounts',
            'sundry debtors',
            'sundry creditors',
            'stock-in-hand',
            'duties & taxes',
            'provisions',
        ];

        $investingRoots = [
            'fixed assets',
            'investments',
            'deposits (asset)',
            'loans & advances (asset)',
        ];

        $financingRoots = [
            'capital account',
            'loans (liability)',
        ];

        $current = trim($groupName);
        $visited = [];

        while ($current !== '') {
            $key = strtolower($current);

            if (in_array($key, $operatingRoots, true)) {
                return 'operating';
            }

            if (in_array($key, $investingRoots, true)) {
                return 'investing';
            }

            if (in_array($key, $financingRoots, true)) {
                return 'financing';
            }

            /*
             * Protect against malformed or circular Tally group hierarchies.
             */
            if (isset($visited[$key])) {
                return null;
            }

            $visited[$key] = true;

            if (! array_key_exists($key, $groupParents)) {
                return null;
            }

            $parent = $groupParents[$key];

            if ($parent === null || trim($parent) === '') {
                return null;
            }

            $current = trim($parent);
        }

        return null;
    }

    /**
     * Build a read-only Tally XML request for accounting vouchers.
     */
    private function voucherRequestXml(): string
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
        <ID>ERP Voucher Collection</ID>
    </HEADER>

    <BODY>
        <DESC>
            <STATICVARIABLES>
                <SVEXPORTFORMAT>\$\$SysName:XML</SVEXPORTFORMAT>
                <SVCURRENTCOMPANY>{$company}</SVCURRENTCOMPANY>
            </STATICVARIABLES>

            <TDL>
                <TDLMESSAGE>
                    <COLLECTION NAME="ERP Voucher Collection">
                        <TYPE>Voucher</TYPE>
                        <FETCH>
                            DATE,
                            VOUCHERTYPENAME,
                            VOUCHERNUMBER,
                            NARRATION,
                            PARTYLEDGERNAME,
                            GUID,
                            ALLLEDGERENTRIES.LIST
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
     * Build a read-only Tally XML request for the group master.
     */
    private function groupRequestXml(): string
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
        <ID>ERP Group Collection</ID>
    </HEADER>

    <BODY>
        <DESC>
            <STATICVARIABLES>
                <SVEXPORTFORMAT>\$\$SysName:XML</SVEXPORTFORMAT>
                <SVCURRENTCOMPANY>{$company}</SVCURRENTCOMPANY>
            </STATICVARIABLES>

            <TDL>
                <TDLMESSAGE>
                    <COLLECTION NAME="ERP Group Collection">
                        <TYPE>Group</TYPE>
                        <FETCH>
                            NAME,
                            PARENT
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

    /**
     * Parse the group master returned by Tally.
     *
     * @return array<int, array{
     *     name: string,
     *     parent: ?string,
     *     reserved_name: ?string
     * }>
     */

    /**
     * Parse accounting vouchers returned by Tally.
     *
     * Tally accounting entry amounts use:
     *     negative = debit
     *     positive = credit
     *
     * Cash-flow interpretation is deliberately NOT performed here.
     * This method only normalizes the raw voucher data.
     *
     * @return array<int, array{
     *     date: ?string,
     *     voucher_type: ?string,
     *     voucher_number: ?string,
     *     narration: ?string,
     *     party_ledger: ?string,
     *     remote_id: ?string,
     *     guid: ?string,
     *     ledger_entries: array<int, array{
     *         ledger_name: string,
     *         amount: float,
     *         is_deemed_positive: ?bool
     *     }>
     * }>
     */
    private function parseVouchers(
        string $responseXml
    ): array {
        if (blank($responseXml)) {
            throw new RuntimeException(
                'Tally returned an empty voucher response.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Clean Tally XML
        |--------------------------------------------------------------------------
        | Tally may return XML 1.0-invalid numeric control-character entities.
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
                'Unable to clean the Tally voucher response.'
            );
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string(
                $responseXml
            );

            if (! $xml instanceof SimpleXMLElement) {
                throw new RuntimeException(
                    'Unable to parse the Tally voucher response.'
                );
            }

            $voucherNodes = $xml->xpath(
                '//VOUCHER'
            );

            if ($voucherNodes === false) {
                throw new RuntimeException(
                    'Unable to locate vouchers in the Tally response.'
                );
            }

            $vouchers = [];

            foreach ($voucherNodes as $voucher) {
                $date = trim(
                    (string) $voucher->DATE
                );

                $voucherType = trim(
                    (string) $voucher->VOUCHERTYPENAME
                );

                $voucherNumber = trim(
                    (string) $voucher->VOUCHERNUMBER
                );

                $narration = trim(
                    (string) $voucher->NARRATION
                );

                $partyLedger = trim(
                    (string) $voucher->PARTYLEDGERNAME
                );

                $remoteId = trim(
                    (string) $voucher['REMOTEID']
                );

                $guid = trim(
                    (string) $voucher->GUID
                );

                $ledgerEntries = [];

                foreach ($voucher->{'ALLLEDGERENTRIES.LIST'} as $entry) {
                    $ledgerName = trim(
                        (string) $entry->LEDGERNAME
                    );

                    if ($ledgerName === '') {
                        continue;
                    }

                    $amount = $this->parseAmount(
                        trim(
                            (string) $entry->AMOUNT
                        )
                    ) ?? 0.0;

                    $isDeemedPositiveRaw = strtolower(
                        trim(
                            (string) $entry->ISDEEMEDPOSITIVE
                        )
                    );

                    $isDeemedPositive = match ($isDeemedPositiveRaw) {
                        'yes' => true,
                        'no' => false,
                        default => null,
                    };

                    $ledgerEntries[] = [
                        'ledger_name' => $ledgerName,
                        'amount' => $amount,
                        'is_deemed_positive' => $isDeemedPositive,
                    ];
                }

                $vouchers[] = [
                    'date' => $date !== ''
                        ? $date
                        : null,
                    'voucher_type' => $voucherType !== ''
                        ? $voucherType
                        : null,
                    'voucher_number' => $voucherNumber !== ''
                        ? $voucherNumber
                        : null,
                    'narration' => $narration !== ''
                        ? $narration
                        : null,
                    'party_ledger' => $partyLedger !== ''
                        ? $partyLedger
                        : null,
                    'remote_id' => $remoteId !== ''
                        ? $remoteId
                        : null,
                    'guid' => $guid !== ''
                        ? $guid
                        : null,
                    'ledger_entries' => $ledgerEntries,
                ];
            }

            usort(
                $vouchers,
                static function (array $a, array $b): int {
                    $dateComparison = strcmp(
                        $a['date'] ?? '',
                        $b['date'] ?? ''
                    );

                    if ($dateComparison !== 0) {
                        return $dateComparison;
                    }

                    return strnatcasecmp(
                        $a['voucher_number'] ?? '',
                        $b['voucher_number'] ?? ''
                    );
                }
            );

            return $vouchers;
        } finally {
            libxml_clear_errors();

            libxml_use_internal_errors(
                $previous
            );
        }
    }

    private function parseGroups(
        string $responseXml
    ): array {
        if (blank($responseXml)) {
            throw new RuntimeException(
                'Tally returned an empty group master response.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Clean Tally XML
        |--------------------------------------------------------------------------
        | Tally may prefix reserved/internal group names with XML 1.0-invalid
        | numeric control-character entities such as &#4;.
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
                'Unable to clean the Tally group master response.'
            );
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string(
                $responseXml
            );

            if (! $xml instanceof SimpleXMLElement) {
                throw new RuntimeException(
                    'Unable to parse the Tally group master response.'
                );
            }

            $groupNodes = $xml->xpath(
                '//GROUP'
            );

            if ($groupNodes === false) {
                throw new RuntimeException(
                    'Unable to locate groups in the Tally group master response.'
                );
            }

            $rows = [];

            foreach ($groupNodes as $group) {
                $name = trim(
                    (string) (
                        $group['NAME']
                        ?? $group->NAME
                    )
                );

                if ($name === '') {
                    continue;
                }

                $parent = trim(
                    (string) $group->PARENT
                );

                $reservedName = trim(
                    (string) $group['RESERVEDNAME']
                );

                $rows[] = [
                    'name' => $name,
                    'parent' => $parent !== ''
                        ? $parent
                        : null,
                    'reserved_name' => $reservedName !== ''
                        ? $reservedName
                        : null,
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
     * Build the Income & Expenditure report from Tally ledgers and groups.
     *
     * @param  array<int, array{
     *     name: string,
     *     parent: ?string,
     *     closing_balance: ?float
     * }>  $ledgers
     * @param  array<int, array{
     *     name: string,
     *     parent: ?string,
     *     reserved_name: ?string
     * }>  $groups
     */
    private function buildIncomeAndExpenditure(
        array $ledgers,
        array $groups
    ): array {
        $groupParents = [];

        foreach ($groups as $group) {
            $groupParents[
                strtolower($group['name'])
            ] = $group['parent'];
        }

        $incomeRoots = [
            'direct incomes',
            'indirect incomes',
            'sales accounts',
        ];

        $expenditureRoots = [
            'direct expenses',
            'indirect expenses',
            'purchase accounts',
        ];

        $income = [];
        $expenditure = [];

        foreach ($ledgers as $ledger) {
            $balance = $ledger['closing_balance'];

            if ($balance === null || abs($balance) < 0.00001) {
                continue;
            }

            $classification = $this->classifyProfitAndLossGroup(
                $ledger['parent'],
                $groupParents,
                $incomeRoots,
                $expenditureRoots
            );

            if ($classification === null) {
                continue;
            }

            /*
             * Tally balances use:
             *     positive = credit
             *     negative = debit
             *
             * Income therefore contributes its credit balance positively,
             * while a debit balance reduces income.
             *
             * Expenditure contributes its debit balance positively,
             * while a credit balance reduces expenditure.
             */
            $amount = $classification === 'income'
                ? $balance
                : -$balance;

            $row = [
                'name' => $ledger['name'],
                'group' => $ledger['parent'],
                'amount' => $amount,
            ];

            if ($classification === 'income') {
                $income[] = $row;
            } else {
                $expenditure[] = $row;
            }
        }

        $totalIncome = array_sum(
            array_column($income, 'amount')
        );

        $totalExpenditure = array_sum(
            array_column($expenditure, 'amount')
        );

        return [
            'income' => $income,
            'expenditure' => $expenditure,
            'total_income' => $totalIncome,
            'total_expenditure' => $totalExpenditure,
            'surplus_deficit' => $totalIncome - $totalExpenditure,
        ];
    }

    /**
     * Determine whether a Tally group ultimately belongs to
     * an income or expenditure root group.
     *
     * @param  array<string, string|null>  $groupParents
     * @param  array<int, string>  $incomeRoots
     * @param  array<int, string>  $expenditureRoots
     */
    private function classifyProfitAndLossGroup(
        ?string $groupName,
        array $groupParents,
        array $incomeRoots,
        array $expenditureRoots
    ): ?string {
        if ($groupName === null || trim($groupName) === '') {
            return null;
        }

        $current = trim($groupName);
        $visited = [];

        while ($current !== '') {
            $key = strtolower($current);

            if (in_array($key, $incomeRoots, true)) {
                return 'income';
            }

            if (in_array($key, $expenditureRoots, true)) {
                return 'expenditure';
            }

            /*
             * Protect against malformed/circular group hierarchies.
             */
            if (isset($visited[$key])) {
                return null;
            }

            $visited[$key] = true;

            if (! array_key_exists($key, $groupParents)) {
                return null;
            }

            $parent = $groupParents[$key];

            if ($parent === null || trim($parent) === '') {
                return null;
            }

            $current = trim($parent);
        }

        return null;
    }

    /**
     * Build the current Balance Sheet from Tally ledgers and groups.
     *
     * Asset and liability classifications are determined from the Tally
     * group hierarchy rather than from ledger names. This allows custom
     * subgroups to inherit the correct Balance Sheet classification.
     *
     * @param  array<int, array{
     *     name: string,
     *     parent: ?string,
     *     closing_balance: ?float
     * }>  $ledgers
     * @param  array<int, array{
     *     name: string,
     *     parent: ?string,
     *     reserved_name: ?string
     * }>  $groups
     */
    private function buildBalanceSheet(
        array $ledgers,
        array $groups
    ): array {
        $groupParents = [];

        foreach ($groups as $group) {
            $groupParents[
                strtolower($group['name'])
            ] = $group['parent'];
        }

        /*
         * Standard Tally Balance Sheet root groups.
         *
         * Descendant groups are classified automatically by walking
         * upwards through the Tally group hierarchy.
         */
        $assetRoots = [
            'current assets',
            'fixed assets',
            'investments',
            'misc. expenses (asset)',
        ];

        $liabilityRoots = [
            'capital account',
            'current liabilities',
            'loans (liability)',
        ];

        $assets = [];
        $liabilities = [];
        $currentProfitLoss = 0.0;

        foreach ($ledgers as $ledger) {
            $balance = $ledger['closing_balance'];

            if ($balance === null || abs($balance) < 0.00001) {
                continue;
            }

            /*
             * Tally exposes the current-period result through its reserved
             * Profit & Loss A/c ledger. It is handled separately because it
             * belongs to Primary rather than to a normal Balance Sheet group.
             */
            if (
                strcasecmp(
                    $ledger['name'],
                    'Profit & Loss A/c'
                ) === 0
            ) {
                $currentProfitLoss = $balance;

                continue;
            }

            $classification = $this->classifyBalanceSheetGroup(
                $ledger['parent'],
                $groupParents,
                $assetRoots,
                $liabilityRoots
            );

            if ($classification === null) {
                continue;
            }

            /*
             * Tally balances use:
             *     negative = debit
             *     positive = credit
             *
             * Assets are therefore presented as positive for debit balances.
             * A credit balance in an asset ledger remains negative so that
             * abnormal/contra balances are not silently reclassified.
             *
             * Liabilities and capital are presented as positive for credit
             * balances. Debit balances remain negative.
             */
            $amount = $classification === 'asset'
                ? -$balance
                : $balance;

            $row = [
                'name' => $ledger['name'],
                'group' => $ledger['parent'],
                'amount' => $amount,
            ];

            if ($classification === 'asset') {
                $assets[] = $row;
            } else {
                $liabilities[] = $row;
            }
        }

        /*
         * Current Profit & Loss is part of the liability/equity side:
         *
         *     positive = surplus
         *     negative = deficit
         */
        $totalAssets = array_sum(
            array_column($assets, 'amount')
        );

        $totalLiabilities = array_sum(
            array_column($liabilities, 'amount')
        ) + $currentProfitLoss;

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'current_profit_loss' => $currentProfitLoss,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'difference' => $totalAssets - $totalLiabilities,
        ];
    }

    /**
     * Build a transaction-based Cash Flow report.
     *
     * Cash and bank accounts are identified from the Tally group hierarchy.
     * The economic cash movement is the inverse of Tally's accounting sign:
     *
     *     Tally debit  (negative) = cash inflow
     *     Tally credit (positive) = cash outflow
     *
     * Transfers entirely between cash/bank accounts are excluded because
     * they do not change total cash and cash equivalents.
     */
    private function buildCashFlow(
        array $vouchers,
        array $ledgers,
        array $groups,
        array $trialBalance
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Build Tally hierarchy maps
        |--------------------------------------------------------------------------
        */

        $groupParents = [];

        foreach ($groups as $group) {
            $groupParents[
                strtolower($group['name'])
            ] = $group['parent'];
        }

        $ledgerGroups = [];

        foreach ($ledgers as $ledger) {
            $ledgerGroups[
                strtolower($ledger['name'])
            ] = $ledger['parent'];
        }

        /*
        |--------------------------------------------------------------------------
        | Cash-flow sections
        |--------------------------------------------------------------------------
        */

        $operating = [];
        $investing = [];
        $financing = [];
        $unclassified = [];

        /*
        |--------------------------------------------------------------------------
        | Process vouchers
        |--------------------------------------------------------------------------
        */

        foreach ($vouchers as $voucher) {
            $cashEntries = [];
            $nonCashEntries = [];

            foreach ($voucher['ledger_entries'] as $entry) {
                $ledgerName = $entry['ledger_name'];

                $parentGroup = $ledgerGroups[
                    strtolower($ledgerName)
                ] ?? null;

                if (
                    $this->isCashEquivalentGroup(
                        $parentGroup,
                        $groupParents
                    )
                ) {
                    $cashEntries[] = $entry;
                } else {
                    $nonCashEntries[] = $entry;
                }
            }

            /*
             * No cash/bank movement means this voucher has no direct
             * Cash Flow effect.
             */
            if ($cashEntries === []) {
                continue;
            }

            /*
             * If every ledger entry belongs to cash/bank accounts, this is
             * an internal transfer and must not affect total Cash Flow.
             */
            if ($nonCashEntries === []) {
                continue;
            }

            $cashMovement = 0.0;

            foreach ($cashEntries as $cashEntry) {
                /*
                 * Reverse Tally's accounting sign to obtain the economic
                 * movement of cash:
                 *
                 *     -500 debit  => +500 inflow
                 *     +500 credit => -500 outflow
                 */
                $cashMovement += -1 * $cashEntry['amount'];
            }

            if (abs($cashMovement) < 0.00001) {
                continue;
            }

            /*
|--------------------------------------------------------------------------
| Classify cash movement
|--------------------------------------------------------------------------
| Classify each non-cash accounting entry using its Tally group
| hierarchy. The signed Tally amount of the balancing non-cash entry
| represents its contribution to the economic cash movement.
|
| Anything that cannot be classified safely remains unclassified.
|--------------------------------------------------------------------------
*/

            $classifiedMovement = 0.0;

            foreach ($nonCashEntries as $entry) {
                $ledgerName = $entry['ledger_name'];

                $parentGroup = $ledgerGroups[
                    strtolower($ledgerName)
                ] ?? null;

                $section = $this->classifyCashFlowGroup(
                    $parentGroup,
                    $groupParents
                );

                $amount = (float) $entry['amount'];

                if (abs($amount) < 0.00001) {
                    continue;
                }

                $row = [
                    'date' => $voucher['date'],
                    'voucher_type' => $voucher['voucher_type'],
                    'voucher_number' => $voucher['voucher_number'],
                    'narration' => $voucher['narration'],
                    'party_ledger' => $voucher['party_ledger'],
                    'counterparty_ledger' => $ledgerName,
                    'counterparty_group' => $parentGroup,
                    'amount' => $amount,
                ];

                match ($section) {
                    'operating' => $operating[] = $row,
                    'investing' => $investing[] = $row,
                    'financing' => $financing[] = $row,
                    default => $unclassified[] = $row,
                };

                $classifiedMovement += $amount;
            }

            /*
            |--------------------------------------------------------------------------
            | Reconciliation residual
            |--------------------------------------------------------------------------
            | The classified non-cash entries should add back to the movement
            | calculated directly from the cash/bank entries. If they do not,
            | preserve the difference explicitly rather than silently losing it.
            |--------------------------------------------------------------------------
            */

            $residual = $cashMovement - $classifiedMovement;

            if (abs($residual) >= 0.00001) {
                $unclassified[] = [
                    'date' => $voucher['date'],
                    'voucher_type' => $voucher['voucher_type'],
                    'voucher_number' => $voucher['voucher_number'],
                    'narration' => $voucher['narration'],
                    'party_ledger' => $voucher['party_ledger'],
                    'counterparty_ledger' => null,
                    'counterparty_group' => null,
                    'amount' => $residual,
                ];
            }
        }

        $totalOperating = array_sum(
            array_column($operating, 'amount')
        );

        $totalInvesting = array_sum(
            array_column($investing, 'amount')
        );

        $totalFinancing = array_sum(
            array_column($financing, 'amount')
        );

        $totalUnclassified = array_sum(
            array_column($unclassified, 'amount')
        );
        /*
        |--------------------------------------------------------------------------
        | Cash and cash-equivalent reconciliation
        |--------------------------------------------------------------------------
        | Trial Balance amounts follow Tally's accounting sign convention:
        |
        |     debit  = negative
        |     credit = positive
        |
        | Cash and bank assets therefore use the inverse sign for their
        | economic balance.
        |--------------------------------------------------------------------------
        */

        $openingCash = 0.0;
        $closingCash = 0.0;

        foreach ($trialBalance as $row) {
            $parentGroup = $row['parent'] ?? null;

            if (
                ! $this->isCashEquivalentGroup(
                    $parentGroup,
                    $groupParents
                )
            ) {
                continue;
            }

            $openingCash += -1 * (float) $row['opening_balance'];
            $closingCash += -1 * (float) $row['closing_balance'];
        }

        $netCashFlow = $totalOperating
            + $totalInvesting
            + $totalFinancing
            + $totalUnclassified;

        $expectedClosingCash = $openingCash + $netCashFlow;

        $reconciliationDifference = $expectedClosingCash
            - $closingCash;

        return [
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'unclassified' => $unclassified,
            'total_operating' => $totalOperating,
            'total_investing' => $totalInvesting,
            'total_financing' => $totalFinancing,
            'total_unclassified' => $totalUnclassified,
            'net_cash_flow' => $netCashFlow,
            'opening_cash' => $openingCash,
            'closing_cash' => $closingCash,
            'expected_closing_cash' => $expectedClosingCash,
            'reconciliation_difference' => $reconciliationDifference,
        ];
    }

    /**
     * Determine whether a Tally group ultimately belongs to
     * an asset or liability/equity root group.
     *
     * @param  array<string, string|null>  $groupParents
     * @param  array<int, string>  $assetRoots
     * @param  array<int, string>  $liabilityRoots
     */
    private function classifyBalanceSheetGroup(
        ?string $groupName,
        array $groupParents,
        array $assetRoots,
        array $liabilityRoots
    ): ?string {
        if ($groupName === null || trim($groupName) === '') {
            return null;
        }

        $current = trim($groupName);
        $visited = [];

        while ($current !== '') {
            $key = strtolower($current);

            if (in_array($key, $assetRoots, true)) {
                return 'asset';
            }

            if (in_array($key, $liabilityRoots, true)) {
                return 'liability';
            }

            /*
             * Protect against malformed/circular group hierarchies.
             */
            if (isset($visited[$key])) {
                return null;
            }

            $visited[$key] = true;

            if (! array_key_exists($key, $groupParents)) {
                return null;
            }

            $parent = $groupParents[$key];

            if ($parent === null || trim($parent) === '') {
                return null;
            }

            $current = trim($parent);
        }

        return null;
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
