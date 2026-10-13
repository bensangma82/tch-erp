<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Services\Tally\TallyJournalExportService;
use App\Services\Tally\TallyJournalXmlBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TallyJournalExportController extends Controller
{
    /**
     * Display posted journals and Tally export status.
     *
     * Read-only operation.
     */
    public function index(): View
    {
        $journals = JournalEntry::query()
            ->where('status', 'posted')
            ->orderByDesc('journal_date')
            ->orderByDesc('id')
            ->paginate(25);

        $journalIds = $journals
            ->getCollection()
            ->pluck('id');

        $exports = TallyExport::query()
            ->whereIn('journal_entry_id', $journalIds)
            ->get()
            ->keyBy('journal_entry_id');

        return view('finance.tally.journals.index', [
            'journals' => $journals,
            'exports' => $exports,
            'tallyCompany' => config('tally.company'),
        ]);
    }

    /**
     * Validate a journal and generate XML in memory.
     *
     * No database writes or Tally HTTP requests.
     */
    public function preview(
        JournalEntry $journalEntry,
        TallyJournalExportService $service,
        TallyJournalXmlBuilder $builder
    ): JsonResponse {
        $eligibility = $service->checkEligibility(
            $journalEntry
        );

        if (! $eligibility['eligible']) {
            return response()->json([
                'eligible' => false,
                'journal' => $journalEntry->journal_no,
                'errors' => $eligibility['errors'],
            ]);
        }

        try {
            $xml = $builder->build($journalEntry);
        } catch (\Throwable $exception) {
            return response()->json([
                'eligible' => false,
                'journal' => $journalEntry->journal_no,
                'errors' => [$exception->getMessage()],
            ]);
        }

        return response()->json([
            'eligible' => true,
            'journal' => $journalEntry->journal_no,
            'tally_company' => config('tally.company'),
            'xml_generated' => true,
            'xml_bytes' => strlen($xml),
        ]);
    }

    /**
     * Journal transmission safety guard.
     *
     * Sending remains unavailable until cross-workflow
     * duplicate protection and reconciliation are complete.
     */
    public function send(
        JournalEntry $journalEntry
    ): RedirectResponse {
        if (! filter_var(
            config('tally.journal_export_enabled', false),
            FILTER_VALIDATE_BOOLEAN
        )) {
            return back()->with(
                'error',
                'Automated Tally Journal Export is currently disabled.'
            );
        }

        return back()->with(
            'error',
            'Journal transmission is awaiting final implementation and approval.'
        );
    }
}
