<?php

namespace Tests\Feature;

use App\Models\InvestigationCancellationRequest;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Run ONLY on the isolated PostgreSQL database named testing.
 * All writes in these tests are rolled back by DatabaseTransactions.
 * No actual cash/bank refund is performed.
 */
class InvestigationReversalIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::getDefaultConnection(), 'PostgreSQL required');
        $this->assertSame('testing', DB::selectOne('select current_database() as name')->name,
            'REFUSING TO RUN: current DB is not testing');
    }


public function test_unprivileged_user_cannot_execute_revenue_reversal(): void
{
    $actor = User::factory()->create([
        'role' => 'reception',
    ]);

    $cancellation = new InvestigationCancellationRequest();

    $before = JournalEntry::count();

    try {
        app(InvestigationCancellationAccountingService::class)
            ->reverseRevenue($cancellation, $actor);

        $this->fail('Unprivileged actor was allowed');

    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {

        $this->assertSame(403, $e->getStatusCode());

        $this->assertSame(
            $before,
            JournalEntry::count(),
            'Unauthorized reversal must not create a journal entry.'
        );
    }
}



public function test_unprivileged_user_cannot_record_refund(): void
{
    $actor = User::factory()->create([
        'role' => 'reception',
    ]);

    $before = JournalEntry::count();

    try {
        app(InvestigationCancellationAccountingService::class)
            ->recordCompletedRefund(
                new InvestigationCancellationRequest(),
                new Payment(),
                $actor,
                'TEST-NOT-PAID',
                '500.00'
            );

        $this->fail('Unprivileged actor was allowed to record a refund.');

    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {

        $this->assertSame(
            403,
            $e->getStatusCode(),
            'Unauthorized refund recording must return HTTP 403.'
        );

        $this->assertSame(
            $before,
            JournalEntry::count(),
            'Unauthorized refund must not create a journal entry.'
        );
    }
}


public function test_reversal_posts_balanced_journal_and_rejects_duplicate(): void
{
    $suffix = (string) \Illuminate\Support\Str::uuid();
    $now = now();

    $actor = User::factory()->create(['role' => 'admin']);

    $permission = \App\Models\Permission::firstOrCreate([
        'name' => 'billing.investigation-cancellation.execute',
    ]);

    $actor->permissions()->syncWithoutDetaching([$permission->id]);
    $this->actingAs($actor);

    // Synthetic accounting master records.
    $financeHeadId = DB::table('finance_heads')->insertGetId([
        'code' => 'TL-' . substr($suffix, 0, 20),
        'name' => 'TEST Laboratory Revenue',
        'head_type' => 'income',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    foreach ([
        ['1200', 'Patient Receivables', 'asset', 'debit'],
        ['4201', 'Laboratory Revenue', 'income', 'credit'],
    ] as [$code, $name, $type, $normal]) {
        DB::table('general_ledger_accounts')->updateOrInsert(
            ['account_code' => $code],
            [
                'account_name' => $name,
                'account_type' => $type,
                'account_group' => $type === 'asset'
                    ? 'Sundry Debtors'
                    : 'Direct Incomes',
                'finance_head_id' => $code === '4201'
                    ? $financeHeadId
                    : null,
                'normal_balance' => $normal,
                'is_active' => true,
                'allow_posting' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    // Synthetic patient.
    $patientId = DB::table('patients')->insertGetId([
        'uhid' => 'TEST-' . $suffix,
        'first_name' => 'Synthetic',
        'last_name' => 'Cancellation',
        'sex' => 'Male',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Laboratory service.
    $serviceId = DB::table('services')->insertGetId([
        'code' => 'TEST-LAB-' . $suffix,
        'name' => 'TEST Laboratory Investigation',
        'category' => 'laboratory',
        'finance_head_id' => $financeHeadId,
        'price' => '500.00',
        'is_active' => true,
        'requires_sample' => false,
        'requires_report' => false,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Clinical investigation order.
    $orderId = DB::table('service_orders')->insertGetId([
        'order_no' => 'TEST-ORD-' . $suffix,
        'patient_id' => $patientId,
        'ordered_at' => $now,
        'status' => 'draft',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $orderItemId = DB::table('service_order_items')->insertGetId([
        'service_order_id' => $orderId,
        'service_id' => $serviceId,
        'service_code' => 'TEST-LAB-' . $suffix,
        'service_name' => 'TEST Laboratory Investigation',
        'category' => 'laboratory',
        'quantity' => 1,
        'unit_price' => '500.00',
        'amount' => '500.00',
        'status' => 'ordered',
        'requires_sample' => false,
        'requires_report' => false,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Original invoice and payment.
    $invoiceId = DB::table('invoices')->insertGetId([
        'invoice_no' => 'TEST-INV-' . $suffix,
        'patient_id' => $patientId,
        'invoice_date' => today(),
        'invoice_type' => 'investigation',
        'subtotal' => '500.00',
        'discount' => '0.00',
        'total_amount' => '500.00',
        'paid_amount' => '500.00',
        'balance_amount' => '0.00',
        'status' => 'paid',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $invoiceItemId = DB::table('invoice_items')->insertGetId([
        'invoice_id' => $invoiceId,
        'service_order_item_id' => $orderItemId,
        'service_id' => $serviceId,
        'description' => 'TEST Laboratory Investigation',
        'quantity' => 1,
        'unit_price' => '500.00',
        'discount' => '0.00',
        'amount' => '500.00',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $paymentId = DB::table('payments')->insertGetId([
        'receipt_no' => 'TEST-RCP-' . $suffix,
        'invoice_id' => $invoiceId,
        'patient_id' => $patientId,
        'payment_date' => $now,
        'amount' => '500.00',
        'payment_mode' => 'cash',
        'received_by' => $actor->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    // Synthetic posted accounting prerequisites.
    foreach ([
        ['invoice', $invoiceId, 'issued', 'INV'],
        ['payment', $paymentId, 'received', 'RCP'],
    ] as [$sourceType, $sourceId, $sourceEvent, $prefix]) {
        DB::table('journal_entries')->insert([
            'journal_no' => 'TEST-JRN-' . $prefix . '-' . $suffix,
            'journal_date' => today(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_event' => $sourceEvent,
            'reference_no' => 'TEST-' . $prefix . '-' . $suffix,
            'narration' => 'Synthetic prerequisite journal',
            'status' => 'posted',
            'created_by' => $actor->id,
            'posted_by' => $actor->id,
            'posted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $cancellationId = DB::table(
        'investigation_cancellation_requests'
    )->insertGetId([
        'invoice_id' => $invoiceId,
        'invoice_item_id' => $invoiceItemId,
        'service_order_item_id' => $orderItemId,
        'clinical_status_at_request' => 'ordered',
        'sample_collected_at_request' => false,
        'requires_ms_approval' => false,
        'requested_amount' => '500.00',
        'approved_amount' => '500.00',
        'status' => 'approved',
        'reason' => 'TEST - Synthetic cancellation',
        'requested_by' => $actor->id,
        'reviewed_by' => $actor->id,
        'reviewed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $request = InvestigationCancellationRequest::findOrFail(
        $cancellationId
    );

    $originalPayments = Payment::where(
        'invoice_id',
        $invoiceId
    )->get(['id', 'amount', 'receipt_no'])->toArray();

    $invoiceTotal = (string) $request->invoice->total_amount;

    // Execute real reversal accounting service.
    $accounting = app(
        InvestigationCancellationAccountingService::class
    );

    $journal = $accounting->reverseRevenue($request, $actor);

    $this->assertSame('posted', $journal->status);
    $this->assertSame('revenue_reversed', $journal->source_event);
    $this->assertSame('executed', $request->fresh()->status);

    $this->assertSame(
        'JRN-ICR-' . $cancellationId,
        $journal->journal_no
    );

    $lines = DB::table('journal_entry_lines as line')
        ->join(
            'general_ledger_accounts as gl',
            'gl.id',
            '=',
            'line.general_ledger_account_id'
        )
        ->where('line.journal_entry_id', $journal->id)
        ->select('gl.account_code', 'line.debit', 'line.credit')
        ->get();

    $this->assertCount(2, $lines);

    $revenue = $lines->firstWhere('account_code', '4201');
    $receivable = $lines->firstWhere('account_code', '1200');

    $this->assertNotNull($revenue);
    $this->assertNotNull($receivable);

    $this->assertEqualsWithDelta(500, $revenue->debit, 0.001);
    $this->assertEqualsWithDelta(0, $revenue->credit, 0.001);

    $this->assertEqualsWithDelta(0, $receivable->debit, 0.001);
    $this->assertEqualsWithDelta(500, $receivable->credit, 0.001);

    $this->assertEqualsWithDelta(
        500,
        $lines->sum('debit'),
        0.001
    );

    $this->assertEqualsWithDelta(
        500,
        $lines->sum('credit'),
        0.001
    );

    // Original payment and invoice must remain unchanged.
    $this->assertSame(
        $originalPayments,
        Payment::where('invoice_id', $invoiceId)
            ->get(['id', 'amount', 'receipt_no'])
            ->toArray()
    );

    $this->assertSame(
        $invoiceTotal,
        (string) $request->invoice->fresh()->total_amount
    );

    // Duplicate execution must fail.
    try {
        $accounting->reverseRevenue($request, $actor);

        $this->fail('Duplicate cancellation reversal succeeded.');
    } catch (ValidationException $e) {
        $this->assertSame(
            1,
            JournalEntry::where(
                'source_type',
                'investigation_cancellation_request'
            )
                ->where('source_id', $cancellationId)
                ->where('source_event', 'revenue_reversed')
                ->count()
        );
    }
}
}
