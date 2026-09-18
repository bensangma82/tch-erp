<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStockLocationBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_stock_location_id',
        'pharmacy_stock_batch_id',
        'quantity_available',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'quantity_available' => 'integer',
            'reorder_level' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockLocation::class,
            'pharmacy_stock_location_id'
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