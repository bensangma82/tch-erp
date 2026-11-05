<?php

namespace App\Console\Commands;

use App\Models\MhisHighEndItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportMhisHighEndItems extends Command
{
    protected $signature = 'mhis:import-high-end {file} {--execute : Actually upsert records}';
    protected $description = 'Validate or import MHIS high-end drugs and implants CSV';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (!is_readable($path)) {
            $this->error('CSV file is not readable: '.$path);
            return self::FAILURE;
        }
        $fh = fopen($path, 'rb');
        if (!$fh) return self::FAILURE;
        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            $this->error('Empty CSV');
            return self::FAILURE;
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $expected = [
            'source_excel_row','specialty','item_name','reference_price',
            'opd_ipd','hospital_type','maximum_multiplier','procedure_code',
        ];
        if ($header !== $expected) {
            fclose($fh);
            $this->error('Unexpected CSV headers: '.implode(', ', $header));
            return self::FAILURE;
        }
        $rows = [];
        try {
            while (($values = fgetcsv($fh)) !== false) {
                if ($values === [null]) continue;
                if (count($values) !== count($expected)) {
                    throw new \RuntimeException('Invalid CSV column count');
                }
                $r = array_combine($expected, $values);
                $rowNumber = filter_var($r['source_excel_row'], FILTER_VALIDATE_INT);
                if ($rowNumber === false || $rowNumber <= 0 || isset($rows[$rowNumber])) {
                    throw new \RuntimeException('Invalid or duplicate Excel row: '.$r['source_excel_row']);
                }
                if (trim($r['procedure_code']) === '' || trim($r['item_name']) === '') {
                    throw new \RuntimeException('Missing code/name at Excel row '.$rowNumber);
                }
                foreach (['maximum_multiplier'] as $numeric) {
                    if ($r[$numeric] !== '' && (!is_numeric($r[$numeric]) || (float) $r[$numeric] < 0)) {
                        throw new \RuntimeException('Invalid '.$numeric.' at Excel row '.$rowNumber);
                    }
                }
                $rows[$rowNumber] = [
                    'source_excel_row' => $rowNumber,
                    'specialty' => trim($r['specialty']) ?: null,
                    'item_name' => trim($r['item_name']),
                    'reference_price' => is_numeric($r['reference_price']) && (float) $r['reference_price'] >= 0 ? $r['reference_price'] : null,
                    'source_price_text' => trim($r['reference_price']) ?: null,
                    'opd_ipd' => trim($r['opd_ipd']) ?: null,
                    'hospital_type' => trim($r['hospital_type']) ?: null,
                    'maximum_multiplier' => $r['maximum_multiplier'] === '' ? null : $r['maximum_multiplier'],
                    'procedure_code' => trim($r['procedure_code']),
                    'is_active' => true,
                ];
            }
        } catch (\Throwable $e) {
            fclose($fh);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        fclose($fh);
        $this->info('Validated '.count($rows).' distinct source rows. Shared procedure codes are permitted.');
        if (!$this->option('execute')) {
            $this->warn('Dry-run only; pass --execute to import.');
            return self::SUCCESS;
        }
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                MhisHighEndItem::query()->updateOrCreate(
                    ['source_excel_row' => $row['source_excel_row']],
                    $row
                );
            }
        });
        $this->info('Imported/updated '.count($rows).' high-end master records.');
        return self::SUCCESS;
    }
}
