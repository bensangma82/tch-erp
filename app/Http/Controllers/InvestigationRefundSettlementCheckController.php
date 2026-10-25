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
 * Three-stage investigation refund workflow.
 *
 * 1. Initiate a refund request.
 * 2. Record actual cashier disbursement.
 * 3. Independently verify and post accounting.
 *
 * This controller never transfers money.
 */
final class InvestigationRefundSettlementCheckController extends Controller
{
    /**
     * Display outstanding refund requests and settlements.
     */
    public function index(Request $request): View
    {
        $this->ensureRefundFeatureEnabled();

        $user = $request->user();

        abort_unless(
            $user && (
                $user->permissions()
                    ->where(
                        'permissions.name',
                        'billing.investigation-refund.process'
                    )
                    ->exists()
                ||
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
            ->whereIn('status', [
                'initiated',
                'disbursed',
                'submitted',
            ])
            ->orderBy('id')
            ->paginate(20);

        return view(
            'billing.investigation-cancellations.refund-verification',
            compact('pending')
        );
    }

    /**
     * Stage 1: Initiate a refund request.
     *
     * Reuses the existing submit route for compatibility.
     * No actual payment or accounting is recorded.
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
        ]);

        $payment = Payment::findOrFail(
            $data['payment_id']
        );

        $service->initiate(
            $cancellation,
            $payment,
            $request->user(),
            $data
        );

        return back()->with(
            'success',
            'Refund request initiated successfully. ' .
            'No payment or accounting journal has been recorded. ' .
            'Cashier disbursement is required.'
        );
    }

    /**
     * Stage 2: Record actual refund disbursement.
     *
     * This records evidence of an external cash/bank settlement.
     * No accounting journal is posted.
     */
    public function disburse(
        Request $request,
        InvestigationRefundSettlementCheck $check,
        InvestigationRefundVerificationService $service
    ): RedirectResponse {
        $this->ensureRefundFeatureEnabled();

        $data = $request->validate([
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

        $service->disburse(
            $check,
            $request->user(),
            $data
        );

        return back()->with(
            'success',
            'Refund disbursement recorded. ' .
            'Independent verification is now required. ' .
            'No accounting journal has been posted.'
        );
    }

    /**
     * Stage 3: Independently verify a completed disbursement.
     *
     * The service enforces maker-checker controls,
     * duplicate prevention and journal posting.
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
            'Refund independently verified and accounting journal posted. ' .
            'Please reconcile the cash/bank register.'
        );
    }

    /**
     * Refund workflow feature flag.
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
