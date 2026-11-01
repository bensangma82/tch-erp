<?php

namespace App\Services\Tally;

use App\Models\Invoice;
use App\Models\JournalEntry;
use RuntimeException;
use SimpleXMLElement;

class TallyOpdSalesXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string
    {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'invoice'
            || $journal->source_event !== 'issued'
        ) {
            throw new RuntimeException(
                'Only posted OPD invoices are supported.'
            );
        }

        $account = Invoice::with('patient')
            ->findOrFail($journal->source_id);

        if (
            ! in_array($account->status, ['issued', 'paid', 'partial', 'partially_paid'], true)
            || blank($account->invoice_no)
            || ! $account->patient
            || (string) $journal->reference_no
                !== (string) $account->invoice_no
        ) {
            throw new RuntimeException(
                'Final bill or patient reference is invalid.'
            );
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() < 2) {
            throw new RuntimeException('Missing journal lines.');
        }

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

        $entries = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $partyDebit = 0;
        $partyLedger = null;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl || blank($gl->tally_ledger_name)) {
                throw new RuntimeException(
                    'Missing Tally GL mapping.'
                );
            }

            $dr = $toPaise($line->debit);
            $cr = $toPaise($line->credit);

            if (($dr > 0) === ($cr > 0)) {
                throw new RuntimeException(
                    'Invalid debit/credit line.'
                );
            }

            $totalDebit += $dr;
            $totalCredit += $cr;

            if ($gl->account_code === '1200') {
                if ($cr !== 0 || $partyLedger !== null) {
                    throw new RuntimeException(
                        'Invalid Patient Receivables line.'
                    );
                }

                $partyDebit = $dr;
                $partyLedger = trim($gl->tally_ledger_name);
            } elseif (
                $dr > 0
                || ! in_array(
                    $gl->account_type,
                    ['income', 'liability'],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Unsupported OPD Sales GL structure.'
                );
            }

            $entries[] = [
                'ledger' => trim($gl->tally_ledger_name),
                'debit' => $dr > 0,
                'paise' => $dr > 0 ? $dr : $cr,
                'party' => $gl->account_code === '1200',
            ];
        }

        if (
            $totalDebit <= 0
            || $totalDebit !== $totalCredit
            || $partyDebit !== $totalDebit
            || ! $partyLedger
            || $partyDebit !== $toPaise($account->total_amount)
        ) {
            throw new RuntimeException(
                'OPD invoice accounting does not reconcile.'
            );
        }

        $money = static fn (int $paise): string =>
            number_format($paise / 100, 2, '.', '');

        $escape = static fn (string $value): string =>
            htmlspecialchars(
                $value,
                ENT_XML1 | ENT_COMPAT,
                'UTF-8'
            );

        $company = trim((string) config('tally.company'));

        if ($company === '') {
            throw new RuntimeException(
                'Tally company is not configured.'
            );
        }

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
        $static->addChild(
            'SVCURRENTCOMPANY',
            $escape($company)
        );

        $request = $import->addChild('REQUESTDATA');
        $message = $request->addChild('TALLYMESSAGE');
        $voucher = $message->addChild('VOUCHER');

        if ($remoteId !== null) {
            if (! preg_match(
                '/^[A-Za-z0-9-]{1,128}$/',
                $remoteId
            )) {
                throw new RuntimeException(
                    'Invalid Tally remote ID.'
                );
            }

            $voucher->addAttribute('REMOTEID', $remoteId);
        }

        $voucher->addAttribute('VCHTYPE', 'Sales');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute(
            'OBJVIEW',
            'Accounting Voucher View'
        );

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Sales');
        $voucher->addChild(
            'PARTYLEDGERNAME',
            $escape($partyLedger)
        );

        $voucher->addChild(
            'REFERENCE',
            $escape($account->invoice_no)
        );

        $voucher->addChild(
            'NARRATION',
            $escape(
                'TCH ERP OPD Invoice '
                .$account->invoice_no
                .' | Patient ID '.$account->patient_id
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
            $node = $voucher->addChild(
                'ALLLEDGERENTRIES.LIST'
            );

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

            $amount = $money($entry['paise']);

            $node->addChild(
                'AMOUNT',
                $entry['debit'] ? '-'.$amount : $amount
            );

            if ($entry['party']) {
                $allocation = $node->addChild(
                    'BILLALLOCATIONS.LIST'
                );

                $allocation->addChild(
                    'NAME',
                    $escape($account->invoice_no)
                );
                $allocation->addChild('BILLTYPE', 'New Ref');
                $allocation->addChild(
                    'AMOUNT',
                    '-'.$money($entry['paise'])
                );
            }
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Sales XML generation failed.'
            );
        }

        return $result;
    }
}
