<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationCancellationRefund extends Model
{
    protected $fillable = [
        'investigation_cancellation_request_id', 'payment_id', 'amount',
        'refund_mode', 'reference_no', 'status', 'processed_by', 'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function cancellationRequest(): BelongsTo
    {
        return $this->belongsTo(InvestigationCancellationRequest::class, 'investigation_cancellation_request_id');
    }

    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
