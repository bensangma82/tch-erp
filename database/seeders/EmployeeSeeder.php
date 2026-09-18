<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            [
                'employee_code' => 'DOC001',
                'title' => 'Dr',
                'first_name' => 'Benjamin',
                'middle_name' => 'S',
                'last_name' => 'Sangma',
                'designation' => 'Consultant',
                'department_code' => 'NEPH',
                'qualification' => 'MD, DM',
                'speciality' => 'Nephrology',
            ],

            [
                'employee_code' => 'DOC002',
                'title' => 'Dr',
                'first_name' => 'Siddhart',
                'middle_name' => null,
                'last_name' => 'Sangma',
                'designation' => 'Consultant',
                'department_code' => 'SURG',
                'qualification' => 'MS',
                'speciality' => 'General Surgery',
            ],

            [
                'employee_code' => 'DOC003',
                'title' => 'Dr',
                'first_name' => 'Editha',
                'middle_name' => 'W',
                'last_name' => 'Momin',
                'designation' => 'Consultant',
                'department_code' => 'PAED',
                'qualification' => 'MD',
                'speciality' => 'Paediatrics',
            ],

            [
                'employee_code' => 'DOC004',
                'title' => 'Dr',
                'first_name' => 'Dila',
                'middle_name' => null,
                'last_name' => 'Sangma',
                'designation' => 'Consultant',
                'department_code' => 'OBG',
                'qualification' => 'MD',
                'speciality' => 'Obstetrics & Gynaecology',
            ],

            [
                'employee_code' => 'DOC005',
                'title' => 'Dr',
                'first_name' => 'Sengchi',
                'middle_name' => 'G',
                'last_name' => 'Momin',
                'designation' => 'Consultant',
                'department_code' => 'MED',
                'qualification' => null,
                'speciality' => 'Diabetology & Geriatric Medicine',
            ],

            [
                'employee_code' => 'DOC006',
                'title' => 'Dr',
                'first_name' => 'Tengsime',
                'middle_name' => null,
                'last_name' => 'Sangma',
                'designation' => 'Consultant',
                'department_code' => 'ANAES',
                'qualification' => 'MD',
                'speciality' => 'Anaesthesia',
            ],
        ];

        foreach ($doctors as $doctor) {

            $department = Department::where(
                'code',
                $doctor['department_code']
            )->first();

            Employee::updateOrCreate(
                [
                    'employee_code' => $doctor['employee_code'],
                ],
                [
                    'title' => $doctor['title'],
                    'first_name' => $doctor['first_name'],
                    'middle_name' => $doctor['middle_name'],
                    'last_name' => $doctor['last_name'],
                    'designation' => $doctor['designation'],
                    'department_id' => $department?->id,
                    'employee_type' => 'permanent',
                    'qualification' => $doctor['qualification'],
                    'speciality' => $doctor['speciality'],
                    'is_doctor' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}