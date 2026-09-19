<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyGrnItem extends Model
{
    protected $fillable = [
        'pharmacy_grn_id',
        'pharmacy_purchase_order_item_id',
        'medicine_id',
        'pharmacy_stock_batch_id',
        'medicine_code',
        'medicine_name',
        'brand_name',
        'strength',
        'unit',
        'batch_number',
        'expiry_date',

        // Pack / stock quantity fields
        'purchase_qty',
        'bonus_qty',
        'units_per_pack',
        'received_units',

        // Legacy quantity field retained for compatibility
        'quantity_received',

        'purchase_price',
        'selling_price',
        'discount_percent',
        'discount_amount',
        'gst_percent',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'line_total',
    ];

    protected $casts = [
        'expiry_date' => 'date',

        'purchase_qty' => 'integer',
        'bonus_qty' => 'integer',
        'units_per_pack' => 'integer',
        'received_units' => 'integer',
        'quantity_received' => 'integer',

        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyGrn::class,
            'pharmacy_grn_id'
        );
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyPurchaseOrderItem::class,
            'pharmacy_purchase_order_item_id'
        );
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(
            Medicine::class
        );
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockBatch::class,
            'pharmacy_stock_batch_id'
        );
    }
}