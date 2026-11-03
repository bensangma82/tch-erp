<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MhisSettlementImportBatch extends Model
{
    protected $fillable = [
        'original_filename',
        'file_sha256',
        'status',
        'total_rows',
        'total_approved',
        'total_tds',
        'total_net',
        'uploaded_by',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'total_approved' => 'decimal:2',
        'total_tds' => 'decimal:2',
        'total_net' => 'decimal:2',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(
            MhisSettlementImportRow::class,
            'batch_id'
        );
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
