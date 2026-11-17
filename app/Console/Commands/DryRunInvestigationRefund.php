<?php

namespace App\Console\Commands;

use App\Models\InvestigationCancellationRefund;
use App\Models\InvestigationCancellationRequest;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use App\Services\Investigation\InvestigationCancellationReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/** Test-only refund posting simulation. NEVER commits, transfers cash or confirms a real settlement. */
final class DryRunInvestigationRefund extends Command
{
    protected $signature = 'investigation:refund-dry-run';
    protected $description = 'Simulate cash refund accounting for disposable invoice #29, then roll back';

    public function handle(
        InvestigationCancellationAccountingService $accounting,
        InvestigationCancellationReconciliationService $reconciliation
    ): int {
        if (!app()->environment('local') || DB::getDefaultConnection() !== 'pgsql'
            || (DB::selectOne('select current_database() as db')->db ?? '') !== 'laravel') {
            $this->error('REFUSED: local PostgreSQL laravel development database required.');
            return self::FAILURE;
        }
        $actor = User::find(2);
        $request = InvestigationCancellationRequest::find(2);
        $payment = Payment::find(29);
        if (!$actor || !$request || !$payment || (int)$request->invoice_id !== 29
            || $request->status !== 'executed' || !$request->executed_at
            || (string)$request->approved_amount !== '500.00'
            || (int)$request->reviewed_by !== 2
            || (string)($request->invoice?->invoice_no) !== 'INV-20261025-000002'
            || (string)($request->invoice?->total_amount) !== '1800.00'
            || (int)$payment->invoice_id !== 29
            || (string)$payment->amount !== '1800.00'
            || strtolower((string)$payment->payment_mode) !== 'cash'
            || !JournalEntry::query()->where('journal_no','JRN-ICR-2')->where('status','posted')->exists()
            || InvestigationCancellationRefund::where('investigation_cancellation_request_id', 2)->exists()) {
            $this->error('REFUSED: hard-coded disposable development fixture has changed or refund already recorded.');
            return self::FAILURE;
        }
        $baseline = [
            'journal_count' => JournalEntry::count(),
            'refund_count' => InvestigationCancellationRefund::count(),
            'payment' => $payment->only(['id','invoice_id','amount','payment_mode','receipt_no']),
            'summary' => $reconciliation->summarize($request->invoice),
        ];
        if ($baseline['summary']['potential_refund'] !== 50000
            || $baseline['summary']['completed_refunds'] !== 0
            || $baseline['summary']['refund_hold']) {
            $this->error('REFUSED: existing reconciliation is not the expected ₹500 refundable test case.');
            return self::FAILURE;
        }
        $previous = Auth::guard()->user();
        $ok = false;
        DB::beginTransaction();
        try {
            Auth::guard()->setUser($actor);
            $journal = $accounting->recordCompletedRefund(
                $request, $payment, $actor, 'SIMULATION-NOT-PAID-2', '500.00'
            );
            $journal->load('lines.generalLedgerAccount');
            $entries = [];
            foreach ($journal->lines as $line) {
                $code = (string)$line->generalLedgerAccount?->account_code;
                $entries[$code] = [(int)round((float)$line->debit*100), (int)round((float)$line->credit*100)];
                $this->line($code.' DR '.$line->debit.' CR '.$line->credit);
            }
            $inside = $reconciliation->summarize($request->invoice);
            if ($journal->status !== 'posted' || (int)$journal->posted_by !== 2
                || !$journal->posted_at || count($entries) !== 2
                || ($entries['1200'] ?? null) !== [50000,0]
                || ($entries['1100'] ?? null) !== [0,50000]
                || $inside['completed_refunds'] !== 50000
                || $inside['potential_refund'] !== 0
                || $inside['net_paid'] !== 130000
                || $inside['revised_net_bill'] !== 130000) {
                throw new RuntimeException('Refund journal or reconciliation assertions failed.');
            }
            $this->info('SIMULATION PASSED: ₹500 cash refund journal balanced; revised net bill and net retained ₹1,300.');
            $ok = true;
        } catch (Throwable $e) {
            $this->error('SIMULATION FAILED: '.$e->getMessage());
        } finally {
            while (DB::transactionLevel() > 0) DB::rollBack();
            if ($previous) Auth::guard()->setUser($previous);
            else Auth::guard()->forgetUser();
        }
        $after = $reconciliation->summarize($request->fresh()->invoice);
        $unchanged = JournalEntry::count() === $baseline['journal_count']
            && InvestigationCancellationRefund::count() === $baseline['refund_count']
            && $payment->fresh()->only(['id','invoice_id','amount','payment_mode','receipt_no']) === $baseline['payment']
            && $after['potential_refund'] === 50000
            && $after['completed_refunds'] === 0
            && $request->fresh()->status === 'executed';
        if (!$unchanged) {
            $this->error('ROLLBACK VERIFICATION FAILED. Stop testing and inspect records.');
            return self::FAILURE;
        }
        $this->info('ROLLBACK VERIFIED: no permanent refund/journal; ₹1,800 receipt retained; ₹500 remains refundable.');
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
