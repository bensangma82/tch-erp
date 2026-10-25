<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvestigationRefundSettlementCheck extends Model {
    protected $fillable = ['cancellation_id','payment_id','amount','settlement_method','settlement_reference','evidence_description','status','submitted_by','verified_by','verified_at','verification_notes','refund_id'];
    protected $casts = ['amount'=>'decimal:2','verified_at'=>'datetime'];
}
