<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\MhisLegacySettlement;
use App\Models\MhisOpeningReceivable;
use App\Models\User;
use App\Services\Accounting\MhisLegacySettlementDraftJournalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MhisLegacyDraftJournalAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    private User $maker;
    private User $checker;
    private User $preparer;
    private MhisOpeningReceivable $opening;
    private MhisLegacySettlement $settlement;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'testing') {
            $this->fail('Safety stop: tests require PostgreSQL database named testing.');
        }
        foreach (['mhis_opening_reconciliations', 'mhis_legacy_settlements', 'journal_entries', 'tally_exports'] as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                $this->fail("Missing required test table: {$table}");
            }
        }
        $this->maker = User::factory()->create(['role'=>'finance', 'is_active'=>true]);
        $this->checker = User::factory()->create(['role'=>'finance_manager', 'is_active'=>true]);
        $this->preparer = User::factory()->create(['role'=>'finance_manager', 'is_active'=>true]);
        $this->opening = MhisOpeningReceivable::create([
            'opening_date'=>'2027-04-01', 'financial_year'=>'2027-28',
            'opening_amount'=>'1000.00', 'statement_reference'=>'SYNTHETIC-TEST-TB',
            'status'=>'approved', 'created_by'=>$this->maker->id,
            'approved_by'=>$this->checker->id, 'approved_at'=>now(),
        ]);
        $this->settlement = MhisLegacySettlement::create([
            'mhis_opening_receivable_id'=>$this->opening->id,
            'settlement_date'=>'2027-04-15', 'settlement_reference'=>'SYNTHETIC-TEST-UTR',
            'historical_claim_reference'=>'SYNTHETIC-CLAIM',
            'net_amount'=>'850.00','tds_amount'=>'100.00',
            'approved_deduction'=>'50.00','gross_settled_amount'=>'1000.00',
            'status'=>'verified', 'created_by'=>$this->maker->id,
            'verified_by'=>$this->checker->id, 'verified_at'=>now(),
        ]);
    }

    private function reconcile(bool $confirmed = true): void
    {
        DB::table('mhis_opening_reconciliations')->insert([
            'mhis_opening_receivable_id'=>$this->opening->id,
            'trial_balance_reference'=>'SYNTHETIC-TEST-TB',
            'tally_company'=>'SYNTHETIC-TEST-ONLY',
            'tally_gl_1210_closing_balance'=>'1000.00',
            'gl_1210_carry_forward_confirmed'=>$confirmed,
            'supporting_evidence_reference'=>'SYNTHETIC-TEST-EVIDENCE',
            'status'=>'verified', 'prepared_by'=>$this->maker->id,
            'verified_by'=>$this->checker->id, 'verified_at'=>now(),
            'created_at'=>now(),'updated_at'=>now(),
        ]);
    }

    private function prepare(int $actorId): \App\Models\JournalEntry
    {
        return app(MhisLegacySettlementDraftJournalService::class)->prepare($this->settlement->id, $actorId);
    }

    public function test_missing_reconciliation_blocks_preparation(): void
    {
        $before = DB::table('journal_entries')->count();
        try {
            $this->prepare($this->preparer->id);
            $this->fail('Should reject missing reconciliation');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reconciliation', $e->errors());
        }
        $this->assertSame($before, DB::table('journal_entries')->count());
    }

    public function test_unconfirmed_carry_forward_blocks_preparation(): void
    {
        $this->reconcile(false);
        try {
            $this->prepare($this->preparer->id);
            $this->fail('Should reject unconfirmed carry-forward');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reconciliation', $e->errors());
        }
    }

    public function test_maker_and_checker_cannot_prepare_journal(): void
    {
        $this->reconcile();
        foreach ([$this->maker, $this->checker] as $actor) {
            try {
                $this->prepare($actor->id);
                $this->fail('Should reject non-independent preparer');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('actor', $e->errors());
            }
        }
    }

    public function test_unverified_settlement_cannot_prepare_journal(): void
    {
        $this->reconcile();
        $this->settlement->update(['status'=>'draft', 'verified_by'=>null, 'verified_at'=>null]);
        try {
            $this->prepare($this->preparer->id);
            $this->fail('Should reject unverified settlement');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('settlement', $e->errors());
        }
    }

    public function test_verified_reconciled_settlement_creates_one_balanced_draft_without_export(): void
    {
        $this->reconcile();
        $required = ['1130','1220','5301','1210'];
        $accounts = GeneralLedgerAccount::whereIn('account_code', $required)->get()->keyBy(fn($a)=>(string)$a->account_code);
        foreach ($required as $code) {
            $this->assertTrue($accounts->has($code), "GL {$code} must exist in testing database to run positive acceptance test");
            $this->assertTrue((bool)$accounts[$code]->is_active, "GL {$code} must be active in testing");
            $this->assertTrue((bool)$accounts[$code]->allow_posting, "GL {$code} must allow posting in testing");
        }
        $beforeExports = DB::table('tally_exports')->count();
        $journal = $this->prepare($this->preparer->id);
        $this->assertSame('draft', $journal->status);
        $this->assertSame('mhis_legacy_settlement', $journal->source_type);
        $this->assertSame('draft_settlement', $journal->source_event);
        $lines = $journal->lines()->get();
        $this->assertCount(4, $lines);
        $this->assertSame('1000.00', number_format((float)$lines->sum('debit'), 2, '.', ''));
        $this->assertSame('1000.00', number_format((float)$lines->sum('credit'), 2, '.', ''));
        $this->assertSame($journal->id, $this->settlement->fresh()->journal_entry_id);
        $again = $this->prepare($this->preparer->id);
        $this->assertSame($journal->id, $again->id);
        $this->assertSame(1, DB::table('journal_entries')
            ->where('source_type','mhis_legacy_settlement')
            ->where('source_id',$this->settlement->id)
            ->where('source_event','draft_settlement')->count());
        $this->assertSame($beforeExports, DB::table('tally_exports')->count());
        $this->assertNull($journal->fresh()->posted_at);
    }
}
