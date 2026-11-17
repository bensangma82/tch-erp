<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AnnualBudgetLine;
use App\Services\Budget\AnnualBudgetService;
use Illuminate\Http\Request;

class AnnualBudgetController extends Controller
{
    public function index(Request $request, AnnualBudgetService $service)
    {
        $service->authorize($request->user());
        $input = $request->validate(['year' => 'nullable|integer|min:2000|max:2100']);
        $year = (int) ($input['year'] ?? AnnualBudgetService::currentYear());
        $report = $service->report($year);
        $canManage = $request->user()->hasPermission('finance.budgets.manage');
        $allocated = $report['rows']->pluck('line.general_ledger_account_id')->all();
        $available = $service->eligibleAccounts()->reject(fn ($item, $id) => in_array((int) $id, $allocated, true));
        return view('finance.budgets.index', compact('report', 'canManage', 'available', 'year'));
    }

    public function store(Request $request, AnnualBudgetService $service)
    {
        $service->authorize($request->user(), true);
        $data = $request->validate(['year' => 'required|integer|min:2000|max:2100',
            'allocations' => 'required|array|max:500', 'allocations.*' => 'nullable|regex:/^\d{1,14}(?:\.\d{1,2})?$/']);
        $count = $service->allocate($request->user(), (int) $data['year'], $data['allocations']);
        return redirect()->route('finance.budgets.index', ['year' => $data['year']])->with('status', $count.' annual budget allocations saved.');
    }

    public function show(Request $request, int $budget, AnnualBudgetService $service)
    {
        $service->authorize($request->user());
        $line = AnnualBudgetLine::with(['ledger', 'creator', 'revisions.actor'])->findOrFail($budget);
        $monthly = $service->monthly($line);
        $actual = array_sum(array_column($monthly, 'actual'));
        $transactions = $service->movements($line->start_year)->where('l.general_ledger_account_id', $line->general_ledger_account_id)
            ->select('j.id', 'j.journal_no', 'j.journal_date', 'j.source_type', 'j.reference_no', 'l.id as line_id', 'l.debit', 'l.credit', 'l.description')
            ->orderByDesc('j.journal_date')->orderByDesc('l.id')->paginate(50);
        $canManage = $request->user()->hasPermission('finance.budgets.manage');
        return view('finance.budgets.show', compact('line', 'monthly', 'actual', 'transactions', 'canManage'));
    }

    public function revise(Request $request, int $budget, AnnualBudgetService $service)
    {
        $service->authorize($request->user(), true);
        $data = $request->validate(['amount' => 'required|string', 'expected_amount' => 'required|string', 'reason' => 'required|string|min:5|max:2000']);
        $service->revise($request->user(), $budget, $data['amount'], $data['expected_amount'], $data['reason']);
        return redirect()->route('finance.budgets.show', $budget)->with('status', 'Revised budget saved. Original allocation and audit history retained.');
    }

    public function csv(Request $request, AnnualBudgetService $service)
    {
        $service->authorize($request->user());
        $input = $request->validate(['year' => 'required|integer|min:2000|max:2100']);
        $report = $service->report((int) $input['year']);
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['FY start year', 'Category', 'GL code', 'Account', 'Original budget', 'Current budget', 'Used (net)', 'Available', 'Allocation status']);
            $safe = static fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : $v;
            foreach ($report['rows'] as $row) {
                $line = $row['line'];
                fputcsv($out, [$report['year'], $line->category, $safe($line->ledger->account_code), $safe($line->ledger->account_name),
                    $line->original_amount, $line->current_amount, AnnualBudgetService::decimal($row['actual']), AnnualBudgetService::decimal($row['remaining']), 'Budgeted']);
            }
            foreach ($report['unbudgeted'] as $row) {
                fputcsv($out, [$report['year'], $row['category'], $safe($row['ledger']->account_code), $safe($row['ledger']->account_name), '', '', AnnualBudgetService::decimal($row['actual']), '', 'No budget']);
            }
            fclose($out);
        }, 'TCH-budget-'.$report['year'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
