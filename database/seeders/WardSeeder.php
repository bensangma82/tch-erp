<?php

namespace Database\Seeders;

use App\Models\Ward;
use Illuminate\Database\Seeder;

class WardSeeder extends Seeder
{
    public function run(): void
    {
        $wards = [
            [
                'code' => 'MALE_WARD',
                'name' => 'Male Ward',
                'ward_type' => 'general',
                'is_active' => true,
                'remarks' => null,
            ],
            [
                'code' => 'FEMALE_WARD',
                'name' => 'Female Ward',
                'ward_type' => 'general',
                'is_active' => true,
                'remarks' => null,
            ],
            [
                'code' => 'PEDIATRIC_WARD',
                'name' => 'Pediatric Ward',
                'ward_type' => 'pediatric',
                'is_active' => true,
                'remarks' => null,
            ],
            [
                'code' => 'OBG_WARD',
                'name' => 'Obstetrics & Gynaecology Ward',
                'ward_type' => 'obstetrics',
                'is_active' => true,
                'remarks' => null,
            ],
            [
                'code' => 'ICU',
                'name' => 'Intensive Care Unit',
                'ward_type' => 'critical_care',
                'is_active' => true,
                'remarks' => null,
            ],
            [
                'code' => 'NEPHROLOGY_WARD',
                'name' => 'Nephrology Ward',
                'ward_type' => 'specialty',
                'is_active' => true,
                'remarks' => null,
            ],
        ];

        foreach ($wards as $ward) {
            Ward::updateOrCreate(
                [
                    'code' => $ward['code'],
                ],
                $ward
            );
        }
    }
}