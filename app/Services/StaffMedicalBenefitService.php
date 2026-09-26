<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Patient;
use App\Models\StaffMedicalBenefitAccount;
use App\Models\StaffMedicalBenefitPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StaffMedicalBenefitService
{
    /*
    |--------------------------------------------------------------------------
    | Find Policy for Date
    |--------------------------------------------------------------------------
    */

    public function policyForDate($date): ?StaffMedicalBenefitPolicy
    {
        $date = Carbon::parse($date)->toDateString();

        return StaffMedicalBenefitPolicy::query()
            ->where('is_active', true)
            ->whereDate('financial_year_start', '<=', $date)
            ->whereDate('financial_year_end', '>=', $date)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Eligibility
    |--------------------------------------------------------------------------
    */

    public function isEmployeeEligible(
        Employee $employee,
        $date
    ): bool {
        $date = Carbon::parse($date)->startOfDay();

        /*
         * Only active permanent employees qualify.
         */
        if (! $employee->is_active) {
            return false;
        }

        if (
            strtolower(trim((string) $employee->employee_type))
            !== 'permanent'
        ) {
            return false;
        }

        /*
         * Benefit cannot be used before joining TCH when the
         * employee's joining date is available.
         */
        if (
            $employee->date_of_joining &&
            $date->lt($employee->date_of_joining->copy()->startOfDay())
        ) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Create / Retrieve Annual Benefit Account
    |--------------------------------------------------------------------------
    */

    public function getOrCreateAccount(
        Employee $employee,
        $date,
        ?int $userId = null
    ): StaffMedicalBenefitAccount {
        $date = Carbon::parse($date)->startOfDay();

        $policy = $this->policyForDate($date);

        if (! $policy) {
            throw new RuntimeException(
                'No active Staff Medical Benefit policy exists for this date.'
            );
        }

        if (! $this->isEmployeeEligible($employee, $date)) {
            throw new RuntimeException(
                'Employee is not eligible for Staff Medical Benefit on this date.'
            );
        }

        return DB::transaction(function () use (
            $employee,
            $policy,
            $date,
            $userId
        ) {
            /*
             * Lock the employee row so two simultaneous requests
             * cannot create duplicate annual benefit accounts.
             */
            $lockedEmployee = Employee::query()
                ->whereKey($employee->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Re-check eligibility after obtaining the database lock.
             */
            if (! $this->isEmployeeEligible($lockedEmployee, $date)) {
                throw new RuntimeException(
                    'Employee is not eligible for Staff Medical Benefit on this date.'
                );
            }

            /*
             * Return the existing FY account when already created.
             */
            $existing = StaffMedicalBenefitAccount::query()
                ->where('employee_id', $lockedEmployee->id)
                ->whereDate(
                    'financial_year_start',
                    $policy->financial_year_start->toDateString()
                )
                ->whereDate(
                    'financial_year_end',
                    $policy->financial_year_end->toDateString()
                )
                ->first();

            if ($existing) {
                return $existing;
            }

            /*
             * Full annual entitlement is granted.
             * There is no mid-year proration.
             *
             * If joining date is unknown for an existing permanent
             * employee, eligibility begins from the policy FY start.
             */
            $eligibleFrom = $policy
                ->financial_year_start
                ->copy()
                ->startOfDay();

            if (
                $lockedEmployee->date_of_joining &&
                $lockedEmployee->date_of_joining->gt($eligibleFrom)
            ) {
                $eligibleFrom = $lockedEmployee
                    ->date_of_joining
                    ->copy()
                    ->startOfDay();
            }

            

            return StaffMedicalBenefitAccount::create([
                'employee_id' => $lockedEmployee->id,
                'policy_id' => $policy->id,

                'financial_year_start' =>
                    $policy->financial_year_start->toDateString(),

                'financial_year_end' =>
                    $policy->financial_year_end->toDateString(),

                /*
                 * Snapshot the policy limits so future policy changes
                 * do not alter historical employee accounts.
                 */
                'employee_entitlement' =>
                    $policy->employee_annual_limit,

                'dependent_family_entitlement' =>
                    $policy->dependent_family_annual_limit,

                'eligible_from' =>
                    $eligibleFrom->toDateString(),

                'eligible_until' => null,

                'status' => 'active',

                'remarks' =>
                    'Annual Staff Medical Benefit account.',

                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }


                      /*
    |--------------------------------------------------------------------------
    | Resolve Patient Staff Medical Benefit
    |--------------------------------------------------------------------------
    |
    | Read-only resolver used by Billing, Pharmacy and IP Billing.
    |
    | It determines whether the patient is:
    | - the employee, using the employee annual pool; or
    | - an eligible spouse/child, using the shared dependent-family pool.
    |
    | This method does not create accounts or post transactions.
    |
    */

    public function resolvePatientBenefit(
        Patient $patient,
        $date
    ): ?array {
        $date = Carbon::parse($date)->startOfDay();

        $policy = $this->policyForDate($date);

        if (! $policy) {
            return null;
        }

        /*
         * First check whether this Patient/UHID is directly linked
         * to a permanent employee.
         */
        $employee = Employee::query()
            ->where('patient_id', $patient->id)
            ->first();

        if (
            $employee &&
            $this->isEmployeeEligible($employee, $date)
        ) {
            $account = StaffMedicalBenefitAccount::query()
                ->where('employee_id', $employee->id)
                ->whereDate(
                    'financial_year_start',
                    $policy->financial_year_start->toDateString()
                )
                ->whereDate(
                    'financial_year_end',
                    $policy->financial_year_end->toDateString()
                )
                ->first();

            if (
                $account &&
                $account->isEligibleOn($date)
            ) {
                return [
                    'eligible' => true,
                    'beneficiary_type' => 'employee',
                    'employee' => $employee,
                    'dependent' => null,
                    'patient' => $patient,
                    'account' => $account,
                    'entitlement' =>
                        (float) $account->employee_entitlement,
                    'utilized' =>
                        (float) $account->employee_utilized,
                    'balance' =>
                        (float) $account->employee_balance,
                    'relationship' => null,
                ];
            }
        }

        /*
         * Then check whether this Patient/UHID is an active
         * spouse or child of an eligible employee.
         *
         * Parents and other relationship types are deliberately
         * excluded server-side.
         */
        $dependentRecords = $patient
            ->staffDependentRecords()
            ->with('employee')
            ->where('is_active', true)
            ->whereIn(
                'relationship',
                ['spouse', 'child']
            )
            ->get();

        foreach ($dependentRecords as $dependent) {
            if (
                ! $dependent->isEligibleOn($date)
            ) {
                continue;
            }

            $dependentEmployee = $dependent->employee;

            if (
                ! $dependentEmployee ||
                ! $this->isEmployeeEligible(
                    $dependentEmployee,
                    $date
                )
            ) {
                continue;
            }

            $account = StaffMedicalBenefitAccount::query()
                ->where(
                    'employee_id',
                    $dependentEmployee->id
                )
                ->whereDate(
                    'financial_year_start',
                    $policy->financial_year_start->toDateString()
                )
                ->whereDate(
                    'financial_year_end',
                    $policy->financial_year_end->toDateString()
                )
                ->first();

            if (
                ! $account ||
                ! $account->isEligibleOn($date)
            ) {
                continue;
            }

            return [
                'eligible' => true,
                'beneficiary_type' => 'dependent',
                'employee' => $dependentEmployee,
                'dependent' => $dependent,
                'patient' => $patient,
                'account' => $account,
                'entitlement' =>
                    (float) $account->dependent_family_entitlement,
                'utilized' =>
                    (float) $account->dependent_family_utilized,
                'balance' =>
                    (float) $account->dependent_family_balance,
                'relationship' =>
                    $dependent->relationship,
            ];
        }

        return null;
    }

}