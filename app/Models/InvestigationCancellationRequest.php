<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestigationCancellationRequest extends Model
{
    protected $fillable = [
        'invoice_id', 'invoice_item_id', 'service_order_item_id',
        'clinical_status_at_request', 'sample_collected_at_request',
        'requires_ms_approval', 'requested_amount', 'approved_amount',
        'status', 'reason', 'decision_reason', 'requested_by',
        'reviewed_by', 'reviewed_at', 'executed_at',
    ];

    protected $casts = [
        'sample_collected_at_request' => 'boolean',
        'requires_ms_approval' => 'boolean',
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function invoiceItem(): BelongsTo { return $this->belongsTo(InvoiceItem::class); }
    public function serviceOrderItem(): BelongsTo { return $this->belongsTo(ServiceOrderItem::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function refunds(): HasMany { return $this->hasMany(InvestigationCancellationRefund::class); }
}
