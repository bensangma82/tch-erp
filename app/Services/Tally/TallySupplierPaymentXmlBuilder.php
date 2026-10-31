<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\PharmacySupplierPayment;
use App\Models\TallyExport;
use RuntimeException;
use SimpleXMLElement;

class TallySupplierPaymentXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'pharmacy_supplier_payment'
            || $journal->source_event !== 'paid'
        ) {
            throw new RuntimeException(
                'Only posted pharmacy supplier payments are supported.'
            );
        }

        if (
            TallyExport::where('journal_entry_id', $journal->id)
                ->exists()
            || (
                $journal->finance_voucher_id !== null
                && TallyExport::where(
                    'finance_voucher_id',
                    $journal->finance_voucher_id
                )->exists()
            )
        ) {
            throw new RuntimeException(
                'Transaction already has a Tally export record.'
            );
        }

        $payment = PharmacySupplierPayment::with([
            'payable.grn',
            'supplier.supplier',
            'financeAccount',
        ])->findOrFail($journal->source_id);

        $payable = $payment->payable;

        if (
            ! $payable
            || ! $payment->supplier?->supplier
            || (string) $payment->pharmacy_supplier_id
                !== (string) $payable->pharmacy_supplier_id
            || blank($payable->payable_no)
            || (string) $journal->reference_no
                !== (string) $payment->payment_no
        ) {
            throw new RuntimeException(
                'Supplier payment references do not match.'
            );
        }

        if (! $payable->grn) {
            throw new RuntimeException(
                'Original pharmacy GRN is missing.'
            );
        }

        $details = app(TallyPharmacyPayableResolver::class)
            ->resolve($payable->grn);

        if (
            (int) $details['payable_id'] !== (int) $payable->id
            || (int) $details['supplier_id']
                !== (int) $payment->supplier->supplier_id
        ) {
            throw new RuntimeException(
                'Supplier payable identity mismatch.'
            );
        }

        $toPaise = static function ($value): int {
            $value = (string) $value;

            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                throw new RuntimeException(
                    'Invalid monetary amount.'
                );
            }

            [$whole, $fraction] = array_pad(
                explode('.', $value, 2), 2, '0'
            );

            return ((int) $whole * 100)
                + (int) str_pad($fraction, 2, '0');
        };

        $amount = $toPaise($payment->amount);

        if ($amount <= 0) {
            throw new RuntimeException(
                'Payment amount must be positive.'
            );
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() !== 2) {
            throw new RuntimeException(
                'Supplier payment requires exactly two GL lines.'
            );
        }

        $cashLedger = null;
        $payableLineFound = false;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl) {
                throw new RuntimeException(
                    'Missing payment GL account.'
                );
            }

            $debit = $toPaise($line->debit);
            $credit = $toPaise($line->credit);

            if (
                (string) $gl->account_code === '2200'
                && $debit === $amount
                && $credit === 0
                && ! $payableLineFound
            ) {
                $payableLineFound = true;
            } elseif (
                (string) $gl->id
                    === (string) $payment->financeAccount?->general_ledger_account_id
                && $credit === $amount
                && $debit === 0
                && $cashLedger === null
                && ! blank($gl->tally_ledger_name)
            ) {
                $cashLedger = trim($gl->tally_ledger_name);
            } else {
                throw new RuntimeException(
                    'Supplier payment GL entries do not reconcile.'
                );
            }
        }

        if (! $payableLineFound || ! $cashLedger) {
            throw new RuntimeException(
                'Payment ledgers are incomplete.'
            );
        }

        $company = trim((string) config('tally.company'));

        if ($company === '') {
            throw new RuntimeException(
                'Tally company is not configured.'
            );
        }

        if (
            $remoteId !== null
            && ! preg_match('/^[A-Za-z0-9-]{1,128}$/', $remoteId)
        ) {
            throw new RuntimeException('Invalid remote ID.');
        }

        $escape = static fn (string $value): string =>
            htmlspecialchars(
                $value,
                ENT_XML1 | ENT_COMPAT,
                'UTF-8'
            );

        $money = number_format($amount / 100, 2, '.', '');
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
            $voucher->addAttribute('REMOTEID', $remoteId);
        }

        $voucher->addAttribute('VCHTYPE', 'Payment');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute(
            'OBJVIEW',
            'Accounting Voucher View'
        );

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Payment');
        $voucher->addChild(
            'PARTYLEDGERNAME',
            $escape($details['supplier_ledger'])
        );
        $voucher->addChild(
            'REFERENCE',
            $escape($payment->payment_no)
        );
        $voucher->addChild(
            'NARRATION',
            $escape(
                'TCH ERP Supplier Payment '.$payment->payment_no
                .' against '.$details['bill_reference']
            )
        );
        $voucher->addChild(
            'PERSISTEDVIEW',
            'Accounting Voucher View'
        );
        $voucher->addChild('EFFECTIVEDATE', $date);
        $voucher->addChild('ISOPTIONAL', 'No');
        $voucher->addChild('ISCANCELLED', 'No');

        // Debit supplier creditor ledger.
        $party = $voucher->addChild('ALLLEDGERENTRIES.LIST');
        $party->addChild(
            'LEDGERNAME',
            $escape($details['supplier_ledger'])
        );
        $party->addChild('ISDEEMEDPOSITIVE', 'Yes');
        $party->addChild('ISPARTYLEDGER', 'Yes');
        $party->addChild('AMOUNT', '-'.$money);

        $allocation = $party->addChild('BILLALLOCATIONS.LIST');
        $allocation->addChild(
            'NAME',
            $escape($details['bill_reference'])
        );
        $allocation->addChild('BILLTYPE', 'Agst Ref');
        $allocation->addChild('AMOUNT', '-'.$money);

        // Credit the actual cash/bank ledger.
        $cash = $voucher->addChild('ALLLEDGERENTRIES.LIST');
        $cash->addChild('LEDGERNAME', $escape($cashLedger));
        $cash->addChild('ISDEEMEDPOSITIVE', 'No');
        $cash->addChild('ISPARTYLEDGER', 'No');
        $cash->addChild('AMOUNT', $money);

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Payment XML generation failed.'
            );
        }

        return $result;
    }
}
