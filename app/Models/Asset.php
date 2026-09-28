<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_code',
        'asset_name',
        'asset_category_id',
        'department_id',
        'custodian_employee_id',
        'asset_vendor_id',

        'manufacturer',
        'model',
        'serial_number',

        'purchase_date',
        'purchase_cost',
        'invoice_number',
        'purchase_order_number',

        'installation_date',
        'commissioning_date',

        'warranty_start_date',
        'warranty_end_date',

        'location',
        'status',
        'criticality',

        'requires_preventive_maintenance',
        'requires_calibration',

        'useful_life_years',

        'remarks',
        'is_active',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',

        'installation_date' => 'date',
        'commissioning_date' => 'date',

        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',

        'requires_preventive_maintenance' => 'boolean',
        'requires_calibration' => 'boolean',

        'useful_life_years' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            AssetCategory::class,
            'asset_category_id'
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'custodian_employee_id'
        );
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(
            AssetVendor::class,
            'asset_vendor_id'
        );
    }

    public function movements(): HasMany
   {
    return $this->hasMany(AssetMovement::class)
        ->orderByDesc('movement_date')
        ->orderByDesc('id');
    }
}