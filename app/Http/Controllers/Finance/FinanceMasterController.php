<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceMasterController extends Controller
{
    /**
     * Display Finance Accounts and Finance Heads.
     */
    public function index(): View
    {
        $accounts = FinanceAccount::query()
            ->orderBy('account_type')
            ->orderBy('name')
            ->get();

        $incomeHeads = FinanceHead::query()
            ->where('head_type', 'income')
            ->with('parent')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $expenseHeads = FinanceHead::query()
            ->where('head_type', 'expense')
            ->with('parent')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $parentHeads = FinanceHead::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('head_type')
            ->orderBy('name')
            ->get();

        return view('finance.master', compact(
            'accounts',
            'incomeHeads',
            'expenseHeads',
            'parentHeads'
        ));
    }

    /**
     * Create a Finance Account.
     */
    public function storeAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                'unique:finance_accounts,code',
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'account_type' => [
                'required',
                Rule::in(['cash', 'bank']),
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'account_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'branch_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'ifsc_code' => [
                'nullable',
                'string',
                'max:30',
            ],
            'opening_balance' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'opening_balance_date' => [
                'nullable',
                'date',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $validated['opening_balance'] =
            $validated['opening_balance'] ?? 0;

        $validated['created_by'] = auth()->id();
        $validated['is_active'] = true;

        FinanceAccount::create($validated);

        return back()->with(
            'success',
            'Finance account created successfully.'
        );
    }

    /**
     * Update a Finance Account.
     */
    public function updateAccount(
        Request $request,
        FinanceAccount $financeAccount
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('finance_accounts', 'code')
                    ->ignore($financeAccount->id),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'account_type' => [
                'required',
                Rule::in(['cash', 'bank']),
            ],
            'bank_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'account_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'branch_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'ifsc_code' => [
                'nullable',
                'string',
                'max:30',
            ],
            'opening_balance' => [
                'required',
                'numeric',
                'min:0',
            ],
            'opening_balance_date' => [
                'nullable',
                'date',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $financeAccount->update($validated);

        return back()->with(
            'success',
            'Finance account updated successfully.'
        );
    }

    /**
     * Create an Income or Expense Head.
     */
    public function storeHead(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->headValidationRules()
        );

        $validated = $this->prepareHeadData(
            $request,
            $validated
        );

        if (!empty($validated['parent_id'])) {
            $parent = FinanceHead::findOrFail(
                $validated['parent_id']
            );

            if ($parent->head_type !== $validated['head_type']) {
                return back()
                    ->withErrors([
                        'parent_id' =>
                            'Parent head must have the same head type.',
                    ])
                    ->withInput();
            }
        }

        $validated['code'] = strtoupper($validated['code']);
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = true;

        FinanceHead::create($validated);

        return back()->with(
            'success',
            'Finance head created successfully.'
        );
    }

    /**
     * Update an Income or Expense Head.
     */
    public function updateHead(
        Request $request,
        FinanceHead $financeHead
    ): RedirectResponse {
        $validated = $request->validate(
            $this->headValidationRules($financeHead)
        );

        $validated = $this->prepareHeadData(
            $request,
            $validated
        );

        if (
            !empty($validated['parent_id'])
            && (int) $validated['parent_id'] === $financeHead->id
        ) {
            return back()
                ->withErrors([
                    'parent_id' =>
                        'A finance head cannot be its own parent.',
                ])
                ->withInput();
        }

        if (!empty($validated['parent_id'])) {
            $parent = FinanceHead::findOrFail(
                $validated['parent_id']
            );

            if ($parent->head_type !== $validated['head_type']) {
                return back()
                    ->withErrors([
                        'parent_id' =>
                            'Parent head must have the same head type.',
                    ])
                    ->withInput();
            }
        }

        $validated['code'] = strtoupper($validated['code']);

        $financeHead->update($validated);

        return back()->with(
            'success',
            'Finance head updated successfully.'
        );
    }

    /**
     * Validation rules for Finance Heads.
     */
    private function headValidationRules(
        ?FinanceHead $financeHead = null
    ): array {
        $codeRule = Rule::unique('finance_heads', 'code');

        if ($financeHead) {
            $codeRule->ignore($financeHead->id);
        }

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                $codeRule,
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'head_type' => [
                'required',
                Rule::in(['income', 'expense']),
            ],
            'category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'cost_behavior' => [
                'nullable',
                Rule::in([
                    'fixed',
                    'variable',
                    'mixed',
                ]),
            ],
            'variable_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                Rule::requiredIf(
                    fn () =>
                        request('head_type') === 'expense'
                        && request('cost_behavior') === 'mixed'
                ),
            ],
            'include_in_break_even' => [
                'nullable',
                'boolean',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:finance_heads,id',
            ],
            'is_active' => [
                $financeHead ? 'required' : 'nullable',
                'boolean',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Normalise Finance Head classification.
     *
     * Income heads do not participate in expense
     * cost-behaviour classification.
     *
     * Fixed expense  = 0% variable.
     * Variable expense = 100% variable.
     * Mixed expense = user-defined percentage.
     */
    private function prepareHeadData(
        Request $request,
        array $validated
    ): array {
        $costBehavior =
            $validated['cost_behavior'] ?? null;

        if ($validated['head_type'] !== 'expense') {
            $validated['cost_behavior'] = null;
            $validated['variable_percentage'] = null;
            $validated['include_in_break_even'] = false;

            return $validated;
        }

        $validated['include_in_break_even'] =
            $request->boolean('include_in_break_even');

        if ($costBehavior === 'fixed') {
            $validated['variable_percentage'] = 0;
        } elseif ($costBehavior === 'variable') {
            $validated['variable_percentage'] = 100;
        } elseif ($costBehavior === 'mixed') {
            $validated['variable_percentage'] =
                round(
                    (float) $validated['variable_percentage'],
                    2
                );
        } else {
            $validated['cost_behavior'] = null;
            $validated['variable_percentage'] = null;
        }

        return $validated;
    }
}