<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MhisLegacySettlement extends Model
{
    protected $fillable = [
        'mhis_opening_receivable_id',
        'settlement_date',
        'settlement_reference',
        'historical_claim_reference',
        'net_amount',
        'tds_amount',
        'approved_deduction',
        'gross_settled_amount',
        'status',
        'remarks',
        'created_by',
        'verified_by',
        'verified_at',
        'journal_entry_id',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'net_amount' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'approved_deduction' => 'decimal:2',
        'gross_settled_amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function openingReceivable(): BelongsTo
    {
        return $this->belongsTo(
            MhisOpeningReceivable::class,
            'mhis_opening_receivable_id'
        );
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(
            JournalEntry::class,
            'journal_entry_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
