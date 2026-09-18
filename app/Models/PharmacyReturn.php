<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyReturn extends Model
{
    protected $fillable = [
        'return_no',
        'pharmacy_sale_id',
        'patient_id',
        'encounter_id',
        'returned_at',
        'gross_amount',
        'taxable_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'refund_amount',
        'refund_mode',
        'transaction_reference',
        'reason',
        'remarks',
        'status',
        'created_by',
    ];

    protected $casts = [
        'returned_at' => 'datetime',

        'gross_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(
            PharmacySale::class,
            'pharmacy_sale_id'
        );
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
            PharmacyReturnItem::class
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