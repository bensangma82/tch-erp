<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\TallyExport;
use App\Models\TallyImportedVoucher;
use App\Models\TallyImportedVoucherEntry;
use App\Models\TallyLedgerMapping;
use App\Services\Tally\TallyReconciliationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class TallyReconciliationDifferenceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_exact_remote_id_with_amount_difference_is_marked_as_difference(): void
    {
        $financeHead = FinanceHead::create([
            'code' => 'TEST-INCOME',
            'name' => 'Test Income',
            'head_type' => 'income',
            'is_active' => true,
        ]);

        $financeAccount = FinanceAccount::create([
            'code' => 'TEST-CASH',
            'name' => 'Test Cash',
            'account_type' => 'cash',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        TallyLedgerMapping::create([
            'finance_head_id' => $financeHead->id,
            'tally_ledger_name' => 'Test Income',
            'tally_group_name' => 'Indirect Incomes',
            'is_active' => true,
        ]);

        TallyLedgerMapping::create([
            'finance_account_id' => $financeAccount->id,
            'tally_ledger_name' => 'Test Cash',
            'tally_group_name' => 'Cash-in-Hand',
            'is_active' => true,
        ]);

        $financeVoucher = FinanceVoucher::create([
            'voucher_no' => 'TEST-REC-001',
            'voucher_type' => 'receipt',
            'voucher_date' => '2026-10-07',
            'finance_head_id' => $financeHead->id,
            'finance_account_id' => $financeAccount->id,
            'amount' => 1000.00,
            'narration' => 'Difference detection test',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $remoteId = (string) Str::uuid();

        TallyExport::create([
            'finance_voucher_id' => $financeVoucher->id,
            'status' => 'exported',
            'remote_id' => $remoteId,
            'exported_at' => now(),
        ]);

        $tallyVoucher = TallyImportedVoucher::create([
            'guid' => (string) Str::uuid(),
            'remote_id' => $remoteId,
            'voucher_date' => '2026-10-07',
            'voucher_type' => 'Receipt',
            'voucher_number' => 'TEST-TALLY-001',
            'party_ledger' => 'Test Income',
            'narration' => 'Difference detection test',
            'reconciliation_status' => 'tally_only',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        /*
         * Deliberately create a Tally transaction of 900.00
         * against an ERP voucher amount of 1000.00.
         *
         * Tally sign convention:
         * debit  = negative
         * credit = positive
         */
        TallyImportedVoucherEntry::create([
            'tally_imported_voucher_id' => $tallyVoucher->id,
            'ledger_name' => 'Test Cash',
            'amount' => -900.00,
            'is_deemed_positive' => true,
            'line_no' => 1,
        ]);

        TallyImportedVoucherEntry::create([
            'tally_imported_voucher_id' => $tallyVoucher->id,
            'ledger_name' => 'Test Income',
            'amount' => 900.00,
            'is_deemed_positive' => false,
            'line_no' => 2,
        ]);

        $summary = app(
            TallyReconciliationService::class
        )->reconcile();

        $tallyVoucher->refresh();

        $this->assertSame(
            'difference',
            $tallyVoucher->reconciliation_status
        );

        $this->assertSame(
            'exact_remote_id',
            $tallyVoucher->match_method
        );

        $this->assertSame(
            $financeVoucher->id,
            $tallyVoucher->finance_voucher_id
        );

        $this->assertSame(
            1,
            $summary['difference']
        );

        $this->assertSame(
            0,
            $summary['matched']
        );

        $this->assertStringContainsString(
            'Amount differs',
            (string) $tallyVoucher->reconciliation_notes
        );

        $this->assertStringContainsString(
            'ERP 1000.00; Tally 900.00',
            (string) $tallyVoucher->reconciliation_notes
        );
    }
}
