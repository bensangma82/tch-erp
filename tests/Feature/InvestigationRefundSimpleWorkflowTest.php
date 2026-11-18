<?php

namespace Tests\Feature;

use App\Models\{InvestigationCancellationRequest, InvestigationRefundSettlementCheck, JournalEntry, Payment, Permission, User};
use App\Services\Investigation\InvestigationRefundSimpleWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvestigationRefundSimpleWorkflowTest extends TestCase
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

    private function fixture(bool $postedInvoice = true): array
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

    public function test_preview_prefills_receipt_amount_and_original_method_without_writes(): void
    {
        $f=$this->fixture(); $before=JournalEntry::count();
        $preview=$this->service()->preview($f['cancellation']);
        $this->assertCount(1,$preview['choices']);
        $this->assertSame($f['payment']->id,$preview['choices'][0]['payment']->id);
        $this->assertSame('500.00',$preview['choices'][0]['amount']);
        $this->assertSame('cash',$preview['choices'][0]['method']);
        $this->assertSame($before,JournalEntry::count());
        $this->assertDatabaseMissing('investigation_refund_settlement_checks',['cancellation_id'=>$f['cancellation']->id]);
    }

    public function test_click_flow_generates_notes_then_posts_one_balanced_journal(): void
    {
        $f=$this->fixture(); $before=JournalEntry::count();
        $s=$this->requestRefund($f);
        $this->assertSame('initiated',$s->status);
        $this->assertSame($before,JournalEntry::count());
        $paid=$this->service()->recordPayment($s,$f['cashier'],['actually_paid'=>true]);
        $this->assertSame('disbursed',$paid->status);
        $this->assertSame('CASH-REFUND-'.$s->id,$paid->settlement_reference);
        $this->assertStringContainsString('not independent proof',$paid->evidence_description);
        $this->assertSame($before,JournalEntry::count());
        $done=$this->service()->verifyRefund($paid,$f['verifier'],$this->proof());
        $this->assertSame('completed',$done->status);
        $this->assertStringContainsString('signed cash refund voucher',$done->verification_notes);
        $this->assertStringContainsString('verification recorded at',$done->verification_notes);
        $journal=JournalEntry::where('source_type','investigation_cancellation_refund')->where('source_id',$done->refund_id)->firstOrFail();
        $lines=DB::table('journal_entry_lines')->where('journal_entry_id',$journal->id)->get();
        $this->assertCount(2,$lines);
        $this->assertEquals('500.00',$lines->sum('debit'));
        $this->assertEquals('500.00',$lines->sum('credit'));
        $this->rejected(fn()=>$this->service()->verifyRefund($done,$f['verifier'],$this->proof()));
        $this->assertSame($before+1,JournalEntry::count());
        $this->assertSame('1800.00',$f['payment']->fresh()->amount);
    }

    public function test_duplicate_request_and_duplicate_payment_are_blocked(): void
    {
        $f=$this->fixture();$s=$this->requestRefund($f);
        $this->rejected(fn()=>$this->requestRefund($f));
        $paid=$this->service()->recordPayment($s,$f['cashier'],['actually_paid'=>true]);
        $this->rejected(fn()=>$this->service()->recordPayment($paid,$f['cashier'],['actually_paid'=>true]));
        $this->assertSame(1,InvestigationRefundSettlementCheck::where('cancellation_id',$f['cancellation']->id)->count());
    }

    public function test_changed_preview_amount_is_rejected(): void
    {
        $f=$this->fixture();
        $f['cancellation']->update(['approved_amount'=>'400.00']);
        $this->rejected(fn()=>$this->requestRefund($f));
    }

    public function test_payment_confirmation_is_required(): void
    {
        $f=$this->fixture();$s=$this->requestRefund($f);
        $this->rejected(fn()=>$this->service()->recordPayment($s,$f['cashier'],[]));
        $this->assertSame('initiated',$s->fresh()->status);
    }

    public function test_non_cash_refund_requires_actual_external_reference(): void
    {
        $f=$this->fixture();$f['payment']->update(['payment_mode'=>'upi']);
        $s=$this->requestRefund($f);
        $this->assertSame('upi',$s->settlement_method);
        $this->rejected(fn()=>$this->service()->recordPayment($s,$f['cashier'],['actually_paid'=>true]));
        $paid=$this->service()->recordPayment($s,$f['cashier'],['actually_paid'=>true,'settlement_reference'=>'TEST-UPI-'.Str::uuid()]);
        $this->assertSame('disbursed',$paid->status);
        $this->rejected(fn()=>$this->service()->verifyRefund($paid,$f['verifier'],$this->proof()));
    }

    public function test_maker_cannot_verify_even_with_direct_verify_permission(): void
    {
        $f=$this->fixture();$paid=$this->paid($f);
        $perm=Permission::where('name','billing.investigation-refund.verify')->firstOrFail();
        $f['cashier']->permissions()->syncWithoutDetaching([$perm->id]);
        $this->rejected(fn()=>$this->service()->verifyRefund($paid,$f['cashier'],$this->proof()));
        $this->assertSame('disbursed',$paid->fresh()->status);
    }

    public function test_verifier_must_explicitly_confirm_examining_evidence(): void
    {
        $f=$this->fixture();$paid=$this->paid($f);
        $this->rejected(fn()=>$this->service()->verifyRefund($paid,$f['verifier'],['proof_kind'=>'cash_voucher']));
    }

    public function test_legacy_missing_payment_date_and_reference_are_not_invented(): void
    {
        $f=$this->fixture();$s=$this->requestRefund($f);
        $s->forceFill(['status'=>'submitted','disbursed_at'=>null,'settlement_reference'=>'OLD-REFERENCE','evidence_description'=>'Historical supporting description retained.'])->save();
        $this->rejected(fn()=>$this->service()->verifyRefund($s,$f['verifier'],$this->proof()));
        $this->assertNull($s->fresh()->disbursed_at);
        $this->assertSame('OLD-REFERENCE',$s->fresh()->settlement_reference);
    }

    public function test_legacy_evidence_completion_preserves_original_description_and_audits_change(): void
    {
        $f=$this->fixture();$s=$this->requestRefund($f);
        $s->forceFill(['status'=>'submitted','disbursed_at'=>null,'settlement_reference'=>'OLD-REFERENCE','evidence_description'=>'Historical supporting description retained.'])->save();
        $date=now()->subDay()->startOfMinute();
        $done=$this->service()->verifyRefund($s,$f['verifier'],$this->proof()+['legacy_payment_reference'=>'ACTUAL-VOUCHER-'.Str::uuid(),'legacy_payment_date'=>$date->format('Y-m-d')]);
        $this->assertSame('completed',$done->status);
        $this->assertSame('Historical supporting description retained.',$done->evidence_description);
        $this->assertStringContainsString('Previous settlement reference: OLD-REFERENCE',$done->verification_notes);
        $this->assertNull($done->disbursed_at);
        $this->assertStringContainsString('Legacy actual payment date: '.$date->format('Y-m-d'),$done->verification_notes);
        $this->assertNull($done->disbursed_by);
    }

    public function test_unposted_accounting_blocks_before_new_request(): void
    {
        $f=$this->fixture(false);
        $preview=$this->service()->preview($f['cancellation']);
        $this->assertEmpty($preview['choices']);
        $this->assertStringContainsString('accounting is missing',$preview['reason']);
        $this->rejected(fn()=>$this->requestRefund($f));
    }

    public function test_queue_renders_actions_without_manual_amount_or_evidence_description(): void
    {
        $f=$this->fixture();
        $this->actingAs($f['cashier'])->get(route('billing.investigation-refunds.verification'))
            ->assertOk()->assertSee('Refund desk')->assertSee('Request ₹500.00')
            ->assertSee('name="expected_amount"',false)->assertDontSee('name="evidence_description"',false);
        $this->paid($f);
        $this->actingAs($f['verifier'])->get(route('billing.investigation-refunds.verification'))
            ->assertOk()->assertSee('Verify & complete refund',false)->assertDontSee('name="verification_notes"',false);
    }

    public function test_admin_without_direct_refund_grant_is_denied(): void
    {
        $this->fixture();$admin=User::factory()->create(['role'=>'admin','is_active'=>true]);
        $admin->permissions()->detach();
        $this->actingAs($admin)->get(route('billing.investigation-refunds.verification'))->assertForbidden();
    }
    public function test_tally_export_created_after_request_blocks_payment_recording(): void
    {
        $f=$this->fixture();$s=$this->requestRefund($f);
        $journal=JournalEntry::where('source_type','invoice')->where('source_id',$f['invoiceId'])->firstOrFail();
        \App\Models\TallyExport::create(['journal_entry_id'=>$journal->id,'status'=>'unknown','remote_id'=>(string)Str::uuid(),'export_reference'=>'TEST-'.Str::uuid()]);
        $this->rejected(fn()=>$this->service()->recordPayment($s,$f['cashier'],['actually_paid'=>true]));
        $this->assertSame('initiated',$s->fresh()->status);
    }

    public function test_unpaid_balance_is_not_offered_as_a_refund(): void
    {
        $f=$this->fixture();$f['payment']->update(['amount'=>'1000.00']);
        $this->assertEmpty($this->service()->preview($f['cancellation'])['choices']);
        $this->rejected(fn()=>$this->requestRefund($f));
    }

    public function test_simplified_http_request_ignores_client_amount_and_method_overrides(): void
    {
        $f=$this->fixture();
        $this->actingAs($f['cashier'])->post(route('billing.investigation-refunds.submit',$f['cancellation']),[
            'simple_flow'=>1,'payment_id'=>$f['payment']->id,'expected_amount'=>'500.00','amount'=>'1.00','settlement_method'=>'card',
        ])->assertRedirect(route('billing.investigation-refunds.verification'))->assertSessionHasNoErrors();
        $s=InvestigationRefundSettlementCheck::where('cancellation_id',$f['cancellation']->id)->firstOrFail();
        $this->assertSame('500.00',$s->amount);$this->assertSame('cash',$s->settlement_method);
        $this->actingAs($f['cashier'])->post(route('billing.investigation-refunds.disburse',$s),[
            'simple_flow'=>1,'actually_paid'=>1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($f['verifier'])->post(route('billing.investigation-refunds.verify',$s),[
            'simple_flow'=>1,'evidence_checked'=>1,'proof_kind'=>'cash_voucher',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('completed',$s->fresh()->status);
    }

    public function test_another_receipt_cannot_be_substituted(): void
    {
        $f=$this->fixture();$other=$this->fixture();
        $this->rejected(fn()=>$this->service()->requestRefund($f['cancellation'],$other['payment'],$f['cashier'],'500.00'));
    }

    public function test_pending_refund_reserves_the_invoice_balance_for_other_requests(): void
    {
        $f=$this->fixture();$f['payment']->update(['amount'=>'1400.00']);
        $item=DB::table('invoice_items')->insertGetId(['invoice_id'=>$f['invoiceId'],'description'=>'TEST second cancellation','quantity'=>1,'unit_price'=>'500.00','discount'=>'0.00','amount'=>'500.00','created_at'=>now(),'updated_at'=>now()]);
        DB::table('invoice_items')->where('invoice_id',$f['invoiceId'])->where('description','TEST - Other Investigation')->update(['unit_price'=>'800.00','amount'=>'800.00']);
        $second=$f['cancellation']->replicate();$second->invoice_item_id=$item;$second->save();
        $journal=JournalEntry::where('source_type','investigation_cancellation_request')->where('source_id',$f['cancellation']->id)->firstOrFail()->replicate();
        $journal->source_id=$second->id;$journal->journal_no='TEST-SECOND-'.Str::uuid();$journal->save();
        $this->requestRefund($f);
        $preview=$this->service()->preview($second);
        $this->assertSame('100.00',$preview['choices'][0]['amount']);
        $this->rejected(fn()=>$this->service()->requestRefund($second,$f['payment'],$f['cashier'],'500.00'));
    }

    public function test_split_receipts_do_not_silently_offer_an_incomplete_refund(): void
    {
        $f=$this->fixture();$f['cancellation']->update(['approved_amount'=>'1000.00','requested_amount'=>'1000.00']);
        DB::table('invoice_items')->where('id',$f['cancellation']->invoice_item_id)->update(['unit_price'=>'1000.00','amount'=>'1000.00']);
        DB::table('invoice_items')->where('invoice_id',$f['invoiceId'])->where('description','TEST - Other Investigation')->update(['unit_price'=>'800.00','amount'=>'800.00']);
        $f['payment']->update(['amount'=>'900.00']);
        $second=$f['payment']->replicate();$second->receipt_no='TEST-SPLIT-'.Str::uuid();$second->save();
        $journal=JournalEntry::where('source_type','payment')->where('source_id',$f['payment']->id)->firstOrFail()->replicate();
        $journal->source_id=$second->id;$journal->journal_no='TEST-SPLIT-'.Str::uuid();$journal->save();
        $preview=$this->service()->preview($f['cancellation']);
        $this->assertEmpty($preview['choices']);
        $this->assertStringContainsString('split receipts',$preview['reason']);
    }

}
