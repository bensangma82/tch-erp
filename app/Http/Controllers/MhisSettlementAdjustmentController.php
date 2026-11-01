<?php

namespace App\Http\Controllers;

use App\Models\MhisSettlementAdjustment;
use App\Services\Accounting\MhisSettlementAdjustmentAccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MhisSettlementAdjustmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            auth()->user()?->hasPermission('finance.mhis-adjustment.submit'),
            403
        );

        $data = $request->validate([
            'ip_billing_mhis_claim_id' => ['required', 'integer', 'exists:ip_billing_mhis_claims,id'],
            'adjustment_reference' => ['required', 'string', 'max:100', 'unique:mhis_settlement_adjustments,adjustment_reference'],
            'adjustment_date' => ['required', 'date'],
            'tds_amount' => ['required', 'numeric', 'in:0,0.0,0.00'],
            'accepted_deduction_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'settlement_reference' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string'],
            'supporting_evidence' => ['required', 'string'],
        ]);

        if (
            round((float) $data['tds_amount'] * 100)
            + round((float) $data['accepted_deduction_amount'] * 100)
            <= 0
        ) {
            throw ValidationException::withMessages([
                'tds_amount' => 'The total adjustment must be positive.',
            ]);
        }

        DB::transaction(function () use ($data) {
            $claim = \App\Models\IpBillingMhisClaim::query()
                ->whereKey($data['ip_billing_mhis_claim_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $account = $claim->billingAccount()
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $account->status !== 'finalized'
                || ! $account->final_bill_no
                || $claim->status !== 'approved'
                || ! $claim->approval_date
            ) {
                throw ValidationException::withMessages([
                    'ip_billing_mhis_claim_id' =>
                        'A finalized bill and approved MHIS claim are required.',
                ]);
            }

            $recognized = \App\Models\JournalEntry::query()
                ->where('source_type', 'ip_billing_mhis_claim')
                ->where('source_id', (string) $claim->id)
                ->where('source_event', 'approved')
                ->where('status', 'posted')
                ->exists();

            if (! $recognized) {
                throw ValidationException::withMessages([
                    'ip_billing_mhis_claim_id' =>
                        'The MHIS recognition journal must be posted first.',
                ]);
            }

            $adjustment = MhisSettlementAdjustment::create([
                ...$data,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            app(MhisSettlementAdjustmentAccountingService::class)
                ->submit($adjustment);
        });

        return back()->with('success', 'MHIS settlement adjustment submitted.');
    }

    public function approve(
        MhisSettlementAdjustment $adjustment,
        MhisSettlementAdjustmentAccountingService $service
    ): RedirectResponse {
        $service->approve($adjustment);

        return back()->with('success', 'MHIS settlement adjustment approved.');
    }

    public function post(
        MhisSettlementAdjustment $adjustment,
        MhisSettlementAdjustmentAccountingService $service
    ): RedirectResponse {
        $service->post($adjustment);

        return back()->with('success', 'MHIS settlement adjustment posted.');
    }
}
