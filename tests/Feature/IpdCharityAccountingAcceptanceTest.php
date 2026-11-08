<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\CharityAdjustment;
use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Patient;
use App\Models\User;
use App\Services\Accounting\IpCharityAccountingService;
use App\Services\Accounting\JournalPostingService;
use App\Services\CharityAdjustmentService;
use App\Services\Tally\TallyCharityJournalXmlBuilder;
use App\Services\Tally\TallyJournalExportPreflightService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IpdCharityAccountingAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    private function fixture(string $status = 'finalized'): array
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $key = 'CHAR-'.strtoupper(bin2hex(random_bytes(5)));

        $patient = Patient::create([
            'uhid' => $key,
            'first_name' => 'Charity',
            'last_name' => 'Acceptance',
            'sex' => 'male',
            'is_active' => true,
        ]);

        $admission = Admission::create([
            'admission_no' => $key,
            'patient_id' => $patient->id,
            'admitted_at' => now(),
            'status' => 'admitted',
        ]);

        $account = IpBillingAccount::create([
            'account_no' => $key,
            'opened_at' => now(),
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'status' => $status,
            'subtotal' => '3850.00',
            'net_amount' => '3850.00',
            'balance_amount' => '3850.00',
            'paid_amount' => '0.00',
            'final_bill_no' => $status === 'finalized'
                ? 'FIN-'.$key : null,
            'finalized_at' => $status === 'finalized'
                ? now()->subDay() : null,
        ]);

        foreach ([
            '1200' => ['Patient Receivables', 'asset', 'debit'],
            '6114' => ['Charity / Patient Assistance Expense', 'expense', 'debit'],
            '4301' => ['IPD Services Revenue', 'income', 'credit'],
        ] as $code => [$name, $type, $normal]) {
            $gl = GeneralLedgerAccount::firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => $name,
                    'account_type' => $type,
                    'account_group' => $type === 'expense'
                        ? 'Patient Assistance' : 'Clinical Revenue',
                    'normal_balance' => $normal,
                    'is_active' => true,
                    'allow_posting' => true,
                ]
            );

            $this->assertTrue((bool) $gl->is_active);
            $this->assertTrue((bool) $gl->allow_posting);

            $gl->tally_ledger_name = $name;
            $gl->save();
        }

        return compact('user', 'patient', 'admission', 'account', 'key');
    }

    private function postSales(array $f): JournalEntry
    {
        $journal = JournalEntry::create([
            'journal_no' => 'JRN-IPD-'.$f['key'],
            'journal_date' => now()->toDateString(),
            'source_type' => 'ip_billing_account',
            'source_id' => (string) $f['account']->id,
            'source_event' => 'finalized',
            'reference_no' => $f['account']->final_bill_no,
            'status' => 'draft',
            'created_by' => $f['user']->id,
        ]);

        foreach ([
            [1, '1200', '3850.00', '0.00'],
            [2, '4301', '0.00', '3850.00'],
        ] as [$number, $code, $debit, $credit]) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => $number,
                'general_ledger_account_id' =>
                    GeneralLedgerAccount::where('account_code', $code)
                        ->firstOrFail()->id,
                'debit' => $debit,
                'credit' => $credit,
                'reference_no' => $f['account']->final_bill_no,
            ]);
        }

        return app(JournalPostingService::class)->post($journal);
    }

    private function adjustment(array $f, string $amount): CharityAdjustment
    {
        return CharityAdjustment::create([
            'patient_id' => $f['patient']->id,
            'admission_id' => $f['admission']->id,
            'ip_billing_account_id' => $f['account']->id,
            'adjustment_type' => 'charity',
            'requested_amount' => $amount,
            'approved_amount' => $amount,
            'reason' => 'Financial assistance',
            'status' => 'approved',
            'requested_by' => $f['user']->id,
            'approved_by' => $f['user']->id,
            'requested_at' => now(),
            'approved_at' => now(),
        ]);
    }

    private function assertJournal(
        CharityAdjustment $adjustment,
        string $amount
    ): JournalEntry {
        $journal = JournalEntry::where(
            'source_type', 'charity_adjustment'
        )
            ->where('source_id', (string) $adjustment->id)
            ->where('source_event', 'applied')
            ->firstOrFail();

        $this->assertSame('posted', $journal->status);

        $lines = $journal->lines()
            ->with('generalLedgerAccount')
            ->orderBy('line_no')
            ->get();

        $this->assertCount(2, $lines);

        $this->assertSame(
            '6114',
            (string) $lines[0]->generalLedgerAccount->account_code
        );

        $this->assertSame(
            '1200',
            (string) $lines[1]->generalLedgerAccount->account_code
        );

        $this->assertEquals($amount, $lines[0]->debit);
        $this->assertEquals(0, $lines[0]->credit);
        $this->assertEquals(0, $lines[1]->debit);
        $this->assertEquals($amount, $lines[1]->credit);

        return $journal;
    }

    public function test_charity_after_finalization_posts_once(): void
    {
        $f = $this->fixture();
        $this->postSales($f);

        $adjustment = $this->adjustment($f, '3850.00');

        app(CharityAdjustmentService::class)->apply($adjustment);

        $journal = $this->assertJournal($adjustment, '3850.00');

        $this->assertEquals(
            0,
            $f['account']->fresh()->balance_amount
        );

        $again = app(IpCharityAccountingService::class)
            ->post($adjustment->fresh());

        $this->assertSame($journal->id, $again->id);

        $this->assertSame(
            1,
            JournalEntry::where('source_type', 'charity_adjustment')
                ->where('source_id', (string) $adjustment->id)
                ->count()
        );
    }

    public function test_charity_before_finalization_is_deferred(): void
    {
        $f = $this->fixture('open');
        $adjustment = $this->adjustment($f, '1000.00');

        app(CharityAdjustmentService::class)->apply($adjustment);

        $this->assertEquals(
            2850,
            $f['account']->fresh()->balance_amount
        );

        $this->assertSame(
            0,
            JournalEntry::where('source_type', 'charity_adjustment')
                ->where('source_id', (string) $adjustment->id)
                ->count()
        );

        $f['account']->update([
            'status' => 'finalized',
            'final_bill_no' => 'FIN-'.$f['key'],
            'finalized_at' => now(),
        ]);

        $this->postSales($f);

        app(IpCharityAccountingService::class)
            ->postAppliedForAccount($f['account']->fresh());

        $this->assertJournal($adjustment, '1000.00');
    }

    public function test_xml_allocation_and_sales_dependency(): void
    {
        $f = $this->fixture();
        $this->postSales($f);

        $adjustment = $this->adjustment($f, '3850.00');

        app(CharityAdjustmentService::class)->apply($adjustment);

        $journal = $this->assertJournal($adjustment, '3850.00');

        $failure = app(TallyJournalExportPreflightService::class)
            ->failure($journal);

        $this->assertNotNull($failure);
        $this->assertStringContainsString(
            'Confirm IPD Sales',
            $failure
        );

        $xml = app(TallyCharityJournalXmlBuilder::class)
            ->build($journal);

        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML($xml));

        $xpath = new \DOMXPath($document);

        $this->assertSame(
            'Journal',
            $xpath->evaluate('string(//VOUCHERTYPENAME)')
        );

        $this->assertSame(
            $f['account']->final_bill_no,
            $xpath->evaluate('string(//BILLALLOCATIONS.LIST/NAME)')
        );

        $this->assertSame(
            'Agst Ref',
            $xpath->evaluate('string(//BILLALLOCATIONS.LIST/BILLTYPE)')
        );

        $this->assertSame(
            '3850.00',
            $xpath->evaluate('string(//BILLALLOCATIONS.LIST/AMOUNT)')
        );
    }

    public function test_charity_export_requires_confirmed_ipd_sales(): void
    {
        $f = $this->fixture();

        $sales = $this->postSales($f);

        $adjustment = $this->adjustment($f, '3850.00');

        app(CharityAdjustmentService::class)
            ->apply($adjustment);

        $journal = $this->assertJournal(
            $adjustment,
            '3850.00'
        );

        $preflight = app(
            TallyJournalExportPreflightService::class
        );

        // Sales has been posted in ERP but not confirmed in Tally.
        $this->assertStringContainsString(
            'Confirm IPD Sales',
            (string) $preflight->failure($journal)
        );

        // Simulate an independently confirmed IPD Sales export.
        // This inserts only into the isolated testing database.
        DB::table('tally_exports')->insert([
            'journal_entry_id' => $sales->id,
            'status' => 'confirmed',
            'export_reference' => 'CHARITY-TEST-'.$f['key'],
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(
            $preflight->failure($journal->fresh()),
            'Confirmed IPD Sales should permit charity export.'
        );

        // Charity export must never change or duplicate Sales.
        $this->assertSame('posted', $sales->fresh()->status);

        $this->assertSame(
            1,
            JournalEntry::where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $f['account']->id)
                ->where('source_event', 'finalized')
                ->count()
        );

        // No charity export has actually been transmitted.
        $this->assertSame(
            0,
            DB::table('tally_exports')
                ->where('journal_entry_id', $journal->id)
                ->count()
        );
    }


    public function test_patient_payment_after_posted_charity(): void
    {
        $f = $this->fixture();
        $this->postSales($f);

        \App\Models\GeneralLedgerAccount::firstOrCreate(
            ['account_code' => '1100'],
            [
                'account_name' => 'Main Cash',
                'account_type' => 'asset',
                'account_group' => 'Cash-in-Hand',
                'normal_balance' => 'debit',
                'is_active' => true,
                'allow_posting' => true,
            ]
        );

        $adjustment = $this->adjustment($f, '2000.00');
        app(CharityAdjustmentService::class)->apply($adjustment);
        $this->assertJournal($adjustment, '2000.00');

        $payment = \App\Models\IpBillingPayment::create([
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'receipt_no' => 'PAY-'.$f['key'],
            'payment_date' => now(),
            'amount' => '1850.00',
            'payment_mode' => 'cash',
            'status' => 'active',
            'received_by' => $f['user']->id,
        ]);

        $journal = app(
            \App\Services\Accounting\IpPaymentAccountingService::class
        )->postReceipt($payment);

        $this->assertSame('posted', $journal->fresh()->status);

        $lines = $journal->lines()
            ->with('generalLedgerAccount')
            ->get()
            ->keyBy(fn ($line) =>
                (string) $line->generalLedgerAccount->account_code
            );

        $this->assertEquals(1850, $lines['1100']->debit);
        $this->assertEquals(1850, $lines['1200']->credit);

        $again = app(
            \App\Services\Accounting\IpPaymentAccountingService::class
        )->postReceipt($payment);

        $this->assertSame($journal->id, $again->id);
    }

    public function test_patient_payment_blocked_for_unposted_charity(): void
    {
        $f = $this->fixture();
        $this->postSales($f);

        $adjustment = $this->adjustment($f, '2000.00');

        // Simulate an unreconciled adjustment.
        // Never invoke charity accounting for this negative test.
        $adjustment->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        $payment = \App\Models\IpBillingPayment::create([
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'receipt_no' => 'BLOCK-'.$f['key'],
            'payment_date' => now(),
            'amount' => '1850.00',
            'payment_mode' => 'cash',
            'status' => 'active',
            'received_by' => $f['user']->id,
        ]);

        try {
            app(
                \App\Services\Accounting\IpPaymentAccountingService::class
            )->postReceipt($payment);

            $this->fail('Unreconciled charity payment was allowed.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString(
                'Finance reconciliation',
                $e->getMessage()
            );
        }

        $this->assertSame(
            0,
            JournalEntry::where('source_type', 'ip_billing_payment')
                ->where('source_id', (string) $payment->id)
                ->count()
        );
    }

}
