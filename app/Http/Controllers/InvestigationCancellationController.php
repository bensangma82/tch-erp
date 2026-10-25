<?php

namespace App\Http\Controllers;

use App\Models\InvestigationCancellationRequest;
use App\Models\InvoiceItem;
use App\Services\Investigation\InvestigationCancellationApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Stage 4A: request/approval endpoints only. NO accounting execution or refunds. */
class InvestigationCancellationController extends Controller
{
    public function requestCancellation(Request $request, InvoiceItem $invoiceItem, InvestigationCancellationApprovalService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $service->request($invoiceItem, $request->user(), $data['reason']);
        return back()->with('success', 'Investigation cancellation submitted for approval. No financial reversal has occurred.');
    }

    public function decide(Request $request, InvestigationCancellationRequest $cancellation, InvestigationCancellationApprovalService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'decision_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $service->decide($cancellation, $request->user(), $data['decision'] === 'approve', $data['decision_reason']);
        return back()->with('success', 'Decision recorded. Financial execution remains disabled pending integration tests.');
    }
}
