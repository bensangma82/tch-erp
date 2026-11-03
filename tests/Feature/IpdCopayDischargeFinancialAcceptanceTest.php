<?php

namespace Tests\Feature;

use App\Models\{Admission, DischargeSummary, GeneralLedgerAccount, IpBillingAccount, IpBillingCharge, IpBillingMhisClaim, IpBillingPayment, JournalEntry, Patient, User};
use App\Services\Accounting\{IpBillingAccountingService, IpPaymentAccountingService, MhisReceivableAccountingService};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class IpdCopayDischargeFinancialAcceptanceTest extends TestCase
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


    private function approvedMhisAccount(): array
    {
        $f = $this->setupBill();
        $f['account']->update(['mhis_category' => 'semi_private']);
        app(IpBillingAccountingService::class)->postFinalBill($f['account']);
        $f['claim'] = IpBillingMhisClaim::create([
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'claim_no' => 'MHIS-'.$f['k'],
            'status' => 'approved',
            'claim_amount' => '750.00',
            'approved_amount' => '750.00',
            'approval_date' => now()->toDateString(),
            'created_by' => $f['user']->id,
        ]);
        app(MhisReceivableAccountingService::class)->recognize($f['claim']);
        foreach (['1100' => 'Main Cash', '1110' => 'UPI Settlement Clearing', '1120' => 'Card Settlement Clearing'] as $code => $name) {
            $gl = GeneralLedgerAccount::firstOrCreate(['account_code'=>$code], [
                'account_name'=>$name, 'account_type'=>'asset',
                'account_group'=>'Current Assets', 'normal_balance'=>'debit',
                'is_active'=>true, 'allow_posting'=>true,
            ]);
            $this->assertTrue((bool) $gl->is_active);
            $this->assertTrue((bool) $gl->allow_posting);
        }
        return $f;
    }

    public function test_private_mhis_copayment_posts_once_and_reconciles_gl(): void
    {
        $f = $this->approvedMhisAccount();
        $beforeExports = DB::table('tally_exports')->count();
        $payment = IpBillingPayment::create([
            'ip_billing_account_id'=>$f['account']->id,
            'admission_id'=>$f['admission']->id,
            'patient_id'=>$f['patient']->id,
            'receipt_no'=>'COPAY-'.$f['k'],
            'payment_date'=>now(), 'amount'=>'250.00',
            'payment_mode'=>'cash', 'status'=>'active',
            'received_by'=>$f['user']->id,
        ]);
        $journal = app(IpPaymentAccountingService::class)->postReceipt($payment);
        $this->assertSame('posted', $journal->fresh()->status);
        $this->assertSame($journal->id, app(IpPaymentAccountingService::class)->postReceipt($payment)->id);
        $lines = $journal->lines()->with('generalLedgerAccount')->get();
        $this->assertSame('250.00', number_format((float)$lines->sum('debit'), 2, '.', ''));
        $this->assertSame('250.00', number_format((float)$lines->sum('credit'), 2, '.', ''));
        $byCode = $lines->keyBy(fn($line) => (string)$line->generalLedgerAccount->account_code);
        $this->assertEquals(250, (float)$byCode['1100']->debit);
        $this->assertEquals(250, (float)$byCode['1200']->credit);
        $this->assertSame(1, JournalEntry::where('source_type','ip_billing_payment')
            ->where('source_id',(string)$payment->id)->where('source_event','received')->count());
        $this->assertSame($beforeExports, DB::table('tally_exports')->count());
    }

    public function test_discharge_record_and_financial_journals_reconcile_for_same_admission(): void
    {
        $f = $this->approvedMhisAccount();
        $summary = DischargeSummary::create([
            'admission_id'=>$f['admission']->id,
            'final_diagnosis'=>'Acceptance test diagnosis',
            'status'=>'finalised',
            'finalised_by'=>$f['user']->id,
            'finalised_at'=>now(),
        ]);
        $this->assertSame($f['admission']->id, $summary->admission->id);
        $this->assertSame($f['admission']->patient_id, $f['account']->patient_id);
        $this->assertSame('finalised', $summary->fresh()->status);
        $bill = JournalEntry::where('source_type','ip_billing_account')
            ->where('source_id',(string)$f['account']->id)->where('source_event','finalized')->firstOrFail();
        $claim = JournalEntry::where('source_type','ip_billing_mhis_claim')
            ->where('source_id',(string)$f['claim']->id)->where('source_event','approved')->firstOrFail();
        $this->assertSame('posted', $bill->status);
        $this->assertSame('posted', $claim->status);
        $this->assertEquals(1000, $bill->lines()->sum('debit'));
        $this->assertEquals(750, $claim->lines()->sum('debit'));
        $this->assertEquals(250, (float)$f['account']->net_amount - (float)$f['claim']->approved_amount);
    }
}
