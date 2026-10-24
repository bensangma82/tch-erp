<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'laboratory',
                'name' => 'Laboratory',
                'is_system' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'radiology',
                'name' => 'Radiology',
                'is_system' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'procedure',
                'name' => 'Procedure',
                'is_system' => false,
                'sort_order' => 30,
            ],
            [
                'code' => 'consultation',
                'name' => 'Consultation',
                'is_system' => false,
                'sort_order' => 40,
            ],
            [
                'code' => 'nursing',
                'name' => 'Nursing',
                'is_system' => false,
                'sort_order' => 50,
            ],
            [
                'code' => 'equipment',
                'name' => 'Equipment / Device Use',
                'is_system' => false,
                'sort_order' => 60,
            ],
            [
                'code' => 'consumable',
                'name' => 'Consumable',
                'is_system' => false,
                'sort_order' => 70,
            ],
            [
                'code' => 'facility',
                'name' => 'Facility / Miscellaneous',
                'is_system' => false,
                'sort_order' => 80,
            ],
            [
                'code' => 'other',
                'name' => 'Other',
                'is_system' => false,
                'sort_order' => 90,
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::firstOrCreate(
                ['code' => $category['code']],
                array_merge($category, [
                    'is_active' => true,
                ])
            );
        }
    }
}
