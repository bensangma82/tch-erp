<?php

namespace Tests\Unit;

use App\Models\AdministrativeRequest;
use App\Models\User;
use App\Support\AdministrativeRequestAccess as Access;
use Tests\TestCase;

class AdministrativeRequestAccessTest extends TestCase
{
    private function userWith(array $permissions = [], int $id = 11, string $role = 'doctor', bool $active = true): User
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => $id, 'name' => 'Test user', 'role' => $role, 'is_active' => $active, 'email_verified_at' => now()]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn (string $permission) => $user->isAdmin() || in_array($permission, $permissions, true));
        $user->shouldReceive('hasAnyPermission')->andReturnUsing(fn (array $names) => $user->isAdmin() || count(array_intersect($names, $permissions)) > 0);
        return $user;
    }

    public function test_guest_inactive_and_missing_module_access_are_denied(): void
    {
        $this->assertFalse(Access::allows(null, 'view'));
        $this->assertFalse(Access::allows($this->userWith(['administration.view'], active: false), 'view'));
        $this->assertFalse(Access::allows($this->userWith(['administration.approve']), 'approve'));
    }

    public function test_view_only_user_cannot_perform_any_workflow_action(): void
    {
        $user = $this->userWith(['administration.view']);
        $item = new AdministrativeRequest(['created_by' => 11, 'assigned_to' => 11, 'status' => 'submitted']);
        $this->assertTrue(Access::allows($user, 'view'));
        foreach (['create', 'submit', 'verify', 'approve', 'higher', 'assign', 'complete', 'close', 'upload'] as $action) {
            $this->assertFalse(Access::allows($user, $action, $item), $action);
        }
    }

    public function test_creator_can_submit_own_request_only(): void
    {
        $user = $this->userWith(['administration.view', 'administration.create']);
        $item = new AdministrativeRequest(['created_by' => 11]);
        $this->assertTrue(Access::allows($user, 'submit', $item));
        $item->created_by = 12;
        $this->assertFalse(Access::allows($user, 'submit', $item));
        $this->assertFalse(Access::allows($user, 'submit'));
    }

    public function test_submission_verifier_does_not_automatically_close_or_approve(): void
    {
        $user = $this->userWith(['administration.view', 'administration.verify']);
        $this->assertTrue(Access::allows($user, 'verify'));
        $this->assertFalse(Access::allows($user, 'close'));
        $this->assertFalse(Access::allows($user, 'approve'));
    }

    public function test_approver_can_decide_and_record_higher_approval(): void
    {
        $user = $this->userWith(['administration.view', 'administration.approve']);
        $this->assertTrue(Access::allows($user, 'approve'));
        $this->assertTrue(Access::allows($user, 'higher'));
        $this->assertFalse(Access::allows($user, 'assign'));
    }

    public function test_execution_requires_permission_and_assigned_user_for_completion(): void
    {
        $user = $this->userWith(['administration.view', 'administration.complete']);
        $item = new AdministrativeRequest(['assigned_to' => 11]);
        $this->assertFalse(Access::allows($user, 'assign'));
        $this->assertTrue(Access::allows($user, 'complete', $item));
        $this->assertFalse(Access::allows($user, 'close', $item));
        $item->assigned_to = 12;
        $this->assertFalse(Access::allows($user, 'complete', $item));
        $item->assigned_to = 11;
        $this->assertFalse(Access::allows($this->userWith(['administration.view']), 'complete', $item));
    }

    public function test_documents_require_a_workflow_responsibility_and_open_record(): void
    {
        $user = $this->userWith(['administration.view', 'administration.complete']);
        $item = new AdministrativeRequest(['assigned_to' => 11, 'status' => 'execution_in_progress']);
        $this->assertTrue(Access::allows($user, 'upload', $item));
        $item->assigned_to = 12;
        $this->assertFalse(Access::allows($user, 'upload', $item));
        $item->assigned_to = 11;
        $item->status = 'closed';
        $this->assertFalse(Access::allows($user, 'upload', $item));
        $this->assertFalse(Access::allows($user, 'upload'));
    }

    public function test_existing_admin_override_is_preserved_without_allowing_unknown_actions(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $user = $this->userWith(role: $role);
            $item = new AdministrativeRequest(['created_by' => 12, 'assigned_to' => 12, 'status' => 'submitted']);
            foreach (['view', 'create', 'submit', 'verify', 'approve', 'higher', 'assign', 'complete', 'close', 'upload'] as $action) {
                $this->assertTrue(Access::allows($user, $action, $item), $role.':'.$action);
            }
            $this->assertFalse(Access::allows($user, 'undefined-action', $item));
        }
    }
}
