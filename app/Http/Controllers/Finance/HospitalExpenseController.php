<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\FinanceHead;
use App\Models\GeneralLedgerAccount;
use App\Models\HospitalExpense;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\Finance\HospitalExpenseNumberService;
use App\Services\Accounting\HospitalExpensePostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HospitalExpenseController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Expense Register
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        abort_unless(
            $request->user()->hasPermission(
                'hospital_expenses.view'
            ),
            403
        );

        $expenses = HospitalExpense::query()
            ->with([
                'financeHead',
                'generalLedgerAccount',
                'createdBy',
            ])
            ->latest('expense_date')
            ->latest('id')
            ->paginate(20);

        return view('finance.hospital-expenses.index', [
            'expenses' => $expenses,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Expense Draft
    |--------------------------------------------------------------------------
    */

    public function create(Request $request): View
    {
        abort_unless(
            $request->user()->hasPermission(
                'hospital_expenses.create'
            ),
            403
        );

        $financeHeads = FinanceHead::query()
            ->where('head_type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $glAccounts = GeneralLedgerAccount::query()
            ->whereIn('account_type', [
                'expense',
                'asset',
            ])
            ->where('is_active', true)
            ->where('allow_posting', true)
            ->orderBy('account_code')
            ->get();

        $departments = Department::query()
            ->orderBy('name')
            ->get();

        return view('finance.hospital-expenses.create', [
            'financeHeads' => $financeHeads,
            'glAccounts' => $glAccounts,
            'departments' => $departments,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Hospital Expense Draft
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            $request->user()->hasPermission(
                'hospital_expenses.create'
            ),
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Validate Expense and Department Allocations
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'finance_head_id' => [
                'required',
                'integer',
                Rule::exists('finance_heads', 'id')
                    ->where('head_type', 'expense')
                    ->where('is_active', true),
            ],

            'general_ledger_account_id' => [
                'required',
                'integer',
                Rule::exists('general_ledger_accounts', 'id')
                    ->where('is_active', true)
                    ->where('allow_posting', true),
            ],

            'expense_classification' => [
                'required',
                Rule::in([
                    'clinical',
                    'non_clinical',
                    'shared',
                ]),
            ],

            'expense_type' => [
                'required',
                Rule::in([
                    'operating',
                    'inventory',
                    'capital',
                ]),
            ],

            'expense_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'accounting_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'supplier_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'invoice_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'invoice_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'description' => [
                'required',
                'string',
                'max:500',
            ],

            'subtotal' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'tax_amount' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'total_amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'allocations' => [
                'required',
                'array',
                'min:1',
            ],

            'allocations.*.department_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('departments', 'id')
                    ->where('is_active', true),
            ],

            'allocations.*.percentage' => [
                'required',
                'numeric',
                'gt:0',
                'lte:100',
                'decimal:0,4',
            ],

            'allocations.*.allocated_amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'allocations.*.remarks' => [
                'nullable',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Convert Monetary Amounts to Integer Paise
        |--------------------------------------------------------------------------
        |
        | Avoid floating-point errors in financial reconciliation.
        |
        */

        $toPaise = static function ($amount): int {
            $parts = explode(
                '.',
                (string) $amount,
                2
            );

            $rupees = (int) $parts[0];

            $paise = (int) str_pad(
                $parts[1] ?? '',
                2,
                '0'
            );

            return ($rupees * 100) + $paise;
        };

        $subtotal = $toPaise(
            $validated['subtotal']
        );

        $tax = $toPaise(
            $validated['tax_amount']
        );

        $total = $toPaise(
            $validated['total_amount']
        );

        if ($subtotal + $tax !== $total) {
            throw ValidationException::withMessages([
                'total_amount' =>
                    'Total amount must equal subtotal plus tax.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate GL Account Classification
        |--------------------------------------------------------------------------
        */

        $account = GeneralLedgerAccount::query()
            ->findOrFail(
                $validated['general_ledger_account_id']
            );

        $expectedType =
            $validated['expense_type'] === 'operating'
                ? 'expense'
                : 'asset';

        if (
            ! $account->is_active
            || ! $account->allow_posting
            || $account->account_type !== $expectedType
        ) {
            throw ValidationException::withMessages([
                'general_ledger_account_id' =>
                    'The selected GL account is not valid '
                    . 'for this expense type.',
            ]);
        }

        if (
            $account->finance_head_id !== null
            && (int) $account->finance_head_id
                !== (int) $validated['finance_head_id']
        ) {
            throw ValidationException::withMessages([
                'general_ledger_account_id' =>
                    'The selected GL account does not match '
                    . 'the selected Finance Head.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Department Allocations
        |--------------------------------------------------------------------------
        */

        $allocationTotal = 0;
        $percentageTotal = 0;

        foreach ($validated['allocations'] as $allocation) {
            $allocatedPaise = $toPaise(
                $allocation['allocated_amount']
            );

            $allocationTotal += $allocatedPaise;

            /*
             * Percentage precision: four decimal places.
             * 100.0000% = 1,000,000 scaled units.
             */

            $percentageTotal += (int) round(
                (float) $allocation['percentage'] * 10000
            );

            $expectedPaise = (int) round(
                $total
                * (float) $allocation['percentage']
                / 100
            );

            if (
                abs($allocatedPaise - $expectedPaise) > 1
            ) {
                throw ValidationException::withMessages([
                    'allocations' =>
                        'Allocated amounts must correspond '
                        . 'to their percentages.',
                ]);
            }
        }

        if ($percentageTotal !== 1000000) {
            throw ValidationException::withMessages([
                'allocations' =>
                    'Department allocation percentages '
                    . 'must total exactly 100%.',
            ]);
        }

        if ($allocationTotal !== $total) {
            throw ValidationException::withMessages([
                'allocations' =>
                    'Department allocation amounts must '
                    . 'equal the total expense amount.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Save Draft and Allocations Atomically
        |--------------------------------------------------------------------------
        */

        $expense = DB::transaction(
            function () use ($validated, $request) {

                $expenseNo = app(
                    HospitalExpenseNumberService::class
                )->generate(
                    $validated['expense_date']
                );

                $expenseData = collect($validated)
                    ->except('allocations')
                    ->all();

                $expenseData['expense_no'] = $expenseNo;

                $expense = new HospitalExpense();

                $expense->fill($expenseData);

                /*
                 * Workflow fields are assigned internally.
                 * They are never accepted from the request.
                 */

                $expense->status = 'draft';

                $expense->created_by =
                    $request->user()->id;

                $expense->save();

                foreach (
                    $validated['allocations']
                    as $allocation
                ) {
                    $expense->allocations()->create([
                        'department_id' =>
                            $allocation['department_id'],

                        'percentage' =>
                            $allocation['percentage'],

                        'allocated_amount' =>
                            $allocation['allocated_amount'],

                        'remarks' =>
                            $allocation['remarks'] ?? null,
                    ]);
                }

                return $expense;
            },
            3
        );

        return redirect()
            ->route('finance.hospital-expenses.index')
            ->with(
                'success',
                'Hospital expense draft '
                . $expense->expense_no
                . ' created successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Submit Expense for Approval
    |--------------------------------------------------------------------------
    */

    public function submit(
        Request $request,
        HospitalExpense $hospitalExpense
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission(
                'hospital_expenses.submit'
            ),
            403
        );

        DB::transaction(function () use ($hospitalExpense) {

            $expense = HospitalExpense::query()
                ->lockForUpdate()
                ->findOrFail($hospitalExpense->id);

            if ($expense->status !== 'draft') {
                throw ValidationException::withMessages([
                    'expense' =>
                        'Only draft expenses can be submitted.',
                ]);
            }

            if (! $expense->allocations()->exists()) {
                throw ValidationException::withMessages([
                    'allocations' =>
                        'Department allocations are required.',
                ]);
            }

            $totalPaise = (int) round(
                (float) $expense->total_amount * 100
            );

            $allocatedPaise = $expense->allocations()
                ->get()
                ->sum(fn ($a) => (int) round(
                    (float) $a->allocated_amount * 100
                ));

            $percentageUnits = $expense->allocations()
                ->get()
                ->sum(fn ($a) => (int) round(
                    (float) $a->percentage * 10000
                ));

            if (
                $allocatedPaise !== $totalPaise
                || $percentageUnits !== 1000000
            ) {
                throw ValidationException::withMessages([
                    'allocations' =>
                        'Department allocations must equal '
                        . '100% of the expense amount.',
                ]);
            }

            $expense->status = 'submitted';
            $expense->save();
        });

        return redirect()
            ->route('finance.hospital-expenses.index')
            ->with(
                'success',
                'Hospital expense submitted for approval.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Approve Hospital Expense
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        HospitalExpense $hospitalExpense
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission(
                'hospital_expenses.approve'
            ),
            403
        );

        DB::transaction(function () use ($hospitalExpense, $request) {

            $expense = HospitalExpense::query()
                ->lockForUpdate()
                ->findOrFail($hospitalExpense->id);

            if ($expense->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'expense' =>
                        'Only submitted expenses can be approved.',
                ]);
            }

            /*
             * Separation of duties:
             * A user cannot approve an expense they created.
             */

            if (
                $expense->created_by === null
                || (int) $expense->created_by
                    === (int) $request->user()->id
            ) {
                throw ValidationException::withMessages([
                    'expense' =>
                        'An expense must be approved by a different '
                        . 'authorized user than its creator.',
                ]);
            }

            $expense->status = 'approved';
            $expense->approved_by = $request->user()->id;
            $expense->approved_at = now();

            $expense->save();
        });

        return redirect()
            ->route('finance.hospital-expenses.index')
            ->with(
                'success',
                'Hospital expense approved successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Return Expense for Correction
    |--------------------------------------------------------------------------
    */

    public function returnForCorrection(
        Request $request,
        HospitalExpense $hospitalExpense
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission(
                'hospital_expenses.approve'
            ),
            403
        );

        DB::transaction(function () use ($hospitalExpense) {

            $expense = HospitalExpense::query()
                ->lockForUpdate()
                ->findOrFail($hospitalExpense->id);

            if ($expense->status !== 'submitted') {
                throw ValidationException::withMessages([
                    'expense' =>
                        'Only submitted expenses can be returned '
                        . 'for correction.',
                ]);
            }

            $expense->status = 'draft';
            $expense->approved_by = null;
            $expense->approved_at = null;

            $expense->save();
        });

        return redirect()
            ->route('finance.hospital-expenses.index')
            ->with(
                'success',
                'Hospital expense returned to draft for correction.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Post Approved Hospital Expense to General Ledger
    |--------------------------------------------------------------------------
    */

    public function post(
        Request $request,
        HospitalExpense $hospitalExpense,
        HospitalExpensePostingService $postingService
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission(
                'hospital_expenses.post'
            ),
            403
        );

        $journal = $postingService->post(
            $hospitalExpense,
            (int) $request->user()->id
        );

        return redirect()
            ->route('finance.hospital-expenses.index')
            ->with(
                'success',
                'Hospital expense posted successfully. Journal: '
                . $journal->journal_no
            );
    }

}
