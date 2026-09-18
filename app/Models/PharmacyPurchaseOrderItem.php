<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyPurchaseOrderItem extends Model
{
    protected $fillable = [
        'pharmacy_purchase_order_id',
        'medicine_id',
        'medicine_code',
        'medicine_name',
        'brand_name',
        'strength',
        'unit',
        'quantity_ordered',
        'unit_cost',
        'discount_percent',
        'discount_amount',
        'gst_percent',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'line_total',
        'quantity_received',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_cost' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyPurchaseOrder::class,
            'pharmacy_purchase_order_id'
        );
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(
            Medicine::class
        );
    }
}