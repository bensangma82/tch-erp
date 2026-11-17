<?php

namespace Tests\Feature;

use App\Models\AdministrativeRequest;
use App\Models\Permission;
use App\Models\User;
use App\Services\Administration\AdministrativeWorkAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdministrativeNamedAssignmentTest extends TestCase
{
    private bool $transactionStarted = false;
    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $database = (string) DB::connection()->getDatabaseName();
        if (! app()->environment('testing') || ! ($database === ':memory:' || preg_match('~(?:^|[_/.-])test(?:ing)?(?:$|[_/.-])~i', $database))) {
            throw new \RuntimeException('Use a dedicated testing database. No named-assignment test writes were performed.');
        }
        // Load this module's prerequisites only after the dedicated-test-DB guard.
        if (! Schema::hasColumn('administrative_requests', 'required_by')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_11_180000_add_required_by_to_administrative_requests.php', '--force' => true])->assertSuccessful();
        }
        if (! Schema::hasColumn('administrative_requests', 'execution_category')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_18_001141_add_execution_assignment_fields_to_administrative_requests_table.php', '--force' => true])->assertSuccessful();
        }
        if (! Schema::hasColumn('administrative_requests', 'higher_approval_reference')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_18_010648_add_higher_approval_reference_to_administrative_requests_table.php', '--force' => true])->assertSuccessful();
        }
        if (! Schema::hasTable('administrative_request_documents')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_18_003005_create_administrative_request_documents_table.php', '--force' => true])->assertSuccessful();
        }
        if (! Schema::hasColumn('administrative_request_documents', 'storage_disk')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_18_010059_add_storage_disk_to_administrative_request_documents_table.php', '--force' => true])->assertSuccessful();
        }
        if (! Schema::hasTable('rbac_role_states')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_11_190000_create_rbac_role_audits_and_states.php', '--force' => true])->assertSuccessful();
        }
        if (! Schema::hasColumn('administrative_requests', 'completion_verified_at')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_11_193000_split_administration_execution_permissions.php', '--force' => true])->assertSuccessful();
        }
        if (! Schema::hasTable('administrative_request_assignments')) {
            $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_11_230000_create_administrative_request_assignments.php', '--force' => true])->assertSuccessful();
        }
        DB::beginTransaction();
        $this->transactionStarted = true;
        $this->actor = $this->member(['administration.view', 'administration.assign'], 'hospital_administrator');
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) DB::rollBack();
        parent::tearDown();
    }

    private function member(array $names, string $role = 'laboratory', bool $active = true): User
    {
        $user = User::factory()->create(['role' => $role, 'designation' => null, 'is_active' => $active]);
        DB::table('role_permission')->where('role', $role)->delete();
        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['label' => $name, 'module' => 'administration']);
            $user->permissions()->syncWithoutDetaching([$permission->id]);
        }
        return $user;
    }

    private function requestItem(): AdministrativeRequest
    {
        return AdministrativeRequest::create([
            'request_no' => 'ADM-NAMED-'.bin2hex(random_bytes(6)), 'request_type' => 'other',
            'title' => 'Named assignment test', 'status' => 'ms_approved', 'priority' => 'normal',
            'created_by' => $this->actor->id, 'ms_decision' => 'approved', 'approved_amount' => '1200.00',
            'required_by' => today()->addDays(7)->toDateString(),
        ]);
    }

    private function start(AdministrativeRequest $item, User $user): void
    {
        $this->actingAs($this->actor)->post('/administration/requests/'.$item->id.'/start-execution', [
            'assigned_to' => $user->id, 'execution_category' => 'laboratory', 'execution_remarks' => 'Initial instructions',
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    private function handoverData(AdministrativeRequest $item, User $user): array
    {
        $item->refresh();
        return [
            'assigned_to' => $user->id, 'execution_category' => 'pharmacy',
            'expected_assignee' => $item->assigned_to,
            'expected_assignment_id' => (int) (DB::table('administrative_request_assignments')->where('administrative_request_id', $item->id)->max('id') ?? 0),
            'reassignment_reason' => 'Handover during planned staff leave',
            'execution_remarks' => 'Updated handover instructions',
        ];
    }

    public function test_assigns_a_named_user_without_a_fixed_designation(): void
    {
        $user = $this->member(['administration.view', 'administration.complete']);
        $item = $this->requestItem();
        $this->start($item, $user);
        $item->refresh();
        $this->assertSame($user->id, (int) $item->assigned_to);
        $this->assertSame('execution_in_progress', $item->status);
        $this->assertSame('laboratory', $item->execution_category);
        $this->assertSame('1200.00', $item->approved_amount);
        $this->assertFalse($user->hasPermission('administration.approve'));
        $this->assertDatabaseHas('administrative_request_assignments', ['administrative_request_id' => $item->id, 'assigned_to_id' => $user->id, 'event' => 'assigned']);
    }

    public function test_eligibility_combines_role_and_direct_grants(): void
    {
        $user = $this->member(['administration.view'], 'stores');
        $complete = Permission::where('name', 'administration.complete')->firstOrFail();
        DB::table('role_permission')->insert(['role' => 'stores', 'permission_id' => $complete->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->assertTrue(app(AdministrativeWorkAssignment::class)->eligibleUsers()->whereKey($user->id)->exists());
    }

    public function test_missing_view_or_completion_grant_is_rejected_server_side(): void
    {
        foreach ([['administration.view'], ['administration.complete']] as $names) {
            $user = $this->member($names);
            $item = $this->requestItem();
            $this->actingAs($this->actor)->post('/administration/requests/'.$item->id.'/start-execution', ['assigned_to' => $user->id, 'execution_category' => 'laboratory'])
                ->assertSessionHasErrors('assigned_to');
            $this->assertSame('ms_approved', $item->fresh()->status);
        }
    }

    public function test_inactive_admin_is_not_an_eligible_target(): void
    {
        $user = $this->member([], 'admin', false);
        $this->assertFalse(app(AdministrativeWorkAssignment::class)->eligibleUsers()->whereKey($user->id)->exists());
        $item = $this->requestItem();
        $this->actingAs($this->actor)->post('/administration/requests/'.$item->id.'/start-execution', ['assigned_to' => $user->id, 'execution_category' => 'it'])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_executor_cannot_assign_or_reassign_work(): void
    {
        $user = $this->member(['administration.view', 'administration.complete']);
        $item = $this->requestItem();
        foreach (['start-execution', 'reassign'] as $action) {
            $this->actingAs($user)->post('/administration/requests/'.$item->id.'/'.$action)->assertForbidden();
        }
    }

    public function test_reassignment_records_handover_and_revokes_previous_assignee_completion(): void
    {
        $first = $this->member(['administration.view', 'administration.complete']);
        $second = $this->member(['administration.view', 'administration.complete'], 'pharmacy');
        $item = $this->requestItem();
        $this->start($item, $first);
        $started = $item->fresh()->execution_started_at->toDateTimeString();
        $due = $item->fresh()->required_by->toDateString();
        $this->actingAs($this->actor)->post('/administration/requests/'.$item->id.'/reassign', $this->handoverData($item, $second))
            ->assertRedirect()->assertSessionHasNoErrors();
        $item->refresh();
        $this->assertSame($second->id, (int) $item->assigned_to);
        $this->assertSame($started, $item->execution_started_at->toDateTimeString());
        $this->assertSame($due, $item->required_by->toDateString());
        $this->assertDatabaseHas('administrative_request_assignments', ['administrative_request_id' => $item->id, 'previous_assignee_id' => $first->id, 'assigned_to_id' => $second->id, 'reason' => 'Handover during planned staff leave']);
        $this->actingAs($first)->post('/administration/requests/'.$item->id.'/mark-executed')->assertForbidden();
        $this->actingAs($second)->post('/administration/requests/'.$item->id.'/mark-executed', ['execution_remarks' => 'Handover task completed'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('executed', $item->fresh()->status);
    }

    public function test_stale_reassignment_does_not_overwrite_a_newer_assignee(): void
    {
        $first = $this->member(['administration.view', 'administration.complete']);
        $second = $this->member(['administration.view', 'administration.complete'], 'pharmacy');
        $item = $this->requestItem();
        $this->start($item, $first);
        $data = $this->handoverData($item, $second);
        $this->actingAs($this->actor)->post('/administration/requests/'.$item->id.'/reassign', $data)->assertSessionHasNoErrors();
        $data['assigned_to'] = $first->id;
        $this->post('/administration/requests/'.$item->id.'/reassign', $data)->assertSessionHasErrors('assigned_to');
        $this->assertSame($second->id, (int) $item->fresh()->assigned_to);
        $this->assertSame(2, DB::table('administrative_request_assignments')->where('administrative_request_id', $item->id)->count());
    }

    public function test_completed_or_closed_work_cannot_be_reassigned(): void
    {
        $first = $this->member(['administration.view', 'administration.complete']);
        $second = $this->member(['administration.view', 'administration.complete'], 'pharmacy');
        $item = $this->requestItem();
        $this->start($item, $first);
        foreach (['executed', 'execution_verified', 'closed'] as $status) {
            $item->update(['status' => $status]);
            $this->actingAs($this->actor)->post('/administration/requests/'.$item->id.'/reassign', $this->handoverData($item, $second))->assertSessionHasErrors('status');
        }
        $this->assertSame(1, DB::table('administrative_request_assignments')->where('administrative_request_id', $item->id)->count());
    }

    public function test_same_assignee_and_missing_reason_do_not_create_history(): void
    {
        $user = $this->member(['administration.view', 'administration.complete']);
        $item = $this->requestItem();
        $this->start($item, $user);
        $data = $this->handoverData($item, $user);
        $this->actingAs($this->actor)->post('/administration/requests/'.$item->id.'/reassign', $data)->assertSessionHasErrors('assigned_to');
        unset($data['reassignment_reason']);
        $this->post('/administration/requests/'.$item->id.'/reassign', $data)->assertSessionHasErrors('reassignment_reason');
        $this->assertSame(1, DB::table('administrative_request_assignments')->where('administrative_request_id', $item->id)->count());
    }

    public function test_historical_in_progress_request_can_be_reassigned_without_invented_history(): void
    {
        $first = $this->member(['administration.view', 'administration.complete']);
        $second = $this->member(['administration.view', 'administration.complete'], 'pharmacy');
        $item = $this->requestItem();
        $item->update(['status' => 'execution_in_progress', 'assigned_to' => $first->id, 'execution_started_at' => now()]);
        $this->actingAs($this->actor)->post('/administration/requests/'.$item->id.'/reassign', $this->handoverData($item, $second))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('administrative_request_assignments', ['administrative_request_id' => $item->id, 'event' => 'reassigned', 'previous_assignee_id' => $first->id]);
        $this->assertSame(1, DB::table('administrative_request_assignments')->where('administrative_request_id', $item->id)->count());
    }

    public function test_assignment_page_shows_named_eligible_users_and_expanded_categories(): void
    {
        $user = $this->member(['administration.view', 'administration.complete']);
        $item = $this->requestItem();
        $this->actingAs($this->actor)->get('/administration/requests/'.$item->id)->assertOk()
            ->assertSee($user->name)->assertSee('Projects / Construction')->assertSee('Responsible person');
    }
}
