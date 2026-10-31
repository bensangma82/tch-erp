<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalExpense extends Model
{
    protected $fillable = [
        'expense_no',
        'finance_head_id',
        'general_ledger_account_id',
        'expense_classification',
        'expense_type',
        'expense_date',
        'accounting_date',
        'supplier_name',
        'invoice_no',
        'invoice_date',
        'description',
        'subtotal',
        'tax_amount',
        'total_amount',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'accounting_date' => 'date',
            'invoice_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function financeHead(): BelongsTo
    {
        return $this->belongsTo(
            FinanceHead::class,
            'finance_head_id'
        );
    }

    public function generalLedgerAccount(): BelongsTo
    {
        return $this->belongsTo(
            GeneralLedgerAccount::class,
            'general_ledger_account_id'
        );
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(
            JournalEntry::class,
            'journal_entry_id'
        );
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            HospitalExpenseAllocation::class,
            'hospital_expense_id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            HospitalExpensePayment::class,
            'hospital_expense_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

}
