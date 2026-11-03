<?php

namespace Tests\Unit;

use App\Support\MhisLegacySettlementAmounts as Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MhisLegacySettlementAmountsTest extends TestCase
{
    public function test_valid_money_and_gross_split(): void
    {
        $net = Money::paise('35598.06');
        $tds = Money::paise('3955.34');
        $deduction = Money::paise('0');
        $this->assertSame('39553.40', Money::rupees($net + $tds + $deduction));
        $this->assertSame(1, Money::paise('0.01'));
        $this->assertSame(120, Money::paise('1.2'));
    }

    public function test_invalid_amount_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::paise('-1.00');
    }

    public function test_excess_precision_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::paise('1.234');
    }

    public function test_excessive_gross_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::rupees(1000000000000000000);
    }
}
