<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetVendor extends Model
{
    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'phone',
        'alternate_phone',
        'email',
        'address',
        'city',
        'district',
        'state',
        'pin_code',
        'gstin',
        'pan_no',
        'provides_sales',
        'provides_service',
        'provides_amc_cmc',
        'provides_calibration',
        'remarks',
        'is_active',
    ];

    protected $casts = [
        'provides_sales' => 'boolean',
        'provides_service' => 'boolean',
        'provides_amc_cmc' => 'boolean',
        'provides_calibration' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}