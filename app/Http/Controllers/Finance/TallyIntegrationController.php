<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\TallyLedgerMapping;
use App\Services\Tally\TallyQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TallyIntegrationController extends Controller
{
    /**
     * Display ERP Finance → Tally ledger mappings.
     */
    public function index(): View
    {
        $financeHeads = FinanceHead::query()
            ->with('tallyMapping')
            ->orderBy('head_type')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $financeAccounts = FinanceAccount::query()
            ->with('tallyMapping')
            ->orderBy('account_type')
            ->orderBy('name')
            ->get();

        return view('finance.tally.index', [
            'financeHeads' => $financeHeads,
            'financeAccounts' => $financeAccounts,
        ]);
    }

    /**
     * Display the current Trial Balance retrieved from Tally.
     */
    public function trialBalance(
        TallyQueryService $tallyQueryService
    ): View {
        $trialBalance = [];
        $error = null;

        try {
            $trialBalance = $tallyQueryService->trialBalance();
        } catch (\Throwable $exception) {
            report($exception);

            $error = 'Unable to retrieve the Trial Balance from Tally. Please ensure TallyPrime is running and the configured company is open.';
        }

        $totalDebit = array_sum(
            array_column($trialBalance, 'debit')
        );

        $totalCredit = array_sum(
            array_column($trialBalance, 'credit')
        );

        return view('finance.tally.trial-balance', [
            'trialBalance' => $trialBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'difference' => $totalDebit - $totalCredit,
            'error' => $error,
        ]);
    }

    /**
     * Display the current Income & Expenditure report retrieved from Tally.
     */
    public function incomeAndExpenditure(
        TallyQueryService $tallyQueryService
    ): View {
        $report = [
            'income' => [],
            'expenditure' => [],
            'total_income' => 0.0,
            'total_expenditure' => 0.0,
            'surplus_deficit' => 0.0,
        ];

        $error = null;

        try {
            $report = $tallyQueryService->incomeAndExpenditure();
        } catch (\Throwable $exception) {
            report($exception);

            $error = 'Unable to retrieve the Income & Expenditure report from Tally. Please ensure TallyPrime is running and the configured company is open.';
        }

        return view('finance.tally.income-expenditure', [
            'income' => $report['income'],
            'expenditure' => $report['expenditure'],
            'totalIncome' => $report['total_income'],
            'totalExpenditure' => $report['total_expenditure'],
            'surplusDeficit' => $report['surplus_deficit'],
            'error' => $error,
        ]);
    }

    /**
     * Display the current Balance Sheet retrieved from Tally.
     */
    public function balanceSheet(
        TallyQueryService $tallyQueryService
    ): View {
        $report = [
            'assets' => [],
            'liabilities' => [],
            'current_profit_loss' => 0.0,
            'total_assets' => 0.0,
            'total_liabilities' => 0.0,
            'difference' => 0.0,
        ];

        $error = null;

        try {
            $report = $tallyQueryService->balanceSheet();
        } catch (\Throwable $exception) {
            report($exception);

            $error = 'Unable to retrieve the Balance Sheet from Tally. Please ensure TallyPrime is running and the configured company is open.';
        }

        return view('finance.tally.balance-sheet', [
            'assets' => $report['assets'],
            'liabilities' => $report['liabilities'],
            'currentProfitLoss' => $report['current_profit_loss'],
            'totalAssets' => $report['total_assets'],
            'totalLiabilities' => $report['total_liabilities'],
            'difference' => $report['difference'],
            'error' => $error,
        ]);
    }

    /**
     * Display the current Cash Flow report retrieved from Tally.
     */
    public function cashFlow(
        TallyQueryService $tallyQueryService
    ): View {
        $report = [
            'operating' => [],
            'investing' => [],
            'financing' => [],
            'unclassified' => [],
            'total_operating' => 0.0,
            'total_investing' => 0.0,
            'total_financing' => 0.0,
            'total_unclassified' => 0.0,
            'net_cash_flow' => 0.0,
        ];

        $error = null;

        try {
            $report = $tallyQueryService->cashFlow();
        } catch (\Throwable $exception) {
            report($exception);

            $error = 'Unable to retrieve the Cash Flow report from Tally. Please ensure TallyPrime is running and the configured company is open.';
        }

        return view('finance.tally.cash-flow', [
            'operating' => $report['operating'],
            'investing' => $report['investing'],
            'financing' => $report['financing'],
            'unclassified' => $report['unclassified'],
            'totalOperating' => $report['total_operating'],
            'totalInvesting' => $report['total_investing'],
            'totalFinancing' => $report['total_financing'],
            'totalUnclassified' => $report['total_unclassified'],
            'netCashFlow' => $report['net_cash_flow'],
            'error' => $error,
        ]);
    }

    /**
     * Create or update the Tally mapping for a Finance Head.
     */
    public function saveHead(
        Request $request,
        FinanceHead $financeHead
    ): RedirectResponse {
        $validated = $request->validate([
            'tally_ledger_name' => [
                'required',
                'string',
                'max:200',
            ],
            'tally_group_name' => [
                'nullable',
                'string',
                'max:200',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        TallyLedgerMapping::updateOrCreate(
            [
                'finance_head_id' => $financeHead->id,
            ],
            [
                'finance_account_id' => null,
                'tally_ledger_name' => trim(
                    $validated['tally_ledger_name']
                ),
                'tally_group_name' => isset(
                    $validated['tally_group_name']
                )
                    ? trim($validated['tally_group_name'])
                    : null,
                'is_active' => $request->boolean('is_active'),
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        return back()->with(
            'success',
            'Tally ledger mapping saved successfully.'
        );
    }

    /**
     * Create or update the Tally mapping for a Finance Account.
     */
    public function saveAccount(
        Request $request,
        FinanceAccount $financeAccount
    ): RedirectResponse {
        $validated = $request->validate([
            'tally_ledger_name' => [
                'required',
                'string',
                'max:200',
            ],
            'tally_group_name' => [
                'nullable',
                'string',
                'max:200',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        TallyLedgerMapping::updateOrCreate(
            [
                'finance_account_id' => $financeAccount->id,
            ],
            [
                'finance_head_id' => null,
                'tally_ledger_name' => trim(
                    $validated['tally_ledger_name']
                ),
                'tally_group_name' => isset(
                    $validated['tally_group_name']
                )
                    ? trim($validated['tally_group_name'])
                    : null,
                'is_active' => $request->boolean('is_active'),
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        return back()->with(
            'success',
            'Tally account mapping saved successfully.'
        );
    }
}
