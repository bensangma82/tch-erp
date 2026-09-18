<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyReturnItem extends Model
{
    protected $fillable = [
        'pharmacy_return_id',
        'pharmacy_sale_item_id',
        'medicine_id',
        'pharmacy_stock_batch_id',
        'medicine_code',
        'medicine_name',
        'brand_name',
        'strength',
        'unit',
        'hsn_code',
        'batch_number',
        'quantity',
        'unit_price',
        'gst_percent',
        'gross_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'refund_amount',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'gst_percent' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function pharmacyReturn(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyReturn::class
        );
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySaleItem::class,
            'pharmacy_sale_item_id'
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