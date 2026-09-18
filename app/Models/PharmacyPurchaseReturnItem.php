<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyPurchaseReturnItem extends Model
{
    protected $fillable = [
        'pharmacy_purchase_return_id',
        'pharmacy_grn_item_id',
        'medicine_id',
        'pharmacy_stock_batch_id',

        'medicine_code',
        'medicine_name',
        'brand_name',
        'strength',
        'unit',

        'batch_number',
        'expiry_date',

        'quantity_returned',
        'purchase_price',

        'discount_percent',
        'discount_amount',

        'gst_percent',

        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',

        'line_total',

        'reason',
        'remarks',
    ];

    protected $casts = [
        'expiry_date' => 'date',

        'quantity_returned' => 'integer',

        'purchase_price' => 'decimal:2',

        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',

        'gst_percent' => 'decimal:2',

        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',

        'line_total' => 'decimal:2',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyPurchaseReturn::class,
            'pharmacy_purchase_return_id'
        );
    }

    public function grnItem(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyGrnItem::class,
            'pharmacy_grn_item_id'
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