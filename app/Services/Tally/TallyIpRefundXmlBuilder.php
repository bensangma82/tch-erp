<?php

namespace App\Services\Tally;

use App\Models\IpBillingRefund;
use App\Models\JournalEntry;
use RuntimeException;
use SimpleXMLElement;

class TallyIpRefundXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'ip_billing_refund'
            || $journal->source_event !== 'paid'
        ) {
            throw new RuntimeException('Invalid IPD refund journal.');
        }

        $refund = IpBillingRefund::with('billingAccount')
            ->findOrFail($journal->source_id);

        if (
            $refund->status !== 'active'
            || ! $refund->billingAccount
            || (int) $refund->patient_id
                !== (int) $refund->billingAccount->patient_id
            || blank($refund->refund_no)
            || (string) $journal->reference_no
                !== (string) $refund->refund_no
        ) {
            throw new RuntimeException('Invalid IPD refund reference.');
        }

        $modeCodes = [
            'cash' => '1100',
            'upi' => '1110',
            'card' => '1120',
        ];

        $mode = strtolower(trim((string) $refund->payment_mode));

        if (! isset($modeCodes[$mode])) {
            throw new RuntimeException('Unsupported refund payment mode.');
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

        $amount = $paise($refund->amount);

        if ($amount <= 0) {
            throw new RuntimeException('Refund amount must be positive.');
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() !== 2) {
            throw new RuntimeException('Refund must have two GL lines.');
        }

        $expected = [
            '2100' => [$amount, 0],
            $modeCodes[$mode] => [0, $amount],
        ];

        $entries = [];

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;
            $code = (string) ($gl?->account_code ?? '');

            if (
                ! isset($expected[$code])
                || blank($gl->tally_ledger_name)
                || isset($entries[$code])
            ) {
                throw new RuntimeException('Invalid refund GL mapping.');
            }

            $debit = $paise($line->debit);
            $credit = $paise($line->credit);

            if ([$debit, $credit] !== $expected[$code]) {
                throw new RuntimeException('Refund GL amounts do not reconcile.');
            }

            $entries[$code] = [
                'ledger' => trim($gl->tally_ledger_name),
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        if (count($entries) !== 2) {
            throw new RuntimeException('Missing refund GL entries.');
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

        $voucher->addAttribute('VCHTYPE', 'Payment');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute('OBJVIEW', 'Accounting Voucher View');

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Payment');
        $voucher->addChild('REFERENCE', $escape($refund->refund_no));
        $voucher->addChild(
            'NARRATION',
            $escape('TCH ERP IPD Advance Refund '.$refund->refund_no)
        );
        $voucher->addChild('PERSISTEDVIEW', 'Accounting Voucher View');
        $voucher->addChild('EFFECTIVEDATE', $date);
        $voucher->addChild('ISOPTIONAL', 'No');
        $voucher->addChild('ISCANCELLED', 'No');

        foreach ($entries as $entry) {
            $node = $voucher->addChild('ALLLEDGERENTRIES.LIST');
            $node->addChild('LEDGERNAME', $escape($entry['ledger']));
            $node->addChild(
                'ISDEEMEDPOSITIVE',
                $entry['debit'] > 0 ? 'Yes' : 'No'
            );
            $node->addChild('ISPARTYLEDGER', 'No');
            $node->addChild(
                'AMOUNT',
                $entry['debit'] > 0
                    ? '-'.$money($entry['debit'])
                    : $money($entry['credit'])
            );
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException('Refund XML generation failed.');
        }

        return $result;
    }
}
