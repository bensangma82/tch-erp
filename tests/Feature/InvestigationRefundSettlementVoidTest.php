<?php

namespace Tests\Feature;

use App\Models\InvestigationRefundSettlementCheck;
use App\Models\Permission;
use App\Models\User;
use App\Services\Investigation\InvestigationRefundVerificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InvestigationRefundSettlementVoidTest extends TestCase
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

    /**
     * Create only synthetic records inside the test transaction.
     * The schema/fields mirror InvestigationRefundFinalAccountingTest.
     */
    private function settlementFixture(string $status = 'initiated'): array
    {
        $suffix = (string) Str::uuid();
        $requester = User::factory()->create(['role' => 'reception']);
        $cashier = User::factory()->create(['role' => 'billing']);
        $officer = User::factory()->create(['role' => 'billing']);

        $permission = Permission::firstOrCreate(
            ['name' => 'billing.investigation-refund.void'],
            [
                'label' => 'Void investigation refund settlement',
                'module' => 'billing',
                'description' => 'Void a settlement with an audit trail',
            ]
        );
        $officer->permissions()->syncWithoutDetaching([$permission->id]);

        $patientId = DB::table('patients')->insertGetId([
            'uhid' => 'TEST-VOID-' . $suffix,
            'first_name' => 'Synthetic',
            'last_name' => 'Void',
            'sex' => 'Male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_no' => 'TEST-VOID-INV-' . $suffix,
            'patient_id' => $patientId,
            'invoice_date' => today(),
            'invoice_type' => 'investigation',
            'subtotal' => '500.00',
            'discount' => '0.00',
            'total_amount' => '500.00',
            'paid_amount' => '500.00',
            'balance_amount' => '0.00',
            'status' => 'paid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('invoice_items')->insertGetId([
            'invoice_id' => $invoiceId,
            'description' => 'TEST - Voidable investigation',
            'quantity' => 1,
            'unit_price' => '500.00',
            'discount' => '0.00',
            'amount' => '500.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentId = DB::table('payments')->insertGetId([
            'receipt_no' => 'TEST-VOID-RCP-' . $suffix,
            'invoice_id' => $invoiceId,
            'patient_id' => $patientId,
            'payment_date' => now(),
            'amount' => '500.00',
            'payment_mode' => 'cash',
            'received_by' => $cashier->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cancellationId = DB::table('investigation_cancellation_requests')->insertGetId([
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

        $settlementId = DB::table('investigation_refund_settlement_checks')->insertGetId([
            'cancellation_id' => $cancellationId,
            'payment_id' => $paymentId,
            'amount' => '500.00',
            'settlement_method' => 'cash',
            'settlement_reference' => 'TEST-VOID-REF-' . $suffix,
            'evidence_description' => 'Synthetic test evidence, no real transfer',
            'status' => $status,
            'submitted_by' => $cashier->id,
            'initiated_by' => $cashier->id,
            'initiated_at' => now(),
            'disbursed_by' => $status === 'disbursed' ? $cashier->id : null,
            'disbursed_at' => $status === 'disbursed' ? now() : null,
            'disbursement_reference' => $status === 'disbursed'
                ? 'TEST-PAYOUT-' . $suffix : null,
            'disbursement_evidence' => $status === 'disbursed'
                ? 'Recorded simulated payout evidence' : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'officer' => $officer,
            'cashier' => $cashier,
            'cancellation_id' => $cancellationId,
            'payment_id' => $paymentId,
            'settlement' => InvestigationRefundSettlementCheck::findOrFail($settlementId),
        ];
    }

    private function void(InvestigationRefundSettlementCheck $settlement, User $officer): InvestigationRefundSettlementCheck
    {
        return app(InvestigationRefundVerificationService::class)->voidSettlement(
            $settlement,
            $officer,
            'Synthetic test settlement cancelled before any payment',
            'TEST-VOID-EVIDENCE-001'
        );
    }

    public function test_unauthorized_user_cannot_void_settlement(): void
    {
        $fixture = $this->settlementFixture();
        $unauthorized = User::factory()->create(['role' => 'billing']);

        try {
            $this->void($fixture['settlement'], $unauthorized);
            $this->fail('Unauthorized void was accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame('initiated', $fixture['settlement']->fresh()->status);
    }

    public function test_authorized_officer_can_void_initiated_settlement_with_full_audit_history(): void
    {
        $fixture = $this->settlementFixture();
        $before = $fixture['settlement'];
        $reference = $before->settlement_reference;
        $evidence = $before->evidence_description;

        $voided = $this->void($before, $fixture['officer']);

        $this->assertSame('voided', $voided->status);
        $this->assertSame($fixture['officer']->id, (int) $voided->voided_by);
        $this->assertNotNull($voided->voided_at);
        $this->assertSame('TEST-VOID-EVIDENCE-001', $voided->void_evidence_reference);
        $this->assertSame('Synthetic test settlement cancelled before any payment', $voided->void_reason);
        $this->assertSame($reference, $voided->settlement_reference);
        $this->assertSame($evidence, $voided->evidence_description);
        $this->assertEquals('500.00', $voided->amount);
        $this->assertNull($voided->refund_id);
        $this->assertDatabaseMissing('investigation_cancellation_refunds', [
            'investigation_cancellation_request_id' => $fixture['cancellation_id'],
        ]);
    }

    public function test_voiding_twice_is_rejected_and_original_audit_remains(): void
    {
        $fixture = $this->settlementFixture();
        $first = $this->void($fixture['settlement'], $fixture['officer']);
        $originalVoidedAt = $first->voided_at->toISOString();

        try {
            $this->void($first, $fixture['officer']);
            $this->fail('Duplicate void was accepted.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $after = $first->fresh();
        $this->assertSame('voided', $after->status);
        $this->assertSame($originalVoidedAt, $after->voided_at->toISOString());
        $this->assertSame('TEST-VOID-EVIDENCE-001', $after->void_evidence_reference);
    }

    public function test_recorded_disbursements_submissions_and_completed_settlements_cannot_be_voided(): void
    {
        foreach (['disbursed', 'submitted', 'completed'] as $status) {
            $fixture = $this->settlementFixture($status);

            try {
                $this->void($fixture['settlement'], $fixture['officer']);
                $this->fail("{$status} settlement was incorrectly voided.");
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }

            $fresh = $fixture['settlement']->fresh();
            $this->assertSame($status, $fresh->status);
            $this->assertNull($fresh->voided_by);
            $this->assertNull($fresh->voided_at);
        }
    }

    public function test_existing_refund_record_blocks_initiated_settlement_void(): void
    {
        $fixture = $this->settlementFixture();
        DB::table('investigation_cancellation_refunds')->insert([
            'investigation_cancellation_request_id' => $fixture['cancellation_id'],
            'payment_id' => $fixture['payment_id'],
            'amount' => '500.00',
            'refund_mode' => 'cash',
            'reference_no' => 'TEST-EXISTING-' . Str::uuid(),
            'status' => 'completed',
            'processed_by' => $fixture['cashier']->id,
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->void($fixture['settlement'], $fixture['officer']);
            $this->fail('Settlement with an existing refund was voided.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $this->assertSame('initiated', $fixture['settlement']->fresh()->status);
    }
}
