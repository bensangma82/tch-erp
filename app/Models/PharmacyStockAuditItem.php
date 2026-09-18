<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStockAuditItem extends Model
{
    protected $fillable = [
        'pharmacy_stock_audit_id',
        'pharmacy_stock_batch_id',
        'medicine_id',

        'medicine_code',
        'medicine_name',
        'brand_name',
        'strength',
        'unit',

        'batch_number',
        'expiry_date',

        'system_quantity',
        'counted_quantity',
        'variance_quantity',

        'purchase_price',
        'variance_value',

        'variance_reason',
        'remarks',

        'is_posted',
        'pharmacy_stock_movement_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',

        'system_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'variance_quantity' => 'integer',

        'purchase_price' => 'decimal:2',
        'variance_value' => 'decimal:2',

        'is_posted' => 'boolean',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockAudit::class,
            'pharmacy_stock_audit_id'
        );
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockBatch::class,
            'pharmacy_stock_batch_id'
        );
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(
            Medicine::class
        );
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockMovement::class,
            'pharmacy_stock_movement_id'
        );
    }
}