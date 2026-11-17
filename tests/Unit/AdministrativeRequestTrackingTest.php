<?php

namespace Tests\Unit;

use App\Models\AdministrativeRequest;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdministrativeRequestTrackingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_deadline_is_overdue_only_after_the_target_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00'));
        $item = new AdministrativeRequest(['status' => 'execution_in_progress']);
        $this->assertFalse($item->is_overdue);
        $item->required_by = '2026-11-11';
        $this->assertFalse($item->is_overdue);
        $item->required_by = '2026-11-12';
        $this->assertFalse($item->is_overdue);
        $item->required_by = '2026-11-10';
        $this->assertTrue($item->is_overdue);
    }

    public function test_finished_and_rejected_requests_are_not_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00'));
        foreach (['executed', 'execution_verified', 'closed', 'ms_rejected', 'cancelled'] as $status) {
            $item = new AdministrativeRequest(['status' => $status, 'required_by' => '2026-11-10']);
            $this->assertFalse($item->is_overdue, $status);
        }
    }

    public function test_higher_approval_reference_is_retained(): void
    {
        $item = new AdministrativeRequest(['higher_approval_reference' => 'BOARD/2026/42']);
        $this->assertSame('BOARD/2026/42', $item->higher_approval_reference);
    }

    public function test_pending_owner_identifies_higher_authority_and_closure_review(): void
    {
        $item = new AdministrativeRequest(['status' => 'higher_approval_required', 'higher_authority' => 'CBCNEI']);
        $this->assertSame('CBCNEI', $item->pending_with);
        $item->status = 'executed';
        $this->assertSame('Completion verification', $item->pending_with);
        $item->status = 'execution_verified';
        $this->assertSame('Administrator closure review', $item->pending_with);
        $item->status = 'closed';
        $this->assertSame('—', $item->pending_with);
    }

    public function test_execution_queue_does_not_include_unapproved_or_completed_work(): void
    {
        $this->assertSame(['ms_approved', 'higher_approved', 'execution_in_progress'], AdministrativeRequest::EXECUTION_STATUSES);
    }
}
