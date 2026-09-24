<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'year',
        'month',
        'period_start',
        'period_end',
        'payroll_no',
        'description',
        'status',
        'employee_count',
        'total_earnings',
        'total_deductions',
        'total_net_pay',
        'calculated_at',
        'calculated_by',
        'approved_at',
        'approved_by',
        'payment_date',
        'paid_at',
        'paid_by',
        'locked_at',
        'locked_by',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'employee_count' => 'integer',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net_pay' => 'decimal:2',
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function entries(): HasMany
    {
        return $this->hasMany(
            PayrollEntry::class,
            'payroll_run_id'
        );
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(
            PayrollAdjustment::class,
            'payroll_run_id'
        );
    }

    /**
     * Finance vouchers generated from this payroll run.
     *
     * A payroll run may generate more than one Finance voucher
     * when employees are paid from different Finance Accounts.
     */
    public function financeVouchers(): HasMany
    {
        return $this->hasMany(
            FinanceVoucher::class,
            'payroll_run_id'
        );
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'calculated_by'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'paid_by'
        );
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'locked_by'
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCalculated(): bool
    {
        return $this->status === 'calculated';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /*
    |--------------------------------------------------------------------------
    | Modification Rules
    |--------------------------------------------------------------------------
    */

    public function canBeModified(): bool
    {
        return in_array(
            $this->status,
            ['draft', 'calculated'],
            true
        );
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'calculated';
    }

    public function canBePaid(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeLocked(): bool
    {
        return $this->status === 'paid';
    }
}