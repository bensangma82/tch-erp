<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\PharmacySale;
use RuntimeException;
use SimpleXMLElement;

class TallyPharmacySalesXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'pharmacy_sale'
            || $journal->source_event !== 'completed'
        ) {
            throw new RuntimeException('Invalid Pharmacy Sales journal.');
        }

        $sale = PharmacySale::findOrFail($journal->source_id);

        if (
            $sale->admission_id !== null
            || blank($sale->sale_no)
            || (string) $journal->reference_no !== (string) $sale->sale_no
        ) {
            throw new RuntimeException('Invalid OPD Pharmacy sale reference.');
        }

        $paise = static function ($value): int {
            $value = (string) $value;

            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                throw new RuntimeException('Invalid monetary amount.');
            }

            [$whole, $fraction] = array_pad(
                explode('.', $value, 2), 2, '0'
            );

            return (int) $whole * 100
                + (int) str_pad($fraction, 2, '0');
        };

        $total = $paise($sale->total_amount);
        $paid = $paise($sale->paid_amount);
        $balance = $paise($sale->balance_amount);

        if ($total <= 0 || $paid + $balance !== $total) {
            throw new RuntimeException('Pharmacy Sales totals do not reconcile.');
        }

        $expected = [
            '4306' => [0, $paise($sale->taxable_amount)],
            '2311' => [0, $paise($sale->cgst_amount)],
            '2312' => [0, $paise($sale->sgst_amount)],
            '2313' => [0, $paise($sale->igst_amount)],
            '1200' => [$balance, 0],
        ];

        if ($paid > 0) {
            $mode = strtolower(trim((string) $sale->payment_mode));

            $codes = [
                'cash' => '1100',
                'upi' => '1110',
                'card' => '1120',
            ];

            if (! isset($codes[$mode])) {
                throw new RuntimeException('Unsupported Pharmacy payment mode.');
            }

            $expected[$codes[$mode]] = [$paid, 0];
        }

        if (
            $expected['4306'][1]
            + $expected['2311'][1]
            + $expected['2312'][1]
            + $expected['2313'][1] !== $total
        ) {
            throw new RuntimeException('Pharmacy GST does not reconcile.');
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        $entries = [];
        $seen = [];
        $debits = 0;
        $credits = 0;
        $cogs = null;
        $inventory = null;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;
            $code = (string) ($gl?->account_code ?? '');

            if (! $gl || blank($gl->tally_ledger_name) || isset($seen[$code])) {
                throw new RuntimeException('Invalid or duplicate Pharmacy GL mapping.');
            }

            $seen[$code] = true;
            $dr = $paise($line->debit);
            $cr = $paise($line->credit);

            if ($dr + $cr <= 0 || ($dr > 0 && $cr > 0)) {
                throw new RuntimeException('Invalid Pharmacy GL line.');
            }

            if (isset($expected[$code])) {
                if ([$dr, $cr] !== $expected[$code]) {
                    throw new RuntimeException('Pharmacy Sales GL mismatch.');
                }
            } elseif ($code === '5101') {
                if ($dr <= 0 || $cr !== 0) {
                    throw new RuntimeException('Invalid Pharmacy COGS.');
                }
                $cogs = $dr;
            } elseif ($code === '1300') {
                if ($cr <= 0 || $dr !== 0) {
                    throw new RuntimeException('Invalid inventory credit.');
                }
                $inventory = $cr;
            } else {
                throw new RuntimeException('Unsupported Pharmacy GL account: '.$code);
            }

            $debits += $dr;
            $credits += $cr;

            $entries[] = [
                'ledger' => trim($gl->tally_ledger_name),
                'debit' => $dr,
                'credit' => $cr,
                'party' => $code === '1200',
            ];
        }

        foreach ($expected as $code => $amounts) {
            if (($amounts[0] + $amounts[1]) > 0 && ! isset($seen[$code])) {
                throw new RuntimeException('Missing Pharmacy GL account: '.$code);
            }
        }

        if (
            $cogs === null
            || $inventory === null
            || $cogs !== $inventory
            || $debits !== $credits
            || $debits !== $total + $cogs
        ) {
            throw new RuntimeException('Pharmacy Sales/COGS journal does not reconcile.');
        }

        $company = trim((string) config('tally.company'));

        if ($company === '') {
            throw new RuntimeException('Tally company is not configured.');
        }

        if (
            $remoteId !== null
            && ! preg_match('/^[A-Za-z0-9-]{1,128}$/', $remoteId)
        ) {
            throw new RuntimeException('Invalid Tally remote ID.');
        }

        $escape = static fn (string $value): string =>
            htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        $money = static fn (int $value): string =>
            number_format($value / 100, 2, '.', '');

        $date = $journal->journal_date->format('Ymd');

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><ENVELOPE/>'
        );

        $header = $xml->addChild('HEADER');
        $header->addChild('TALLYREQUEST', 'Import Data');

        $body = $xml->addChild('BODY');
        $import = $body->addChild('IMPORTDATA');

        $desc = $import->addChild('REQUESTDESC');
        $desc->addChild('REPORTNAME', 'Vouchers');

        $static = $desc->addChild('STATICVARIABLES');
        $static->addChild('SVCURRENTCOMPANY', $escape($company));

        $request = $import->addChild('REQUESTDATA');
        $message = $request->addChild('TALLYMESSAGE');
        $voucher = $message->addChild('VOUCHER');

        if ($remoteId !== null) {
            $voucher->addAttribute('REMOTEID', $remoteId);
        }

        $voucher->addAttribute('VCHTYPE', 'Sales');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute('OBJVIEW', 'Accounting Voucher View');

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Sales');
        $voucher->addChild('REFERENCE', $escape($sale->sale_no));
        $voucher->addChild(
            'NARRATION',
            $escape('TCH ERP Pharmacy Sale '.$sale->sale_no)
        );
        $voucher->addChild('PERSISTEDVIEW', 'Accounting Voucher View');
        $voucher->addChild('EFFECTIVEDATE', $date);
        $voucher->addChild('ISINVOICE', 'No');
        $voucher->addChild('ISOPTIONAL', 'No');
        $voucher->addChild('ISCANCELLED', 'No');

        foreach ($entries as $entry) {
            $node = $voucher->addChild('ALLLEDGERENTRIES.LIST');
            $node->addChild('LEDGERNAME', $escape($entry['ledger']));
            $node->addChild(
                'ISDEEMEDPOSITIVE',
                $entry['debit'] > 0 ? 'Yes' : 'No'
            );
            $node->addChild(
                'ISPARTYLEDGER',
                $entry['party'] ? 'Yes' : 'No'
            );
            $node->addChild(
                'AMOUNT',
                $entry['debit'] > 0
                    ? '-'.$money($entry['debit'])
                    : $money($entry['credit'])
            );

            if ($entry['party'] && $entry['debit'] > 0) {
                $allocation = $node->addChild('BILLALLOCATIONS.LIST');
                $allocation->addChild('NAME', $escape($sale->sale_no));
                $allocation->addChild('BILLTYPE', 'New Ref');
                $allocation->addChild(
                    'AMOUNT',
                    '-'.$money($entry['debit'])
                );
            }
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException('Pharmacy Sales XML generation failed.');
        }

        return $result;
    }
}
