<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\TallyExport;
use App\Models\User;
use App\Services\Accounting\InvoiceAccountingService;
use App\Services\Accounting\PaymentAccountingService;
use App\Services\Tally\TallyJournalExportPreflightService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TallyOpdReceiptDependencyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_opd_receipt_requires_confirmed_sales(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->actingAs($user);

        foreach ([
            ['1200', 'Patient Receivables', 'asset', 'debit'],
            ['4101', 'OPD Consultation Revenue', 'income', 'credit'],
            ['4102', 'OPD Registration Revenue', 'income', 'credit'],
            ['1100', 'Main Cash', 'asset', 'debit'],
        ] as [$code, $name, $type, $normal]) {
            $account = GeneralLedgerAccount::firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => $name,
                    'account_type' => $type,
                    'account_group' => 'Acceptance Testing',
                    'normal_balance' => $normal,
                    'is_active' => true,
                    'allow_posting' => true,
                ]
            );

            $account->update([
                'is_active' => true,
                'allow_posting' => true,
            ]);
        }

        $patient = Patient::create([
            'uhid' => 'TALLY-DEPENDENCY-'.Str::random(8),
            'first_name' => 'Tally',
            'last_name' => 'Dependency',
            'sex' => 'Male',
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'invoice_no' => 'TEST-INV-'.Str::random(12),
            'patient_id' => $patient->id,
            'invoice_type' => 'OPD',
            'invoice_date' => now(),
            'subtotal' => '1000.00',
            'discount' => '0.00',
            'total_amount' => '1000.00',
            'paid_amount' => '0.00',
            'balance_amount' => '1000.00',
            'status' => 'unpaid',
            'created_by' => $user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'code' => 'OPD-CONS',
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => '1000.00',
            'discount' => '0.00',
            'amount' => '1000.00',
        ]);

        $sales = app(InvoiceAccountingService::class)
            ->postOpdInvoice($invoice);

        $payment = Payment::create([
            'receipt_no' => 'TEST-RCP-'.Str::random(12),
            'invoice_id' => $invoice->id,
            'patient_id' => $patient->id,
            'payment_date' => now(),
            'amount' => '1000.00',
            'payment_mode' => 'cash',
            'received_by' => $user->id,
        ]);

        $receipt = app(PaymentAccountingService::class)
            ->postReceipt($payment);

        $preflight = app(TallyJournalExportPreflightService::class);

        // Sales not exported: receipt must be blocked.
        $this->assertNotNull($preflight->failure($receipt));

        // An uncertain Sales export must not unlock receipt.
        $export = TallyExport::create([
            'journal_entry_id' => $sales->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-'.Str::uuid(),
            'remote_id' => (string) Str::uuid(),
        ]);

        $this->assertNotNull($preflight->failure($receipt));

        // Only confirmed Sales permits receipt.
        $export->update(['status' => 'confirmed']);

        $this->assertNull($preflight->failure($receipt));

        // No Receipt export should have been created.
        $this->assertDatabaseMissing('tally_exports', [
            'journal_entry_id' => $receipt->id,
        ]);
    }
}
