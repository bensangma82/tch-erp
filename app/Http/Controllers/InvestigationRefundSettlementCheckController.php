<?php
namespace App\Http\Controllers;
use App\Models\{InvestigationCancellationRequest,InvestigationRefundSettlementCheck,Invoice,Payment};
use App\Services\Investigation\{InvestigationCancellationAccountingService,InvestigationRefundVerificationService,InvestigationCancellationReconciliationService};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** No funds are transferred. Used only AFTER documentary evidence of external payout. */
final class InvestigationRefundSettlementCheckController extends Controller {
    public function index(Request $request, InvestigationCancellationReconciliationService $reconciliation): View {
        abort_unless($request->user()?->hasPermission('billing.investigation-refund.process') ||
            $request->user()?->permissions()->where('permissions.name','billing.investigation-refund.verify')->exists(),403);
        $pending = InvestigationRefundSettlementCheck::where('status','submitted')->orderBy('id')->paginate(20);
        return view('billing.investigation-cancellations.refund-verification',compact('pending'));
    }
    public function submit(Request $request, InvestigationCancellationRequest $cancellation, InvestigationRefundVerificationService $service): RedirectResponse {
        $this->localOnly();
        $data=$request->validate([
            'payment_id'=>['required','integer','exists:payments,id'],
            'amount'=>['required','regex:/^\d{1,10}\.\d{2}$/'],
            'settlement_method'=>['required','in:cash,upi,card'],
            'settlement_reference'=>['required','string','min:6','max:100'],
            'evidence_description'=>['required','string','min:15','max:2000'],
            'actually_paid'=>['required','accepted'],
        ]);
        $service->submit($cancellation,Payment::findOrFail($data['payment_id']),$request->user(),$data);
        return back()->with('success','Settlement evidence submitted; independent verification required. No journal posted.');
    }
    public function verify(Request $request, InvestigationRefundSettlementCheck $check, InvestigationRefundVerificationService $service, InvestigationCancellationAccountingService $accounting): RedirectResponse {
        $this->localOnly();
        $data=$request->validate(['verification_notes'=>['required','string','min:10','max:2000'],'evidence_checked'=>['required','accepted']]);
        $service->verify($check,$request->user(),$data['verification_notes'],$accounting);
        return back()->with('success','Refund settlement verified and accounting recorded. Reconcile cash/bank register.');
    }
    private function localOnly():void {
        // Development-only until clinical writer locking and full Tally acceptance tests pass.
        abort_unless(app()->environment('local') && config('investigation_cancellation.enable_verified_refunds',false) === true,404);
    }
}
