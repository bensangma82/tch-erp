<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'head_type',
        'category',
        'parent_id',
        'is_active',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Parent finance head.
     *
     * Example:
     * Diagnostics
     *   -> Laboratory
     *   -> Radiology
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            FinanceHead::class,
            'parent_id'
        );
    }

    /**
     * Child finance heads.
     */
    public function children(): HasMany
    {
        return $this->hasMany(
            FinanceHead::class,
            'parent_id'
        )
            ->orderBy('name');
    }

    /**
     * Vouchers classified under this head.
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(
            FinanceVoucher::class,
            'finance_head_id'
        );
    }

    /**
     * User who created this finance head.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}