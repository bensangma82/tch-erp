<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MhisProcedure extends Model
{
    protected $table = 'mhis_procedures';

    protected $fillable = [
        'source_row_number',
        'source_serial_number',
        'procedure_code',
        'specialty',
        'medical_surgical',
        'package_name',
        'procedure_name',
        'procedure_price',
        'source_price_text',
        'average_length_of_stay',
        'stratification_options',
        'stratification_rate',
        'implants',
        'preauth_documents',
        'claim_documents',
        'is_active',
    ];

    protected $casts = [
        'procedure_price' => 'decimal:2',
        'is_active' => 'boolean',
        'source_row_number' => 'integer',
        'source_serial_number' => 'integer',
    ];
}
