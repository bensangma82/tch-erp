<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class AdministrativeRequest extends Model
{
    protected $fillable = [
        'request_no',
        'required_by',
        'higher_approval_reference',
        'request_type',
        'title',
        'description',
        'department_id',

        'estimated_amount',
        'approved_amount',

        'status',

        'created_by',
        'submitted_at',

        'verified_by',
        'verified_at',
        'verification_remarks',

        'ms_decided_by',
        'ms_decided_at',
        'ms_decision',
        'ms_remarks',

        'requires_higher_approval',
        'higher_authority',
        'higher_approval_recorded_by',
        'higher_approved_at',
        'higher_approval_remarks',

        'assigned_to',
        'execution_category',
        'assigned_role',
        'completion_verified_by',
        'completion_verified_at',
        'completion_verification_remarks',
        'execution_started_at',
        'executed_at',
        'executed_by',
        'execution_remarks',

        'closed_at',
        'closed_by',
        'closure_remarks',

        'priority',
        'remarks',
    ];

    protected $casts = [
        'required_by' => 'date',
        'estimated_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',

        'requires_higher_approval' => 'boolean',

        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'ms_decided_at' => 'datetime',
        'higher_approved_at' => 'datetime',
        'execution_started_at' => 'datetime',
        'executed_at' => 'datetime',
        'completion_verified_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const EXECUTION_STATUSES = ['ms_approved', 'higher_approved', 'execution_in_progress'];
    public const TERMINAL_STATUSES = ['closed', 'cancelled', 'ms_rejected'];

    public function scopeOverdue($query)
    {
        // Executed requests await closure, but their execution deadline has been met.
        return $query->whereDate('required_by', '<', today())
            ->whereNotIn('status', [...self::TERMINAL_STATUSES, 'executed', 'execution_verified']);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->required_by !== null
            && $this->required_by->lt(today())
            && ! in_array($this->status, [...self::TERMINAL_STATUSES, 'executed', 'execution_verified'], true);
    }

    public function getPendingWithAttribute(): string
    {
        return match ($this->status) {
            'draft' => $this->createdBy?->name ?? 'Requestor',
            'submitted', 'verified' => 'Administrator verification',
            'pending_ms_approval' => 'Medical Superintendent',
            'higher_approval_required' => $this->higher_authority ?: 'Higher authority',
            'ms_approved', 'higher_approved' => 'Administrator assignment',
            'execution_in_progress' => $this->assignedTo?->name ?? $this->assigned_role ?? 'Execution team',
            'executed' => 'Completion verification',
            'execution_verified' => 'Administrator closure review',
            default => '—',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }

    public function msDecidedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ms_decided_by'
        );
    }

    public function higherApprovalRecordedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'higher_approval_recorded_by'
        );
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'executed_by'
        );
    }

    public function completionVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completion_verified_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'closed_by'
        );
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            AdministrativeRequestDocument::class,
            'administrative_request_id'
        )->latest('uploaded_at');
    }

    /*
    |--------------------------------------------------------------------------
    | REQUEST NUMBER
    |--------------------------------------------------------------------------
    */

    public static function generateRequestNumber(): string
    {
        return DB::transaction(function () {

            DB::select(
                "SELECT pg_advisory_xact_lock(hashtext('administrative_request_number'))"
            );

            $date = now()->format('Ymd');

            $prefix = "ADM-{$date}-";

            $latestRequestNumber = self::query()
                ->where(
                    'request_no',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc('request_no')
                ->value('request_no');

            $nextSequence = 1;

            if ($latestRequestNumber) {
                $lastSequence = (int) substr(
                    $latestRequestNumber,
                    strlen($prefix)
                );

                $nextSequence = $lastSequence + 1;
            }

            return $prefix
                . str_pad(
                    (string) $nextSequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
        });
    }
}