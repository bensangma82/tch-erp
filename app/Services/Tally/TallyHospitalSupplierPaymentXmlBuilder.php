<?php

namespace App\Services\Tally;

use App\Models\FinanceVoucher;
use App\Models\HospitalExpensePayment;
use App\Models\JournalEntry;
use App\Models\TallyExport;
use RuntimeException;
use SimpleXMLElement;

class TallyHospitalSupplierPaymentXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'hospital_expense_payment'
            || $journal->source_event !== 'supplier_payment'
        ) {
            throw new RuntimeException(
                'Only posted hospital supplier payments are supported.'
            );
        }

        $payment = HospitalExpensePayment::with('expense')
            ->findOrFail($journal->source_id);

        $expense = $payment->expense;

        if (
            $payment->status !== 'posted'
            || ! $expense
            || ! $payment->finance_voucher_id
            || (int) $journal->finance_voucher_id
                !== (int) $payment->finance_voucher_id
        ) {
            throw new RuntimeException(
                'Payment or Finance Voucher linkage is invalid.'
            );
        }

        $voucher = FinanceVoucher::findOrFail(
            $payment->finance_voucher_id
        );

        if (
            $voucher->status !== 'posted'
            || $voucher->voucher_type !== 'payment'
            || $voucher->source_type !== 'hospital_expense_payment'
            || $voucher->source_event !== 'supplier_payment'
            || (string) $voucher->source_id
                !== (string) $payment->id
            || (string) $journal->reference_no
                !== (string) $voucher->voucher_no
        ) {
            throw new RuntimeException(
                'Finance Voucher and journal do not match.'
            );
        }

        // Never prepare a second export for an existing transaction.
        if (
            TallyExport::where('journal_entry_id', $journal->id)
                ->exists()
            || TallyExport::where(
                'finance_voucher_id',
                $voucher->id
            )->exists()
        ) {
            throw new RuntimeException(
                'Existing Tally export requires reconciliation.'
            );
        }

        $details = app(TallyHospitalExpenseResolver::class)
            ->resolve($expense);

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

        $amount = $toPaise($payment->amount);

        if (
            $amount <= 0
            || $amount !== $toPaise($voucher->amount)
        ) {
            throw new RuntimeException(
                'Payment and Finance Voucher amounts do not match.'
            );
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        if ($journal->lines->count() !== 2) {
            throw new RuntimeException(
                'Supplier payment requires exactly two GL lines.'
            );
        }

        $partyFound = false;
        $cashLedger = null;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl) {
                throw new RuntimeException('Missing GL account.');
            }

            $dr = $toPaise($line->debit);
            $cr = $toPaise($line->credit);

            if (
                (string) $gl->account_code === '2200'
                && $dr === $amount
                && $cr === 0
                && ! $partyFound
            ) {
                $partyFound = true;
            } elseif (
                (int) $gl->finance_account_id
                    === (int) $voucher->finance_account_id
                && $voucher->finance_account_id !== null
                && $gl->account_type === 'asset'
                && $dr === 0
                && $cr === $amount
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

        if (! $partyFound || ! $cashLedger) {
            throw new RuntimeException(
                'Supplier or cash/bank GL entry is missing.'
            );
        }

        $company = trim((string) config('tally.company'));

        if ($company === '') {
            throw new RuntimeException('Tally company is missing.');
        }

        if (
            $remoteId !== null
            && ! preg_match('/^[A-Za-z0-9-]{1,128}$/', $remoteId)
        ) {
            throw new RuntimeException('Invalid remote ID.');
        }

        $escape = static fn (string $value): string =>
            htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');

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

        $message = $import->addChild('REQUESTDATA')
            ->addChild('TALLYMESSAGE');

        $node = $message->addChild('VOUCHER');

        if ($remoteId !== null) {
            $node->addAttribute('REMOTEID', $remoteId);
        }

        $node->addAttribute('VCHTYPE', 'Payment');
        $node->addAttribute('ACTION', 'Create');
        $node->addAttribute(
            'OBJVIEW',
            'Accounting Voucher View'
        );

        $node->addChild('DATE', $date);
        $node->addChild('VOUCHERTYPENAME', 'Payment');
        $node->addChild(
            'PARTYLEDGERNAME',
            $escape($details['supplier_ledger'])
        );
        $node->addChild(
            'REFERENCE',
            $escape($voucher->voucher_no)
        );
        $node->addChild(
            'NARRATION',
            $escape(
                'TCH ERP Supplier Payment '.$voucher->voucher_no
                .' against '.$details['bill_reference']
            )
        );
        $node->addChild(
            'PERSISTEDVIEW',
            'Accounting Voucher View'
        );
        $node->addChild('EFFECTIVEDATE', $date);
        $node->addChild('ISOPTIONAL', 'No');
        $node->addChild('ISCANCELLED', 'No');

        // Debit supplier and allocate against original expense.
        $party = $node->addChild('ALLLEDGERENTRIES.LIST');
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

        // Credit actual cash/bank ledger.
        $cash = $node->addChild('ALLLEDGERENTRIES.LIST');
        $cash->addChild('LEDGERNAME', $escape($cashLedger));
        $cash->addChild('ISDEEMEDPOSITIVE', 'No');
        $cash->addChild('ISPARTYLEDGER', 'No');
        $cash->addChild('AMOUNT', $money);

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Hospital supplier Payment XML generation failed.'
            );
        }

        return $result;
    }
}
