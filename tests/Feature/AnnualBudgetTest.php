<?php

namespace Tests\Feature;

use App\Models\{AnnualBudgetLine, GeneralLedgerAccount, JournalEntry, Permission, User};
use App\Services\Accounting\JournalPostingService;
use App\Services\Budget\AnnualBudgetService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{DB, Route};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AnnualBudgetTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName(), 'Use the dedicated testing database.');
        $staged = dirname(__DIR__, 2);
        if (is_dir($staged.'/resources/views')) app('view')->getFinder()->prependLocation($staged.'/resources/views');
        if (! Route::has('finance.budgets.index')) {
            require $staged.'/routes/annual-budgets.php';
            Route::getRoutes()->refreshNameLookups();
        }
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email_verified_at' => now()]));
    }

    private function gl(string $type = 'expense', string $group = 'Operating Expenses'): GeneralLedgerAccount
    {
        return GeneralLedgerAccount::create(['account_code' => 'BT'.bin2hex(random_bytes(4)), 'account_name' => 'Budget test '.bin2hex(random_bytes(4)),
            'account_type' => $type, 'account_group' => $group, 'normal_balance' => $type === 'liability' ? 'credit' : 'debit',
            'is_active' => true, 'allow_posting' => true]);
    }

    private function budget(GeneralLedgerAccount $gl, string $amount = '10000.00'): AnnualBudgetLine
    {
        app(AnnualBudgetService::class)->allocate(auth()->user(), 2026, [$gl->id => $amount]);
        return AnnualBudgetLine::where('start_year', 2026)->where('general_ledger_account_id', $gl->id)->firstOrFail();
    }

    private function journal(GeneralLedgerAccount $debit, GeneralLedgerAccount $credit, string $amount, string $date = '2026-05-01', bool $post = true): JournalEntry
    {
        return DB::transaction(function () use ($debit, $credit, $amount, $date, $post) {
            $journal = JournalEntry::create(['journal_no' => 'BUD-'.bin2hex(random_bytes(6)), 'journal_date' => $date,
                'source_type' => 'budget_test', 'source_id' => random_int(100000000, 999999999), 'source_event' => 'posted',
                'status' => 'draft', 'created_by' => auth()->id(), 'narration' => 'Budget acceptance test']);
            $journal->lines()->createMany([
                ['line_no' => 1, 'general_ledger_account_id' => $debit->id, 'debit' => $amount, 'credit' => '0.00'],
                ['line_no' => 2, 'general_ledger_account_id' => $credit->id, 'debit' => '0.00', 'credit' => $amount],
            ]);
            return $post ? app(JournalPostingService::class)->post($journal) : $journal;
        });
    }

    private function row(AnnualBudgetLine $line): array
    {
        return app(AnnualBudgetService::class)->report($line->start_year)['rows']->firstWhere('line.id', $line->id);
    }

    public function test_allocations_do_not_post_accounting_and_both_categories_are_shown(): void
    {
        $expense = $this->gl(); $asset = $this->gl('asset', 'Fixed Assets');
        $before = JournalEntry::count();
        $this->post(route('finance.budgets.store'), ['year' => 2026, 'allocations' => [$expense->id => '10000', $asset->id => '500000']])->assertRedirect();
        $this->assertSame($before, JournalEntry::count());
        $this->get(route('finance.budgets.index', ['year' => 2026]))->assertOk()->assertSee($asset->account_name)->assertSee('500,000.00')->assertSee('10,000.00');
    }

    public function test_posted_expense_uses_budget_and_supplier_payment_does_not_use_it_twice(): void
    {
        $expense = $this->gl(); $line = $this->budget($expense); $payable = $this->gl('liability'); $bank = $this->gl('asset', 'Bank Accounts');
        $this->journal($expense, $payable, '2500.25');
        $this->journal($payable, $bank, '2500.25');
        $this->assertSame(250025, $this->row($line)['actual']);
        $this->assertSame(749975, $this->row($line)['remaining']);
    }

    public function test_draft_expense_is_excluded_until_posted(): void
    {
        $expense = $this->gl(); $line = $this->budget($expense);
        $journal = $this->journal($expense, $this->gl('liability'), '200.00', '2026-05-01', false);
        $this->assertSame(0, $this->row($line)['actual']);
        app(JournalPostingService::class)->post($journal);
        $this->assertSame(20000, $this->row($line)['actual']);
    }

    public function test_credit_reversal_restores_budget(): void
    {
        $expense = $this->gl(); $line = $this->budget($expense); $payable = $this->gl('liability');
        $original = $this->journal($expense, $payable, '1200');
        $reversal = $this->journal($payable, $expense, '1200', '2026-05-02', false);
        $reversal->update(['reversal_of_id' => $original->id]);
        app(JournalPostingService::class)->post($reversal);
        $this->assertSame(0, $this->row($line)['actual']);
        $this->assertSame(1000000, $this->row($line)['remaining']);
    }

    public function test_financial_year_boundaries_and_prior_year_supplier_payment(): void
    {
        $expense = $this->gl(); $line = $this->budget($expense); $payable = $this->gl('liability'); $bank = $this->gl('asset', 'Bank Accounts');
        foreach (['2026-03-31' => '900', '2026-04-01' => '100', '2027-03-31' => '200', '2027-04-01' => '800'] as $date => $amount) $this->journal($expense, $payable, $amount, $date);
        $this->journal($payable, $bank, '900', '2026-04-01');
        $this->assertSame(30000, $this->row($line)['actual']);
    }

    public function test_capital_purchase_uses_asset_budget_and_does_not_touch_operating_budget(): void
    {
        $asset = $this->gl('asset', 'Fixed Assets'); $capital = $this->budget($asset, '500000'); $operating = $this->budget($this->gl());
        $this->journal($asset, $this->gl('liability'), '125000');
        $this->assertSame('capital', $capital->category);
        $this->assertSame(12500000, $this->row($capital)['actual']);
        $this->assertSame(0, $this->row($operating)['actual']);
    }

    public function test_fixed_asset_parent_group_is_supported(): void
    {
        $parent = $this->gl('asset', 'Fixed Assets'); $child = $this->gl('asset', 'Medical Equipment'); $child->update(['parent_id' => $parent->id]);
        $this->assertSame('capital', $this->budget($child)->category);
    }

    public function test_bank_and_inventory_cannot_be_selected_for_capital_budget(): void
    {
        foreach (['Bank Accounts', 'Stock-in-Hand', 'Sundry Debtors'] as $group) {
            $gl = $this->gl('asset', $group);
            $this->post(route('finance.budgets.store'), ['year' => 2026, 'allocations' => [$gl->id => '1000']])->assertSessionHasErrors('allocations');
        }
        $this->assertSame(0, AnnualBudgetLine::count());
    }

    public function test_duplicate_allocations_are_blocked_atomically(): void
    {
        $gl = $this->gl(); $other = $this->gl(); $this->budget($gl);
        $this->post(route('finance.budgets.store'), ['year' => 2026, 'allocations' => [$gl->id => '99', $other->id => '99']])->assertSessionHasErrors('allocations');
        $this->assertDatabaseMissing('annual_budget_lines', ['general_ledger_account_id' => $other->id]);
    }

    public function test_over_budget_is_visible_and_does_not_block_posting(): void
    {
        $gl = $this->gl(); $line = $this->budget($gl, '100'); $this->journal($gl, $this->gl('liability'), '150');
        $this->assertSame(-5000, $this->row($line)['remaining']);
        $this->get(route('finance.budgets.index', ['year' => 2026]))->assertOk()->assertSee('Over budget')->assertSee('-50.00');
    }

    public function test_unbudgeted_spending_is_visible(): void
    {
        $gl = $this->gl(); $this->journal($gl, $this->gl('liability'), '555.50');
        $this->get(route('finance.budgets.index', ['year' => 2026]))->assertOk()->assertSee('Spending without an annual allocation')->assertSee('555.50');
    }

    public function test_revision_preserves_original_and_audits_actor_and_reason(): void
    {
        $line = $this->budget($this->gl());
        $this->post(route('finance.budgets.revise', $line->id), ['amount' => '12000', 'expected_amount' => '10000.00', 'reason' => 'Additional approved allocation'])->assertRedirect();
        $line->refresh();
        $this->assertSame('10000.00', $line->original_amount); $this->assertSame('12000.00', $line->current_amount);
        $this->assertDatabaseHas('annual_budget_revisions', ['annual_budget_line_id' => $line->id, 'actor_id' => auth()->id(), 'reason' => 'Additional approved allocation', 'previous_amount' => '10000.00', 'new_amount' => '12000.00']);
        $this->get(route('finance.budgets.show', $line->id))->assertOk()->assertSee('Additional approved allocation');
    }

    public function test_stale_revision_does_not_overwrite_newer_budget(): void
    {
        $line = $this->budget($this->gl()); $s = app(AnnualBudgetService::class);
        $s->revise(auth()->user(), $line->id, '12000', '10000', 'Approved increase');
        $this->post(route('finance.budgets.revise', $line->id), ['amount' => '8000', 'expected_amount' => '10000', 'reason' => 'Stale browser form'])->assertSessionHasErrors('revision');
        $this->assertSame('12000.00', $line->fresh()->current_amount);
        $this->assertSame(1, $line->revisions()->count());
    }

    public function test_monthly_rounding_totals_exactly_and_capital_has_no_monthly_plan(): void
    {
        $line = $this->budget($this->gl(), '100.01'); $s = app(AnnualBudgetService::class);
        $monthly = $s->monthly($line);
        $this->assertCount(12, $monthly); $this->assertSame('Apr 2026', $monthly[0]['month']);
        $this->assertSame('Mar 2027', $monthly[11]['month']);
        $this->assertSame(10001, array_sum(array_column($monthly, 'plan')));
        $capital = $this->budget($this->gl('asset', 'Fixed Assets'));
        $this->assertNull($s->monthly($capital)[0]['plan']);
    }

    public function test_view_permission_does_not_allow_changes(): void
    {
        $line = $this->budget($this->gl());
        $viewer = User::factory()->create(['role' => 'billing', 'is_active' => true, 'email_verified_at' => now()]);
        $permission = Permission::where('name', 'finance.budgets.view')->firstOrFail(); $viewer->permissions()->attach($permission->id);
        $this->actingAs($viewer);
        $this->get(route('finance.budgets.index'))->assertOk();
        $this->post(route('finance.budgets.revise', $line->id), ['amount' => '5', 'expected_amount' => '10000', 'reason' => 'Denied change'])->assertForbidden();
    }

    public function test_unauthorized_user_is_denied_and_guest_redirected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'billing', 'is_active' => true, 'email_verified_at' => now()]));
        $this->get(route('finance.budgets.index'))->assertForbidden();
        $this->post(route('finance.budgets.store'), [])->assertForbidden();
        auth()->logout(); $this->get(route('finance.budgets.index'))->assertRedirect();
    }

    public function test_invalid_amounts_are_rejected_and_zero_is_supported(): void
    {
        $gl = $this->gl();
        foreach (['-1', '1.001', '1e3'] as $value) $this->post(route('finance.budgets.store'), ['year' => 2026, 'allocations' => [$gl->id => $value]])->assertSessionHasErrors();
        $this->assertSame('0.00', $this->budget($gl, '0')->current_amount);
    }

    public function test_csv_includes_unbudgeted_spending_and_budget_rows(): void
    {
        $gl = $this->gl(); $this->budget($gl); $other = $this->gl(); $this->journal($other, $this->gl('liability'), '200');
        $response = $this->get(route('finance.budgets.csv', ['year' => 2026]))->assertOk();
        $this->assertStringContainsString('No budget', $response->streamedContent());
    }
}
