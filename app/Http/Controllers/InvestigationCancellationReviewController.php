<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvestigationCancellationRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

/** Read-only management views; no refunds, journal writes or clinical edits. */
class InvestigationCancellationReviewController extends Controller
{
   public function queue(Request $request): View
{
    abort_unless(
        $request->user()?->hasPermission(
            'billing.investigation-cancellation.approve'
        ),
        403
    );
        $status = $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'approved', 'rejected', 'executed', 'all'], true)) {
            abort(422, 'Invalid status filter.');
        }
        $query = InvestigationCancellationRequest::query()
            ->with(['invoice', 'invoiceItem', 'requester', 'reviewer'])
            ->orderByDesc('id');
        if ($status !== 'all') { $query->where('status', $status); }
        return view('billing.investigation-cancellations.queue', [
            'cancellations' => $query->paginate(25)->withQueryString(),
            'status' => $status,
            'canDecide' => $request->user()->hasPermission('billing.investigation-cancellation.approve'),
        ]);
    }

    public function invoice(Request $request, Invoice $invoice): View
    {
        $this->authorizeScreen($request);
        abort_unless($invoice->invoice_type === 'investigation', 404);
        $invoice->load(['items.serviceOrderItem.diagnosticSample',
            'items.serviceOrderItem.diagnosticResult']);
        $requests = InvestigationCancellationRequest::query()
            ->where('invoice_id', $invoice->id)->latest('id')->get()->groupBy('invoice_item_id');
        return view('billing.investigation-cancellations.invoice', [
            'invoice' => $invoice,
            'requestsByItem' => $requests,
            'canRequest' => $request->user()->hasPermission('billing.investigation-cancellation.request'),
        ]);
    }

    private function authorizeScreen(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && (
            $user->hasPermission('billing.investigation-cancellation.request') ||
            $user->hasPermission('billing.investigation-cancellation.approve')
        ), 403);
    }
}
