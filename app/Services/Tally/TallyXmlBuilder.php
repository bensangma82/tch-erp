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

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><ENVELOPE></ENVELOPE>'
        );

        $header = $xml->addChild('HEADER');
        $header->addChild('TALLYREQUEST', 'Import Data');

        $body = $xml->addChild('BODY');
        $importData = $body->addChild('IMPORTDATA');

        $requestDesc = $importData->addChild('REQUESTDESC');
        $requestDesc->addChild('REPORTNAME', 'Vouchers');

        $requestData = $importData->addChild('REQUESTDATA');
        $message = $requestData->addChild('TALLYMESSAGE');

        $voucherNode = $message->addChild('VOUCHER');

        $voucherNode->addAttribute(
            'VCHTYPE',
            $this->tallyVoucherType($voucher)
        );

        $voucherNode->addAttribute(
            'ACTION',
            'Create'
        );

        $voucherNode->addChild(
            'DATE',
            $voucher->voucher_date->format('Ymd')
        );

        $voucherNode->addChild(
            'VOUCHERTYPENAME',
            $this->tallyVoucherType($voucher)
        );

        $voucherNode->addChild(
            'VOUCHERNUMBER',
            $this->escape($voucher->voucher_no)
        );

        if (filled($voucher->reference_no)) {
            $voucherNode->addChild(
                'REFERENCE',
                $this->escape($voucher->reference_no)
            );
        }

        if (filled($voucher->narration)) {
            $voucherNode->addChild(
                'NARRATION',
                $this->escape($voucher->narration)
            );
        }

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
     *     debit: bool
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
        |--------------------------------------------------------------------------
        */

        if ($voucher->voucher_type === 'receipt') {
            return [
                [
                    'ledger' => $this->accountLedger(
                        $voucher->financeAccount
                    ),
                    'amount' => $amount,
                    'debit' => true,
                ],
                [
                    'ledger' => $this->headLedger(
                        $voucher->financeHead
                    ),
                    'amount' => $amount,
                    'debit' => false,
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
                ],
                [
                    'ledger' => $this->accountLedger(
                        $voucher->financeAccount
                    ),
                    'amount' => $amount,
                    'debit' => false,
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
                ],
                [
                    'ledger' => $this->accountLedger(
                        $voucher->financeAccount
                    ),
                    'amount' => $amount,
                    'debit' => false,
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
