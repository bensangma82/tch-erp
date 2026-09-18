<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyPurchaseReturn extends Model
{
    protected $fillable = [
        'return_no',
        'pharmacy_supplier_id',
        'return_date',
        'supplier_credit_note_no',
        'supplier_credit_note_date',
        'reason',
        'status',
        'subtotal',
        'discount_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total_amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'supplier_credit_note_date' => 'date',

        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySupplier::class,
            'pharmacy_supplier_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            PharmacyPurchaseReturnItem::class
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