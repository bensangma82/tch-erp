<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\MhisLegacySettlement;
use App\Services\Accounting\MhisLegacySettlementValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MhisLegacySettlementController extends Controller
{
    public function verify(
        Request $request,
        MhisLegacySettlement $settlement,
        MhisLegacySettlementValidationService $validator
    ): RedirectResponse {
        $request->user()->hasPermission('finance.mhis-legacy-settlement.verify')
            || abort(403);

        $validator->validateForVerification(
            $settlement,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Historical MHIS settlement verified successfully. No accounting journal has been posted.'
        );
    }
}
