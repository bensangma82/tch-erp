<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryTestParameter extends Model
{
    protected $fillable = [
        'service_id',
        'parameter_name',
        'unit',
        'method',
        'result_type',
        'reference_range',
        'low_value',
        'high_value',
        'critical_low',
        'critical_high',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'low_value' => 'decimal:4',
        'high_value' => 'decimal:4',
        'critical_low' => 'decimal:4',
        'critical_high' => 'decimal:4',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
