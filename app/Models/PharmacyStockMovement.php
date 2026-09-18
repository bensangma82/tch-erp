<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pharmacy_stock_batch_id',
    'movement_type',
    'quantity',
    'balance_after',
    'reference_type',
    'reference_id',
    'remarks',
    'created_by',
    'movement_at',
])]
class PharmacyStockMovement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'balance_after' => 'integer',
            'movement_at' => 'datetime',
        ];
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockBatch::class,
            'pharmacy_stock_batch_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}