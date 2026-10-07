<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TallyImportedVoucherEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tally_imported_voucher_id',
        'ledger_name',
        'amount',
        'is_deemed_positive',
        'line_no',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_deemed_positive' => 'boolean',
        'line_no' => 'integer',
    ];

    public function tallyImportedVoucher(): BelongsTo
    {
        return $this->belongsTo(
            TallyImportedVoucher::class
        );
    }
}