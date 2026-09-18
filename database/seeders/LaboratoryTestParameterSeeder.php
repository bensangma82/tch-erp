<?php

namespace Database\Seeders;

use App\Models\LaboratoryTestParameter;
use App\Models\Service;
use Illuminate\Database\Seeder;

class LaboratoryTestParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cbc = Service::query()
            ->where('category', 'laboratory')
            ->where(function ($query) {
                $query
                    ->where('code', 'CBC')
                    ->orWhere('name', 'Complete Blood Count');
            })
            ->first();

        if (! $cbc) {
            $this->command?->warn(
                'CBC service not found. Create a laboratory service with code CBC first.'
            );

            return;
        }

        $parameters = [
            [
                'parameter_name' => 'Hemoglobin',
                'unit' => 'g/dL',
                'reference_range' => '12.0 - 16.0',
                'low_value' => 12,
                'high_value' => 16,
                'critical_low' => 7,
                'critical_high' => 20,
            ],
            [
                'parameter_name' => 'Total WBC Count',
                'unit' => '/µL',
                'reference_range' => '4,000 - 11,000',
                'low_value' => 4000,
                'high_value' => 11000,
                'critical_low' => 2000,
                'critical_high' => 30000,
            ],
            [
                'parameter_name' => 'Neutrophils',
                'unit' => '%',
                'reference_range' => '40 - 75',
                'low_value' => 40,
                'high_value' => 75,
                'critical_low' => 20,
                'critical_high' => 90,
            ],
            [
                'parameter_name' => 'Lymphocytes',
                'unit' => '%',
                'reference_range' => '20 - 45',
                'low_value' => 20,
                'high_value' => 45,
                'critical_low' => 10,
                'critical_high' => 70,
            ],
            [
                'parameter_name' => 'Monocytes',
                'unit' => '%',
                'reference_range' => '2 - 10',
                'low_value' => 2,
                'high_value' => 10,
                'critical_low' => 0,
                'critical_high' => 20,
            ],
            [
                'parameter_name' => 'Eosinophils',
                'unit' => '%',
                'reference_range' => '1 - 6',
                'low_value' => 1,
                'high_value' => 6,
                'critical_low' => 0,
                'critical_high' => 15,
            ],
            [
                'parameter_name' => 'Basophils',
                'unit' => '%',
                'reference_range' => '0 - 1',
                'low_value' => 0,
                'high_value' => 1,
                'critical_low' => 0,
                'critical_high' => 5,
            ],
            [
                'parameter_name' => 'RBC Count',
                'unit' => 'million/µL',
                'reference_range' => '4.0 - 5.5',
                'low_value' => 4,
                'high_value' => 5.5,
                'critical_low' => 2.5,
                'critical_high' => 7,
            ],
            [
                'parameter_name' => 'Hematocrit',
                'unit' => '%',
                'reference_range' => '36 - 46',
                'low_value' => 36,
                'high_value' => 46,
                'critical_low' => 20,
                'critical_high' => 60,
            ],
            [
                'parameter_name' => 'MCV',
                'unit' => 'fL',
                'reference_range' => '80 - 100',
                'low_value' => 80,
                'high_value' => 100,
                'critical_low' => 60,
                'critical_high' => 120,
            ],
            [
                'parameter_name' => 'MCH',
                'unit' => 'pg',
                'reference_range' => '27 - 32',
                'low_value' => 27,
                'high_value' => 32,
                'critical_low' => 20,
                'critical_high' => 40,
            ],
            [
                'parameter_name' => 'MCHC',
                'unit' => 'g/dL',
                'reference_range' => '32 - 36',
                'low_value' => 32,
                'high_value' => 36,
                'critical_low' => 25,
                'critical_high' => 40,
            ],
            [
                'parameter_name' => 'RDW-CV',
                'unit' => '%',
                'reference_range' => '11.5 - 14.5',
                'low_value' => 11.5,
                'high_value' => 14.5,
                'critical_low' => 8,
                'critical_high' => 25,
            ],
            [
                'parameter_name' => 'Platelet Count',
                'unit' => '/µL',
                'reference_range' => '150,000 - 450,000',
                'low_value' => 150000,
                'high_value' => 450000,
                'critical_low' => 50000,
                'critical_high' => 1000000,
            ],
            [
                'parameter_name' => 'MPV',
                'unit' => 'fL',
                'reference_range' => '7.5 - 12.0',
                'low_value' => 7.5,
                'high_value' => 12,
                'critical_low' => 5,
                'critical_high' => 15,
            ],
        ];

        foreach ($parameters as $index => $parameter) {
            LaboratoryTestParameter::updateOrCreate(
                [
                    'service_id' => $cbc->id,
                    'parameter_name' => $parameter['parameter_name'],
                ],
                [
                    'unit' => $parameter['unit'],
                    'reference_range' => $parameter['reference_range'],
                    'low_value' => $parameter['low_value'],
                    'high_value' => $parameter['high_value'],
                    'critical_low' => $parameter['critical_low'],
                    'critical_high' => $parameter['critical_high'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info(
            'CBC laboratory parameters seeded successfully.'
        );
    }
}