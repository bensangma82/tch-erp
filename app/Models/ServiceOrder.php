<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends Model
{
    protected $fillable = [
        'order_no',
        'patient_id',
        'encounter_id',
        'admission_id',
        'ordered_at',
        'status',
        'remarks',
        'created_by',
    ];


    protected $casts = [
        'ordered_at' => 'datetime',
    ];


    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class
        );
    }


    public function encounter(): BelongsTo
    {
        return $this->belongsTo(
            Encounter::class
        );
    }


    public function admission(): BelongsTo
    {
        return $this->belongsTo(
            Admission::class
        );
    }


    public function items(): HasMany
    {
        return $this->hasMany(
            ServiceOrderItem::class
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