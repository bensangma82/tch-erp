<?php

namespace App\Services\Tally;

use App\Models\FinanceVoucher;
use RuntimeException;
use SimpleXMLElement;

class TallyXmlBuilder
{
    /**
     * Build Tally-compatible XML for one Finance voucher.
     */
    public function build(FinanceVoucher $voucher): string
    {
        $voucher->loadMissing([
            'financeHead.tallyMapping',
            'financeAccount.tallyMapping',
            'destinationAccount.tallyMapping',
        ]);

        $entries = $this->ledgerEntries($voucher);
        $voucherType = $this->tallyVoucherType($voucher);
        $date = $voucher->voucher_date->format('Ymd');

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><ENVELOPE></ENVELOPE>'
        );

        /*
        |--------------------------------------------------------------------------
        | Import Header
        |--------------------------------------------------------------------------
        */

        $header = $xml->addChild('HEADER');
        $header->addChild('TALLYREQUEST', 'Import Data');

        /*
        |--------------------------------------------------------------------------
        | Import Body
        |--------------------------------------------------------------------------
        */

        $body = $xml->addChild('BODY');
        $importData = $body->addChild('IMPORTDATA');

        $requestDesc = $importData->addChild('REQUESTDESC');
        $requestDesc->addChild('REPORTNAME', 'Vouchers');

        $requestData = $importData->addChild('REQUESTDATA');

        $message = $requestData->addChild('TALLYMESSAGE');
        $message->addAttribute('xmlns:UDF', 'TallyUDF');

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucherNode = $message->addChild('VOUCHER');

        $voucherNode->addAttribute(
            'VCHTYPE',
            $voucherType
        );

        $voucherNode->addAttribute(
            'ACTION',
            'Create'
        );

        $voucherNode->addAttribute(
            'OBJVIEW',
            'Accounting Voucher View'
        );

        $voucherNode->addChild(
            'DATE',
            $date
        );

        $voucherNode->addChild(
            'VCHSTATUSDATE',
            $date
        );

        if (filled($voucher->narration)) {
            $voucherNode->addChild(
                'NARRATION',
                $this->escape($voucher->narration)
            );
        }

        $voucherNode->addChild(
            'VOUCHERTYPENAME',
            $voucherType
        );

        /*
        |--------------------------------------------------------------------------
        | Party Ledger
        |--------------------------------------------------------------------------
        | Receipt  : Cash / Bank receiving the money
        | Payment  : Cash / Bank paying the money
        | Contra   : Source Cash / Bank account
        |--------------------------------------------------------------------------
        */

        $voucherNode->addChild(
            'PARTYLEDGERNAME',
            $this->escape(
                $this->partyLedger($voucher)
            )
        );

        /*
        |--------------------------------------------------------------------------
        | ERP Reference
        |--------------------------------------------------------------------------
        | Let Tally maintain its own voucher numbering.
        | Store the ERP voucher number as the reference for reconciliation.
        |--------------------------------------------------------------------------
        */

        $reference = filled($voucher->reference_no)
            ? $voucher->reference_no
            : $voucher->voucher_no;

        $voucherNode->addChild(
            'REFERENCE',
            $this->escape($reference)
        );

        $voucherNode->addChild(
            'PERSISTEDVIEW',
            'Accounting Voucher View'
        );

        $voucherNode->addChild(
            'EFFECTIVEDATE',
            $date
        );

        $voucherNode->addChild(
            'ISOPTIONAL',
            'No'
        );

        $voucherNode->addChild(
            'ISCANCELLED',
            'No'
        );

        $voucherNode->addChild(
            'ISPOSTDATED',
            'No'
        );

        $voucherNode->addChild(
            'ISINVOICE',
            'No'
        );

        /*
        |--------------------------------------------------------------------------
        | Ledger Entries
        |--------------------------------------------------------------------------
        |
        | Tally's own exported XML uses:
        |
        | Debit:
        |   ISDEEMEDPOSITIVE = Yes
        |   AMOUNT            = negative
        |
        | Credit:
        |   ISDEEMEDPOSITIVE = No
        |   AMOUNT            = positive
        |
        |--------------------------------------------------------------------------
        */

        foreach ($entries as $entry) {
            $ledgerEntry = $voucherNode->addChild(
                'ALLLEDGERENTRIES.LIST'
            );

            $ledgerEntry->addChild(
                'LEDGERNAME',
                $this->escape($entry['ledger'])
            );

            $ledgerEntry->addChild(
                'ISDEEMEDPOSITIVE',
                $entry['debit'] ? 'Yes' : 'No'
            );

            $ledgerEntry->addChild(
                'ISPARTYLEDGER',
                $entry['party'] ? 'Yes' : 'No'
            );

            $ledgerEntry->addChild(
                'AMOUNT',
                $this->formatAmount(
                    $entry['amount'],
                    $entry['debit']
                )
            );
        }

        $output = $xml->asXML();

        if ($output === false) {
            throw new RuntimeException(
                'Unable to generate Tally XML.'
            );
        }

        return $output;
    }

    /**
     * @return array<int, array{
     *     ledger: string,
     *     amount: float,
     *     debit: bool,
     *     party: bool
     * }>
     */
    private function ledgerEntries(FinanceVoucher $voucher): array
    {
        $amount = (float) $voucher->amount;

        if ($amount <= 0) {
            throw new RuntimeException(
                'Voucher amount must be greater than zero.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Receipt
        |--------------------------------------------------------------------------
        | Debit  : Cash / Bank
        | Credit : Income
        |
        | Order mirrors TallyPrime's own exported Receipt XML:
        | Income first, Cash / Bank second.
        |--------------------------------------------------------------------------
        */

        if ($voucher->voucher_type === 'receipt') {
            return [
                [
                    'ledger' => $this->headLedger(
                        $voucher->financeHead
                    ),
                    'amount' => $amount,
                    'debit' => false,
                    'party' => false,
                ],
                [
                    'ledger' => $this->accountLedger(
                        $voucher->financeAccount
                    ),
                    'amount' => $amount,
                    'debit' => true,
                    'party' => true,
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        | Debit  : Expense
        | Credit : Cash / Bank
        |--------------------------------------------------------------------------
        */

        if ($voucher->voucher_type === 'payment') {
            return [
                [
                    'ledger' => $this->headLedger(
                        $voucher->financeHead
                    ),
                    'amount' => $amount,
                    'debit' => true,
                    'party' => false,
                ],
                [
                    'ledger' => $this->accountLedger(
                        $voucher->financeAccount
                    ),
                    'amount' => $amount,
                    'debit' => false,
                    'party' => true,
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Transfer / Contra
        |--------------------------------------------------------------------------
        | Debit  : Destination Cash / Bank
        | Credit : Source Cash / Bank
        |--------------------------------------------------------------------------
        */

        if ($voucher->voucher_type === 'transfer') {
            return [
                [
                    'ledger' => $this->accountLedger(
                        $voucher->destinationAccount
                    ),
                    'amount' => $amount,
                    'debit' => true,
                    'party' => false,
                ],
                [
                    'ledger' => $this->accountLedger(
                        $voucher->financeAccount
                    ),
                    'amount' => $amount,
                    'debit' => false,
                    'party' => true,
                ],
            ];
        }

        throw new RuntimeException(
            sprintf(
                'Voucher type "%s" is not supported.',
                $voucher->voucher_type
            )
        );
    }

    private function tallyVoucherType(FinanceVoucher $voucher): string
    {
        return match ($voucher->voucher_type) {
            'receipt' => 'Receipt',
            'payment' => 'Payment',
            'transfer' => 'Contra',

            default => throw new RuntimeException(
                sprintf(
                    'Voucher type "%s" is not supported.',
                    $voucher->voucher_type
                )
            ),
        };
    }

    private function partyLedger(FinanceVoucher $voucher): string
    {
        return match ($voucher->voucher_type) {
            'receipt',
            'payment',
            'transfer' => $this->accountLedger(
                $voucher->financeAccount
            ),

            default => throw new RuntimeException(
                sprintf(
                    'Voucher type "%s" has no supported party ledger.',
                    $voucher->voucher_type
                )
            ),
        };
    }

    private function headLedger($head): string
    {
        $ledger = $head?->tallyMapping?->tally_ledger_name;

        if (blank($ledger)) {
            throw new RuntimeException(
                'Finance Head has no Tally ledger mapping.'
            );
        }

        return $ledger;
    }

    private function accountLedger($account): string
    {
        $ledger = $account?->tallyMapping?->tally_ledger_name;

        if (blank($ledger)) {
            throw new RuntimeException(
                'Finance Account has no Tally ledger mapping.'
            );
        }

        return $ledger;
    }

    private function formatAmount(float $amount, bool $debit): string
    {
        $value = number_format(
            abs($amount),
            2,
            '.',
            ''
        );

        return $debit ? '-'.$value : $value;
    }

    /**
     * Escape text before passing it to SimpleXMLElement::addChild().
     */
    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_XML1 | ENT_COMPAT,
            'UTF-8'
        );
    }
}
