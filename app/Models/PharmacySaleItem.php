<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pharmacy_sale_id',
    'medicine_id',
    'pharmacy_stock_batch_id',
    'medicine_code',
    'medicine_name',
    'brand_name',
    'strength',
    'unit',
    'batch_number',
    'quantity',
    'unit_price',
    'gst_percent',
    'taxable_amount',
    'cgst_amount',
    'sgst_amount',
    'igst_amount',
    'amount',
])]
class PharmacySaleItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'gst_percent' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySale::class,
            'pharmacy_sale_id'
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

    public function returnItems()
{
    return $this->hasMany(
        PharmacyReturnItem::class
    );
}
}