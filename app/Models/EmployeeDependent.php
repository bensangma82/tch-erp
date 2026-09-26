<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDependent extends Model
{
    protected $fillable = [
        'employee_id',
        'patient_id',
        'relationship',
        'eligible_from',
        'eligible_until',
        'is_active',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'eligible_from' => 'date',
        'eligible_until' => 'date',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
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
    | Eligibility
    |--------------------------------------------------------------------------
    */

    public function isEligibleOn($date): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $date = \Carbon\Carbon::parse($date);

        if (
            $this->eligible_from &&
            $date->lt($this->eligible_from)
        ) {
            return false;
        }

        if (
            $this->eligible_until &&
            $date->gt($this->eligible_until)
        ) {
            return false;
        }

        return true;
    }
}