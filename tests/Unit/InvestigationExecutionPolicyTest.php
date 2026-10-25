<?php

namespace Tests\Unit;

use App\Services\Investigation\InvestigationExecutionPolicy;
use PHPUnit\Framework\TestCase;

final class InvestigationExecutionPolicyTest extends TestCase
{
    public function test_uncollected_order_can_use_supervisor_decision(): void
    {
        self::assertFalse((new InvestigationExecutionPolicy())->requiresMs('ordered', false, false, false));
    }

    public function test_collected_sample_requires_ms(): void
    {
        self::assertTrue((new InvestigationExecutionPolicy())->requiresMs('ordered', true, false, false));
    }

    public function test_processing_and_completed_require_ms(): void
    {
        $policy = new InvestigationExecutionPolicy();
        self::assertTrue($policy->requiresMs('in_process', false, false, false));
        self::assertTrue($policy->requiresMs('completed', false, false, false));
    }

    public function test_result_requires_ms_even_if_status_is_ordered(): void
    {
        self::assertTrue((new InvestigationExecutionPolicy())->requiresMs('ordered', false, true, false));
    }

    public function test_prior_ms_requirement_cannot_be_downgraded(): void
    {
        self::assertTrue((new InvestigationExecutionPolicy())->requiresMs('ordered', false, false, true));
    }

    public function test_unknown_status_fails_closed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new InvestigationExecutionPolicy())->requiresMs('cancelled', false, false, false);
    }
}
