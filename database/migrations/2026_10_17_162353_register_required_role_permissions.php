<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
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
            'patients.view',
            'billing.view',
            'billing.collect',
            'ip-billing.view',
            'ip-billing.advance',
            'ipd.view',
            'ip-billing.manage',
            'nursing.view',
            'nursing.vitals',
            'ipd.discharge-summary.view',
            'billing.order-investigations',
        ];

        foreach ($permissions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'label' => ucwords(str_replace(['.', '-', '_'], ' ', $name)),
                'module' => explode('.', $name)[0],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Preserve permissions and existing role assignments.
    }
};
