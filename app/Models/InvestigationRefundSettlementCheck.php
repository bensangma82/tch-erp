<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationRefundSettlementCheck extends Model
{
    protected $fillable = [
        'cancellation_id',
        'payment_id',
        'amount',
        'settlement_method',
        'settlement_reference',
        'evidence_description',
        'status',
        'submitted_by',
        'verified_by',
        'verified_at',
        'verification_notes',
        'refund_id',

        // Stage 1: Refund initiation
        'initiated_by',
        'initiated_at',

        // Stage 2: Cashier disbursement
        'disbursed_by',
        'disbursed_at',
        'disbursement_reference',
        'disbursement_evidence',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'initiated_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    public function cancellation(): BelongsTo
    {
        return $this->belongsTo(
            InvestigationCancellationRequest::class,
            'cancellation_id'
        );
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            Payment::class,
            'payment_id'
        );
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'submitted_by'
        );
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(
            InvestigationCancellationRefund::class,
            'refund_id'
        );
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'initiated_by'
        );
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'disbursed_by'
        );
    }
}
