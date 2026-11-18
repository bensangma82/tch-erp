<?php

namespace Tests\Feature;

use App\Models\{InvestigationCancellationRequest, InvestigationRefundSettlementCheck, JournalEntry, Payment, Permission, User};
use App\Services\Investigation\InvestigationRefundSimpleWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvestigationRefundConfirmedTallyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue(app()->environment('testing'), 'REFUSING NON-TESTING APP ENVIRONMENT');
        $this->assertSame('pgsql', DB::getDefaultConnection());
        $this->assertSame('testing', DB::connection()->getDatabaseName(), 'REFUSING NON-TESTING DATABASE');
        config(['investigation_cancellation.enable_verified_refunds' => true]);
    }

    private function fixture(bool $postedInvoice = true, ?int $financeVoucherId = null): array
    {
        $suffix = (string) Str::uuid();

        // Synthetic users; no real patient or payment data.
        $requester = User::factory()->create(['role' => 'reception', 'is_active' => true]);
        $cashier = User::factory()->create(['role' => 'billing', 'is_active' => true]);
        $verifier = User::factory()->create(['role' => 'billing', 'is_active' => true]);

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
            $isPosted = $postedInvoice || $entry['source_type'] !== 'invoice';
            DB::table('journal_entries')->insert([
                ...$entry,
                'finance_voucher_id' => $entry['source_type'] === 'invoice' ? $financeVoucherId : null,
                'journal_date' => today(),
                'narration' => 'Synthetic accounting prerequisite',
                'status' => $isPosted ? 'posted' : 'draft',
                'created_by' => $cashier->id,
                'posted_by' => $isPosted ? $cashier->id : null,
                'posted_at' => $isPosted ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return compact('requester', 'cashier', 'verifier', 'cancellation', 'payment', 'invoiceId');
    }

    private function service(): InvestigationRefundSimpleWorkflowService
    {
        return app(InvestigationRefundSimpleWorkflowService::class);
    }

    private function requestRefund(array $f): InvestigationRefundSettlementCheck
    {
        return $this->service()->requestRefund($f['cancellation'], $f['payment'], $f['cashier'], '500.00');
    }

    private function paid(array $f): InvestigationRefundSettlementCheck
    {
        return $this->service()->recordPayment($this->requestRefund($f), $f['cashier'], ['actually_paid' => true]);
    }

    private function proof(): array
    {
        return ['evidence_checked' => true, 'proof_kind' => 'cash_voucher'];
    }

    private function rejected(callable $action): void
    {
        try { $action(); $this->fail('Expected validation rejection.'); }
        catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
    }

    private function sources(array $f)
    {
        return JournalEntry::where(function($q) use($f) {
            $q->where(fn($j)=>$j->where('source_type','invoice')->where('source_id',$f['invoiceId']))
                ->orWhere(fn($j)=>$j->where('source_type','payment')->where('source_id',$f['payment']->id))
                ->orWhere(fn($j)=>$j->where('source_type','investigation_cancellation_request')->where('source_id',$f['cancellation']->id));
        })->orderBy('id')->get();
    }

    private function confirmed(array $f): array
    {
        $exports=[];
        foreach($this->sources($f) as $j) {
            $e=\App\Models\TallyExport::create(['journal_entry_id'=>$j->id,'status'=>'confirmed',
                'remote_id'=>(string)Str::uuid(),'export_reference'=>'TEST-CONFIRMED-'.Str::uuid()]);
            $exports[]=$e->fresh()->getAttributes();
        }
        return $exports;
    }

    public function test_confirmed_exports_allow_one_new_refund_without_changing_source_exports(): void
    {
        $f=$this->fixture();$exports=$this->confirmed($f);
        $originals=$this->sources($f)->map->getAttributes()->all();$before=JournalEntry::count();
        $this->assertSame('500.00',$this->service()->preview($f['cancellation'])['choices'][0]['amount']);
        $paid=$this->paid($f);
        $done=$this->service()->verifyRefund($paid,$f['verifier'],$this->proof());
        $this->assertSame('completed',$done->status);
        $this->assertSame($originals,$this->sources($f)->map->getAttributes()->all());
        foreach($exports as $snapshot) $this->assertSame($snapshot,\App\Models\TallyExport::findOrFail($snapshot['id'])->getAttributes());
        $refundJournal=JournalEntry::where('source_type','investigation_cancellation_refund')->where('source_id',$done->refund_id)->firstOrFail();
        $this->assertSame('posted',$refundJournal->status);
        $this->assertFalse($refundJournal->tallyExport()->exists());
        $lines=DB::table('journal_entry_lines')->where('journal_entry_id',$refundJournal->id)->get();
        $this->assertCount(2,$lines);$this->assertEquals(500,$lines->sum('debit'));$this->assertEquals(500,$lines->sum('credit'));
        $this->rejected(fn()=>$this->service()->verifyRefund($done,$f['verifier'],$this->proof()));
        $this->assertSame($before+1,JournalEntry::count());
    }

    public function test_nonconfirmed_exports_still_block_new_refund_requests(): void
    {
        foreach(['unknown','pending','failed','rejected'] as $status) {
            $f=$this->fixture();$j=$this->sources($f)->first();
            \App\Models\TallyExport::create(['journal_entry_id'=>$j->id,'status'=>$status,'export_reference'=>'TEST-BLOCK-'.Str::uuid()]);
            $this->assertEmpty($this->service()->preview($f['cancellation'])['choices']);
            $this->rejected(fn()=>$this->requestRefund($f));
            $this->assertDatabaseMissing('investigation_refund_settlement_checks',['cancellation_id'=>$f['cancellation']->id]);
        }
    }

    public function test_unknown_export_on_each_source_blocks_final_verification_without_posting(): void
    {
        foreach(['invoice','payment','investigation_cancellation_request'] as $source) {
            $f=$this->fixture();$paid=$this->paid($f);$before=JournalEntry::count();
            $j=$this->sources($f)->firstWhere('source_type',$source);
            \App\Models\TallyExport::create(['journal_entry_id'=>$j->id,'status'=>'unknown','export_reference'=>'TEST-UNCERTAIN-'.Str::uuid()]);
            $this->rejected(fn()=>$this->service()->verifyRefund($paid,$f['verifier'],$this->proof()));
            $this->assertSame('disbursed',$paid->fresh()->status);
            $this->assertSame($before,JournalEntry::count());
            $this->assertDatabaseMissing('investigation_cancellation_refunds',['investigation_cancellation_request_id'=>$f['cancellation']->id]);
        }
    }

    public function test_legacy_refund_with_confirmed_sources_can_complete_with_actual_evidence(): void
    {
        $f=$this->fixture();$this->confirmed($f);$s=$this->requestRefund($f);
        $s->forceFill(['status'=>'submitted','settlement_reference'=>'LEGACY-ORIGINAL','evidence_description'=>'Historical supporting description retained.'])->save();
        $done=$this->service()->verifyRefund($s,$f['verifier'],$this->proof()+[
            'legacy_payment_reference'=>'ACTUAL-CASH-VOUCHER-'.Str::uuid(),'legacy_payment_date'=>today()->subDay()->format('Y-m-d')]);
        $this->assertSame('completed',$done->status);
        $this->assertStringContainsString('Previous settlement reference: LEGACY-ORIGINAL',$done->verification_notes);
        $this->assertNull($done->disbursed_at);
    }

    public function test_existing_verification_api_uses_the_same_confirmed_source_policy(): void
    {
        $f=$this->fixture();$this->confirmed($f);$paid=$this->paid($f);
        $done=app(\App\Services\Investigation\InvestigationRefundVerificationService::class)->verify(
            $paid,$f['verifier'],'Synthetic signed payment evidence independently checked.',
            app(\App\Services\Investigation\InvestigationCancellationAccountingService::class));
        $this->assertSame('completed',$done->status);
    }

    public function test_confirmed_export_with_finance_workflow_link_still_requires_review(): void
    {
        $voucher=DB::table('finance_vouchers')->insertGetId([
            'voucher_no'=>'TEST-FINANCE-'.Str::uuid(),'voucher_type'=>'payment','voucher_date'=>today(),
            'amount'=>'500.00','status'=>'draft','created_at'=>now(),'updated_at'=>now()]);
        $f=$this->fixture(true,$voucher);
        $j=$this->sources($f)->first();
        \App\Models\TallyExport::create(['journal_entry_id'=>$j->id,'status'=>'confirmed','export_reference'=>'TEST-MIXED-'.Str::uuid()]);
        $this->assertEmpty($this->service()->preview($f['cancellation'])['choices']);
        $this->rejected(fn()=>$this->requestRefund($f));
    }
}
