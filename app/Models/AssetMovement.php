<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMovement extends Model
{
    protected $fillable = [
        'asset_id',
        'movement_date',

        'from_department_id',
        'to_department_id',

        'from_location',
        'to_location',

        'from_custodian_employee_id',
        'to_custodian_employee_id',

        'movement_type',
        'reason',
        'remarks',

        'moved_by_user_id',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'from_department_id'
        );
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'to_department_id'
        );
    }

    public function fromCustodian(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'from_custodian_employee_id'
        );
    }

    public function toCustodian(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'to_custodian_employee_id'
        );
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'moved_by_user_id'
        );
    }
}