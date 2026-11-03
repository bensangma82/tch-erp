<?php

namespace Tests\Feature;

use App\Models\{Admission, GeneralLedgerAccount, Invoice, InvoiceItem, IpBillingAccount, IpBillingCharge, IpBillingMhisClaim, JournalEntry, Patient, Payment, User};
use App\Services\Accounting\{InvoiceAccountingService, PaymentAccountingService, IpBillingAccountingService, MhisReceivableAccountingService};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PatientJourneyAccountingAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'pgsql' || DB::connection()->getDatabaseName() !== 'testing') {
            throw new \RuntimeException('Refusing financial journey tests outside PostgreSQL testing database.');
        }
    }

    private function ensureGl(string $code, string $name, string $type, string $normal): void
    {
        $account = GeneralLedgerAccount::where('account_code', $code)->first();
        if ($account) {
            $this->assertTrue((bool) $account->is_active, "GL {$code} must be active in testing");
            $this->assertTrue((bool) $account->allow_posting, "GL {$code} must be postable in testing");
            return;
        }
        GeneralLedgerAccount::create([
            'account_code' => $code, 'account_name' => $name,
            'account_type' => $type, 'account_group' => 'Journey Acceptance',
            'normal_balance' => $normal, 'is_active' => true, 'allow_posting' => true,
        ]);
    }

    private function amounts(JournalEntry $journal): array
    {
        $totals = [];
        foreach ($journal->lines()->with('generalLedgerAccount')->get() as $line) {
            $code = (string) $line->generalLedgerAccount->account_code;
            $totals[$code] = ['dr' => (float) $line->debit, 'cr' => (float) $line->credit];
        }
        $this->assertEqualsWithDelta(
            (float) $journal->lines()->sum('debit'),
            (float) $journal->lines()->sum('credit'),
            0.001,
            'Unbalanced journal '.$journal->journal_no
        );
        $this->assertSame('posted', $journal->fresh()->status);
        return $totals;
    }

    public function test_one_patient_opd_receipt_then_ipd_final_bill_and_mhis_are_linked_and_balanced(): void
    {
        $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($user);
        foreach ([
            ['1100','Main Cash','asset','debit'],
            ['1200','Patient Receivables','asset','debit'],
            ['1210','MHIS Receivables','asset','debit'],
            ['4101','OPD Consultation Revenue','income','credit'],
            ['4102','OPD Registration Revenue','income','credit'],
            ['4301','IPD Services Revenue','income','credit'],
        ] as [$code,$name,$type,$normal]) {
            $this->ensureGl($code,$name,$type,$normal);
        }
        $key = 'JOURNEY-'.strtoupper(bin2hex(random_bytes(5)));
        $patient = Patient::create([
            'uhid' => $key, 'first_name' => 'Journey', 'last_name' => 'Acceptance',
            'sex' => 'male', 'is_active' => true,
        ]);
        $invoice = Invoice::create([
            'invoice_no' => 'OPD-'.$key, 'patient_id' => $patient->id, 'invoice_type' => 'OPD',
            'invoice_date' => now(), 'subtotal' => '300.00', 'discount' => '0.00',
            'total_amount' => '300.00', 'paid_amount' => '0.00', 'balance_amount' => '300.00',
            'status' => 'unpaid', 'created_by' => $user->id,
        ]);
        foreach ([['OPD-CONS','200.00'], ['OPD-REG','100.00']] as [$code,$amount]) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id, 'code' => $code, 'description' => $code,
                'quantity' => 1, 'unit_price' => $amount, 'discount' => '0.00', 'amount' => $amount,
            ]);
        }
        $exportsBefore = DB::table('tally_exports')->count();
        $opd = app(InvoiceAccountingService::class)->postOpdInvoice($invoice);
        $payment = Payment::create([
            'receipt_no' => 'RCP-'.$key, 'invoice_id' => $invoice->id,
            'patient_id' => $patient->id, 'payment_date' => now(),
            'amount' => '300.00', 'payment_mode' => 'cash', 'received_by' => $user->id,
        ]);
        $receipt = app(PaymentAccountingService::class)->postReceipt($payment);
        $this->assertEqualsWithDelta(300, $this->amounts($opd)['1200']['dr'], .001);
        $this->assertEqualsWithDelta(300, $this->amounts($receipt)['1200']['cr'], .001);
        $this->assertEqualsWithDelta(300, $this->amounts($receipt)['1100']['dr'], .001);

        $admission = Admission::create([
            'admission_no' => 'IPD-'.$key, 'patient_id' => $patient->id,
            'admitted_at' => now(), 'status' => 'admitted',
        ]);
        $account = IpBillingAccount::create([
            'account_no' => 'ACC-'.$key, 'admission_id' => $admission->id,
            'patient_id' => $patient->id, 'opened_at' => now(), 'status' => 'finalized',
            'subtotal' => '1000.00', 'net_amount' => '1000.00',
            'paid_amount' => '0.00', 'balance_amount' => '1000.00',
            'final_bill_no' => 'FIN-'.$key, 'finalized_at' => now(),
        ]);
        IpBillingCharge::create([
            'ip_billing_account_id' => $account->id, 'admission_id' => $admission->id,
            'charge_date' => now(), 'charge_type' => 'bed', 'description' => 'Room charges',
            'quantity' => '1.00', 'unit_price' => '1000.00', 'amount' => '1000.00',
            'discount' => '0.00', 'status' => 'active', 'created_by' => $user->id,
        ]);
        $ipd = app(IpBillingAccountingService::class)->postFinalBill($account);
        $claim = IpBillingMhisClaim::create([
            'ip_billing_account_id' => $account->id, 'admission_id' => $admission->id,
            'patient_id' => $patient->id, 'claim_no' => 'MHIS-'.$key,
            'status' => 'approved', 'claim_amount' => '750.00', 'approved_amount' => '750.00',
            'approval_date' => now()->toDateString(), 'created_by' => $user->id,
        ]);
        $mhis = app(MhisReceivableAccountingService::class)->recognize($claim);
        $this->assertEqualsWithDelta(1000, $this->amounts($ipd)['1200']['dr'], .001);
        $this->assertEqualsWithDelta(1000, $this->amounts($ipd)['4301']['cr'], .001);
        $this->assertEqualsWithDelta(750, $this->amounts($mhis)['1200']['cr'], .001);
        $this->assertEqualsWithDelta(750, $this->amounts($mhis)['1210']['dr'], .001);
        $this->assertSame($patient->id, $invoice->fresh()->patient_id);
        $this->assertSame($patient->id, $admission->fresh()->patient_id);
        $this->assertSame($patient->id, $account->fresh()->patient_id);
        $this->assertSame($admission->id, $account->fresh()->admission_id);
        $this->assertSame($account->id, $claim->fresh()->ip_billing_account_id);
        $this->assertEqualsWithDelta(250, 1000 - 750, .001, 'Uncollected IPD patient share');
        $this->assertSame($opd->id, app(InvoiceAccountingService::class)->postOpdInvoice($invoice)->id);
        $this->assertSame($receipt->id, app(PaymentAccountingService::class)->postReceipt($payment)->id);
        $this->assertSame($ipd->id, app(IpBillingAccountingService::class)->postFinalBill($account)->id);
        $this->assertSame($exportsBefore, DB::table('tally_exports')->count());
    }
}
