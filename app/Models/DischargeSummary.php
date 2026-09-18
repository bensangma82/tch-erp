<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DischargeSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'final_diagnosis',
        'hospital_course',
        'procedures_performed',
        'important_investigations',
        'treatment_given',
        'condition_at_discharge',
        'discharge_medications',
        'review_date',
        'follow_up_advice',
        'diet_advice',
        'warning_signs',
        'prepared_by',
        'updated_by',
    ];

    protected $casts = [
        'review_date' => 'date',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(
            Admission::class
        );
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'prepared_by'
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}