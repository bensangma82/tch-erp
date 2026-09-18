<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encounter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'encounter_no',
        'patient_id',
        'encounter_type',
        'department_id',
        'doctor_id',
        'encounter_date',
        'encounter_time',
        'visit_type',
        'queue_number',
        'referred_by',
        'reason_for_visit',
        'status',
        'created_by',
    ];

    protected $casts = [
        'encounter_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function doctor()
    {
        return $this->belongsTo(
            Employee::class,
            'doctor_id'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
    public function invoices()
{
    return $this->hasMany(Invoice::class);
}

public function payments()
{
    return $this->hasMany(Payment::class);
}
public function vitals()
{
    return $this->hasMany(EncounterVital::class);
}
public function serviceOrders()
{
    return $this->hasMany(
        ServiceOrder::class
    );
}


}

