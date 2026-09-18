<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'department_id',
        'price',
        'is_active',
        'requires_sample',
        'requires_report',
        'unit',
        'description',
    ];


    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_sample' => 'boolean',
        'requires_report' => 'boolean',
    ];


    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }


    public function orderItems(): HasMany
    {
        return $this->hasMany(
            ServiceOrderItem::class
        );
    }


    public function invoiceItems(): HasMany
    {
        return $this->hasMany(
            InvoiceItem::class
        );
    }


    public function laboratoryTestParameters(): HasMany
    {
        return $this->hasMany(
            LaboratoryTestParameter::class
        )->orderBy('sort_order');
    }
}