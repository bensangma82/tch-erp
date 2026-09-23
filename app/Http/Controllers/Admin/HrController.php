<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use Illuminate\View\View;

class HrController extends Controller
{
    /**
     * Display the Human Resources dashboard.
     */
    public function index(): View
    {
        /*
        |--------------------------------------------------------------------------
        | Workforce Summary
        |--------------------------------------------------------------------------
        */

        $totalEmployees = Employee::query()
            ->count();

        $activeEmployees = Employee::query()
            ->where('is_active', true)
            ->count();

        $inactiveEmployees = Employee::query()
            ->where('is_active', false)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Employment Type Summary
        |--------------------------------------------------------------------------
        */

        $employmentTypeCounts = Employee::query()
            ->selectRaw(
                "LOWER(COALESCE(employee_type, 'unspecified')) as employee_type_key"
            )
            ->selectRaw(
                'COUNT(*) as total'
            )
            ->groupByRaw(
                "LOWER(COALESCE(employee_type, 'unspecified'))"
            )
            ->pluck(
                'total',
                'employee_type_key'
            );


        $permanentEmployees =
            (int) (
                $employmentTypeCounts['permanent']
                ?? 0
            );

        $temporaryEmployees =
            (int) (
                $employmentTypeCounts['temporary']
                ?? 0
            );

        $contractualEmployees =
            (int) (
                $employmentTypeCounts['contractual']
                ?? 0
            );

        $unspecifiedEmploymentType =
            (int) (
                $employmentTypeCounts['unspecified']
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | Clinical Workforce Summary
        |--------------------------------------------------------------------------
        */

        $doctorCount = Employee::query()
            ->where('is_doctor', true)
            ->count();

        $nonDoctorCount = Employee::query()
            ->where('is_doctor', false)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Department-wise Staff Strength
        |--------------------------------------------------------------------------
        */

        $departmentStrength = Employee::query()
            ->whereNotNull('department_id')
            ->with('department')
            ->selectRaw(
                'department_id, COUNT(*) as total_staff'
            )
            ->selectRaw(
                'SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active_staff'
            )
            ->groupBy('department_id')
            ->orderByDesc('total_staff')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Employees Without Department
        |--------------------------------------------------------------------------
        */

        $employeesWithoutDepartment = Employee::query()
            ->whereNull('department_id')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Recent Joiners
        |--------------------------------------------------------------------------
        */

        $recentEmployees = Employee::query()
            ->with('department')
            ->whereNotNull('date_of_joining')
            ->orderByDesc('date_of_joining')
            ->orderByDesc('id')
            ->limit(8)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Active Department Count
        |--------------------------------------------------------------------------
        */

        $activeDepartmentCount = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('department_id')
            ->distinct()
            ->count('department_id');


        /*
        |--------------------------------------------------------------------------
        | Joining Statistics
        |--------------------------------------------------------------------------
        */

        $joinedThisMonth = Employee::query()
            ->whereNotNull('date_of_joining')
            ->whereYear(
                'date_of_joining',
                now()->year
            )
            ->whereMonth(
                'date_of_joining',
                now()->month
            )
            ->count();

        $joinedThisYear = Employee::query()
            ->whereNotNull('date_of_joining')
            ->whereYear(
                'date_of_joining',
                now()->year
            )
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Data Quality Indicators
        |--------------------------------------------------------------------------
        */

        $employeesWithoutJoiningDate = Employee::query()
            ->whereNull('date_of_joining')
            ->count();

        $employeesWithoutDesignation = Employee::query()
            ->where(function ($query) {
                $query
                    ->whereNull('designation')
                    ->orWhere(
                        'designation',
                        ''
                    );
            })
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Leave Management Metrics
        |--------------------------------------------------------------------------
        */

        $pendingLeaveCount = LeaveRequest::query()
            ->where('status', 'pending')
            ->count();


        $approvedLeaveThisMonth = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereYear(
                'start_date',
                now()->year
            )
            ->whereMonth(
                'start_date',
                now()->month
            )
            ->count();


        $employeesOnLeaveToday = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate(
                'start_date',
                '<=',
                today()
            )
            ->whereDate(
                'end_date',
                '>=',
                today()
            )
            ->distinct()
            ->count('employee_id');


        $pendingLeaveRequests = LeaveRequest::query()
            ->with([
                'employee.department',
                'leaveType',
            ])
            ->where('status', 'pending')
            ->orderBy('start_date')
            ->orderBy('id')
            ->limit(6)
            ->get();




        /*
        |--------------------------------------------------------------------------
        | Contract Management Metrics
        |--------------------------------------------------------------------------
        */

        $activeContractCount = EmployeeContract::query()
            ->where('status', 'active')
            ->count();


        $expiringContractCount = EmployeeContract::query()
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


        $expiredContractCount = EmployeeContract::query()
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


        $contractsRequiringAttention = EmployeeContract::query()
            ->with([
                'employee.department',
                'department',
            ])
            ->whereIn(
                'status',
                [
                    'active',
                    'expired',
                ]
            )
            ->whereDate(
                'end_date',
                '<=',
                today()->copy()->addDays(30)
            )
            ->orderBy('end_date')
            ->orderBy('id')
            ->limit(6)
            ->get();



        /*
        |--------------------------------------------------------------------------
        | Employee Document Metrics
        |--------------------------------------------------------------------------
        */

        $pendingDocumentVerificationCount = EmployeeDocument::query()
            ->where(
                'verification_status',
                'pending'
            )
            ->count();


        $expiringDocumentCount = EmployeeDocument::query()
            ->whereNotNull('expiry_date')
            ->whereDate(
                'expiry_date',
                '>=',
                today()
            )
            ->whereDate(
                'expiry_date',
                '<=',
                today()->copy()->addDays(30)
            )
            ->count();


        $expiredDocumentCount = EmployeeDocument::query()
            ->whereNotNull('expiry_date')
            ->whereDate(
                'expiry_date',
                '<',
                today()
            )
            ->count();


        $documentsRequiringAttention = EmployeeDocument::query()
            ->with([
                'employee.department',
            ])
            ->where(function ($query) {
                $query
                    ->where('verification_status', 'pending')
                    ->orWhere(function ($expiryQuery) {
                        $expiryQuery
                            ->whereNotNull('expiry_date')
                            ->whereDate(
                                'expiry_date',
                                '<=',
                                today()->copy()->addDays(30)
                            );
                    });
            })
            ->orderByRaw(
                'CASE
                    WHEN expiry_date IS NOT NULL
                         AND expiry_date < CURRENT_DATE
                    THEN 0
                    WHEN expiry_date IS NOT NULL
                         AND expiry_date <= CURRENT_DATE + INTERVAL \'30 days\'
                    THEN 1
                    WHEN verification_status = \'pending\'
                    THEN 2
                    ELSE 3
                END'
            )
            ->orderBy('expiry_date')
            ->orderBy('id')
            ->limit(6)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard View
        |--------------------------------------------------------------------------
        */

        return view(
            'admin.hr.index',
            compact(
                'totalEmployees',
                'activeEmployees',
                'inactiveEmployees',
                'permanentEmployees',
                'temporaryEmployees',
                'contractualEmployees',
                'unspecifiedEmploymentType',
                'doctorCount',
                'nonDoctorCount',
                'departmentStrength',
                'employeesWithoutDepartment',
                'recentEmployees',
                'activeDepartmentCount',
                'joinedThisMonth',
                'joinedThisYear',
                'employeesWithoutJoiningDate',
                'employeesWithoutDesignation',
                'pendingLeaveCount',
                'approvedLeaveThisMonth',
                'employeesOnLeaveToday',
                'pendingLeaveRequests',
                'activeContractCount',
                'expiringContractCount',
                'expiredContractCount',
                'contractsRequiringAttention',
                'pendingDocumentVerificationCount',
                'expiringDocumentCount',
                'expiredDocumentCount',
                'documentsRequiringAttention'
            )
        );
    }
}
