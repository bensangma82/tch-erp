<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'bank_name',
        'account_number',
        'branch_name',
        'ifsc_code',
        'opening_balance',
        'opening_balance_date',
        'is_active',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * User who created this account.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * Vouchers affecting this account.
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(
            FinanceVoucher::class,
            'finance_account_id'
        );
    }

    /**
     * Transfers received by this account.
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(
            FinanceVoucher::class,
            'destination_account_id'
        );
    }
}