<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;
use RuntimeException;
use SimpleXMLElement;

class TallyIpdAdvanceApplicationXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null,
        ?string $testDate = null
    ): string {
        if (
            $journal->source_type !== 'ip_billing_account' ||
            $journal->source_event !== 'advance_applied' ||
            $journal->status !== 'posted'
        ) {
            throw new RuntimeException(
                'Expected a posted IPD advance application.'
            );
        }

        $invoiceNo = trim((string) $journal->reference_no);

        if ($invoiceNo === '') {
            throw new RuntimeException(
                'Advance application has no final invoice reference.'
            );
        }

        $sales = JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_event', 'finalized')
            ->where('reference_no', $invoiceNo)
            ->where('status', 'posted')
            ->first();

        if (! $sales) {
            throw new RuntimeException(
                'Referenced IPD final Sales journal was not found.'
            );
        }

        if (! TallyExport::query()
            ->where('journal_entry_id', $sales->id)
            ->where('status', 'confirmed')
            ->exists()) {
            throw new RuntimeException(
                'Export the referenced IPD Sales voucher first.'
            );
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        $receivableLines = $journal->lines->filter(
            fn ($line) =>
                $line->generalLedgerAccount?->account_code === '1200'
                && (float) $line->credit > 0
                && (float) $line->debit == 0
        );

        $advanceLines = $journal->lines->filter(
            fn ($line) =>
                $line->generalLedgerAccount?->account_code === '2100'
                && (float) $line->debit > 0
                && (float) $line->credit == 0
        );

        if (
            $journal->lines->count() !== 2 ||
            $receivableLines->count() !== 1 ||
            $advanceLines->count() !== 1
        ) {
            throw new RuntimeException(
                'Advance must debit GL 2100 and credit GL 1200.'
            );
        }

        $receivable = $receivableLines->first();
        $advance = $advanceLines->first();

        $amount = (int) round(
            (float) $receivable->credit * 100
        );

        if (
            $amount <= 0 ||
            $amount !== (int) round(
                (float) $advance->debit * 100
            )
        ) {
            throw new RuntimeException(
                'Advance application amounts are inconsistent.'
            );
        }

        // Reuse the existing balanced Journal XML.
        $xml = new SimpleXMLElement(
            app(TallyJournalXmlBuilder::class)->build(
                $journal,
                $remoteId,
                $testDate
            )
        );

        $voucher = $xml
            ->BODY
            ->IMPORTDATA
            ->REQUESTDATA
            ->TALLYMESSAGE
            ->VOUCHER;

        $ledgerName = (string)
            $receivable->generalLedgerAccount->tally_ledger_name;

        $matched = 0;

        foreach ($voucher->{'ALLLEDGERENTRIES.LIST'} as $entry) {
            if ((string) $entry->LEDGERNAME !== $ledgerName) {
                continue;
            }

            if (
                (string) $entry->AMOUNT !==
                number_format($amount / 100, 2, '.', '')
            ) {
                throw new RuntimeException(
                    'Unexpected receivable XML amount.'
                );
            }

            $entry->ISPARTYLEDGER = 'Yes';

            // Settle the original IPD Sales invoice.
            $bill = $entry->addChild('BILLALLOCATIONS.LIST');

            $bill->addChild(
                'NAME',
                htmlspecialchars(
                    $invoiceNo,
                    ENT_XML1 | ENT_COMPAT,
                    'UTF-8'
                )
            );

            $bill->addChild('BILLTYPE', 'Agst Ref');

            $bill->addChild(
                'AMOUNT',
                number_format($amount / 100, 2, '.', '')
            );

            $matched++;
        }

        if ($matched !== 1) {
            throw new RuntimeException(
                'Could not uniquely identify the receivable entry.'
            );
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Unable to generate advance application XML.'
            );
        }

        return $result;
    }
}
