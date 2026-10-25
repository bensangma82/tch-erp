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
 * Stage 3 accounting engine. NOT connected to routes or production UI.
 * Monetary entries preserve source invoice, receipt and diagnostic records.
 * Explicitly rejects partially paid invoices until allocation policy is implemented.
 */
class InvestigationCancellationAccountingService
{
    public function __construct(
        private readonly JournalPostingService $posting,
        private readonly InvestigationExecutionPolicy $executionPolicy
    ) {}

    public function reverseRevenue(InvestigationCancellationRequest $cancellation, User $actor): JournalEntry
    {
        // Journal posting requires explicit refund verification authority.
// Cashier submission is authorized separately by the verification service.
abort_unless(
    $actor->permissions()
        ->where('permissions.name', 'billing.investigation-refund.verify')
        ->exists(),
    403
);
        return DB::transaction(function () use ($cancellation, $actor) {
            $request = InvestigationCancellationRequest::query()->whereKey($cancellation->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'approved' || $request->executed_at !== null) {
                $this->fail('Only an approved, unexecuted cancellation can be reversed.');
            }
            $invoice = Invoice::query()->whereKey($request->invoice_id)->lockForUpdate()->firstOrFail();
            $item = InvoiceItem::query()->whereKey($request->invoice_item_id)->lockForUpdate()->firstOrFail();
            if ($invoice->invoice_type !== 'investigation' || (int) $item->invoice_id !== (int) $invoice->id) {
                $this->fail('Invoice/item linkage is invalid.');
            }
            // Execution-time clinical guard: authorisation must remain valid even if the
            // sample was collected or the investigation was completed after approval.
            $this->verifyClinicalStateAtExecution($request, $item, $actor);
            $amount = $this->cents($request->approved_amount);
            if ($amount <= 0 || $amount !== $this->cents($item->amount)) {
                $this->fail('Stage 3 only supports cancellation of an entire invoice item at its original amount.');
            }
            if (InvestigationCancellationRequest::query()->where('invoice_item_id', $item->id)
                ->where('id', '<>', $request->id)->where('status', 'executed')->exists()) {
                $this->fail('This investigation item has already been cancelled.');
            }
            $payments = Payment::query()->where('invoice_id', $invoice->id)->lockForUpdate()->get();
            $paid = $payments->sum(fn ($p) => $this->cents($p->amount));
            $invoiceTotal = $this->cents($invoice->total_amount);
            if ($paid < 0 || $paid > $invoiceTotal) {
                $this->fail('Unreconciled invoice receipts: automated cancellation is blocked.');
            }
            $issued = JournalEntry::query()->where('source_type', 'invoice')->where('source_id', $invoice->id)
                ->where('source_event', 'issued')->lockForUpdate()->first();
            if (! $issued || $issued->status !== 'posted') {
                $this->fail('Posted investigation invoice journal not found.');
            }
            $this->blockTallyLinked($issued);
            foreach ($payments as $payment) {
                $receipt = JournalEntry::query()->where('source_type', 'payment')->where('source_id', $payment->id)
                    ->where('source_event', 'received')->lockForUpdate()->first();
                if (! $receipt || $receipt->status !== 'posted') {
                    $this->fail('An original payment journal is missing or unposted.');
                }
                $this->blockTallyLinked($receipt);
            }
            $service = $item->service;
            if (! $service || ! $service->finance_head_id) {
                $this->fail('Investigation revenue mapping is missing.');
            }
            $revenue = GeneralLedgerAccount::query()->where('finance_head_id', $service->finance_head_id)
                ->whereIn('account_code', ['4201','4202','4203','4204','4205','4206'])
                ->where('is_active', true)->where('allow_posting', true)->sole();
            $receivable = $this->account('1200');
            $journal = JournalEntry::create([
                'journal_no' => 'JRN-ICR-'.$request->id,
                'journal_date' => now()->toDateString(),
                'source_type' => 'investigation_cancellation_request',
                'source_id' => $request->id,
                'source_event' => 'revenue_reversed',
                'reference_no' => $invoice->invoice_no,
                'narration' => 'Investigation item cancellation '.$item->id.'; request '.$request->id,
                'status' => 'draft',
                'created_by' => $actor->id,
            ]);
            $this->line($journal, 1, $revenue, $amount, 0, $service->department_id);
            $this->line($journal, 2, $receivable, 0, $amount);
            $this->posting->post($journal);
            $request->forceFill(['status' => 'executed', 'executed_at' => now()])->save();
            return $journal->fresh('lines');
        }, 3);
    }

    /**
     * Record ONE verified external refund settlement. Split payments may produce
     * multiple refund rows, each allocated against its original receipt.
     * This method does not send funds. Call only after bank/cash confirmation.
     * @param string $amount Decimal currency value, e.g. "700.00".
     */
    public function recordCompletedRefund(
        InvestigationCancellationRequest $cancellation,
        Payment $sourcePayment,
        User $actor,
        string $reference,
        string $amount
    ): JournalEntry {
        $this->requirePermission($actor, 'billing.investigation-refund.process');
        return DB::transaction(function () use ($cancellation, $sourcePayment, $actor, $reference, $amount) {
            $request = InvestigationCancellationRequest::query()->whereKey($cancellation->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'executed' || $request->executed_at === null) {
                $this->fail('Revenue reversal must be posted before refund recording.');
            }
            $invoice = Invoice::query()->whereKey($request->invoice_id)->lockForUpdate()->firstOrFail();
            $payments = Payment::query()->where('invoice_id', $invoice->id)->orderBy('id')->lockForUpdate()->get();
            $payment = $payments->firstWhere('id', $sourcePayment->id);
            if (! $payment) {
                $this->fail('Selected original receipt does not belong to this invoice.');
            }
            $refundCents = $this->cents($amount);
            $ref = trim($reference);
            $mode = strtolower(trim((string) $payment->payment_mode));
            $accountCode = match ($mode) {
                'cash' => '1100', 'upi' => '1110', 'card' => '1120', default => null,
            };
            if ($refundCents <= 0 || $ref === '' || $accountCode === null) {
                $this->fail('Positive refund amount, supported receipt mode and settlement reference required.');
            }
            $invoiceJournal = JournalEntry::query()->where('source_type', 'invoice')
                ->where('source_id', $invoice->id)->where('source_event', 'issued')
                ->lockForUpdate()->first();
            if (! $invoiceJournal || $invoiceJournal->status !== 'posted') {
                $this->fail('Posted original invoice journal is missing.');
            }
            $this->blockTallyLinked($invoiceJournal);
            foreach ($payments as $receiptPayment) {
                $receipt = JournalEntry::query()->where('source_type', 'payment')
                    ->where('source_id', $receiptPayment->id)->where('source_event', 'received')
                    ->lockForUpdate()->first();
                if (! $receipt || $receipt->status !== 'posted') {
                    $this->fail('Posted receipt journal is missing.');
                }
                $this->blockTallyLinked($receipt);
            }
            $reversals = InvestigationCancellationRequest::query()->where('invoice_id', $invoice->id)
                ->where('status', 'executed')->orderBy('id')->lockForUpdate()->get();
            foreach ($reversals as $reversal) {
                $journal = JournalEntry::query()->where('source_type', 'investigation_cancellation_request')
                    ->where('source_id', $reversal->id)->where('source_event', 'revenue_reversed')
                    ->lockForUpdate()->first();
                if (! $journal || $journal->status !== 'posted') {
                    $this->fail('A cancellation journal is missing or unposted.');
                }
                $this->blockTallyLinked($journal);
            }
            // ALL refund rows are locked; any pending/uncertain settlement must be
            // reconciled before an additional external payout can be recorded.
            $refundRows = InvestigationCancellationRefund::query()
                ->whereHas('cancellationRequest', fn ($q) => $q->where('invoice_id', $invoice->id))
                ->orderBy('id')->lockForUpdate()->get();
            if ($refundRows->contains(fn ($r) => $r->status !== 'completed')) {
                $this->fail('Pending or uncertain refund exists; reconcile it first.');
            }
            if ($refundRows->contains(fn ($r) => (int) $r->payment_id === (int) $payment->id && $r->reference_no === $ref)) {
                $this->fail('This refund reference has already been recorded against the receipt.');
            }
            $totalPaid = $payments->sum(fn ($p) => $this->cents($p->amount));
            $totalCancelled = $reversals->sum(fn ($r) => $this->cents($r->approved_amount));
            $previouslyRefunded = $refundRows->sum(fn ($r) => $this->cents($r->amount));
            $previouslyForRequest = $refundRows->where('investigation_cancellation_request_id', $request->id)
                ->sum(fn ($r) => $this->cents($r->amount));
            $previouslyForPayment = $refundRows->where('payment_id', $payment->id)
                ->sum(fn ($r) => $this->cents($r->amount));
            $allocation = (new InvestigationRefundAllocationPolicy())->available(
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
                $this->fail('Refund exceeds the available overpayment, cancellation, or receipt balance.');
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
                'journal_no' => 'JRN-IRF-'.$refund->id,
                'journal_date' => now()->toDateString(),
                'source_type' => 'investigation_cancellation_refund',
                'source_id' => $refund->id,
                'source_event' => 'paid',
                'reference_no' => $ref,
                'narration' => 'Verified investigation refund '.$refund->id.' against payment '.$payment->id,
                'status' => 'draft',
                'created_by' => $actor->id,
            ]);
            $this->line($journal, 1, $this->account('1200'), $refundCents, 0);
            $this->line($journal, 2, $this->account($accountCode), 0, $refundCents);
            $this->posting->post($journal);
            $refund->forceFill(['status' => 'completed', 'processed_at' => now()])->save();
            return $journal->fresh('lines');
        }, 3);
    }

    private function verifyClinicalStateAtExecution(
        InvestigationCancellationRequest $request,
        InvoiceItem $item,
        User $actor
    ): void {
        if ((int) $item->service_order_item_id !== (int) $request->service_order_item_id) {
            $this->fail('Investigation association has changed.');
        }
        $orderItem = ServiceOrderItem::query()
            ->whereKey($request->service_order_item_id)->lockForUpdate()->first();
        if (! $orderItem) {
            $this->fail('Associated investigation no longer exists.');
        }
        $sample = $orderItem->diagnosticSample()->first();
        $result = $orderItem->diagnosticResult()->first();
        $collected = $sample !== null && $sample->status === 'collected';
        try {
            $requiresMs = $this->executionPolicy->requiresMs(
                (string) $orderItem->status,
                $collected,
                $result !== null,
                (bool) $request->requires_ms_approval
            );
        } catch (\InvalidArgumentException $exception) {
            $this->fail('Clinical status is unknown; execution blocked.');
        }
        if ($requiresMs) {
            // Do not reinterpret a supervisor's earlier approval as an MS decision.
            if (! $request->reviewed_by || ! InvestigationMsApprover::query()
                ->where('user_id', $request->reviewed_by)->where('active', true)->exists()) {
                $this->fail('Clinical progression requires a fresh Medical Superintendent approval.');
            }
            // Explicit direct permission; admins bypass hasPermission() globally.
            $reviewer = User::query()->find($request->reviewed_by);
            if (! $reviewer || ! $reviewer->permissions()
                ->where('permissions.name', 'billing.investigation-cancellation.approve')->exists()) {
                $this->fail('Medical Superintendent direct approval permission is missing.');
            }
        }
        // The clinical result is never removed or modified by this service.
    }

    private function account(string $code): GeneralLedgerAccount
    {
        return GeneralLedgerAccount::query()->where('account_code', $code)
            ->where('is_active', true)->where('allow_posting', true)->sole();
    }

    private function line(JournalEntry $journal, int $number, GeneralLedgerAccount $account, int $debit, int $credit, ?int $department = null): void
    {
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

    private function blockTallyLinked(JournalEntry $journal): void
    {
        if ($journal->finance_voucher_id !== null || $journal->tallyExport()->exists()) {
            $this->fail('Tally-linked accounting requires manual reconciliation; automatic reversal blocked.');
        }
    }

    private function cents(mixed $amount): int
    {
        if ($amount === null || ! is_numeric($amount)) {
            $this->fail('Invalid monetary amount.');
        }
        return (int) round(((float) $amount) * 100);
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function requirePermission(User $actor, string $permission): void
    {
        if (! $actor->hasPermission($permission)) {
            $this->fail('Insufficient permission.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['cancellation' => $message]);
    }
}
