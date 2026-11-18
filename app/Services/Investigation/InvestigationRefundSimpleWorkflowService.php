<?php

namespace App\Services\Investigation;

use App\Models\{InvestigationCancellationRequest, InvestigationRefundSettlementCheck, Invoice, JournalEntry, Payment, User};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Click-based orchestration. Existing verification/accounting services remain authoritative. No money is transferred. */
final class InvestigationRefundSimpleWorkflowService
{
    public function __construct(private readonly InvestigationRefundVerificationService $workflow,
        private readonly InvestigationCancellationReconciliationService $reconciliation,
        private readonly InvestigationCancellationAccountingService $accounting) {}

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['refund' => $message]);
    }

    private function direct(User $actor, string $permission): void
    {
        if (!$actor->is_active || !$actor->permissions()->where('permissions.name', $permission)->exists()) {
            abort(403, 'An active user with the explicitly assigned refund permission is required.');
        }
    }

    private function paise($amount): int
    {
        if (!preg_match('/^\d{1,10}\.\d{2}$/D', (string) $amount)) $this->fail('Invalid refund amount.');
        [$whole,$fraction] = explode('.', (string) $amount);
        return (int) $whole * 100 + (int) $fraction;
    }

    private function money(int $paise): string
    {
        return intdiv($paise,100).'.'.str_pad((string)($paise%100),2,'0',STR_PAD_LEFT);
    }

    private function lockInvoice(InvestigationCancellationRequest $c): Invoice
    {
        $invoice = Invoice::whereKey($c->invoice_id)->lockForUpdate()->firstOrFail();
        Payment::where('invoice_id', $invoice->id)->orderBy('id')->lockForUpdate()->get();
        return $invoice;
    }

    /** Block before cash is paid when the unchanged accounting engine cannot accept its prerequisites. */
    private function prerequisites(InvestigationCancellationRequest $c): void
    {
        $invoice = Invoice::findOrFail($c->invoice_id);
        if ($invoice->invoice_type !== 'investigation' || $c->status !== 'executed' || !$c->executed_at) {
            $this->fail('An executed investigation cancellation is required.');
        }
        $events = [['invoice', $invoice->id, 'issued']];
        foreach (Payment::where('invoice_id',$invoice->id)->get() as $p) $events[] = ['payment',$p->id,'received'];
        foreach (InvestigationCancellationRequest::where('invoice_id',$invoice->id)->where('status','executed')->get() as $r) {
            $events[] = ['investigation_cancellation_request',$r->id,'revenue_reversed'];
        }
        foreach ($events as [$type,$id,$event]) {
            $journal = JournalEntry::where('source_type',$type)->where('source_id',(string)$id)->where('source_event',$event)->first();
            if (!$journal || $journal->status !== 'posted') $this->fail('Finance review required: invoice, receipt or cancellation accounting is missing.');
            InvestigationRefundTallyPolicy::assertCompatible($journal);
        }
    }

    private function available(InvestigationCancellationRequest $c, Payment $p, ?int $exclude = null): array
    {
        if ((int)$p->invoice_id !== (int)$c->invoice_id) $this->fail('The receipt belongs to another invoice.');
        if (!in_array(strtolower(trim((string)$p->payment_mode)),['cash','upi','card'],true)) $this->fail('Unsupported original payment method.');
        $summary = $this->reconciliation->summarize(Invoice::findOrFail($c->invoice_id));
        if ($summary['refund_hold']) $this->fail('An unresolved refund requires Finance reconciliation.');
        if ($summary['refunds']->contains(fn($r)=>(int)$r->investigation_cancellation_request_id===(int)$c->id)) {
            $this->fail('This cancellation already has a refund. Use Finance reconciliation.');
        }
        $reservations = InvestigationRefundSettlementCheck::whereHas('cancellation',fn($q)=>$q->where('invoice_id',$c->invoice_id))
            ->whereIn('status',['initiated','disbursed','submitted','verified'])
            ->when($exclude !== null,fn($q)=>$q->whereKeyNot($exclude))->get();
        $reserved = $reservations->sum(fn($r)=>$this->paise($r->amount));
        $receiptReserved = $reservations->where('payment_id',$p->id)->sum(fn($r)=>$this->paise($r->amount));
        $refundedForReceipt = $summary['refunds']->where('payment_id',$p->id)->sum(fn($r)=>$this->paise($r->amount));
        $overall = max(0,min($summary['potential_refund']-$reserved,$this->paise($c->approved_amount)));
        try {
            $policy = app(InvestigationRefundAllocationPolicy::class)->available(
                $summary['invoice_total'],$summary['paid'],$summary['executed_cancellations'],$summary['completed_refunds'],
                $this->paise($c->approved_amount),0,$this->paise($p->amount),$refundedForReceipt);
        } catch (\InvalidArgumentException $e) {
            $this->fail('Finance reconciliation required: receipt and refund balances do not agree.');
        }
        $available = max(0,min($policy,$overall,$this->paise($p->amount)-$refundedForReceipt-$receiptReserved));
        return ['available'=>$available,'overall'=>$overall];
    }

    /** Read-only receipt choices. One settlement per cancellation is an existing accounting restriction. */
    public function preview(InvestigationCancellationRequest $c): array
    {
        try {
            $this->prerequisites($c);
            if (InvestigationRefundSettlementCheck::where('cancellation_id',$c->id)
                ->whereIn('status',['initiated','disbursed','submitted','verified','completed'])->exists()) {
                $this->fail('A refund request already exists for this cancellation.');
            }
            $choices=[];
            foreach (Payment::where('invoice_id',$c->invoice_id)->orderBy('id')->get() as $p) {
                if (!in_array(strtolower(trim((string)$p->payment_mode)),['cash','upi','card'],true)) continue;
                $a=$this->available($c,$p);
                if ($a['overall']>0 && $a['available']===$a['overall']) {
                    $choices[]=['payment'=>$p,'amount'=>$this->money($a['available']),'method'=>strtolower(trim((string)$p->payment_mode))];
                }
            }
            return ['choices'=>$choices,'reason'=>$choices ? null : 'No single receipt covers the eligible refund. Finance review may be needed for split receipts or an unpaid bill.'];
        } catch (ValidationException $e) {
            return ['choices'=>[],'reason'=>collect($e->errors())->flatten()->first()];
        }
    }

    public function requestRefund(InvestigationCancellationRequest $c, Payment $p, User $actor, string $expected): InvestigationRefundSettlementCheck
    {
        $this->direct($actor,'billing.investigation-refund.process');
        return DB::transaction(function() use($c,$p,$actor,$expected) {
            $c=InvestigationCancellationRequest::whereKey($c->id)->lockForUpdate()->firstOrFail();
            $this->lockInvoice($c);
            $p=Payment::findOrFail($p->id);
            $this->prerequisites($c);
            $a=$this->available($c,$p);
            if ($a['overall']<=0 || $a['available']!==$a['overall'] || $this->paise($expected)!==$a['available']) {
                $this->fail('Refund eligibility changed. Refresh and choose the receipt again.');
            }
            return $this->workflow->initiate($c,$p,$actor,['amount'=>$this->money($a['available']),
                'settlement_method'=>strtolower(trim((string)$p->payment_mode))]);
        },3);
    }

    private function reference(string $value): string
    {
        $value=trim($value);
        if (strlen($value)<6 || strlen($value)>100 || preg_match('/[\x00-\x1F\x7F]/',$value)) {
            $this->fail('Enter the actual payment or document reference (6–100 characters).');
        }
        return $value;
    }

    private function proof(string $mode, string $kind): string
    {
        $labels=['cash_voucher'=>'signed cash refund voucher','cash_register'=>'cash register with recipient acknowledgement',
            'bank_statement'=>'bank statement','upi_confirmation'=>'UPI refund confirmation','card_confirmation'=>'card refund confirmation'];
        $allowed=match($mode){'cash'=>['cash_voucher','cash_register'],'upi'=>['bank_statement','upi_confirmation'],'card'=>['bank_statement','card_confirmation'],default=>[]};
        if (!in_array($kind,$allowed,true)) $this->fail('Choose the payment evidence you actually reviewed.');
        return $labels[$kind];
    }

    public function recordPayment(InvestigationRefundSettlementCheck $check, User $actor, array $input): InvestigationRefundSettlementCheck
    {
        $this->direct($actor,'billing.investigation-refund.process');
        if (!filter_var($input['actually_paid']??false,FILTER_VALIDATE_BOOLEAN)) $this->fail('Confirm that the refund was actually paid.');
        return DB::transaction(function() use($check,$actor,$input) {
            $s=InvestigationRefundSettlementCheck::whereKey($check->id)->lockForUpdate()->firstOrFail();
            if ($s->status!=='initiated') $this->fail('Payment has already been recorded or this request is not payable.');
            $c=InvestigationCancellationRequest::whereKey($s->cancellation_id)->lockForUpdate()->firstOrFail();
            $this->lockInvoice($c);$p=Payment::findOrFail($s->payment_id);$this->prerequisites($c);
            if ($this->available($c,$p,$s->id)['available']<$this->paise($s->amount)) $this->fail('Refund balance changed. Finance review is required before payment.');
            $mode=strtolower((string)$s->settlement_method);
            $reference=$mode==='cash' && trim((string)($input['settlement_reference']??''))===''
                ? 'CASH-REFUND-'.$s->id : $this->reference((string)($input['settlement_reference']??''));
            $notes=trim((string)($input['notes']??''));
            if (strlen($notes)>500) $this->fail('Optional notes must be 500 characters or less.');
            $evidence='Cashier confirmation recorded in ERP: actor '.$actor->id.'; refund '.$s->id.'; receipt '.$p->receipt_no
                .'; amount '.$s->amount.'; method '.$mode.'; recorded at '.now()->toIso8601String().'. '
                .($mode==='cash' ? 'Cash handover attested by cashier. Internal voucher reference is not independent proof of payment. ' : 'Cashier supplied actual external refund reference: '.$reference.'. ')
                .$notes;
            return $this->workflow->disburse($s,$actor,['actually_paid'=>true,'settlement_reference'=>$reference,'evidence_description'=>$evidence]);
        },3);
    }

    public function verifyRefund(InvestigationRefundSettlementCheck $check, User $actor, array $input): InvestigationRefundSettlementCheck
    {
        $this->direct($actor,'billing.investigation-refund.verify');
        if (!filter_var($input['evidence_checked']??false,FILTER_VALIDATE_BOOLEAN)) $this->fail('Confirm independent examination of actual payment evidence.');
        return DB::transaction(function() use($check,$actor,$input) {
            $s=InvestigationRefundSettlementCheck::whereKey($check->id)->lockForUpdate()->firstOrFail();
            if (!in_array($s->status,['disbursed','submitted'],true)) $this->fail('Only a recorded payment can be verified.');
            $c=InvestigationCancellationRequest::whereKey($s->cancellation_id)->lockForUpdate()->firstOrFail();
            foreach ([$s->submitted_by,$s->initiated_by,$s->disbursed_by,$c->requested_by] as $id) {
                if ($id!==null && (int)$id===(int)$actor->id) $this->fail('Independent verifier required. Use a different explicitly authorized user.');
            }
            $this->lockInvoice($c);$p=Payment::findOrFail($s->payment_id);$this->prerequisites($c);
            if ($this->available($c,$p,$s->id)['available']<$this->paise($s->amount)) $this->fail('Refund balance changed. Reconcile before completing.');
            $mode=strtolower((string)$s->settlement_method);$proof=$this->proof($mode,(string)($input['proof_kind']??''));
            $notes=trim((string)($input['notes']??''));if(strlen($notes)>500)$this->fail('Optional notes must be 500 characters or less.');
            $history='';
            if ($s->status==='submitted') {
                $reference=$this->reference((string)($input['legacy_payment_reference']??''));
                $value=(string)($input['legacy_payment_date']??'');
                if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value))$this->fail('Enter the actual legacy refund payment date from evidence.');
                try {$date=CarbonImmutable::createFromFormat('!Y-m-d',$value,config('app.timezone'));}
                catch(\Throwable){$this->fail('Invalid payment date.');}
                if(!$date || $date->format('Y-m-d')!==$value || $date->gt(today()))$this->fail('The actual payment date must be valid and not in the future.');
                if(InvestigationRefundSettlementCheck::where('settlement_method',$mode)->where('settlement_reference',$reference)->whereKeyNot($s->id)->exists())$this->fail('This payment reference already belongs to another refund.');
                $history='Legacy actual payment date: '.$value.' (date from evidence; no new payment time inferred). Legacy details completed by verifier from '.$proof.'. Previous settlement reference: '.$s->settlement_reference
                    .'; previous payment date: '.($s->disbursed_at?->toIso8601String()??'not recorded').'. ';
                $knownTime=$s->disbursed_at && $s->disbursed_at->format('Y-m-d')===$value ? $s->disbursed_at : null;
                $s->forceFill(['settlement_reference'=>$reference,'disbursement_reference'=>$reference,'disbursed_at'=>$knownTime,
                    'disbursement_evidence'=>'Verifier '.$actor->id.' supplied actual payment date/reference from '.$proof.'. Original legacy evidence retained.'])->save();
            }
            $generated=$history.'Independent verifier '.$actor->id.' confirmed review of '.$proof.'; refund '.$s->id
                .'; receipt '.$p->receipt_no.'; amount '.$s->amount.'; method '.$mode.'; payment reference '.$s->settlement_reference
                .'; actual payment recorded at '.($s->disbursed_at?->toIso8601String()??'unknown')
                .'; verification recorded at '.now()->toIso8601String().'. '.$notes;
            return $this->workflow->verify($s,$actor,$generated,$this->accounting);
        },3);
    }
}
