<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Read-only financial workflow contract audit. These tests inspect the real service
 * implementations; they do not substitute for database-backed financial acceptance.
 */
class IpdFinancialAccountingContractTest extends TestCase
{
    private function source(string $service): string
    {
        $file = app_path('Services/Accounting/'.$service.'.php');
        $this->assertFileExists($file);
        return (string) file_get_contents($file);
    }

    private function assertAccountingContract(string $service, array $guards): void
    {
        $source = $this->source($service);
        foreach ($guards as $guard) {
            $this->assertStringContainsString($guard, $source, "Missing '$guard' in $service");
        }
    }

    public function test_final_bill_requires_finalized_account_and_unique_journal(): void
    {
        $this->assertAccountingContract('IpBillingAccountingService', ["lockForUpdate()", "status !== 'finalized'", "final_bill_no", "source_event', 'finalized'", "An unposted IPD journal already exists", "JournalEntry::create", "postingService->post"]);
    }

    public function test_final_bill_rejects_unconfigured_mhis_adjustments_and_checks_amounts(): void
    {
        $this->assertAccountingContract('IpBillingAccountingService', ['MHIS package adjustments require separate accounting configuration', 'Invalid IPD accounting amount', 'net_amount', 'chargeTotalCents']);
    }

    public function test_advance_receipt_requires_active_advance_and_unique_source(): void
    {
        $this->assertAccountingContract('IpAdvanceAccountingService', ["lockForUpdate()", "status !== 'active'", "source_event', 'received'", 'An unposted IPD advance journal already exists', "postingService->post"]);
    }

    public function test_advance_application_requires_posted_final_bill_and_receipts(): void
    {
        $this->assertAccountingContract('IpAdvanceApplicationAccountingService', ["lockForUpdate()", 'The IPD revenue journal is not posted', 'Advance receipt journal missing', 'advance_applied', 'toPaise', "postingService->post"]);
    }

    public function test_mhis_recognition_requires_formal_approval_and_posted_bill(): void
    {
        $this->assertAccountingContract('MhisReceivableAccountingService', ['An authenticated user is required', "status !== 'finalized'", "status !== 'approved'", 'Formal MHIS approval is required', 'IPD final-bill journal is not posted', "source_event', 'approved'"]);
    }

    public function test_mhis_recognition_limits_amount_and_checks_patient_payments(): void
    {
        $this->assertAccountingContract('MhisReceivableAccountingService', ['MHIS approval exceeds the eligible bill amount', 'Approved MHIS amount exceeds remaining Patient Receivables', 'Patient payment journal does not reconcile with GL 1200', 'Advance receipt amount does not reconcile']);
    }

    public function test_mhis_receipt_handles_tds_outstanding_and_prior_settlements(): void
    {
        $this->assertAccountingContract('MhisReceiptAccountingService', ["status !== 'active'", "status !== 'finalized'", 'tds_amount', 'netBankPaise', 'outstandingPaise', "postingService->post"]);
    }

    public function test_all_five_financial_services_use_database_transactions(): void
    {
        foreach (['IpBillingAccountingService', 'IpAdvanceAccountingService', 'IpAdvanceApplicationAccountingService', 'MhisReceivableAccountingService', 'MhisReceiptAccountingService'] as $service) {
            $this->assertStringContainsString('DB::transaction(', $this->source($service), $service);
        }
    }
}
