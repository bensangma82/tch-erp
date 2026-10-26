<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\TallyExport;
use App\Services\Tally\TallyJournalRecoveryResolutionService;
use App\Services\Tally\TallyJournalRecoveryService;
use App\Services\Tally\TallyJournalRecoveryTransmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;

class TallyJournalRecoveryWebController extends Controller
{
    private function guard(Request $request): int
    {
        $user = $request->user();
        if (!$user || !$user->hasPermission('tally.journal-recovery.authorize')) abort(403);
        if (!config('tally.journal_export_enabled', false) || !config('tally.journal_recovery_enabled', false)
            || !str_contains(strtolower((string) config('tally.company')), '-test')) abort(403);
        return (int) $user->id;
    }

    public function show(Request $request, TallyExport $tallyExport): View
    {
        $this->guard($request);
        abort_unless($tallyExport->journal_entry_id, 404);
        return view('finance.tally.journals.recovery', [
            'export' => $tallyExport->load('journalEntry', 'journalExportAttempts'),
            'company' => config('tally.company'),
        ]);
    }

    public function resolve(Request $request, TallyExport $tallyExport, TallyJournalRecoveryResolutionService $service): RedirectResponse
    {
        $userId = $this->guard($request);
        $data = $request->validate(['evidence' => 'required|string|min:35|max:2000', 'absence_confirmed' => 'accepted']);
        $service->resolve($tallyExport->id, $userId, $data['evidence']);
        return back()->with('success', 'Explicitly rejected attempt resolved and audited. No voucher sent.');
    }

    public function approve(Request $request, TallyExport $tallyExport, TallyJournalRecoveryService $service): RedirectResponse
    {
        $userId = $this->guard($request);
        $data = $request->validate([
            'reason' => 'required|string|min:15|max:1000',
            'evidence' => 'required|string|min:35|max:2000',
            'absence_confirmed' => 'accepted',
        ]);
        $attempt = $service->authorizeRecovery($tallyExport->id, $userId, $data['reason'], $data['evidence']);
        return back()->with('success', "Recovery attempt {$attempt->id} approved. No voucher sent.");
    }

    public function send(Request $request, TallyExport $tallyExport, TallyJournalRecoveryTransmissionService $service): RedirectResponse
    {
        $userId = $this->guard($request);
        $request->validate(['absence_confirmed' => 'accepted', 'send_once' => 'accepted']);
        $result = $service->send($tallyExport->id, $userId);
        return back()->with('success', 'Recovery send result: '.($result['status'] ?? 'unknown')
            .'. Do NOT send again without reconciliation.');
    }
}
