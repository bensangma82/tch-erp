<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    /**
     * Display leave requests and filters.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'approved',
                    'rejected',
                    'cancelled',
                ]),
            ],

            'employee_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
            ],

            'year' => [
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],
        ]);


        $year = (int) (
            $validated['year']
            ?? now()->year
        );


        $query = LeaveRequest::query()
            ->with([
                'employee.department',
                'leaveType',
                'approvedBy',
                'createdBy',
            ])
            ->whereYear(
                'start_date',
                $year
            );


        if (! empty($validated['status'])) {

            $query->where(
                'status',
                $validated['status']
            );
        }


        if (! empty($validated['employee_id'])) {

            $query->where(
                'employee_id',
                $validated['employee_id']
            );
        }


        $leaveRequests = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();


        $employees = Employee::query()
            ->with('department')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();


        $leaveTypes = LeaveType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();


        $pendingCount = LeaveRequest::query()
            ->where('status', 'pending')
            ->whereYear('start_date', $year)
            ->count();

        $approvedCount = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->count();

        $rejectedCount = LeaveRequest::query()
            ->where('status', 'rejected')
            ->whereYear('start_date', $year)
            ->count();

        $cancelledCount = LeaveRequest::query()
            ->where('status', 'cancelled')
            ->whereYear('start_date', $year)
            ->count();


        return view(
            'admin.hr.leave-requests.index',
            compact(
                'year',
                'leaveRequests',
                'employees',
                'leaveTypes',
                'pendingCount',
                'approvedCount',
                'rejectedCount',
                'cancelledCount'
            )
        );
    }


    /**
     * Show new leave request form.
     */
    public function create(): View
    {
        $employees = Employee::query()
            ->with('department')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();


        $leaveTypes = LeaveType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();


        return view(
            'admin.hr.leave-requests.create',
            compact(
                'employees',
                'leaveTypes'
            )
        );
    }


    /**
     * Store a new leave request.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists(
                    'employees',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                ),
            ],

            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists(
                    'leave_types',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                ),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        $employee = Employee::findOrFail(
            $validated['employee_id']
        );

        $leaveType = LeaveType::findOrFail(
            $validated['leave_type_id']
        );


        $startDate = Carbon::parse(
            $validated['start_date']
        )->startOfDay();

        $endDate = Carbon::parse(
            $validated['end_date']
        )->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Inclusive Calendar-Day Calculation
        |--------------------------------------------------------------------------
        |
        | Example:
        | 10 Sep to 10 Sep = 1 day
        | 10 Sep to 12 Sep = 3 days
        |
        | Weekly offs / public holidays are not excluded at this stage because
        | hospital leave policy has not yet been encoded in the ERP.
        |
        */

        $totalDays =
            $startDate
                ->diffInDays($endDate)
            + 1;


        /*
        |--------------------------------------------------------------------------
        | Overlap Protection
        |--------------------------------------------------------------------------
        |
        | Prevent overlapping pending or approved leave for the same employee.
        |
        */

        $hasOverlap = LeaveRequest::query()
            ->where(
                'employee_id',
                $employee->id
            )
            ->whereIn(
                'status',
                [
                    'pending',
                    'approved',
                ]
            )
            ->whereDate(
                'start_date',
                '<=',
                $endDate->toDateString()
            )
            ->whereDate(
                'end_date',
                '>=',
                $startDate->toDateString()
            )
            ->exists();


        if ($hasOverlap) {

            return back()
                ->withInput()
                ->withErrors([
                    'start_date' =>
                        'This employee already has a pending or approved leave request overlapping the selected dates.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Create Request
        |--------------------------------------------------------------------------
        */

        $leaveRequest = DB::transaction(
            function () use (
                $employee,
                $leaveType,
                $startDate,
                $endDate,
                $totalDays,
                $validated
            ) {

                /*
                |------------------------------------------------------------------
                | PostgreSQL transaction-level advisory lock
                |------------------------------------------------------------------
                |
                | Keeps sequential leave request numbers safe when two HR users
                | submit at nearly the same time.
                |
                */

                DB::statement(
                    'SELECT pg_advisory_xact_lock(?)',
                    [2026092201]
                );


                $year =
                    (int) $startDate->year;


                $prefix =
                    'LR-'
                    . $year
                    . '-';


                $lastRequest = LeaveRequest::query()
                    ->where(
                        'request_no',
                        'like',
                        $prefix . '%'
                    )
                    ->orderByDesc('request_no')
                    ->first();


                $nextSequence = 1;


                if ($lastRequest) {

                    $lastSequence =
                        (int) substr(
                            $lastRequest->request_no,
                            strlen($prefix)
                        );

                    $nextSequence =
                        $lastSequence + 1;
                }


                $requestNo =
                    $prefix
                    . str_pad(
                        (string) $nextSequence,
                        6,
                        '0',
                        STR_PAD_LEFT
                    );


                return LeaveRequest::create([
                    'request_no' =>
                        $requestNo,

                    'employee_id' =>
                        $employee->id,

                    'leave_type_id' =>
                        $leaveType->id,

                    'start_date' =>
                        $startDate->toDateString(),

                    'end_date' =>
                        $endDate->toDateString(),

                    'total_days' =>
                        $totalDays,

                    'reason' =>
                        filled(
                            $validated['reason']
                            ?? null
                        )
                            ? trim(
                                $validated['reason']
                            )
                            : null,

                    'status' =>
                        'pending',

                    'applied_at' =>
                        now(),

                    'created_by' =>
                        auth()->id(),

                    'updated_by' =>
                        auth()->id(),
                ]);
            }
        );


        return redirect()
            ->route(
                'admin.hr.leave-requests.index',
                [
                    'year' =>
                        $startDate->year,
                ]
            )
            ->with(
                'success',
                'Leave request '
                . $leaveRequest->request_no
                . ' created successfully.'
            );
    }


    /**
     * Approve a pending leave request.
     */
    public function approve(
        LeaveRequest $leaveRequest
    ): RedirectResponse {

        if (! $leaveRequest->isPending()) {

            return back()->withErrors([
                'leave_request' =>
                    'Only pending leave requests can be approved.',
            ]);
        }


        $leaveRequest->load([
            'employee',
            'leaveType',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Recheck Overlap Before Approval
        |--------------------------------------------------------------------------
        */

        $approvedOverlap = LeaveRequest::query()
            ->where(
                'employee_id',
                $leaveRequest->employee_id
            )
            ->where(
                'status',
                'approved'
            )
            ->where(
                'id',
                '<>',
                $leaveRequest->id
            )
            ->whereDate(
                'start_date',
                '<=',
                $leaveRequest
                    ->end_date
                    ->toDateString()
            )
            ->whereDate(
                'end_date',
                '>=',
                $leaveRequest
                    ->start_date
                    ->toDateString()
            )
            ->exists();


        if ($approvedOverlap) {

            return back()->withErrors([
                'leave_request' =>
                    'Approval blocked because this employee now has another approved leave overlapping these dates.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Leave Balance Check
        |--------------------------------------------------------------------------
        |
        | Leave Without Pay / other unpaid leave is not blocked by entitlement.
        | Paid leave requires sufficient remaining balance.
        |
        */

        if ($leaveRequest->leaveType->is_paid) {

            $year =
                (int) $leaveRequest
                    ->start_date
                    ->year;


            $balance = EmployeeLeaveBalance::query()
                ->where(
                    'employee_id',
                    $leaveRequest->employee_id
                )
                ->where(
                    'leave_type_id',
                    $leaveRequest->leave_type_id
                )
                ->where(
                    'leave_year',
                    $year
                )
                ->first();


            $openingBalance =
                (float) (
                    $balance?->opening_balance
                    ?? 0
                );


            $entitlement =
                (float) (
                    $balance?->entitlement
                    ?? $leaveRequest
                        ->leaveType
                        ->default_annual_entitlement
                );


            $adjustment =
                (float) (
                    $balance?->adjustment
                    ?? 0
                );


            $alreadyUsed = (float) LeaveRequest::query()
                ->where(
                    'employee_id',
                    $leaveRequest->employee_id
                )
                ->where(
                    'leave_type_id',
                    $leaveRequest->leave_type_id
                )
                ->where(
                    'status',
                    'approved'
                )
                ->where(
                    'id',
                    '<>',
                    $leaveRequest->id
                )
                ->whereYear(
                    'start_date',
                    $year
                )
                ->sum('total_days');


            $available =
                $openingBalance
                + $entitlement
                + $adjustment;


            $remainingBeforeApproval =
                $available
                - $alreadyUsed;


            if (
                (float) $leaveRequest->total_days
                >
                $remainingBeforeApproval
            ) {

                return back()->withErrors([
                    'leave_request' =>
                        'Approval blocked. '
                        . $leaveRequest->employee->full_name
                        . ' has only '
                        . number_format(
                            $remainingBeforeApproval,
                            1
                        )
                        . ' day(s) remaining for '
                        . $leaveRequest->leaveType->name
                        . ', but this request is for '
                        . number_format(
                            (float) $leaveRequest->total_days,
                            1
                        )
                        . ' day(s).',
                ]);
            }
        }


        DB::transaction(
            function () use ($leaveRequest) {

                $freshRequest =
                    LeaveRequest::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $leaveRequest->id
                        );


                if (
                    $freshRequest->status
                    !== 'pending'
                ) {
                    return;
                }


                $freshRequest->update([
                    'status' =>
                        'approved',

                    'approved_by' =>
                        auth()->id(),

                    'approved_at' =>
                        now(),

                    'updated_by' =>
                        auth()->id(),
                ]);
            }
        );


        return back()->with(
            'success',
            'Leave request '
            . $leaveRequest->request_no
            . ' approved successfully.'
        );
    }


    /**
     * Reject a pending leave request.
     */
    public function reject(
        Request $request,
        LeaveRequest $leaveRequest
    ): RedirectResponse {

        if (! $leaveRequest->isPending()) {

            return back()->withErrors([
                'leave_request' =>
                    'Only pending leave requests can be rejected.',
            ]);
        }


        $validated = $request->validate([
            'remarks' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);


        DB::transaction(
            function () use (
                $leaveRequest,
                $validated
            ) {

                $freshRequest =
                    LeaveRequest::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $leaveRequest->id
                        );


                if (
                    $freshRequest->status
                    !== 'pending'
                ) {
                    return;
                }


                $freshRequest->update([
                    'status' =>
                        'rejected',

                    'approved_by' =>
                        auth()->id(),

                    'approved_at' =>
                        now(),

                    'remarks' =>
                        trim(
                            $validated['remarks']
                        ),

                    'updated_by' =>
                        auth()->id(),
                ]);
            }
        );


        return back()->with(
            'success',
            'Leave request '
            . $leaveRequest->request_no
            . ' rejected.'
        );
    }


    /**
     * Cancel a pending or approved leave request.
     */
    public function cancel(
        Request $request,
        LeaveRequest $leaveRequest
    ): RedirectResponse {

        if (
            ! in_array(
                $leaveRequest->status,
                [
                    'pending',
                    'approved',
                ],
                true
            )
        ) {

            return back()->withErrors([
                'leave_request' =>
                    'Only pending or approved leave requests can be cancelled.',
            ]);
        }


        $validated = $request->validate([
            'remarks' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);


        DB::transaction(
            function () use (
                $leaveRequest,
                $validated
            ) {

                $freshRequest =
                    LeaveRequest::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $leaveRequest->id
                        );


                if (
                    ! in_array(
                        $freshRequest->status,
                        [
                            'pending',
                            'approved',
                        ],
                        true
                    )
                ) {
                    return;
                }


                $freshRequest->update([
                    'status' =>
                        'cancelled',

                    'remarks' =>
                        trim(
                            $validated['remarks']
                        ),

                    'updated_by' =>
                        auth()->id(),
                ]);
            }
        );


        return back()->with(
            'success',
            'Leave request '
            . $leaveRequest->request_no
            . ' cancelled.'
        );
    }
}
