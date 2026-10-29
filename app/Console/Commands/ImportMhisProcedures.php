<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ImportMhisProcedures extends Command
{
    protected $signature = 'mhis:import-procedures
        {--file=/tmp/tch_mhis_procedures_import.csv}
        {--execute : Apply changes to PostgreSQL}';

    protected $description = 'Validate and import the MHIS IPD procedure master';

    public function handle(): int
    {
        $path = $this->option('file');
        $handle = @fopen($path, 'rb');

        if (!$handle) {
            $this->error("Cannot read CSV: {$path}");
            return self::FAILURE;
        }

        try {
            $header = fgetcsv($handle);
            $required = [
                'source_row_number',
                'source_serial_number',
                'procedure_code',
                'specialty',
                'medical_surgical',
                'package_name',
                'procedure_name',
                'procedure_price',
                'source_price_text',
                'average_length_of_stay',
                'stratification_options',
                'stratification_rate',
                'implants',
                'preauth_documents',
                'claim_documents',
            ];

            if ($header !== $required) {
                throw new RuntimeException('Unexpected CSV columns.');
            }

            $records = [];
            $seen = [];

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($required)) {
                    throw new RuntimeException('Invalid CSV column count.');
                }

                $data = array_combine($required, $row);
                $sourceRow = $data['source_row_number'];

                if (
                    !ctype_digit($sourceRow) ||
                    (int) $sourceRow < 2 ||
                    isset($seen[$sourceRow])
                ) {
                    throw new RuntimeException(
                        "Invalid or repeated Excel row: {$sourceRow}"
                    );
                }

                if (trim($data['procedure_code']) === '') {
                    throw new RuntimeException(
                        "Missing procedure code at row {$sourceRow}"
                    );
                }

                if (
                    $data['procedure_price'] !== '' &&
                    !preg_match(
                        '/^\d{1,10}(\.\d{1,2})?$/',
                        $data['procedure_price']
                    )
                ) {
                    throw new RuntimeException(
                        "Invalid price at row {$sourceRow}"
                    );
                }

                $seen[$sourceRow] = true;

                $records[] = [
                    'source_row_number' => (int) $sourceRow,
                    'source_serial_number' =>
                        (int) $data['source_serial_number'],
                    'procedure_code' => $data['procedure_code'],
                    'specialty' => $data['specialty'],
                    'medical_surgical' => $data['medical_surgical'],
                    'package_name' => $data['package_name'],
                    'procedure_name' => $data['procedure_name'],
                    'procedure_price' =>
                        $data['procedure_price'] === ''
                            ? null
                            : $data['procedure_price'],
                    'source_price_text' => $data['source_price_text'],
                    'average_length_of_stay' =>
                        $data['average_length_of_stay'],
                    'stratification_options' =>
                        $data['stratification_options'],
                    'stratification_rate' =>
                        $data['stratification_rate'],
                    'implants' => $data['implants'],
                    'preauth_documents' => $data['preauth_documents'],
                    'claim_documents' => $data['claim_documents'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($records) !== 3232) {
                throw new RuntimeException(
                    'Expected 3232 records; found ' . count($records)
                );
            }

            $this->info('CSV validation passed.');
            $this->info('Records: ' . count($records));

            if (!$this->option('execute')) {
                $this->warn('DRY RUN — PostgreSQL unchanged.');
                return self::SUCCESS;
            }

            DB::transaction(function () use ($records) {
                foreach (array_chunk($records, 200) as $chunk) {
                    DB::table('mhis_procedures')->upsert(
                        $chunk,
                        ['source_row_number'],
                        [
                            'source_serial_number',
                            'procedure_code',
                            'specialty',
                            'medical_surgical',
                            'package_name',
                            'procedure_name',
                            'procedure_price',
                            'source_price_text',
                            'average_length_of_stay',
                            'stratification_options',
                            'stratification_rate',
                            'implants',
                            'preauth_documents',
                            'claim_documents',
                            'is_active',
                            'updated_at',
                        ]
                    );
                }
            });

            $this->info('Database import committed successfully.');
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($handle);
        }
    }
}
