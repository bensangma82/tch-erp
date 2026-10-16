<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalExpensePayment extends Model
{
    protected $fillable = [
        'hospital_expense_id',
        'payment_date',
        'amount',
        'payment_mode',
        'reference_no',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(
            HospitalExpense::class,
            'hospital_expense_id'
        );
    }

    public function financeVoucher(): BelongsTo
    {
        return $this->belongsTo(
            FinanceVoucher::class,
            'finance_voucher_id'
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
