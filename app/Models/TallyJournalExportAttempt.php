<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallyJournalExportAttempt extends Model
{
    protected $fillable = [
        'tally_export_id',
        'attempt_number',
        'status',
        'remote_id',
        'export_reference',
        'tally_accounting_date',
        'tally_response',
        'error_message',
        'recovery_authorized_by',
        'recovery_authorized_at',
        'recovery_reason',
        'reconciliation_evidence',
        'transmitted_at',
        'confirmed_at',
    ];

    protected $casts = [
        'tally_accounting_date' => 'date',
        'recovery_authorized_at' => 'datetime',
        'transmitted_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function tallyExport(): BelongsTo
    {
        return $this->belongsTo(TallyExport::class);
    }

    public function recoveryAuthorizedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recovery_authorized_by'
        );
    }
}
