<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyTriage extends Model
{
    use HasFactory;

    protected $table = 'emergency_triage';

    protected $fillable = [
        'emergency_visit_id',
        'temperature',
        'pulse',
        'respiratory_rate',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'spo2',
        'gcs',
        'pain_score',
        'triage_category',
        'oxygen_support',
        'notes',
        'recorded_by',
        'recorded_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'recorded_at' => 'datetime',
    ];

    public function emergencyVisit(): BelongsTo
    {
        return $this->belongsTo(
            EmergencyVisit::class
        );
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recorded_by'
        );
    }
}