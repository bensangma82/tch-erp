<?php

namespace Tests\Unit;

use App\Services\Investigation\InvestigationRefundAllocationPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InvestigationRefundAllocationPolicyTest extends TestCase
{
    private InvestigationRefundAllocationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new InvestigationRefundAllocationPolicy();
    }

    public function test_fully_paid_invoice_allows_item_refund(): void
    {
        self::assertSame(100000, $this->policy->available(230000,230000,100000,0,100000,0,230000,0));
    }

    public function test_partial_receipts_refund_only_excess_over_net_bill(): void
    {
        self::assertSame(20000, $this->policy->available(230000,150000,100000,0,100000,0,150000,0));
    }

    public function test_receipts_below_net_bill_create_no_refund(): void
    {
        self::assertSame(0, $this->policy->available(230000,100000,100000,0,100000,0,100000,0));
    }

    public function test_only_the_overpaid_portion_is_refundable(): void
    {
        self::assertSame(70000, $this->policy->available(230000,200000,100000,0,100000,0,200000,0));
    }

    public function test_split_receipt_limits_refund_to_selected_payment(): void
    {
        self::assertSame(40000, $this->policy->available(230000,230000,100000,0,100000,0,40000,0));
    }

    public function test_prior_refund_reduces_remaining_entitlement(): void
    {
        self::assertSame(30000, $this->policy->available(230000,230000,100000,70000,100000,70000,150000,70000));
    }

    public function test_multiple_cancellations_cannot_over_refund(): void
    {
        self::assertSame(20000, $this->policy->available(230000,230000,120000,100000,20000,0,100000,0));
    }

    public function test_invalid_totals_fail_closed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->policy->available(230000,250000,100000,0,100000,0,250000,0);
    }
}
