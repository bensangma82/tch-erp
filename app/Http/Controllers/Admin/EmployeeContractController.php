<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeContractController extends Controller
{
    /**
     * Display contract register.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'expired',
                    'renewed',
                    'terminated',
                    'cancelled',
                ]),
            ],

            'employee_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
            ],

            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],

            'expiring' => [
                'nullable',
                Rule::in([
                    '30',
                    'expired',
                ]),
            ],
        ]);


        $query = EmployeeContract::query()
            ->with([
                'employee',
                'department',
                'previousContract',
            ]);


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


        if (! empty($validated['department_id'])) {
            $query->where(
                'department_id',
                $validated['department_id']
            );
        }


        if (
            ($validated['expiring'] ?? null)
            === '30'
        ) {
            $query
                ->where(
                    'status',
                    'active'
                )
                ->whereDate(
                    'end_date',
                    '>=',
                    today()
                )
                ->whereDate(
                    'end_date',
                    '<=',
                    today()->copy()->addDays(30)
                );
        }


        if (
            ($validated['expiring'] ?? null)
            === 'expired'
        ) {
            $query
                ->whereDate(
                    'end_date',
                    '<',
                    today()
                )
                ->whereNotIn(
                    'status',
                    [
                        'renewed',
                        'terminated',
                        'cancelled',
                    ]
                );
        }


        $contracts = $query
            ->orderByRaw(
                "CASE
                    WHEN status = 'active' THEN 0
                    ELSE 1
                END"
            )
            ->orderBy('end_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();


        $employees = Employee::query()
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();


        $departments = Department::query()
            ->orderBy('name')
            ->get();


        $activeCount = EmployeeContract::query()
            ->where('status', 'active')
            ->count();


        $expiringSoonCount = EmployeeContract::query()
            ->where('status', 'active')
            ->whereDate(
                'end_date',
                '>=',
                today()
            )
            ->whereDate(
                'end_date',
                '<=',
                today()->copy()->addDays(30)
            )
            ->count();


        $expiredCount = EmployeeContract::query()
            ->whereDate(
                'end_date',
                '<',
                today()
            )
            ->whereNotIn(
                'status',
                [
                    'renewed',
                    'terminated',
                    'cancelled',
                ]
            )
            ->count();


        $terminatedCount = EmployeeContract::query()
            ->where(
                'status',
                'terminated'
            )
            ->count();


        return view(
            'admin.hr.contracts.index',
            compact(
                'contracts',
                'employees',
                'departments',
                'activeCount',
                'expiringSoonCount',
                'expiredCount',
                'terminatedCount'
            )
        );
    }


    /**
     * Show contract creation form.
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


        $departments = Department::query()
            ->orderBy('name')
            ->get();


        return view(
            'admin.hr.contracts.create',
            compact(
                'employees',
                'departments'
            )
        );
    }


    /**
     * Store a new contract.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
            ],

            'contract_type' => [
                'required',
                'string',
                'max:50',
            ],

            'designation' => [
                'nullable',
                'string',
                'max:150',
            ],

            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
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

            'renewal_due_date' => [
                'nullable',
                'date',
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'monthly_remuneration' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'terms_summary' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        $employee = Employee::with('department')
            ->findOrFail(
                $validated['employee_id']
            );


        $contract = DB::transaction(
            function () use (
                $validated,
                $employee
            ) {

                DB::statement(
                    'SELECT pg_advisory_xact_lock(?)',
                    [2026092202]
                );


                $year =
                    (int) now()->year;


                $prefix =
                    'CTR-'
                    . $year
                    . '-';


                $lastContract = EmployeeContract::query()
                    ->where(
                        'contract_no',
                        'like',
                        $prefix . '%'
                    )
                    ->orderByDesc('contract_no')
                    ->first();


                $nextSequence = 1;


                if ($lastContract) {

                    $lastSequence =
                        (int) substr(
                            $lastContract->contract_no,
                            strlen($prefix)
                        );

                    $nextSequence =
                        $lastSequence + 1;
                }


                $contractNo =
                    $prefix
                    . str_pad(
                        (string) $nextSequence,
                        6,
                        '0',
                        STR_PAD_LEFT
                    );


                return EmployeeContract::create([
                    'employee_id' =>
                        $employee->id,

                    'contract_no' =>
                        $contractNo,

                    'contract_type' =>
                        trim(
                            $validated[
                                'contract_type'
                            ]
                        ),

                    'designation' =>
                        filled(
                            $validated[
                                'designation'
                            ]
                            ?? null
                        )
                            ? trim(
                                $validated[
                                    'designation'
                                ]
                            )
                            : $employee->designation,

                    'department_id' =>
                        $validated[
                            'department_id'
                        ]
                        ?? $employee->department_id,

                    'start_date' =>
                        $validated[
                            'start_date'
                        ],

                    'end_date' =>
                        $validated[
                            'end_date'
                        ],

                    'renewal_due_date' =>
                        $validated[
                            'renewal_due_date'
                        ]
                        ?? null,

                    'status' =>
                        'active',

                    'reference_no' =>
                        filled(
                            $validated[
                                'reference_no'
                            ]
                            ?? null
                        )
                            ? trim(
                                $validated[
                                    'reference_no'
                                ]
                            )
                            : null,

                    'monthly_remuneration' =>
                        $validated[
                            'monthly_remuneration'
                        ]
                        ?? null,

                    'terms_summary' =>
                        filled(
                            $validated[
                                'terms_summary'
                            ]
                            ?? null
                        )
                            ? trim(
                                $validated[
                                    'terms_summary'
                                ]
                            )
                            : null,

                    'remarks' =>
                        filled(
                            $validated[
                                'remarks'
                            ]
                            ?? null
                        )
                            ? trim(
                                $validated[
                                    'remarks'
                                ]
                            )
                            : null,

                    'created_by' =>
                        auth()->id(),

                    'updated_by' =>
                        auth()->id(),
                ]);
            }
        );


        return redirect()
            ->route(
                'admin.hr.contracts.index'
            )
            ->with(
                'success',
                'Contract '
                . $contract->contract_no
                . ' created successfully.'
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        EmployeeContract $contract
    ): View {

        $contract->load([
            'employee',
            'department',
        ]);


        $departments = Department::query()
            ->orderBy('name')
            ->get();


        return view(
            'admin.hr.contracts.edit',
            compact(
                'contract',
                'departments'
            )
        );
    }


    /**
     * Update contract details.
     */
    public function update(
        Request $request,
        EmployeeContract $contract
    ): RedirectResponse {

        if (
            in_array(
                $contract->status,
                [
                    'renewed',
                    'terminated',
                    'cancelled',
                ],
                true
            )
        ) {
            return back()->withErrors([
                'contract' =>
                    'Closed contracts cannot be edited.',
            ]);
        }


        $validated = $request->validate([
            'contract_type' => [
                'required',
                'string',
                'max:50',
            ],

            'designation' => [
                'nullable',
                'string',
                'max:150',
            ],

            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
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

            'renewal_due_date' => [
                'nullable',
                'date',
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'monthly_remuneration' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'terms_summary' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        $contract->update([
            'contract_type' =>
                trim(
                    $validated[
                        'contract_type'
                    ]
                ),

            'designation' =>
                filled(
                    $validated[
                        'designation'
                    ]
                    ?? null
                )
                    ? trim(
                        $validated[
                            'designation'
                        ]
                    )
                    : null,

            'department_id' =>
                $validated[
                    'department_id'
                ]
                ?? null,

            'start_date' =>
                $validated[
                    'start_date'
                ],

            'end_date' =>
                $validated[
                    'end_date'
                ],

            'renewal_due_date' =>
                $validated[
                    'renewal_due_date'
                ]
                ?? null,

            'reference_no' =>
                filled(
                    $validated[
                        'reference_no'
                    ]
                    ?? null
                )
                    ? trim(
                        $validated[
                            'reference_no'
                        ]
                    )
                    : null,

            'monthly_remuneration' =>
                $validated[
                    'monthly_remuneration'
                ]
                ?? null,

            'terms_summary' =>
                filled(
                    $validated[
                        'terms_summary'
                    ]
                    ?? null
                )
                    ? trim(
                        $validated[
                            'terms_summary'
                        ]
                    )
                    : null,

            'remarks' =>
                filled(
                    $validated[
                        'remarks'
                    ]
                    ?? null
                )
                    ? trim(
                        $validated[
                            'remarks'
                        ]
                    )
                    : null,

            'updated_by' =>
                auth()->id(),
        ]);


        return redirect()
            ->route(
                'admin.hr.contracts.index'
            )
            ->with(
                'success',
                'Contract '
                . $contract->contract_no
                . ' updated successfully.'
            );
    }


    /**
     * Renew an existing contract.
     */
    public function renew(
        Request $request,
        EmployeeContract $contract
    ): RedirectResponse {

        if (
            ! in_array(
                $contract->status,
                [
                    'active',
                    'expired',
                ],
                true
            )
        ) {
            return back()->withErrors([
                'contract' =>
                    'Only active or expired contracts can be renewed.',
            ]);
        }


        $validated = $request->validate([
            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'renewal_due_date' => [
                'nullable',
                'date',
            ],

            'monthly_remuneration' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        $newContract = DB::transaction(
            function () use (
                $contract,
                $validated
            ) {

                $lockedContract =
                    EmployeeContract::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $contract->id
                        );


                if (
                    ! in_array(
                        $lockedContract->status,
                        [
                            'active',
                            'expired',
                        ],
                        true
                    )
                ) {
                    return null;
                }


                DB::statement(
                    'SELECT pg_advisory_xact_lock(?)',
                    [2026092202]
                );


                $year =
                    (int) now()->year;


                $prefix =
                    'CTR-'
                    . $year
                    . '-';


                $lastContract = EmployeeContract::query()
                    ->where(
                        'contract_no',
                        'like',
                        $prefix . '%'
                    )
                    ->orderByDesc('contract_no')
                    ->first();


                $nextSequence = 1;


                if ($lastContract) {

                    $lastSequence =
                        (int) substr(
                            $lastContract->contract_no,
                            strlen($prefix)
                        );

                    $nextSequence =
                        $lastSequence + 1;
                }


                $contractNo =
                    $prefix
                    . str_pad(
                        (string) $nextSequence,
                        6,
                        '0',
                        STR_PAD_LEFT
                    );


                $newContract =
                    EmployeeContract::create([
                        'employee_id' =>
                            $lockedContract->employee_id,

                        'contract_no' =>
                            $contractNo,

                        'contract_type' =>
                            $lockedContract->contract_type,

                        'designation' =>
                            $lockedContract->designation,

                        'department_id' =>
                            $lockedContract->department_id,

                        'start_date' =>
                            $validated[
                                'start_date'
                            ],

                        'end_date' =>
                            $validated[
                                'end_date'
                            ],

                        'renewal_due_date' =>
                            $validated[
                                'renewal_due_date'
                            ]
                            ?? null,

                        'status' =>
                            'active',

                        'previous_contract_id' =>
                            $lockedContract->id,

                        'reference_no' =>
                            filled(
                                $validated[
                                    'reference_no'
                                ]
                                ?? null
                            )
                                ? trim(
                                    $validated[
                                        'reference_no'
                                    ]
                                )
                                : null,

                        'monthly_remuneration' =>
                            $validated[
                                'monthly_remuneration'
                            ]
                            ?? $lockedContract
                                ->monthly_remuneration,

                        'terms_summary' =>
                            $lockedContract
                                ->terms_summary,

                        'remarks' =>
                            filled(
                                $validated[
                                    'remarks'
                                ]
                                ?? null
                            )
                                ? trim(
                                    $validated[
                                        'remarks'
                                    ]
                                )
                                : null,

                        'created_by' =>
                            auth()->id(),

                        'updated_by' =>
                            auth()->id(),
                    ]);


                $lockedContract->update([
                    'status' =>
                        'renewed',

                    'updated_by' =>
                        auth()->id(),
                ]);


                return $newContract;
            }
        );


        if (! $newContract) {
            return back()->withErrors([
                'contract' =>
                    'The contract status changed before renewal could be completed.',
            ]);
        }


        return redirect()
            ->route(
                'admin.hr.contracts.index'
            )
            ->with(
                'success',
                'Contract renewed successfully as '
                . $newContract->contract_no
                . '.'
            );
    }


    /**
     * Terminate an active contract.
     */
    public function terminate(
        Request $request,
        EmployeeContract $contract
    ): RedirectResponse {

        if (
            $contract->status !== 'active'
        ) {
            return back()->withErrors([
                'contract' =>
                    'Only active contracts can be terminated.',
            ]);
        }


        $validated = $request->validate([
            'terminated_on' => [
                'required',
                'date',
            ],

            'termination_reason' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);


        $contract->update([
            'status' =>
                'terminated',

            'terminated_on' =>
                $validated[
                    'terminated_on'
                ],

            'termination_reason' =>
                trim(
                    $validated[
                        'termination_reason'
                    ]
                ),

            'updated_by' =>
                auth()->id(),
        ]);


        return redirect()
            ->route(
                'admin.hr.contracts.index'
            )
            ->with(
                'success',
                'Contract '
                . $contract->contract_no
                . ' terminated.'
            );
    }


    /**
     * Mark overdue active contracts as expired.
     */
    public function refreshStatuses(): RedirectResponse
    {
        $updated = EmployeeContract::query()
            ->where(
                'status',
                'active'
            )
            ->whereDate(
                'end_date',
                '<',
                today()
            )
            ->update([
                'status' =>
                    'expired',

                'updated_by' =>
                    auth()->id(),

                'updated_at' =>
                    now(),
            ]);


        return back()->with(
            'success',
            $updated
                . ' contract status'
                . ($updated === 1 ? '' : 'es')
                . ' refreshed.'
        );
    }
}
