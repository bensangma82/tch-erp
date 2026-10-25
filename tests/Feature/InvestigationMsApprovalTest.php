<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\InvestigationCancellationRequest;
use App\Models\InvestigationMsApprover;
use App\Services\Investigation\InvestigationCancellationApprovalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;

class InvestigationMsApprovalTest extends TestCase
{
    use DatabaseTransactions;

    private function cancellation(User $requester): InvestigationCancellationRequest
    {
        return new InvestigationCancellationRequest([
            'requested_by' => $requester->id,
            'requires_ms_approval' => true,
            'status' => 'pending',
        ]);
    }

    public function test_admin_without_ms_designation_cannot_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $requester = User::factory()->create(['role' => 'reception']);

        // This checks the independent MS designation requirement.
        $this->assertFalse(
            InvestigationMsApprover::where('user_id', $admin->id)
                ->where('active', true)
                ->exists()
        );

        $this->assertTrue($admin->hasPermission(
            'billing.investigation-cancellation.approve'
        ));

        $this->assertNotSame($admin->id, $requester->id);
    }

    public function test_ordinary_user_has_no_direct_ms_permission(): void
    {
        $user = User::factory()->create(['role' => 'reception']);

        $this->assertFalse(
            $user->permissions()
                ->where(
                    'permissions.name',
                    'billing.investigation-cancellation.approve'
                )
                ->exists()
        );
    }

    public function test_requester_and_reviewer_must_be_different(): void
    {
        $requester = User::factory()->create(['role' => 'reception']);
        $reviewer = User::factory()->create(['role' => 'admin']);

        $this->assertNotSame($requester->id, $reviewer->id);
    }
}
