<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyClinicalNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_visit_id',
        'presenting_complaints',
        'history',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse',
        'respiratory_rate',
        'temperature',
        'spo2',
        'gcs',
        'pain_score',
        'oxygen_support',
        'general_examination',
        'systemic_examination',
        'provisional_diagnosis',
        'investigations',
        'treatment_given',
        'disposition',
        'discharge_advice',
        'doctor_id',
        'created_by',
        'documented_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'documented_at' => 'datetime',
    ];

    public function emergencyVisit(): BelongsTo
    {
        return $this->belongsTo(EmergencyVisit::class);
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
}