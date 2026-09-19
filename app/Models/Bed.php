<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bed extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_id',
        'bed_number',
        'room_id',
        'bed_type',
        'status',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(
            Ward::class
        );
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(
            Admission::class
        );
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            BedAllocation::class
        );
    }

    public function activeAllocation(): HasOne
    {
        return $this->hasOne(
            BedAllocation::class
        )
            ->where(
                'status',
                'active'
            )
            ->latestOfMany(
                'allocated_at'
            );
    }

    /**
 * Room / cabin this bed belongs to, if applicable.
 */
public function room(): BelongsTo
{
    return $this->belongsTo(Room::class);
}
}