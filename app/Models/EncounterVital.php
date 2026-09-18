<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncounterVital extends Model
{
    protected $fillable = [
        'encounter_id',
        'systolic_bp',
        'diastolic_bp',
        'pulse_rate',
        'respiratory_rate',
        'temperature',
        'spo2',
        'weight_kg',
        'height_cm',
        'blood_glucose',
        'notes',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'weight_kg' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'blood_glucose' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function encounter()
    {
        return $this->belongsTo(Encounter::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(
            User::class,
            'recorded_by'
        );
    }
}