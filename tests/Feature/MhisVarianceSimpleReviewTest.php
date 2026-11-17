<?php
namespace Tests\Feature;

use App\Models\{User, Patient, Admission, IpBillingAccount, IpBillingMhisClaim, MhisPackageVarianceApproval};
use App\Services\Accounting\{MhisPackageVarianceApprovalService, MhisPackageVarianceReviewException};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MhisVarianceSimpleReviewTest extends TestCase
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

    public function test_prefilled_review_displays_patient_and_correct_amounts(): void
    {
        $f = $this->fixture();
        $this->get(route('finance.mhis-package-variance.index', ['claim_id' => $f['claim']->id]))
            ->assertOk()->assertSee($f['patient']->uhid)->assertSee('4,850.00')->assertSee('4,000.00')->assertSee('850.00')
            ->assertSee('Submit for Finance review')->assertDontSee('MHIS claim ID');
    }

    public function test_submission_generates_evidence_and_posts_no_accounting(): void
    {
        $f = $this->fixture(); $before = DB::table('journal_entries')->count();
        $a = app(MhisPackageVarianceApprovalService::class)->prepare($f['claim'], $this->inputs());
        $this->assertSame('submitted', $a->status);
        $this->assertSame('-850.00', $a->variance_amount);
        $this->assertStringContainsString('ERP snapshot:', $a->supporting_evidence);
        $this->assertStringContainsString('4850.00', $a->reason);
        $this->assertDatabaseHas('mhis_variance_review_events', ['approval_id' => $a->id, 'action' => 'submitted', 'actor_id' => $f['maker']->id]);
        $this->assertSame($before, DB::table('journal_entries')->count());
    }

    public function test_same_maker_cannot_approve(): void
    {
        $f = $this->fixture(); $s = app(MhisPackageVarianceApprovalService::class);
        $a = $s->prepare($f['claim'], $this->inputs());
        $this->expectException(MhisPackageVarianceReviewException::class); $s->approve($a);
    }

    public function test_independent_approval_is_audited_and_recognition_guard_accepts_it(): void
    {
        $f = $this->fixture(); $s = app(MhisPackageVarianceApprovalService::class); $a = $s->prepare($f['claim'], $this->inputs());
        $this->actingAs($f['checker']); $a = $s->approve($a);
        $this->assertSame('approved', $a->status);
        $this->assertSame($f['checker']->id, (int) $a->approved_by);
        $s->assertApproved($f['claim'], $f['account'], -85000);
        $this->assertDatabaseHas('mhis_variance_review_events', ['approval_id' => $a->id, 'action' => 'approved']);
    }

    public function test_changed_preview_amount_requires_refresh(): void
    {
        $f = $this->fixture(); $f['account']->update(['net_amount' => '4900.00']);
        $this->expectException(MhisPackageVarianceReviewException::class);
        app(MhisPackageVarianceApprovalService::class)->prepare($f['claim'], $this->inputs());
    }

    public function test_changed_bill_blocks_checker_approval(): void
    {
        $f = $this->fixture(); $s = app(MhisPackageVarianceApprovalService::class); $a = $s->prepare($f['claim'], $this->inputs());
        $f['account']->update(['net_amount' => '4900.00']); $this->actingAs($f['checker']);
        $this->expectException(MhisPackageVarianceReviewException::class); $s->approve($a);
    }

    public function test_unconfirmed_nonrecoverable_shortfall_cannot_be_submitted(): void
    {
        $f = $this->fixture(); $data = $this->inputs(); $data['contractual_nonrecoverable'] = false;
        $this->expectException(MhisPackageVarianceReviewException::class);
        app(MhisPackageVarianceApprovalService::class)->prepare($f['claim'], $data);
    }

    public function test_return_and_resubmit_preserve_history_and_require_new_checker_approval(): void
    {
        $f = $this->fixture(); $s = app(MhisPackageVarianceApprovalService::class); $a = $s->prepare($f['claim'], $this->inputs());
        $this->actingAs($f['checker']); $s->returnForCorrection($a, 'Provide the corrected document reference.');
        $this->assertSame('returned', $a->fresh()->status);
        $this->actingAs($f['maker']); $data = $this->inputs(); $data['supporting_reference'] = 'CORRECTED-DOCUMENT';
        $revised = $s->prepare($f['claim'], $data);
        $this->assertSame($a->id, $revised->id); $this->assertSame('submitted', $revised->status); $this->assertNull($revised->approved_by);
        $this->assertSame(4, DB::table('mhis_variance_review_events')->where('approval_id', $a->id)->count());
        $old = DB::table('mhis_variance_review_events')->where('approval_id', $a->id)->where('action', 'before_revision')->first();
        $this->assertStringContainsString('TEST-APPROVAL-DOCUMENT', $old->snapshot);
    }

    public function test_unapproved_variance_throws_specific_business_exception(): void
    {
        $f = $this->fixture();
        $this->expectException(MhisPackageVarianceReviewException::class);
        app(MhisPackageVarianceApprovalService::class)->assertApproved($f['claim'], $f['account'], -85000);
    }

    public function test_duplicate_submission_is_blocked(): void
    {
        $f = $this->fixture(); $s = app(MhisPackageVarianceApprovalService::class); $s->prepare($f['claim'], $this->inputs());
        $this->expectException(MhisPackageVarianceReviewException::class); $s->prepare($f['claim'], $this->inputs());
    }

    public function test_non_finance_user_cannot_open_review(): void
    {
        $f = $this->fixture();
        $this->actingAs(User::factory()->create(['role' => 'reception', 'is_active' => true, 'email_verified_at' => now()]));
        $this->get(route('finance.mhis-package-variance.index', ['claim_id' => $f['claim']->id]))->assertForbidden();
    }

    public function test_placeholder_reference_is_rejected_by_form(): void
    {
        $f = $this->fixture(); $data = $this->inputs(); $data['claim_id'] = $f['claim']->id;
        $data['supporting_reference'] = 'Actual MHIS approval/package reference';
        $this->withSession(['_token' => 'variance-test'])->post(route('finance.mhis-package-variance.store'), $data + ['_token' => 'variance-test'])
            ->assertSessionHasErrors('supporting_reference');
        $this->assertSame(0, MhisPackageVarianceApproval::where('ip_billing_mhis_claim_id', $f['claim']->id)->count());
    }
}
