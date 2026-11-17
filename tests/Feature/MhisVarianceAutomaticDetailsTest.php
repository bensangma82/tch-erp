<?php
namespace Tests\Feature;

use App\Models\{User, Patient, Admission, IpBillingAccount, IpBillingMhisClaim, MhisPackageVarianceApproval};
use App\Services\Accounting\{MhisPackageVarianceApprovalService, MhisPackageVarianceReviewException};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MhisVarianceAutomaticDetailsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName(), 'Do not run against an operational database.');
        // Supports validation in staged source without overwriting Mac views.
        $stagedViews = dirname(__DIR__, 2).'/resources/views';
        if (is_dir($stagedViews)) app('view')->getFinder()->prependLocation($stagedViews);
    }

    private function fixture(): array
    {
        $maker = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email_verified_at' => now()]);
        $checker = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($maker);
        $key = 'VAR-'.bin2hex(random_bytes(6));
        $patient = Patient::create(['uhid' => $key, 'first_name' => 'Variance', 'last_name' => 'ReviewTest', 'sex' => 'male', 'is_active' => true]);
        $admission = Admission::create(['admission_no' => $key, 'patient_id' => $patient->id, 'admitted_at' => now(), 'status' => 'admitted']);
        $account = IpBillingAccount::create([
            'account_no' => $key, 'admission_id' => $admission->id, 'patient_id' => $patient->id,
            'opened_at' => now(), 'status' => 'open', 'mhis_category' => 'free',
            'subtotal' => '4850.00', 'net_amount' => '4850.00', 'paid_amount' => '0.00', 'balance_amount' => '4850.00',
        ]);
        $claim = IpBillingMhisClaim::create([
            'ip_billing_account_id' => $account->id, 'admission_id' => $admission->id, 'patient_id' => $patient->id,
            'claim_amount' => '4000.00', 'approved_amount' => '4000.00', 'status' => 'approved',
            'approval_date' => today(), 'authorization_no' => 'AUTH-'.$key, 'created_by' => $maker->id,
        ]);
        return compact('maker', 'checker', 'patient', 'account', 'claim');
    }

    private function inputs(): array
    {
        return ['simple_review' => true, 'expected_bill' => '4850.00', 'expected_approved' => '4000.00',
            'supporting_reference' => 'TEST-APPROVAL-DOCUMENT', 'reason_choice' => 'package_tariff',
            'contractual_nonrecoverable' => true, 'notes' => 'Verified contractual package terms in test document.'];
    }

    public function test_reference_and_reason_are_generated_without_manual_fields(): void
    {
        $f = $this->fixture(); $data = $this->inputs(); unset($data['supporting_reference'], $data['reason_choice']);
        $a = app(MhisPackageVarianceApprovalService::class)->prepare($f['claim'], $data);
        $this->assertSame($f['claim']->authorization_no, $a->supporting_reference);
        $this->assertStringContainsString('Recorded MHIS approval differs', $a->reason);
        $this->assertStringContainsString('no live MHIS portal lookup', $a->supporting_evidence);
        $this->assertSame('submitted', $a->status);
    }

    public function test_registration_is_a_lookup_reference_when_approval_number_is_missing(): void
    {
        $f = $this->fixture(); $f['claim']->update(['authorization_no' => null, 'claim_no' => null, 'mhis_registration_id' => '43537763']);
        $data = $this->inputs(); unset($data['supporting_reference'], $data['reason_choice']);
        $a = app(MhisPackageVarianceApprovalService::class)->prepare($f['claim'], $data);
        $this->assertSame('43537763', $a->supporting_reference);
    }

    public function test_absent_identifiers_block_automatic_submission(): void
    {
        $f = $this->fixture(); $f['claim']->update(['authorization_no' => null, 'claim_no' => null, 'mhis_registration_id' => null]);
        $data = $this->inputs(); unset($data['supporting_reference']);
        $this->expectException(MhisPackageVarianceReviewException::class);
        app(MhisPackageVarianceApprovalService::class)->prepare($f['claim'], $data);
    }

    public function test_amount_difference_does_not_auto_confirm_nonrecoverability(): void
    {
        $f = $this->fixture(); $data = $this->inputs(); unset($data['supporting_reference'], $data['reason_choice'], $data['contractual_nonrecoverable']);
        $this->expectException(MhisPackageVarianceReviewException::class);
        app(MhisPackageVarianceApprovalService::class)->prepare($f['claim'], $data);
    }

    public function test_form_submits_with_no_reference_or_reason_input_when_recorded(): void
    {
        $f = $this->fixture(); $data = $this->inputs(); unset($data['supporting_reference'], $data['reason_choice']);
        $data['claim_id'] = $f['claim']->id;
        $this->withSession(['_token' => 'auto-variance'])->post(route('finance.mhis-package-variance.store'), $data + ['_token' => 'auto-variance'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('mhis_package_variance_approvals', ['ip_billing_mhis_claim_id' => $f['claim']->id, 'status' => 'submitted']);
    }
}
