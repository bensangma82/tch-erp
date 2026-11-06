<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\TallyExport;
use App\Models\TallyImportedVoucher;
use App\Models\TallyImportedVoucherEntry;
use App\Models\User;
use App\Services\Tally\TallyManualJournalMatchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Database-backed, NO TallyPrime network access. All records roll back.
 * Protects against an incorrect manual match and a second ERP export.
 */
class TallyManualJournalMatchAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName(),
            'Refusing manual-match acceptance test outside the testing database.');
        config()->set('tally.company', 'TCH-AUTOMATED-TEST-COMPANY');
    }

    /** @return array{JournalEntry,TallyImportedVoucher,User} */
    private function fixture(string $voucherType = 'Receipt', string $voucherDate = '2026-11-06'): array
    {
        $user = User::factory()->create();
        $tag = strtoupper(bin2hex(random_bytes(6)));

        $cash = GeneralLedgerAccount::firstOrCreate(['account_code' => '1100'], [
            'account_name' => 'Main Cash', 'account_type' => 'asset',
            'normal_balance' => 'debit', 'is_active' => true, 'allow_posting' => true,
        ]);
        $advance = GeneralLedgerAccount::firstOrCreate(['account_code' => '2100'], [
            'account_name' => 'Patient Advances', 'account_type' => 'liability',
            'normal_balance' => 'credit', 'is_active' => true, 'allow_posting' => true,
        ]);
        // Isolated test DB only; this ensures the comparison uses known Tally names.
        $cash->update(['tally_ledger_name' => 'Test Main Cash']);
        $advance->update(['tally_ledger_name' => 'Test Patient Advances']);

        $journal = JournalEntry::create([
            'journal_no' => 'JRN-TCH-TEST-'.$tag,
            'journal_date' => '2026-11-06',
            'source_type' => 'ip_billing_advance',
            'source_id' => 'TEST-'.$tag,
            'source_event' => 'received',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
        foreach ([
            [$cash->id, '427.80', '0.00'],
            [$advance->id, '0.00', '427.80'],
        ] as $index => [$glId, $debit, $credit]) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => $index + 1,
                'general_ledger_account_id' => $glId,
                'debit' => $debit, 'credit' => $credit,
            ]);
        }

        $this->actingAs($user);
        app(\App\Services\Accounting\JournalPostingService::class)->post($journal);

        $voucher = TallyImportedVoucher::create([
            'tally_company' => 'TCH-AUTOMATED-TEST-COMPANY',
            'guid' => 'TEST-TALLY-'.$tag,
            'voucher_date' => $voucherDate,
            'voucher_type' => $voucherType,
            'voucher_number' => 'TEST-'.$tag,
            'reconciliation_status' => 'pending',
        ]);
        foreach ([
            ['Test Main Cash', '-427.80', true],
            ['Test Patient Advances', '427.80', false],
        ] as $index => [$ledger, $amount, $debit]) {
            TallyImportedVoucherEntry::create([
                'tally_imported_voucher_id' => $voucher->id,
                'line_no' => $index + 1,
                'ledger_name' => $ledger,
                'amount' => $amount,
                'is_deemed_positive' => $debit,
            ]);
        }
        return [$journal, $voucher, $user];
    }

    public function test_matching_existing_receipt_creates_one_confirmed_non_transmitted_record(): void
    {
        [$journal, $voucher, $user] = $this->fixture();
        $service = app(TallyManualJournalMatchService::class);
        $export = $service->match($journal->id, $voucher->id, $user->id,
            'Verified matching original Tally receipt in isolated test company');

        $this->assertSame('confirmed', $export->fresh()->status);
        $this->assertNull($export->fresh()->exported_at);
        $this->assertSame($journal->id, (int) $export->journal_entry_id);
        $this->assertSame($journal->id, (int) $voucher->fresh()->journal_entry_id);
        $this->assertSame('manual_gl', $voucher->fresh()->match_method);
        $this->assertSame('matched', $voucher->fresh()->reconciliation_status);
        $this->assertSame(1, TallyExport::where('journal_entry_id', $journal->id)->count());
        $this->assertSame('matched',
            app(\App\Services\Tally\TallyJournalReconciliationService::class)
                ->reconcile($journal->fresh())['status']);

        try {
            $service->match($journal->id, $voucher->id, $user->id, 'Attempted duplicate match');
            $this->fail('Duplicate manual matching should be rejected.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('already has an export', $e->getMessage());
        }
        $this->assertSame(1, TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_wrong_voucher_type_is_rejected_without_link_or_export(): void
    {
        [$journal, $voucher, $user] = $this->fixture('Journal');
        try {
            app(TallyManualJournalMatchService::class)->match(
                $journal->id, $voucher->id, $user->id, 'Wrong voucher type acceptance test');
            $this->fail('Wrong Tally voucher type should be rejected.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('voucher type', strtolower($e->getMessage()));
        }
        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(0, TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_wrong_accounting_date_is_rejected_without_link_or_export(): void
    {
        [$journal, $voucher, $user] = $this->fixture('Receipt', '2026-10-01');
        try {
            app(TallyManualJournalMatchService::class)->match(
                $journal->id, $voucher->id, $user->id, 'Wrong accounting date acceptance test');
            $this->fail('Wrong voucher date should be rejected.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('dates differ', strtolower($e->getMessage()));
        }
        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(0, TallyExport::where('journal_entry_id', $journal->id)->count());
    }
}
