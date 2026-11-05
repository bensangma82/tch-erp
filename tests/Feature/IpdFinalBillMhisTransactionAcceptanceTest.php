<?php

namespace Tests\Feature;

use App\Models\{Admission, GeneralLedgerAccount, IpBillingAccount, IpBillingCharge, IpBillingMhisClaim, JournalEntry, Patient, User};
use App\Services\Accounting\{IpBillingAccountingService, MhisReceivableAccountingService};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class IpdFinalBillMhisTransactionAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    private function setupBill(): array
    {
        $this->assertSame('testing', DB::connection()->getDatabaseName(), 'Refusing to write outside testing DB');
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $user = User::factory()->create(['role'=>'super_admin', 'is_active'=>true, 'email_verified_at'=>now()]);
        $this->actingAs($user);
        $k = 'IPDFIN-'.strtoupper(bin2hex(random_bytes(5)));
        $patient = Patient::create(['uhid'=>$k, 'first_name'=>'IPD', 'last_name'=>'Finance', 'sex'=>'male', 'is_active'=>true]);
        $admission = Admission::create(['admission_no'=>$k, 'patient_id'=>$patient->id, 'admitted_at'=>now(), 'status'=>'admitted']);
        $account = IpBillingAccount::create([
            'account_no'=>$k, 'opened_at'=>now(), 'admission_id'=>$admission->id,
            'patient_id'=>$patient->id, 'status'=>'finalized', 'net_amount'=>'1000.00',
            'subtotal'=>'1000.00', 'paid_amount'=>'0.00', 'balance_amount'=>'1000.00',
            'final_bill_no'=>'FIN-'.$k, 'finalized_at'=>now(),
        ]);
        IpBillingCharge::create([
            'ip_billing_account_id'=>$account->id, 'admission_id'=>$admission->id,
            'charge_date'=>now(), 'charge_type'=>'bed', 'description'=>'Room charges',
            'quantity'=>'1.00', 'unit_price'=>'1000.00', 'amount'=>'1000.00',
            'discount'=>'0.00', 'status'=>'active', 'created_by'=>$user->id,
        ]);
        foreach ([
            '1200'=>['Patient Receivables','asset','debit'],
            '1210'=>['MHIS Receivables','asset','debit'],
            '4301'=>['IPD Services Revenue','income','credit'],
        ] as $code => [$name,$type,$normal]) {
            $gl = GeneralLedgerAccount::firstOrCreate(['account_code'=>(string)$code], [
                'account_name'=>$name, 'account_type'=>$type,
                'account_group'=>$type==='asset'?'Current Assets':'Clinical Revenue',
                'normal_balance'=>$normal, 'is_active'=>true, 'allow_posting'=>true,
            ]);
            $this->assertTrue((bool)$gl->is_active, "GL $code inactive in testing");
            $this->assertTrue((bool)$gl->allow_posting, "GL $code cannot post in testing");
        }
        return compact('user','patient','admission','account','k');
    }

    private function amounts(JournalEntry $j): array
    {
        $result=[];
        foreach ($j->lines()->with('generalLedgerAccount')->get() as $line) {
            $code=(string)$line->generalLedgerAccount->account_code;
            $result[$code]=['dr'=>(string)$line->debit, 'cr'=>(string)$line->credit];
        }
        return $result;
    }

    public function test_mhis_free_rejects_patient_payment_without_receipt_or_journal(): void
    {
        $f = $this->setupBill();

        $f['account']->update([
            'mhis_category' => 'free',
        ]);

        $beforePayments = DB::table('ip_billing_payments')
            ->where('ip_billing_account_id', $f['account']->id)
            ->count();

        $beforeJournals = JournalEntry::query()
            ->where('source_type', 'ip_billing_payment')
            ->count();

        $response = $this->from(
            '/ip-billing/'.$f['admission']->id
        )->post(
            route('ip-billing.payment.store', [
                'admission' => $f['admission']->id,
            ]),
            [
                'amount' => '100.00',
                'payment_mode' => 'cash',
            ]
        );

        $response->assertSessionHasErrors('amount');

        $this->assertSame(
            $beforePayments,
            DB::table('ip_billing_payments')
                ->where('ip_billing_account_id', $f['account']->id)
                ->count()
        );

        $this->assertSame(
            $beforeJournals,
            JournalEntry::query()
                ->where('source_type', 'ip_billing_payment')
                ->count()
        );
    }

    public function test_mhis_free_rejects_advance_without_receipt_or_journal(): void
    {
        $f = $this->setupBill();

        $f['account']->update([
            'status' => 'open',
            'final_bill_no' => null,
            'finalized_at' => null,
            'mhis_category' => 'free',
        ]);

        $beforeAdvances = DB::table('ip_billing_advances')
            ->where('ip_billing_account_id', $f['account']->id)
            ->count();

        $beforeJournals = JournalEntry::query()
            ->where('source_type', 'ip_billing_advance')
            ->count();

        $response = $this->from(
            '/ip-billing/'.$f['admission']->id
        )->post(
            route('ip-billing.advance.store', [
                'admission' => $f['admission']->id,
            ]),
            [
                'amount' => '100.00',
                'payment_mode' => 'cash',
            ]
        );

        $response->assertSessionHasErrors('amount');

        $this->assertSame(
            $beforeAdvances,
            DB::table('ip_billing_advances')
                ->where('ip_billing_account_id', $f['account']->id)
                ->count()
        );

        $this->assertSame(
            $beforeJournals,
            JournalEntry::query()
                ->where('source_type', 'ip_billing_advance')
                ->count()
        );
    }

    public function test_mhis_free_submitted_claim_passes_finalization_preflight(): void
    {
        $f = $this->setupBill();

        $f['account']->update([
            'status' => 'open',
            'final_bill_no' => null,
            'finalized_at' => null,
            'mhis_category' => 'free',
        ]);

        $claim = IpBillingMhisClaim::create([
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'claim_no' => 'MHIS-'.$f['k'],
            'status' => 'submitted',
            'claim_amount' => '1000.00',
            'expected_amount' => '1000.00',
            'approved_amount' => '0.00',
            'created_by' => $f['user']->id,
        ]);

        $preflight = app(
            \App\Services\Preflight\IpBillingPreflightService::class
        )->checkFinalization($f['account']->fresh());

        $this->assertFalse(
            $preflight->hasBlockingIssues(),
            json_encode($preflight->issues())
        );

        $this->assertSame('submitted', $claim->fresh()->status);

        $this->assertSame(
            0,
            JournalEntry::query()
                ->where('source_type', 'ip_billing_mhis_claim')
                ->where('source_id', (string) $claim->id)
                ->where('source_event', 'approved')
                ->count()
        );
    }

    public function test_mhis_free_submitted_claim_finalizes_without_premature_recognition(): void
    {
        $f = $this->setupBill();

        $f['account']->update([
            'status' => 'open',
            'final_bill_no' => null,
            'finalized_at' => null,
            'finalized_by' => null,
            'mhis_category' => 'free',
        ]);

        $claim = IpBillingMhisClaim::create([
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'claim_no' => 'MHIS-'.$f['k'],
            'status' => 'submitted',
            'claim_amount' => '1000.00',
            'expected_amount' => '1000.00',
            'approved_amount' => '0.00',
            'created_by' => $f['user']->id,
        ]);

        $response = $this->post(
            route('ip-billing.finalize', [
                'admission' => $f['admission']->id,
            ])
        );

        $response->assertSessionHasNoErrors();

        $account = $f['account']->fresh();

        $this->assertSame('finalized', $account->status);
        $this->assertNotNull($account->final_bill_no);
        $this->assertNotNull($account->finalized_at);

        $journal = JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_id', (string) $account->id)
            ->where('source_event', 'finalized')
            ->firstOrFail();

        $this->assertSame('posted', $journal->status);
        $this->assertSame(
            ['dr' => '1000.00', 'cr' => '0.00'],
            $this->amounts($journal)['1200']
        );
        $this->assertSame(
            ['dr' => '0.00', 'cr' => '1000.00'],
            $this->amounts($journal)['4301']
        );

        $this->assertSame('submitted', $claim->fresh()->status);

        $finalBill = $this->get(route('ip-billing.final-bill', [
            'admission' => $f['admission']->id,
        ]));

        $finalBill->assertOk();
        $finalBill->assertViewHas('mhisApprovedAmount', 0.0);
        $finalBill->assertViewHas('mhisOutstandingAmount', 0.0);

        $this->assertSame(
            0,
            JournalEntry::query()
                ->where('source_type', 'ip_billing_mhis_claim')
                ->where('source_id', (string) $claim->id)
                ->where('source_event', 'approved')
                ->count()
        );

        $this->assertSame(
            0,
            $account->charityAdjustments()->count()
        );

        // Repeating finalisation must not create another journal.
        $this->post(route('ip-billing.finalize', [
            'admission' => $f['admission']->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $account->id)
                ->where('source_event', 'finalized')
                ->count()
        );
    }

    private function setupMhisVariance(
        string $bill,
        string $approved
    ): array {
        $f = $this->setupBill();

        config()->set('mhis_package_variance.enabled', true);

        foreach ([
            '4391' => ['MHIS Package Differential Revenue', 'credit'],
            '4392' => ['MHIS Contractual Allowances', 'debit'],
        ] as $code => [$name, $normal]) {
            $gl = GeneralLedgerAccount::firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => $name,
                    'account_type' => 'income',
                    'account_group' => $code === '4391'
                        ? 'MHIS Revenue'
                        : 'Contra Revenue — MHIS',
                    'normal_balance' => $normal,
                    'is_active' => true,
                    'allow_posting' => true,
                ]
            );

            $this->assertSame('income', $gl->account_type);
            $this->assertSame($normal, $gl->normal_balance);
            $this->assertTrue((bool) $gl->is_active);
            $this->assertTrue((bool) $gl->allow_posting);
        }

        $f['account']->update([
            'mhis_category' => 'free',
            'subtotal' => $bill,
            'net_amount' => $bill,
            'balance_amount' => $bill,
        ]);

        IpBillingCharge::where(
            'ip_billing_account_id',
            $f['account']->id
        )->update([
            'unit_price' => $bill,
            'amount' => $bill,
        ]);

        app(IpBillingAccountingService::class)
            ->postFinalBill($f['account']);

        $claim = IpBillingMhisClaim::create([
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'claim_no' => 'VAR-'.$f['k'],
            'status' => 'approved',
            'claim_amount' => $approved,
            'expected_amount' => $approved,
            'approved_amount' => $approved,
            'approval_date' => now()->toDateString(),
            'created_by' => $f['user']->id,
        ]);

        return [$f, $claim];
    }

    private function assertMhisVarianceTallyXml(
        \App\Models\JournalEntry $journal,
        array $expected
    ): void {
        foreach ([
            '1200' => 'Patient Receivables',
            '1210' => 'MHIS Receivables',
            '4391' => 'MHIS Package Differential Revenue',
            '4392' => 'MHIS Contractual Allowances',
        ] as $code => $ledger) {
            GeneralLedgerAccount::where('account_code', $code)
                ->update(['tally_ledger_name' => $ledger]);
        }

        $journal->unsetRelation('lines');

        $xml = app(
            \App\Services\Tally\TallyJournalXmlBuilder::class
        )->build($journal, null, '2026-11-05');

        $document = simplexml_load_string($xml);
        $this->assertNotFalse($document);

        $entries = $document->xpath('//ALLLEDGERENTRIES.LIST');
        $this->assertCount(3, $entries);

        $actual = [];

        foreach ($entries as $entry) {
            $ledger = (string) $entry->LEDGERNAME;
            $actual[$ledger] = (string) $entry->AMOUNT;
        }

        $this->assertSame($expected, $actual);
    }

    private function createMhisVarianceTestApproval(array $f, $claim): void
    {
        $maker = \App\Models\User::factory()->create();
        $bill = (string) $f['account']->fresh()->net_amount;
        $approved = (string) $claim->fresh()->approved_amount;
        $variance = round((float) $approved - (float) $bill, 2);
        \App\Models\MhisPackageVarianceApproval::create([
            'ip_billing_mhis_claim_id' => $claim->id,
            'ip_billing_account_id' => $f['account']->id,
            'bill_amount' => $bill,
            'approved_amount' => $approved,
            'variance_amount' => number_format($variance, 2, '.', ''),
            'treatment' => $variance > 0 ? 'additional_revenue' : 'contractual_allowance',
            'supporting_reference' => 'TEST-AUTH-'.$claim->id,
            'reason' => 'Automated test of documented MHIS package entitlement',
            'supporting_evidence' => 'TEST-APPROVAL-'.$claim->id,
            'contractual_nonrecoverable' => $variance < 0,
            'status' => 'approved',
            'created_by' => $maker->id,
            'submitted_at' => now(),
            'approved_by' => $f['user']->id,
            'approved_at' => now(),
        ]);
    }

    public function test_mhis_variance_without_finance_approval_is_blocked(): void
    {
        [$f, $claim] = $this->setupMhisVariance('4000.00', '6000.00');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Finance authorisation is required');
        app(MhisReceivableAccountingService::class)->recognize($claim);
    }

    public function test_mhis_free_package_variance_above_bill(): void
    {
        [$f, $claim] = $this->setupMhisVariance(
            '4000.00',
            '6000.00'
        );

        $this->createMhisVarianceTestApproval($f, $claim);

        $journal = app(MhisReceivableAccountingService::class)
            ->recognize($claim);

        $this->assertSame('posted', $journal->fresh()->status);
        $this->assertCount(3, $journal->lines);

        $lines = $this->amounts($journal);

        $this->assertSame(
            ['dr' => '6000.00', 'cr' => '0.00'],
            $lines['1210']
        );

        $this->assertSame(
            ['dr' => '0.00', 'cr' => '4000.00'],
            $lines['1200']
        );

        $this->assertSame(
            ['dr' => '0.00', 'cr' => '2000.00'],
            $lines['4391']
        );

        $this->assertEquals(
            6000,
            $journal->lines()->sum('debit')
        );

        $this->assertEquals(
            6000,
            $journal->lines()->sum('credit')
        );
        $this->assertMhisVarianceTallyXml($journal, [
            'MHIS Receivables' => '-6000.00',
            'Patient Receivables' => '4000.00',
            'MHIS Package Differential Revenue' => '2000.00',
        ]);

    }

    public function test_mhis_free_package_variance_below_bill(): void
    {
        [$f, $claim] = $this->setupMhisVariance(
            '10000.00',
            '8000.00'
        );

        $this->createMhisVarianceTestApproval($f, $claim);

        $journal = app(MhisReceivableAccountingService::class)
            ->recognize($claim);

        $this->assertSame('posted', $journal->fresh()->status);
        $this->assertCount(3, $journal->lines);

        $lines = $this->amounts($journal);

        $this->assertSame(
            ['dr' => '8000.00', 'cr' => '0.00'],
            $lines['1210']
        );

        $this->assertSame(
            ['dr' => '0.00', 'cr' => '10000.00'],
            $lines['1200']
        );

        $this->assertSame(
            ['dr' => '2000.00', 'cr' => '0.00'],
            $lines['4392']
        );

        $this->assertEquals(
            10000,
            $journal->lines()->sum('debit')
        );

        $this->assertEquals(
            10000,
            $journal->lines()->sum('credit')
        );
        $this->assertMhisVarianceTallyXml($journal, [
            'MHIS Receivables' => '-8000.00',
            'Patient Receivables' => '10000.00',
            'MHIS Contractual Allowances' => '-2000.00',
        ]);

    }

    public function test_mhis_package_variance_receipt_with_tds(): void
    {
        [$f, $claim] = $this->setupMhisVariance(
            '4000.00',
            '6000.00'
        );

        foreach ([
            '1130' => ['MHIS Bank', 'debit'],
            '1220' => ['MHIS TDS Receivable', 'debit'],
        ] as $code => [$name, $normal]) {
            $gl = GeneralLedgerAccount::firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => $name,
                    'account_type' => 'asset',
                    'account_group' => 'Current Assets',
                    'normal_balance' => $normal,
                    'is_active' => true,
                    'allow_posting' => true,
                ]
            );

            $this->assertTrue((bool) $gl->is_active);
            $this->assertTrue((bool) $gl->allow_posting);
        }

        $this->createMhisVarianceTestApproval($f, $claim);

        $recognition = app(MhisReceivableAccountingService::class)
            ->recognize($claim);

        $this->assertSame('posted', $recognition->status);
        $this->assertCount(3, $recognition->lines);

        $receipt = \App\Models\IpBillingMhisReceipt::create([
            'ip_billing_mhis_claim_id' => $claim->id,
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'receipt_date' => now(),
            'amount' => '6000.00',
            'tds_amount' => '120.00',
            'bank_reference' => 'TEST-MHIS-'.$f['k'],
            'status' => 'active',
            'received_by' => $f['user']->id,
        ]);

        // Full reimbursement requires the claim to be settled.
        $claim->update(['status' => 'settled']);

        $journal = app(
            \App\Services\Accounting\MhisReceiptAccountingService::class
        )->record($receipt);

        $this->assertSame('posted', $journal->fresh()->status);

        $lines = $this->amounts($journal);

        $this->assertSame(
            ['dr' => '5880.00', 'cr' => '0.00'],
            $lines['1130']
        );

        $this->assertSame(
            ['dr' => '120.00', 'cr' => '0.00'],
            $lines['1220']
        );

        $this->assertSame(
            ['dr' => '0.00', 'cr' => '6000.00'],
            $lines['1210']
        );

        $this->assertEquals(
            6000,
            $journal->lines()->sum('debit')
        );

        $this->assertEquals(
            6000,
            $journal->lines()->sum('credit')
        );

        $this->assertSame(
            1,
            JournalEntry::query()
                ->where('source_type', 'ip_billing_mhis_receipt')
                ->where('source_id', (string) $receipt->id)
                ->where('source_event', 'received')
                ->count()
        );
    }

    public function test_finalized_bed_bill_posts_correct_receivable_and_revenue_once(): void
    {
        $f=$this->setupBill();
        $before=DB::table('tally_exports')->count();
        $j=app(IpBillingAccountingService::class)->postFinalBill($f['account']);
        $this->assertSame('posted', $j->fresh()->status);
        $this->assertSame(['dr'=>'1000.00','cr'=>'0.00'], $this->amounts($j)['1200']);
        $this->assertSame(['dr'=>'0.00','cr'=>'1000.00'], $this->amounts($j)['4301']);
        $this->assertSame($j->id, app(IpBillingAccountingService::class)->postFinalBill($f['account'])->id);
        $this->assertSame(1, JournalEntry::where('source_type','ip_billing_account')->where('source_id',(string)$f['account']->id)->where('source_event','finalized')->count());
        $this->assertSame($before, DB::table('tally_exports')->count());
    }

    public function test_mhis_approved_amount_transfers_patient_receivable_once_and_preserves_copay(): void
    {
        $f=$this->setupBill();
        app(IpBillingAccountingService::class)->postFinalBill($f['account']);
        $claim=IpBillingMhisClaim::create([
            'ip_billing_account_id'=>$f['account']->id, 'admission_id'=>$f['admission']->id,
            'patient_id'=>$f['patient']->id, 'claim_no'=>'MHIS-'.$f['k'],
            'status'=>'approved', 'claim_amount'=>'750.00', 'approved_amount'=>'750.00',
            'approval_date'=>now()->toDateString(), 'created_by'=>$f['user']->id,
        ]);
        $before=DB::table('tally_exports')->count();
        $j=app(MhisReceivableAccountingService::class)->recognize($claim);
        $this->assertSame('posted', $j->fresh()->status);
        $this->assertSame(['dr'=>'750.00','cr'=>'0.00'], $this->amounts($j)['1210']);
        $this->assertSame(['dr'=>'0.00','cr'=>'750.00'], $this->amounts($j)['1200']);
        $this->assertEquals(250, 1000-750, 'Patient copay remaining, in rupees');
        $this->assertSame($before, DB::table('tally_exports')->count());
        try {
            app(MhisReceivableAccountingService::class)->recognize($claim);
            $this->fail('Duplicate MHIS recognition was allowed');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('already has a recognition journal', $e->getMessage());
        }
        $this->assertSame(1, JournalEntry::where('source_type','ip_billing_mhis_claim')->where('source_id',(string)$claim->id)->where('source_event','approved')->count());
    }

    public function test_final_bill_charge_mismatch_is_rejected_without_journal(): void
    {
        $f=$this->setupBill();
        IpBillingCharge::where('ip_billing_account_id',$f['account']->id)->update(['amount'=>'950.00']);
        try {
            app(IpBillingAccountingService::class)->postFinalBill($f['account']);
            $this->fail('Mismatched bill was accepted');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('do not reconcile', $e->getMessage());
        }
        $this->assertSame(0, JournalEntry::where('source_type','ip_billing_account')->where('source_id',(string)$f['account']->id)->count());
    }
}
