<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceVoucher;
use App\Models\TallyImportedVoucher;
use App\Services\Tally\TallyReconciliationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TallyReconciliationController extends Controller
{
    public function __construct(
        private readonly TallyReconciliationService $reconciliationService
    ) {}

    public function index(): View
    {
        /*
         * Tally vouchers successfully linked to ERP Finance vouchers.
         */
        $matched = TallyImportedVoucher::query()
            ->with([
                'financeVoucher.financeHead',
                'financeVoucher.financeAccount',
                'financeVoucher.destinationAccount',
                'entries',
            ])
            ->where('reconciliation_status', 'matched')
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get();

        /*
         * Tally vouchers for which no ERP Finance voucher is currently
         * linked.
         */
        $tallyOnly = TallyImportedVoucher::query()
            ->with('entries')
            ->whereNull('finance_voucher_id')
            ->where('reconciliation_status', 'tally_only')
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get();

        /*
         * Persisted accounting differences.
         *
         * Difference detection will be expanded later to compare the
         * accounting content of an identified ERP/Tally voucher pair.
         */
        $differences = TallyImportedVoucher::query()
            ->with([
                'financeVoucher.financeHead',
                'financeVoucher.financeAccount',
                'financeVoucher.destinationAccount',
                'entries',
            ])
            ->where('reconciliation_status', 'difference')
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get();

        /*
         * Start with every posted ERP Finance voucher that does not have
         * an imported Tally voucher linked to it.
         */
        $unmatchedErp = FinanceVoucher::query()
            ->with([
                'financeHead',
                'financeAccount',
                'destinationAccount',
                'tallyExport',
            ])
            ->where('status', 'posted')
            ->whereDoesntHave('tallyImportedVouchers')
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get();

        /*
         * Classify each unmatched ERP voucher using the same conservative
         * matching rules used by TallyReconciliationService.
         */
        $classified = $unmatchedErp->map(
            function (FinanceVoucher $financeVoucher): array {
                $diagnostic = $this
                    ->reconciliationService
                    ->unmatchedStatus($financeVoucher);

                return [
                    'voucher' => $financeVoucher,
                    'status' => $diagnostic['status'],
                    'candidate_count' => $diagnostic['candidate_count'],
                ];
            }
        );

        $ambiguous = $classified
            ->where('status', 'ambiguous')
            ->values();

        $unmapped = $classified
            ->where('status', 'unmapped')
            ->values();

        $erpOnly = $classified
            ->where('status', 'erp_only')
            ->values();

        $summary = [
            'matched' => $matched->count(),
            'tally_only' => $tallyOnly->count(),
            'erp_only' => $erpOnly->count(),
            'ambiguous' => $ambiguous->count(),
            'unmapped' => $unmapped->count(),
            'difference' => $differences->count(),
        ];

        return view(
            'finance.tally.reconciliation',
            compact(
                'summary',
                'matched',
                'tallyOnly',
                'erpOnly',
                'ambiguous',
                'unmapped',
                'differences'
            )
        );
    }

    /**
     * Show a read-only reconciliation review for one ERP Finance voucher.
     */
    public function showErp(FinanceVoucher $financeVoucher): View
    {
        $financeVoucher->load([
            'financeHead',
            'financeAccount',
            'destinationAccount',
            'tallyExport',
            'tallyImportedVouchers.entries',
        ]);

        $diagnostic = $this
            ->reconciliationService
            ->unmatchedStatus($financeVoucher);

        $candidates = $this
            ->reconciliationService
            ->candidatesFor($financeVoucher);

        $candidates->load('entries');

        return view(
            'finance.tally.reconciliation-erp',
            compact(
                'financeVoucher',
                'diagnostic',
                'candidates'
            )
        );
    }

    /**
     * Manually reconcile a posted ERP Finance voucher with a selected
     * imported Tally voucher.
     */
    public function manualMatch(
        Request $request,
        FinanceVoucher $financeVoucher,
        TallyImportedVoucher $tallyVoucher
    ): RedirectResponse {
        $validated = $request->validate([
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $userId = $request->user()?->id;

        if ($userId === null) {
            abort(403);
        }

        try {
            $this->reconciliationService->manualMatch(
                $financeVoucher,
                $tallyVoucher,
                $userId,
                $validated['notes'] ?? null
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route(
                    'finance.tally.reconciliation.erp',
                    $financeVoucher
                )
                ->with(
                    'error',
                    $exception->getMessage()
                );
        }

        return redirect()
            ->route('finance.tally.reconciliation')
            ->with(
                'success',
                'ERP and Tally vouchers were manually reconciled successfully.'
            );
    }
}
