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
            // Fail closed: only exact, positive monetary amounts within the approved
            // cancellation value and the originating receipt are eligible.
            // The accounting service remains the final allocation/duplicate guard.
            $amount = (string) ($data['amount'] ?? '');
            if (!preg_match('/^[0-9]{1,10}\.[0-9]{2}$/D', $amount)) {
                $this->deny('A valid refund amount is required.');
            }
            $cents = static function ($value): int {
                $value = (string) $value;
                if (!preg_match('/^[0-9]{1,10}\.[0-9]{2}$/D', $value)) {
                    throw ValidationException::withMessages(['refund' => 'Invalid monetary value.']);
                }
                [$whole, $fraction] = explode('.', $value);
                return ((int) $whole * 100) + (int) $fraction;
            };
            $requestedCents = $cents($amount);
            $approvedCents = $cents($c->approved_amount);
            $paymentCents = $cents($p->amount);
            if ($requestedCents <= 0 || $requestedCents > $approvedCents || $requestedCents > $paymentCents) {
                $this->deny('Refund exceeds the approved cancellation or original receipt.');
            }
            // Full audit-chain inspection before another refund submission.
            // Never automatically reissue if a previous refund is present.
            if (InvestigationCancellationRefund::where('investigation_cancellation_request_id', $c->id)->exists()) {
                $this->deny('A refund is already recorded for this cancellation. Reconcile before proceeding.');
            }

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
