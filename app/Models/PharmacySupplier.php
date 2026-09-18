<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PharmacySupplier extends Model
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
        'drug_license_no',
        'pan_no',
        'credit_days',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'credit_days' => 'integer',
        'is_active' => 'boolean',
    ];
}