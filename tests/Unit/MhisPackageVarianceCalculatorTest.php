<?php

namespace Tests\Unit;

use App\Services\Accounting\MhisPackageVarianceCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MhisPackageVarianceCalculatorTest extends TestCase
{
    public function test_package_exceeds_bill(): void
    {
        $result = (new MhisPackageVarianceCalculator())
            ->calculate('4000.00', '6000.00');

        $this->assertSame('4000.00', $result['bill_transfer']);
        $this->assertSame('2000.00', $result['additional_revenue']);
        $this->assertSame('0.00', $result['contractual_allowance']);
    }

    public function test_bill_exceeds_package(): void
    {
        $result = (new MhisPackageVarianceCalculator())
            ->calculate('10000.00', '8000.00');

        $this->assertSame('8000.00', $result['bill_transfer']);
        $this->assertSame('0.00', $result['additional_revenue']);
        $this->assertSame('2000.00', $result['contractual_allowance']);
    }

    public function test_equal_amounts(): void
    {
        $result = (new MhisPackageVarianceCalculator())
            ->calculate('8000.00', '8000.00');

        $this->assertSame('8000.00', $result['bill_transfer']);
        $this->assertSame('0.00', $result['additional_revenue']);
        $this->assertSame('0.00', $result['contractual_allowance']);
    }

    public function test_invalid_amount_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new MhisPackageVarianceCalculator())
            ->calculate('-100.00', '8000.00');
    }
}
