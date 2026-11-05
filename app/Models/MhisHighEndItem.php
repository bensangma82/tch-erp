<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MhisHighEndItem extends Model
{
    protected $fillable = [
        'source_excel_row', 'specialty', 'item_name', 'reference_price',
        'source_price_text',
        'opd_ipd', 'hospital_type', 'maximum_multiplier',
        'procedure_code', 'is_active',
    ];

    protected $casts = [
        'reference_price' => 'decimal:2',
        'maximum_multiplier' => 'decimal:2',
        'source_excel_row' => 'integer',
        'is_active' => 'boolean',
    ];
}
