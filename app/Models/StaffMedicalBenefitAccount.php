<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMedicalBenefitAccount extends Model
{
    protected $fillable = [
        'employee_id',
        'policy_id',
        'financial_year_start',
        'financial_year_end',
        'employee_entitlement',
        'dependent_family_entitlement',
        'eligible_from',
        'eligible_until',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'financial_year_start' => 'date',
        'financial_year_end' => 'date',
        'eligible_from' => 'date',
        'eligible_until' => 'date',
        'employee_entitlement' => 'decimal:2',
        'dependent_family_entitlement' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(
            StaffMedicalBenefitPolicy::class,
            'policy_id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            StaffMedicalBenefitTransaction::class,
            'benefit_account_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eligibility
    |--------------------------------------------------------------------------
    */

    public function isEligibleOn($date): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $date = \Carbon\Carbon::parse($date);

        if ($date->lt($this->financial_year_start)) {
            return false;
        }

        if ($date->gt($this->financial_year_end)) {
            return false;
        }

        if (
            $this->eligible_from &&
            $date->lt($this->eligible_from)
        ) {
            return false;
        }

        if (
            $this->eligible_until &&
            $date->gt($this->eligible_until)
        ) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Pool
    |--------------------------------------------------------------------------
    */

    public function getEmployeeUtilizedAttribute(): float
    {
        $utilization = $this->transactions()
            ->where('beneficiary_type', 'employee')
            ->where('transaction_type', 'utilization')
            ->where('status', 'active')
            ->sum('amount');

        $reversals = $this->transactions()
            ->where('beneficiary_type', 'employee')
            ->where('transaction_type', 'reversal')
            ->where('status', 'active')
            ->sum('amount');

        return max(
            0,
            (float) $utilization - (float) $reversals
        );
    }

    public function getEmployeeBalanceAttribute(): float
    {
        return max(
            0,
            (float) $this->employee_entitlement
                - $this->employee_utilized
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Dependent Family Pool
    |--------------------------------------------------------------------------
    */

    public function getDependentFamilyUtilizedAttribute(): float
    {
        $utilization = $this->transactions()
            ->where('beneficiary_type', 'dependent')
            ->where('transaction_type', 'utilization')
            ->where('status', 'active')
            ->sum('amount');

        $reversals = $this->transactions()
            ->where('beneficiary_type', 'dependent')
            ->where('transaction_type', 'reversal')
            ->where('status', 'active')
            ->sum('amount');

        return max(
            0,
            (float) $utilization - (float) $reversals
        );
    }

    public function getDependentFamilyBalanceAttribute(): float
    {
        return max(
            0,
            (float) $this->dependent_family_entitlement
                - $this->dependent_family_utilized
        );
    }
}