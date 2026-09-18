<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpBillingCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_billing_account_id',
        'admission_id',
        'charge_date',
        'charge_type',
        'service_id',
        'service_order_item_id',
        'code',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'amount',
        'source_type',
        'source_id',
        'status',
        'remarks',
        'created_by',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'charge_date' => 'datetime',
        'cancelled_at' => 'datetime',

        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount' => 'decimal:2',
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


    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class
        );
    }


    public function serviceOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrderItem::class
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
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