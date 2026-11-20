<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BasicPayRate extends Model
{
    protected $fillable = ['staff_group', 'employee_category', 'pay_grade', 'grade_key', 'amount'];

    private const STAFF_GROUPS = [
        'doctors' => 'Doctors',
        'nurses' => 'Nurses',
        'technicians' => 'Technicians',
        'housekeeping' => 'Housekeeping',
        'administration' => 'Administration',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public static function staffGroups(Collection $rates): Collection
    {
        return collect(self::STAFF_GROUPS)->union(
            $rates->pluck('staff_group')->filter()->unique()
                ->mapWithKeys(fn (string $group) => [$group => Str::title($group)])
        );
    }

    public static function key(string $value): string
    {
        return Str::lower(trim($value));
    }
}
