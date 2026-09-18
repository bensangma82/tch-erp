<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyDisposal extends Model
{
    protected $fillable = [
        'disposal_no',
        'disposed_at',
        'reason',
        'remarks',
        'status',
        'created_by',
    ];

    protected $casts = [
        'disposed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(
            PharmacyDisposalItem::class
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