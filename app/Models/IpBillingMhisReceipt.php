<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpBillingMhisReceipt extends Model
{
    use HasFactory;


    protected $fillable = [
        'ip_billing_mhis_claim_id',
        'ip_billing_account_id',
        'admission_id',
        'patient_id',
        'receipt_date',
        'amount',
        'payment_reference',
        'transaction_reference',
        'bank_reference',
        'remarks',
        'status',
        'received_by',
        'cancelled_by',
        'cancelled_at',
    ];


    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];


    public function mhisClaim(): BelongsTo
    {
        return $this->belongsTo(
            IpBillingMhisClaim::class,
            'ip_billing_mhis_claim_id'
        );
    }


    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(
            IpBillingAccount::class,
            'ip_billing_account_id'
        );
    }


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


    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'received_by'
        );
    }


    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }
}