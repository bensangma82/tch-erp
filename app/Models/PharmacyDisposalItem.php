<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyDisposalItem extends Model
{
    protected $fillable = [
        'pharmacy_disposal_id',
        'medicine_id',
        'pharmacy_stock_batch_id',
        'medicine_code',
        'medicine_name',
        'brand_name',
        'strength',
        'unit',
        'batch_number',
        'expiry_date',
        'quantity',
        'purchase_price',
        'stock_value',
        'reason',
        'remarks',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'stock_value' => 'decimal:2',
    ];

    public function disposal(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyDisposal::class,
            'pharmacy_disposal_id'
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