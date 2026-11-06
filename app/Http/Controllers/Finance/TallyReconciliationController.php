<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceVoucher;
use App\Models\JournalEntry;
use App\Models\TallyImportedVoucher;
use App\Services\Tally\TallyReconciliationService;
use App\Services\Tally\TallyJournalReconciliationService;
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
            ->whereNotNull('finance_voucher_id')
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
            ->whereNull('journal_entry_id')
            ->where('reconciliation_status', 'tally_only')
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get();

        /*
         * Persisted accounting differences.
         */
        $differences = TallyImportedVoucher::query()
            ->with([
                'financeVoucher.financeHead',
                'financeVoucher.financeAccount',
                'financeVoucher.destinationAccount',
                'entries',
            ])
            ->where('reconciliation_status', 'difference')
            ->whereNotNull('finance_voucher_id')
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

            /*
|--------------------------------------------------------------------------
| GL Journal Reconciliation
|--------------------------------------------------------------------------
| Read-only comparison of ERP journals against imported Tally vouchers.
|--------------------------------------------------------------------------
*/

$journalReconciliationService = app(
    TallyJournalReconciliationService::class
);

$journalReconciliations = JournalEntry::query()
    ->where('status', 'posted')
    ->limit(200)
    ->with([
        'lines.generalLedgerAccount',
        'tallyExport',
    ])
    ->orderByDesc('journal_date')
    ->orderByDesc('id')
    ->get()
    ->map(function (JournalEntry $journal) use (
        $journalReconciliationService
    ): array {
        return [
            'journal' => $journal,
            'reconciliation' => $journalReconciliationService
                ->reconcile($journal),
        ];
    });

$manualTallyCandidates = TallyImportedVoucher::query()
    ->where('tally_company', trim((string) config('tally.company')))
    ->whereNull('journal_entry_id')
    ->whereNull('finance_voucher_id')
    ->where('reconciliation_status', '!=', 'matched')
    ->whereIn('voucher_type', ['Sales', 'Receipt', 'Journal'])
    ->orderByDesc('voucher_date')->limit(100)
    ->get(['id', 'voucher_type', 'voucher_number', 'voucher_date', 'guid']);

$journalSummary = [
    'matched' => $journalReconciliations
        ->where('reconciliation.status', 'matched')
        ->count(),

    'difference' => $journalReconciliations
        ->where('reconciliation.status', 'difference')
        ->count(),

    'missing' => $journalReconciliations
        ->where('reconciliation.status', 'missing')
        ->count(),

    'ambiguous' => $journalReconciliations
        ->where('reconciliation.status', 'ambiguous')
        ->count(),
];

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
                'differences',
                'journalReconciliations',
                'journalSummary',
                'manualTallyCandidates'
            )
        );
    }

    /**
     * Show reconciliation review for one ERP Finance voucher.
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

        /*
         * If this ERP voucher already has a linked imported Tally voucher,
         * prefer that persisted reconciliation result instead of treating
         * the ERP voucher as unmatched.
         */
        $linkedTallyVoucher = $financeVoucher
            ->tallyImportedVouchers
            ->firstWhere('reconciliation_status', 'difference')
            ?? $financeVoucher
                ->tallyImportedVouchers
                ->firstWhere('reconciliation_status', 'matched');

        if ($linkedTallyVoucher !== null) {
            $diagnostic = [
                'status' => $linkedTallyVoucher->reconciliation_status,
                'reason' => $linkedTallyVoucher->reconciliation_notes,
                'candidate_count' => 0,
            ];

            $candidates = collect();
        } else {
            $diagnostic = $this
                ->reconciliationService
                ->unmatchedStatus($financeVoucher);

            $candidates = $this
                ->reconciliationService
                ->candidatesFor($financeVoucher);

            $candidates->load('entries');
        }

        return view(
            'finance.tally.reconciliation-erp',
            compact(
                'financeVoucher',
                'diagnostic',
                'candidates',
                'linkedTallyVoucher'
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
    /** Manually match an imported Tally voucher without sending any XML. */
    public function matchJournal(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('finance.reconciliation.manage'), 403);
        $validated = $request->validate([
            'tally_imported_voucher_id' => ['required', 'integer', 'exists:tally_imported_vouchers,id'],
            'notes' => ['required', 'string', 'min:15', 'max:1000'],
            'confirm_manual_entry' => ['accepted'],
        ]);
        try {
            app(\App\Services\Tally\TallyManualJournalMatchService::class)->match(
                (int) $journalEntry->id,
                (int) $validated['tally_imported_voucher_id'],
                (int) $request->user()->id,
                (string) $validated['notes']
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('finance.tally.reconciliation')
            ->with('success', 'Existing Tally voucher matched; ERP transmission blocked.');
    }
}
