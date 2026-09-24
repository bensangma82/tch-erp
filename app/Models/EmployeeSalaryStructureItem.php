<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryStructureItem extends Model
{
    protected $fillable = [
        'employee_salary_structure_id',
        'salary_component_id',
        'calculation_type',
        'amount',
        'percentage',
        'percentage_of_component_id',
        'minimum_amount',
        'maximum_amount',
        'is_active',
        'sort_order',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:4',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeSalaryStructure::class,
            'employee_salary_structure_id'
        );
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(
            SalaryComponent::class,
            'salary_component_id'
        );
    }

    public function percentageOfComponent(): BelongsTo
    {
        return $this->belongsTo(
            SalaryComponent::class,
            'percentage_of_component_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isFixed(): bool
    {
        return $this->calculation_type === 'fixed';
    }

    public function isPercentageBased(): bool
    {
        return $this->calculation_type === 'percentage';
    }

    public function calculateAmount(?float $baseAmount = null): float
    {
        if ($this->isFixed()) {
            return round((float) ($this->amount ?? 0), 2);
        }

        if ($this->isPercentageBased()) {
            $base = $baseAmount ?? 0;
            $percentage = (float) ($this->percentage ?? 0);

            $calculated = $base * ($percentage / 100);

            if ($this->minimum_amount !== null) {
                $calculated = max(
                    $calculated,
                    (float) $this->minimum_amount
                );
            }

            if ($this->maximum_amount !== null) {
                $calculated = min(
                    $calculated,
                    (float) $this->maximum_amount
                );
            }

            return round($calculated, 2);
        }

        return 0.00;
    }
}