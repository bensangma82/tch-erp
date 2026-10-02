<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceVoucher;
use App\Services\Tally\TallyExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TallyExportController extends Controller
{
    /**
     * Display posted Finance vouchers and their Tally export readiness.
     */
    public function index(
        Request $request,
        TallyExportService $tallyExportService
    ): View {
        $query = FinanceVoucher::query()
            ->where('status', 'posted')
            ->with([
                'financeHead.tallyMapping',
                'financeAccount.tallyMapping',
                'destinationAccount.tallyMapping',
                'tallyExport',
            ]);

        if ($request->filled('voucher_type')) {
            $voucherType = $request
                ->string('voucher_type')
                ->toString();

            if (in_array(
                $voucherType,
                ['receipt', 'payment', 'transfer'],
                true
            )) {
                $query->where(
                    'voucher_type',
                    $voucherType
                );
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'voucher_date',
                '>=',
                $request->date('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'voucher_date',
                '<=',
                $request->date('date_to')
            );
        }

        $vouchers = $query
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $eligibility = [];

        foreach ($vouchers as $voucher) {
            $eligibility[$voucher->id] =
                $tallyExportService->checkEligibility(
                    $voucher
                );
        }

        return view(
            'finance.tally.exports.index',
            [
                'vouchers' => $vouchers,
                'eligibility' => $eligibility,
            ]
        );
    }
}
