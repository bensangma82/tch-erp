<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'calculation_type',
        'percentage_of_component_id',
        'default_percentage',
        'default_amount',
        'is_taxable',
        'affects_gross',
        'is_statutory',
        'is_recurring',
        'sort_order',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_percentage' => 'decimal:4',
        'default_amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'affects_gross' => 'boolean',
        'is_statutory' => 'boolean',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function percentageOfComponent(): BelongsTo
    {
        return $this->belongsTo(
            SalaryComponent::class,
            'percentage_of_component_id'
        );
    }

    public function dependentComponents(): HasMany
    {
        return $this->hasMany(
            SalaryComponent::class,
            'percentage_of_component_id'
        );
    }

    public function salaryStructureItems(): HasMany
    {
        return $this->hasMany(
            EmployeeSalaryStructureItem::class,
            'salary_component_id'
        );
    }

    public function payrollEntryItems(): HasMany
    {
        return $this->hasMany(
            PayrollEntryItem::class,
            'salary_component_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isEarning(): bool
    {
        return $this->type === 'earning';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }

    public function isPercentageBased(): bool
    {
        return $this->calculation_type === 'percentage';
    }
}