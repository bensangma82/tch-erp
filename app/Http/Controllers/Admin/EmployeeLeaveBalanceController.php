<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeLeaveBalanceController extends Controller
{
    /**
     * Display employee leave balances for a selected year.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'year' => [
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'employee_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
            ],
        ]);


        $year = (int) (
            $validated['year']
            ?? now()->year
        );


        /*
        |--------------------------------------------------------------------------
        | Leave Types
        |--------------------------------------------------------------------------
        */

        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Employees
        |--------------------------------------------------------------------------
        |
        | Keep inactive employees available when specifically selected so
        | historical balances remain reviewable.
        |
        */

        $employeesQuery = Employee::query()
            ->with('department')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name');


        if (! empty($validated['employee_id'])) {

            $employeesQuery->where(
                'id',
                $validated['employee_id']
            );

        } else {

            $employeesQuery->where(
                'is_active',
                true
            );
        }


        $employees = $employeesQuery->get();


        /*
        |--------------------------------------------------------------------------
        | Existing Leave Balance Records
        |--------------------------------------------------------------------------
        */

        $balances = EmployeeLeaveBalance::query()
            ->where(
                'leave_year',
                $year
            )
            ->whereIn(
                'employee_id',
                $employees->pluck('id')
            )
            ->whereIn(
                'leave_type_id',
                $leaveTypes->pluck('id')
            )
            ->get()
            ->keyBy(
                fn (EmployeeLeaveBalance $balance) =>
                    $balance->employee_id
                    . ':'
                    . $balance->leave_type_id
            );


        /*
        |--------------------------------------------------------------------------
        | Approved Leave Usage
        |--------------------------------------------------------------------------
        |
        | Used leave is derived from approved leave requests and is never stored
        | redundantly in employee_leave_balances.
        |
        | Current calculation assigns a request to the year in which its
        | start_date falls. Cross-year leave can be split later if TCH policy
        | requires year-by-year apportionment.
        |
        */

        $approvedUsage = LeaveRequest::query()
            ->select([
                'employee_id',
                'leave_type_id',
            ])
            ->selectRaw(
                'SUM(total_days) as used_days'
            )
            ->where(
                'status',
                'approved'
            )
            ->whereYear(
                'start_date',
                $year
            )
            ->whereIn(
                'employee_id',
                $employees->pluck('id')
            )
            ->whereIn(
                'leave_type_id',
                $leaveTypes->pluck('id')
            )
            ->groupBy(
                'employee_id',
                'leave_type_id'
            )
            ->get()
            ->keyBy(
                fn ($row) =>
                    $row->employee_id
                    . ':'
                    . $row->leave_type_id
            );


        /*
        |--------------------------------------------------------------------------
        | Prepare Balance Matrix
        |--------------------------------------------------------------------------
        */

        $balanceMatrix = [];

        foreach ($employees as $employee) {

            foreach ($leaveTypes as $leaveType) {

                $key =
                    $employee->id
                    . ':'
                    . $leaveType->id;


                $balance =
                    $balances->get($key);


                $openingBalance =
                    (float) (
                        $balance?->opening_balance
                        ?? 0
                    );


                $entitlement =
                    (float) (
                        $balance?->entitlement
                        ?? $leaveType
                            ->default_annual_entitlement
                    );


                $adjustment =
                    (float) (
                        $balance?->adjustment
                        ?? 0
                    );


                $usedDays =
                    (float) (
                        $approvedUsage
                            ->get($key)
                            ?->used_days
                        ?? 0
                    );


                $availableDays =
                    $openingBalance
                    + $entitlement
                    + $adjustment;


                $remainingDays =
                    $availableDays
                    - $usedDays;


                $balanceMatrix[
                    $employee->id
                ][
                    $leaveType->id
                ] = [
                    'record' =>
                        $balance,

                    'opening_balance' =>
                        $openingBalance,

                    'entitlement' =>
                        $entitlement,

                    'adjustment' =>
                        $adjustment,

                    'available_days' =>
                        $availableDays,

                    'used_days' =>
                        $usedDays,

                    'remaining_days' =>
                        $remainingDays,
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Employee Filter List
        |--------------------------------------------------------------------------
        */

        $employeeFilter = Employee::query()
            ->with('department')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();


        return view(
            'admin.hr.leave-balances.index',
            compact(
                'year',
                'leaveTypes',
                'employees',
                'employeeFilter',
                'balanceMatrix'
            )
        );
    }


    /**
     * Save leave balances for one employee for one year.
     */
    public function update(
        Request $request,
        Employee $employee
    ): RedirectResponse {

        $validated = $request->validate([
            'leave_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'balances' => [
                'required',
                'array',
            ],

            'balances.*.leave_type_id' => [
                'required',
                'integer',
                Rule::exists(
                    'leave_types',
                    'id'
                ),
            ],

            'balances.*.opening_balance' => [
                'required',
                'numeric',
                'min:0',
                'max:999.5',
            ],

            'balances.*.entitlement' => [
                'required',
                'numeric',
                'min:0',
                'max:999.5',
            ],

            'balances.*.adjustment' => [
                'required',
                'numeric',
                'min:-999.5',
                'max:999.5',
            ],

            'balances.*.remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);


        $year =
            (int) $validated[
                'leave_year'
            ];


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Leave Types in One Submission
        |--------------------------------------------------------------------------
        */

        $submittedLeaveTypeIds =
            collect(
                $validated['balances']
            )
            ->pluck('leave_type_id')
            ->map(
                fn ($id) => (int) $id
            );


        if (
            $submittedLeaveTypeIds
                ->duplicates()
                ->isNotEmpty()
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'balances' =>
                        'The same leave type cannot be submitted more than once.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Balances
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $validated,
                $employee,
                $year
            ) {

                foreach (
                    $validated['balances']
                    as $balanceData
                ) {

                    EmployeeLeaveBalance::updateOrCreate(
                        [
                            'employee_id' =>
                                $employee->id,

                            'leave_type_id' =>
                                (int) $balanceData[
                                    'leave_type_id'
                                ],

                            'leave_year' =>
                                $year,
                        ],
                        [
                            'opening_balance' =>
                                (float) $balanceData[
                                    'opening_balance'
                                ],

                            'entitlement' =>
                                (float) $balanceData[
                                    'entitlement'
                                ],

                            'adjustment' =>
                                (float) $balanceData[
                                    'adjustment'
                                ],

                            'remarks' =>
                                filled(
                                    $balanceData[
                                        'remarks'
                                    ]
                                    ?? null
                                )
                                    ? trim(
                                        $balanceData[
                                            'remarks'
                                        ]
                                    )
                                    : null,

                            'updated_by' =>
                                auth()->id(),
                        ]
                    );
                }
            }
        );


        return redirect()
            ->route(
                'admin.hr.leave-balances.index',
                [
                    'year' => $year,
                    'employee_id' =>
                        $employee->id,
                ]
            )
            ->with(
                'success',
                'Leave balances updated successfully for '
                . $employee->full_name
                . '.'
            );
    }
}
