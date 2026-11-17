<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualBudgetRevision extends Model
{
    public $timestamps = false;
    protected $fillable = ['annual_budget_line_id', 'previous_amount', 'new_amount', 'reason', 'actor_id', 'created_at'];
    protected $casts = ['previous_amount' => 'decimal:2', 'new_amount' => 'decimal:2', 'created_at' => 'datetime'];
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
