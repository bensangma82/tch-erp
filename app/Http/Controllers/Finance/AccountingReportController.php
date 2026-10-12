<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountingReportController extends Controller
{
    private function dates(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            $data['from'] ?? now()->startOfYear()->toDateString(),
            $data['to'] ?? now()->toDateString(),
        ];
    }

    private function balances(?string $from, string $to)
    {
        $query = DB::table('journal_entry_lines as l')
            ->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('general_ledger_accounts as a', 'a.id', '=', 'l.general_ledger_account_id')
            ->where('j.status', 'posted')
            ->whereDate('j.journal_date', '<=', $to);

        if ($from !== null) {
            $query->whereDate('j.journal_date', '>=', $from);
        }

        return $query
            ->select(
                'a.id',
                'a.account_code',
                'a.account_name',
                'a.account_type',
                'a.account_group',
                'a.normal_balance'
            )
            ->selectRaw('SUM(l.debit) AS total_debit')
            ->selectRaw('SUM(l.credit) AS total_credit')
            ->groupBy(
                'a.id',
                'a.account_code',
                'a.account_name',
                'a.account_type',
                'a.account_group',
                'a.normal_balance'
            )
            ->orderBy('a.account_code')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Accounting Reports Dashboard
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('finance.accounting-reports.index');
    }

    /*
    |--------------------------------------------------------------------------
    | General Ledger
    |--------------------------------------------------------------------------
    */

    public function generalLedger(Request $request): View
    {
        [$from, $to] = $this->dates($request);

        $data = $request->validate([
            'account_id' => [
                'nullable',
                'integer',
                'exists:general_ledger_accounts,id',
            ],
        ]);

        $accounts = GeneralLedgerAccount::query()
            ->orderBy('account_code')
            ->get();

        $account = null;
        $lines = collect();
        $openingBalance = 0.0;
        $closingBalance = 0.0;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        if (!empty($data['account_id'])) {
            $account = GeneralLedgerAccount::findOrFail($data['account_id']);

            $opening = DB::table('journal_entry_lines as l')
                ->join('journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
                ->where('j.status', 'posted')
                ->where('l.general_ledger_account_id', $account->id)
                ->whereDate('j.journal_date', '<', $from)
                ->selectRaw('COALESCE(SUM(l.debit - l.credit), 0) AS balance')
                ->value('balance');

            $openingBalance = (float) $opening;

            $lines = JournalEntryLine::query()
                ->with('journalEntry')
                ->where('general_ledger_account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($from, $to) {
                    $query->where('status', 'posted')
                        ->whereDate('journal_date', '>=', $from)
                        ->whereDate('journal_date', '<=', $to);
                })
                ->get()
                ->sortBy(fn ($line) => sprintf(
                    '%s-%012d-%06d',
                    $line->journalEntry->journal_date->format('Ymd'),
                    $line->journal_entry_id,
                    $line->line_no
                ))
                ->values();

            $running = $openingBalance;

            $lines = $lines->map(function ($line) use (&$running) {
                $running += (float) $line->debit - (float) $line->credit;
                $line->running_balance = $running;

                return $line;
            });

            $totalDebit = $lines->sum(fn ($line) => (float) $line->debit);
            $totalCredit = $lines->sum(fn ($line) => (float) $line->credit);
            $closingBalance = $running;
        }

        return view('finance.accounting-reports.general-ledger', compact(
            'accounts',
            'account',
            'lines',
            'from',
            'to',
            'openingBalance',
            'closingBalance',
            'totalDebit',
            'totalCredit'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Trial Balance
    |--------------------------------------------------------------------------
    */

    public function trialBalance(Request $request): View
    {
        $data = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        $asOf = $data['as_of'] ?? now()->toDateString();

        $accounts = $this->balances(null, $asOf)
            ->map(function ($account) {
                $balance = round(
                    (float) $account->total_debit
                    - (float) $account->total_credit,
                    2
                );

                $account->closing_debit = max($balance, 0);
                $account->closing_credit = max(-$balance, 0);

                return $account;
            });

        $totalDebit = $accounts->sum('closing_debit');
        $totalCredit = $accounts->sum('closing_credit');

        return view('finance.accounting-reports.trial-balance', compact(
            'accounts',
            'asOf',
            'totalDebit',
            'totalCredit'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Income and Expenditure
    |--------------------------------------------------------------------------
    */

    public function incomeExpenditure(Request $request): View
    {
        [$from, $to] = $this->dates($request);

        $accounts = $this->balances($from, $to);

        $income = $accounts
            ->where('account_type', 'income')
            ->values()
            ->map(function ($account) {
                $account->amount = round(
                    (float) $account->total_credit
                    - (float) $account->total_debit,
                    2
                );

                return $account;
            });

        $expenses = $accounts
            ->where('account_type', 'expense')
            ->values()
            ->map(function ($account) {
                $account->amount = round(
                    (float) $account->total_debit
                    - (float) $account->total_credit,
                    2
                );

                return $account;
            });

        $totalIncome = $income->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $surplus = $totalIncome - $totalExpense;

        return view('finance.accounting-reports.income-expenditure', compact(
            'income',
            'expenses',
            'from',
            'to',
            'totalIncome',
            'totalExpense',
            'surplus'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Balance Sheet
    |--------------------------------------------------------------------------
    */

    public function balanceSheet(Request $request): View
    {
        $data = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        $asOf = $data['as_of'] ?? now()->toDateString();

        $accounts = $this->balances(null, $asOf);

        $assets = $accounts
            ->where('account_type', 'asset')
            ->values()
            ->map(function ($account) {
                $account->amount = round(
                    (float) $account->total_debit
                    - (float) $account->total_credit,
                    2
                );

                return $account;
            });

        $liabilities = $accounts
            ->where('account_type', 'liability')
            ->values()
            ->map(function ($account) {
                $account->amount = round(
                    (float) $account->total_credit
                    - (float) $account->total_debit,
                    2
                );

                return $account;
            });

        $equity = $accounts
            ->where('account_type', 'equity')
            ->values()
            ->map(function ($account) {
                $account->amount = round(
                    (float) $account->total_credit
                    - (float) $account->total_debit,
                    2
                );

                return $account;
            });

        $totalIncome = $accounts
            ->where('account_type', 'income')
            ->sum(fn ($a) =>
                (float) $a->total_credit - (float) $a->total_debit
            );

        $totalExpense = $accounts
            ->where('account_type', 'expense')
            ->sum(fn ($a) =>
                (float) $a->total_debit - (float) $a->total_credit
            );

        $accumulatedSurplus = $totalIncome - $totalExpense;

        $totalAssets = $assets->sum('amount');

        $totalLiabilities = $liabilities->sum('amount');

        $totalEquity = $equity->sum('amount')
            + $accumulatedSurplus;

        $difference = round(
            $totalAssets - $totalLiabilities - $totalEquity,
            2
        );

        return view('finance.accounting-reports.balance-sheet', compact(
            'assets',
            'liabilities',
            'equity',
            'asOf',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'accumulatedSurplus',
            'difference'
        ));
    }
}
