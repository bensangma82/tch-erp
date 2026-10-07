<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Services\Tally\TallyReconciliationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TallyUnmappedReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_posted_erp_voucher_without_tally_mapping_is_classified_as_unmapped(): void
    {
        $financeHead = FinanceHead::create([
            'code' => 'TEST-UNMAPPED-INCOME',
            'name' => 'Test Unmapped Income',
            'head_type' => 'income',
            'is_active' => true,
        ]);

        $financeAccount = FinanceAccount::create([
            'code' => 'TEST-UNMAPPED-CASH',
            'name' => 'Test Unmapped Cash',
            'account_type' => 'cash',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        /*
         * Deliberately do not create any TallyLedgerMapping records
         * for either the finance head or the finance account.
         */
        $financeVoucher = FinanceVoucher::create([
            'voucher_no' => 'TEST-UNMAPPED-001',
            'voucher_type' => 'receipt',
            'voucher_date' => '2026-10-07',
            'finance_head_id' => $financeHead->id,
            'finance_account_id' => $financeAccount->id,
            'amount' => 800.00,
            'payment_mode' => 'cash',
            'party_name' => 'Unmapped Test',
            'narration' => 'Unmapped reconciliation test',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $service = app(
            TallyReconciliationService::class
        );

        $summary = $service->reconcile();

        /*
         * An unmapped ERP voucher is still ERP-only from the overall
         * reconciliation perspective, but its specific reason must be
         * identified separately as unmapped.
         */
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
            1,
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
         * The review-page diagnostic should give the more useful,
         * specific classification: unmapped.
         */
        $diagnostic = $service->unmatchedStatus(
            $financeVoucher
        );

        $this->assertSame(
            'unmapped',
            $diagnostic['status']
        );

        $this->assertSame(
            0,
            $diagnostic['candidate_count']
        );

        /*
         * Without ledger mappings there must be no manual-match
         * candidates either.
         */
        $this->assertCount(
            0,
            $service->candidatesFor($financeVoucher)
        );
    }
}