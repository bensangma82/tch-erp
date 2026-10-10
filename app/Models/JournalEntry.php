<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'journal_no',
        'journal_date',
        'source_type',
        'source_id',
        'source_event',
        'finance_voucher_id',
        'reference_no',
        'narration',
        'status',
        'reversal_of_id',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'journal_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(
            JournalEntryLine::class,
            'journal_entry_id'
        )->orderBy('line_no');
    }

    public function financeVoucher(): BelongsTo
    {
        return $this->belongsTo(
            FinanceVoucher::class,
            'finance_voucher_id'
        );
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'reversal_of_id'
        );
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(
            self::class,
            'reversal_of_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'posted_by'
        );
    }
    protected static function booted(): void
{
    static::updating(function (self $journal) {
        if ($journal->getOriginal('status') !== 'draft') {
            throw new \RuntimeException(
                'Posted or reversed journals cannot be modified.'
            );
        }

        if ($journal->isDirty('status')
            && $journal->status !== 'posted') {
            throw new \RuntimeException(
                'Invalid journal status transition.'
            );
        }

        if ($journal->isDirty('status')
            && $journal->status === 'posted'
            && (
                ! $journal->posted_at
                || ! $journal->posted_by
            )) {
            throw new \RuntimeException(
                'Posting requires an authorized user and timestamp.'
            );
        }
    });

    static::deleting(function (self $journal) {
        if ($journal->status !== 'draft') {
            throw new \RuntimeException(
                'Posted or reversed journals cannot be deleted.'
            );
        }
    });
}
}