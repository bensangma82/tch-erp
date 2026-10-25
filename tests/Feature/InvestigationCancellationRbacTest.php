<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InvestigationCancellationRbacTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ordinary_user_cannot_access_approval_queue(): void
    {
        $user = User::factory()->create(['role' => 'reception']);

        $this->actingAs($user)
            ->get(route('billing.investigation-cancellations.queue'))
            ->assertForbidden();
    }

    public function test_request_only_user_cannot_access_approval_queue(): void
    {
        $user = User::factory()->create(['role' => 'reception']);

        $permission = \App\Models\Permission::where(
            'name',
            'billing.investigation-cancellation.request'
        )->firstOrFail();

        $user->permissions()->attach($permission->id);

        $this->actingAs($user)
            ->get(route('billing.investigation-cancellations.queue'))
            ->assertForbidden();
    }
}
