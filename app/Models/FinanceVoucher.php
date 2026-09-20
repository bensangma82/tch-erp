<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_no',
        'voucher_type',
        'voucher_date',
        'finance_head_id',
        'finance_account_id',
        'destination_account_id',
        'amount',
        'payment_mode',
        'reference_no',
        'party_name',
        'narration',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'amount' => 'decimal:2',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Income or expense classification.
     */
    public function financeHead(): BelongsTo
    {
        return $this->belongsTo(
            FinanceHead::class,
            'finance_head_id'
        );
    }

    /**
     * Cash/bank account affected by this voucher.
     */
    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(
            FinanceAccount::class,
            'finance_account_id'
        );
    }

    /**
     * Destination account for transfer vouchers.
     */
    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(
            FinanceAccount::class,
            'destination_account_id'
        );
    }

    /**
     * User who entered the voucher.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * User who posted the voucher to Finance.
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'posted_by'
        );
    }

    /**
     * User who cancelled the voucher.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }
}