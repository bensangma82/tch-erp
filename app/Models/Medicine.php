<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'generic_name',
    'brand_name',
    'strength',
    'dosage_form',
    'manufacturer',
    'hsn_code',
    'gst_percent',
    'unit',
    'default_selling_price',
    'is_active',
    'description',
])]
class Medicine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'gst_percent' => 'decimal:2',
            'default_selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(
            PharmacyStockBatch::class
        );
    }
}