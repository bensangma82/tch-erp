<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class HospitalExpensePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Hospital Expense Management Permissions
        |--------------------------------------------------------------------------
        |
        | Register permissions using TCH ERP's custom RBAC system.
        | Do not automatically assign permissions to users or roles.
        |
        */

        $permissions = [
            [
                'name' => 'hospital_expenses.view',
                'label' => 'View Hospital Expenses',
                'module' => 'finance',
                'description' => 'View clinical and non-clinical hospital expenses.',
            ],
            [
                'name' => 'hospital_expenses.create',
                'label' => 'Create Hospital Expenses',
                'module' => 'finance',
                'description' => 'Register new hospital expense drafts.',
            ],
            [
                'name' => 'hospital_expenses.edit',
                'label' => 'Edit Hospital Expenses',
                'module' => 'finance',
                'description' => 'Edit hospital expenses while in draft status.',
            ],
            [
                'name' => 'hospital_expenses.submit',
                'label' => 'Submit Hospital Expenses',
                'module' => 'finance',
                'description' => 'Submit hospital expenses for approval.',
            ],
            [
                'name' => 'hospital_expenses.approve',
                'label' => 'Approve Hospital Expenses',
                'module' => 'finance',
                'description' => 'Approve submitted hospital expenses.',
            ],
            [
                'name' => 'hospital_expenses.post',
                'label' => 'Post Hospital Expenses',
                'module' => 'finance',
                'description' => 'Post approved hospital expenses to the General Ledger.',
            ],
            [
                'name' => 'hospital_expenses.pay',
                'label' => 'Pay Hospital Expenses',
                'module' => 'finance',
                'description' => 'Record authorized settlements against posted hospital expenses.',
            ],
        ];

        foreach ($permissions as $data) {
            Permission::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
