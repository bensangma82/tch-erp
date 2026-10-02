<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallyLedgerMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_head_id',
        'finance_account_id',
        'tally_ledger_name',
        'tally_group_name',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Income / expense Finance Head mapped to this Tally ledger.
     */
    public function financeHead(): BelongsTo
    {
        return $this->belongsTo(
            FinanceHead::class,
            'finance_head_id'
        );
    }

    /**
     * Cash / bank Finance Account mapped to this Tally ledger.
     */
    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(
            FinanceAccount::class,
            'finance_account_id'
        );
    }
}