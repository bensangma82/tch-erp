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
    'admission_id',
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


    /**
     * Current unpaid receivable after completed pharmacy returns.
     * Preserve original sale balance for accounting reconciliation.
     */
    public function getCurrentOutstandingAttribute(): float
    {
        $returns = $this->relationLoaded('returns')
            ? $this->returns
            : $this->returns()->get();

        $creditReversed = 0;

        foreach ($returns as $return) {
            if ($return->status !== 'completed') {
                continue;
            }

            $amount = (int) round(
                (float) $return->refund_amount * 100
            );

            $mode = strtolower((string) $return->refund_mode);

            // A cash/bank refund is not a receivable reversal.
            if (in_array($mode, ['cash', 'upi', 'card'], true)) {
                continue;
            }

            if (! in_array($mode, ['none', 'credit', 'mhis'], true)) {
                continue;
            }

            $creditReversed += $amount;
        }

        $originalBalance = (int) round(
            (float) $this->balance_amount * 100
        );

        return max(0, $originalBalance - $creditReversed) / 100;
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

    public function admission(): BelongsTo
    {
        return $this->belongsTo(
            Admission::class
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

    public function returns(): HasMany
    {
        return $this->hasMany(
            PharmacyReturn::class
        );
    }
}