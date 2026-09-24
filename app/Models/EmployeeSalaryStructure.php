<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeSalaryStructure extends Model
{
    protected $fillable = [
        'employee_id',
        'effective_from',
        'effective_to',
        'monthly_salary',
        'status',
        'remarks',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'monthly_salary' => 'decimal:2',
        'approved_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(
            EmployeeSalaryStructureItem::class,
            'employee_salary_structure_id'
        )->orderBy('sort_order');
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(
            PayrollEntry::class,
            'employee_salary_structure_id'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isEffectiveOn($date): bool
    {
        $date = \Illuminate\Support\Carbon::parse($date);

        if ($date->lt($this->effective_from)) {
            return false;
        }

        if (
            $this->effective_to !== null
            && $date->gt($this->effective_to)
        ) {
            return false;
        }

        return true;
    }
}