<?php

namespace App\Http\Controllers;

use App\Models\InvestigationCancellationRequest;
use App\Models\Payment;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Strictly development-only financial execution of a single disposable fixture. */
final class InvestigationCancellationExecuteController extends Controller
{
    public function execute(
        Request $httpRequest,
        InvestigationCancellationRequest $cancellation,
        InvestigationCancellationAccountingService $accounting
    ): RedirectResponse {
        abort_unless(
            app()->environment('local')
            && DB::getDefaultConnection() === 'pgsql'
            && (DB::selectOne('select current_database() as name')->name ?? '') === 'laravel'
            && config('investigation_cancellation.allow_dummy_execution') === true,
            404
        );

        $actor = $httpRequest->user();
        abort_unless($actor && (int) $actor->id === 2 && $actor->hasRole('admin') &&
            $actor->hasPermission('billing.investigation-refund.process'), 403);

        $httpRequest->validate([
            'confirmation' => ['required', 'in:EXECUTE TEST CANCELLATION 2'],
        ]);

        // A hard-coded scope makes this an acceptance-test route, NOT a general
        // financial feature. This is intentionally not configurable by request.
        if ((int) $cancellation->id !== 2) {
            abort(404);
        }
        $cancellation->load('invoice');
        if (
            (int) $cancellation->invoice_id !== 29 ||
            (int) $cancellation->requested_by !== 1 ||
            (int) $cancellation->reviewed_by !== 2 ||
            (string) $cancellation->approved_amount !== '500.00' ||
            $cancellation->status !== 'approved' ||
            $cancellation->executed_at !== null ||
            $cancellation->invoice?->invoice_no !== 'INV-20261025-000002' ||
            ! str_contains(strtolower((string) $cancellation->reason), 'test') ||
            (string) $cancellation->invoice?->total_amount !== '1800.00' ||
            Payment::where('invoice_id', 29)->count() !== 1 ||
            (string) Payment::where('invoice_id', 29)->value('amount') !== '1800.00' ||
            Payment::where('invoice_id', 29)->value('payment_mode') !== 'cash'
        ) {
            throw ValidationException::withMessages([
                'cancellation' => 'The approved dummy fixture has changed. Execution blocked.',
            ]);
        }

        // The accounting service is responsible for transaction row locks,
        // clinical rechecks, GL mapping, Tally holds and duplicate prevention.
        // This HTTP path does not independently move funds or mark refunds paid.
        $journal = $accounting->reverseRevenue($cancellation, $actor);

        return redirect()
            ->route('billing.investigation-cancellations.queue', ['status' => 'executed'])
            ->with('success', 'Dummy cancellation reversal posted: '.$journal->journal_no.'. No refund issued.');
    }
}
