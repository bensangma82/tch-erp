<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\Payment;
use RuntimeException;
use SimpleXMLElement;

class TallyStaffMedicalBenefitXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'payment'
            || $journal->source_event !== 'received'
        ) {
            throw new RuntimeException(
                'Only posted staff medical benefit payments are supported.'
            );
        }

        $payment = Payment::with('invoice')
            ->findOrFail($journal->source_id);

        $mode = strtolower(trim((string) $payment->payment_mode));

        if ($mode !== 'staff_medical_benefit') {
            throw new RuntimeException(
                'Payment is not a staff medical benefit.'
            );
        }

        $bill = $payment->invoice;

        if (
            ! $bill
            || blank($bill->invoice_no)
            || blank($payment->receipt_no)
            || (string) $journal->reference_no !== (string) $payment->receipt_no
            || (string) $payment->invoice_id !== (string) $bill->id
            || (string) $payment->patient_id !== (string) $bill->patient_id
        ) {
            throw new RuntimeException(
                'Invalid staff medical benefit invoice or receipt reference.'
            );
        }

        $hasInvoiceJournal = JournalEntry::query()
            ->where('source_type', 'invoice')
            ->where('source_id', (string) $bill->id)
            ->where('source_event', 'issued')
            ->where('status', 'posted')
            ->exists();

        if (! $hasInvoiceJournal) {
            throw new RuntimeException(
                'Original posted OPD invoice journal is required.'
            );
        }

        $paise = static function ($value): int {
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

        $amount = $paise($payment->amount);

        if ($amount <= 0) {
            throw new RuntimeException(
                'Staff medical benefit amount must be positive.'
            );
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() !== 2) {
            throw new RuntimeException(
                'Staff benefit journal must have exactly two GL lines.'
            );
        }

        $receivableLedger = null;
        $expenseFound = false;
        $receivableFound = false;

        foreach ($journal->lines as $line) {
            $account = $line->generalLedgerAccount;

            if (! $account || blank($account->tally_ledger_name)) {
                throw new RuntimeException('Missing Tally GL mapping.');
            }

            $debit = $paise($line->debit);
            $credit = $paise($line->credit);

            if (
                (string) $account->account_code === '6113'
                && $debit === $amount
                && $credit === 0
                && ! $expenseFound
            ) {
                $expenseFound = true;
            } elseif (
                (string) $account->account_code === '1200'
                && $credit === $amount
                && $debit === 0
                && ! $receivableFound
            ) {
                $receivableFound = true;
                $receivableLedger = trim($account->tally_ledger_name);
            } else {
                throw new RuntimeException(
                    'Staff benefit journal GL entries do not reconcile.'
                );
            }
        }

        if (! $expenseFound || ! $receivableFound) {
            throw new RuntimeException(
                'Expected staff benefit expense and patient receivable.'
            );
        }

        // Reuse existing balanced Journal XML generation.
        $raw = app(TallyJournalXmlBuilder::class)
            ->build($journal, $remoteId);

        $xml = new SimpleXMLElement($raw);
        $voucher = $xml->BODY->IMPORTDATA
            ->REQUESTDATA->TALLYMESSAGE->VOUCHER;

        $voucher->NARRATION = sprintf(
            'ERP %s | Staff Medical Benefit %s against OPD Invoice %s',
            $journal->journal_no,
            $payment->receipt_no,
            $bill->invoice_no
        );

        // Keep ERP journal number as the stable voucher reference.
        $matched = 0;

        foreach ($voucher->{'ALLLEDGERENTRIES.LIST'} as $entry) {
            if ((string) $entry->LEDGERNAME !== $receivableLedger) {
                continue;
            }

            $matched++;

            if (
                (string) $entry->ISDEEMEDPOSITIVE !== 'No'
                || abs((float) $entry->AMOUNT - $amount / 100) > 0.001
            ) {
                throw new RuntimeException(
                    'Receivable XML amount or direction mismatch.'
                );
            }

            $entry->ISPARTYLEDGER = 'Yes';

            $allocation = $entry->addChild('BILLALLOCATIONS.LIST');
            $allocation->addChild(
                'NAME',
                htmlspecialchars(
                    (string) $bill->invoice_no,
                    ENT_XML1 | ENT_COMPAT,
                    'UTF-8'
                )
            );
            $allocation->addChild('BILLTYPE', 'Agst Ref');
            $allocation->addChild(
                'AMOUNT',
                number_format($amount / 100, 2, '.', '')
            );
        }

        if ($matched !== 1) {
            throw new RuntimeException(
                'Expected exactly one patient receivable XML entry.'
            );
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Staff medical benefit Journal XML generation failed.'
            );
        }

        return $result;
    }
}
