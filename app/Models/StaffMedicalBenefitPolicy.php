<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMedicalBenefitPolicy extends Model
{
    protected $fillable = [
        'name',
        'financial_year_start',
        'financial_year_end',
        'employee_annual_limit',
        'dependent_family_annual_limit',
        'carry_forward',
        'is_active',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'financial_year_start' => 'date',
        'financial_year_end' => 'date',
        'employee_annual_limit' => 'decimal:2',
        'dependent_family_annual_limit' => 'decimal:2',
        'carry_forward' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function accounts(): HasMany
    {
        return $this->hasMany(
            StaffMedicalBenefitAccount::class,
            'policy_id'
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
    | Financial Year Helpers
    |--------------------------------------------------------------------------
    */

    public function containsDate($date): bool
    {
        $date = \Carbon\Carbon::parse($date);

        return $date->betweenIncluded(
            $this->financial_year_start,
            $this->financial_year_end
        );
    }

    public function getFinancialYearLabelAttribute(): string
    {
        return $this->financial_year_start->format('Y')
            . '-'
            . $this->financial_year_end->format('y');
    }
}