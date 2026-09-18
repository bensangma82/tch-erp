<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacySupplierPayable extends Model
{
    protected $fillable = [
        'payable_no',
        'pharmacy_supplier_id',
        'pharmacy_grn_id',
        'supplier_invoice_no',
        'supplier_invoice_date',
        'payable_date',
        'due_date',
        'original_amount',
        'return_adjustment',
        'other_adjustment',
        'paid_amount',
        'outstanding_amount',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'supplier_invoice_date' => 'date',
        'payable_date' => 'date',
        'due_date' => 'date',

        'original_amount' => 'decimal:2',
        'return_adjustment' => 'decimal:2',
        'other_adjustment' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySupplier::class,
            'pharmacy_supplier_id'
        );
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyGrn::class,
            'pharmacy_grn_id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            PharmacySupplierPayment::class,
            'pharmacy_supplier_payable_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

   public function supplierCredits(): HasMany
{
    return $this->hasMany(
        PharmacySupplierCredit::class,
        'source_payable_id'
    );
} 
}