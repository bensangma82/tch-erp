<?php

namespace App\Http\Controllers;

use App\Models\InvestigationCancellationRequest;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class InvestigationCancellationExecuteController extends Controller
{
    /**
     * Execute an approved investigation cancellation.
     *
     * The accounting service handles clinical revalidation,
     * database locks, duplicate protection, Tally checks
     * and balanced journal posting.
     */
    public function execute(
        Request $request,
        InvestigationCancellationRequest $cancellation,
        InvestigationCancellationAccountingService $accounting
    ): RedirectResponse {
        $actor = $request->user();

        // Explicit execution permission required.
        abort_unless(
            $actor &&
            $actor->permissions()
                ->where(
                    'permissions.name',
                    'billing.investigation-cancellation.execute'
                )
                ->exists(),
            403
        );

        $request->validate([
            'confirmation' => [
                'required',
                'in:EXECUTE REVERSAL',
            ],
        ]);

        // Ensure that the cancellation was approved.
        abort_unless(
            $cancellation->status === 'approved' &&
            $cancellation->executed_at === null,
            422
        );

        // Independent accounting service handles all final safeguards.
        $journal = $accounting->reverseRevenue(
            $cancellation,
            $actor
        );

        return redirect()
            ->route(
                'billing.investigation-cancellations.queue',
                ['status' => 'executed']
            )
            ->with(
                'success',
                'Investigation cancellation executed successfully. '
                . 'Revenue reversal journal: '
                . $journal->journal_no
                . '. No refund has been issued.'
            );
    }
}
