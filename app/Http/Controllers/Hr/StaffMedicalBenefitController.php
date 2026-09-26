<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDependent;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\StaffMedicalBenefitAccount;
use App\Models\StaffMedicalBenefitPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffMedicalBenefitController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Benefit Account List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $query = StaffMedicalBenefitAccount::query()
            ->with([
                'employee.department',
                'employee.patient',
                'policy',
            ]);

        /*
         * Financial-year filter.
         */
        if ($request->filled('policy_id')) {
            $query->where(
                'policy_id',
                $request->integer('policy_id')
            );
        }

        /*
         * Employee search.
         */
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->whereHas(
                'employee',
                function ($employeeQuery) use ($search) {
                    $employeeQuery->where(
                        function ($q) use ($search) {
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
                                );
                        }
                    );
                }
            );
        }

        $accounts = $query
            ->orderByDesc('financial_year_start')
            ->orderBy('employee_id')
            ->paginate(25)
            ->withQueryString();

        $policies = StaffMedicalBenefitPolicy::query()
            ->orderByDesc('financial_year_start')
            ->get();

        return view(
            'admin.hr.medical-benefits.index',
            compact(
                'accounts',
                'policies'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Benefit Account Details
    |--------------------------------------------------------------------------
    */

    public function show(
        StaffMedicalBenefitAccount $benefitAccount
    ): View {
        $benefitAccount->load([
            'employee.department',
            'employee.patient',
            'employee.medicalDependents.patient',
            'policy',
            'transactions.patient',
            'transactions.dependent',
            'transactions.createdBy',
        ]);

        return view(
            'admin.hr.medical-benefits.show',
            compact('benefitAccount')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Search Patient Registry
    |--------------------------------------------------------------------------
    |
    | Search only. No automatic linking is performed.
    |
    */

    public function searchPatients(Request $request)
    {
        $validated = $request->validate([
            'q' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
        ]);

        $search = trim($validated['q']);

        $patients = Patient::query()
            ->where('is_active', true)
            ->where(function ($query) use ($search) {
                $query
                    ->where(
                        'uhid',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'mrd_number',
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
                        'phone',
                        'ilike',
                        "%{$search}%"
                    );
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(20)
            ->get([
                'id',
                'uhid',
                'mrd_number',
                'title',
                'first_name',
                'middle_name',
                'last_name',
                'date_of_birth',
                'sex',
                'phone',
            ]);

        return response()->json([
            'patients' => $patients->map(
                function (Patient $patient) {
                    return [
                        'id' => $patient->id,
                        'uhid' => $patient->uhid,
                        'mrd_number' => $patient->mrd_number,
                        'name' => $patient->full_name,
                        'date_of_birth' =>
                            $patient->date_of_birth?->toDateString(),
                        'sex' => $patient->sex,
                        'phone' => $patient->phone,
                    ];
                }
            )->values(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Link Employee to Patient / UHID
    |--------------------------------------------------------------------------
    */

    public function linkEmployeePatient(
        Request $request,
        Employee $employee
    ): RedirectResponse {
        $validated = $request->validate([
            'patient_id' => [
                'required',
                'integer',
                'exists:patients,id',
            ],
        ]);

        $patient = Patient::query()
            ->whereKey($validated['patient_id'])
            ->where('is_active', true)
            ->firstOrFail();

        /*
         * One Patient/UHID cannot be linked directly to two employees.
         */
        $alreadyLinkedEmployee = Employee::query()
            ->where('patient_id', $patient->id)
            ->whereKeyNot($employee->id)
            ->first();

        if ($alreadyLinkedEmployee) {
            return back()->withErrors([
                'patient_id' =>
                    'This Patient/UHID is already linked to employee '
                    . $alreadyLinkedEmployee->employee_code
                    . '.',
            ]);
        }

        $employee->update([
            'patient_id' => $patient->id,
        ]);

        return back()->with(
            'success',
            'Employee successfully linked to patient '
            . $patient->uhid
            . '.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Unlink Employee from Patient / UHID
    |--------------------------------------------------------------------------
    |
    | This removes only the HR-to-patient link.
    | It does not delete the Patient/UHID.
    |
    */

    public function unlinkEmployeePatient(
        Employee $employee
    ): RedirectResponse {
        if (! $employee->patient_id) {
            return back()->with(
                'success',
                'Employee does not currently have a linked Patient/UHID.'
            );
        }

        /*
         * Once benefit transactions exist for the employee beneficiary,
         * the historical patient linkage should not be casually changed.
         */
        $hasTransactions = StaffMedicalBenefitAccount::query()
            ->where('employee_id', $employee->id)
            ->whereHas(
                'transactions',
                function ($query) use ($employee) {
                    $query
                        ->where(
                            'beneficiary_type',
                            'employee'
                        )
                        ->where(
                            'patient_id',
                            $employee->patient_id
                        );
                }
            )
            ->exists();

        if ($hasTransactions) {
            return back()->withErrors([
                'patient_id' =>
                    'This employee already has Staff Medical Benefit '
                    . 'transactions. The Patient/UHID link cannot be '
                    . 'removed directly.',
            ]);
        }

        $employee->update([
            'patient_id' => null,
        ]);

        return back()->with(
            'success',
            'Patient/UHID link removed successfully.'
        );
    }

                  /*
    |--------------------------------------------------------------------------
    | Register Medical Benefit Dependent
    |--------------------------------------------------------------------------
    |
    | TCH policy permits spouse and children only.
    | All registered dependents share one family annual benefit pool.
    |
    */

    public function storeDependent(
        Request $request,
        StaffMedicalBenefitAccount $benefitAccount
    ): RedirectResponse {
        $validated = $request->validate([
            'patient_id' => [
                'required',
                'integer',
                'exists:patients,id',
            ],
            'relationship' => [
                'required',
                'string',
                'in:spouse,child',
            ],
            'eligible_from' => [
                'required',
                'date',
            ],
        ]);

        $benefitAccount->load('employee');

        /*
         * Dependents can only be added to an active benefit account.
         */
        if ($benefitAccount->status !== 'active') {
            return back()->withErrors([
                'dependent' =>
                    'Dependents cannot be added to an inactive '
                    . 'Staff Medical Benefit account.',
            ]);
        }

        /*
         * Eligibility must fall inside this benefit account's
         * financial-year and employee eligibility period.
         */
        $eligibleFrom = \Carbon\Carbon::parse(
            $validated['eligible_from']
        )->startOfDay();

        $minimumEligibleDate = $benefitAccount
            ->eligible_from
            ->copy()
            ->startOfDay();

        $maximumEligibleDate = $benefitAccount
            ->financial_year_end
            ->copy()
            ->endOfDay();

        if ($benefitAccount->eligible_until) {
            $employeeEligibleUntil = $benefitAccount
                ->eligible_until
                ->copy()
                ->endOfDay();

            if ($employeeEligibleUntil->lt($maximumEligibleDate)) {
                $maximumEligibleDate = $employeeEligibleUntil;
            }
        }

        if (
            $eligibleFrom->lt($minimumEligibleDate) ||
            $eligibleFrom->gt($maximumEligibleDate)
        ) {
            return back()->withErrors([
                'eligible_from' =>
                    'Dependent eligibility date must fall within '
                    . 'the employee benefit eligibility period.',
            ]);
        }

        /*
         * The selected patient must be an active Patient Registry record.
         */
        $patient = Patient::query()
            ->whereKey($validated['patient_id'])
            ->where('is_active', true)
            ->firstOrFail();

        /*
         * The employee cannot be registered as their own dependent.
         */
        if (
            $benefitAccount->employee->patient_id &&
            (int) $benefitAccount->employee->patient_id ===
            (int) $patient->id
        ) {
            return back()->withErrors([
                'patient_id' =>
                    'The employee cannot be registered as their '
                    . 'own dependent.',
            ]);
        }

        /*
         * Do not register the same patient twice under the same employee.
         */
        $existingDependent = EmployeeDependent::query()
            ->where(
                'employee_id',
                $benefitAccount->employee_id
            )
            ->where(
                'patient_id',
                $patient->id
            )
            ->first();

        if ($existingDependent) {
            return back()->withErrors([
                'patient_id' =>
                    'This Patient/UHID is already registered as a '
                    . 'dependent of this employee.',
            ]);
        }

        /*
         * A patient directly linked as another employee is not
         * automatically rejected here. This matters when both spouses
         * are TCH employees. Dual-benefit rules will be enforced at
         * utilization rather than corrupting the patient identity.
         */

        EmployeeDependent::create([
            'employee_id' =>
                $benefitAccount->employee_id,
            'patient_id' =>
                $patient->id,
            'relationship' =>
                $validated['relationship'],
            'eligible_from' =>
                $eligibleFrom->toDateString(),
            'eligible_until' =>
                $benefitAccount->eligible_until?->toDateString(),
            'is_active' =>
                true,
            'remarks' =>
                null,
            'created_by' =>
                auth()->id(),
            'updated_by' =>
                auth()->id(),
        ]);

        return back()->with(
            'success',
            ucfirst($validated['relationship'])
            . ' successfully registered as an eligible dependent.'
        );
    }

                        /*
    |--------------------------------------------------------------------------
    | Deactivate Medical Benefit Dependent
    |--------------------------------------------------------------------------
    |
    | Dependents are never deleted from benefit history.
    | Deactivation ends future eligibility while preserving prior records.
    |
    */

    public function deactivateDependent(
        StaffMedicalBenefitAccount $benefitAccount,
        EmployeeDependent $dependent
    ): RedirectResponse {
        /*
         * The dependent must belong to the employee whose
         * benefit account is currently being managed.
         */
        if (
            (int) $dependent->employee_id !==
            (int) $benefitAccount->employee_id
        ) {
            abort(404);
        }

        if (! $dependent->is_active) {
            return back()->with(
                'success',
                'Dependent is already inactive.'
            );
        }

        /*
         * End eligibility today, but never before the original
         * eligibility start date.
         */
        $today = now()->startOfDay();

        if (
            $dependent->eligible_from &&
            $today->lt($dependent->eligible_from->copy()->startOfDay())
        ) {
            return back()->withErrors([
                'dependent' =>
                    'This dependent cannot be deactivated before '
                    . 'their eligibility start date.',
            ]);
        }

        $dependent->update([
            'is_active' => false,
            'eligible_until' => $today->toDateString(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with(
            'success',
            'Dependent deactivated successfully. '
            . 'Previous benefit history has been preserved.'
        );
    }

}