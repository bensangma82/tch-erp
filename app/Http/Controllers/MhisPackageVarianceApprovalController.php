<?php
namespace App\Http\Controllers;

use App\Models\IpBillingMhisClaim;
use App\Models\MhisPackageVarianceApproval;
use App\Services\Accounting\MhisPackageVarianceApprovalService;
use App\Services\Accounting\MhisPackageVarianceReviewException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class MhisPackageVarianceApprovalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('finance.mhis-adjustment.post')
            || $request->user()->hasPermission('finance.mhis-adjustment.approve'), 403);
        $request->validate(['claim_id' => 'nullable|integer|min:1', 'q' => 'nullable|string|max:100']);
        $q = trim((string) $request->query('q', ''));
        $claims = IpBillingMhisClaim::with('billingAccount', 'patient')
            ->where('status', 'approved')->whereNotNull('approval_date')
            ->whereHas('billingAccount', function ($query) {
                $query->where('mhis_category', 'free')->whereIn('status', ['open', 'finalized'])
                    ->whereColumn('ip_billing_accounts.net_amount', '!=', 'ip_billing_mhis_claims.approved_amount');
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('patient', function ($patients) use ($q) {
                    $patients->where('uhid', 'ilike', '%'.$q.'%')->orWhere('first_name', 'ilike', '%'.$q.'%')->orWhere('last_name', 'ilike', '%'.$q.'%');
                });
            })->latest('id')->paginate(20, ['*'], 'claims_page')->withQueryString();
        $selected = $request->filled('claim_id') ? IpBillingMhisClaim::with('billingAccount', 'patient')->findOrFail($request->integer('claim_id')) : null;
        $snapshot = null; $problem = null; $review = null; $events = collect();
        if ($selected) {
            try {
                if (! $selected->billingAccount) throw new MhisPackageVarianceReviewException('The billing account is missing.');
                $snapshot = MhisPackageVarianceApprovalService::snapshot($selected, $selected->billingAccount);
            }
            catch (MhisPackageVarianceReviewException $e) { $problem = $e->getMessage(); }
            $review = MhisPackageVarianceApproval::where('ip_billing_mhis_claim_id', $selected->id)->first();
            if ($review) $events = \Illuminate\Support\Facades\DB::table('mhis_variance_review_events')->where('approval_id', $review->id)->orderBy('id')->get();
        }
        $items = MhisPackageVarianceApproval::with('claim.patient')->latest()->paginate(30, ['*'], 'reviews_page')->withQueryString();
        return view('finance.mhis-package-variance-approvals', compact('claims', 'selected', 'snapshot', 'problem', 'review', 'events', 'items', 'q'));
    }

    public function store(Request $request, MhisPackageVarianceApprovalService $service)
    {
        abort_unless($request->user()->hasPermission('finance.mhis-adjustment.post'), 403);
        $data = $request->validate([
            'claim_id' => 'required|integer|exists:ip_billing_mhis_claims,id',
            'supporting_reference' => 'nullable|string|max:255',
            'reason_choice' => 'nullable|in:recorded_approval,package_tariff,approved_package,other',
            'notes' => 'nullable|string|max:2000|required_if:reason_choice,other',
            'contractual_nonrecoverable' => 'sometimes|boolean',
            'expected_bill' => 'required|numeric|min:0', 'expected_approved' => 'required|numeric|min:0',
        ]);
        if (in_array(trim((string) ($data['supporting_reference'] ?? '')), ['Actual MHIS approval/package reference', 'Actual approval document/reference'], true)) {
            throw ValidationException::withMessages(['supporting_reference' => 'Enter the real approval or document reference, not the example text.']);
        }
        try { $service->prepare(IpBillingMhisClaim::findOrFail($data['claim_id']), $data + ['simple_review' => true]); }
        catch (MhisPackageVarianceReviewException $e) { throw ValidationException::withMessages(['variance' => $e->getMessage()]); }
        return redirect()->route('finance.mhis-package-variance.index', ['claim_id' => $data['claim_id']])
            ->with('status', 'Submitted. An independent Finance reviewer must approve it before finalization.');
    }

    public function approve(Request $request, MhisPackageVarianceApproval $approval, MhisPackageVarianceApprovalService $service)
    {
        abort_unless($request->user()->hasPermission('finance.mhis-adjustment.approve'), 403);
        try {
            if ($request->input('decision') === 'return') {
                $data = $request->validate(['return_notes' => 'required|string|max:2000']);
                $service->returnForCorrection($approval, $data['return_notes']);
                $message = 'Returned to the original submitter for correction.';
            } else {
                $service->approve($approval);
                $message = 'Finance approval recorded. Return to the patient bill and finalize.';
            }
        } catch (MhisPackageVarianceReviewException $e) { throw ValidationException::withMessages(['variance' => $e->getMessage()]); }
        return redirect()->route('finance.mhis-package-variance.index', ['claim_id' => $approval->ip_billing_mhis_claim_id])->with('status', $message);
    }
}
