<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpBillingMhisClaimPackage extends Model
{
    protected $fillable = [
        'ip_billing_mhis_claim_id', 'mhis_procedure_id',
        'procedure_code', 'procedure_name', 'reference_tariff', 'sort_order',
        'item_type', 'mhis_high_end_item_id',
    ];

    protected $casts = [
        'reference_tariff' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function highEndItem(): BelongsTo
    {
        return $this->belongsTo(MhisHighEndItem::class, 'mhis_high_end_item_id');
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(IpBillingMhisClaim::class, 'ip_billing_mhis_claim_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(MhisProcedure::class, 'mhis_procedure_id');
    }
}
