<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestigationMsApprover extends Model
{
    protected $fillable = ['user_id', 'active', 'designated_by', 'designated_at'];

    protected $casts = ['active' => 'boolean', 'designated_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function designator(): BelongsTo { return $this->belongsTo(User::class, 'designated_by'); }
}
