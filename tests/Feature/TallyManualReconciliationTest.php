<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\TallyImportedVoucher;
use App\Models\TallyImportedVoucherEntry;
use App\Models\TallyLedgerMapping;
use App\Models\User;
use App\Services\Tally\TallyReconciliationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TallyManualReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manual_reconciliation_is_preserved_by_later_automatic_reconciliation(): void
    {
        $financeHead = FinanceHead::create([
            'code' => 'TEST-MANUAL-INCOME',
            'name' => 'Test Manual Income',
            'head_type' => 'income',
            'is_active' => true,
        ]);

        $financeAccount = FinanceAccount::create([
            'code' => 'TEST-MANUAL-CASH',
            'name' => 'Test Manual Cash',
            'account_type' => 'cash',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        TallyLedgerMapping::create([
            'finance_head_id' => $financeHead->id,
            'tally_ledger_name' => 'Test Manual Income',
            'tally_group_name' => 'Indirect Incomes',
            'is_active' => true,
        ]);

        TallyLedgerMapping::create([
            'finance_account_id' => $financeAccount->id,
            'tally_ledger_name' => 'Test Manual Cash',
            'tally_group_name' => 'Cash-in-Hand',
            'is_active' => true,
        ]);

        $financeVoucher = FinanceVoucher::create([
            'voucher_no' => 'TEST-MANUAL-001',
            'voucher_type' => 'receipt',
            'voucher_date' => '2026-10-07',
            'finance_head_id' => $financeHead->id,
            'finance_account_id' => $financeAccount->id,
            'amount' => 700.00,
            'narration' => null,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $candidateA = $this->createCandidate(
            'TEST-MANUAL-A',
            'TEST-A',
            '2026-10-07'
        );

        $this->createCandidate(
            'TEST-MANUAL-B',
            'TEST-B',
            '2026-10-07'
        );

        /*
         * Manual reconciliation records the ERP user who made the
         * decision, so the test must create a valid user because
         * reconciled_by is protected by a foreign key.
         */
        $user = User::factory()->create();

        $service = app(TallyReconciliationService::class);

        $beforeManualMatch = $service->reconcile();

        $this->assertSame(
            1,
            $beforeManualMatch['ambiguous']
        );

        $service->manualMatch(
            $financeVoucher,
            $candidateA,
            $user->id,
            'Manual ambiguity test.'
        );

        $candidateA->refresh();

        $this->assertSame(
            $financeVoucher->id,
            $candidateA->finance_voucher_id
        );

        $this->assertSame(
            'matched',
            $candidateA->reconciliation_status
        );

        $this->assertSame(
            'manual',
            $candidateA->match_method
        );

        $this->assertSame(
            $user->id,
            $candidateA->reconciled_by
        );

        $this->assertNotNull(
            $candidateA->reconciled_at
        );

        $this->assertStringContainsString(
            'Manually reconciled by an authorised ERP user.',
            (string) $candidateA->reconciliation_notes
        );

        $this->assertStringContainsString(
            'Manual ambiguity test.',
            (string) $candidateA->reconciliation_notes
        );

        $reconciledAt = $candidateA->reconciled_at?->copy();
        $reconciliationNotes = $candidateA->reconciliation_notes;

        /*
         * Run automatic reconciliation again.
         *
         * The manually reconciled voucher must remain protected and
         * must not be reclassified or overwritten.
         */
        $afterManualMatch = $service->reconcile();

        $candidateA->refresh();

        $this->assertSame(
            'manual',
            $candidateA->match_method
        );

        $this->assertSame(
            $financeVoucher->id,
            $candidateA->finance_voucher_id
        );

        $this->assertSame(
            'matched',
            $candidateA->reconciliation_status
        );

        $this->assertSame(
            $user->id,
            $candidateA->reconciled_by
        );

        $this->assertTrue(
            $candidateA->reconciled_at?->equalTo($reconciledAt)
        );

        $this->assertSame(
            $reconciliationNotes,
            $candidateA->reconciliation_notes
        );

        $this->assertSame(
            0,
            $afterManualMatch['ambiguous']
        );
    }

    private function createCandidate(
        string $guid,
        string $voucherNumber,
        string $voucherDate
    ): TallyImportedVoucher {
        $voucher = TallyImportedVoucher::create([
            'guid' => $guid,
            'remote_id' => $guid,
            'voucher_date' => $voucherDate,
            'voucher_type' => 'Receipt',
            'voucher_number' => $voucherNumber,
            'party_ledger' => 'Test Manual Income',
            'narration' => null,
            'reconciliation_status' => 'tally_only',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        TallyImportedVoucherEntry::create([
            'tally_imported_voucher_id' => $voucher->id,
            'ledger_name' => 'Test Manual Cash',
            'amount' => -700.00,
            'is_deemed_positive' => true,
            'line_no' => 1,
        ]);

        TallyImportedVoucherEntry::create([
            'tally_imported_voucher_id' => $voucher->id,
            'ledger_name' => 'Test Manual Income',
            'amount' => 700.00,
            'is_deemed_positive' => false,
            'line_no' => 2,
        ]);

        return $voucher->fresh('entries');
    }
}