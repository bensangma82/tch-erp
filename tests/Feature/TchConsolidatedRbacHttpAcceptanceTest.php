<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TchConsolidatedRbacHttpAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'testing') {
            throw new \RuntimeException('RBAC acceptance tests must run only on database testing.');
        }
    }

    public static function roles(): array
    {
        return [
            'nursing' => ['nursing', 'nursing.view', 'nursing.index'],
            'doctor' => ['doctor', 'opd.view', 'opd.index'],
            'laboratory' => ['laboratory', 'laboratory.view', 'laboratory.index'],
            'radiology' => ['radiology', 'radiology.view', 'imaging.index'],
            'pharmacy' => ['pharmacy', 'pharmacy.view', 'pharmacy.dashboard'],
            'billing' => ['billing', 'billing.view', 'billing.index'],
            'ipd' => ['ipd', 'ipd.view', 'ipd.index'],
        ];
    }

    #[DataProvider('roles')]
    public function test_department_role_can_open_its_worklist_but_cannot_open_administration_or_finance(
        string $role,
        string $permission,
        string $allowedRoute
    ): void {
        $p = Permission::firstOrCreate(
            ['name' => $permission],
            ['module' => explode('.', $permission)[0], 'label' => $permission, 'description' => 'RBAC test fixture']
        );
        DB::table('role_permission')->updateOrInsert(
            ['role' => $role, 'permission_id' => $p->id],
            ['updated_at' => now()]
        );
        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);
        $this->get(route($allowedRoute))->assertOk();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('finance.vouchers.index'))->assertForbidden();
    }

    public function test_guest_cannot_access_role_administration(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect();
    }
}
