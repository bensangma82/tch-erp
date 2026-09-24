<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollEntry extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'employee_salary_structure_id',
        'employee_code',
        'employee_name',
        'designation',
        'department_name',
        'employee_type',
        'calendar_days',
        'payable_days',
        'lop_days',
        'gross_earnings',
        'total_deductions',
        'net_pay',
        'lop_deduction',
        'additional_earnings',
        'additional_deductions',
        'status',
        'payment_mode',
        'finance_account_id',
        'payment_reference',
        'payment_date',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'calendar_days' => 'decimal:2',
        'payable_days' => 'decimal:2',
        'lop_days' => 'decimal:2',
        'gross_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'lop_deduction' => 'decimal:2',
        'additional_earnings' => 'decimal:2',
        'additional_deductions' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(
            PayrollRun::class,
            'payroll_run_id'
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeSalaryStructure::class,
            'employee_salary_structure_id'
        );
    }
               public function financeAccount(): BelongsTo
{
    return $this->belongsTo(
        FinanceAccount::class,
        'finance_account_id'
    );
}


    public function items(): HasMany
    {
        return $this->hasMany(
            PayrollEntryItem::class,
            'payroll_entry_id'
        )->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function earnings(): HasMany
    {
        return $this->hasMany(
            PayrollEntryItem::class,
            'payroll_entry_id'
        )->where('type', 'earning')
         ->orderBy('sort_order');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(
            PayrollEntryItem::class,
            'payroll_entry_id'
        )->where('type', 'deduction')
         ->orderBy('sort_order');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCalculated(): bool
    {
        return $this->status === 'calculated';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function recalculateTotals(): void
    {
        $earnings = (float) $this->items()
            ->where('type', 'earning')
            ->sum('amount');

        $deductions = (float) $this->items()
            ->where('type', 'deduction')
            ->sum('amount');

        $this->gross_earnings = round($earnings, 2);
        $this->total_deductions = round($deductions, 2);
        $this->net_pay = round($earnings - $deductions, 2);

        $this->save();
    }
}