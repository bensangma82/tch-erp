<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MhisPackageVarianceApproval extends Model
{
    protected $fillable = [
        'ip_billing_mhis_claim_id', 'ip_billing_account_id',
        'bill_amount', 'approved_amount', 'variance_amount',
        'treatment', 'supporting_reference', 'reason',
        'supporting_evidence', 'contractual_nonrecoverable',
        'status', 'created_by', 'submitted_at', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'bill_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'contractual_nonrecoverable' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(IpBillingMhisClaim::class, 'ip_billing_mhis_claim_id');
    }
}
