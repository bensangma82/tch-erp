<?php

namespace Tests\Feature;

use App\Models\AdministrativeRequest;
use App\Models\User;
use Tests\TestCase;

class AdministrativeRequestAuthorizationTest extends TestCase
{
    private function userWith(array $permissions = [], int $id = 11, string $role = 'doctor', bool $active = true): User
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->forceFill(['id' => $id, 'name' => 'Test user', 'role' => $role, 'is_active' => $active, 'email_verified_at' => now()]);
        $user->shouldReceive('hasPermission')->andReturnUsing(fn (string $permission) => $user->isAdmin() || in_array($permission, $permissions, true));
        $user->shouldReceive('hasAnyPermission')->andReturnUsing(fn (array $names) => $user->isAdmin() || count(array_intersect($names, $permissions)) > 0);
        return $user;
    }

    private function bindRequest(int $owner = 11, int $assignee = 11): void
    {
        $item = new AdministrativeRequest(['created_by' => $owner, 'assigned_to' => $assignee, 'status' => 'draft']);
        $item->id = 123;
        $this->app['router']->bind('administrativeRequest', fn ($value) => $item);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/administration')->assertRedirect(route('login'));
    }

    public function test_user_without_view_permission_cannot_open_module_or_my_work(): void
    {
        $this->actingAs($this->userWith());
        $this->get('/administration')->assertForbidden();
        $this->get('/administration/my-work')->assertForbidden();
    }

    public function test_viewer_cannot_post_workflow_decisions_even_with_a_direct_url(): void
    {
        $this->actingAs($this->userWith(['administration.view']));
        $this->bindRequest();
        foreach (['submit', 'verify', 'ms-approve', 'ms-reject', 'ms-return', 'start-execution', 'reassign', 'mark-executed', 'verify-completion', 'close', 'higher-approval', 'documents'] as $action) {
            $this->post('/administration/requests/123/'.$action)->assertForbidden();
        }
        $this->post('/administration/requests', [])->assertForbidden();
        $this->get('/administration/requests/create')->assertForbidden();
    }

    public function test_executor_cannot_complete_another_users_assignment(): void
    {
        $this->actingAs($this->userWith(['administration.view', 'administration.complete']));
        $this->bindRequest(assignee: 12);
        $this->post('/administration/requests/123/mark-executed')->assertForbidden();
    }

    public function test_creator_cannot_submit_another_users_draft(): void
    {
        $this->actingAs($this->userWith(['administration.view', 'administration.create']));
        $this->bindRequest(owner: 12);
        $this->post('/administration/requests/123/submit')->assertForbidden();
    }

    public function test_approver_passes_authorization_but_cannot_approve_a_draft(): void
    {
        $this->actingAs($this->userWith(['administration.view', 'administration.approve']));
        $this->bindRequest();
        $this->from('/administration')->post('/administration/requests/123/ms-approve')
            ->assertRedirect('/administration')
            ->assertSessionHas('error', 'Only requests pending MS approval can be approved.');
    }
}
