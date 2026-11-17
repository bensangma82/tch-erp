<?php

namespace App\Services\Budget;

use App\Models\{AnnualBudgetLine, GeneralLedgerAccount, User};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnualBudgetService
{
    public static function paise($amount): int
    {
        $value = (string) $amount;
        if (! preg_match('/^-?\d{1,14}(?:\.\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages(['amount' => 'Enter a valid amount with at most two decimal places.']);
        }
        $negative = str_starts_with($value, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-'), 2), 2, '0');
        return ($negative ? -1 : 1) * ((int) $whole * 100 + (int) str_pad($fraction, 2, '0'));
    }

    public static function decimal(int $paise): string
    {
        return ($paise < 0 ? '-' : '').intdiv(abs($paise), 100).'.'.str_pad((string) (abs($paise) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function currentYear(): int
    {
        $date = now();
        return $date->month >= 4 ? $date->year : $date->year - 1;
    }

    public static function dates(int $year): array
    {
        if ($year < 2000 || $year > 2100) abort(422, 'Choose a financial year between 2000 and 2100.');
        return [sprintf('%04d-04-01', $year), sprintf('%04d-04-01', $year + 1)];
    }

    public function authorize(User $user, bool $manage = false): void
    {
        abort_unless($user->is_active && ($user->hasPermission('finance.budgets.manage')
            || (! $manage && $user->hasPermission('finance.budgets.view'))), 403);
    }

    // Match fixed-asset groups, including parent groups; do not treat bank, inventory or receivables as capital spending.
    public function category(GeneralLedgerAccount $gl, $accounts): ?string
    {
        if ($gl->account_type === 'expense') return 'recurring';
        if ($gl->account_type !== 'asset' || $gl->normal_balance !== 'debit' || $gl->finance_account_id !== null) return null;
        $node = $gl; $visited = [];
        while ($node && ! isset($visited[$node->id])) {
            $visited[$node->id] = true;
            foreach ([$node->account_group, $node->tally_group_name] as $group) {
                $group = strtolower(trim(preg_replace('/\s+/', ' ', (string) $group)));
                if (in_array($group, ['fixed assets', 'fixed asset', 'capital work in progress', 'capital work-in-progress', 'capital work-in progress', 'cwip'], true)) return 'capital';
            }
            $node = $accounts->get($node->parent_id);
        }
        return null;
    }

    public function eligibleAccounts()
    {
        $all = GeneralLedgerAccount::with('financeHead')->orderBy('account_code')->get()->keyBy('id');
        return $all->filter(fn ($gl) => $gl->is_active && $gl->allow_posting && $gl->normal_balance === 'debit'
            && $this->category($gl, $all) !== null)
            ->map(fn ($gl) => ['ledger' => $gl, 'category' => $this->category($gl, $all)]);
    }

    public function allocate(User $user, int $year, array $allocations): int
    {
        $this->authorize($user, true); self::dates($year);
        $eligible = $this->eligibleAccounts(); $prepared = [];
        foreach ($allocations as $id => $amount) {
            if ($amount === null || $amount === '') continue;
            $item = $eligible->get($id);
            if (! $item) throw ValidationException::withMessages(['allocations' => 'Choose an active posting expense or fixed-asset account.']);
            $paise = self::paise($amount);
            if ($paise < 0) throw ValidationException::withMessages(['allocations' => 'A budget cannot be negative.']);
            $prepared[$id] = ['category' => $item['category'], 'amount' => self::decimal($paise)];
        }
        if (! $prepared) throw ValidationException::withMessages(['allocations' => 'Enter at least one annual budget. Zero is allowed.']);
        return DB::transaction(function () use ($user, $year, $prepared) {
            // Lock ledgers in a stable order, serialising concurrent first allocations as well as revisions.
            $ids = array_keys($prepared); sort($ids, SORT_NUMERIC);
            GeneralLedgerAccount::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if (AnnualBudgetLine::where('start_year', $year)->whereIn('general_ledger_account_id', $ids)->exists()) {
                throw ValidationException::withMessages(['allocations' => 'An allocation already exists. Refresh the page and use Revise budget.']);
            }
            foreach ($prepared as $id => $value) {
                AnnualBudgetLine::create(['start_year' => $year, 'general_ledger_account_id' => $id,
                    'category' => $value['category'], 'original_amount' => $value['amount'],
                    'current_amount' => $value['amount'], 'created_by' => $user->id]);
            }
            return count($prepared);
        });
    }

    public function revise(User $user, int $id, string $amount, string $expected, string $reason): void
    {
        $this->authorize($user, true);
        $paise = self::paise($amount); $expectedPaise = self::paise($expected);
        if ($paise < 0 || strlen(trim($reason)) < 5 || strlen($reason) > 2000) {
            throw ValidationException::withMessages(['revision' => 'Enter a non-negative budget and a reason (5–2000 characters).']);
        }
        DB::transaction(function () use ($user, $id, $paise, $expectedPaise, $reason) {
            $line = AnnualBudgetLine::whereKey($id)->lockForUpdate()->firstOrFail();
            if (self::paise($line->current_amount) !== $expectedPaise) {
                throw ValidationException::withMessages(['revision' => 'The allocation changed. Refresh before revising it.']);
            }
            if ($paise === $expectedPaise) throw ValidationException::withMessages(['revision' => 'Enter a different revised amount.']);
            $line->revisions()->create(['previous_amount' => $line->current_amount, 'new_amount' => self::decimal($paise),
                'reason' => trim($reason), 'actor_id' => $user->id, 'created_at' => now()]);
            $line->update(['current_amount' => self::decimal($paise)]);
        });
    }

    public function movements(int $year)
    {
        [$from, $until] = self::dates($year);
        return DB::table('journal_entry_lines as l')->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->where('j.status', 'posted')->where('j.journal_date', '>=', $from)->where('j.journal_date', '<', $until);
    }

    public function report(int $year): array
    {
        [$from, $until] = self::dates($year);
        $lines = AnnualBudgetLine::with('ledger.financeHead')->where('start_year', $year)->orderBy('id')->get();
        $actuals = $this->movements($year)->selectRaw('l.general_ledger_account_id as gl_id, SUM(l.debit - l.credit) as actual')
            ->groupBy('l.general_ledger_account_id')->get()->keyBy('gl_id');
        $rows = $lines->map(function ($line) use ($actuals) {
            $actual = self::paise($actuals->get($line->general_ledger_account_id)->actual ?? '0');
            $budget = self::paise($line->current_amount);
            return ['line' => $line, 'budget' => $budget, 'actual' => $actual, 'remaining' => $budget - $actual];
        });
        $totals = [];
        foreach (['recurring', 'capital'] as $category) {
            $subset = $rows->where('line.category', $category);
            $totals[$category] = ['budget' => $subset->sum('budget'), 'actual' => $subset->sum('actual'), 'remaining' => $subset->sum('remaining')];
        }
        // Include inactive or subsequently regrouped expense ledgers with activity, so unmapped spending is visible.
        $all = GeneralLedgerAccount::orderBy('account_code')->get()->keyBy('id');
        $budgeted = $lines->pluck('general_ledger_account_id')->all();
        $unbudgeted = collect();
        foreach ($actuals as $id => $activity) {
            $gl = $all->get($id); $amount = self::paise($activity->actual);
            if ($gl && ! in_array((int) $id, $budgeted, true) && $amount !== 0 && ($category = $this->category($gl, $all))) {
                $unbudgeted->push(['ledger' => $gl, 'category' => $category, 'actual' => $amount]);
            }
        }
        return compact('year', 'from', 'until', 'rows', 'totals', 'unbudgeted');
    }

    public function monthly(AnnualBudgetLine $line): array
    {
        [$from] = self::dates($line->start_year); $start = CarbonImmutable::parse($from); $result = [];
        $actuals = $this->movements($line->start_year)->where('l.general_ledger_account_id', $line->general_ledger_account_id)
            ->selectRaw("EXTRACT(MONTH FROM j.journal_date) as month, SUM(l.debit - l.credit) as actual")
            ->groupByRaw('EXTRACT(MONTH FROM j.journal_date)')->get()->keyBy('month');
        $budget = self::paise($line->current_amount); $base = intdiv($budget, 12); $remainder = $budget % 12;
        for ($i = 0; $i < 12; $i++) {
            $month = $start->addMonths($i);
            $result[] = ['month' => $month->format('M Y'),
                'plan' => $line->category === 'recurring' ? $base + ($i < $remainder ? 1 : 0) : null,
                'actual' => self::paise($actuals->get($month->month)->actual ?? '0')];
        }
        return $result;
    }
}
