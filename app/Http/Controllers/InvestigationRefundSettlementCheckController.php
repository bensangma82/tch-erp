<?php

namespace App\Http\Controllers;

use App\Models\InvestigationCancellationRequest;
use App\Models\InvestigationRefundSettlementCheck;
use App\Models\Payment;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use App\Services\Investigation\InvestigationRefundVerificationService;
use App\Services\Investigation\InvestigationRefundSimpleWorkflowService;
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

        $filters = $request->validate([
            'stage' => ['nullable', 'in:all,initiated,disbursed,submitted'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $stage = $filters['stage'] ?? 'all';
        $q = trim($filters['q'] ?? '');
        $canProcess = $user->permissions()->where('permissions.name', 'billing.investigation-refund.process')->exists();
        $hasVerifyPermission = $user->permissions()->where('permissions.name', 'billing.investigation-refund.verify')->exists();
        $searchInvoice = function ($query) use ($q) {
            $query->where('invoice_no', 'ilike', '%'.$q.'%')->orWhereHas('patient', function ($patient) use ($q) {
                $patient->where('uhid', 'ilike', '%'.$q.'%')
                    ->orWhere('first_name', 'ilike', '%'.$q.'%')->orWhere('last_name', 'ilike', '%'.$q.'%');
            });
        };
        $pending = InvestigationRefundSettlementCheck::query()
            ->with(['cancellation.invoice.patient', 'payment', 'submittedBy', 'initiatedBy', 'disbursedBy'])
            ->whereIn('status', ['initiated', 'disbursed', 'submitted'])
            ->when($stage !== 'all', fn ($query) => $query->where('status', $stage))
            ->when($q !== '', fn ($query) => $query->whereHas('cancellation.invoice', $searchInvoice))
            ->orderBy('id')->paginate(20)->withQueryString();

        $eligible = null;
        $previews = [];
        if ($canProcess) {
            $eligible = InvestigationCancellationRequest::query()
                ->with('invoice.patient')->where('status', 'executed')->whereNotNull('executed_at')
                ->whereHas('invoice', fn ($query) => $query->where('invoice_type', 'investigation'))
                ->whereNotIn('id', InvestigationRefundSettlementCheck::query()
                    ->select('cancellation_id')->whereIn('status', ['initiated', 'disbursed', 'submitted', 'verified', 'completed']))
                ->whereNotIn('id', \App\Models\InvestigationCancellationRefund::query()->select('investigation_cancellation_request_id'))
                ->when($q !== '', fn ($query) => $query->whereHas('invoice', $searchInvoice))
                ->latest('id')->paginate(10, ['*'], 'new_page')->withQueryString();
            $simple = app(InvestigationRefundSimpleWorkflowService::class);
            foreach ($eligible as $cancellation) $previews[$cancellation->id] = $simple->preview($cancellation);
        }
        return view('billing.investigation-cancellations.refund-verification',
            compact('pending', 'eligible', 'previews', 'stage', 'q', 'canProcess', 'hasVerifyPermission'));
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

        if ($request->boolean('simple_flow')) {
            $data = $request->validate([
                'payment_id' => ['required', 'integer', 'exists:payments,id'],
                'expected_amount' => ['required', 'regex:/^\d{1,10}\.\d{2}$/'],
            ]);
            app(InvestigationRefundSimpleWorkflowService::class)->requestRefund(
                $cancellation, Payment::findOrFail($data['payment_id']), $request->user(), $data['expected_amount']);
            return redirect()->route('billing.investigation-refunds.verification')
                ->with('success', 'Refund requested. Next: pay the patient and record payment. No accounting posted.');
        }

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

        if ($request->boolean('simple_flow')) {
            $data = $request->validate([
                'actually_paid' => ['required', 'accepted'],
                'settlement_reference' => ['nullable', 'string', 'max:100'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);
            app(InvestigationRefundSimpleWorkflowService::class)->recordPayment($check, $request->user(), $data);
            return redirect()->route('billing.investigation-refunds.verification')
                ->with('success', 'Payment recorded. A different authorized user must verify it. No accounting posted yet.');
        }

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

        if ($request->boolean('simple_flow')) {
            $data = $request->validate([
                'evidence_checked' => ['required', 'accepted'],
                'proof_kind' => ['required', 'in:cash_voucher,cash_register,bank_statement,upi_confirmation,card_confirmation'],
                'notes' => ['nullable', 'string', 'max:500'],
                'legacy_payment_reference' => ['nullable', 'string', 'max:100'],
                'legacy_payment_date' => ['nullable', 'string', 'max:10'],
            ]);
            app(InvestigationRefundSimpleWorkflowService::class)->verifyRefund($check, $request->user(), $data);
            return redirect()->route('billing.investigation-refunds.verification')
                ->with('success', 'Refund verified and completed. The refund accounting journal is posted.');
        }

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
