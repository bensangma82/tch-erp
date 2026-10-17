<?php

namespace Tests\Feature;

use App\Models\IpBillingRefund;
use App\Services\Accounting\IpBillingRefundAccountingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AccountingIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_refund_without_accounting_records_is_rejected(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(IpBillingRefundAccountingService::class)
            ->post(new IpBillingRefund());
    }

    public function test_posted_voucher_with_journal_cannot_be_cancelled(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'admin']);

        $voucher = \App\Models\FinanceVoucher::create([
            'voucher_no' => 'TEST-CANCEL-001',
            'voucher_type' => 'receipt',
            'voucher_date' => today(),
            'amount' => '100.00',
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        \App\Models\JournalEntry::create([
            'journal_no' => 'TEST-JRN-CANCEL-001',
            'journal_date' => today(),
            'source_type' => 'finance_voucher',
            'source_id' => (string) $voucher->id,
            'source_event' => 'posted',
            'finance_voucher_id' => $voucher->id,
            'status' => 'draft',
        ]);

        $this->actingAs($user);

        $response = $this->patch(
            route('finance.vouchers.cancel', $voucher),
            ['cancellation_reason' => 'Accounting integrity test']
        );

        $response->assertSessionHasErrors();

        $this->assertSame(
            'posted',
            $voucher->fresh()->status
        );
    }



    private function makeRefundFixture(): array
    {
        $patient = \App\Models\Patient::create([
            'uhid' => 'TEST-REFUND-001',
            'first_name' => 'Refund',
            'last_name' => 'Test',
            'sex' => 'male',
        ]);

        $admission = \App\Models\Admission::create([
            'admission_no' => 'TEST-REFUND-001',
            'patient_id' => $patient->id,
            'admitted_at' => now(),
        ]);

        $account = \App\Models\IpBillingAccount::create([
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'account_no' => 'TEST-REFUND-001',
            'opened_at' => now(),
            'status' => 'finalized',
            'net_amount' => '0.00',
        ]);

        return [$patient, $admission, $account];
    }


    public function test_advance_refund_posts_balanced_journal(): void
    {
        $this->actingAs(
            \App\Models\User::factory()->create([
                'role' => 'admin',
            ])
        );

        [$patient, $admission, $account] = $this->makeRefundFixture();

        foreach (['1100', '2100'] as $code) {
            $gl = \App\Models\GeneralLedgerAccount::query()
                ->firstOrNew(['account_code' => $code]);

            $gl->account_name = $code === '1100'
                ? 'Test Main Cash'
                : 'Test Patient Advances';

            $gl->account_type = $code === '1100'
                ? 'asset'
                : 'liability';

            $gl->normal_balance = $code === '1100'
                ? 'debit'
                : 'credit';

            $gl->is_active = true;
            $gl->allow_posting = true;
            $gl->save();
        }

        $advance = \App\Models\IpBillingAdvance::create([
            'ip_billing_account_id' => $account->id,
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'receipt_no' => 'TEST-ADV-001',
            'payment_date' => now(),
            'amount' => '5000.00',
            'payment_mode' => 'cash',
            'status' => 'active',
        ]);

        app(\App\Services\Accounting\IpAdvanceAccountingService::class)
            ->postAdvance($advance);

        $refund = \App\Models\IpBillingRefund::create([
            'ip_billing_account_id' => $account->id,
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'refund_no' => 'TEST-REF-001',
            'refund_date' => now(),
            'amount' => '3000.00',
            'payment_mode' => 'cash',
            'reason' => 'Refund test',
            'status' => 'active',
        ]);

        $service = app(
            \App\Services\Accounting\IpBillingRefundAccountingService::class
        );

        $journal = $service->post($refund);

        $this->assertSame('posted', $journal->status);

        $lines = $journal->lines()
            ->with('generalLedgerAccount')
            ->orderBy('line_no')
            ->get();

        $this->assertCount(2, $lines);

        $this->assertSame(
            '2100',
            $lines[0]->generalLedgerAccount->account_code
        );

        $this->assertEquals(3000, $lines[0]->debit);

        $this->assertSame(
            '1100',
            $lines[1]->generalLedgerAccount->account_code
        );

        $this->assertEquals(3000, $lines[1]->credit);

        $again = $service->post($refund);

        $this->assertSame($journal->id, $again->id);
    }


    public function test_refund_exceeding_advance_is_rejected(): void
    {
        $this->actingAs(\App\Models\User::factory()->create([
            'role' => 'admin',
        ]));

        [$patient, $admission, $account] = $this->makeRefundFixture();

        $refund = \App\Models\IpBillingRefund::create([
            'ip_billing_account_id' => $account->id,
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'refund_no' => 'TEST-EXCESS-001',
            'refund_date' => now(),
            'amount' => '3000.00',
            'payment_mode' => 'cash',
            'reason' => 'Excess refund test',
            'status' => 'active',
        ]);

        try {
            app(\App\Services\Accounting\IpBillingRefundAccountingService::class)
                ->post($refund);

            $this->fail('Refund should have been rejected.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString(
                'No accounted patient advance',
                $e->getMessage()
            );
        }

        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => 'ip_billing_refund',
            'source_id' => (string) $refund->id,
        ]);
    }

    public function test_failed_refund_accounting_rolls_back_creation(): void
    {
        $this->actingAs(\App\Models\User::factory()->create([
            'role' => 'admin',
        ]));

        [$patient, $admission, $account] = $this->makeRefundFixture();

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use (
                $patient, $admission, $account
            ) {
                $refund = \App\Models\IpBillingRefund::create([
                    'ip_billing_account_id' => $account->id,
                    'admission_id' => $admission->id,
                    'patient_id' => $patient->id,
                    'refund_no' => 'TEST-ROLLBACK-001',
                    'refund_date' => now(),
                    'amount' => '3000.00',
                    'payment_mode' => 'cash',
                    'reason' => 'Rollback test',
                    'status' => 'active',
                ]);

                app(\App\Services\Accounting\IpBillingRefundAccountingService::class)
                    ->post($refund);
            });

            $this->fail('Accounting failure expected.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString(
                'No accounted patient advance',
                $e->getMessage()
            );
        }

        $this->assertDatabaseMissing('ip_billing_refunds', [
            'refund_no' => 'TEST-ROLLBACK-001',
        ]);
    }

}
