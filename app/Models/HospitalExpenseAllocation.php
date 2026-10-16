<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalExpenseAllocation extends Model
{
    protected $fillable = [
        'hospital_expense_id',
        'department_id',
        'percentage',
        'allocated_amount',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:4',
            'allocated_amount' => 'decimal:2',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(
            HospitalExpense::class,
            'hospital_expense_id'
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }
}
