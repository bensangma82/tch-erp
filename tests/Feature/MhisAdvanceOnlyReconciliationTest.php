<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\IpBillingAdvance;
use App\Models\IpBillingMhisClaim;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Patient;
use App\Models\User;
use App\Services\Accounting\MhisAdvanceOnlyReconciliationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MhisAdvanceOnlyReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    private function fixture(
        string $receiptAmount = '1000.00',
        string $receiptDebitGl = '1100',
        bool $postFinal = true
    ): array
    {
        // Never write test records to an operational ERP database.
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $key = 'MH-ADV-'.bin2hex(random_bytes(6));
        $user = User::factory()->create();
        $this->actingAs($user);

        $patient = Patient::create([
            'uhid' => $key,
            'first_name' => 'MHIS',
            'last_name' => 'AdvanceTest',
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
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'opened_at' => now(),
            'status' => 'finalized',
            'subtotal' => '12121.25',
            'net_amount' => '12121.25',
            'paid_amount' => '1000.00',
            'balance_amount' => '11121.25',
            'final_bill_no' => 'FIN-'.$key,
            'finalized_at' => now(),
        ]);
        IpBillingMhisClaim::create([
            'ip_billing_account_id' => $account->id,
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'claim_no' => 'CL-'.$key,
            'claim_amount' => '5000.00',
            'expected_amount' => '5000.00',
            'approved_amount' => '0.00',
            'status' => 'submitted',
            'created_by' => $user->id,
        ]);
        $advance = IpBillingAdvance::create([
            'ip_billing_account_id' => $account->id,
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'receipt_no' => 'ADV-'.$key,
            'payment_date' => now(),
            'amount' => '1000.00',
            'payment_mode' => 'cash',
            'status' => 'active',
            'received_by' => $user->id,
        ]);

        $gl = [];
        foreach ([
            '1100' => ['Main Cash', 'asset', 'debit'],
            '1200' => ['Patient Receivables', 'asset', 'debit'],
            '2100' => ['Patient Advances', 'liability', 'credit'],
            '4301' => ['IPD Revenue', 'income', 'credit'],
        ] as $code => [$name, $type, $normal]) {
            $gl[$code] = GeneralLedgerAccount::firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => $name,
                    'account_type' => $type,
                    'account_group' => 'Test',
                    'normal_balance' => $normal,
                    'is_active' => true,
                    'allow_posting' => true,
                ]
            );
            $this->assertTrue((bool) $gl[$code]->is_active);
            $this->assertTrue((bool) $gl[$code]->allow_posting);
        }

        $final = $this->journal($key.'-FINAL', 'ip_billing_account', $account->id, 'finalized', [
            ['1200', '12121.25', '0.00'],
            ['4301', '0.00', '12121.25'],
        ], $gl, $user, $postFinal);
        $receipt = $this->journal($key.'-RECEIPT', 'ip_billing_advance', $advance->id, 'received', [
            [$receiptDebitGl, $receiptAmount, '0.00'],
            ['2100', '0.00', $receiptAmount],
        ], $gl, $user);

        return compact('account', 'advance', 'receipt', 'final', 'gl');
    }

    private function journal(string $no, string $source, int $id, string $event, array $lines, array $gl, User $user, bool $post = true): JournalEntry
    {
        $entry = JournalEntry::create([
            'journal_no' => $no,
            'journal_date' => now()->toDateString(),
            'source_type' => $source,
            'source_id' => (string) $id,
            'source_event' => $event,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
        foreach ($lines as $i => [$code, $debit, $credit]) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'line_no' => $i + 1,
                'general_ledger_account_id' => $gl[$code]->id,
                'debit' => $debit,
                'credit' => $credit,
            ]);
        }
        return $post
            ? app(\App\Services\Accounting\JournalPostingService::class)->post($entry)
            : $entry;
    }

    private function countApplications(IpBillingAccount $account): int
    {
        return JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_id', (string) $account->id)
            ->where('source_event', 'advance_applied')->count();
    }

    public function test_posts_exact_advance_application_and_preserves_claim_amounts(): void
    {
        $f = $this->fixture();
        $result = app(MhisAdvanceOnlyReconciliationService::class)->reconcile($f['account']);
        $this->assertSame(['JRN-IPAPPLY-'.$f['account']->id], $result['posted_journals']);
        $this->assertFalse($result['already_reconciled']);
        $this->assertSame(1, $this->countApplications($f['account']));
        $entry = JournalEntry::query()->where('journal_no', 'JRN-IPAPPLY-'.$f['account']->id)
            ->with('lines.generalLedgerAccount')->firstOrFail();
        $this->assertSame('posted', $entry->status);
        $this->assertCount(2, $entry->lines);
        $amounts = $entry->lines->mapWithKeys(fn ($line) => [
            $line->generalLedgerAccount->account_code => [(string)$line->debit, (string)$line->credit],
        ])->all();
        $this->assertEquals([1000.0, 0.0], array_map('floatval', $amounts['2100']));
        $this->assertEquals([0.0, 1000.0], array_map('floatval', $amounts['1200']));
        $this->assertEquals(12121.25, (float) $f['account']->fresh()->net_amount);
        $this->assertEquals(0.0, (float) $f['account']->mhisClaims()->firstOrFail()->approved_amount);
    }

    public function test_repeated_reconciliation_is_idempotent(): void
    {
        $f = $this->fixture();
        $service = app(MhisAdvanceOnlyReconciliationService::class);
        $service->reconcile($f['account']);
        $again = $service->reconcile($f['account']);
        $this->assertTrue($again['already_reconciled']);
        $this->assertSame([], $again['posted_journals']);
        $this->assertSame(1, $this->countApplications($f['account']));
    }

    public function test_rejects_receipt_amount_mismatch_without_posting(): void
    {
        $f = $this->fixture(receiptAmount: '900.00');
        try {
            app(MhisAdvanceOnlyReconciliationService::class)->reconcile($f['account']);
            $this->fail('Expected receipt amount validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reconciliation', $e->errors());
        }
        $this->assertSame(0, $this->countApplications($f['account']));
    }

    public function test_rejects_wrong_receipt_gl_without_posting(): void
    {
        $f = $this->fixture(receiptDebitGl: '1200');
        try {
            app(MhisAdvanceOnlyReconciliationService::class)->reconcile($f['account']);
            $this->fail('Expected receipt GL validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reconciliation', $e->errors());
        }
        $this->assertSame(0, $this->countApplications($f['account']));
    }

    public function test_rejects_missing_final_revenue_journal_without_posting(): void
    {
        $f = $this->fixture(postFinal: false);
        try {
            app(MhisAdvanceOnlyReconciliationService::class)->reconcile($f['account']);
            $this->fail('Expected posted final-bill journal validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reconciliation', $e->errors());
        }
        $this->assertSame(0, $this->countApplications($f['account']));
    }
}
