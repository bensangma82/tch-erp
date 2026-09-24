<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntryItem extends Model
{
    protected $fillable = [
        'payroll_entry_id',
        'salary_component_id',
        'component_code',
        'component_name',
        'type',
        'calculation_type',
        'base_amount',
        'percentage',
        'amount',
        'is_taxable',
        'is_statutory',
        'is_recurring',
        'is_prorated',
        'proration_factor',
        'sort_order',
        'remarks',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'percentage' => 'decimal:4',
        'amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_statutory' => 'boolean',
        'is_recurring' => 'boolean',
        'is_prorated' => 'boolean',
        'proration_factor' => 'decimal:6',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function payrollEntry(): BelongsTo
    {
        return $this->belongsTo(
            PayrollEntry::class,
            'payroll_entry_id'
        );
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(
            SalaryComponent::class,
            'salary_component_id'
        );
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

    public function isAdjustment(): bool
    {
        return $this->calculation_type === 'adjustment';
    }
}