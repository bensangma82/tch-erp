<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_code',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'designation',
        'department_id',
        'employee_type',
        'professional_registration_no',
        'qualification',
        'speciality',
        'phone',
        'email',
        'date_of_joining',
        'is_doctor',
        'is_active',
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'is_doctor' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Department this employee belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Full display name.
     */
    public function getFullNameAttribute(): string
    {
        return trim(
            collect([
                $this->title,
                $this->first_name,
                $this->middle_name,
                $this->last_name,
            ])
                ->filter()
                ->implode(' ')
        );
    }

    /**
     * OPD / clinical encounters assigned to this doctor.
     */
    public function encounters()
    {
        return $this->hasMany(
            Encounter::class,
            'doctor_id'
        );
    }
}