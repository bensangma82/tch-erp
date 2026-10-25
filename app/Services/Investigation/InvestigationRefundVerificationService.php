<?php
namespace App\Services\Investigation;
use App\Models\{InvestigationCancellationRequest,InvestigationRefundSettlementCheck,InvestigationCancellationRefund,Payment,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Maker-checker accounting record of an ALREADY PAID refund. Never transfers funds. */
final class InvestigationRefundVerificationService {
    private function deny(string $message): never { throw ValidationException::withMessages(['refund'=>$message]); }
    private function direct(User $user, string $permission): bool {
        return $user->permissions()->where('permissions.name',$permission)->exists();
    }
    public function submit(InvestigationCancellationRequest $request, Payment $payment, User $cashier, array $data): InvestigationRefundSettlementCheck {
        if (!$this->direct($cashier,'billing.investigation-refund.process')) $this->deny('Explicit refund permission required.');
        return DB::transaction(function () use ($request,$payment,$cashier,$data) {
            $c = InvestigationCancellationRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($c->status !== 'executed' || !$c->executed_at || (int)$payment->invoice_id !== (int)$c->invoice_id) $this->deny('Cancellation and payment mismatch.');
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $method = strtolower((string)$p->payment_mode);
            if (!in_array($method,['cash','upi','card'],true) || $data['settlement_method'] !== $method) $this->deny('Refund method must match original payment.');
            if (empty($data['actually_paid'])) $this->deny('External settlement must already be completed.');
            if (InvestigationRefundSettlementCheck::where('cancellation_id',$c->id)->whereIn('status',['submitted','verified','completed'])->exists()) $this->deny('An existing settlement requires reconciliation before another submission.');
            return InvestigationRefundSettlementCheck::create([
                'cancellation_id'=>$c->id,'payment_id'=>$p->id,'amount'=>$data['amount'],
                'settlement_method'=>$method,'settlement_reference'=>trim($data['settlement_reference']),
                'evidence_description'=>trim($data['evidence_description']),'status'=>'submitted','submitted_by'=>$cashier->id,
            ]);
        },3);
    }
    public function verify(InvestigationRefundSettlementCheck $check, User $verifier, string $notes, InvestigationCancellationAccountingService $accounting): InvestigationRefundSettlementCheck {
        if (!$this->direct($verifier,'billing.investigation-refund.verify')) $this->deny('Direct verifier permission required; admin override is not sufficient.');
        return DB::transaction(function () use ($check,$verifier,$notes,$accounting) {
            $s = InvestigationRefundSettlementCheck::whereKey($check->id)->lockForUpdate()->firstOrFail();
            if ($s->status !== 'submitted') $this->deny('Settlement has already been reviewed.');
            $c = InvestigationCancellationRequest::whereKey($s->cancellation_id)->lockForUpdate()->firstOrFail();
            if ((int)$s->submitted_by === (int)$verifier->id || (int)$c->requested_by === (int)$verifier->id) $this->deny('Independent verifier required.');
            if (strlen(trim($notes)) < 10) $this->deny('Evidence verification notes required.');
            // The verifier must have inspected an independent cash voucher/bank confirmation.
            // This application cannot connect to a bank or verify physical currency.
            $p = Payment::whereKey($s->payment_id)->firstOrFail();
            $journal = $accounting->recordCompletedRefund($c,$p,$verifier,$s->settlement_reference,(string)$s->amount);
            $refund = InvestigationCancellationRefund::where('investigation_cancellation_request_id',$c->id)
                ->where('payment_id',$p->id)->where('reference_no',$s->settlement_reference)->firstOrFail();
            $s->forceFill(['status'=>'completed','verified_by'=>$verifier->id,'verified_at'=>now(),
                'verification_notes'=>trim($notes),'refund_id'=>$refund->id])->save();
            return $s->refresh();
        },3);
    }
}
