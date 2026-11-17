<?php

namespace Tests\Unit;

use App\Models\AdministrativeRequest;
use App\Models\User;
use App\Support\AdministrativeRequestAccess as Access;
use Tests\TestCase;

class AdministrationWorkflowSplitTest extends TestCase
{
    private function member(array $permissions): User
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => 11, 'role' => 'nursing_supervisor', 'is_active' => true]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn ($name) => in_array($name, ['administration.view', ...$permissions], true));
        return $user;
    }

    public function test_completion_permission_does_not_allow_assignment(): void
    {
        $user = $this->member(['administration.complete']);
        $item = new AdministrativeRequest(['assigned_to' => 11]);
        $this->assertTrue(Access::allows($user, 'complete', $item));
        $this->assertFalse(Access::allows($user, 'assign', $item));
        $item->assigned_to = 12;
        $this->assertFalse(Access::allows($user, 'complete', $item));
    }

    public function test_assignment_permission_does_not_allow_completion(): void
    {
        $user = $this->member(['administration.assign']);
        $this->assertTrue(Access::allows($user, 'assign'));
        $this->assertFalse(Access::allows($user, 'complete', new AdministrativeRequest(['assigned_to' => 11])));
    }

    public function test_legacy_execute_no_longer_authorizes_workflow_actions(): void
    {
        $user = $this->member(['administration.execute']);
        foreach (['assign', 'complete', 'verify-completion', 'close'] as $action) {
            $this->assertFalse(Access::allows($user, $action, new AdministrativeRequest(['assigned_to' => 11, 'executed_by' => 12])), $action);
        }
    }

    public function test_completion_verifier_cannot_verify_their_own_execution(): void
    {
        $user = $this->member(['administration.verify-completion']);
        $item = new AdministrativeRequest(['executed_by' => 11]);
        $this->assertFalse(Access::allows($user, 'verify-completion', $item));
        $item->executed_by = 12;
        $this->assertTrue(Access::allows($user, 'verify-completion', $item));
        $this->assertFalse(Access::allows($user, 'close', $item));
    }

    public function test_submission_verification_and_closure_are_separate_permissions(): void
    {
        $user = $this->member(['administration.verify']);
        $item = new AdministrativeRequest(['executed_by' => 12]);
        $this->assertTrue(Access::allows($user, 'verify', $item));
        $this->assertFalse(Access::allows($user, 'verify-completion', $item));
        $this->assertFalse(Access::allows($user, 'close', $item));
        $this->assertTrue(Access::allows($this->member(['administration.close']), 'close', $item));
    }
}
