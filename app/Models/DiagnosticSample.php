<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticSample extends Model
{
    protected $fillable = [
        'service_order_item_id',
        'sample_no',
        'specimen_type',
        'collected_at',
        'collected_by',
        'status',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'remarks',
    ];


    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }


    public function serviceOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrderItem::class
        );
    }


    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'collected_by'
        );
    }


    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'rejected_by'
        );
    }
}