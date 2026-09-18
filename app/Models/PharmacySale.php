<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sale_no',
    'patient_id',
    'encounter_id',
    'sale_at',
    'subtotal',
    'discount',
    'taxable_amount',
    'cgst_amount',
    'sgst_amount',
    'igst_amount',
    'total_amount',
    'paid_amount',
    'balance_amount',
    'payment_mode',
    'transaction_reference',
    'status',
    'remarks',
    'created_by',
])]
class PharmacySale extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sale_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class
        );
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(
            Encounter::class
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            PharmacySaleItem::class
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
    public function returns()
{
    return $this->hasMany(
        PharmacyReturn::class
    );
}
}