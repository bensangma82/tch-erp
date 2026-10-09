<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallyLedgerMaster extends Model
{
    protected $fillable = [
        'tally_company',
        'ledger_name',
        'parent_group',
        'closing_balance',
        'department_id',
        'last_synced_at',
    ];

    protected $casts = [
        'closing_balance' => 'decimal:2',
        'department_id' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}