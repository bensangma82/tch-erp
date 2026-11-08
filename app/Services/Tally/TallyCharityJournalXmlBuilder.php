<?php

namespace App\Services\Tally;

use App\Models\CharityAdjustment;
use App\Models\JournalEntry;
use RuntimeException;
use SimpleXMLElement;

class TallyCharityJournalXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null,
        ?string $testDate = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'charity_adjustment'
            || $journal->source_event !== 'applied'
        ) {
            throw new RuntimeException('Invalid posted charity journal.');
        }

        $adjustment = CharityAdjustment::with('ipBillingAccount')
            ->findOrFail($journal->source_id);

        $account = $adjustment->ipBillingAccount;

        if (
            ! $account
            || $adjustment->status !== 'applied'
            || ! $account->final_bill_no
            || $journal->reference_no !== $account->final_bill_no
        ) {
            throw new RuntimeException('Invalid charity invoice reference.');
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() !== 2) {
            throw new RuntimeException('Charity journal requires two GL lines.');
        }

        $entries = [];
        $debits = 0;
        $credits = 0;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl || blank($gl->tally_ledger_name)) {
                throw new RuntimeException('Missing Tally GL mapping.');
            }

            $debit = (int) round((float) $line->debit * 100);
            $credit = (int) round((float) $line->credit * 100);

            if (
                ($debit > 0 && $credit > 0)
                || ($debit === 0 && $credit === 0)
                || $debit < 0 || $credit < 0
            ) {
                throw new RuntimeException('Invalid charity journal amounts.');
            }

            $code = (string) $gl->account_code;

            if (
                ($code === '6114' && ($debit <= 0 || $credit !== 0))
                || ($code === '1200' && ($credit <= 0 || $debit !== 0))
                || ! in_array($code, ['6114', '1200'], true)
            ) {
                throw new RuntimeException('Invalid charity GL structure.');
            }

            $debits += $debit;
            $credits += $credit;

            $entries[$code] = [
                'ledger' => $gl->tally_ledger_name,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        $expected = (int) round(
            (float) $adjustment->approved_amount * 100
        );

        if (
            count($entries) !== 2
            || $debits !== $credits
            || $debits !== $expected
            || $expected <= 0
        ) {
            throw new RuntimeException('Charity journal does not reconcile.');
        }

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><ENVELOPE/>'
        );

        $header = $xml->addChild('HEADER');
        $header->addChild('TALLYREQUEST', 'Import Data');

        $body = $xml->addChild('BODY');
        $import = $body->addChild('IMPORTDATA');
        $description = $import->addChild('REQUESTDESC');
        $description->addChild('REPORTNAME', 'Vouchers');
        $static = $description->addChild('STATICVARIABLES');
        $static->addChild(
            'SVCURRENTCOMPANY',
            $this->escape((string) config('tally.company'))
        );

        $request = $import->addChild('REQUESTDATA');
        $message = $request->addChild('TALLYMESSAGE');
        $voucher = $message->addChild('VOUCHER');

        if (filled($remoteId)) {
            $voucher->addAttribute('REMOTEID', $remoteId);
        }

        $voucher->addAttribute('VCHTYPE', 'Journal');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute('OBJVIEW', 'Accounting Voucher View');

        $date = $testDate
            ? \Carbon\Carbon::parse($testDate)->format('Ymd')
            : \Carbon\Carbon::parse($journal->journal_date)->format('Ymd');

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VCHSTATUSDATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Journal');
        $voucher->addChild(
            'REFERENCE',
            $this->escape($journal->journal_no)
        );
        $voucher->addChild(
            'NARRATION',
            $this->escape(
                'ERP '.$journal->journal_no
                .' | Charity adjustment against '
                .$account->final_bill_no
            )
        );
        $voucher->addChild('PERSISTEDVIEW', 'Accounting Voucher View');
        $voucher->addChild('EFFECTIVEDATE', $date);
        $voucher->addChild('ISOPTIONAL', 'No');
        $voucher->addChild('ISCANCELLED', 'No');
        $voucher->addChild('ISINVOICE', 'No');

        foreach (['6114', '1200'] as $code) {
            $entry = $entries[$code];
            $debit = $entry['debit'] > 0;

            $node = $voucher->addChild('ALLLEDGERENTRIES.LIST');
            $node->addChild(
                'LEDGERNAME',
                $this->escape($entry['ledger'])
            );
            $node->addChild('ISDEEMEDPOSITIVE', $debit ? 'Yes' : 'No');
            $node->addChild(
                'ISPARTYLEDGER',
                $code === '1200' ? 'Yes' : 'No'
            );

            $paise = $debit ? $entry['debit'] : $entry['credit'];
            $amount = number_format($paise / 100, 2, '.', '');

            $node->addChild(
                'AMOUNT',
                $debit ? '-'.$amount : $amount
            );

            if ($code === '1200') {
                $allocation = $node->addChild('BILLALLOCATIONS.LIST');
                $allocation->addChild(
                    'NAME',
                    $this->escape($account->final_bill_no)
                );
                $allocation->addChild('BILLTYPE', 'Agst Ref');
                $allocation->addChild('AMOUNT', $amount);
            }
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException('Charity XML generation failed.');
        }

        return $result;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_XML1 | ENT_COMPAT,
            'UTF-8'
        );
    }
}
