<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use RuntimeException;
use SimpleXMLElement;

class TallyJournalXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null,
        ?string $testDate = null
    ): string {
        if ($journal->status !== 'posted') {
            throw new RuntimeException(
                'Only posted journals may be exported to Tally.'
            );
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() < 2) {
            throw new RuntimeException(
                'Journal must contain at least two lines.'
            );
        }

        $totalDebit = 0;
        $totalCredit = 0;
        $entries = [];

        foreach ($journal->lines as $line) {
            $account = $line->generalLedgerAccount;

            if (! $account || blank($account->tally_ledger_name)) {
                throw new RuntimeException(
                    'Missing Tally ledger mapping for journal line '
                    .$line->line_no
                );
            }

            $debit = (int) round((float) $line->debit * 100);
            $credit = (int) round((float) $line->credit * 100);

            if (
                $debit < 0 ||
                $credit < 0 ||
                ($debit > 0 && $credit > 0) ||
                ($debit === 0 && $credit === 0)
            ) {
                throw new RuntimeException(
                    'Invalid debit/credit amounts on journal line '
                    .$line->line_no
                );
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $entries[] = [
                'ledger' => $account->tally_ledger_name,
                'debit' => $debit > 0,
                'amount' => $debit > 0 ? $debit : $credit,
            ];
        }

        if ($totalDebit <= 0 || $totalDebit !== $totalCredit) {
            throw new RuntimeException(
                'Journal is not balanced.'
            );
        }

        $date = $journal->journal_date->format('Ymd');

        if ($testDate !== null) {
            if (! app()->environment('local', 'testing')) {
                throw new RuntimeException(
                    'Test date override is only permitted locally.'
                );
            }

            $parsed = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $testDate
            );

            if (
                ! $parsed ||
                $parsed->format('Y-m-d') !== $testDate
            ) {
                throw new RuntimeException(
                    'Invalid test date. Use YYYY-MM-DD.'
                );
            }

            $date = $parsed->format('Ymd');
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
        $voucher->addAttribute(
            'OBJVIEW',
            'Accounting Voucher View'
        );

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VCHSTATUSDATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Journal');

        $narration = trim(
            'ERP '.$journal->journal_no
            .' | Original date: '
            .$journal->journal_date->format('Y-m-d')
            .' | '.($journal->narration ?? '')
        );

        $voucher->addChild(
            'NARRATION',
            $this->escape($narration)
        );

        $voucher->addChild(
            'REFERENCE',
            $this->escape($journal->journal_no)
        );

        $voucher->addChild(
            'PERSISTEDVIEW',
            'Accounting Voucher View'
        );

        $voucher->addChild('EFFECTIVEDATE', $date);
        $voucher->addChild('ISOPTIONAL', 'No');
        $voucher->addChild('ISCANCELLED', 'No');
        $voucher->addChild('ISPOSTDATED', 'No');
        $voucher->addChild('ISINVOICE', 'No');

        foreach ($entries as $entry) {
            $node = $voucher->addChild(
                'ALLLEDGERENTRIES.LIST'
            );

            $node->addChild(
                'LEDGERNAME',
                $this->escape($entry['ledger'])
            );

            $node->addChild(
                'ISDEEMEDPOSITIVE',
                $entry['debit'] ? 'Yes' : 'No'
            );

            $node->addChild('ISPARTYLEDGER', 'No');

            $amount = number_format(
                $entry['amount'] / 100,
                2,
                '.',
                ''
            );

            $node->addChild(
                'AMOUNT',
                $entry['debit'] ? '-'.$amount : $amount
            );
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Unable to generate journal XML.'
            );
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