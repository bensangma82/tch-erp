<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [

            [
                'code' => 'MED',
                'name' => 'Internal Medicine',
                'type' => 'clinical',
            ],

            [
                'code' => 'NEPH',
                'name' => 'Nephrology',
                'type' => 'clinical',
            ],

            [
                'code' => 'SURG',
                'name' => 'General Surgery',
                'type' => 'clinical',
            ],

            [
                'code' => 'OBG',
                'name' => 'Obstetrics & Gynaecology',
                'type' => 'clinical',
            ],

            [
                'code' => 'PAED',
                'name' => 'Paediatrics',
                'type' => 'clinical',
            ],

            [
                'code' => 'ANAES',
                'name' => 'Anaesthesia',
                'type' => 'clinical',
            ],

            [
                'code' => 'DENT',
                'name' => 'Dental',
                'type' => 'clinical',
            ],

            [
                'code' => 'EMER',
                'name' => 'Emergency',
                'type' => 'clinical',
            ],

            [
                'code' => 'ICU',
                'name' => 'Intensive Care Unit',
                'type' => 'clinical',
            ],

            [
                'code' => 'DIAL',
                'name' => 'Dialysis',
                'type' => 'clinical',
            ],

            [
                'code' => 'LAB',
                'name' => 'Laboratory',
                'type' => 'diagnostic',
            ],

            [
                'code' => 'RAD',
                'name' => 'Radiology',
                'type' => 'diagnostic',
            ],

            [
                'code' => 'PHARM',
                'name' => 'Pharmacy',
                'type' => 'support',
            ],

            [
                'code' => 'FIN',
                'name' => 'Finance & Accounts',
                'type' => 'administrative',
            ],

            [
                'code' => 'HR',
                'name' => 'Human Resources',
                'type' => 'administrative',
            ],

            [
                'code' => 'ADMIN',
                'name' => 'Administration',
                'type' => 'administrative',
            ],

        ];

        foreach ($departments as $department) {

            Department::updateOrCreate(
                [
                    'code' => $department['code'],
                ],
                [
                    'name' => $department['name'],
                    'type' => $department['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}