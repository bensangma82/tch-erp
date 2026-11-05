<?php

namespace App\Http\Controllers;

use App\Models\IpBillingMhisClaim;
use App\Models\MhisPackageVarianceApproval;
use App\Services\Accounting\MhisPackageVarianceApprovalService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MhisPackageVarianceApprovalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('finance.mhis-adjustment.post')
            || $request->user()->hasPermission('finance.mhis-adjustment.approve'), 403);
        $items = MhisPackageVarianceApproval::query()->latest()->paginate(30);
        return view('finance.mhis-package-variance-approvals', compact('items'));
    }

    public function store(Request $request, MhisPackageVarianceApprovalService $service)
    {
        abort_unless($request->user()->hasPermission('finance.mhis-adjustment.post'), 403);
        $data = $request->validate([
            'claim_id' => 'required|integer|exists:ip_billing_mhis_claims,id',
            'supporting_reference' => 'required|string|max:255',
            'reason' => 'required|string|max:5000',
            'supporting_evidence' => 'required|string|max:5000',
            'contractual_nonrecoverable' => 'sometimes|boolean',
        ]);
        $service->prepare(IpBillingMhisClaim::findOrFail($data['claim_id']), $data);
        return redirect()->route('finance.mhis-package-variance.index')
            ->with('status', 'Variance submitted for independent Finance approval.');
    }

    public function approve(MhisPackageVarianceApproval $approval, MhisPackageVarianceApprovalService $service)
    {
        abort_unless(auth()->user()->hasPermission('finance.mhis-adjustment.approve'), 403);
        $service->approve($approval);
        return redirect()->route('finance.mhis-package-variance.index')
            ->with('status', 'Claim-specific Finance variance approved.');
    }
}
