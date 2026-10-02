<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\TallyLedgerMapping;
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