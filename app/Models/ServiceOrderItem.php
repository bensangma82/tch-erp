<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceOrderItem extends Model
{
    protected $fillable = [
        'service_order_id',
        'service_id',
        'service_code',
        'service_name',
        'category',
        'quantity',
        'unit_price',
        'amount',
        'status',
        'requires_sample',
        'requires_report',
    ];


    protected $casts = [
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'requires_sample' => 'boolean',
        'requires_report' => 'boolean',
    ];


    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(
            ServiceOrder::class
        );
    }


    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class
        );
    }


    public function invoiceItems(): HasMany
    {
        return $this->hasMany(
            InvoiceItem::class
        );
    }


    public function diagnosticResult(): HasOne
    {
        return $this->hasOne(
            DiagnosticResult::class
        );
    }


    public function diagnosticSample(): HasOne
    {
        return $this->hasOne(
            DiagnosticSample::class
        );
    }
}