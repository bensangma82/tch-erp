<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code',
        'patient_id',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'designation',
        'department_id',
        'employee_type',
        'professional_registration_no',
        'qualification',
        'speciality',
        'phone',
        'email',
        'date_of_joining',
        'is_doctor',
        'is_active',
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'is_doctor' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Department
    |--------------------------------------------------------------------------
    */

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Payroll
    |--------------------------------------------------------------------------
    */

    public function payrollAdjustments(): HasMany
    {
        return $this->hasMany(
            PayrollAdjustment::class,
            'employee_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Display Name
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        return trim(
            collect([
                $this->title,
                $this->first_name,
                $this->middle_name,
                $this->last_name,
            ])
                ->filter()
                ->implode(' ')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Clinical Relationships
    |--------------------------------------------------------------------------
    */

    public function encounters(): HasMany
    {
        return $this->hasMany(
            Encounter::class,
            'doctor_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Staff Medical Benefit
    |--------------------------------------------------------------------------
    |
    | patient:
    | Links the employee's HR record to the hospital Patient/UHID record.
    |
    | medicalDependents:
    | Registered dependents who may use the employee's shared dependent
    | family medical-benefit pool.
    |
    | medicalBenefitAccounts:
    | One historical benefit account for each financial year.
    |
    */

    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class,
            'patient_id'
        );
    }

    public function medicalDependents(): HasMany
    {
        return $this->hasMany(
            EmployeeDependent::class,
            'employee_id'
        );
    }

    public function medicalBenefitAccounts(): HasMany
    {
        return $this->hasMany(
            StaffMedicalBenefitAccount::class,
            'employee_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Human Resources - Leave Management
    |--------------------------------------------------------------------------
    */

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(
            EmployeeLeaveBalance::class
        );
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(
            LeaveRequest::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Human Resources - Contract Management
    |--------------------------------------------------------------------------
    */

    public function contracts(): HasMany
    {
        return $this->hasMany(
            EmployeeContract::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Human Resources - Employee Documents
    |--------------------------------------------------------------------------
    */

    public function documents(): HasMany
    {
        return $this->hasMany(
            EmployeeDocument::class
        );
    }
}