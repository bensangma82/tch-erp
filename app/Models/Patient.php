<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uhid',
        'mrd_number',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'age',
        'sex',
        'phone',
        'alternate_phone',
        'email',
        'address',
        'locality',
        'district',
        'state',
        'pin_code',
        'blood_group',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'abha_number',
        'mhis_number',
        'known_allergies',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Display Name
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
    | Clinical / Billing Relationships
    |--------------------------------------------------------------------------
    */

    public function encounters(): HasMany
    {
        return $this->hasMany(
            Encounter::class
        );
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(
            Invoice::class
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class
        );
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(
            ServiceOrder::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Staff Medical Benefit
    |--------------------------------------------------------------------------
    |
    | staffEmployee:
    | Returns the Employee record when this patient/UHID belongs directly
    | to a hospital employee.
    |
    | staffDependentRecords:
    | Returns dependent registrations in which this patient/UHID has been
    | registered as an employee's dependent.
    |
    | medicalBenefitTransactions:
    | Complete Staff Medical Benefit ledger activity for this patient.
    |
    */

    public function staffEmployee(): HasOne
    {
        return $this->hasOne(
            Employee::class,
            'patient_id'
        );
    }

    public function staffDependentRecords(): HasMany
    {
        return $this->hasMany(
            EmployeeDependent::class,
            'patient_id'
        );
    }

    public function medicalBenefitTransactions(): HasMany
    {
        return $this->hasMany(
            StaffMedicalBenefitTransaction::class,
            'patient_id'
        );
    }
}