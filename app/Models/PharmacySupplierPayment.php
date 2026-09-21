<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacySupplierPayment extends Model
{
    protected $fillable = [
        'payment_no',
        'pharmacy_supplier_payable_id',
        'pharmacy_supplier_id',
        'finance_account_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'bank_name',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function payable(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySupplierPayable::class,
            'pharmacy_supplier_payable_id'
        );
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySupplier::class,
            'pharmacy_supplier_id'
        );
    }
    public function financeAccount(): BelongsTo
{
    return $this->belongsTo(
        \App\Models\FinanceAccount::class,
        'finance_account_id'
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