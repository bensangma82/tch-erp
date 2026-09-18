<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'ward_type',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    /**
     * Beds belonging to this ward.
     */
    public function beds(): HasMany
    {
        return $this->hasMany(
            Bed::class
        );
    }


    /**
     * Bed tariff history for this ward.
     */
    public function bedTariffs(): HasMany
    {
        return $this->hasMany(
            BedTariff::class
        )
            ->orderByDesc('effective_from');
    }
}