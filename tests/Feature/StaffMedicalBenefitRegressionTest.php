<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\StaffMedicalBenefitAccount;
use App\Models\StaffMedicalBenefitTransaction;
use App\Services\Accounting\IpBillingAccountingService;
use App\Services\StaffMedicalBenefitUtilizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class StaffMedicalBenefitRegressionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_invalid_utilization_is_rejected_without_writing_ledger(): void
    {
        $account = new StaffMedicalBenefitAccount();
        $patient = new \App\Models\Patient();
        $before = StaffMedicalBenefitTransaction::count();
        $service = app(StaffMedicalBenefitUtilizationService::class);

        foreach ([
            ['employee', 0.0, 'invoice'],
            ['employee', -10.0, 'invoice'],
            ['invalid', 100.0, 'invoice'],
            ['employee', 100.0, ''],
        ] as [$type, $amount, $source]) {
            try {
                $service->utilize(
                    benefitAccount: $account,
                    patient: $patient,
                    beneficiaryType: $type,
                    requestedAmount: $amount,
                    sourceType: $source
                );
                $this->fail('Invalid utilization was accepted.');
            } catch (RuntimeException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }

        $this->assertSame($before, StaffMedicalBenefitTransaction::count());
    }

    public function test_ipd_settlement_rejects_invalid_utilization_without_journal(): void
    {
        $service = app(IpBillingAccountingService::class);
        $before = JournalEntry::count();
        $invalid = new StaffMedicalBenefitTransaction();
        $invalid->id = -1;

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        try {
            $service->postStaffBenefitSettlement($invalid);
        } finally {
            $this->assertSame($before, JournalEntry::count());
        }
    }

    public function test_utilization_has_positive_ledger_effect(): void
    {
        $transaction = new StaffMedicalBenefitTransaction([
            'transaction_type' => 'utilization',
            'status' => 'active',
            'amount' => '2442.00',
        ]);

        $this->assertSame(2442.0, $transaction->ledger_effect);
        $this->assertTrue($transaction->isUtilization());
        $this->assertTrue($transaction->isActive());
    }

    public function test_reversal_restores_entitlement_in_ledger_effect(): void
    {
        $transaction = new StaffMedicalBenefitTransaction([
            'transaction_type' => 'reversal',
            'status' => 'active',
            'amount' => '500.00',
        ]);

        $this->assertSame(-500.0, $transaction->ledger_effect);
        $this->assertTrue($transaction->isReversal());
    }

    public function test_inactive_transactions_have_no_ledger_effect(): void
    {
        foreach (['utilization', 'reversal'] as $type) {
            $transaction = new StaffMedicalBenefitTransaction([
                'transaction_type' => $type,
                'status' => 'cancelled',
                'amount' => '500.00',
            ]);
            $this->assertSame(0.0, $transaction->ledger_effect);
        }
    }

    public function test_beneficiary_type_helpers_distinguish_employee_and_dependent(): void
    {
        $employee = new StaffMedicalBenefitTransaction(['beneficiary_type' => 'employee']);
        $dependent = new StaffMedicalBenefitTransaction(['beneficiary_type' => 'dependent']);

        $this->assertTrue($employee->isEmployeeBenefit());
        $this->assertFalse($employee->isDependentBenefit());
        $this->assertTrue($dependent->isDependentBenefit());
        $this->assertFalse($dependent->isEmployeeBenefit());
    }
}
