<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallyExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_voucher_id',
        'status',
        'export_reference',
        'remote_id',
        'tally_voucher_id',
        'tally_response',
        'exported_at',
        'exported_by',
        'confirmed_at',
        'error_message',
        'remarks',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function financeVoucher(): BelongsTo
    {
        return $this->belongsTo(FinanceVoucher::class);
    }

    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'exported_by'
        );
    }
}
