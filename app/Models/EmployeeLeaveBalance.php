<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'leave_year',
        'opening_balance',
        'entitlement',
        'adjustment',
        'remarks',
        'updated_by',
    ];


    protected $casts = [
        'leave_year' => 'integer',
        'opening_balance' => 'decimal:1',
        'entitlement' => 'decimal:1',
        'adjustment' => 'decimal:1',
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


    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(
            LeaveType::class
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
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getBaseBalanceAttribute(): float
    {
        return
            (float) $this->opening_balance
            +
            (float) $this->entitlement
            +
            (float) $this->adjustment;
    }
}
