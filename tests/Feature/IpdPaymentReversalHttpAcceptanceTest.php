<?php

namespace Tests\Feature;

use App\Models\{Admission, GeneralLedgerAccount, IpBillingAccount, IpBillingCharge, IpBillingPayment, JournalEntry, JournalEntryLine, Patient, User};
use App\Services\Accounting\JournalPostingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IpdPaymentReversalHttpAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    private function fixture(bool $postOriginal = true): array
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertContains(DB::connection()->getDatabaseName(), ['testing', 'tch_concurrency_test']);

        $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email_verified_at' => now()]);
        $this->assertTrue($user->hasPermission('ip-billing.payment-reverse'));
        $this->actingAs($user);
        $token = 'HTTPREV-'.strtoupper(bin2hex(random_bytes(5)));
        $patient = Patient::create(['uhid' => $token, 'first_name' => 'HTTP', 'last_name' => 'Reversal', 'sex' => 'male', 'is_active' => true]);
        $admission = Admission::create(['admission_no' => $token, 'patient_id' => $patient->id, 'admitted_at' => now(), 'status' => 'admitted']);
        $account = IpBillingAccount::create([
            'opened_at' => now(), 'account_no' => $token, 'admission_id' => $admission->id,
            'patient_id' => $patient->id, 'status' => 'finalized', 'net_amount' => 1000,
            'paid_amount' => 300, 'balance_amount' => 700,
        ]);
        IpBillingCharge::create([
            'ip_billing_account_id' => $account->id, 'admission_id' => $admission->id,
            'charge_date' => now(), 'charge_type' => 'other', 'code' => 'HTTP-TEST',
            'description' => 'Test hospital charge', 'quantity' => 1, 'unit_price' => 1000,
            'discount' => 0, 'amount' => 1000, 'status' => 'active', 'created_by' => $user->id,
        ]);
        $payment = IpBillingPayment::create([
            'ip_billing_account_id' => $account->id, 'admission_id' => $admission->id,
            'patient_id' => $patient->id, 'receipt_no' => $token, 'payment_date' => now(),
            'amount' => 300, 'payment_mode' => 'cash', 'status' => 'active', 'received_by' => $user->id,
        ]);
        // Test-local prerequisites; DatabaseTransactions rolls back newly created GL accounts.
        foreach ([
            ['1100', 'Main Cash', 'Cash-in-Hand'],
            ['1200', 'Patient Receivables', 'Sundry Debtors'],
        ] as [$code, $name, $group]) {
            GeneralLedgerAccount::firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => $name,
                    'account_type' => 'asset',
                    'account_group' => $group,
                    'normal_balance' => 'debit',
                    'is_active' => true,
                    'allow_posting' => true,
                ]
            );
        }
        $cash = GeneralLedgerAccount::where('account_code', '1100')->where('is_active', true)->where('allow_posting', true)->firstOrFail();
        $receivable = GeneralLedgerAccount::where('account_code', '1200')->where('is_active', true)->where('allow_posting', true)->firstOrFail();
        $original = JournalEntry::create([
            'journal_no' => 'JRN-'.$token, 'journal_date' => now()->toDateString(),
            'source_type' => 'ip_billing_payment', 'source_id' => $payment->id,
            'source_event' => 'received', 'reference_no' => $token,
            'narration' => 'HTTP acceptance fixture', 'status' => 'draft', 'created_by' => $user->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $original->id, 'line_no' => 1,
            'general_ledger_account_id' => $cash->id, 'debit' => 300, 'credit' => 0,
            'description' => 'Cash received',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $original->id, 'line_no' => 2,
            'general_ledger_account_id' => $receivable->id, 'debit' => 0, 'credit' => 300,
            'description' => 'Receivable settled',
        ]);
        if ($postOriginal) {
            $original = app(JournalPostingService::class)->post($original);
        }
        return compact('user', 'patient', 'admission', 'account', 'payment', 'original');
    }

    private function endpoint(array $f): string
    {
        return route('ip-billing.payment.reverse', [
            'admission' => $f['admission']->id, 'payment' => $f['payment']->id,
        ]);
    }

    public function test_authorized_http_reversal_cancels_payment_and_recalculates_balance(): void
    {
        $f = $this->fixture();
        $this->post($this->endpoint($f), ['cancellation_reason' => 'Duplicate cash receipt entered'])
            ->assertRedirect(route('ip-billing.show', $f['admission']));
        $payment = $f['payment']->fresh();
        $account = $f['account']->fresh();
        $this->assertSame('cancelled', $payment->status);
        $this->assertSame($f['user']->id, $payment->cancelled_by);
        $this->assertNotNull($payment->cancelled_at);
        $this->assertSame('Duplicate cash receipt entered', $payment->cancellation_reason);
        $this->assertEquals(0, $account->paid_amount);
        $this->assertEquals(1000, $account->net_amount);
        $this->assertEquals(1000, $account->balance_amount);
        $this->assertSame('posted', $f['original']->fresh()->status);
        $reversals = JournalEntry::where('reversal_of_id', $f['original']->id)->get();
        $this->assertCount(1, $reversals);
        $this->assertSame('posted', $reversals->first()->status);
        $originalLines = $f['original']->lines()->orderBy('line_no')->get();
        $reversalLines = $reversals->first()->lines()->orderBy('line_no')->get();
        $this->assertCount(2, $reversalLines);
        foreach ($originalLines as $line) {
            $reverse = $reversalLines->firstWhere('general_ledger_account_id', $line->general_ledger_account_id);
            $this->assertNotNull($reverse);
            $this->assertEquals($line->debit, $reverse->credit);
            $this->assertEquals($line->credit, $reverse->debit);
        }
    }

    public function test_second_http_reversal_does_not_create_another_journal(): void
    {
        $f = $this->fixture();
        $this->post($this->endpoint($f), ['cancellation_reason' => 'Correcting duplicate receipt'])
            ->assertRedirect();
        $this->post($this->endpoint($f), ['cancellation_reason' => 'Trying again incorrectly'])
            ->assertSessionHasErrors('payment');
        $this->assertSame(1, JournalEntry::where('reversal_of_id', $f['original']->id)->count());
        $this->assertSame('cancelled', $f['payment']->fresh()->status);
    }

    public function test_unposted_original_rejects_reversal_and_rolls_back_payment_changes(): void
    {
        $f = $this->fixture(false);
        $this->post($this->endpoint($f), ['cancellation_reason' => 'Attempt against draft journal'])
            ->assertStatus(500);
        $this->assertSame('active', $f['payment']->fresh()->status);
        $this->assertNull($f['payment']->fresh()->cancelled_at);
        $this->assertEquals(300, $f['account']->fresh()->paid_amount);
        $this->assertSame(0, JournalEntry::where('reversal_of_id', $f['original']->id)->count());
    }
    public function test_finance_linked_payment_cannot_be_reversed(): void
    {
        // Link while draft: posted journals must never be edited.
        $f = $this->fixture(false);
        $voucherId = DB::table('finance_vouchers')->insertGetId([
            'voucher_no' => 'FV-'.$f['payment']->receipt_no,
            'voucher_type' => 'receipt',
            'voucher_date' => now()->toDateString(),
            'amount' => 300,
            'status' => 'draft',
            'created_by' => $f['user']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $f['original']->finance_voucher_id = $voucherId;
        $f['original']->save();
        app(JournalPostingService::class)->post($f['original']);

        $this->post($this->endpoint($f), ['cancellation_reason' => 'Finance-linked reversal attempt'])
            ->assertStatus(500);
        $this->assertSame('active', $f['payment']->fresh()->status);
        $this->assertNull($f['payment']->fresh()->cancelled_at);
        $this->assertEquals(300, $f['account']->fresh()->paid_amount);
        $this->assertEquals(700, $f['account']->fresh()->balance_amount);
        $this->assertSame('posted', $f['original']->fresh()->status);
        $this->assertSame(0, JournalEntry::where('reversal_of_id', $f['original']->id)->count());
    }

    public function test_tally_export_linked_payment_cannot_be_reversed(): void
    {
        $f = $this->fixture();
        DB::table('tally_exports')->insert([
            'journal_entry_id' => $f['original']->id,
            'status' => 'exported',
            'export_reference' => 'TEST-'.$f['payment']->receipt_no,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post($this->endpoint($f), ['cancellation_reason' => 'Tally-linked reversal attempt'])
            ->assertStatus(500);
        $this->assertSame('active', $f['payment']->fresh()->status);
        $this->assertNull($f['payment']->fresh()->cancelled_at);
        $this->assertEquals(300, $f['account']->fresh()->paid_amount);
        $this->assertEquals(700, $f['account']->fresh()->balance_amount);
        $this->assertSame('posted', $f['original']->fresh()->status);
        $this->assertSame(0, JournalEntry::where('reversal_of_id', $f['original']->id)->count());
    }

}
