<?php

namespace App\Services\Investigation;

use App\Models\GeneralLedgerAccount;
use App\Models\InvestigationCancellationRequest;
use App\Models\InvestigationCancellationRefund;
use App\Models\InvestigationMsApprover;
use App\Models\ServiceOrderItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\User;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Investigation cancellation accounting engine.
 *
 * Handles:
 * - Approved investigation revenue reversal
 * - Refund accounting after independent verification
 * - Clinical eligibility checks
 * - Duplicate reversal/refund prevention
 * - PostgreSQL transaction locking
 * - Tally-linked journal safeguards
 *
 * Does not transfer money.
 */
class InvestigationCancellationAccountingService
{
    public function __construct(
        private readonly JournalPostingService $posting,
        private readonly InvestigationExecutionPolicy $executionPolicy
    ) {}

    /**
     * Reverse revenue for an approved investigation cancellation.
     *
     * Requires explicit cancellation execution permission.
     */
    public function reverseRevenue(
        InvestigationCancellationRequest $cancellation,
        User $actor
    ): JournalEntry {
        $this->requireDirectPermission(
            $actor,
            'billing.investigation-cancellation.execute'
        );

        return DB::transaction(function () use ($cancellation, $actor) {
            $request = InvestigationCancellationRequest::query()
                ->whereKey($cancellation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $request->status !== 'approved' ||
                $request->executed_at !== null
            ) {
                $this->fail(
                    'Only an approved, unexecuted cancellation can be reversed.'
                );
            }

            $invoice = Invoice::query()
                ->whereKey($request->invoice_id)
                ->lockForUpdate()
                ->firstOrFail();

            $item = InvoiceItem::query()
                ->whereKey($request->invoice_item_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $invoice->invoice_type !== 'investigation' ||
                (int) $item->invoice_id !== (int) $invoice->id
            ) {
                $this->fail('Invoice/item linkage is invalid.');
            }

            // Recheck clinical eligibility immediately before execution.
            $this->verifyClinicalStateAtExecution(
                $request,
                $item,
                $actor
            );

            $amount = $this->cents($request->approved_amount);

            if (
                $amount <= 0 ||
                $amount !== $this->cents($item->amount)
            ) {
                $this->fail(
                    'Only full cancellation of an invoice item '
                    . 'at its original amount is supported.'
                );
            }

            // Prevent duplicate cancellation of the same invoice item.
            if (
                InvestigationCancellationRequest::query()
                    ->where('invoice_item_id', $item->id)
                    ->where('id', '<>', $request->id)
                    ->where('status', 'executed')
                    ->exists()
            ) {
                $this->fail(
                    'This investigation item has already been cancelled.'
                );
            }

            $payments = Payment::query()
                ->where('invoice_id', $invoice->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $paid = $payments->sum(
                fn ($payment) => $this->cents($payment->amount)
            );

            $invoiceTotal = $this->cents($invoice->total_amount);

            if ($paid < 0 || $paid > $invoiceTotal) {
                $this->fail(
                    'Unreconciled invoice receipts: '
                    . 'automated cancellation is blocked.'
                );
            }

            $issued = JournalEntry::query()
                ->where('source_type', 'invoice')
                ->where('source_id', $invoice->id)
                ->where('source_event', 'issued')
                ->lockForUpdate()
                ->first();

            if (! $issued || $issued->status !== 'posted') {
                $this->fail(
                    'Posted investigation invoice journal not found.'
                );
            }

            $this->blockTallyLinked($issued);

            foreach ($payments as $payment) {
                $receipt = JournalEntry::query()
                    ->where('source_type', 'payment')
                    ->where('source_id', $payment->id)
                    ->where('source_event', 'received')
                    ->lockForUpdate()
                    ->first();

                if (! $receipt || $receipt->status !== 'posted') {
                    $this->fail(
                        'An original payment journal is missing or unposted.'
                    );
                }

                $this->blockTallyLinked($receipt);
            }

            $service = $item->service;

            if (! $service || ! $service->finance_head_id) {
                $this->fail(
                    'Investigation revenue mapping is missing.'
                );
            }

            $revenue = GeneralLedgerAccount::query()
                ->where(
                    'finance_head_id',
                    $service->finance_head_id
                )
                ->whereIn('account_code', [
                    '4201',
                    '4202',
                    '4203',
                    '4204',
                    '4205',
                    '4206',
                ])
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->sole();

            $receivable = $this->account('1200');

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-ICR-' . $request->id,
                'journal_date' => now()->toDateString(),
                'source_type' => 'investigation_cancellation_request',
                'source_id' => $request->id,
                'source_event' => 'revenue_reversed',
                'reference_no' => $invoice->invoice_no,
                'narration' => 'Investigation item cancellation '
                    . $item->id
                    . '; request '
                    . $request->id,
                'status' => 'draft',
                'created_by' => $actor->id,
            ]);

            $this->line(
                $journal,
                1,
                $revenue,
                $amount,
                0,
                $service->department_id
            );

            $this->line(
                $journal,
                2,
                $receivable,
                0,
                $amount
            );

            $this->posting->post($journal);

            $request->forceFill([
                'status' => 'executed',
                'executed_at' => now(),
            ])->save();

            return $journal->fresh('lines');
        }, 3);
    }

    /**
     * Record an independently verified refund settlement.
     *
     * This method posts accounting only.
     * It does not transfer cash or initiate bank payments.
     *
     * Call through the refund verification workflow after
     * settlement evidence has been independently verified.
     */
    public function recordCompletedRefund(
        InvestigationCancellationRequest $cancellation,
        Payment $sourcePayment,
        User $actor,
        string $reference,
        string $amount
    ): JournalEntry {
        $this->requireDirectPermission(
            $actor,
            'billing.investigation-refund.verify'
        );

        return DB::transaction(function () use (
            $cancellation,
            $sourcePayment,
            $actor,
            $reference,
            $amount
        ) {
            $request = InvestigationCancellationRequest::query()
                ->whereKey($cancellation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $request->status !== 'executed' ||
                $request->executed_at === null
            ) {
                $this->fail(
                    'Revenue reversal must be posted '
                    . 'before refund recording.'
                );
            }

            $invoice = Invoice::query()
                ->whereKey($request->invoice_id)
                ->lockForUpdate()
                ->firstOrFail();

            $payments = Payment::query()
                ->where('invoice_id', $invoice->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $payment = $payments->firstWhere(
                'id',
                $sourcePayment->id
            );

            if (! $payment) {
                $this->fail(
                    'Selected original receipt does not belong '
                    . 'to this invoice.'
                );
            }

            $refundCents = $this->cents($amount);
            $ref = trim($reference);

            $mode = strtolower(
                trim((string) $payment->payment_mode)
            );

            $accountCode = match ($mode) {
                'cash' => '1100',
                'upi' => '1110',
                'card' => '1120',
                default => null,
            };

            if (
                $refundCents <= 0 ||
                $ref === '' ||
                $accountCode === null
            ) {
                $this->fail(
                    'Positive refund amount, supported receipt mode '
                    . 'and settlement reference required.'
                );
            }

            $invoiceJournal = JournalEntry::query()
                ->where('source_type', 'invoice')
                ->where('source_id', $invoice->id)
                ->where('source_event', 'issued')
                ->lockForUpdate()
                ->first();

            if (
                ! $invoiceJournal ||
                $invoiceJournal->status !== 'posted'
            ) {
                $this->fail(
                    'Posted original invoice journal is missing.'
                );
            }

            $this->blockTallyLinked($invoiceJournal);

            foreach ($payments as $receiptPayment) {
                $receipt = JournalEntry::query()
                    ->where('source_type', 'payment')
                    ->where('source_id', $receiptPayment->id)
                    ->where('source_event', 'received')
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $receipt ||
                    $receipt->status !== 'posted'
                ) {
                    $this->fail(
                        'Posted receipt journal is missing.'
                    );
                }

                $this->blockTallyLinked($receipt);
            }

            $reversals = InvestigationCancellationRequest::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', 'executed')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($reversals as $reversal) {
                $journal = JournalEntry::query()
                    ->where(
                        'source_type',
                        'investigation_cancellation_request'
                    )
                    ->where('source_id', $reversal->id)
                    ->where('source_event', 'revenue_reversed')
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $journal ||
                    $journal->status !== 'posted'
                ) {
                    $this->fail(
                        'A cancellation journal is missing or unposted.'
                    );
                }

                $this->blockTallyLinked($journal);
            }

            // Lock all refund records linked to this invoice.
            $refundRows = InvestigationCancellationRefund::query()
                ->whereHas(
                    'cancellationRequest',
                    fn ($query) => $query->where(
                        'invoice_id',
                        $invoice->id
                    )
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if (
                $refundRows->contains(
                    fn ($refund) => $refund->status !== 'completed'
                )
            ) {
                $this->fail(
                    'Pending or uncertain refund exists; '
                    . 'reconcile it first.'
                );
            }

            if (
                $refundRows->contains(
                    fn ($refund) =>
                        (int) $refund->payment_id === (int) $payment->id
                        && $refund->reference_no === $ref
                )
            ) {
                $this->fail(
                    'This refund reference has already been '
                    . 'recorded against the receipt.'
                );
            }

            $totalPaid = $payments->sum(
                fn ($p) => $this->cents($p->amount)
            );

            $totalCancelled = $reversals->sum(
                fn ($r) => $this->cents($r->approved_amount)
            );

            $previouslyRefunded = $refundRows->sum(
                fn ($r) => $this->cents($r->amount)
            );

            $previouslyForRequest = $refundRows
                ->where(
                    'investigation_cancellation_request_id',
                    $request->id
                )
                ->sum(
                    fn ($r) => $this->cents($r->amount)
                );

            $previouslyForPayment = $refundRows
                ->where('payment_id', $payment->id)
                ->sum(
                    fn ($r) => $this->cents($r->amount)
                );

            $allocation = (
                new InvestigationRefundAllocationPolicy()
            )->available(
                $this->cents($invoice->total_amount),
                $totalPaid,
                $totalCancelled,
                $previouslyRefunded,
                $this->cents($request->approved_amount),
                $previouslyForRequest,
                $this->cents($payment->amount),
                $previouslyForPayment
            );

            if ($refundCents > $allocation) {
                $this->fail(
                    'Refund exceeds the available overpayment, '
                    . 'cancellation, or receipt balance.'
                );
            }

            $refund = InvestigationCancellationRefund::create([
                'investigation_cancellation_request_id' => $request->id,
                'payment_id' => $payment->id,
                'amount' => $this->money($refundCents),
                'refund_mode' => $mode,
                'reference_no' => $ref,
                'status' => 'pending',
                'processed_by' => $actor->id,
            ]);

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IRF-' . $refund->id,
                'journal_date' => now()->toDateString(),
                'source_type' => 'investigation_cancellation_refund',
                'source_id' => $refund->id,
                'source_event' => 'paid',
                'reference_no' => $ref,
                'narration' => 'Verified investigation refund '
                    . $refund->id
                    . ' against payment '
                    . $payment->id,
                'status' => 'draft',
                'created_by' => $actor->id,
            ]);

            // Debit Patient Receivables.
            $this->line(
                $journal,
                1,
                $this->account('1200'),
                $refundCents,
                0
            );

            // Credit cash or the corresponding settlement account.
            $this->line(
                $journal,
                2,
                $this->account($accountCode),
                0,
                $refundCents
            );

            /*
             * REFUND JOURNAL POSTING CORRECTION
             *
             * Populate posting metadata while the journal
             * remains in draft status.
             *
             * JournalPostingService retains responsibility
             * for validating and posting the journal.
             */
            $journal->forceFill([
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ])->save();

            $this->posting->post($journal);

            $refund->forceFill([
                'status' => 'completed',
                'processed_at' => now(),
            ])->save();

            return $journal->fresh('lines');
        }, 3);
    }

    /**
     * Verify the clinical state at execution time.
     */
    private function verifyClinicalStateAtExecution(
        InvestigationCancellationRequest $request,
        InvoiceItem $item,
        User $actor
    ): void {
        if (
            (int) $item->service_order_item_id !==
            (int) $request->service_order_item_id
        ) {
            $this->fail(
                'Investigation association has changed.'
            );
        }

        $orderItem = ServiceOrderItem::query()
            ->whereKey($request->service_order_item_id)
            ->lockForUpdate()
            ->first();

        if (! $orderItem) {
            $this->fail(
                'Associated investigation no longer exists.'
            );
        }

        $sample = $orderItem->diagnosticSample()->first();
        $result = $orderItem->diagnosticResult()->first();

        $collected = (
            $sample !== null &&
            $sample->status === 'collected'
        );

        try {
            $requiresMs = $this->executionPolicy->requiresMs(
                (string) $orderItem->status,
                $collected,
                $result !== null,
                (bool) $request->requires_ms_approval
            );
        } catch (\InvalidArgumentException $exception) {
            $this->fail(
                'Clinical status is unknown; execution blocked.'
            );
        }

        if ($requiresMs) {
            // Supervisor approval cannot substitute for MS approval.
            if (
                ! $request->reviewed_by ||
                ! InvestigationMsApprover::query()
                    ->where('user_id', $request->reviewed_by)
                    ->where('active', true)
                    ->exists()
            ) {
                $this->fail(
                    'Clinical progression requires a fresh '
                    . 'Medical Superintendent approval.'
                );
            }

            $reviewer = User::query()
                ->find($request->reviewed_by);

            if (
                ! $reviewer ||
                ! $reviewer->permissions()
                    ->where(
                        'permissions.name',
                        'billing.investigation-cancellation.approve'
                    )
                    ->exists()
            ) {
                $this->fail(
                    'Medical Superintendent direct approval '
                    . 'permission is missing.'
                );
            }
        }

        // Clinical results are not removed or modified.
    }

    /**
     * Retrieve an active and postable GL account.
     */
    private function account(
        string $code
    ): GeneralLedgerAccount {
        return GeneralLedgerAccount::query()
            ->where('account_code', $code)
            ->where('is_active', true)
            ->where('allow_posting', true)
            ->sole();
    }

    /**
     * Create a journal entry line.
     */
    private function line(
        JournalEntry $journal,
        int $number,
        GeneralLedgerAccount $account,
        int $debit,
        int $credit,
        ?int $department = null
    ): void {
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'line_no' => $number,
            'general_ledger_account_id' => $account->id,
            'department_id' => $department,
            'debit' => $this->money($debit),
            'credit' => $this->money($credit),
            'description' => 'Investigation cancellation accounting',
        ]);
    }

    /**
     * Prevent automated changes to Tally-linked accounting.
     */
    private function blockTallyLinked(
        JournalEntry $journal
    ): void {
        if (
            $journal->finance_voucher_id !== null ||
            $journal->tallyExport()->exists()
        ) {
            $this->fail(
                'Tally-linked accounting requires manual reconciliation; '
                . 'automatic reversal blocked.'
            );
        }
    }

    /**
     * Convert a currency value into integer paise.
     */
    private function cents(
        mixed $amount
    ): int {
        if (
            $amount === null ||
            ! is_numeric($amount)
        ) {
            $this->fail('Invalid monetary amount.');
        }

        return (int) round(
            ((float) $amount) * 100
        );
    }

    /**
     * Convert integer paise to a decimal currency string.
     */
    private function money(
        int $cents
    ): string {
        return number_format(
            $cents / 100,
            2,
            '.',
            ''
        );
    }

    /**
     * Require an explicitly assigned permission.
     *
     * Does not use hasPermission(), because that may
     * provide administrator overrides.
     */
    private function requireDirectPermission(
        User $actor,
        string $permission
    ): void {
        abort_unless(
            $actor->permissions()
                ->where('permissions.name', $permission)
                ->exists(),
            403
        );
    }

    /**
     * Reject invalid cancellation/refund accounting operations.
     */
    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'cancellation' => $message,
        ]);
    }
}
