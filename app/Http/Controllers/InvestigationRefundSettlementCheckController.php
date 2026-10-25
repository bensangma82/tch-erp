<?php

namespace App\Http\Controllers;

use App\Models\InvestigationCancellationRequest;
use App\Models\InvestigationRefundSettlementCheck;
use App\Models\Payment;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use App\Services\Investigation\InvestigationRefundVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Investigation refund settlement verification.
 *
 * This controller records refund settlement evidence and
 * authorizes accounting entries after independent verification.
 *
 * It does not transfer money through a payment gateway.
 */
final class InvestigationRefundSettlementCheckController extends Controller
{
    /**
     * Display pending refund settlements.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->hasPermission('billing.investigation-refund.process') ||
                $user->permissions()
                    ->where(
                        'permissions.name',
                        'billing.investigation-refund.verify'
                    )
                    ->exists()
            ),
            403
        );

        $pending = InvestigationRefundSettlementCheck::query()
            ->where('status', 'submitted')
            ->orderBy('id')
            ->paginate(20);

        return view(
            'billing.investigation-cancellations.refund-verification',
            compact('pending')
        );
    }

    /**
     * Submit refund settlement evidence.
     *
     * No accounting journal is posted at this stage.
     */
    public function submit(
        Request $request,
        InvestigationCancellationRequest $cancellation,
        InvestigationRefundVerificationService $service
    ): RedirectResponse {
        $this->ensureRefundFeatureEnabled();

        $data = $request->validate([
            'payment_id' => [
                'required',
                'integer',
                'exists:payments,id',
            ],
            'amount' => [
                'required',
                'regex:/^\d{1,10}\.\d{2}$/',
            ],
            'settlement_method' => [
                'required',
                'in:cash,upi,card',
            ],
            'settlement_reference' => [
                'required',
                'string',
                'min:6',
                'max:100',
            ],
            'evidence_description' => [
                'required',
                'string',
                'min:15',
                'max:2000',
            ],
            'actually_paid' => [
                'required',
                'accepted',
            ],
        ]);

        $payment = Payment::findOrFail($data['payment_id']);

        $service->submit(
            $cancellation,
            $payment,
            $request->user(),
            $data
        );

        return back()->with(
            'success',
            'Settlement evidence submitted successfully. ' .
            'Independent verification is required. ' .
            'No accounting journal has been posted.'
        );
    }

    /**
     * Independently verify a refund settlement.
     *
     * The service performs permission checks, separation
     * of duties, duplicate prevention, and journal posting.
     */
    public function verify(
        Request $request,
        InvestigationRefundSettlementCheck $check,
        InvestigationRefundVerificationService $service,
        InvestigationCancellationAccountingService $accounting
    ): RedirectResponse {
        $this->ensureRefundFeatureEnabled();

        $data = $request->validate([
            'verification_notes' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'evidence_checked' => [
                'required',
                'accepted',
            ],
        ]);

        $service->verify(
            $check,
            $request->user(),
            $data['verification_notes'],
            $accounting
        );

        return back()->with(
            'success',
            'Refund settlement verified and accounting recorded. ' .
            'Please reconcile the cash/bank register.'
        );
    }

    /**
     * Allow refund testing when explicitly enabled.
     *
     * Does not depend on APP_ENV=local.
     * Authentication and authorization remain mandatory.
     */
    private function ensureRefundFeatureEnabled(): void
    {
        abort_unless(
            config(
                'investigation_cancellation.enable_verified_refunds',
                false
            ) === true,
            404
        );
    }
}
