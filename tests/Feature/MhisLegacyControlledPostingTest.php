<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\MhisLegacySettlement;
use App\Models\MhisOpeningReceivable;
use App\Models\Permission;
use App\Models\User;
use App\Services\Accounting\MhisLegacySettlementControlledPostingService;
use App\Services\Accounting\MhisLegacySettlementDraftJournalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MhisLegacyControlledPostingTest extends TestCase
{
    use DatabaseTransactions;

    private User $maker;
    private User $checker;
    private User $preparer;
    private User $poster;
    private MhisOpeningReceivable $opening;
    private MhisLegacySettlement $settlement;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'testing') {
            $this->fail('Safety stop: requires testing database');
        }
        config()->set('mhis_legacy_posting.enabled', false);
        $this->maker = User::factory()->create(['role' => 'finance', 'is_active' => true]);
        $this->checker = User::factory()->create(['role' => 'finance_manager', 'is_active' => true]);
        $this->preparer = User::factory()->create(['role' => 'finance_manager', 'is_active' => true]);
        $this->poster = User::factory()->create(['role' => 'finance_manager', 'is_active' => true]);
        $perm = Permission::firstOrCreate(
            ['name' => 'finance.mhis-legacy-settlement.post'],
            ['label' => 'Post Legacy MHIS Settlement', 'module' => 'finance', 'description' => 'Testing fixture']
        );
        DB::table('role_permission')->updateOrInsert(
            ['role' => 'finance_manager', 'permission_id' => $perm->id],
            ['updated_at' => now()]
        );
        $this->opening = MhisOpeningReceivable::create([
            'opening_date' => '2027-04-01', 'financial_year' => '2027-28',
            'opening_amount' => '1000.00', 'statement_reference' => 'TEST-TB-POST',
            'status' => 'approved', 'created_by' => $this->maker->id,
            'approved_by' => $this->checker->id, 'approved_at' => now(),
        ]);
        $this->settlement = MhisLegacySettlement::create([
            'mhis_opening_receivable_id' => $this->opening->id,
            'settlement_date' => '2027-04-15', 'settlement_reference' => 'TEST-POST-UTR',
            'historical_claim_reference' => 'TEST-POST-CLAIM',
            'net_amount' => '850.00', 'tds_amount' => '100.00',
            'approved_deduction' => '50.00', 'gross_settled_amount' => '1000.00',
            'status' => 'verified', 'created_by' => $this->maker->id,
            'verified_by' => $this->checker->id, 'verified_at' => now(),
        ]);
        DB::table('mhis_opening_reconciliations')->insert([
            'mhis_opening_receivable_id' => $this->opening->id,
            'trial_balance_reference' => 'TEST-TB-POST',
            'tally_company' => 'TEST ONLY',
            'tally_gl_1210_closing_balance' => '1000.00',
            'gl_1210_carry_forward_confirmed' => true,
            'supporting_evidence_reference' => 'TEST-EVIDENCE',
            'status' => 'verified', 'prepared_by' => $this->maker->id,
            'verified_by' => $this->checker->id, 'verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function draft(): \App\Models\JournalEntry
    {
        return app(MhisLegacySettlementDraftJournalService::class)
            ->prepare($this->settlement->id, $this->preparer->id);
    }

    private function postLegacyJournal(int $actor): \App\Models\JournalEntry
    {
        return app(MhisLegacySettlementControlledPostingService::class)
            ->post($this->settlement->id, $actor);
    }

    private function rejects(callable $fn, string $key): void
    {
        try {
            $fn();
            $this->fail("Expected validation error: {$key}");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }

    public function test_default_gate_blocks_real_posting(): void
    {
        $journal = $this->draft();
        $this->rejects(fn () => $this->postLegacyJournal($this->poster->id), 'posting');
        $this->assertSame('draft', $journal->fresh()->status);
    }

    public function test_no_journal_cannot_be_posted_even_with_gate(): void
    {
        config()->set('mhis_legacy_posting.enabled', true);
        $this->rejects(fn () => $this->postLegacyJournal($this->poster->id), 'journal');
    }

    public function test_unverified_reconciliation_blocks_post(): void
    {
        $journal = $this->draft();
        DB::table('mhis_opening_reconciliations')
            ->where('mhis_opening_receivable_id', $this->opening->id)
            ->update(['status' => 'draft']);
        config()->set('mhis_legacy_posting.enabled', true);
        $this->rejects(fn () => $this->postLegacyJournal($this->poster->id), 'reconciliation');
        $this->assertSame('draft', $journal->fresh()->status);
    }

    public function test_preparer_cannot_post(): void
    {
        $journal = $this->draft();
        config()->set('mhis_legacy_posting.enabled', true);
        $this->rejects(fn () => $this->postLegacyJournal($this->preparer->id), 'actor');
        $this->assertSame('draft', $journal->fresh()->status);
    }

    public function test_modified_draft_lines_are_rejected(): void
    {
        $journal = $this->draft();
        DB::table('journal_entry_lines')->where('journal_entry_id', $journal->id)
            ->where('line_no', 1)->update(['debit' => '851.00']);
        config()->set('mhis_legacy_posting.enabled', true);
        $this->rejects(fn () => $this->postLegacyJournal($this->poster->id), 'journal');
        $this->assertSame('draft', $journal->fresh()->status);
    }

    public function test_authorized_post_is_balanced_and_idempotent_without_creating_export(): void
    {
        $journal = $this->draft();
        $before = DB::table('tally_exports')->count();
        config()->set('mhis_legacy_posting.enabled', true); // TEST DATABASE ONLY
        $posted = $this->postLegacyJournal($this->poster->id);
        $this->assertSame($journal->id, $posted->id);
        $this->assertSame('posted', $posted->status);
        $this->assertNotNull($posted->posted_at);
        $this->assertSame($this->poster->id, (int) $posted->posted_by);
        $this->assertSame('1000.00', number_format((float) $posted->lines->sum('debit'), 2, '.', ''));
        $this->assertSame('1000.00', number_format((float) $posted->lines->sum('credit'), 2, '.', ''));
        $this->assertSame($posted->id, $this->postLegacyJournal($this->poster->id)->id);
        $this->assertSame(1, DB::table('journal_entries')
            ->where('source_type', 'mhis_legacy_settlement')
            ->where('source_id', $this->settlement->id)->count());
        $this->assertSame($before, DB::table('tally_exports')->count());
    }
}
