<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\MhisSettlementImportBatch;

class MhisSettlementExcelImportService
{
    public function import(string $filePath, ?int $userId = null, ?string $originalFilename = null): MhisSettlementImportBatch
    {
        $hash = hash_file('sha256', $filePath);

        if (MhisSettlementImportBatch::where('file_sha256', $hash)->exists()) {
            throw ValidationException::withMessages([
                'file' => 'This Excel file has already been imported.',
            ]);
        }

        $spreadsheet = IOFactory::load($filePath);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $headers = $sheet->rangeToArray('A1:V1')[0];

            $expected = [
                0 => 'Registration ID',
                1 => 'Program ID',
                2 => 'Beneficiary Name',
                8 => 'Procedure Details',
                11 => 'Claim Initiated Amount',
                12 => 'Claim Approved Amount',
                13 => 'Case Status',
                18 => 'TDS Amount',
                19 => 'Claim Paid Amount',
                20 => 'UTR',
                21 => 'Payment Date',
            ];

            foreach ($expected as $index => $label) {
                if (trim((string) ($headers[$index] ?? '')) !== $label) {
                    throw ValidationException::withMessages([
                        'file' => "Unexpected Excel header: {$label}",
                    ]);
                }
            }

            $records = [];
            $totals = ['approved' => 0, 'tds' => 0, 'net' => 0];

            for ($r = 2; $r <= $sheet->getHighestDataRow(); $r++) {
                $values = $sheet->rangeToArray(
                    "A{$r}:V{$r}", null, true, false
                )[0];

                if (!array_filter($values, fn ($v) => $v !== null && $v !== '')) {
                    continue;
                }

                $money = function ($index) use ($values, $r) {
                    $value = $values[$index] ?? null;

                    if (!is_numeric($value) || (float) $value < 0) {
                        throw ValidationException::withMessages([
                            'file' => "Invalid amount at Excel row {$r}",
                        ]);
                    }

                    $cents = round((float) $value * 100);

                    if (abs((float) $value * 100 - $cents) > 0.0001) {
                        throw ValidationException::withMessages([
                            'file' => "Invalid decimal precision at row {$r}",
                        ]);
                    }

                    return (int) $cents;
                };

                $initiated = $money(11);
                $approved = $money(12);
                $tds = $money(18);
                $net = $money(19);

                if ($approved !== $tds + $net) {
                    throw ValidationException::withMessages([
                        'file' => "Payment mismatch at Excel row {$r}",
                    ]);
                }

                $registration = trim((string) ($values[0] ?? ''));
                $utr = trim((string) ($values[20] ?? ''));

                if ($registration === '' || $utr === '') {
                    throw ValidationException::withMessages([
                        'file' => "Missing registration ID or UTR at row {$r}",
                    ]);
                }

                $rawDate = $values[21] ?? null;

                if (!is_numeric($rawDate)) {
                    throw ValidationException::withMessages([
                        'file' => "Invalid payment date at row {$r}",
                    ]);
                }

                $records[] = [
                    'excel_row_number' => $r,
                    'registration_id' => $registration,
                    'programme_id' => trim((string) ($values[1] ?? '')),
                    'beneficiary_name' => trim((string) ($values[2] ?? '')),
                    'procedure_name' => trim((string) ($values[8] ?? '')),
                    'initiated_amount' => number_format($initiated / 100, 2, '.', ''),
                    'approved_amount' => number_format($approved / 100, 2, '.', ''),
                    'tds_amount' => number_format($tds / 100, 2, '.', ''),
                    'net_amount' => number_format($net / 100, 2, '.', ''),
                    'payment_status' => trim((string) ($values[13] ?? '')),
                    'utr_reference' => $utr,
                    'payment_date' => Date::excelToDateTimeObject(
                        (float) $rawDate
                    )->format('Y-m-d'),
                    'match_status' => 'unmatched',
                    'source_data' => array_combine($headers, $values),
                ];

                $totals['approved'] += $approved;
                $totals['tds'] += $tds;
                $totals['net'] += $net;
            }

            return DB::transaction(function () use (
                $filePath, $hash, $userId, $records, $totals, $originalFilename
            ) {
                $batch = MhisSettlementImportBatch::create([
                    'original_filename' => $originalFilename ?? basename($filePath),
                    'file_sha256' => $hash,
                    'status' => 'imported',
                    'total_rows' => count($records),
                    'total_approved' => $totals['approved'] / 100,
                    'total_tds' => $totals['tds'] / 100,
                    'total_net' => $totals['net'] / 100,
                    'uploaded_by' => $userId,
                ]);

                foreach (array_chunk($records, 100) as $chunk) {
                    foreach ($chunk as $record) {
                        $batch->rows()->create($record);
                    }
                }

                return $batch;
            });
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }
}
