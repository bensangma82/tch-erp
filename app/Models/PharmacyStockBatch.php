<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'medicine_id',
    'batch_number',
    'expiry_date',
    'purchase_price',
    'selling_price',
    'quantity_received',
    'quantity_available',
    'reorder_level',
    'received_date',
    'is_active',
])]
class PharmacyStockBatch extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'received_date' => 'date',
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'quantity_received' => 'integer',
            'quantity_available' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(
            Medicine::class
        );
    }

    public function movements(): HasMany
{
    return $this->hasMany(
        PharmacyStockMovement::class
    );
}

public function locationBalances(): HasMany
{
    return $this->hasMany(
        PharmacyStockLocationBalance::class,
        'pharmacy_stock_batch_id'
    );
}
public function transferItems(): HasMany
{
    return $this->hasMany(
        PharmacyStockTransferItem::class,
        'pharmacy_stock_batch_id'
    );
}
}