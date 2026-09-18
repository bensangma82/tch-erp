<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyStockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_no',
        'transfer_date',
        'from_location_id',
        'to_location_id',
        'status',
        'remarks',
        'created_by',
        'issued_by',
        'issued_at',
        'received_by',
        'received_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'issued_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockLocation::class,
            'from_location_id'
        );
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockLocation::class,
            'to_location_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            PharmacyStockTransferItem::class,
            'pharmacy_stock_transfer_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'issued_by'
        );
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'received_by'
        );
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }
}