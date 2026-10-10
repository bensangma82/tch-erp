<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class JournalEntryLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'line_no',
        'general_ledger_account_id',
        'department_id',
        'debit',
        'credit',
        'reference_no',
        'description',
    ];

    protected $casts = [
        'line_no' => 'integer',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(
            JournalEntry::class,
            'journal_entry_id'
        );
    }

    public function generalLedgerAccount(): BelongsTo
    {
        return $this->belongsTo(
            GeneralLedgerAccount::class,
            'general_ledger_account_id'
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }
    protected static function booted(): void
{
    $checkDraft = function (self $line): void {
        if (DB::transactionLevel() === 0) {
            throw new \RuntimeException(
                'Journal line changes require a database transaction.'
            );
        }

        $journal = JournalEntry::query()
            ->whereKey($line->journal_entry_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($journal->status !== 'draft') {
            throw new \RuntimeException(
                'Only draft journal lines can be modified.'
            );
        }
    };

    static::creating($checkDraft);

    static::updating(function (self $line) use ($checkDraft) {
        if ($line->isDirty('journal_entry_id')) {
            throw new \RuntimeException(
                'Journal lines cannot be moved between journals.'
            );
        }

        $checkDraft($line);
    });

    static::deleting($checkDraft);
}
}