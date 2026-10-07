<?php

namespace Tests\Feature;

use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\TallyImportedVoucher;
use App\Models\TallyImportedVoucherEntry;
use App\Models\User;
use App\Services\Tally\TallyImportService;
use App\Services\Tally\TallyQueryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Tests\TestCase;

class TallyImportStabilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tally_reimport_preserves_existing_reconciliation_audit_fields(): void
    {
        $user = User::factory()->create();

        $financeHead = FinanceHead::create([
            'code' => 'TEST-IMPORT-INCOME',
            'name' => 'Test Import Income',
            'head_type' => 'income',
            'is_active' => true,
        ]);

        $financeAccount = FinanceAccount::create([
            'code' => 'TEST-IMPORT-CASH',
            'name' => 'Test Import Cash',
            'account_type' => 'cash',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $financeVoucher = FinanceVoucher::create([
            'voucher_no' => 'TEST-IMPORT-001',
            'voucher_type' => 'receipt',
            'voucher_date' => '2026-09-01',
            'finance_head_id' => $financeHead->id,
            'finance_account_id' => $financeAccount->id,
            'amount' => 500.00,
            'narration' => 'ERP reconciliation record',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        /*
         * Store timestamps at second precision because PostgreSQL may
         * normalise fractional seconds differently from Carbon.
         */
        $reconciledAt = now()
            ->subHour()
            ->startOfSecond();

        $firstSeenAt = now()
            ->subDay()
            ->startOfSecond();

        $importedVoucher = TallyImportedVoucher::create([
            'guid' => 'TEST-IMPORT-GUID-001',
            'remote_id' => 'OLD-REMOTE-ID',
            'voucher_date' => '2026-09-01',
            'voucher_type' => 'Receipt',
            'voucher_number' => 'OLD-VOUCHER-NUMBER',
            'party_ledger' => 'Old Party',
            'narration' => 'Old Tally narration',

            /*
             * Existing reconciliation state.
             *
             * These fields must survive a later Tally import.
             */
            'finance_voucher_id' => $financeVoucher->id,
            'reconciliation_status' => 'matched',
            'match_method' => 'manual',
            'match_confidence' => null,
            'reconciliation_notes' => 'Approved manual reconciliation.',
            'reconciled_by' => $user->id,
            'reconciled_at' => $reconciledAt,

            'first_seen_at' => $firstSeenAt,
            'last_seen_at' => now()->subHour(),
        ]);

        /*
         * Existing ledger lines should be replaced by the latest
         * representation received from Tally.
         */
        TallyImportedVoucherEntry::create([
            'tally_imported_voucher_id' => $importedVoucher->id,
            'ledger_name' => 'Old Income Ledger',
            'amount' => 500.00,
            'is_deemed_positive' => false,
            'line_no' => 1,
        ]);

        TallyImportedVoucherEntry::create([
            'tally_imported_voucher_id' => $importedVoucher->id,
            'ledger_name' => 'Old Cash Ledger',
            'amount' => -500.00,
            'is_deemed_positive' => true,
            'line_no' => 2,
        ]);

        /*
         * Simulate Tally returning the same durable GUID with updated
         * source data.
         */
        $this->mock(
            TallyQueryService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('vouchers')
                    ->once()
                    ->andReturn([
                        [
                            'guid' => 'TEST-IMPORT-GUID-001',
                            'remote_id' => 'NEW-REMOTE-ID',
                            'date' => '20260902',
                            'voucher_type' => 'Receipt',
                            'voucher_number' => 'NEW-VOUCHER-NUMBER',
                            'party_ledger' => 'Updated Party',
                            'narration' => 'Updated Tally narration',
                            'ledger_entries' => [
                                [
                                    'ledger_name' => 'Updated Income Ledger',
                                    'amount' => 500.00,
                                    'is_deemed_positive' => false,
                                ],
                                [
                                    'ledger_name' => 'Updated Cash Ledger',
                                    'amount' => -500.00,
                                    'is_deemed_positive' => true,
                                ],
                            ],
                        ],
                    ]);
            }
        );

        $summary = app(
            TallyImportService::class
        )->sync();

        $importedVoucher->refresh();
        $importedVoucher->load('entries');

        /*
         * Import summary confirms that the existing voucher was updated,
         * not inserted as a new record.
         */
        $this->assertSame(
            1,
            $summary['received']
        );

        $this->assertSame(
            0,
            $summary['imported']
        );

        $this->assertSame(
            1,
            $summary['updated']
        );

        $this->assertSame(
            0,
            $summary['skipped']
        );

        /*
         * Tally-owned source fields should update.
         */
        $this->assertSame(
            'NEW-REMOTE-ID',
            $importedVoucher->remote_id
        );

        $this->assertSame(
            '2026-09-02',
            $importedVoucher->voucher_date?->format('Y-m-d')
        );

        $this->assertSame(
            'NEW-VOUCHER-NUMBER',
            $importedVoucher->voucher_number
        );

        $this->assertSame(
            'Updated Party',
            $importedVoucher->party_ledger
        );

        $this->assertSame(
            'Updated Tally narration',
            $importedVoucher->narration
        );

        /*
         * Existing reconciliation state must remain untouched.
         */
        $this->assertSame(
            $financeVoucher->id,
            $importedVoucher->finance_voucher_id
        );

        $this->assertSame(
            'matched',
            $importedVoucher->reconciliation_status
        );

        $this->assertSame(
            'manual',
            $importedVoucher->match_method
        );

        $this->assertNull(
            $importedVoucher->match_confidence
        );

        $this->assertSame(
            'Approved manual reconciliation.',
            $importedVoucher->reconciliation_notes
        );

        $this->assertSame(
            $user->id,
            $importedVoucher->reconciled_by
        );

        /*
         * Compare at second precision so harmless database timestamp
         * precision differences do not cause a false failure.
         */
        $this->assertSame(
            $reconciledAt->format('Y-m-d H:i:s'),
            $importedVoucher
                ->reconciled_at
                ?->format('Y-m-d H:i:s')
        );

        $this->assertSame(
            $firstSeenAt->format('Y-m-d H:i:s'),
            $importedVoucher
                ->first_seen_at
                ?->format('Y-m-d H:i:s')
        );

        /*
         * Child ledger lines should reflect the latest Tally data.
         */
        $this->assertCount(
            2,
            $importedVoucher->entries
        );

        $this->assertSame(
            [
                'Updated Income Ledger',
                'Updated Cash Ledger',
            ],
            $importedVoucher
                ->entries
                ->sortBy('line_no')
                ->pluck('ledger_name')
                ->values()
                ->all()
        );

        $this->assertSame(
            [
                '500.00',
                '-500.00',
            ],
            $importedVoucher
                ->entries
                ->sortBy('line_no')
                ->pluck('amount')
                ->values()
                ->all()
        );

        $this->assertDatabaseMissing(
            'tally_imported_voucher_entries',
            [
                'tally_imported_voucher_id' => $importedVoucher->id,
                'ledger_name' => 'Old Income Ledger',
            ]
        );

        $this->assertDatabaseMissing(
            'tally_imported_voucher_entries',
            [
                'tally_imported_voucher_id' => $importedVoucher->id,
                'ledger_name' => 'Old Cash Ledger',
            ]
        );
    }
}