<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TallyImportedVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'tally_company',
        'guid',
        'remote_id',
        'voucher_date',
        'voucher_type',
        'voucher_number',
        'party_ledger',
        'narration',
        'finance_voucher_id',
        'reconciliation_status',
        'match_method',
        'match_confidence',
        'reconciliation_notes',
        'reconciled_by',
        'reconciled_at',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'match_confidence' => 'decimal:2',
        'reconciled_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(
            TallyImportedVoucherEntry::class
        );
    }

    public function financeVoucher(): BelongsTo
    {
        return $this->belongsTo(
            FinanceVoucher::class
        );
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reconciled_by'
        );
    }
}
