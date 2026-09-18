<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IpBillingMhisClaim extends Model
{
    use HasFactory;


    protected $fillable = [
        'ip_billing_account_id',
        'admission_id',
        'patient_id',
        'claim_no',
        'authorization_no',
        'package_code',
        'package_name',
        'claim_amount',
        'approved_amount',
        'settlement_amount',
        'status',
        'approval_date',
        'settlement_date',
        'remarks',
        'created_by',
        'updated_by',
    ];


    protected $casts = [
        'claim_amount' =>
            'decimal:2',

        'approved_amount' =>
            'decimal:2',

        'settlement_amount' =>
            'decimal:2',

        'approval_date' =>
            'date',

        'settlement_date' =>
            'date',
    ];


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


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function receipts(): HasMany
{
    return $this->hasMany(
        IpBillingMhisReceipt::class,
        'ip_billing_mhis_claim_id'
    )
        ->orderBy('receipt_date')
        ->orderBy('id');
}
}