<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeContract extends Model
{
    protected $fillable = [
        'employee_id',
        'contract_no',
        'contract_type',
        'designation',
        'department_id',
        'start_date',
        'end_date',
        'renewal_due_date',
        'status',
        'previous_contract_id',
        'reference_no',
        'monthly_remuneration',
        'terms_summary',
        'remarks',
        'terminated_on',
        'termination_reason',
        'created_by',
        'updated_by',
    ];


    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'renewal_due_date' => 'date',
        'terminated_on' => 'date',
        'monthly_remuneration' => 'decimal:2',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }


    public function previousContract(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeContract::class,
            'previous_contract_id'
        );
    }


    public function renewedContracts(): HasMany
    {
        return $this->hasMany(
            EmployeeContract::class,
            'previous_contract_id'
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
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }


    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }


    public function isRenewed(): bool
    {
        return $this->status === 'renewed';
    }


    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }


    /*
    |--------------------------------------------------------------------------
    | Date Helpers
    |--------------------------------------------------------------------------
    */

    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return today()->diffInDays(
            $this->end_date,
            false
        );
    }


    public function getIsExpiringSoonAttribute(): bool
    {
        $daysRemaining =
            $this->days_remaining;

        return $this->status === 'active'
            && $daysRemaining !== null
            && $daysRemaining >= 0
            && $daysRemaining <= 30;
    }
}
