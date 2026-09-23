<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'default_annual_entitlement',
        'is_paid',
        'allow_carry_forward',
        'max_carry_forward',
        'requires_approval',
        'is_active',
        'sort_order',
        'description',
    ];


    protected $casts = [
        'default_annual_entitlement' => 'decimal:1',
        'is_paid' => 'boolean',
        'allow_carry_forward' => 'boolean',
        'max_carry_forward' => 'decimal:1',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function balances(): HasMany
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
}
