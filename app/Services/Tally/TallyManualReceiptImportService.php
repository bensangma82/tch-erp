<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Models\TallyImportedVoucher;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;

class TallyManualReceiptImportService
{
    public function register(
        JournalEntry $journal,
        string $xmlPath,
        int $verifiedBy
    ): array {
        if (! is_file($xmlPath)) {
            throw new RuntimeException('Receipt XML not found.');
        }

        $contents = file_get_contents($xmlPath);

        if ($contents === false) {
            throw new RuntimeException('Cannot read Receipt XML.');
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $contents = preg_replace(
            '/&#(?:4|x4|x04);/i',
            '',
            $contents
        );

        $xml = simplexml_load_string(
            $contents,
            SimpleXMLElement::class,
            LIBXML_NONET
        );

        if ($xml === false) {
            throw new RuntimeException('Invalid Receipt XML.');
        }

        $vouchers = $xml->xpath('//VOUCHER');

        if (count($vouchers) !== 1) {
            throw new RuntimeException('Expected exactly one Receipt.');
        }

        $voucher = $vouchers[0];

        $guid = trim((string) $voucher->GUID);
        $remoteId = trim((string) $voucher['REMOTEID']);
        $type = trim((string) $voucher->VOUCHERTYPENAME);
        $number = trim((string) $voucher->VOUCHERNUMBER);
        $reference = trim((string) $voucher->REFERENCE);
        $date = trim((string) $voucher->DATE);
        $company = trim((string) config('tally.company'));

        if (
            $company === ''
            || $guid === ''
            || $remoteId !== $guid
            || $type !== 'Receipt'
            || $number === ''
            || ! preg_match('/^\d{8}$/', $date)
            || (string) $voucher->ISCANCELLED === 'Yes'
            || (string) $voucher->ISDELETED === 'Yes'
        ) {
            throw new RuntimeException('Invalid Receipt identity.');
        }

        return DB::transaction(function () use (
            $journal, $voucher, $guid, $number,
            $reference, $date, $company, $verifiedBy, $xmlPath
        ) {
            $journal = JournalEntry::query()
                ->whereKey($journal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $journal->status !== 'posted'
                || $journal->source_type !== 'ip_billing_payment'
                || $journal->source_event !== 'received'
            ) {
                throw new RuntimeException('Not a posted IPD Receipt journal.');
            }

            if (
                TallyExport::where('journal_entry_id', $journal->id)->exists()
                || TallyExport::where('remote_id', $guid)->exists()
                || TallyImportedVoucher::where('tally_company', $company)
                    ->where('guid', $guid)->exists()
            ) {
                throw new RuntimeException('Receipt already registered.');
            }

            $payment = DB::table('ip_billing_payments')
                ->where('id', $journal->source_id)
                ->first();

            if (
                ! $payment
                || $payment->status !== 'active'
                || $reference !== $payment->receipt_no
            ) {
                throw new RuntimeException('ERP payment reference mismatch.');
            }

            $bill = DB::table('ip_billing_accounts')
                ->where('id', $payment->ip_billing_account_id)
                ->first();

            if (! $bill || empty($bill->final_bill_no)) {
                throw new RuntimeException('Final IPD bill not found.');
            }

            $voucherDate = substr($date, 0, 4).'-'
                .substr($date, 4, 2).'-'.substr($date, 6, 2);

            if (
                $voucherDate !== substr($payment->payment_date, 0, 10)
                || $voucherDate !== $journal->journal_date->format('Y-m-d')
            ) {
                throw new RuntimeException('Receipt date mismatch.');
            }

            $journal->load('lines.generalLedgerAccount');

            $erp = [];
            foreach ($journal->lines as $line) {
                $name = trim((string)
                    $line->generalLedgerAccount?->tally_ledger_name);

                if ($name === '') {
                    throw new RuntimeException('Unmapped ERP ledger.');
                }

                $amount = (int) round(
                    ((float) $line->credit - (float) $line->debit) * 100
                );

                $erp[$name] = ($erp[$name] ?? 0) + $amount;
            }

            $tally = [];
            $entries = [];
            $allocations = [];

            foreach ($voucher->{'ALLLEDGERENTRIES.LIST'} as $line) {
                $name = trim((string) $line->LEDGERNAME);
                $amount = (int) round((float) $line->AMOUNT * 100);

                $tally[$name] = ($tally[$name] ?? 0) + $amount;

                $entries[] = [
                    'ledger_name' => $name,
                    'amount' => (string) $line->AMOUNT,
                    'is_deemed_positive' =>
                        (string) $line->ISDEEMEDPOSITIVE === 'Yes',
                ];

                foreach ($line->{'BILLALLOCATIONS.LIST'} as $allocation) {
                    $nameRef = trim((string) $allocation->NAME);
                    $typeRef = trim((string) $allocation->BILLTYPE);
                    $amountRef = trim((string) $allocation->AMOUNT);

                    if (
                        $nameRef === ''
                        && $typeRef === ''
                        && $amountRef === ''
                    ) {
                        continue;
                    }

                    $allocations[] = [
                        'ledger' => $name,
                        'name' => $nameRef,
                        'type' => $typeRef,
                        'amount' => (int) round(
                            (float) $amountRef * 100
                        ),
                    ];
                }
            }

            ksort($erp);
            ksort($tally);

            if (
                $erp !== $tally
                || count($entries) !== count($journal->lines)
            ) {
                throw new RuntimeException('Receipt ledger mismatch.');
            }

            $paymentPaise = (int) round(
                (float) $payment->amount * 100
            );

            $expected = [
                'ledger' => 'Patient Receivables',
                'name' => $bill->final_bill_no,
                'type' => 'Agst Ref',
                'amount' => $paymentPaise,
            ];

            if (
                $paymentPaise <= 0
                || count($allocations) !== 1
                || $allocations[0] != $expected
                || array_sum($tally) !== 0
            ) {
                throw new RuntimeException('Receipt bill allocation mismatch.');
            }

            if (! DB::table('users')->where('id', $verifiedBy)->exists()) {
                throw new RuntimeException('Verification user not found.');
            }

            $now = now();

            $import = TallyImportedVoucher::create([
                'tally_company' => $company,
                'guid' => $guid,
                'remote_id' => $guid,
                'voucher_date' => $voucherDate,
                'voucher_type' => 'Receipt',
                'voucher_number' => $number,
                'party_ledger' => (string) $voucher->PARTYLEDGERNAME,
                'narration' => (string) $voucher->NARRATION,
                'reconciliation_status' => 'matched',
                'match_method' => 'verified_manual_xml',
                'match_confidence' => 1.00,
                'reconciliation_notes' =>
                    'Verified manual Receipt against ERP '
                    .$journal->journal_no.'; evidence: '
                    .basename($xmlPath),
                'reconciled_by' => $verifiedBy,
                'reconciled_at' => $now,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            foreach ($entries as $i => $entry) {
                DB::table('tally_imported_voucher_entries')->insert([
                    'tally_imported_voucher_id' => $import->id,
                    'line_no' => $i + 1,
                    'ledger_name' => $entry['ledger_name'],
                    'amount' => $entry['amount'],
                    'is_deemed_positive' => $entry['is_deemed_positive'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $export = TallyExport::create([
                'journal_entry_id' => $journal->id,
                'status' => 'confirmed',
                'export_reference' => 'MANUAL-RECEIPT-'.$journal->id,
                'remote_id' => $guid,
                'confirmed_at' => $now,
                'tally_accounting_date' => $voucherDate,
                'remarks' => 'Manually imported Receipt '.$number
                    .'; verified against Tally XML. '
                    .'Imported voucher ID '.$import->id,
            ]);

            return [
                'journal' => $journal->journal_no,
                'tally_export_id' => $export->id,
                'tally_imported_voucher_id' => $import->id,
                'tally_guid' => $guid,
                'status' => 'confirmed',
            ];
        });
    }
}
