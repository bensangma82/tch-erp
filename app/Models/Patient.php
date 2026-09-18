<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uhid',
        'mrd_number',
        'title',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'age',
        'sex',
        'phone',
        'alternate_phone',
        'email',
        'address',
        'locality',
        'district',
        'state',
        'pin_code',
        'blood_group',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'abha_number',
        'mhis_number',
        'known_allergies',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

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

    public function encounters()
    {
        return $this->hasMany(Encounter::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function serviceOrders()
{
    return $this->hasMany(
        ServiceOrder::class
    );
}
}