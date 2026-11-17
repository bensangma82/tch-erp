<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualBudgetLine extends Model
{
    protected $fillable = ['start_year', 'general_ledger_account_id', 'category', 'original_amount', 'current_amount', 'created_by'];
    protected $casts = ['start_year' => 'integer', 'original_amount' => 'decimal:2', 'current_amount' => 'decimal:2'];

    public function ledger() { return $this->belongsTo(GeneralLedgerAccount::class, 'general_ledger_account_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function revisions() { return $this->hasMany(AnnualBudgetRevision::class)->orderByDesc('id'); }
}
