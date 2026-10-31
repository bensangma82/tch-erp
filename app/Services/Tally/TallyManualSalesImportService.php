<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Models\TallyImportedVoucher;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;

class TallyManualSalesImportService
{
    public function register(
        JournalEntry $journal,
        string $xmlPath,
        int $verifiedBy
    ): array {
        if (! is_file($xmlPath)) {
            throw new RuntimeException('Tally evidence file not found.');
        }

        $contents = file_get_contents($xmlPath);

        if ($contents === false) {
            throw new RuntimeException('Cannot read Tally evidence.');
        }

        // Tally may export XML with a BOM and invalid XML 1.0
        // control-character references.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $contents = preg_replace_callback(
            '/&#(?:x([0-9a-fA-F]+)|([0-9]+));/',
            static function (array $m): string {
                $n = isset($m[1]) && $m[1] !== ''
                    ? hexdec($m[1])
                    : (int) $m[2];

                $invalid = ($n < 32 && ! in_array($n, [9, 10, 13], true))
                    || ($n >= 0xD800 && $n <= 0xDFFF)
                    || $n > 0x10FFFF;

                return $invalid ? '' : $m[0];
            },
            $contents
        );

        $xml = simplexml_load_string(
            $contents,
            SimpleXMLElement::class,
            LIBXML_NONET
        );

        if ($xml === false) {
            throw new RuntimeException('Invalid Tally XML.');
        }

        $vouchers = $xml->xpath('//VOUCHER');

        if (count($vouchers) !== 1) {
            throw new RuntimeException('Expected exactly one Tally voucher.');
        }

        $voucher = $vouchers[0];

        $guid = trim((string) $voucher->GUID);
        $type = trim((string) $voucher->VOUCHERTYPENAME);
        $number = trim((string) $voucher->VOUCHERNUMBER);
        $invoice = trim((string) $voucher->REFERENCE);
        $date = (string) $voucher->DATE;
        $company = trim((string) config('tally.company'));

        if (
            $company === ''
            || $guid === ''
            || $number === ''
            || $type !== 'Sales'
            || ! preg_match('/^\d{8}$/', $date)
        ) {
            throw new RuntimeException('Invalid Sales voucher identity.');
        }

        if (! DB::table('users')->where('id', $verifiedBy)->exists()) {
            throw new RuntimeException('Reconciliation user not found.');
        }

        return DB::transaction(function () use (
            $journal, $voucher, $guid, $number,
            $invoice, $date, $company, $verifiedBy, $xmlPath
        ) {
            $journal = JournalEntry::query()
                ->whereKey($journal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $journal->status !== 'posted'
                || $journal->source_type !== 'ip_billing_account'
                || $journal->source_event !== 'finalized'
            ) {
                throw new RuntimeException('Not a posted IPD Sales journal.');
            }

            if (
                TallyExport::where('journal_entry_id', $journal->id)->exists()
                || TallyExport::where('remote_id', $guid)->exists()
                || TallyImportedVoucher::where('tally_company', $company)
                    ->where(function ($q) use ($guid) {
                        $q->where('guid', $guid)
                          ->orWhere('remote_id', $guid);
                    })->exists()
            ) {
                throw new RuntimeException('Voucher is already registered.');
            }

            $journal->load('lines.generalLedgerAccount');

            $bill = DB::table('ip_billing_accounts')
                ->where('id', $journal->source_id)
                ->first();

            if (! $bill || $invoice !== $bill->final_bill_no) {
                throw new RuntimeException('IPD invoice reference mismatch.');
            }

            $voucherDate = substr($date, 0, 4).'-'
                .substr($date, 4, 2).'-'.substr($date, 6, 2);

            if ($voucherDate !== $journal->journal_date->format('Y-m-d')) {
                throw new RuntimeException('Voucher accounting date mismatch.');
            }

            if (
                trim((string) $voucher->PARTYLEDGERNAME)
                !== 'Patient Receivables'
                || (string) $voucher->ISCANCELLED === 'Yes'
                || (string) $voucher->ISDELETED === 'Yes'
            ) {
                throw new RuntimeException('Unexpected voucher party or status.');
            }

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
                    if (
                        trim((string) $allocation->NAME) === ''
                        && trim((string) $allocation->BILLTYPE) === ''
                        && trim((string) $allocation->AMOUNT) === ''
                    ) {
                        continue;
                    }

                    $allocations[] = [
                        'ledger' => $name,
                        'name' => (string) $allocation->NAME,
                        'type' => (string) $allocation->BILLTYPE,
                        'amount' => (int) round(
                            (float) $allocation->AMOUNT * 100
                        ),
                    ];
                }
            }

            ksort($erp);
            ksort($tally);

            if ($erp !== $tally || count($entries) !== count($journal->lines)) {
                throw new RuntimeException('Tally and ERP ledger entries differ.');
            }

            $receivable = (int) round(
                (float) $journal->lines
                    ->filter(fn ($l) =>
                        $l->generalLedgerAccount?->account_code === '1200'
                    )->sum('debit') * 100
            );

            if (
                $receivable <= 0
                || count($allocations) !== 1
                || $allocations[0] !== [
                    'ledger' => 'Patient Receivables',
                    'name' => $invoice,
                    'type' => 'New Ref',
                    'amount' => -$receivable,
                ]
            ) {
                throw new RuntimeException('Bill-wise allocation mismatch.');
            }

            $now = now();

            $import = TallyImportedVoucher::create([
                'tally_company' => $company,
                'guid' => $guid,
                'remote_id' => $guid,
                'voucher_date' => $voucherDate,
                'voucher_type' => 'Sales',
                'voucher_number' => $number,
                'party_ledger' => 'Patient Receivables',
                'narration' => (string) $voucher->NARRATION,
                'reconciliation_status' => 'matched',
                'match_method' => 'verified_manual_xml',
                'match_confidence' => 1.00,
                'reconciliation_notes' =>
                    'Verified manual Sales import against ERP '
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
                'export_reference' => 'MANUAL-SALES-'.$journal->id,
                'remote_id' => $guid,
                'confirmed_at' => $now,
                'tally_accounting_date' => $voucherDate,
                'remarks' => 'Manually imported Sales voucher '.$number
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
