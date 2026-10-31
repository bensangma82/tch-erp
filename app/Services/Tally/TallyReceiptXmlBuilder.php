<?php

namespace App\Services\Tally;

use App\Models\IpBillingPayment;
use App\Models\JournalEntry;
use RuntimeException;
use SimpleXMLElement;

class TallyReceiptXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'ip_billing_payment'
            || $journal->source_event !== 'received'
        ) {
            throw new RuntimeException(
                'Only posted IPD patient receipts are supported.'
            );
        }

        $payment = IpBillingPayment::with('billingAccount')
            ->findOrFail($journal->source_id);

        $bill = $payment->billingAccount;

        if (
            $payment->status !== 'active'
            || ! $bill
            || $bill->status !== 'finalized'
            || blank($bill->final_bill_no)
            || (string) $journal->reference_no
                !== (string) $payment->receipt_no
            || (string) $payment->ip_billing_account_id
                !== (string) $bill->id
            || (string) $payment->patient_id
                !== (string) $bill->patient_id
        ) {
            throw new RuntimeException(
                'Payment or finalized bill reference is invalid.'
            );
        }

        $mode = strtolower(trim((string) $payment->payment_mode));

        $codes = [
            'cash' => '1100',
            'upi' => '1110',
            'card' => '1120',
        ];

        if (! isset($codes[$mode])) {
            throw new RuntimeException(
                'Unsupported IPD payment mode.'
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
                'Receipt amount must be positive.'
            );
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() !== 2) {
            throw new RuntimeException(
                'IPD Receipt must have exactly two GL lines.'
            );
        }

        $collection = null;
        $receivable = null;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl || blank($gl->tally_ledger_name)) {
                throw new RuntimeException(
                    'Missing Tally GL mapping.'
                );
            }

            $debit = $toPaise($line->debit);
            $credit = $toPaise($line->credit);

            if (
                (string) $gl->account_code === $codes[$mode]
                && $debit === $amount
                && $credit === 0
                && $collection === null
            ) {
                $collection = trim($gl->tally_ledger_name);
            } elseif (
                (string) $gl->account_code === '1200'
                && $credit === $amount
                && $debit === 0
                && $receivable === null
            ) {
                $receivable = trim($gl->tally_ledger_name);
            } else {
                throw new RuntimeException(
                    'Receipt GL entries do not reconcile.'
                );
            }
        }

        if (! $collection || ! $receivable) {
            throw new RuntimeException(
                'Receipt collection or receivable ledger missing.'
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
            throw new RuntimeException(
                'Invalid Tally remote ID.'
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

        $voucher->addAttribute('VCHTYPE', 'Receipt');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute(
            'OBJVIEW',
            'Accounting Voucher View'
        );

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Receipt');
        $voucher->addChild(
            'PARTYLEDGERNAME',
            $escape($receivable)
        );
        $voucher->addChild(
            'REFERENCE',
            $escape($payment->receipt_no)
        );
        $voucher->addChild(
            'NARRATION',
            $escape(
                'TCH ERP Receipt '.$payment->receipt_no
                .' against IPD Bill '.$bill->final_bill_no
            )
        );
        $voucher->addChild(
            'PERSISTEDVIEW',
            'Accounting Voucher View'
        );
        $voucher->addChild('EFFECTIVEDATE', $date);
        $voucher->addChild('ISOPTIONAL', 'No');
        $voucher->addChild('ISCANCELLED', 'No');

        // Debit actual collection ledger.
        $cash = $voucher->addChild('ALLLEDGERENTRIES.LIST');
        $cash->addChild('LEDGERNAME', $escape($collection));
        $cash->addChild('ISDEEMEDPOSITIVE', 'Yes');
        $cash->addChild('ISPARTYLEDGER', 'No');
        $cash->addChild('AMOUNT', '-'.$money($amount));

        // Credit receivable and settle the original Sales reference.
        $party = $voucher->addChild('ALLLEDGERENTRIES.LIST');
        $party->addChild('LEDGERNAME', $escape($receivable));
        $party->addChild('ISDEEMEDPOSITIVE', 'No');
        $party->addChild('ISPARTYLEDGER', 'Yes');
        $party->addChild('AMOUNT', $money($amount));

        $allocation = $party->addChild('BILLALLOCATIONS.LIST');
        $allocation->addChild(
            'NAME',
            $escape($bill->final_bill_no)
        );
        $allocation->addChild('BILLTYPE', 'Agst Ref');
        $allocation->addChild('AMOUNT', $money($amount));

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Receipt XML generation failed.'
            );
        }

        return $result;
    }
}
