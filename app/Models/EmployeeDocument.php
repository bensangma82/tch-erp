<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'employee_id',
        'document_type',
        'title',
        'reference_no',
        'issue_date',
        'expiry_date',
        'disk',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_remarks',
        'remarks',
        'created_by',
        'updated_by',
    ];


    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Verification Helpers
    |--------------------------------------------------------------------------
    */

    public function isPendingVerification(): bool
    {
        return $this->verification_status === 'pending';
    }


    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }


    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }


    /*
    |--------------------------------------------------------------------------
    | Expiry Helpers
    |--------------------------------------------------------------------------
    */

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return today()->diffInDays(
            $this->expiry_date,
            false
        );
    }


    public function getIsExpiredAttribute(): bool
    {
        $days =
            $this->days_until_expiry;

        return $days !== null
            && $days < 0;
    }


    public function getIsExpiringSoonAttribute(): bool
    {
        $days =
            $this->days_until_expiry;

        return $days !== null
            && $days >= 0
            && $days <= 30;
    }


    /*
    |--------------------------------------------------------------------------
    | Display Helpers
    |--------------------------------------------------------------------------
    */

    public function getDocumentTypeLabelAttribute(): string
    {
        return ucwords(
            str_replace(
                '_',
                ' ',
                $this->document_type
            )
        );
    }


    public function getFormattedFileSizeAttribute(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $bytes =
            (float) $this->file_size;

        if ($bytes >= 1024 * 1024) {
            return number_format(
                $bytes / (1024 * 1024),
                2
            ) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format(
                $bytes / 1024,
                1
            ) . ' KB';
        }

        return number_format(
            $bytes,
            0
        ) . ' bytes';
    }
}
