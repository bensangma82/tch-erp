<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BedTariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'ward_id',
        'bed_type',
        'rate_per_day',
        'effective_from',
        'effective_to',
        'is_active',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'rate_per_day' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];


    public function ward(): BelongsTo
    {
        return $this->belongsTo(
            Ward::class
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}