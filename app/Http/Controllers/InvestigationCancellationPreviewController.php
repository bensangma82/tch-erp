<?php

namespace App\Http\Controllers;

use App\Models\GeneralLedgerAccount;
use App\Models\InvestigationCancellationRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only proposed journal; deliberately does not invoke the accounting service. */
class InvestigationCancellationPreviewController extends Controller
{
    public function show(Request $httpRequest, InvestigationCancellationRequest $cancellation): View
    {
        abort_unless($httpRequest->user()?->hasPermission('billing.investigation-cancellation.approve'), 403);
        abort_unless($cancellation->status === 'approved', 404);

        $cancellation->load(['invoice', 'invoiceItem.service', 'serviceOrderItem.diagnosticSample',
            'serviceOrderItem.diagnosticResult', 'reviewer']);
        $invoice = $cancellation->invoice;
        $item = $cancellation->invoiceItem;
        abort_unless($invoice && $invoice->invoice_type === 'investigation'
            && $item && (int) $item->invoice_id === (int) $invoice->id, 422);

        $service = $item->service;
        $revenue = $service?->finance_head_id
            ? GeneralLedgerAccount::query()->where('finance_head_id', $service->finance_head_id)
                ->whereIn('account_code', ['4201', '4202', '4203', '4204', '4205', '4206'])
                ->where('is_active', true)->where('allow_posting', true)->get()
            : collect();
        $receivable = GeneralLedgerAccount::query()->where('account_code', '1200')
            ->where('is_active', true)->where('allow_posting', true)->get();
        $amount = (string) $cancellation->approved_amount;
        $mappingValid = $revenue->count() === 1 && $receivable->count() === 1;
        $amountValid = is_numeric($amount) && (float) $amount > 0
            && round((float) $amount, 2) === round((float) $item->amount, 2);
        $clinical = $cancellation->serviceOrderItem;

        return view('billing.investigation-cancellations.preview', compact(
            'cancellation', 'invoice', 'item', 'revenue', 'receivable',
            'amount', 'mappingValid', 'amountValid', 'clinical'
        ));
    }
}
