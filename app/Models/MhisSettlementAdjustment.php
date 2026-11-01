<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MhisSettlementAdjustment extends Model
{
    protected $fillable = [
        'ip_billing_mhis_claim_id',
        'adjustment_reference',
        'adjustment_date',
        'tds_amount',
        'accepted_deduction_amount',
        'settlement_reference',
        'reason',
        'supporting_evidence',
        'status',
        'created_by',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'tds_amount' => 'decimal:2',
        'accepted_deduction_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(
            IpBillingMhisClaim::class,
            'ip_billing_mhis_claim_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
