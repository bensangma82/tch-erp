<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\GeneralLedgerAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GeneralLedgerAccountController extends Controller
{
    private const ACCOUNT_TYPES = [
        'asset',
        'liability',
        'equity',
        'income',
        'expense',
    ];

    private const NORMAL_BALANCES = [
        'debit',
        'credit',
    ];

    /*
    |--------------------------------------------------------------------------
    | Chart of Accounts
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $query = GeneralLedgerAccount::query()
            ->with('parent');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'ilike', "%{$search}%")
                    ->orWhere('account_name', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('account_type')) {
            $query->where(
                'account_type',
                $request->input('account_type')
            );
        }

        $accounts = $query
            ->orderBy('account_code')
            ->paginate(25)
            ->withQueryString();

        return view('finance.gl-accounts.index', compact('accounts'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create Account
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        return view('finance.gl-accounts.create', [
            'parents' => GeneralLedgerAccount::query()
                ->orderBy('account_code')
                ->get(),

            'financeHeads' => FinanceHead::query()
                ->orderBy('name')
                ->get(),

            'financeAccounts' => FinanceAccount::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Account
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAccount($request);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['allow_posting'] = $request->boolean('allow_posting');

        DB::transaction(function () use ($validated) {
            $this->validateHierarchy($validated);

            GeneralLedgerAccount::create($validated);
        });

        return redirect()
            ->route('finance.gl-accounts.index')
            ->with('success', 'General Ledger account created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Account
    |--------------------------------------------------------------------------
    */

    public function edit(GeneralLedgerAccount $generalLedgerAccount): View
    {
        return view('finance.gl-accounts.edit', [
            'account' => $generalLedgerAccount,

            'parents' => GeneralLedgerAccount::query()
                ->whereKeyNot($generalLedgerAccount->id)
                ->orderBy('account_code')
                ->get(),

            'financeHeads' => FinanceHead::query()
                ->orderBy('name')
                ->get(),

            'financeAccounts' => FinanceAccount::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Account
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        GeneralLedgerAccount $generalLedgerAccount
    ): RedirectResponse {
        $validated = $this->validateAccount(
            $request,
            $generalLedgerAccount
        );

        $validated['is_active'] = $request->boolean('is_active');
        $validated['allow_posting'] = $request->boolean('allow_posting');

        DB::transaction(function () use (
            $generalLedgerAccount,
            $validated
        ) {
            $account = GeneralLedgerAccount::query()
                ->lockForUpdate()
                ->findOrFail($generalLedgerAccount->id);

            $this->validateHierarchy($validated, $account);

            /*
            | Protect accounts already used in journals.
            | Financial identity and classification must remain stable.
            */

            if ($account->journalLines()->exists()) {
                $protected = [
                    'account_code',
                    'account_type',
                    'normal_balance',
                    'parent_id',
                    'finance_head_id',
                    'finance_account_id',
                ];

                foreach ($protected as $field) {
                    if (
                        (string) ($account->{$field} ?? '')
                        !== (string) ($validated[$field] ?? '')
                    ) {
                        throw ValidationException::withMessages([
                            $field => 'This field cannot be changed because the account has journal entries.',
                        ]);
                    }
                }
            }

            $account->update($validated);
        });

        return redirect()
            ->route('finance.gl-accounts.index')
            ->with('success', 'General Ledger account updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Common Validation
    |--------------------------------------------------------------------------
    */

    private function validateAccount(
        Request $request,
        ?GeneralLedgerAccount $account = null
    ): array {
        return $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('general_ledger_accounts', 'account_code')
                    ->ignore($account?->id),
            ],

            'account_name' => [
                'required',
                'string',
                'max:150',
            ],

            'account_type' => [
                'required',
                Rule::in(self::ACCOUNT_TYPES),
            ],

            'account_group' => [
                'nullable',
                'string',
                'max:100',
            ],

            'parent_id' => [
                'nullable',
                'integer',
                'exists:general_ledger_accounts,id',
            ],

            'finance_head_id' => [
                'nullable',
                'integer',
                'exists:finance_heads,id',
            ],

            'finance_account_id' => [
                'nullable',
                'integer',
                'exists:finance_accounts,id',
            ],

            'tally_ledger_name' => [
                'nullable',
                'string',
                'max:200',
            ],

            'tally_group_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'normal_balance' => [
                'required',
                Rule::in(self::NORMAL_BALANCES),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'allow_posting' => [
                'sometimes',
                'boolean',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Account Hierarchy
    |--------------------------------------------------------------------------
    */

    private function validateHierarchy(
        array $data,
        ?GeneralLedgerAccount $account = null
    ): void {
        $parentId = $data['parent_id'] ?? null;

        $expectedBalance = in_array(
    $data['account_type'],
    ['asset', 'expense'],
    true
) ? 'debit' : 'credit';

if ($data['normal_balance'] !== $expectedBalance) {
    throw ValidationException::withMessages([
        'normal_balance' =>
            ucfirst($data['account_type'])
            . ' accounts must normally have a '
            . $expectedBalance
            . ' balance.',
    ]);
}

        if ($account && $parentId && (int) $parentId === (int) $account->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'An account cannot be its own parent.',
            ]);
        }

        if ($parentId) {
            $parent = GeneralLedgerAccount::findOrFail($parentId);

            if ($parent->account_type !== $data['account_type']) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent and child accounts must have the same account type.',
                ]);
            }
            if ($parent->allow_posting) {
    throw ValidationException::withMessages([
        'parent_id' =>
            'The selected parent account allows direct journal posting. '
            . 'Disable posting on the parent before adding child accounts.',
    ]);
}

            $visited = [];

            while ($parent) {
                if (isset($visited[$parent->id])) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'A circular account hierarchy was detected.',
                    ]);
                }

                $visited[$parent->id] = true;

                if ($account && $parent->id === $account->id) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Circular account hierarchy is not allowed.',
                    ]);
                }

                $parent = $parent->parent;
            }
        }

        if (
            $account
            && $account->children()->exists()
            && $account->account_type !== $data['account_type']
        ) {
            throw ValidationException::withMessages([
                'account_type' => 'An account with child accounts cannot change its type.',
            ]);
        }

        if (
            $account
            && $account->children()->exists()
            && ($data['allow_posting'] ?? false)
        ) {
            throw ValidationException::withMessages([
                'allow_posting' => 'Parent accounts with children cannot allow direct posting.',
            ]);
        }
    }
}
