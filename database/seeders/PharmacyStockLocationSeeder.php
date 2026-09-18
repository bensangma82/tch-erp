<?php

namespace Database\Seeders;

use App\Models\PharmacyStockLocation;
use Illuminate\Database\Seeder;

class PharmacyStockLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'code' => 'CENTRAL_STORE',
                'name' => 'Central Store',
                'location_type' => 'central_store',
            ],
            [
                'code' => 'PHARMACY',
                'name' => 'Pharmacy',
                'location_type' => 'pharmacy',
            ],
            [
                'code' => 'ICU',
                'name' => 'ICU',
                'location_type' => 'ward',
            ],
            [
                'code' => 'MALE_WARD',
                'name' => 'Male Ward',
                'location_type' => 'ward',
            ],
            [
                'code' => 'FEMALE_WARD',
                'name' => 'Female Ward',
                'location_type' => 'ward',
            ],
            [
                'code' => 'PEDIATRIC_WARD',
                'name' => 'Pediatric Ward',
                'location_type' => 'ward',
            ],
            [
                'code' => 'OBG_WARD',
                'name' => 'Obstetrics & Gynaecology Ward',
                'location_type' => 'ward',
            ],
        ];

        foreach ($locations as $location) {
            PharmacyStockLocation::updateOrCreate(
                [
                    'code' => $location['code'],
                ],
                [
                    'name' => $location['name'],
                    'location_type' => $location['location_type'],
                    'is_active' => true,
                ]
            );
        }
    }
}