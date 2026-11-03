<?php

namespace Tests\Unit;

use App\Services\Accounting\MhisLegacySettlementJournalBlueprint as Blueprint;
use DomainException;
use PHPUnit\Framework\TestCase;

class MhisLegacySettlementJournalBlueprintTest extends TestCase
{
    public function test_balanced_entries_for_net_tds_and_deduction(): void
    {
        $plan = Blueprint::build('verified', '850.00', '100.00', '50.00', '1000.00', true, true);
        $this->assertSame('1000.00', $plan['total_debit']);
        $this->assertSame('1000.00', $plan['total_credit']);
        $this->assertSame(['1130','1220','5301','1210'], array_map('strval', array_column($plan['lines'], 'account_code')));
        $this->assertFalse($plan['posts_journal']);
        $this->assertFalse($plan['exports_to_tally']);
    }

    public function test_zero_tds_and_deduction_omit_zero_debit_lines(): void
    {
        $plan = Blueprint::build('verified', '1000', '0', '0', '1000', true, true);
        $this->assertSame(['1130','1210'], array_map('strval', array_column($plan['lines'], 'account_code')));
    }

    public function test_unverified_settlement_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        Blueprint::build('draft', '1000', '0', '0', '1000', true, true);
    }

    public function test_unreconciled_opening_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        Blueprint::build('verified', '1000', '0', '0', '1000', false, true);
    }

    public function test_opening_not_in_control_account_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        Blueprint::build('verified', '1000', '0', '0', '1000', true, false);
    }

    public function test_unbalanced_component_amounts_are_rejected(): void
    {
        $this->expectException(DomainException::class);
        Blueprint::build('verified', '850.00', '100.00', '50.00', '1001.00', true, true);
    }
}
