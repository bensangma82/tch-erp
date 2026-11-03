<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MhisOpeningReceivable extends Model
{
    protected $fillable = [
        'opening_date',
        'financial_year',
        'opening_amount',
        'statement_reference',
        'remarks',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'journal_entry_id',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'opening_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(
            JournalEntry::class,
            'journal_entry_id'
        );
    }
}
