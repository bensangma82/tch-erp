<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\PharmacyGrn;
use App\Models\PharmacyPurchaseOrder;
use App\Models\PharmacySupplier;
use App\Models\User;
use App\Services\Accounting\PharmacyAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PharmacyAccountingIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);
    }

    public function test_grn_posts_balanced_inventory_and_supplier_liability_once(): void
    {
        config(['pharmacy_accounting.input_gst_recoverable' => false]);
        $this->makeAccount('1300', 'Medicine Inventory', 'asset', 'debit');
        $this->makeAccount('2200', 'Accounts Payable', 'liability', 'credit');

        $grn = $this->makeGrn();
        $service = app(PharmacyAccountingService::class);

        $first = $service->postGrn($grn);
        $second = $service->postGrn($grn);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('posted', $first->status);
        $this->assertSame(1, JournalEntry::query()
            ->where('source_type', 'pharmacy_grn')
            ->where('source_id', (string) $grn->id)
            ->where('source_event', 'received')
            ->count());

        $lines = $first->lines()->with('generalLedgerAccount')->get();
        $this->assertSame(11800, (int) round((float) $lines->sum('debit') * 100));
        $this->assertSame(11800, (int) round((float) $lines->sum('credit') * 100));
        $this->assertSame('1300', $lines->firstWhere('generalLedgerAccount.account_code', '1300')->generalLedgerAccount->account_code);
    }

    public function test_grn_posts_input_gst_separately_only_when_credit_is_enabled(): void
    {
        config(['pharmacy_accounting.input_gst_recoverable' => true]);
        $this->makeAccount('1300', 'Medicine Inventory', 'asset', 'debit');
        $this->makeAccount('2200', 'Accounts Payable', 'liability', 'credit');
        $this->makeAccount('2301', 'Input CGST Receivable', 'asset', 'debit');
        $this->makeAccount('2302', 'Input SGST Receivable', 'asset', 'debit');
        $this->makeAccount('2303', 'Input IGST Receivable', 'asset', 'debit');

        $grn = $this->makeGrn();
        $journal = app(PharmacyAccountingService::class)->postGrn($grn);
        $lines = $journal->lines()->with('generalLedgerAccount')->get()
            ->keyBy(fn ($line) => $line->generalLedgerAccount->account_code);

        $this->assertSame('100.00', $lines['1300']->debit);
        $this->assertSame('9.00', $lines['2301']->debit);
        $this->assertSame('9.00', $lines['2302']->debit);
        $this->assertSame('118.00', $lines['2200']->credit);
    }

    private function makeAccount(string $code, string $name, string $type, string $normalBalance): GeneralLedgerAccount
    {
        return GeneralLedgerAccount::query()->firstOrCreate(
            ['account_code' => $code],
            [
                'account_name' => $name,
                'account_type' => $type,
                'account_group' => $type === 'asset' ? 'Current Assets' : ($type === 'liability' ? 'Current Liabilities' : 'Clinical Operating Expenses'),
                'normal_balance' => $normalBalance,
                'is_active' => true,
                'allow_posting' => true,
            ]
        );
    }

    private function makeGrn(): PharmacyGrn
    {
        $suffix = Str::upper(Str::random(8));
        $supplier = PharmacySupplier::create([
            'code' => 'TEST-SUP-'.$suffix,
            'name' => 'Accounting Test Supplier '.$suffix,
        ]);

        $purchaseOrder = PharmacyPurchaseOrder::create([
            'po_no' => 'TEST-PO-'.$suffix,
            'pharmacy_supplier_id' => $supplier->id,
            'po_date' => today(),
            'status' => 'received',
            'created_by' => $this->user->id,
        ]);

        return PharmacyGrn::create([
            'grn_no' => 'TEST-GRN-'.$suffix,
            'pharmacy_purchase_order_id' => $purchaseOrder->id,
            'pharmacy_supplier_id' => $supplier->id,
            'grn_date' => today(),
            'status' => 'completed',
            'taxable_amount' => '100.00',
            'cgst_amount' => '9.00',
            'sgst_amount' => '9.00',
            'igst_amount' => '0.00',
            'total_amount' => '118.00',
            'created_by' => $this->user->id,
        ]);
    }
}
