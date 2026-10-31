<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\PharmacyGrn;
use RuntimeException;
use SimpleXMLElement;

class TallyPurchaseXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'pharmacy_grn'
            || $journal->source_event !== 'received'
        ) {
            throw new RuntimeException(
                'Only posted pharmacy GRN journals are supported.'
            );
        }

        $grn = PharmacyGrn::findOrFail($journal->source_id);

        $details = app(TallyPharmacyPayableResolver::class)
            ->resolve($grn);

        $journal->loadMissing('lines.generalLedgerAccount');

        $toPaise = static function ($value): int {
            $value = (string) $value;

            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                throw new RuntimeException('Invalid monetary amount.');
            }

            [$whole, $fraction] = array_pad(
                explode('.', $value, 2), 2, '0'
            );

            return ((int) $whole * 100)
                + (int) str_pad($fraction, 2, '0');
        };

        $money = static fn (int $paise): string =>
            number_format($paise / 100, 2, '.', '');

        $escape = static fn (string $value): string =>
            htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        $entries = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $payableCredit = 0;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl) {
                throw new RuntimeException('Missing GL account.');
            }

            $debit = $toPaise($line->debit);
            $credit = $toPaise($line->credit);

            if (($debit > 0) === ($credit > 0)) {
                throw new RuntimeException('Invalid debit/credit line.');
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            if ($gl->account_code === '2200') {
                if ($debit !== 0 || $payableCredit !== 0) {
                    throw new RuntimeException(
                        'Invalid Accounts Payable entry.'
                    );
                }

                $payableCredit = $credit;
                $ledgerName = $details['supplier_ledger'];
                $isParty = true;
            } else {
                if (
                    $credit !== 0
                    || ! in_array(
                        $gl->account_code,
                        ['1300', '2301', '2302', '2303'],
                        true
                    )
                    || blank($gl->tally_ledger_name)
                ) {
                    throw new RuntimeException(
                        'Unsupported Purchase GL structure or mapping.'
                    );
                }

                $ledgerName = trim($gl->tally_ledger_name);
                $isParty = false;
            }

            $entries[] = [
                'ledger' => $ledgerName,
                'debit' => $debit > 0,
                'amount' => $debit > 0 ? $debit : $credit,
                'party' => $isParty,
            ];
        }

        if (
            count($entries) < 2
            || $totalDebit <= 0
            || $totalDebit !== $totalCredit
            || $payableCredit !== $totalCredit
            || $payableCredit !== $toPaise($grn->total_amount)
        ) {
            throw new RuntimeException(
                'Purchase journal and GRN amounts do not reconcile.'
            );
        }

        $company = trim((string) config('tally.company'));

        if ($company === '') {
            throw new RuntimeException('Tally company is missing.');
        }

        if (
            $remoteId !== null
            && ! preg_match('/^[A-Za-z0-9-]{1,128}$/', $remoteId)
        ) {
            throw new RuntimeException('Invalid remote ID.');
        }

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
        $static->addChild(
            'SVCURRENTCOMPANY',
            $escape($company)
        );

        $request = $import->addChild('REQUESTDATA');
        $message = $request->addChild('TALLYMESSAGE');
        $voucher = $message->addChild('VOUCHER');

        if ($remoteId !== null) {
            $voucher->addAttribute('REMOTEID', $remoteId);
        }

        $voucher->addAttribute('VCHTYPE', 'Purchase');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute(
            'OBJVIEW',
            'Accounting Voucher View'
        );

        $date = $journal->journal_date->format('Ymd');

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Purchase');
        $voucher->addChild(
            'PARTYLEDGERNAME',
            $escape($details['supplier_ledger'])
        );
        $voucher->addChild(
            'REFERENCE',
            $escape($details['supplier_invoice'] ?: $grn->grn_no)
        );
        $voucher->addChild(
            'NARRATION',
            $escape(
                'TCH ERP Pharmacy GRN '.$grn->grn_no
                .' | Payable '.$details['bill_reference']
            )
        );
        $voucher->addChild(
            'PERSISTEDVIEW',
            'Accounting Voucher View'
        );
        $voucher->addChild('EFFECTIVEDATE', $date);
        $voucher->addChild('ISINVOICE', 'No');
        $voucher->addChild('ISOPTIONAL', 'No');
        $voucher->addChild('ISCANCELLED', 'No');

        foreach ($entries as $entry) {
            $node = $voucher->addChild('ALLLEDGERENTRIES.LIST');

            $node->addChild(
                'LEDGERNAME',
                $escape($entry['ledger'])
            );
            $node->addChild(
                'ISDEEMEDPOSITIVE',
                $entry['debit'] ? 'Yes' : 'No'
            );
            $node->addChild(
                'ISPARTYLEDGER',
                $entry['party'] ? 'Yes' : 'No'
            );
            $node->addChild(
                'AMOUNT',
                ($entry['debit'] ? '-' : '')
                .$money($entry['amount'])
            );

            if ($entry['party']) {
                $allocation = $node->addChild(
                    'BILLALLOCATIONS.LIST'
                );

                $allocation->addChild(
                    'NAME',
                    $escape($details['bill_reference'])
                );
                $allocation->addChild('BILLTYPE', 'New Ref');
                $allocation->addChild(
                    'AMOUNT',
                    $money($entry['amount'])
                );
            }
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Purchase XML generation failed.'
            );
        }

        return $result;
    }
}
