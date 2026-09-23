<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrShift extends Model
{
    protected $table = 'hr_shifts';

    protected $fillable = [
        'code',
        'name',
        'start_time',
        'end_time',
        'grace_minutes',
        'minimum_work_minutes',
        'crosses_midnight',
        'is_active',
        'sort_order',
        'remarks',
    ];

    protected $casts = [
        'crosses_midnight' => 'boolean',
        'is_active' => 'boolean',
        'grace_minutes' => 'integer',
        'minimum_work_minutes' => 'integer',
        'sort_order' => 'integer',
    ];
}