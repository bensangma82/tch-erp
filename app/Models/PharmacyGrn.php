<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyGrn extends Model
{
    protected $fillable = [
        'grn_no',
        'pharmacy_purchase_order_id',
        'pharmacy_supplier_id',
        'grn_date',
        'supplier_invoice_no',
        'supplier_invoice_date',
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
        'grn_date' => 'date',
        'supplier_invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyPurchaseOrder::class,
            'pharmacy_purchase_order_id'
        );
    }

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
            PharmacyGrnItem::class
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