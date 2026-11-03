<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\MhisLegacySettlement;
use App\Models\MhisOpeningReceivable;
use App\Support\MhisLegacySettlementAmounts as Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MhisLegacySettlementEntryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('finance.mhis-legacy-settlement.view'), 403);

        $openings = MhisOpeningReceivable::query()
            ->where('status', 'approved')
            ->orderByDesc('opening_date')
            ->get();

        $settlements = MhisLegacySettlement::query()
            ->latest('id')
            ->paginate(25);

        return view('finance.mhis-legacy-settlements.index', compact('openings', 'settlements'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('finance.mhis-legacy-settlement.create'), 403);

        $data = $request->validate([
            'mhis_opening_receivable_id' => ['required', 'integer', 'exists:mhis_opening_receivables,id'],
            'settlement_date' => ['required', 'date'],
            'settlement_reference' => ['required', 'string', 'max:255'],
            'historical_claim_reference' => ['nullable', 'string', 'max:255'],
            'net_amount' => ['required', 'regex:/\A(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,2})?\z/D'],
            'tds_amount' => ['required', 'regex:/\A(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,2})?\z/D'],
            'approved_deduction' => ['required', 'regex:/\A(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,2})?\z/D'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $net = Money::paise($data['net_amount']);
        $tds = Money::paise($data['tds_amount']);
        $deduction = Money::paise($data['approved_deduction']);
        $gross = $net + $tds + $deduction;
        if ($gross <= 0) {
            throw ValidationException::withMessages(['net_amount' => 'Gross settlement must exceed zero.']);
        }
        $grossString = Money::rupees($gross);

        DB::transaction(function () use ($data, $request, $net, $tds, $deduction, $grossString) {
            $opening = MhisOpeningReceivable::query()->whereKey($data['mhis_opening_receivable_id'])
                ->lockForUpdate()->firstOrFail();

            if ($opening->status !== 'approved') {
                throw ValidationException::withMessages(['mhis_opening_receivable_id' => 'Opening balance must be approved.']);
            }
            if ($data['settlement_date'] < $opening->opening_date->toDateString()) {
                throw ValidationException::withMessages(['settlement_date' => 'Settlement predates the opening balance.']);
            }
            $claimRef = trim($data['historical_claim_reference'] ?? '');
            $reference = trim($data['settlement_reference']);
            if ($reference === '') {
                throw ValidationException::withMessages(['settlement_reference' => 'Reference cannot be blank.']);
            }
            $duplicate = MhisLegacySettlement::query()
                ->where('mhis_opening_receivable_id', $opening->id)
                ->where('settlement_reference', $reference)
                ->where('historical_claim_reference', $claimRef !== '' ? $claimRef : null)
                ->where('status', '!=', 'cancelled')
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['settlement_reference' => 'This settlement/claim reference is already recorded.']);
            }

            MhisLegacySettlement::create([
                'mhis_opening_receivable_id' => $opening->id,
                'settlement_date' => $data['settlement_date'],
                'settlement_reference' => $reference,
                'historical_claim_reference' => $claimRef !== '' ? $claimRef : null,
                'net_amount' => Money::rupees($net),
                'tds_amount' => Money::rupees($tds),
                'approved_deduction' => Money::rupees($deduction),
                'gross_settled_amount' => $grossString,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('finance.mhis-legacy-settlements.index')
            ->with('success', 'Legacy MHIS settlement saved as draft. No GL journal posted.');
    }
}
