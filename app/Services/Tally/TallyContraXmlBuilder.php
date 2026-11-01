<?php

namespace App\Services\Tally;

use App\Models\FinanceVoucher;
use App\Models\JournalEntry;
use RuntimeException;
use SimpleXMLElement;

class TallyContraXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'finance_voucher'
            || $journal->source_event !== 'transfer_posted'
        ) {
            throw new RuntimeException('Invalid posted Contra journal.');
        }

        $voucher = FinanceVoucher::with([
            'financeAccount.generalLedgerAccount',
            'destinationAccount.generalLedgerAccount',
        ])->findOrFail($journal->source_id);

        $source = $voucher->financeAccount;
        $destination = $voucher->destinationAccount;

        if (
            $voucher->voucher_type !== 'transfer'
            || $voucher->status !== 'posted'
            || (int) $journal->finance_voucher_id !== (int) $voucher->id
            || ! $source
            || ! $destination
            || $source->id === $destination->id
            || ! $source->is_active
            || ! $destination->is_active
            || ! in_array($source->account_type, ['cash', 'bank'], true)
            || ! in_array($destination->account_type, ['cash', 'bank'], true)
            || ! $source->generalLedgerAccount
            || ! $destination->generalLedgerAccount
            || ! $source->generalLedgerAccount->is_active
            || ! $destination->generalLedgerAccount->is_active
            || ! $source->generalLedgerAccount->allow_posting
            || ! $destination->generalLedgerAccount->allow_posting
            || $source->general_ledger_account_id
                === $destination->general_ledger_account_id
            || (string) $journal->reference_no !== (string) $voucher->voucher_no
            || $journal->journal_date->format('Y-m-d')
                !== $voucher->voucher_date->format('Y-m-d')
        ) {
            throw new RuntimeException('Contra transfer or account mapping is invalid.');
        }

        $paise = static function ($value): int {
            $value = (string) $value;

            if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                throw new RuntimeException('Invalid monetary amount.');
            }

            [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

            return (int) $whole * 100
                + (int) str_pad($fraction, 2, '0');
        };

        $amount = $paise($voucher->amount);

        if ($amount <= 0) {
            throw new RuntimeException('Contra amount must be positive.');
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() !== 2) {
            throw new RuntimeException('Contra journal must have exactly two lines.');
        }

        $expected = [
            $destination->general_ledger_account_id => [$amount, 0],
            $source->general_ledger_account_id => [0, $amount],
        ];

        $entries = [];

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (
                ! $gl
                || ! isset($expected[$gl->id])
                || isset($entries[$gl->id])
                || blank($gl->tally_ledger_name)
            ) {
                throw new RuntimeException('Invalid Contra GL mapping.');
            }

            $debit = $paise($line->debit);
            $credit = $paise($line->credit);

            if ([$debit, $credit] !== $expected[$gl->id]) {
                throw new RuntimeException('Contra GL amounts do not reconcile.');
            }

            $entries[$gl->id] = [
                'ledger' => trim($gl->tally_ledger_name),
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        if (count($entries) !== 2) {
            throw new RuntimeException('Missing Contra GL entries.');
        }

        if (
            $entries[$source->general_ledger_account_id]['ledger']
            === $entries[$destination->general_ledger_account_id]['ledger']
        ) {
            throw new RuntimeException('Contra Tally ledgers must be distinct.');
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

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><ENVELOPE/>'
        );

        $xml->addChild('HEADER')->addChild('TALLYREQUEST', 'Import Data');

        $import = $xml->addChild('BODY')->addChild('IMPORTDATA');
        $desc = $import->addChild('REQUESTDESC');
        $desc->addChild('REPORTNAME', 'Vouchers');
        $desc->addChild('STATICVARIABLES')
            ->addChild('SVCURRENTCOMPANY', $escape($company));

        $voucherXml = $import->addChild('REQUESTDATA')
            ->addChild('TALLYMESSAGE')
            ->addChild('VOUCHER');

        if ($remoteId !== null) {
            $voucherXml->addAttribute('REMOTEID', $remoteId);
        }

        $voucherXml->addAttribute('VCHTYPE', 'Contra');
        $voucherXml->addAttribute('ACTION', 'Create');
        $voucherXml->addAttribute('OBJVIEW', 'Accounting Voucher View');

        $date = $journal->journal_date->format('Ymd');

        $voucherXml->addChild('DATE', $date);
        $voucherXml->addChild('VOUCHERTYPENAME', 'Contra');
        $voucherXml->addChild('REFERENCE', $escape($voucher->voucher_no));
        $voucherXml->addChild(
            'NARRATION',
            $escape(
                'TCH ERP Internal Transfer '.$voucher->voucher_no
                .' | '.$source->name.' to '.$destination->name
            )
        );
        $voucherXml->addChild('PERSISTEDVIEW', 'Accounting Voucher View');
        $voucherXml->addChild('EFFECTIVEDATE', $date);
        $voucherXml->addChild('ISOPTIONAL', 'No');
        $voucherXml->addChild('ISCANCELLED', 'No');

        foreach ($entries as $entry) {
            $node = $voucherXml->addChild('ALLLEDGERENTRIES.LIST');

            $node->addChild('LEDGERNAME', $escape($entry['ledger']));
            $node->addChild(
                'ISDEEMEDPOSITIVE',
                $entry['debit'] > 0 ? 'Yes' : 'No'
            );

            $node->addChild(
                'AMOUNT',
                $entry['debit'] > 0
                    ? '-'.$money($entry['debit'])
                    : $money($entry['credit'])
            );
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException('Contra XML generation failed.');
        }

        return $result;
    }
}
