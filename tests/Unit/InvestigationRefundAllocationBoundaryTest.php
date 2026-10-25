<?php

namespace Tests\Unit;

use App\Services\Investigation\InvestigationRefundAllocationPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InvestigationRefundAllocationBoundaryTest extends TestCase
{
    public function test_zero_overpayment_even_after_cancellation(): void
    {
        self::assertSame(0, (new InvestigationRefundAllocationPolicy())->available(
            230000, 130000, 100000, 0, 100000, 0, 130000, 0
        ));
    }

    public function test_multiple_receipt_allocations_share_one_overpayment_ceiling(): void
    {
        $policy = new InvestigationRefundAllocationPolicy();
        $first = $policy->available(230000, 230000, 100000, 0, 100000, 0, 60000, 0);
        self::assertSame(60000, $first);
        $second = $policy->available(230000, 230000, 100000, $first, 100000, $first, 170000, 0);
        self::assertSame(40000, $second);
        self::assertSame(100000, $first + $second);
    }

    public function test_exact_prior_refund_exhausts_entitlement(): void
    {
        self::assertSame(0, (new InvestigationRefundAllocationPolicy())->available(
            230000, 230000, 100000, 100000, 100000, 100000, 230000, 100000
        ));
    }

    public function test_invalid_negative_previous_refund_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new InvestigationRefundAllocationPolicy())->available(10000, 10000, 2000, -1, 2000, 0, 10000, 0);
    }

    public function test_deterministic_allocation_never_exceeds_receipt_or_cancellation(): void
    {
        $policy = new InvestigationRefundAllocationPolicy();
        for ($paid = 0; $paid <= 230000; $paid += 10000) {
            for ($cancelled = 0; $cancelled <= 100000; $cancelled += 10000) {
                $available = $policy->available(230000, $paid, $cancelled, 0, $cancelled, 0, $paid, 0);
                self::assertGreaterThanOrEqual(0, $available);
                self::assertLessThanOrEqual($paid, $available);
                self::assertLessThanOrEqual($cancelled, $available);
                self::assertSame(max(0, $paid - (230000 - $cancelled)), $available);
            }
        }
    }
}
