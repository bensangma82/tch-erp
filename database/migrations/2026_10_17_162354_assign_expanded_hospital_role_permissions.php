<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            'finance_manager' => [
                'finance.dashboard',
                'finance.reports',
                'finance.vouchers.view',
                'finance.vouchers.create',
                'finance.vouchers.post',
                'finance.vouchers.cancel',
                'hospital_expenses.view',
                'hospital_expenses.approve',
                'hospital_expenses.post',
                'hospital_expenses.pay',
            ],
            'cashier' => [
                'patients.view',
                'billing.view',
                'billing.collect',
                'ip-billing.view',
                'ip-billing.advance',
            ],
            'ipd_billing' => [
                'patients.view',
                'ipd.view',
                'ip-billing.view',
                'ip-billing.manage',
                'ip-billing.advance',
                'billing.collect',
            ],
            'nursing_supervisor' => [
                'nursing.view',
                'nursing.vitals',
                'ipd.view',
                'ipd.discharge-summary.view',
                'billing.order-investigations',
            ],
        ];

        DB::transaction(function () use ($roles) {
            foreach ($roles as $role => $names) {
                $permissions = DB::table('permissions')
                    ->whereIn('name', $names)
                    ->pluck('id', 'name');

                if ($permissions->count() !== count($names)) {
                    throw new RuntimeException(
                        'Missing required permissions for '.$role
                    );
                }

                foreach ($permissions as $id) {
                    $exists = DB::table('role_permission')
                        ->where('role', $role)
                        ->where('permission_id', $id)
                        ->exists();

                    if (! $exists) {
                        DB::table('role_permission')->insert([
                            'role' => $role,
                            'permission_id' => $id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // Intentionally preserve permission assignments.
        // Rollback must not remove administrator-approved access.
    }
};
