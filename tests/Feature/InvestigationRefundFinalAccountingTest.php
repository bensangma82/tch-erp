<?php

namespace Tests\Feature;

use App\Models\InvestigationCancellationRequest;
use App\Models\Permission;
use App\Models\User;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use App\Services\Investigation\InvestigationRefundVerificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvestigationRefundFinalAccountingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('pgsql', DB::getDefaultConnection());

        $this->assertSame(
            'testing',
            DB::selectOne('select current_database() as name')->name,
            'REFUSING TO RUN OUTSIDE ISOLATED TEST DATABASE'
        );
    }

    public function test_verified_cash_refund_posts_once_and_balances(): void
    {
        $suffix = (string) Str::uuid();

        // Synthetic users; no real patient or payment data.
        $requester = User::factory()->create(['role' => 'reception']);
        $cashier = User::factory()->create(['role' => 'billing']);
        $verifier = User::factory()->create(['role' => 'billing']);

        $process = Permission::firstOrCreate([
            'name' => 'billing.investigation-refund.process',
        ]);

        $verify = Permission::firstOrCreate([
            'name' => 'billing.investigation-refund.verify',
        ]);

        $cashier->permissions()->syncWithoutDetaching([$process->id]);
        $verifier->permissions()->syncWithoutDetaching([$verify->id]);

        $this->assertNotEquals($requester->id, $verifier->id);
        $this->assertNotEquals($cashier->id, $verifier->id);

        
$accounts = [
    [
        'account_code' => '1100',
        'account_name' => 'Main Cash',
        'account_type' => 'asset',
        'account_group' => 'Cash-in-Hand',
        'normal_balance' => 'debit',
    ],
    [
        'account_code' => '1200',
        'account_name' => 'Patient Receivables',
        'account_type' => 'asset',
        'account_group' => 'Sundry Debtors',
        'normal_balance' => 'debit',
    ],
];

foreach ($accounts as $account) {
    DB::table('general_ledger_accounts')->updateOrInsert(
        ['account_code' => $account['account_code']],
        [
            'account_name' => $account['account_name'],
            'account_type' => $account['account_type'],
            'account_group' => $account['account_group'],
            'normal_balance' => $account['normal_balance'],
            'is_active' => true,
            'allow_posting' => true,
            'updated_at' => now(),
        ] + $account
    );
}

        $patientId = DB::table('patients')->insertGetId([
            'uhid' => 'TEST-' . $suffix,
            'first_name' => 'Synthetic',
            'last_name' => 'Refund',
            'sex' => 'Male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_no' => 'TEST-INV-' . $suffix,
            'patient_id' => $patientId,
            'invoice_date' => today(),
            'invoice_type' => 'investigation',
            'subtotal' => '1800.00',
            'discount' => '0.00',
            'total_amount' => '1800.00',
            'paid_amount' => '1800.00',
            'balance_amount' => '0.00',
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('invoice_items')->insertGetId([
            'invoice_id' => $invoiceId,
            'description' => 'TEST - Cancelled Investigation',
            'quantity' => 1,
            'unit_price' => '500.00',
            'discount' => '0.00',
            'amount' => '500.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_items')->insert([
            'invoice_id' => $invoiceId,
            'description' => 'TEST - Other Investigation',
            'quantity' => 1,
            'unit_price' => '1300.00',
            'discount' => '0.00',
            'amount' => '1300.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentId = DB::table('payments')->insertGetId([
            'receipt_no' => 'TEST-RCP-' . $suffix,
            'invoice_id' => $invoiceId,
            'patient_id' => $patientId,
            'payment_date' => now(),
            'amount' => '1800.00',
            'payment_mode' => 'cash',
            'received_by' => $cashier->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cancellationId = DB::table(
            'investigation_cancellation_requests'
        )->insertGetId([
            'invoice_id' => $invoiceId,
            'invoice_item_id' => $itemId,
            'clinical_status_at_request' => 'pending',
            'sample_collected_at_request' => false,
            'requires_ms_approval' => false,
            'requested_amount' => '500.00',
            'approved_amount' => '500.00',
            'status' => 'executed',
            'reason' => 'TEST - Synthetic cancellation',
            'requested_by' => $requester->id,
            'reviewed_by' => $cashier->id,
            'reviewed_at' => now(),
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cancellation = InvestigationCancellationRequest::findOrFail(
            $cancellationId
        );

        $payment = \App\Models\Payment::findOrFail($paymentId);

        // Synthetic prerequisite accounting history.
        // These records exist only inside the test transaction.
        $prerequisites = [
            [
                'journal_no' => 'TEST-JRN-INV-' . $suffix,
                'source_type' => 'invoice',
                'source_id' => $invoiceId,
                'source_event' => 'issued',
                'reference_no' => 'TEST-INV-' . $suffix,
            ],
            [
                'journal_no' => 'TEST-JRN-RCP-' . $suffix,
                'source_type' => 'payment',
                'source_id' => $paymentId,
                'source_event' => 'received',
                'reference_no' => 'TEST-RCP-' . $suffix,
            ],
            [
                'journal_no' => 'TEST-JRN-REV-' . $suffix,
                'source_type' => 'investigation_cancellation_request',
                'source_id' => $cancellationId,
                'source_event' => 'revenue_reversed',
                'reference_no' => 'TEST-REV-' . $suffix,
            ],
        ];

        foreach ($prerequisites as $entry) {
            DB::table('journal_entries')->insert([
                ...$entry,
                'journal_date' => today(),
                'narration' => 'Synthetic accounting prerequisite',
                'status' => 'posted',
                'created_by' => $cashier->id,
                'posted_by' => $cashier->id,
                'posted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $service = app(InvestigationRefundVerificationService::class);

        $accounting = app(
            InvestigationCancellationAccountingService::class
        );

        // Stage 1: Initiation.
        $initiated = $service->initiate(
            $cancellation,
            $payment,
            $cashier,
            [
                'amount' => '500.00',
                'settlement_method' => 'cash',
            ]
        );

        $this->assertSame('initiated', $initiated->status);

        $this->assertSame(
            0,
            DB::table('investigation_cancellation_refunds')
                ->where(
                    'investigation_cancellation_request_id',
                    $cancellationId
                )
                ->count()
        );

        // Stage 2: Simulated disbursement.
        $disbursed = $service->disburse(
            $initiated,
            $cashier,
            [
                'actually_paid' => true,
                'settlement_reference' => 'TEST-PAYOUT-' . $suffix,
                'evidence_description' =>
                    'Synthetic testing only; no actual cash transferred.',
            ]
        );

        $this->assertSame('disbursed', $disbursed->status);

        // Stage 3: Independent verification and accounting.
        $completed = $service->verify(
            $disbursed,
            $verifier,
            'Synthetic refund evidence independently reviewed.',
            $accounting
        );

        $this->assertSame('completed', $completed->status);
        $this->assertSame($verifier->id, (int) $completed->verified_by);
        $this->assertNotNull($completed->refund_id);

        $journal = DB::table('journal_entries')
            ->where('source_type', 'investigation_cancellation_refund')
            ->where('source_id', $completed->refund_id)
            ->where('source_event', 'paid')
            ->first();

        $this->assertNotNull($journal);
        $this->assertSame('posted', $journal->status);

        $this->assertSame(
            'JRN-IRF-' . $completed->refund_id,
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

        $receivable = $lines->firstWhere('account_code', '1200');
        $cash = $lines->firstWhere('account_code', '1100');

        $this->assertNotNull($receivable);
        $this->assertNotNull($cash);

        $this->assertEqualsWithDelta(
            500,
            (float) $receivable->debit,
            0.001
        );

        $this->assertEqualsWithDelta(
            0,
            (float) $receivable->credit,
            0.001
        );

        $this->assertEqualsWithDelta(
            0,
            (float) $cash->debit,
            0.001
        );

        $this->assertEqualsWithDelta(
            500,
            (float) $cash->credit,
            0.001
        );

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

        // Re-verification cannot create another refund or journal.
        try {
            $service->verify(
                $completed,
                $verifier,
                'Attempting duplicate independent verification.',
                $accounting
            );

            $this->fail('Duplicate verification was accepted.');
        } catch (ValidationException $e) {
            $this->assertSame(
                1,
                DB::table('investigation_cancellation_refunds')
                    ->where(
                        'investigation_cancellation_request_id',
                        $cancellationId
                    )
                    ->count()
            );

            $this->assertSame(
                1,
                DB::table('journal_entries')
                    ->where(
                        'source_type',
                        'investigation_cancellation_refund'
                    )
                    ->where('source_id', $completed->refund_id)
                    ->count()
            );
        }
    }
}
