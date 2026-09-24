<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\PayrollAdjustment;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Services\Finance\FinanceVoucherNumberService;
use App\Services\Payroll\PayrollCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PayrollRunController extends Controller
{
   public function __construct(
    private readonly PayrollCalculator $calculator,
    private readonly FinanceVoucherNumberService $voucherNumberService
) {
}

    /*
    |--------------------------------------------------------------------------
    | Payroll Run Register
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $query = PayrollRun::query()
            ->withCount('entries')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id');

        if ($request->filled('year')) {
            $query->where('year', (int) $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payrollRuns = $query
            ->paginate(25)
            ->withQueryString();

        return view(
            'admin.hr.payroll.runs.index',
            compact('payrollRuns')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Payroll Run
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        return view('admin.hr.payroll.runs.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Store Payroll Run
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'month' => [
                'required',
                'integer',
                'between:1,12',

                Rule::unique('payroll_runs', 'month')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'year',
                                (int) $request->input('year')
                            )
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $periodStart = Carbon::create(
            (int) $validated['year'],
            (int) $validated['month'],
            1
        )->startOfMonth();

        $periodEnd = $periodStart->copy()->endOfMonth();

        $payrollNo = sprintf(
            'PAY-%04d-%02d',
            $validated['year'],
            $validated['month']
        );

        $payrollRun = PayrollRun::create([
            'year' => $validated['year'],
            'month' => $validated['month'],

            'period_start' => $periodStart,
            'period_end' => $periodEnd,

            'payroll_no' => $payrollNo,

            'description' =>
                $validated['description']
                ?? $periodStart->format('F Y') . ' Payroll',

            'status' => 'draft',

            'employee_count' => 0,
            'total_earnings' => 0,
            'total_deductions' => 0,
            'total_net_pay' => 0,

            'remarks' => $validated['remarks'] ?? null,

            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route(
                'admin.hr.payroll.runs.show',
                $payrollRun
            )
            ->with(
                'success',
                'Payroll run created successfully. Calculate payroll when ready.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Show Payroll Run
    |--------------------------------------------------------------------------
    */

   public function show(PayrollRun $payrollRun): View
{
    $payrollRun->load([
        'entries' => function ($query) {
            $query
                ->with([
                    'employee',
                    'items',
                    'financeAccount',
                ])
                ->orderBy('employee_name');
        },

        'financeVouchers' => function ($query) {
            $query
                ->with([
                    'financeHead',
                    'financeAccount',
                ])
                ->orderBy('voucher_date')
                ->orderBy('voucher_no');
        },
    ]);

    $financeAccounts = \App\Models\FinanceAccount::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get([
            'id',
            'code',
            'name',
            'account_type',
        ]);

    return view(
        'admin.hr.payroll.runs.show',
        compact(
            'payrollRun',
            'financeAccounts'
        )
    );
}

    /*
    |--------------------------------------------------------------------------
    | Calculate / Recalculate Payroll
    |--------------------------------------------------------------------------
    */

    public function calculate(
        PayrollRun $payrollRun
    ): RedirectResponse {
        if (!$payrollRun->canBeModified()) {
            return back()->with(
                'error',
                'This payroll run can no longer be recalculated.'
            );
        }

        try {
            DB::transaction(function () use ($payrollRun) {
                /*
                |--------------------------------------------------------------------------
                | Lock Payroll Run
                |--------------------------------------------------------------------------
                */

                $run = PayrollRun::query()
                    ->whereKey($payrollRun->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$run->canBeModified()) {
                    throw new RuntimeException(
                        'This payroll run can no longer be recalculated.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Remove Previous Draft Calculation
                |--------------------------------------------------------------------------
                |
                | payroll_entry_items are removed automatically because the
                | payroll_entries foreign key uses cascadeOnDelete.
                |
                */

                $run->entries()->delete();

                /*
                |--------------------------------------------------------------------------
                | Employees Eligible for this Payroll Period
                |--------------------------------------------------------------------------
                */

                $employees = Employee::query()
                    ->with('department')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->where(function ($query) use ($run) {
                        $query
                            ->whereNull('date_of_joining')
                            ->orWhereDate(
                                'date_of_joining',
                                '<=',
                                $run->period_end
                            );
                    })
                    ->orderBy('employee_code')
                    ->get();

                $employeeCount = 0;
                $totalEarnings = 0.00;
                $totalDeductions = 0.00;
                $totalNetPay = 0.00;

                foreach ($employees as $employee) {
                    /*
                    |--------------------------------------------------------------------------
                    | Find Salary Structure Applicable to this Payroll Period
                    |--------------------------------------------------------------------------
                    |
                    | The structure must overlap the payroll month:
                    |
                    | effective_from <= payroll period end
                    | AND
                    | effective_to is null OR effective_to >= payroll period start
                    |
                    | We use the most recently effective active structure.
                    |
                    */

                    $salaryStructure =
                        EmployeeSalaryStructure::query()
                            ->with([
                                'items.salaryComponent',
                                'items.percentageOfComponent',
                            ])
                            ->where(
                                'employee_id',
                                $employee->id
                            )
                            ->where('status', 'active')
                            ->whereDate(
                                'effective_from',
                                '<=',
                                $run->period_end
                            )
                            ->where(function ($query) use ($run) {
                                $query
                                    ->whereNull('effective_to')
                                    ->orWhereDate(
                                        'effective_to',
                                        '>=',
                                        $run->period_start
                                    );
                            })
                            ->orderByDesc('effective_from')
                            ->orderByDesc('id')
                            ->first();

                    /*
                    |--------------------------------------------------------------------------
                    | Skip Employees Without an Active Salary Structure
                    |--------------------------------------------------------------------------
                    */

                    if (!$salaryStructure) {
                        continue;
                    }

                    $calculation =
                        $this->calculator->calculate(
                            $salaryStructure
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Employee Payroll Adjustments
                    |--------------------------------------------------------------------------
                    |
                    | Adjustments belong to the payroll run and employee rather than
                    | the disposable payroll entry, so they survive recalculation.
                    |
                    */

                    $adjustments = PayrollAdjustment::query()
                        ->where('payroll_run_id', $run->id)
                        ->where('employee_id', $employee->id)
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->get();

                    $calendarDays =
                        $run->period_start->daysInMonth;

                    $additionalEarnings = (float) $adjustments
                        ->where('type', 'earning')
                        ->sum('amount');

                    $additionalDeductions = (float) $adjustments
                        ->where('type', 'deduction')
                        ->sum('amount');

                    $lopAdjustments = $adjustments
                        ->where('type', 'lop');

                    $lopDays = (float) $lopAdjustments->sum('lop_days');

                    $lopDays = min(max($lopDays, 0), $calendarDays);
                    $payableDays = max($calendarDays - $lopDays, 0);

                    /*
                    |--------------------------------------------------------------------------
                    | TCH Loss of Pay Policy
                    |--------------------------------------------------------------------------
                    |
                    | LOP uses a fixed 30-day divisor:
                    |
                    | monthly salary / 30 x LOP days
                    |
                    | The salary structure monthly_salary is the reference monthly
                    | remuneration for this calculation.
                    |
                    */

                    $monthlySalary = (float) $salaryStructure->monthly_salary;

                    $lopDeduction = round(
                        ($monthlySalary / 30) * $lopDays,
                        2
                    );

                    $grossEarnings = round(
                        $calculation['gross_earnings'] + $additionalEarnings,
                        2
                    );

                    $employeeDeductions = round(
                        $calculation['total_deductions']
                        + $additionalDeductions
                        + $lopDeduction,
                        2
                    );

                    $netPay = round(
                        $grossEarnings - $employeeDeductions,
                        2
                    );

                    $entry = PayrollEntry::create([
                        'payroll_run_id' => $run->id,
                        'employee_id' => $employee->id,
                        'employee_salary_structure_id' =>
                            $salaryStructure->id,

                        'employee_code' => $employee->employee_code,
                        'employee_name' => $employee->full_name,
                        'designation' => $employee->designation,
                        'department_name' => $employee->department?->name,
                        'employee_type' => $employee->employee_type,

                        'calendar_days' => $calendarDays,
                        'payable_days' => $payableDays,
                        'lop_days' => $lopDays,

                        'gross_earnings' => $grossEarnings,
                        'total_deductions' => $employeeDeductions,
                        'net_pay' => $netPay,
                        'lop_deduction' => round($lopDeduction, 2),
                        'additional_earnings' => round($additionalEarnings, 2),
                        'additional_deductions' => round($additionalDeductions, 2),

                        'status' => 'calculated',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);

                    foreach ($calculation['items'] as $calculatedItem) {
                        $entry->items()->create($calculatedItem);
                    }

                    $employeeCount++;

                    $totalEarnings += $grossEarnings;
                    $totalDeductions += $employeeDeductions;
                    $totalNetPay += $netPay;
                }

                /*
                |--------------------------------------------------------------------------
                | Update Payroll Run Totals
                |--------------------------------------------------------------------------
                */

                $run->update([
                    'status' => 'calculated',

                    'employee_count' =>
                        $employeeCount,

                    'total_earnings' =>
                        round($totalEarnings, 2),

                    'total_deductions' =>
                        round($totalDeductions, 2),

                    'total_net_pay' =>
                        round($totalNetPay, 2),

                    'calculated_at' => now(),
                    'calculated_by' => auth()->id(),

                    'updated_by' => auth()->id(),
                ]);
            });

            return redirect()
                ->route(
                    'admin.hr.payroll.runs.show',
                    $payrollRun
                )
                ->with(
                    'success',
                    'Payroll calculated successfully.'
                );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Payroll calculation failed: '
                . $exception->getMessage()
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Store Payroll Adjustment
    |--------------------------------------------------------------------------
    */

    public function storeAdjustment(
        Request $request,
        PayrollRun $payrollRun,
        PayrollEntry $payrollEntry
    ): RedirectResponse {
        if (!$payrollRun->canBeModified()) {
            return back()->with(
                'error',
                'Adjustments cannot be changed after payroll approval.'
            );
        }

        if ((int) $payrollEntry->payroll_run_id !== (int) $payrollRun->id) {
            abort(404);
        }

        $validated = $request->validate([
            'type' => [
                'required',
                Rule::in(['earning', 'deduction', 'lop']),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'lop_days' => [
                'nullable',
                'numeric',
                'min:0',
                'max:' . (int) $payrollEntry->calendar_days,
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $type = $validated['type'];
        $amount = round((float) ($validated['amount'] ?? 0), 2);
        $lopDays = round((float) ($validated['lop_days'] ?? 0), 2);

        if ($type === 'lop') {
            if ($lopDays <= 0) {
                return back()
                    ->withErrors([
                        'lop_days' =>
                            'LOP days must be greater than zero.',
                    ])
                    ->withInput();
            }

            /*
            |--------------------------------------------------------------------------
            | LOP Amount Is System Calculated
            |--------------------------------------------------------------------------
            |
            | The amount stored on an LOP adjustment is informational only.
            | Recalculation derives the authoritative deduction from the active
            | salary structure using TCH's fixed 30-day divisor.
            |
            */

            $salaryStructure = EmployeeSalaryStructure::query()
                ->whereKey($payrollEntry->employee_salary_structure_id)
                ->where('employee_id', $payrollEntry->employee_id)
                ->first();

            if (!$salaryStructure) {
                return back()->with(
                    'error',
                    'The employee salary structure could not be found for LOP calculation.'
                );
            }

            $amount = round(
                ((float) $salaryStructure->monthly_salary / 30) * $lopDays,
                2
            );
        } else {
            if ($amount <= 0) {
                return back()
                    ->withErrors([
                        'amount' =>
                            'Adjustment amount must be greater than zero.',
                    ])
                    ->withInput();
            }

            $lopDays = 0;
        }

        DB::transaction(function () use (
            $payrollRun,
            $payrollEntry,
            $validated,
            $type,
            $amount,
            $lopDays
        ) {
            $run = PayrollRun::query()
                ->whereKey($payrollRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$run->canBeModified()) {
                throw new RuntimeException(
                    'This payroll run can no longer be modified.'
                );
            }

            PayrollAdjustment::create([
                'payroll_run_id' => $run->id,
                'employee_id' => $payrollEntry->employee_id,
                'type' => $type,
                'code' => strtoupper(trim($validated['code'])),
                'name' => trim($validated['name']),
                'amount' => $amount,
                'lop_days' => $lopDays,
                'remarks' => $validated['remarks'] ?? null,
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route(
                'admin.hr.payroll.runs.show',
                $payrollRun
            )
            ->with(
                'success',
                'Payroll adjustment saved. Recalculate payroll to apply the updated totals.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Payroll Adjustment
    |--------------------------------------------------------------------------
    */

    public function destroyAdjustment(
    PayrollRun $payrollRun,
    PayrollAdjustment $payrollAdjustment
): RedirectResponse {
    if (! $payrollRun->canBeModified()) {
        return back()->with(
            'error',
            'Adjustments cannot be changed after payroll approval.'
        );
    }

    if (
        (int) $payrollAdjustment->payroll_run_id
        !== (int) $payrollRun->id
    ) {
        abort(404);
    }

    try {
        DB::transaction(function () use (
            $payrollRun,
            $payrollAdjustment
        ) {
            $run = PayrollRun::query()
                ->whereKey($payrollRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $run->canBeModified()) {
                throw new RuntimeException(
                    'This payroll run can no longer be modified.'
                );
            }

            $adjustment = PayrollAdjustment::query()
                ->whereKey($payrollAdjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $adjustment->payroll_run_id
                !== (int) $run->id
            ) {
                abort(404);
            }

            if (! $adjustment->is_active) {
                throw new RuntimeException(
                    'This payroll adjustment has already been cancelled.'
                );
            }

            $adjustment->update([
                'is_active' => false,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => 'Cancelled from payroll run',
                'updated_by' => auth()->id(),
            ]);
        });
    } catch (RuntimeException $exception) {
        return back()->with(
            'error',
            $exception->getMessage()
        );
    }

    return redirect()
        ->route(
            'admin.hr.payroll.runs.show',
            $payrollRun
        )
        ->with(
            'success',
            'Payroll adjustment cancelled. Recalculate payroll to update the totals.'
        );
}
    /*
    |--------------------------------------------------------------------------
    | Record Employee Payroll Payment
    |--------------------------------------------------------------------------
    */

    public function recordPayment(
        Request $request,
        PayrollRun $payrollRun,
        PayrollEntry $payrollEntry
    ): RedirectResponse {
        if ((int) $payrollEntry->payroll_run_id !== (int) $payrollRun->id) {
            abort(404);
        }

        if (!in_array($payrollRun->status, ['approved', 'paid'], true)) {
            return back()->with(
                'error',
                'Payments can be recorded only for an approved payroll run.'
            );
        }

        if ($payrollEntry->isPaid()) {
            return back()->with(
                'error',
                'Payment has already been recorded for this employee.'
            );
        }

        $validated = $request->validate([
            'payment_date' => [
                'required',
                'date',
            ],
            'payment_mode' => [
                'required',
                Rule::in([
                    'bank_transfer',
                    'cash',
                    'cheque',
                    'other',
                ]),
            ],

                 'finance_account_id' => [
    'required',
    'integer',
    Rule::exists('finance_accounts', 'id')
        ->where(fn ($query) => $query->where('is_active', true)),
],
            'payment_reference' => [
                'nullable',
                'string',
                'max:150',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        if (
            in_array(
                $validated['payment_mode'],
                ['bank_transfer', 'cheque'],
                true
            )
            && blank($validated['payment_reference'] ?? null)
        ) {
            return back()
                ->withErrors([
                    'payment_reference' =>
                        'A payment reference is required for bank transfer or cheque payments.',
                ])
                ->withInput();
        }

        DB::transaction(function () use (
            $payrollRun,
            $payrollEntry,
            $validated
        ) {
            $run = PayrollRun::query()
                ->whereKey($payrollRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($run->status, ['approved', 'paid'], true)) {
                throw new RuntimeException(
                    'This payroll run is no longer available for payment processing.'
                );
            }

            $entry = PayrollEntry::query()
                ->whereKey($payrollEntry->id)
                ->where('payroll_run_id', $run->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->status === 'paid') {
                throw new RuntimeException(
                    'Payment has already been recorded for this employee.'
                );
            }

            if ($entry->status !== 'approved') {
                throw new RuntimeException(
                    'Only an approved payroll entry can be paid.'
                );
            }

            $entry->update([
    'status' => 'paid',
    'payment_mode' => $validated['payment_mode'],
    'finance_account_id' =>
        $validated['finance_account_id'],
    'payment_reference' =>
        $validated['payment_reference'] ?? null,
                'payment_date' => $validated['payment_date'],
                'remarks' => $validated['remarks']
                    ?? $entry->remarks,
                'updated_by' => auth()->id(),
            ]);

            $remainingUnpaid = $run->entries()
                ->where('status', '!=', 'paid')
                ->count();

            if ($remainingUnpaid === 0) {
                $latestPaymentDate = $run->entries()
                    ->max('payment_date');

                $run->update([
                    'status' => 'paid',
                    'payment_date' => $latestPaymentDate,
                    'paid_at' => now(),
                    'paid_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        $payrollRun->refresh();

        return redirect()
            ->route(
                'admin.hr.payroll.runs.show',
                $payrollRun
            )
            ->with(
                'success',
                $payrollRun->status === 'paid'
                    ? 'Employee payment recorded. All employees are now paid and the payroll run has been marked Paid.'
                    : 'Employee payment recorded successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Lock Payroll
    |--------------------------------------------------------------------------
    */

    public function lock(
        PayrollRun $payrollRun
    ): RedirectResponse {
        if (!$payrollRun->canBeLocked()) {
            return back()->with(
                'error',
                'Only a fully paid payroll run can be locked.'
            );
        }

        DB::transaction(function () use ($payrollRun) {
            $run = PayrollRun::query()
                ->whereKey($payrollRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$run->canBeLocked()) {
                throw new RuntimeException(
                    'This payroll run is no longer available for locking.'
                );
            }

            if (
                $run->entries()->count() === 0
                || $run->entries()->where('status', '!=', 'paid')->exists()
            ) {
                throw new RuntimeException(
                    'Payroll cannot be locked until every employee payment is recorded.'
                );
            }

            $run->update([
                'status' => 'locked',
                'locked_at' => now(),
                'locked_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route(
                'admin.hr.payroll.runs.show',
                $payrollRun
            )
            ->with(
                'success',
                'Payroll locked successfully. This payroll is now a final historical record.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | Employee Payslip
    |--------------------------------------------------------------------------
    */

    public function payslip(
        PayrollRun $payrollRun,
        PayrollEntry $payrollEntry
    ): View {
        if ((int) $payrollEntry->payroll_run_id !== (int) $payrollRun->id) {
            abort(404);
        }

        if (!in_array($payrollRun->status, ['approved', 'paid', 'locked'], true)) {
            abort(403, 'Payslips are available only after payroll approval.');
        }

        $payrollEntry->load([
            'items',
        ]);

        $adjustments = PayrollAdjustment::query()
            ->where('payroll_run_id', $payrollRun->id)
            ->where('employee_id', $payrollEntry->employee_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return view(
            'admin.hr.payroll.runs.payslip',
            compact(
                'payrollRun',
                'payrollEntry',
                'adjustments'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Approve Payroll
    |--------------------------------------------------------------------------
    */

    public function approve(
        PayrollRun $payrollRun
    ): RedirectResponse {
        if (!$payrollRun->canBeApproved()) {
            return back()->with(
                'error',
                'Only a calculated payroll run can be approved.'
            );
        }

        DB::transaction(function () use ($payrollRun) {
            $run = PayrollRun::query()
                ->whereKey($payrollRun->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$run->canBeApproved()) {
                throw new RuntimeException(
                    'This payroll run is no longer available for approval.'
                );
            }

            if ($run->entries()->count() === 0) {
                throw new RuntimeException(
                    'A payroll run with no employees cannot be approved.'
                );
            }

            $run->entries()->update([
                'status' => 'approved',
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            $run->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route(
                'admin.hr.payroll.runs.show',
                $payrollRun
            )
            ->with(
                'success',
                'Payroll approved successfully.'
            );
    }


              /*
    |--------------------------------------------------------------------------
    | Create Finance Vouchers
    |--------------------------------------------------------------------------
    */

    public function createFinanceVouchers(
        PayrollRun $payrollRun
    ): RedirectResponse {
        if (!$payrollRun->isLocked()) {
            return back()->with(
                'error',
                'Finance vouchers can be created only after the payroll run has been locked.'
            );
        }

        try {
            $result = DB::transaction(function () use ($payrollRun) {
                $run = PayrollRun::query()
                    ->whereKey($payrollRun->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$run->isLocked()) {
                    throw new RuntimeException(
                        'This payroll run is no longer available for Finance voucher creation.'
                    );
                }

                $entries = PayrollEntry::query()
                    ->where('payroll_run_id', $run->id)
                    ->lockForUpdate()
                    ->get();

                if ($entries->isEmpty()) {
                    throw new RuntimeException(
                        'Finance vouchers cannot be created because this payroll run has no employee entries.'
                    );
                }

                $unpaidEntries = $entries
                    ->where('status', '!=', 'paid');

                if ($unpaidEntries->isNotEmpty()) {
                    throw new RuntimeException(
                        'Finance vouchers cannot be created because every payroll entry must be paid.'
                    );
                }

                $missingAccounts = $entries
                    ->filter(
                        fn (PayrollEntry $entry) =>
                            blank($entry->finance_account_id)
                    );

                if ($missingAccounts->isNotEmpty()) {
                    $employees = $missingAccounts
                        ->pluck('employee_name')
                        ->filter()
                        ->implode(', ');

                    throw new RuntimeException(
                        'Finance voucher creation stopped because the following paid employee(s) do not have a Finance Account recorded: '
                        . ($employees !== '' ? $employees : 'Unknown employee')
                        . '.'
                    );
                }

                $salaryHead = FinanceHead::query()
                    ->where('code', 'EXP-SALARY')
                    ->where('is_active', true)
                    ->first();

                if (!$salaryHead) {
                    throw new RuntimeException(
                        'The active Finance Head EXP-SALARY (Salary & Wages) could not be found.'
                    );
                }

                /*
                 * Payroll is grouped by the Finance Account from which
                 * each employee was actually paid.
                 *
                 * Therefore:
                 * one Payroll Run + one Finance Account
                 * = one Finance payment voucher.
                 */
                $groups = $entries->groupBy('finance_account_id');

                $createdCount = 0;
                $existingCount = 0;
                $createdAmount = 0.00;

                foreach ($groups as $financeAccountId => $groupEntries) {
                    $existingVoucher = FinanceVoucher::query()
                        ->where('payroll_run_id', $run->id)
                        ->where(
                            'finance_account_id',
                            (int) $financeAccountId
                        )
                        ->first();

                    if ($existingVoucher) {
                        $existingCount++;
                        continue;
                    }

                    $amount = round(
                        (float) $groupEntries->sum('net_pay'),
                        2
                    );

                    if ($amount <= 0) {
                        throw new RuntimeException(
                            'Finance voucher creation stopped because a payroll payment group has a zero or negative net amount.'
                        );
                    }

                    /*
                     * Use the latest employee payment date for this
                     * particular Finance Account group.
                     */
                    $voucherDate = $groupEntries
                        ->pluck('payment_date')
                        ->filter()
                        ->map(
                            fn ($date) => Carbon::parse($date)
                        )
                        ->sortByDesc(
                            fn (Carbon $date) => $date->timestamp
                        )
                        ->first();

                    if (!$voucherDate) {
                        throw new RuntimeException(
                            'Finance voucher creation stopped because a payroll payment date is missing.'
                        );
                    }

                    $paymentModes = $groupEntries
                        ->pluck('payment_mode')
                        ->filter()
                        ->unique()
                        ->values();

                    /*
                     * Finance vouchers allow payment_mode to be nullable.
                     * Preserve a mode only when all employees in this
                     * account group used the same payment mode.
                     */
                    $paymentMode = $paymentModes->count() === 1
                        ? $paymentModes->first()
                        : null;

                    $voucherNo = $this->voucherNumberService->generate(
                        'payment',
                        $voucherDate->toDateString()
                    );

                    FinanceVoucher::create([
                        'voucher_no' => $voucherNo,
                        'voucher_type' => 'payment',
                        'voucher_date' => $voucherDate->toDateString(),
                        'finance_head_id' => $salaryHead->id,
                        'finance_account_id' => (int) $financeAccountId,
                        'payroll_run_id' => $run->id,
                        'destination_account_id' => null,
                        'amount' => $amount,
                        'payment_mode' => $paymentMode,
                        'reference_no' => $run->payroll_no,
                        'party_name' => 'Payroll',
                        'narration' => sprintf(
                            'Salary payment for %s %d — %s',
                            Carbon::create()
                                ->month((int) $run->month)
                                ->format('F'),
                            (int) $run->year,
                            $run->payroll_no
                        ),
                        'status' => 'draft',
                        'created_by' => auth()->id(),
                    ]);

                    $createdCount++;
                    $createdAmount += $amount;
                }

                return [
                    'created_count' => $createdCount,
                    'existing_count' => $existingCount,
                    'created_amount' => $createdAmount,
                ];
            });

            if ($result['created_count'] === 0) {
                return back()->with(
                    'success',
                    'Finance voucher creation was already completed for this payroll run. No duplicate vouchers were created.'
                );
            }

            $message = sprintf(
                '%d Finance voucher(s) created in Draft status for a total of ₹%s.',
                $result['created_count'],
                number_format(
                    $result['created_amount'],
                    2
                )
            );

            if ($result['existing_count'] > 0) {
                $message .= sprintf(
                    ' %d existing voucher(s) were left unchanged.',
                    $result['existing_count']
                );
            }

            $message .= ' Finance must review and post the draft voucher(s) before they affect Finance reports.';

            return redirect()
                ->route(
                    'admin.hr.payroll.runs.show',
                    $payrollRun
                )
                ->with(
                    'success',
                    $message
                );
        } catch (RuntimeException $exception) {
            return back()->with(
                'error',
                $exception->getMessage()
            );
        }
    }
}
