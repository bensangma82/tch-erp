<?php

namespace Database\Seeders;

use App\Models\Sonologist;
use Illuminate\Database\Seeder;

class SonologistSeeder extends Seeder
{
    public function run(): void
    {
        $sonologists = [
            [
                'name' => 'Dr. Sengchi N G Momin',
                'qualification' => 'MBBS, F Diab., PG Diploma in Sonography',
            ],
            [
                'name' => 'Dr. Rongsil Ch Marak',
                'qualification' => 'MBBS',
            ],
        ];

        foreach ($sonologists as $sonologist) {
            Sonologist::updateOrCreate(
                ['name' => $sonologist['name']],
                [
                    'qualification' => $sonologist['qualification'],
                    'is_active' => true,
                ]
            );
        }
    }
}
