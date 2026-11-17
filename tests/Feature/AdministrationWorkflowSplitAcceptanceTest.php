<?php

namespace Tests\Feature;

use App\Models\AdministrativeRequest;
use App\Models\Permission;
use App\Models\User;
use App\Services\Administration\AdministrationWorkflowGrantMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdministrationWorkflowSplitAcceptanceTest extends TestCase
{
    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();
        $database = (string) DB::connection()->getDatabaseName();
        if (! app()->environment('testing') || ! ($database === ':memory:' || preg_match('~(?:^|[_/.-])test(?:ing)?(?:$|[_/.-])~i', $database))) {
            throw new \RuntimeException('Use a dedicated testing database. No workflow test writes were performed.');
        }
        if (! Schema::hasTable('rbac_role_states')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_11_190000_create_rbac_role_audits_and_states.php', '--force' => true])->assertSuccessful();
        }
        if (! Schema::hasColumn('administrative_requests', 'completion_verified_at')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_11_193000_split_administration_execution_permissions.php', '--force' => true])->assertSuccessful();
        }
        DB::beginTransaction();
        $this->transactionStarted = true;
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) DB::rollBack();
        parent::tearDown();
    }

    private function member(array $codes, string $role = 'nursing_supervisor'): User
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);
        // Isolate each fixture from saved role grants in the dedicated test DB.
        DB::table('role_permission')->where('role', $role)->delete();
        $ids = [];
        foreach (['administration.view', ...$codes] as $name) {
            $ids[] = Permission::firstOrCreate(['name' => $name], ['label' => $name, 'module' => 'administration'])->id;
        }
        $user->permissions()->syncWithoutDetaching($ids);
        return $user;
    }

    private function completed(User $executor): AdministrativeRequest
    {
        return AdministrativeRequest::create([
            'request_no' => 'ADM-SPLIT-'.bin2hex(random_bytes(6)), 'request_type' => 'other',
            'title' => 'Workflow split acceptance', 'status' => 'executed', 'priority' => 'normal',
            'created_by' => $executor->id, 'assigned_to' => $executor->id,
            'executed_by' => $executor->id, 'executed_at' => now(),
        ]);
    }

    public function test_completion_must_be_verified_before_closure_and_evidence_is_retained(): void
    {
        $executor = $this->member(['administration.complete']);
        $verifier = $this->member(['administration.verify-completion'], 'doctor');
        $closer = $this->member(['administration.close'], 'hospital_administrator');
        $item = $this->completed($executor);
        $this->actingAs($closer)->post('/administration/requests/'.$item->id.'/close')->assertSessionHasErrors('status');
        $this->assertSame('executed', $item->fresh()->status);
        $this->actingAs($verifier)->post('/administration/requests/'.$item->id.'/verify-completion', [
            'completion_verification_remarks' => 'Completion evidence checked against the approved request.',
        ])->assertRedirect();
        $this->assertSame('execution_verified', $item->fresh()->status);
        $this->assertSame($verifier->id, (int) $item->fresh()->completion_verified_by);
        $this->actingAs($closer)->post('/administration/requests/'.$item->id.'/close', ['closure_remarks' => 'Verified work accepted.'])->assertRedirect();
        $item->refresh();
        $this->assertSame('closed', $item->status);
        $this->assertSame($verifier->id, (int) $item->completion_verified_by);
        $this->assertNotNull($item->completion_verified_at);
        $this->assertSame('Completion evidence checked against the approved request.', $item->completion_verification_remarks);
    }

    public function test_executor_cannot_verify_own_completion_even_if_given_the_permission(): void
    {
        $executor = $this->member(['administration.complete', 'administration.verify-completion']);
        $item = $this->completed($executor);
        $this->actingAs($executor)->post('/administration/requests/'.$item->id.'/verify-completion', ['completion_verification_remarks' => 'Self verification attempt'])->assertForbidden();
        $this->assertSame('executed', $item->fresh()->status);
    }

    public function test_completion_only_user_cannot_assign_or_close(): void
    {
        $executor = $this->member(['administration.complete']);
        $item = $this->completed($executor);
        foreach (['start-execution', 'close'] as $action) {
            $this->actingAs($executor)->post('/administration/requests/'.$item->id.'/'.$action)->assertForbidden();
        }
    }

    public function test_legacy_execute_alone_does_not_allow_completion(): void
    {
        $executor = $this->member(['administration.execute']);
        $item = $this->completed($executor);
        $item->update(['status' => 'execution_in_progress']);
        $this->actingAs($executor)->post('/administration/requests/'.$item->id.'/mark-executed')->assertForbidden();
    }

    public function test_mapping_gives_supervisor_completion_and_coordinator_assignment(): void
    {
        $legacy = Permission::firstOrCreate(['name' => 'administration.execute'], ['label' => 'Legacy execute', 'module' => 'administration']);
        foreach (['nursing_supervisor', 'hospital_administrator'] as $role) {
            DB::table('role_permission')->where('role', $role)->delete();
            DB::table('role_permission')->insert(['role' => $role, 'permission_id' => $legacy->id, 'created_at' => now(), 'updated_at' => now()]);
        }
        app(AdministrationWorkflowGrantMapper::class)->apply();
        $grantNames = fn ($role) => DB::table('role_permission')->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')->where('role', $role)->pluck('permissions.name')->all();
        $this->assertContains('administration.complete', $grantNames('nursing_supervisor'));
        $this->assertNotContains('administration.assign', $grantNames('nursing_supervisor'));
        $this->assertContains('administration.assign', $grantNames('hospital_administrator'));
        $this->assertDatabaseHas('rbac_role_audits', ['role' => 'nursing_supervisor', 'actor_name' => 'Administration workflow migration']);
    }

    public function test_direct_legacy_verification_is_mapped_and_recorded_once(): void
    {
        $user = $this->member(['administration.verify'], 'doctor');
        app(AdministrationWorkflowGrantMapper::class)->apply();
        $this->assertTrue($user->hasPermission('administration.verify-completion'));
        $this->assertTrue($user->hasPermission('administration.close'));
        $count = DB::table('administration_workflow_grant_mappings')->where('target_type', 'user')->where('target_key', (string) $user->id)->count();
        app(AdministrationWorkflowGrantMapper::class)->apply();
        $this->assertSame($count, DB::table('administration_workflow_grant_mappings')->where('target_type', 'user')->where('target_key', (string) $user->id)->count());
        $this->assertSame(1, $count);
    }
}
