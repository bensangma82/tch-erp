<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BedAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'bed_id',
        'allocated_at',
        'released_at',
        'status',
        'allocation_type',
        'remarks',
        'allocated_by',
        'released_by',
    ];

    protected $casts = [
        'allocated_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(
            Admission::class
        );
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(
            Bed::class
        );
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'allocated_by'
        );
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'released_by'
        );
    }
}