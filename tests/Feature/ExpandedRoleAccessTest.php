<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExpandedRoleAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function loginAs(string $role): void
    {
        $permissions = [
            'cashier' => ['ip-billing.view'],
            'ipd_billing' => ['ip-billing.view'],
            'nursing_supervisor' => ['nursing.view'],
        ];

        foreach ($permissions[$role] ?? [] as $name) {
            $id = \App\Models\Permission::query()
                ->where('name', $name)
                ->value('id');

            if (! $id) {
                $id = \Illuminate\Support\Facades\DB::table('permissions')
                    ->insertGetId([
                        'module' => str_starts_with($name, 'nursing.')
                            ? 'nursing' : 'billing',
                        'name' => $name,
                        'label' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            \Illuminate\Support\Facades\DB::table('role_permission')
                ->insertOrIgnore([
                    'role' => $role,
                    'permission_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $this->actingAs(
            User::factory()->create(['role' => $role])
        );
    }

    public function test_cashier_cannot_access_finance_vouchers(): void
    {
        $this->loginAs('cashier');

        $this->get(route('finance.vouchers.index'))
            ->assertForbidden();
    }

    public function test_ipd_billing_cannot_access_refund_route(): void
    {
        $this->loginAs('ipd_billing');

        $patient = \App\Models\Patient::create([
            'uhid' => 'RBAC-REFUND-TEST',
            'first_name' => 'RBAC',
            'sex' => 'male',
        ]);

        $admission = \App\Models\Admission::create([
            'admission_no' => 'RBAC-REFUND-TEST',
            'patient_id' => $patient->id,
            'admitted_at' => now(),
        ]);

        $this->post(
            route('ip-billing.refunds.store', $admission),
            []
        )->assertForbidden();
    }

    public function test_nursing_supervisor_cannot_access_finance(): void
    {
        $this->loginAs('nursing_supervisor');

        $this->get(route('finance.vouchers.index'))
            ->assertForbidden();
    }

    public function test_finance_manager_cannot_manage_system_users(): void
    {
        $this->loginAs('finance_manager');

        $this->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_finance_manager_can_access_finance(): void
    {
        $this->loginAs('finance_manager');
        $this->get(route('finance.vouchers.index'))->assertOk();
    }

    public function test_cashier_can_access_ipd_billing(): void
    {
        $this->loginAs('cashier');
        $this->get(route('ip-billing.index'))->assertOk();
    }


    public function test_ipd_billing_can_access_billing(): void
    {
        $this->loginAs('ipd_billing');
        $this->get(route('ip-billing.index'))->assertOk();
    }

    public function test_nursing_supervisor_can_access_nursing(): void
    {
        $this->loginAs('nursing_supervisor');
        $this->get(route('nursing.index'))->assertOk();
    }

}
