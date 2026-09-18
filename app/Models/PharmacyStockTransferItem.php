<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyStockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pharmacy_stock_transfer_id',
        'pharmacy_stock_batch_id',
        'medicine_code',
        'medicine_name',
        'brand_name',
        'strength',
        'unit',
        'batch_number',
        'expiry_date',
        'quantity_requested',
        'quantity_issued',
        'quantity_received',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'quantity_requested' => 'integer',
            'quantity_issued' => 'integer',
            'quantity_received' => 'integer',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockTransfer::class,
            'pharmacy_stock_transfer_id'
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