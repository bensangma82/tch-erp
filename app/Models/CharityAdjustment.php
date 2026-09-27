<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharityAdjustment extends Model
{
    protected $fillable = [
        'patient_id',
        'invoice_id',
        'encounter_id',
        'admission_id',
        'ip_billing_account_id',

        'adjustment_type',

        'requested_amount',
        'approved_amount',

        'reason',
        'remarks',

        'status',

        'requested_by',
        'approved_by',

        'requested_at',
        'approved_at',
        'applied_at',

        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',

        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',

        'cancelled_at' => 'datetime',
    ];


    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class
        );
    }


    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            Invoice::class
        );
    }


    public function encounter(): BelongsTo
    {
        return $this->belongsTo(
            Encounter::class
        );
    }


    public function admission(): BelongsTo
    {
        return $this->belongsTo(
            Admission::class
        );
    }


    public function ipBillingAccount(): BelongsTo
    {
        return $this->belongsTo(
            IpBillingAccount::class,
            'ip_billing_account_id'
        );
    }


    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }


    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }


    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }


    public function scopePending($query)
    {
        return $query->where(
            'status',
            'pending'
        );
    }


    public function scopeApproved($query)
    {
        return $query->where(
            'status',
            'approved'
        );
    }


    public function scopeApplied($query)
    {
        return $query->where(
            'status',
            'applied'
        );
    }
}