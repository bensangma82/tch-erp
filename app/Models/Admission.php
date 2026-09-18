<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_no',
        'patient_id',
        'source_type',
        'source_id',
        'admitted_at',
        'department_id',
        'consultant_id',
        'admission_id',
        'admission_type',
        'admission_reason',
        'provisional_diagnosis',
        'bed_id',
        'status',
        'discharged_at',

        // IPD closure fields
        'closed_at',
        'closure_notes',
        'referral_destination',
        'closed_by',

        'created_by',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
        'closed_at' => 'datetime',
    ];


    /**
     * Patient.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class
        );
    }


    /**
     * Admitting department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }


    /**
     * Admitting consultant.
     *
     * Consultants are maintained in Employee master.
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'consultant_id'
        );
    }


    /**
     * Bed stored on the admission.
     *
     * This is updated whenever the patient is transferred.
     */
    public function bed(): BelongsTo
    {
        return $this->belongsTo(
            Bed::class
        );
    }


    /**
     * User who created the admission.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /**
     * User who closed the admission.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'closed_by'
        );
    }


    /**
     * Complete bed-allocation history.
     */
    public function bedAllocations(): HasMany
    {
        return $this->hasMany(
            BedAllocation::class
        )
            ->orderByDesc(
                'allocated_at'
            );
    }


    /**
     * Current active bed allocation.
     */
    public function currentBedAllocation(): HasOne
    {
        return $this->hasOne(
            BedAllocation::class
        )
            ->where(
                'status',
                'active'
            )
            ->latestOfMany(
                'allocated_at'
            );
    }


    /**
     * Emergency visit from which this admission originated.
     */
    public function emergencyVisit(): BelongsTo
    {
        return $this->belongsTo(
            EmergencyVisit::class,
            'source_id'
        );
    }


    /**
     * Discharge summary.
     *
     * One discharge summary per admission.
     */
    public function dischargeSummary(): HasOne
    {
        return $this->hasOne(
            DischargeSummary::class
        );
    }


    /**
     * Running inpatient billing account.
     *
     * One billing account per admission.
     */
    public function billingAccount(): HasOne
    {
        return $this->hasOne(
            IpBillingAccount::class
        );
    }

    public function mhisClaims(): HasMany
{
    return $this->hasMany(
        IpBillingMhisClaim::class
    );
}


public function admission()
{
    return $this->belongsTo(
        Admission::class
    );
}
}