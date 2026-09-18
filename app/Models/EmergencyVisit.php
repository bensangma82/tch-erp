<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmergencyVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_no',
        'patient_id',
        'arrival_at',
        'arrival_mode',
        'brought_by',
        'chief_complaint',
        'status',
        'doctor_id',
        'disposition',
        'disposition_at',
        'admission_id',
        'created_by',
    ];

    protected $casts = [
        'arrival_at' => 'datetime',
        'disposition_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'doctor_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function triageRecords(): HasMany
    {
        return $this->hasMany(
            EmergencyTriage::class
        )->orderByDesc('recorded_at');
    }

    public function latestTriage(): HasOne
    {
        return $this->hasOne(
            EmergencyTriage::class
        )->latestOfMany('recorded_at');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(
            Admission::class,
            'admission_id'
        );
    }
}