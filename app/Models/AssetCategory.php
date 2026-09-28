<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'asset_class',
        'requires_preventive_maintenance',
        'requires_calibration',
        'default_useful_life_years',
        'is_active',
    ];

    protected $casts = [
        'requires_preventive_maintenance' => 'boolean',
        'requires_calibration' => 'boolean',
        'default_useful_life_years' => 'integer',
        'is_active' => 'boolean',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}