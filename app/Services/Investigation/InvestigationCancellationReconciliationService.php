<?php

namespace App\Services\Investigation;

use App\Models\Invoice;
use App\Models\InvestigationCancellationRefund;
use App\Models\InvestigationCancellationRequest;
use App\Models\Payment;
use Illuminate\Validation\ValidationException;

/** Read-only accounting summary, derived from immutable original receipts. */
final class InvestigationCancellationReconciliationService
{
    private function paise(mixed $value): int
    {
        if (!is_numeric($value) || (float) $value < 0) {
            throw ValidationException::withMessages(['accounting' => 'Invalid invoice accounting value.']);
        }
        return (int) round(((float) $value) * 100);
    }

    public function summarize(Invoice $invoice): array
    {
        if ($invoice->invoice_type !== 'investigation') {
            abort(404);
        }
        $total = $this->paise($invoice->total_amount);
        $payments = Payment::query()->where('invoice_id', $invoice->id)->get();
        $paid = $payments->sum(fn ($p) => $this->paise($p->amount));
        $executed = InvestigationCancellationRequest::query()
            ->where('invoice_id', $invoice->id)->where('status', 'executed')->get();
        $cancelled = $executed->sum(fn ($r) => $this->paise($r->approved_amount));
        $refunds = InvestigationCancellationRefund::query()
            ->whereHas('cancellationRequest', fn ($q) => $q->where('invoice_id', $invoice->id))
            ->get();
        $refunded = $refunds->where('status', 'completed')->sum(fn ($r) => $this->paise($r->amount));
        $unresolved = $refunds->contains(fn ($r) => $r->status !== 'completed');
        if ($cancelled > $total || $refunded > $paid || $paid > $total) {
            throw ValidationException::withMessages(['accounting' => 'Unreconciled totals; manual review required.']);
        }
        $net = $total - $cancelled;
        $netPaid = $paid - $refunded;
        $overpayment = max(0, $netPaid - $net);
        $balance = max(0, $net - $netPaid);
        return [
            'invoice_total' => $total,
            'paid' => $paid,
            'executed_cancellations' => $cancelled,
            'revised_net_bill' => $net,
            'completed_refunds' => $refunded,
            'net_paid' => $netPaid,
            'balance_due' => $balance,
            'potential_refund' => $overpayment,
            'refund_hold' => $unresolved,
            'requests' => InvestigationCancellationRequest::query()->where('invoice_id', $invoice->id)->latest('id')->get(),
            'refunds' => $refunds,
        ];
    }
}
