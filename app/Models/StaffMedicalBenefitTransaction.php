<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMedicalBenefitTransaction extends Model
{
    protected $fillable = [
        'benefit_account_id',
        'beneficiary_type',
        'dependent_id',
        'patient_id',
        'transaction_type',
        'transaction_date',
        'amount',
        'source_type',
        'source_id',
        'source_reference',
        'gross_bill_amount',
        'mhis_approved_amount',
        'residual_before_benefit',
        'reverses_transaction_id',
        'status',
        'reason',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'gross_bill_amount' => 'decimal:2',
        'mhis_approved_amount' => 'decimal:2',
        'residual_before_benefit' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function benefitAccount(): BelongsTo
    {
        return $this->belongsTo(
            StaffMedicalBenefitAccount::class,
            'benefit_account_id'
        );
    }

    public function dependent(): BelongsTo
    {
        return $this->belongsTo(
            EmployeeDependent::class,
            'dependent_id'
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'reverses_transaction_id'
        );
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(
            self::class,
            'reverses_transaction_id'
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

    /*
    |--------------------------------------------------------------------------
    | Transaction Helpers
    |--------------------------------------------------------------------------
    */

    public function isUtilization(): bool
    {
        return $this->transaction_type === 'utilization';
    }

    public function isReversal(): bool
    {
        return $this->transaction_type === 'reversal';
    }

    public function isEmployeeBenefit(): bool
    {
        return $this->beneficiary_type === 'employee';
    }

    public function isDependentBenefit(): bool
    {
        return $this->beneficiary_type === 'dependent';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /*
    |--------------------------------------------------------------------------
    | Ledger Effect
    |--------------------------------------------------------------------------
    |
    | Positive value = consumes benefit entitlement.
    | Negative value = restores benefit entitlement.
    |
    */

    public function getLedgerEffectAttribute(): float
    {
        if (! $this->isActive()) {
            return 0;
        }

        if ($this->isUtilization()) {
            return (float) $this->amount;
        }

        if ($this->isReversal()) {
            return -1 * (float) $this->amount;
        }

        return 0;
    }
}