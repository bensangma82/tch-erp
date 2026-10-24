<?php

namespace Tests\Feature;

use App\Models\IpBillingPayment;
use App\Models\User;
use App\Services\Accounting\IpPaymentAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class IpdPaymentReversalGuardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_without_reversal_permission_cannot_access_route(): void
    {
        $user = User::factory()->create([
            'role' => 'reception',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->assertFalse($user->hasPermission('ip-billing.payment-reverse'));

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\SubstituteBindings::class);

        $this->actingAs($user)
            ->post(route('ip-billing.payment.reverse', [
                'admission' => 99999999,
                'payment' => 99999999,
            ]), ['cancellation_reason' => 'Unauthorized reversal attempt'])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_reverse_existing_payment(): void
    {
        // All records exist only inside the isolated testing DB transaction.
        $user = User::factory()->create([
            'role' => 'reception',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->assertFalse($user->hasPermission('ip-billing.payment-reverse'));

        $token = 'RBAC-'.strtoupper(bin2hex(random_bytes(5)));
        $patient = \App\Models\Patient::create([
            'uhid' => $token,
            'first_name' => 'Security',
            'last_name' => 'Test',
            'sex' => 'male',
            'is_active' => true,
        ]);
        $admission = \App\Models\Admission::create([
            'admission_no' => $token,
            'patient_id' => $patient->id,
            'admitted_at' => now(),
            'status' => 'admitted',
        ]);
        $account = \App\Models\IpBillingAccount::create([
            'opened_at' => now(),
            'account_no' => $token,
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'status' => 'finalized',
            'net_amount' => 100,
            'paid_amount' => 50,
            'balance_amount' => 50,
        ]);
        $payment = IpBillingPayment::create([
            'ip_billing_account_id' => $account->id,
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'receipt_no' => $token,
            'payment_date' => now(),
            'amount' => 50,
            'payment_mode' => 'cash',
            'status' => 'active',
            'received_by' => $user->id,
        ]);

        $journalCount = DB::table('journal_entries')->count();
        // Do not disable SubstituteBindings: test the actual HTTP route.
        $this->actingAs($user)->post(
            route('ip-billing.payment.reverse', [
                'admission' => $admission->id,
                'payment' => $payment->id,
            ]),
            ['cancellation_reason' => 'Unauthorized test attempt']
        )->assertForbidden();

        $payment->refresh();
        $this->assertSame('active', $payment->status);
        $this->assertNull($payment->cancelled_at);
        $this->assertNull($payment->cancelled_by);
        $this->assertSame($journalCount, DB::table('journal_entries')->count());
    }

    public function test_accounting_service_rejects_unauthorized_caller_before_writing(): void
    {
        $user = User::factory()->create(['role' => 'reception', 'is_active' => true]);
        $this->actingAs($user);
        $this->assertFalse($user->hasPermission('ip-billing.payment-reverse'));

        $before = DB::table('journal_entries')->count();
        try {
            app(IpPaymentAccountingService::class)->reverseReceipt(
                new IpBillingPayment(['status' => 'active']),
                'Attempt without permission'
            );
            $this->fail('Unauthorized reversal unexpectedly succeeded.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Not authorized', $exception->getMessage());
        }
        $this->assertSame($before, DB::table('journal_entries')->count());
    }

    public function test_postgresql_enforces_unique_reversal_and_source_indexes(): void
    {
        $indexes = DB::select("SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = 'public' AND tablename = 'journal_entries'");
        $definitions = implode("\n", array_map(fn ($row) => $row->indexdef, $indexes));
        $this->assertMatchesRegularExpression('/CREATE UNIQUE INDEX[^\n]*\(reversal_of_id\)/i', $definitions);
        $this->assertMatchesRegularExpression('/CREATE UNIQUE INDEX[^\n]*\(source_type, source_id, source_event\)/i', $definitions);
    }
}
