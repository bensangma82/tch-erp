<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MhisSettlementImportRow extends Model
{
    protected $fillable = [
        'batch_id',
        'excel_row_number',
        'registration_id',
        'beneficiary_name',
        'programme_id',
        'procedure_name',
        'initiated_amount',
        'approved_amount',
        'tds_amount',
        'net_amount',
        'utr_reference',
        'payment_date',
        'payment_status',
        'matched_claim_id',
        'match_status',
        'match_notes',
        'source_data',
        'posted_receipt_id', 'posted_journal_id', 'posted_by',
        'bank_verified_by', 'bank_verified_at', 'posted_at',
    ];

    protected $casts = [
        'excel_row_number' => 'integer',
        'initiated_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'tds_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'date',
        'source_data' => 'array',
        'bank_verified_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            MhisSettlementImportBatch::class,
            'batch_id'
        );
    }

    public function matchedClaim(): BelongsTo
    {
        return $this->belongsTo(
            IpBillingMhisClaim::class,
            'matched_claim_id'
        );
    }
}
