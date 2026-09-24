<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticResult extends Model
{
    protected $fillable = [
        'service_order_item_id',
        'result_text',
        'findings',
        'impression',
        'structured_data',
        'attachment_path',
        'status',
        'entered_by',
        'entered_at',
        'verified_by',
        'verified_at',
    ];


    protected function casts(): array
    {
        return [
            'structured_data' => 'array',
            'entered_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }


    public function serviceOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrderItem::class
        );
    }


    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'entered_by'
        );
    }


    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }


    public function items(): HasMany
    {
        return $this->hasMany(
            DiagnosticResultItem::class
        )->orderBy('sort_order');
    }
}