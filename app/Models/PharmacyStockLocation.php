<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyStockLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location_type',
        'is_active',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(
            PharmacyStockLocationBalance::class,
            'pharmacy_stock_location_id'
        );
    }

    public function outgoingTransfers(): HasMany
{
    return $this->hasMany(
        PharmacyStockTransfer::class,
        'from_location_id'
    );
}

public function incomingTransfers(): HasMany
{
    return $this->hasMany(
        PharmacyStockTransfer::class,
        'to_location_id'
    );
}
}