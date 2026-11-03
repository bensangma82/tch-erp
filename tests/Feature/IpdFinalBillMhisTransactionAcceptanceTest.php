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
