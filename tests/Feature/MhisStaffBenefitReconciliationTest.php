<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Patient;
use App\Models\StaffMedicalBenefitTransaction;
use App\Services\Accounting\StaffMedicalBenefitReconciliationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class MhisStaffBenefitReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    private function fixture(bool $journal = true): array
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName());

        $key = 'SMB-'.bin2hex(random_bytes(6));

        $patient = Patient::create([
            'uhid' => $key,
            'first_name' => 'MHIS',
            'last_name' => 'Test',
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
            'net_amount' => '13321.80',
            'subtotal' => '13321.80',
            'paid_amount' => '0.00',
            'balance_amount' => '13321.80',
            'final_bill_no' => 'FIN-'.$key,
            'finalized_at' => now(),
        ]);

        $employeeId = DB::table('employees')->insertGetId([
            'employee_code' => 'SMB-'.bin2hex(random_bytes(6)),
            'first_name' => 'MHIS Test',
            'employee_type' => 'permanent',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $policyId = DB::table('staff_medical_benefit_policies')
            ->insertGetId([
                'name' => 'MHIS Test '.bin2hex(random_bytes(6)),
                'financial_year_start' => '2026-04-01',
                'financial_year_end' => '2027-03-31',
                'employee_annual_limit' => '50000.00',
                'dependent_family_annual_limit' => '25000.00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $benefitAccountId = DB::table('staff_medical_benefit_accounts')
            ->insertGetId([
                'employee_id' => $employeeId,
                'policy_id' => $policyId,
                'financial_year_start' => '2026-04-01',
                'financial_year_end' => '2027-03-31',
                'employee_entitlement' => '50000.00',
                'dependent_family_entitlement' => '25000.00',
                'eligible_from' => '2026-04-01',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $benefit = StaffMedicalBenefitTransaction::create([
            'benefit_account_id' => $benefitAccountId,
            'beneficiary_type' => 'employee',
            'patient_id' => $patient->id,
            'transaction_type' => 'utilization',
            'transaction_date' => now(),
            'amount' => '9321.80',
            'source_type' => 'ip_billing_account',
            'source_id' => $account->id,
            'status' => 'active',
        ]);

        if ($journal) {
            $gl = [];

            foreach ([
                '6113' => ['Staff Medical Benefit Expense', 'expense', 'debit'],
                '1200' => ['Patient Receivables', 'asset', 'debit'],
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
            }

            $entry = JournalEntry::create([
                'journal_no' => 'JRN-IPD-SMB-'.$benefit->id,
                'journal_date' => now(),
                'source_type' => 'staff_medical_benefit_transaction',
                'source_id' => (string) $benefit->id,
                'source_event' => 'utilized',
                'status' => 'draft',
            ]);

            foreach ([
                [1, '6113', '9321.80', '0.00'],
                [2, '1200', '0.00', '9321.80'],
            ] as [$line, $code, $debit, $credit]) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'line_no' => $line,
                    'general_ledger_account_id' => $gl[$code]->id,
                    'debit' => $debit,
                    'credit' => $credit,
                ]);
            }
        }

        if ($journal) {
            $this->actingAs(\App\Models\User::factory()->create());

            app(\App\Services\Accounting\JournalPostingService::class)
                ->post($entry);
        }

        return [$account, $benefit];
    }

    public function test_valid_benefit_journal_reconciles(): void
    {
        [$account] = $this->fixture();

        $this->assertSame(
            932180,
            app(StaffMedicalBenefitReconciliationService::class)
                ->reconcile($account)
        );
    }

    public function test_missing_benefit_journal_is_rejected(): void
    {
        [$account] = $this->fixture(false);

        $this->expectException(RuntimeException::class);

        app(StaffMedicalBenefitReconciliationService::class)
            ->reconcile($account);
    }

    public function test_posted_benefit_journal_cannot_be_modified(): void
    {
        [$account, $benefit] = $this->fixture();

        $entry = JournalEntry::where(
            'journal_no', 'JRN-IPD-SMB-'.$benefit->id
        )->firstOrFail();

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        JournalEntryLine::where('journal_entry_id', $entry->id)
            ->where('debit', '>', 0)
            ->update(['debit' => '1.00']);
    }

    public function test_duplicate_journal_is_rejected(): void
    {
        [$account, $benefit] = $this->fixture();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        JournalEntry::create([
            'journal_no' => 'DUP-SMB-'.$benefit->id,
            'journal_date' => now(),
            'source_type' => 'staff_medical_benefit_transaction',
            'source_id' => (string) $benefit->id,
            'source_event' => 'utilized',
            'status' => 'posted',
        ]);

        $this->expectException(RuntimeException::class);

        app(StaffMedicalBenefitReconciliationService::class)
            ->reconcile($account);
    }

    public function test_mhis_receivable_boundary(): void
    {
        [$account] = $this->fixture();

        $benefit = app(StaffMedicalBenefitReconciliationService::class)
            ->reconcile($account);

        $remaining = 1332180 - $benefit;

        $this->assertSame(400000, $remaining);
        $this->assertTrue(400000 <= $remaining);
        $this->assertFalse(400001 <= $remaining);
    }
}
