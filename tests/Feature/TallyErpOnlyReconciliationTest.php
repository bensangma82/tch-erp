<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\TallyLedgerMapping;
use App\Services\Tally\TallyReconciliationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TallyErpOnlyReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_posted_erp_voucher_with_valid_mappings_but_no_tally_match_is_classified_as_erp_only(): void
    {
        $financeHead = FinanceHead::create([
            'code' => 'TEST-ERPONLY-INCOME',
            'name' => 'Test ERP Only Income',
            'head_type' => 'income',
            'is_active' => true,
        ]);

        $financeAccount = FinanceAccount::create([
            'code' => 'TEST-ERPONLY-CASH',
            'name' => 'Test ERP Only Cash',
            'account_type' => 'cash',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        /*
         * Valid Tally mappings exist, so this voucher must not be
         * classified as unmapped.
         */
        TallyLedgerMapping::create([
            'finance_head_id' => $financeHead->id,
            'tally_ledger_name' => 'Test ERP Only Income',
            'tally_group_name' => 'Indirect Incomes',
            'is_active' => true,
        ]);

        TallyLedgerMapping::create([
            'finance_account_id' => $financeAccount->id,
            'tally_ledger_name' => 'Test ERP Only Cash',
            'tally_group_name' => 'Cash-in-Hand',
            'is_active' => true,
        ]);

        $financeVoucher = FinanceVoucher::create([
            'voucher_no' => 'TEST-ERPONLY-001',
            'voucher_type' => 'receipt',
            'voucher_date' => '2026-10-07',
            'finance_head_id' => $financeHead->id,
            'finance_account_id' => $financeAccount->id,
            'amount' => 900.00,
            'payment_mode' => 'cash',
            'party_name' => 'ERP Only Test',
            'narration' => 'ERP only reconciliation test',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        /*
         * Deliberately create no imported Tally voucher.
         */
        $service = app(
            TallyReconciliationService::class
        );

        $summary = $service->reconcile();

        $this->assertSame(
            1,
            $summary['processed']
        );

        $this->assertSame(
            0,
            $summary['matched']
        );

        $this->assertSame(
            1,
            $summary['erp_only']
        );

        $this->assertSame(
            0,
            $summary['unmapped']
        );

        $this->assertSame(
            0,
            $summary['ambiguous']
        );

        $this->assertSame(
            0,
            $summary['difference']
        );

        /*
         * The review-page diagnostic should identify this specifically
         * as ERP-only, because mappings exist but no valid Tally
         * candidate exists.
         */
        $diagnostic = $service->unmatchedStatus(
            $financeVoucher
        );

        $this->assertSame(
            'erp_only',
            $diagnostic['status']
        );

        $this->assertSame(
            0,
            $diagnostic['candidate_count']
        );

        $this->assertCount(
            0,
            $service->candidatesFor($financeVoucher)
        );
    }
}