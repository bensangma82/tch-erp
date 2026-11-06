<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\TallyExport;
use App\Models\TallyImportedVoucher;
use App\Models\TallyImportedVoucherEntry;
use App\Models\User;
use App\Services\Accounting\JournalPostingService;
use App\Services\Tally\TallyAutomaticJournalMatchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class TallyAutomaticJournalMatchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName(),
            'Refusing automatic reconciliation test outside isolated testing database.');
        config()->set('tally.company', 'TCH-AUTOMATED-TEST-COMPANY');
    }

    protected function fixture(): array
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
        $cash->update(['tally_ledger_name' => 'Test Main Cash']);
        $advance->update(['tally_ledger_name' => 'Test Patient Advances']);
        $journal = JournalEntry::create([
            'journal_no' => 'JRN-TCH-TEST-'.$tag,
            'journal_date' => '2026-11-06',
            'source_type' => 'ip_billing_advance', 'source_id' => 'TEST-'.$tag,
            'source_event' => 'received', 'status' => 'draft', 'created_by' => $user->id,
        ]);
        foreach ([[$cash->id, '427.80', '0.00'], [$advance->id, '0.00', '427.80']] as $i => [$id, $debit, $credit]) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id, 'line_no' => $i + 1,
                'general_ledger_account_id' => $id, 'debit' => $debit, 'credit' => $credit,
            ]);
        }
        $this->actingAs($user);
        app(JournalPostingService::class)->post($journal);
        $journal->refresh();
        $voucher = TallyImportedVoucher::create([
            'tally_company' => 'TCH-AUTOMATED-TEST-COMPANY',
            'guid' => 'AUTO-TEST-GUID-'.$tag, 'voucher_date' => '2026-11-06',
            'voucher_type' => 'Receipt', 'voucher_number' => 'TEST-'.$tag,
            'narration' => 'ERP '.$journal->journal_no.' | existing Tally entry',
            'reconciliation_status' => 'pending',
        ]);
        foreach ([['Test Main Cash', '-427.80', true], ['Test Patient Advances', '427.80', false]] as $i => [$ledger, $amount, $debit]) {
            TallyImportedVoucherEntry::create([
                'tally_imported_voucher_id' => $voucher->id, 'line_no' => $i + 1,
                'ledger_name' => $ledger, 'amount' => $amount, 'is_deemed_positive' => $debit,
            ]);
        }
        return [$journal, $voucher, $user];
    }

    public function test_ledger_mismatch_is_rejected_without_export(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $voucher->entries()->where('is_deemed_positive', true)
            ->firstOrFail()->update(['ledger_name' => 'Incorrect Cash Ledger']);

        $result = app(TallyAutomaticJournalMatchService::class)
            ->preview($journal->fresh());

        $this->assertSame('ledger_mismatch', $result['status']);
        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(0,
            TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_confirmed_match_cannot_be_applied_twice(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $service = app(TallyAutomaticJournalMatchService::class);

        $service->apply($journal->id, $voucher->id, $user->id);

        $this->expectException(RuntimeException::class);

        try {
            $service->apply($journal->id, $voucher->id, $user->id);
        } finally {
            $this->assertSame(1,
                TallyExport::where('journal_entry_id', $journal->id)->count());
            $this->assertSame('matched',
                $voucher->fresh()->reconciliation_status);
        }
    }

    public function test_authorised_http_confirmation_requires_checkbox(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'finance.reconciliation.manage'],
            [
                'module' => 'finance',
                'label' => 'Manage Tally Reconciliation',
                'description' => 'Test permission',
            ]
        );

        $user->forceFill([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        DB::table('role_permission')->updateOrInsert(
            [
                'role' => 'admin',
                'permission_id' => $permission->id,
            ],
            ['updated_at' => now()]
        );

        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertTrue($user->fresh()->hasPermission('finance.reconciliation.manage'));
        $this->assertTrue((bool) $user->fresh()->is_active);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user)->post(route(
            'finance.tally.reconciliation.journal.automatic-match',
            $journal
        ), [
            'tally_imported_voucher_id' => $voucher->id,
        ])->assertSessionHasErrors('confirm_no_transmission');

        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(0,
            TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_authorised_http_confirmation_creates_one_match(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'finance.reconciliation.manage'],
            [
                'module' => 'finance',
                'label' => 'Manage Tally Reconciliation',
                'description' => 'Test permission',
            ]
        );

        $user->forceFill([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        DB::table('role_permission')->updateOrInsert(
            [
                'role' => 'admin',
                'permission_id' => $permission->id,
            ],
            ['updated_at' => now()]
        );

        $url = route(
            'finance.tally.reconciliation.journal.automatic-match',
            $journal
        );

        $data = [
            'tally_imported_voucher_id' => $voucher->id,
            'confirm_no_transmission' => '1',
        ];

        $this->actingAs($user)->post($url, $data)
            ->assertRedirect(route('finance.tally.reconciliation'));

        $this->assertSame('matched',
            $voucher->fresh()->reconciliation_status);
        $this->assertSame('automatic_gl',
            $voucher->fresh()->match_method);
        $this->assertSame(1,
            TallyExport::where('journal_entry_id', $journal->id)->count());

        $this->post($url, $data)
            ->assertSessionHas('error');

        $this->assertSame(1,
            TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_amount_mismatch_is_classified_without_creating_export(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $voucher->entries()->where('is_deemed_positive', true)
            ->firstOrFail()->update(['amount' => '-400.00']);

        $result = app(TallyAutomaticJournalMatchService::class)
            ->preview($journal->fresh());

        $this->assertSame('amount_mismatch', $result['status']);
        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(
            0,
            TallyExport::where('journal_entry_id', $journal->id)->count()
        );
    }

    public function test_unique_verified_match_is_confirmed_without_transmission(): void
    {
        [$journal, $voucher, $user] = $this->fixture();
        $service = app(TallyAutomaticJournalMatchService::class);
        $this->assertSame('ready', $service->preview($journal)['status']);
        $export = $service->apply($journal->id, $voucher->id, $user->id);
        $this->assertSame('confirmed', $export->status);
        $this->assertNull($export->exported_at);
        $this->assertSame('automatic_gl', $voucher->fresh()->match_method);
        $this->assertSame('matched', $service->preview($journal->fresh())['status']);
        $this->assertSame(1, TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_duplicate_erp_narration_is_ambiguous_and_never_applied(): void
    {
        [$journal, $voucher, $user] = $this->fixture();
        $copy = $voucher->replicate();
        $copy->guid = 'DUP-'.$voucher->guid;
        $copy->voucher_number = 'OTHER-'.$voucher->voucher_number;
        $copy->save();
        foreach ($voucher->entries as $entry) {
            $line = $entry->replicate();
            $line->tally_imported_voucher_id = $copy->id;
            $line->save();
        }
        $service = app(TallyAutomaticJournalMatchService::class);
        $this->assertSame('duplicate_ambiguous', $service->preview($journal)['status']);
        try {
            $service->apply($journal->id, $voucher->id, $user->id);
            $this->fail('Ambiguous candidate should never be applied.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('not eligible', $e->getMessage());
        }
        $this->assertSame(0, TallyExport::where('journal_entry_id', $journal->id)->count());
    }
}
