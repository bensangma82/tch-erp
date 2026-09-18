<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacySupplierCredit extends Model
{
    protected $fillable = [
        'credit_no',
        'pharmacy_supplier_id',
        'source_payable_id',
        'source_grn_id',
        'credit_date',
        'original_credit_amount',
        'utilised_amount',
        'available_amount',
        'status',
        'source_type',
        'remarks',
        'created_by',
    ];


    protected $casts = [
        'credit_date' => 'date',

        'original_credit_amount' =>
            'decimal:2',

        'utilised_amount' =>
            'decimal:2',

        'available_amount' =>
            'decimal:2',
    ];


    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySupplier::class,
            'pharmacy_supplier_id'
        );
    }


    public function sourcePayable(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySupplierPayable::class,
            'source_payable_id'
        );
    }


    public function sourceGrn(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyGrn::class,
            'source_grn_id'
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}