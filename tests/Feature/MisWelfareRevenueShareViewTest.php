<?php

namespace Tests\Feature;

use Tests\TestCase;

class MisWelfareRevenueShareViewTest extends TestCase
{
    private function renderShares(array $values = [])
    {
        return $this->view('mis.partials.welfare-revenue-shares', array_replace([
            'financeRecognizedRevenue' => 10000.00,
            'charityAppliedAmount' => 500.00,
            'staffBenefitNetUtilization' => 200.00,
            'from' => '2026-11-01',
            'to' => '2026-11-18',
        ], $values));
    }

    public function test_displays_both_amounts_and_percentages_against_recognized_revenue(): void
    {
        $this->renderShares()->assertSee('Charity % of revenue')->assertSee('Staff medical benefit % of revenue')
            ->assertSee('5.00%')->assertSee('2.00%')->assertSee('₹500.00')->assertSee('₹200.00')->assertSee('₹10,000.00');
    }

    public function test_zero_revenue_shows_na_without_hiding_assistance_amounts(): void
    {
        $view = $this->renderShares(['financeRecognizedRevenue' => 0]);
        $view->assertSee('N/A')->assertSee('₹500.00')->assertSee('₹200.00')->assertDontSee('5.00%');
        $this->assertSame(2, substr_count((string) $view, '>N/A</p>'));
    }

    public function test_negative_revenue_is_not_used_as_a_positive_denominator(): void
    {
        $this->renderShares(['financeRecognizedRevenue' => -10000])->assertSee('N/A')->assertSee('₹-10,000.00')
            ->assertSee('zero or negative')->assertDontSee('-5.00%');
    }

    public function test_positive_revenue_with_no_benefits_displays_zero_percent(): void
    {
        $view = $this->renderShares(['charityAppliedAmount' => 0, 'staffBenefitNetUtilization' => 0]);
        $view->assertSee('0.00%')->assertDontSee('N/A');
        $this->assertSame(2, substr_count((string) $view, '>0.00%</p>'));
    }

    public function test_uses_net_staff_amount_instead_of_gross_utilization_or_entitlement(): void
    {
        $this->renderShares(['staffBenefitNetUtilization' => 150, 'staffBenefitUtilizedAmount' => 9999,
            'staffEmployeeEntitlement' => 50000])->assertSee('1.50%')->assertSee('₹150.00')
            ->assertDontSee('₹9,999.00')->assertDontSee('₹50,000.00')->assertSee('after reversals');
    }

    public function test_reversal_dominated_period_keeps_negative_net_use_visible(): void
    {
        $this->renderShares(['staffBenefitNetUtilization' => -50])->assertSee('-0.50%')->assertSee('₹-50.00')
            ->assertSee('Recorded reversals exceed utilization');
    }

    public function test_shares_above_one_hundred_percent_are_not_capped_and_dates_are_displayed(): void
    {
        $this->renderShares(['charityAppliedAmount' => 15000])->assertSee('150.00%')->assertSee('01 Nov 2026')->assertSee('18 Nov 2026');
    }
}
