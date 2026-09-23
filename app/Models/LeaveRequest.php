<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'request_no',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'applied_at',
        'approved_by',
        'approved_at',
        'remarks',
        'created_by',
        'updated_by',
    ];


    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_days' => 'decimal:1',
        'applied_at' => 'datetime',
        'approved_at' => 'datetime',
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


    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }


    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }


    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }


    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }


    /*
    |--------------------------------------------------------------------------
    | Duration Helper
    |--------------------------------------------------------------------------
    */

    public function getDateRangeLabelAttribute(): string
    {
        if (! $this->start_date || ! $this->end_date) {
            return '—';
        }

        if (
            $this->start_date->isSameDay(
                $this->end_date
            )
        ) {
            return $this->start_date->format(
                'd M Y'
            );
        }

        return
            $this->start_date->format(
                'd M Y'
            )
            . ' - '
            . $this->end_date->format(
                'd M Y'
            );
    }
}
