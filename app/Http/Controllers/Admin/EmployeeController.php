<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request)
    {
        $query = Employee::query()
            ->with([
                'department',
                'user',
            ])
            ->orderBy('first_name')
            ->orderBy('last_name');


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($q) use ($search) {

                $q->where(
                    'employee_code',
                    'ilike',
                    "%{$search}%"
                )
                    ->orWhere(
                        'first_name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'middle_name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'last_name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'designation',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'qualification',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'speciality',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'phone',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'email',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'professional_registration_no',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'user',
                        function ($userQuery) use ($search) {

                            $userQuery
                                ->where(
                                    'name',
                                    'ilike',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'ilike',
                                    "%{$search}%"
                                );
                        }
                    );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Department filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('department_id')) {

            $query->where(
                'department_id',
                $request->department_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Employee type filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('employee_type')) {

            $query->where(
                'employee_type',
                $request->employee_type
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Doctor filter
        |--------------------------------------------------------------------------
        */

        if ($request->doctor === 'yes') {

            $query->where(
                'is_doctor',
                true
            );

        } elseif ($request->doctor === 'no') {

            $query->where(
                'is_doctor',
                false
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Active status filter
        |--------------------------------------------------------------------------
        */

        if ($request->status === 'active') {

            $query->where(
                'is_active',
                true
            );

        } elseif ($request->status === 'inactive') {

            $query->where(
                'is_active',
                false
            );
        }


        $employees = $query
            ->paginate(25)
            ->withQueryString();


        $departments = Department::query()
            ->orderBy('name')
            ->get();


        $employeeTypes = Employee::query()
            ->whereNotNull('employee_type')
            ->where(
                'employee_type',
                '<>',
                ''
            )
            ->distinct()
            ->orderBy('employee_type')
            ->pluck('employee_type');


        return view(
            'admin.employees.index',
            compact(
                'employees',
                'departments',
                'employeeTypes'
            )
        );
    }


    /**
     * Show form for creating a new employee.
     */
    public function create()
    {
        $departments = Department::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();


        $employeeTypes = Employee::query()
            ->whereNotNull('employee_type')
            ->where(
                'employee_type',
                '<>',
                ''
            )
            ->distinct()
            ->orderBy('employee_type')
            ->pluck('employee_type');


        /*
        |--------------------------------------------------------------------------
        | ERP users not linked to another employee
        |--------------------------------------------------------------------------
        */

        $linkedUserIds = Employee::withTrashed()
            ->whereNotNull('user_id')
            ->pluck('user_id');


        $users = User::query()
            ->where(
                'is_active',
                true
            )
            ->whereNotIn(
                'id',
                $linkedUserIds
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
            ]);


        return view(
            'admin.employees.create',
            compact(
                'departments',
                'employeeTypes',
                'users'
            )
        );
    }


    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | ERP User Link
            |--------------------------------------------------------------------------
            */

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                'unique:employees,user_id',
            ],


            /*
            |--------------------------------------------------------------------------
            | Employee Details
            |--------------------------------------------------------------------------
            */

            'title' => [
                'nullable',
                'string',
                'max:20',
            ],

            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:100',
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

            'employee_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'professional_registration_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'qualification' => [
                'nullable',
                'string',
                'max:255',
            ],

            'speciality' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'date_of_joining' => [
                'nullable',
                'date',
            ],

            'is_doctor' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Checkbox handling
        |--------------------------------------------------------------------------
        */

        $validated['is_doctor'] =
            $request->boolean('is_doctor');

        $validated['is_active'] =
            $request->boolean('is_active');


        $validated['employee_type'] =
            $validated['employee_type']
            ?? 'permanent';


        /*
        |--------------------------------------------------------------------------
        | Normalise optional fields
        |--------------------------------------------------------------------------
        */

        $validated['user_id'] =
            ! empty($validated['user_id'])
                ? (int) $validated['user_id']
                : null;


        if (isset($validated['email'])) {

            $validated['email'] =
                $validated['email']
                    ? strtolower(
                        trim($validated['email'])
                    )
                    : null;
        }


        if (isset($validated['phone'])) {

            $validated['phone'] =
                $validated['phone']
                    ? trim($validated['phone'])
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Generate Employee Code
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use ($validated) {

                DB::statement(
                    "SELECT pg_advisory_xact_lock(hashtext('tch_employee_code_sequence'))"
                );


                $maxNumber =
                    Employee::withTrashed()
                        ->where(
                            'employee_code',
                            'like',
                            'TCH-%'
                        )
                        ->selectRaw(
                            "COALESCE(MAX(CAST(SUBSTRING(employee_code FROM '[0-9]+$') AS INTEGER)), 0) AS max_number"
                        )
                        ->value('max_number');


                $validated['employee_code'] =
                    'TCH-'
                    .
                    str_pad(
                        (string) (
                            ((int) $maxNumber)
                            + 1
                        ),
                        4,
                        '0',
                        STR_PAD_LEFT
                    );


                Employee::create(
                    $validated
                );
            }
        );


        return redirect()
            ->route(
                'admin.employees.index'
            )
            ->with(
                'success',
                'Employee created successfully.'
            );
    }


    /**
     * Show form for editing an employee.
     */
    public function edit(Employee $employee)
    {
        $departments = Department::query()
            ->orderBy('name')
            ->get();


        $employeeTypes = Employee::query()
            ->whereNotNull('employee_type')
            ->where(
                'employee_type',
                '<>',
                ''
            )
            ->distinct()
            ->orderBy('employee_type')
            ->pluck('employee_type');


        /*
        |--------------------------------------------------------------------------
        | ERP users available for this employee
        |--------------------------------------------------------------------------
        |
        | Users linked to OTHER employee records are excluded.
        | The employee's current linked account remains selectable.
        |
        */

        $linkedUserIds =
            Employee::withTrashed()
                ->where(
                    'id',
                    '!=',
                    $employee->id
                )
                ->whereNotNull('user_id')
                ->pluck('user_id');


        $users = User::query()
            ->where(
                'is_active',
                true
            )
            ->whereNotIn(
                'id',
                $linkedUserIds
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
            ]);


        return view(
            'admin.employees.edit',
            compact(
                'employee',
                'departments',
                'employeeTypes',
                'users'
            )
        );
    }


    /**
     * Update an existing employee.
     */
    public function update(
        Request $request,
        Employee $employee
    ) {
        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | ERP User Link
            |--------------------------------------------------------------------------
            */

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',

                Rule::unique(
                    'employees',
                    'user_id'
                )->ignore(
                    $employee->id
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Employee Details
            |--------------------------------------------------------------------------
            */

            'employee_code' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'employees',
                    'employee_code'
                )
                    ->ignore(
                        $employee->id
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],

            'title' => [
                'nullable',
                'string',
                'max:20',
            ],

            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'middle_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:100',
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

            'employee_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'professional_registration_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'qualification' => [
                'nullable',
                'string',
                'max:255',
            ],

            'speciality' => [
                'nullable',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'date_of_joining' => [
                'nullable',
                'date',
            ],

            'is_doctor' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Checkbox handling
        |--------------------------------------------------------------------------
        */

        $validated['is_doctor'] =
            $request->boolean('is_doctor');

        $validated['is_active'] =
            $request->boolean('is_active');


        /*
        |--------------------------------------------------------------------------
        | Normalise fields
        |--------------------------------------------------------------------------
        */

        $validated['employee_code'] =
            trim(
                $validated['employee_code']
            );


        $validated['user_id'] =
            ! empty($validated['user_id'])
                ? (int) $validated['user_id']
                : null;


        if (isset($validated['email'])) {

            $validated['email'] =
                $validated['email']
                    ? strtolower(
                        trim($validated['email'])
                    )
                    : null;
        }


        if (isset($validated['phone'])) {

            $validated['phone'] =
                $validated['phone']
                    ? trim($validated['phone'])
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Update Employee
        |--------------------------------------------------------------------------
        */

        $employee->update(
            $validated
        );


        return redirect()
            ->route(
                'admin.employees.index'
            )
            ->with(
                'success',
                'Employee updated successfully.'
            );
    }
}