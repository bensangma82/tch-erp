<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpBillingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'patient_id',
        'account_no',
        'final_bill_no',
        'opened_at',
        'status',
        'subtotal',
        'discount_amount',
        'net_amount',
        'advance_amount',
        'paid_amount',
        'balance_amount',
        'finalized_at',
        'finalized_by',
        'created_by',
        'remarks',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'finalized_at' => 'datetime',

        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];


    public function admission(): BelongsTo
    {
        return $this->belongsTo(
            Admission::class
        );
    }


    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class
        );
    }


    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'finalized_by'
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function charges(): HasMany
    {
        return $this->hasMany(
            IpBillingCharge::class
        )
            ->orderBy('charge_date')
            ->orderBy('id');
    }


    public function advances(): HasMany
    {
        return $this->hasMany(
            IpBillingAdvance::class
        )
            ->orderBy('payment_date')
            ->orderBy('id');
    }

public function payments(): HasMany
{
    return $this->hasMany(
        IpBillingPayment::class,
        'ip_billing_account_id'
    )
        ->orderBy('payment_date')
        ->orderBy('id');
}
    public function mhisClaims(): HasMany
{
    return $this->hasMany(
        IpBillingMhisClaim::class,
        'ip_billing_account_id'
    )->orderByDesc('id');
}

public function mhisReceipts(): HasMany
{
    return $this->hasMany(
        IpBillingMhisReceipt::class,
        'ip_billing_account_id'
    )
        ->orderBy('receipt_date')
        ->orderBy('id');
}
}