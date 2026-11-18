<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\GeneralLedgerAccount;
use App\Models\HospitalExpense;
use App\Models\JournalEntry;
use App\Models\Supplier;
use App\Models\TallyExport;
use App\Models\TallyLedgerMapping;
use App\Models\TallyLedgerMaster;
use App\Models\User;
use App\Services\Accounting\HospitalExpensePaymentService;
use App\Services\Accounting\HospitalExpensePostingService;
use App\Services\Tally\TallyHospitalExpenseXmlBuilder;
use App\Services\Tally\TallyHospitalSupplierPaymentXmlBuilder;
use App\Services\Tally\TallyJournalExportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CapitalSupplierWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('testing', DB::connection()->getDatabaseName());
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));
        config(['tally.company' => 'Capital workflow test company']);
    }

    public function test_capital_posting_payment_balance_and_tally_bill_link(): void
    {
        [$expense, $supplier, $asset, $payable, $bank] = $this->invoice();
        $accrual = app(HospitalExpensePostingService::class)
            ->post($expense, (int) auth()->id(), $supplier->id);
        $expense->refresh();

        $this->assertSame('posted', $expense->status);
        $this->assertEquals($supplier->id, $expense->supplier_id);
        $this->assertEquals(45430, $accrual->lines->where('general_ledger_account_id', $asset->id)->sum('debit'));
        $this->assertEquals(45430, $accrual->lines->where('general_ledger_account_id', $payable->id)->sum('credit'));
        $xml = app(TallyHospitalExpenseXmlBuilder::class)->build($accrual);
        $this->assertStringContainsString('<BILLTYPE>New Ref</BILLTYPE>', $xml);
        $this->assertStringContainsString('<NAME>'.$expense->expense_no.'</NAME>', $xml);

        $payments = app(HospitalExpensePaymentService::class);
        $payment = $payments->pay(
            $expense, $bank, today()->toDateString(), '45000.00', 'bank_transfer', 'CAP-UTR-01', null, auth()->id()
        );
        $this->assertSame('430.00', $payments->outstanding($expense));
        try {
            $payments->pay(
                $expense, $bank, today()->toDateString(), '100.00', 'bank_transfer', 'cap-utr-01', null, auth()->id()
            );
            $this->fail('An existing settlement reference was accepted again.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reference_no', $e->errors());
        }
        $this->assertSame('430.00', $payments->outstanding($expense));
        $journal = JournalEntry::where('source_type', 'hospital_expense_payment')
            ->where('source_id', (string) $payment->id)->firstOrFail();
        $this->assertEquals(45000, $journal->lines->where('general_ledger_account_id', $payable->id)->sum('debit'));
        $this->assertEquals(45000, $journal->lines->where('general_ledger_account_id', $bank->general_ledger_account_id)->sum('credit'));

        $eligibility = app(TallyJournalExportService::class);
        $this->assertFalse($eligibility->checkEligibility($journal)['eligible']);
        $this->assertContains(
            'Export and confirm the capital purchase journal in Tally before sending this payment.',
            $eligibility->checkEligibility($journal)['errors']
        );
        TallyExport::create(['journal_entry_id' => $accrual->id, 'status' => 'confirmed', 'confirmed_at' => now()]);

        $this->assertTrue($eligibility->checkEligibility($journal)['eligible']);
        $xml = app(TallyHospitalSupplierPaymentXmlBuilder::class)->build($journal);
        $this->assertStringContainsString('<BILLTYPE>Agst Ref</BILLTYPE>', $xml);
        $this->assertStringContainsString('<NAME>'.$expense->expense_no.'</NAME>', $xml);
    }

    public function test_capital_posts_require_explicit_nonrecoverable_tax_confirmation(): void
    {
        [$expense, $supplier] = $this->invoice();
        $expense->update(['subtotal' => '45000.00', 'tax_amount' => '430.00']);

        try {
            app(HospitalExpensePostingService::class)->post($expense, (int) auth()->id(), $supplier->id);
            $this->fail('Unconfirmed capital invoice tax was posted.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('non-recoverable', $e->errors()['expense'][0]);
        }

        $this->assertSame('approved', $expense->fresh()->status);
        $this->assertNull($expense->fresh()->journal_entry_id);
    }

    private function invoice(): array
    {
        $suffix = strtoupper(bin2hex(random_bytes(4)));
        $company = config('tally.company');
        $head = FinanceHead::create([
            'code' => 'CAP-'.$suffix, 'name' => 'Test medical equipment', 'head_type' => 'expense', 'is_active' => true,
        ]);
        $department = Department::create(['name' => 'Capital test '.$suffix, 'code' => 'CAP'.$suffix, 'is_active' => true]);
        $supplier = Supplier::create([
            'supplier_code' => 'CAP-'.$suffix, 'supplier_name' => 'Capital supplier '.$suffix, 'is_active' => true,
        ]);
        $asset = $this->ledger('CA-'.$suffix, 'asset', 'Fixed Assets', 'Equipment '.$suffix);
        $payable = $this->ledger('2200', 'liability', 'Current Liabilities', 'Accounts Payable');
        $bankGl = $this->ledger('CB-'.$suffix, 'asset', 'Bank Accounts', 'Bank '.$suffix);
        $bank = FinanceAccount::create([
            'code' => 'BANK-'.$suffix, 'name' => 'Capital test bank '.$suffix, 'account_type' => 'bank',
            'general_ledger_account_id' => $bankGl->id, 'is_active' => true,
        ]);
        $bankGl->update(['finance_account_id' => $bank->id]);

        TallyLedgerMaster::create([
            'tally_company' => $company, 'ledger_name' => $supplier->supplier_name,
            'parent_group' => 'Sundry Creditors', 'last_synced_at' => now(),
        ]);
        foreach ([$asset, $payable, $bankGl] as $ledger) {
            TallyLedgerMaster::create([
                'tally_company' => $company, 'ledger_name' => $ledger->tally_ledger_name,
                'parent_group' => $ledger->account_group, 'last_synced_at' => now(),
            ]);
        }
        TallyLedgerMapping::create([
            'supplier_id' => $supplier->id, 'tally_company' => $company, 'tally_ledger_name' => $supplier->supplier_name,
            'tally_group_name' => 'Sundry Creditors', 'is_active' => true,
        ]);
        $expense = new HospitalExpense([
            'expense_no' => 'HE-CAP-'.$suffix, 'expense_type' => 'capital', 'expense_classification' => 'clinical',
            'expense_date' => today(), 'accounting_date' => today(), 'supplier_name' => $supplier->supplier_name,
            'invoice_no' => 'INV-CAP-'.$suffix, 'description' => 'Test ventilator', 'finance_head_id' => $head->id,
            'general_ledger_account_id' => $asset->id, 'subtotal' => '45430.00', 'tax_amount' => '0.00',
            'total_amount' => '45430.00',
        ]);
        $expense->status = 'approved';
        $expense->save();
        $expense->allocations()->create([
            'department_id' => $department->id, 'percentage' => 100, 'allocated_amount' => '45430.00',
        ]);

        return [$expense, $supplier, $asset, $payable, $bank];
    }

    private function ledger(string $code, string $type, string $group, string $name): GeneralLedgerAccount
    {
        return GeneralLedgerAccount::updateOrCreate(['account_code' => $code], [
            'account_name' => $name, 'account_type' => $type, 'account_group' => $group,
            'normal_balance' => $type === 'liability' ? 'credit' : 'debit',
            'is_active' => true, 'allow_posting' => true, 'tally_ledger_name' => $name,
        ]);
    }
}
