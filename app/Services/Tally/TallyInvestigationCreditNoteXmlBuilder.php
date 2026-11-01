<?php

namespace App\Services\Tally;

use App\Models\InvestigationCancellationRequest;
use App\Models\JournalEntry;
use RuntimeException;
use SimpleXMLElement;

class TallyInvestigationCreditNoteXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'investigation_cancellation_request'
            || $journal->source_event !== 'revenue_reversed'
        ) {
            throw new RuntimeException('Invalid posted cancellation journal.');
        }

        $cancellation = InvestigationCancellationRequest::with('invoice')
            ->findOrFail($journal->source_id);

        $invoice = $cancellation->invoice;

        if (
            $cancellation->status !== 'executed'
            || $cancellation->executed_at === null
            || ! $invoice
            || blank($invoice->invoice_no)
            || (string) $journal->reference_no !== (string) $invoice->invoice_no
        ) {
            throw new RuntimeException('Invalid cancellation or invoice.');
        }

        $toPaise = static function ($value): int {
            $value = (string) $value;

            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                throw new RuntimeException('Invalid monetary amount.');
            }

            [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

            return ((int) $whole * 100)
                + (int) str_pad($fraction, 2, '0');
        };

        $approved = $toPaise($cancellation->approved_amount);

        if ($approved <= 0) {
            throw new RuntimeException('Invalid approved cancellation amount.');
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        $entries = [];
        $totalDebit = 0;
        $totalCredit = 0;
        $receivableCredit = 0;
        $partyLedger = null;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl || blank($gl->tally_ledger_name)) {
                throw new RuntimeException('Missing Tally GL mapping.');
            }

            $dr = $toPaise($line->debit);
            $cr = $toPaise($line->credit);

            if (($dr > 0) === ($cr > 0)) {
                throw new RuntimeException('Invalid debit/credit line.');
            }

            $code = (string) $gl->account_code;

            if ($code === '1200') {
                if ($dr !== 0 || $partyLedger !== null) {
                    throw new RuntimeException('Invalid receivables reversal.');
                }

                $receivableCredit = $cr;
                $partyLedger = trim($gl->tally_ledger_name);
            } elseif (
                ! in_array($code, ['4201', '4202', '4203', '4204', '4205', '4206'], true)
                || $dr <= 0
                || $cr !== 0
            ) {
                throw new RuntimeException('Unsupported cancellation GL structure.');
            }

            $totalDebit += $dr;
            $totalCredit += $cr;

            $entries[] = [
                'ledger' => trim($gl->tally_ledger_name),
                'debit' => $dr > 0,
                'amount' => $dr > 0 ? $dr : $cr,
                'party' => $code === '1200',
            ];
        }

        if (
            count($entries) < 2
            || $totalDebit !== $approved
            || $totalCredit !== $approved
            || $receivableCredit !== $approved
            || ! $partyLedger
        ) {
            throw new RuntimeException('Cancellation journal does not reconcile.');
        }

        $company = trim((string) config('tally.company'));

        if ($company === '') {
            throw new RuntimeException('Tally company not configured.');
        }

        if (
            $remoteId !== null
            && ! preg_match('/^[A-Za-z0-9-]{1,128}$/', $remoteId)
        ) {
            throw new RuntimeException('Invalid Tally remote ID.');
        }

        $money = static fn (int $value): string =>
            number_format($value / 100, 2, '.', '');

        $escape = static fn (string $value): string =>
            htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');

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

        $voucher->addAttribute('VCHTYPE', 'Credit Note');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute('OBJVIEW', 'Accounting Voucher View');

        $date = $journal->journal_date->format('Ymd');

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Credit Note');
        $voucher->addChild('PARTYLEDGERNAME', $escape($partyLedger));
        $voucher->addChild('REFERENCE', $escape($invoice->invoice_no));
        $voucher->addChild(
            'NARRATION',
            $escape(
                'TCH ERP Investigation Cancellation '
                . $cancellation->id
                . ' against invoice '
                . $invoice->invoice_no
            )
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
                $entry['debit'] ? 'Yes' : 'No'
            );
            $node->addChild(
                'ISPARTYLEDGER',
                $entry['party'] ? 'Yes' : 'No'
            );

            $amount = $money($entry['amount']);

            $node->addChild(
                'AMOUNT',
                $entry['debit'] ? '-' . $amount : $amount
            );

            if ($entry['party']) {
                $allocation = $node->addChild('BILLALLOCATIONS.LIST');
                $allocation->addChild('NAME', $escape($invoice->invoice_no));
                $allocation->addChild('BILLTYPE', 'Agst Ref');
                $allocation->addChild('AMOUNT', $amount);
            }
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException('Credit Note XML generation failed.');
        }

        return $result;
    }
}
