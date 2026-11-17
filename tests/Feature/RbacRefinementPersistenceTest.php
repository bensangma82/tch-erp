<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use App\Services\Rbac\RolePermissionManager;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RbacRefinementPersistenceTest extends TestCase
{
    private bool $transactionStarted = false;
    private User $actor;
    private RolePermissionManager $manager;
    private Permission $first;
    private Permission $second;
    private string $role = 'nursing_supervisor';

    protected function setUp(): void
    {
        parent::setUp();
        // This check happens before any schema migration or transaction write.
        $database = (string) DB::connection()->getDatabaseName();
        if (! app()->environment('testing') || ! ($database === ':memory:' || preg_match('~(?:^|[_/.-])test(?:ing)?(?:$|[_/.-])~i', $database))) {
            throw new \RuntimeException('Use a dedicated testing database named with _test/_testing (or in-memory SQLite). No RBAC test writes were performed.');
        }
        if (! Schema::hasTable('rbac_role_states') || ! Schema::hasTable('rbac_role_audits')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_11_190000_create_rbac_role_audits_and_states.php', '--force' => true])->assertSuccessful();
        }
        DB::beginTransaction();
        $this->transactionStarted = true;
        DB::table('role_permission')->where('role', $this->role)->delete();
        DB::table('rbac_role_states')->where('role', $this->role)->delete();
        DB::table('rbac_role_audits')->where('role', $this->role)->delete();
        $this->actor = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->first = Permission::firstOrCreate(['name' => 'rbac_test.first'], ['label' => 'RBAC test first', 'module' => 'rbac_test']);
        $this->second = Permission::firstOrCreate(['name' => 'rbac_test.second'], ['label' => 'RBAC test second', 'module' => 'rbac_test']);
        $this->manager = app(RolePermissionManager::class);
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function save(array $ids, ?string $reason = 'Approved responsibility change for test user'): bool
    {
        return $this->manager->save($this->actor, $this->role, $ids, $this->manager->snapshot($this->role), $reason);
    }

    public function test_role_change_is_audited_and_direct_user_grants_are_preserved(): void
    {
        $member = User::factory()->create(['role' => $this->role]);
        $member->permissions()->attach($this->second->id);
        $this->save([$this->first->id]);
        $this->assertTrue($member->hasPermission($this->first->name));
        $this->assertTrue($member->hasPermission($this->second->name));
        $this->save([$this->second->id]);
        $this->assertFalse($member->hasPermission($this->first->name));
        $this->assertDatabaseHas('permission_user', ['user_id' => $member->id, 'permission_id' => $this->second->id]);
        $audit = DB::table('rbac_role_audits')->where('role', $this->role)->orderByDesc('id')->first();
        $this->assertSame($this->actor->id, (int) $audit->actor_id);
        $this->assertSame([$this->first->name], json_decode($audit->removed_permissions, true));
        $this->assertSame([$this->second->name], json_decode($audit->added_permissions, true));
    }

    public function test_stale_save_does_not_overwrite_the_latest_grants(): void
    {
        $stale = $this->manager->snapshot($this->role);
        $this->save([$this->first->id]);
        try {
            $this->manager->save($this->actor, $this->role, [$this->second->id], $stale, 'Stale permission change test');
            $this->fail('Stale form should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('snapshot', $exception->errors());
        }
        $this->assertDatabaseHas('role_permission', ['role' => $this->role, 'permission_id' => $this->first->id]);
        $this->assertDatabaseMissing('role_permission', ['role' => $this->role, 'permission_id' => $this->second->id]);
    }

    public function test_changed_grants_require_a_reason_and_rollback_on_failure(): void
    {
        try {
            $this->save([$this->first->id], 'short');
            $this->fail('Short reason should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }
        $this->assertSame(0, DB::table('role_permission')->where('role', $this->role)->count());
        $this->assertSame(0, DB::table('rbac_role_audits')->where('role', $this->role)->count());
    }

    public function test_no_change_does_not_create_a_spurious_audit(): void
    {
        $this->save([$this->first->id]);
        $this->assertFalse($this->save([$this->first->id], null));
        $this->assertSame(1, DB::table('rbac_role_audits')->where('role', $this->role)->count());
    }

    public function test_historical_direct_only_role_grant_is_preserved_without_new_grants(): void
    {
        $protected = Permission::firstOrCreate(['name' => 'billing.investigation-refund.process'], ['label' => 'Process refund', 'module' => 'billing']);
        DB::table('role_permission')->insert(['role' => $this->role, 'permission_id' => $protected->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->save([$this->first->id]);
        $this->assertDatabaseHas('role_permission', ['role' => $this->role, 'permission_id' => $protected->id]);
        $this->expectException(ValidationException::class);
        $this->save([$this->first->id, $protected->id]);
    }

    public function test_administration_dependency_is_enforced_before_writing(): void
    {
        $execute = Permission::firstOrCreate(['name' => 'administration.execute'], ['label' => 'Execute administration', 'module' => 'administration']);
        $this->expectException(ValidationException::class);
        $this->save([$execute->id]);
    }

    public function test_non_admin_cannot_change_role_grants(): void
    {
        $member = User::factory()->create(['role' => $this->role, 'is_active' => true]);
        try {
            $this->manager->save($member, $this->role, [], $this->manager->snapshot($this->role), 'Unauthorized permission change');
            $this->fail('Non-admin should be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->actingAs($member)->get('/admin/role-permissions')->assertForbidden();
    }

    public function test_baseline_seeder_preserves_configured_and_deliberately_empty_roles(): void
    {
        // Nursing is an actual baseline-seeded role, unlike the supervisor role.
        $this->role = 'nursing';
        DB::table('role_permission')->where('role', $this->role)->delete();
        DB::table('rbac_role_states')->where('role', $this->role)->delete();
        $this->save([$this->first->id]);
        $this->seed(RolePermissionSeeder::class);
        $this->assertSame([$this->first->id], DB::table('role_permission')->where('role', $this->role)->pluck('permission_id')->map(fn ($id) => (int) $id)->all());
        $this->save([]);
        $this->seed(RolePermissionSeeder::class);
        $this->assertSame(0, DB::table('role_permission')->where('role', $this->role)->count());
    }

    public function test_baseline_initializes_an_unconfigured_role_once(): void
    {
        DB::table('role_permission')->where('role', 'reception')->delete();
        DB::table('rbac_role_states')->where('role', 'reception')->delete();
        $this->seed(RolePermissionSeeder::class);
        $this->assertGreaterThan(0, DB::table('role_permission')->where('role', 'reception')->count());
        $this->assertDatabaseHas('rbac_role_states', ['role' => 'reception']);
    }

    public function test_editor_identifies_direct_user_grants_and_designation(): void
    {
        $member = User::factory()->create(['role' => $this->role, 'designation' => 'Nursing Superintendent', 'is_active' => true]);
        $member->permissions()->attach($this->second->id);
        $this->actingAs($this->actor)->get('/admin/role-permissions?role='.$this->role.'&user_id='.$member->id)
            ->assertOk()->assertSee('Direct user')->assertSee('Nursing Superintendent')->assertSee('matrix_present', false);
    }

    public function test_matrix_post_requires_the_complete_form_marker(): void
    {
        $this->actingAs($this->actor)->post('/admin/role-permissions', [
            'role' => $this->role, 'snapshot' => $this->manager->snapshot($this->role), 'permissions' => [],
        ])->assertSessionHasErrors('matrix_present');
    }
}
