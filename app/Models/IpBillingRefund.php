<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpBillingRefund extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_billing_account_id',
        'admission_id',
        'patient_id',
        'refund_no',
        'refund_date',
        'amount',
        'payment_mode',
        'transaction_reference',
        'reason',
        'remarks',
        'status',
        'refunded_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'refund_date' => 'datetime',
        'amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
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
        return $this->belongsTo(Admission::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'refunded_by'
        );
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}