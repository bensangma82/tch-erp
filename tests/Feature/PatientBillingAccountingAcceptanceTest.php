<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\Accounting\InvoiceAccountingService;
use App\Services\Accounting\PaymentAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PatientBillingAccountingAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Fail closed before this suite writes anything. Do not use migrate:fresh.
        if (DB::connection()->getDatabaseName() !== 'testing') {
            throw new \RuntimeException('Refusing patient billing tests outside database testing.');
        }
    }

    private function account(string $code, string $name, string $type, string $normal): void
    {
        $account = GeneralLedgerAccount::where('account_code', $code)->first();
        if ($account) {
            $account->update(['is_active' => true, 'allow_posting' => true]);
            return;
        }
        GeneralLedgerAccount::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'account_group' => 'Acceptance Testing',
            'normal_balance' => $normal,
            'is_active' => true,
            'allow_posting' => true,
        ]);
    }

    private function invoice(): Invoice
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]));
        $this->account('1200', 'Patient Receivables', 'asset', 'debit');
        $this->account('4101', 'OPD Consultation Revenue', 'income', 'credit');
        $this->account('4102', 'OPD Registration Revenue', 'income', 'credit');
        $patient = Patient::create([
            'uhid' => 'BILL-TEST-'.uniqid(),
            'first_name' => 'Accounting',
            'last_name' => 'Test',
            'sex' => 'Male',
            'is_active' => true,
        ]);
        $invoice = Invoice::create([
            'invoice_no' => 'TEST-INV-'.uniqid(),
            'patient_id' => $patient->id,
            'invoice_type' => 'OPD',
            'invoice_date' => now(),
            'subtotal' => '1000.00',
            'discount' => '0.00',
            'total_amount' => '1000.00',
            'paid_amount' => '0.00',
            'balance_amount' => '1000.00',
            'status' => 'unpaid',
            'created_by' => auth()->id(),
        ]);
        foreach ([['OPD-CONS', '800.00'], ['OPD-REG', '200.00']] as [$code, $amount]) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'code' => $code,
                'description' => $code,
                'quantity' => 1,
                'unit_price' => $amount,
                'discount' => '0.00',
                'amount' => $amount,
            ]);
        }
        return $invoice;
    }

    public function test_opd_invoice_posts_balanced_revenue_and_is_idempotent(): void
    {
        $invoice = $this->invoice();
        $beforeExport = DB::table('tally_exports')->count();
        $service = app(InvoiceAccountingService::class);
        $first = $service->postOpdInvoice($invoice);
        $again = $service->postOpdInvoice($invoice);
        $this->assertSame($first->id, $again->id);
        $this->assertSame('posted', $first->fresh()->status);
        $lines = $first->lines()->get();
        $this->assertEqualsWithDelta(1000, (float)$lines->sum('debit'), 0.001);
        $this->assertEqualsWithDelta(1000, (float)$lines->sum('credit'), 0.001);
        $this->assertSame(3, $lines->count());
        $this->assertSame($beforeExport, DB::table('tally_exports')->count());
    }

    public function test_cash_upi_and_card_receipts_post_once_and_balance(): void
    {
        foreach (['cash' => '1100', 'upi' => '1110', 'card' => '1120'] as $mode => $code) {
            $this->account($code, strtoupper($mode).' Settlement', 'asset', 'debit');
            $invoice = $this->invoice();
            app(InvoiceAccountingService::class)->postOpdInvoice($invoice);
            $payment = Payment::create([
                'receipt_no' => 'TEST-RCP-'.uniqid(),
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'payment_date' => now(),
                'amount' => '1000.00',
                'payment_mode' => $mode,
                'received_by' => auth()->id(),
            ]);
            $beforeExport = DB::table('tally_exports')->count();
            $service = app(PaymentAccountingService::class);
            $first = $service->postReceipt($payment);
            $again = $service->postReceipt($payment);
            $this->assertSame($first->id, $again->id);
            $this->assertSame('posted', $first->fresh()->status);
            $lines = $first->lines()->get();
            $this->assertEqualsWithDelta(1000, (float)$lines->sum('debit'), 0.001);
            $this->assertEqualsWithDelta(1000, (float)$lines->sum('credit'), 0.001);
            $this->assertSame($code, (string)GeneralLedgerAccount::findOrFail($lines->first()->general_ledger_account_id)->account_code);
            $this->assertSame($beforeExport, DB::table('tally_exports')->count());
        }
    }

    public function test_receipt_without_posted_invoice_is_rejected(): void
    {
        $invoice = $this->invoice();
        $this->account('1100', 'Main Cash', 'asset', 'debit');
        $payment = Payment::create([
            'receipt_no' => 'TEST-RCP-'.uniqid(),
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
            'payment_date' => now(),
            'amount' => '1000.00',
            'payment_mode' => 'cash',
            'received_by' => auth()->id(),
        ]);
        $this->expectException(ValidationException::class);
        app(PaymentAccountingService::class)->postReceipt($payment);
    }

    public function test_guest_cannot_access_billing_routes(): void
    {
        auth()->logout();
        foreach (['billing.index', 'patients.index'] as $route) {
            $this->get(route($route))->assertRedirect();
        }
    }
}
