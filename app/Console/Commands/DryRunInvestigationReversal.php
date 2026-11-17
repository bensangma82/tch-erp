<?php

namespace App\Console\Commands;

use App\Models\InvestigationCancellationRequest;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Local development-only investigation cancellation reversal simulation.
 *
 * All accounting changes are rolled back.
 * No refund is processed.
 */
class DryRunInvestigationReversal extends Command
{
    protected $signature = 'investigation:reversal-dry-run {--request=2} {--actor=1}';

    protected $description = 'Simulate investigation revenue reversal and always roll back';

    public function handle(
        InvestigationCancellationAccountingService $service
    ): int {
        /*
        |--------------------------------------------------------------------------
        | 1. Verify local development environment
        |--------------------------------------------------------------------------
        */

        $db = DB::selectOne(
            'select current_database() as name'
        )->name ?? '';

        if (
            ! app()->environment('local') ||
            DB::getDefaultConnection() !== 'pgsql' ||
            $db !== 'laravel'
        ) {
            $this->error(
                'REFUSED: Only the local PostgreSQL laravel development database is allowed.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Restrict to the known test case
        |--------------------------------------------------------------------------
        */

        if (
            (string) $this->option('request') !== '2' ||
            (string) $this->option('actor') !== '1'
        ) {
            $this->error(
                'REFUSED: Only cancellation request #2 and test actor #1 are allowed.'
            );

            return self::FAILURE;
        }

        $request = InvestigationCancellationRequest::find(2);
        $actor = User::find(1);

        if (
            ! $request ||
            ! $actor ||
            $request->status !== 'approved' ||
            $request->executed_at !== null ||
            (int) $request->invoice_id !== 29 ||
            (int) $request->requested_by !== 1 ||
            (int) $request->reviewed_by !== 2 ||
            (string) $request->approved_amount !== '500.00' ||
            (string) ($request->invoice?->invoice_no)
                !== 'INV-20261025-000002' ||
            (string) ($request->invoice?->total_amount)
                !== '1800.00' ||
            Payment::where('invoice_id', 29)->count() !== 1 ||
            (string) Payment::where('invoice_id', 29)->value('amount')
                !== '1800.00' ||
            (string) Payment::where('invoice_id', 29)->value('payment_mode')
                !== 'cash' ||
            ! str_contains(
                strtolower((string) $request->reason),
                'test'
            )
        ) {
            $this->error(
                'REFUSED: Expected disposable test fixture has changed.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Verify no existing reversal
        |--------------------------------------------------------------------------
        */

        $journalQuery = fn () => JournalEntry::query()
            ->where('source_type', 'investigation_cancellation_request')
            ->where('source_id', 2)
            ->where('source_event', 'revenue_reversed');

        if ($journalQuery()->exists()) {
            $this->error(
                'REFUSED: Reversal journal already exists.'
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Capture original accounting state
        |--------------------------------------------------------------------------
        */

        $beforeCount = JournalEntry::count();

        $beforePayments = Payment::where('invoice_id', 29)
            ->orderBy('id')
            ->get(['id', 'amount', 'receipt_no'])
            ->toArray();

        $success = false;

        /*
        |--------------------------------------------------------------------------
        | 5. Begin rollback-only transaction
        |--------------------------------------------------------------------------
        */

        $guard = Auth::guard();
        $previousUser = $guard->user();

        DB::beginTransaction();

        try {
            /*
             * JournalPostingService uses auth()->id().
             * Authenticate the authorized test actor.
             */
            $guard->setUser($actor);

            $this->info(
                'Authenticated test actor: '.$actor->id
            );

            /*
             * Execute the REAL accounting service
             * inside the rollback-only transaction.
             */
            $journal = $service->reverseRevenue(
                $request,
                $actor
            );

            $journal->load('lines.generalLedgerAccount');

            /*
            |--------------------------------------------------------------------------
            | 6. Verify journal lines
            |--------------------------------------------------------------------------
            */

            $debit = 0;
            $credit = 0;
            $accountEntries = [];

            foreach ($journal->lines as $line) {
                $code = (string) (
                    $line->generalLedgerAccount?->account_code
                    ?? 'UNKNOWN'
                );

                $lineDebit = (int) round(
                    (float) $line->debit * 100
                );

                $lineCredit = (int) round(
                    (float) $line->credit * 100
                );

                $accountEntries[$code] = [
                    'debit' => $lineDebit,
                    'credit' => $lineCredit,
                ];

                $this->line(sprintf(
                    '%s | DR %s | CR %s',
                    $code,
                    $line->debit,
                    $line->credit
                ));

                $debit += $lineDebit;
                $credit += $lineCredit;
            }

            /*
            |--------------------------------------------------------------------------
            | 7. Validate actual accounting
            |--------------------------------------------------------------------------
            */

            if (
                $journal->status !== 'posted' ||
                $journal->lines->count() !== 2 ||
                $debit !== 50000 ||
                $credit !== 50000 ||
                (int) $journal->posted_by !== $actor->id ||
                ! $journal->posted_at ||
                ($accountEntries['4201'] ?? null) !== [
                    'debit' => 50000,
                    'credit' => 0,
                ] ||
                ($accountEntries['1200'] ?? null) !== [
                    'debit' => 0,
                    'credit' => 50000,
                ] ||
                $request->fresh()->status !== 'executed'
            ) {
                throw new RuntimeException(
                    'Simulation failed accounting assertions.'
                );
            }

            /*
             * Verify duplicate execution is rejected.
             * Use a savepoint so a PostgreSQL error cannot
             * invalidate the enclosing rollback transaction.
             */
            DB::beginTransaction();

            try {
                $duplicateRejected = false;

                try {
                    $service->reverseRevenue(
                        $request->fresh(),
                        $actor
                    );
                } catch (Throwable $duplicateException) {
                    $duplicateRejected = true;

                    $this->info(
                        'Duplicate reversal rejected: '
                        .$duplicateException->getMessage()
                    );
                }

                if (! $duplicateRejected) {
                    throw new RuntimeException(
                        'Duplicate reversal was incorrectly allowed.'
                    );
                }
            } finally {
                DB::rollBack();
            }

            $this->info(
                'SIMULATION PASSED: ₹500 laboratory revenue reversal balanced.'
            );

            $this->info(
                'Posted user and timestamp verified.'
            );

            $this->info(
                'Duplicate reversal protection verified.'
            );

            $success = true;

        } catch (Throwable $e) {
            $this->error(
                'SIMULATION FAILED: '.$e->getMessage()
            );

        } finally {
            /*
             * Always roll back the outer financial transaction.
             */
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            /*
             * Restore the authentication context.
             */
            if ($previousUser) {
                $guard->setUser($previousUser);
            } else {
                $guard->forgetUser();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Verify database is unchanged
        |--------------------------------------------------------------------------
        */

        $unchanged =
            $journalQuery()->count() === 0 &&
            JournalEntry::count() === $beforeCount &&
            $request->fresh()->status === 'approved' &&
            $request->fresh()->executed_at === null &&
            Payment::where('invoice_id', 29)
                ->orderBy('id')
                ->get(['id', 'amount', 'receipt_no'])
                ->toArray() === $beforePayments;

        if (! $unchanged) {
            $this->error(
                'POST-ROLLBACK CHECK FAILED: Inspect database before continuing.'
            );

            return self::FAILURE;
        }

        $this->info(
            'ROLLBACK VERIFIED: Request remains approved.'
        );

        $this->info(
            'No reversal journal retained.'
        );

        $this->info(
            'Original ₹1,800 receipt unchanged.'
        );

        return $success
            ? self::SUCCESS
            : self::FAILURE;
    }
}
