<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'BIOMED',
                'name' => 'Biomedical Equipment',
                'description' => 'Medical and clinical equipment used in patient care.',
                'asset_class' => 'biomedical',
                'requires_preventive_maintenance' => true,
                'requires_calibration' => true,
                'default_useful_life_years' => 8,
                'is_active' => true,
            ],
            [
                'code' => 'IT',
                'name' => 'IT Equipment',
                'description' => 'Computers, servers, networking equipment and related devices.',
                'asset_class' => 'it',
                'requires_preventive_maintenance' => true,
                'requires_calibration' => false,
                'default_useful_life_years' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'ELEC',
                'name' => 'Electrical Equipment',
                'description' => 'Electrical and power-related equipment.',
                'asset_class' => 'electrical',
                'requires_preventive_maintenance' => true,
                'requires_calibration' => false,
                'default_useful_life_years' => 8,
                'is_active' => true,
            ],
            [
                'code' => 'FURN',
                'name' => 'Furniture & Fixtures',
                'description' => 'Hospital furniture, office furniture and fixtures.',
                'asset_class' => 'furniture',
                'requires_preventive_maintenance' => false,
                'requires_calibration' => false,
                'default_useful_life_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'VEH',
                'name' => 'Vehicles',
                'description' => 'Ambulances and other hospital vehicles.',
                'asset_class' => 'vehicle',
                'requires_preventive_maintenance' => true,
                'requires_calibration' => false,
                'default_useful_life_years' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'INFRA',
                'name' => 'Building / Infrastructure',
                'description' => 'Buildings and fixed hospital infrastructure.',
                'asset_class' => 'building',
                'requires_preventive_maintenance' => true,
                'requires_calibration' => false,
                'default_useful_life_years' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'GENERAL',
                'name' => 'General Assets',
                'description' => 'Other hospital assets not classified elsewhere.',
                'asset_class' => 'general',
                'requires_preventive_maintenance' => false,
                'requires_calibration' => false,
                'default_useful_life_years' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            AssetCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}