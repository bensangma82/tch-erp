<?php

namespace App\Services\Tally;

use App\Models\HospitalExpense;
use App\Models\JournalEntry;
use App\Models\TallyExport;
use RuntimeException;
use SimpleXMLElement;

class TallyHospitalExpenseXmlBuilder
{
    public function build(
        JournalEntry $journal,
        ?string $remoteId = null
    ): string {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'hospital_expense'
            || $journal->source_event !== 'accrual_posted'
        ) {
            throw new RuntimeException(
                'Only posted hospital expense accruals are supported.'
            );
        }

        if (
            $journal->tallyExport()->exists()
            || (
                $journal->finance_voucher_id !== null
                && TallyExport::where(
                    'finance_voucher_id',
                    $journal->finance_voucher_id
                )->exists()
            )
        ) {
            throw new RuntimeException(
                'Existing Tally export requires reconciliation.'
            );
        }

        $expense = HospitalExpense::findOrFail($journal->source_id);

        $details = app(TallyHospitalExpenseResolver::class)
            ->resolve($expense);

        if ((int) $details['journal_id'] !== (int) $journal->id) {
            throw new RuntimeException(
                'Expense and journal linkage mismatch.'
            );
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

        $journal->loadMissing('lines.generalLedgerAccount');

        $entries = [];
        $debits = 0;
        $credits = 0;
        $payableCredit = 0;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl) {
                throw new RuntimeException('Missing GL account.');
            }

            $dr = $toPaise($line->debit);
            $cr = $toPaise($line->credit);

            if (($dr > 0) === ($cr > 0)) {
                throw new RuntimeException('Invalid GL line.');
            }

            $debits += $dr;
            $credits += $cr;

            if ((string) $gl->account_code === '2200') {
                if ($dr !== 0 || $cr <= 0 || $payableCredit !== 0) {
                    throw new RuntimeException(
                        'Invalid supplier payable entry.'
                    );
                }

                $payableCredit = $cr;
                $ledger = $details['supplier_ledger'];
                $party = true;
            } else {
                if (
                    $cr !== 0
                    || $gl->account_type !== 'expense'
                    || blank($gl->tally_ledger_name)
                ) {
                    throw new RuntimeException(
                        'Unsupported expense GL structure or mapping.'
                    );
                }

                $ledger = trim($gl->tally_ledger_name);
                $party = false;
            }

            $entries[] = [
                'ledger' => $ledger,
                'debit' => $dr > 0,
                'amount' => $dr > 0 ? $dr : $cr,
                'party' => $party,
            ];
        }

        if (
            count($entries) < 2
            || $debits <= 0
            || $debits !== $credits
            || $payableCredit !== $credits
            || $payableCredit !== $toPaise($expense->total_amount)
        ) {
            throw new RuntimeException(
                'Hospital expense accrual does not reconcile.'
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
            htmlspecialchars(
                $value, ENT_XML1 | ENT_COMPAT, 'UTF-8'
            );

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

        $voucher->addAttribute('VCHTYPE', 'Journal');
        $voucher->addAttribute('ACTION', 'Create');
        $voucher->addAttribute(
            'OBJVIEW',
            'Accounting Voucher View'
        );

        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERTYPENAME', 'Journal');
        $voucher->addChild(
            'REFERENCE',
            $escape($expense->expense_no)
        );
        $voucher->addChild(
            'NARRATION',
            $escape(
                'TCH ERP Expense '.$expense->expense_no
                .' | Supplier Invoice '
                .($details['supplier_invoice'] ?: 'N/A')
            )
        );
        $voucher->addChild(
            'PERSISTEDVIEW',
            'Accounting Voucher View'
        );
        $voucher->addChild('EFFECTIVEDATE', $date);
        $voucher->addChild('ISOPTIONAL', 'No');
        $voucher->addChild('ISCANCELLED', 'No');

        foreach ($entries as $entry) {
            $node = $voucher->addChild('ALLLEDGERENTRIES.LIST');

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
            $node->addChild(
                'AMOUNT',
                ($entry['debit'] ? '-' : '')
                .$money($entry['amount'])
            );

            if ($entry['party']) {
                $allocation = $node->addChild(
                    'BILLALLOCATIONS.LIST'
                );

                $allocation->addChild(
                    'NAME',
                    $escape($details['bill_reference'])
                );
                $allocation->addChild('BILLTYPE', 'New Ref');
                $allocation->addChild(
                    'AMOUNT',
                    $money($entry['amount'])
                );
            }
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException(
                'Hospital expense XML generation failed.'
            );
        }

        return $result;
    }
}
