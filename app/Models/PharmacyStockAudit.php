<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyStockAudit extends Model
{
    protected $fillable = [
        'pharmacy_stock_location_id',
        'audit_no',
        'audit_date',
        'status',
        'audit_type',
        'remarks',
        'created_by',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];


    /**
     * Physical stock location being audited.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(
            PharmacyStockLocation::class,
            'pharmacy_stock_location_id'
        );
    }


    /**
     * Audit line items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            PharmacyStockAuditItem::class,
            'pharmacy_stock_audit_id'
        );
    }


    /**
     * Audit creator.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /**
     * Audit approver.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }


    /**
     * Final posting user.
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'posted_by'
        );
    }
}