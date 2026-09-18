<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class BedSeeder extends Seeder
{
    public function run(): void
    {
        $wardBeds = [
            'MALE_WARD' => [
                'M-01',
                'M-02',
                'M-03',
                'M-04',
                'M-05',
                'M-06',
                'M-07',
                'M-08',
                'M-09',
                'M-10',
            ],

            'FEMALE_WARD' => [
                'F-01',
                'F-02',
                'F-03',
                'F-04',
                'F-05',
                'F-06',
                'F-07',
                'F-08',
                'F-09',
                'F-10',
            ],

            'PEDIATRIC_WARD' => [
                'P-01',
                'P-02',
                'P-03',
                'P-04',
                'P-05',
                'P-06',
                'P-07',
                'P-08',
            ],

            'OBG_WARD' => [
                'OG-01',
                'OG-02',
                'OG-03',
                'OG-04',
                'OG-05',
                'OG-06',
                'OG-07',
                'OG-08',
            ],

            'ICU' => [
                'ICU-01',
                'ICU-02',
                'ICU-03',
                'ICU-04',
                'ICU-05',
            ],

            'NEPHROLOGY_WARD' => [
                'N-01',
                'N-02',
                'N-03',
                'N-04',
                'N-05',
                'N-06',
                'N-07',
            ],
        ];

        foreach ($wardBeds as $wardCode => $beds) {
            $ward = Ward::where('code', $wardCode)->firstOrFail();

            foreach ($beds as $bedNumber) {
                Bed::updateOrCreate(
                    [
                        'ward_id' => $ward->id,
                        'bed_number' => $bedNumber,
                    ],
                    [
                        'bed_type' => $wardCode === 'ICU'
                            ? 'icu'
                            : 'general',

                        'status' => 'available',

                        'is_active' => true,

                        'remarks' => null,
                    ]
                );
            }
        }
    }
}