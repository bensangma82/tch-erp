<?php

namespace App\Http\Controllers;

use App\Models\InvestigationCancellationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use App\Services\Investigation\InvestigationCancellationReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Development reconciliation UI and tightly restricted cash-settlement recording. */
final class InvestigationCancellationSettlementController extends Controller
{
    public function show(Request $request, Invoice $invoice, InvestigationCancellationReconciliationService $reconciliation): View
    {
        abort_unless($request->user()?->hasPermission('billing.investigation-cancellation.approve'), 403);
        $summary = $reconciliation->summarize($invoice);
        $dummyEnabled = $this->dummyEnabled() && (int) $request->user()->id === 2
            && (int) $invoice->id === 29;
        $eligible = $summary['requests']->first(fn ($r) => (int) $r->id === 2 && $r->status === 'executed');
        return view('billing.investigation-cancellations.reconciliation', compact('invoice', 'summary', 'dummyEnabled', 'eligible'));
    }

    public function refund(
        Request $request,
        InvestigationCancellationRequest $cancellation,
        InvestigationCancellationAccountingService $accounting
    ): RedirectResponse {
        // Disabled by default. Never generalize this endpoint to live patients.
        abort_unless($this->dummyEnabled(), 404);
        $actor = $request->user();
        abort_unless($actor && (int) $actor->id === 2 && $actor->hasRole('admin'), 403);
        $input = $request->validate([
            'payment_id' => ['required', 'integer', 'in:29'],
            'amount' => ['required', 'in:500.00'],
            'reference' => ['required', 'string', 'min:6', 'max:100'],
            'confirmation' => ['required', 'in:RECEIPT 29 CASH REFUND PAID'],
            'cashier_attestation' => ['accepted'],
        ]);
        abort_unless((int) $cancellation->id === 2
            && (int) $cancellation->invoice_id === 29
            && $cancellation->status === 'executed'
            && (string) $cancellation->approved_amount === '500.00'
            && $cancellation->invoice?->invoice_no === 'INV-20261025-000002', 404);
        $payment = Payment::query()->whereKey(29)->where('invoice_id', 29)
            ->where('payment_mode', 'cash')->where('amount', '1800.00')->firstOrFail();
        // IMPORTANT: User must actually pay cash before using this operation.
        // This application does NOT verify physical handover independently.
        $journal = $accounting->recordCompletedRefund(
            $cancellation, $payment, $actor, $input['reference'], $input['amount']
        );
        return redirect()->route('billing.investigation-cancellations.reconciliation', 29)
            ->with('success', 'Cash refund recorded: '.$journal->journal_no.'. Independently reconcile cash drawer.');
    }

    private function dummyEnabled(): bool
    {
        return app()->environment('local')
            && DB::getDefaultConnection() === 'pgsql'
            && (DB::selectOne('select current_database() as db')->db ?? '') === 'laravel'
            && config('investigation_cancellation.allow_dummy_refund', false) === true;
    }
}
