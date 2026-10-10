<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneralLedgerAccount extends Model
{
    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'account_group',
        'parent_id',
        'finance_head_id',
        'finance_account_id',
        'tally_ledger_name',
        'tally_group_name',
        'normal_balance',
        'is_active',
        'allow_posting',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allow_posting' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_id'
        );
    }

    public function financeHead(): BelongsTo
    {
        return $this->belongsTo(FinanceHead::class);
    }

    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(
            JournalEntryLine::class,
            'general_ledger_account_id'
        );
    }
}