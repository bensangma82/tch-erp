<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_code',
        'supplier_name',
        'gstin',
        'pan_no',
        'contact_person',
        'phone',
        'email',
        'address',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hospitalExpenses(): HasMany
    {
        return $this->hasMany(
            HospitalExpense::class,
            'supplier_id'
        );
    }

    public function pharmacySuppliers(): HasMany
    {
        return $this->hasMany(
            PharmacySupplier::class,
            'supplier_id'
        );
    }

    public function tallyLedgerMappings(): HasMany
    {
        return $this->hasMany(
            TallyLedgerMapping::class,
            'supplier_id'
        );
    }

}
