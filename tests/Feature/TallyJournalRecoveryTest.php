<?php

namespace Tests\Feature;

use App\Models\TallyExport;
use App\Models\User;
use App\Services\Tally\TallyJournalRecoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TallyJournalRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tally.journal_export_enabled', true);
        config()->set('tally.journal_recovery_enabled', true);
        config()->set('tally.company', 'Tura Christian Hospital ERP -Test');
    }


    public function test_user_without_permission_cannot_authorize_recovery(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(TallyJournalRecoveryService::class)->authorizeRecovery(
            999999,
            $user->id,
            'Previous export was rejected',
            'Independent Tally reconciliation completed'
        );
    }

    public function test_recovery_requires_reason_and_evidence(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(TallyJournalRecoveryService::class)->authorizeRecovery(
            999999,
            $user->id,
            '',
            ''
        );
    }

    public function test_nonexistent_export_is_rejected(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            [
                'label' => 'Authorize Tally Journal Recovery',
                'module' => 'finance',
            ]
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        app(TallyJournalRecoveryService::class)->authorizeRecovery(
            999999,
            $user->id,
            'Attempted recovery',
            'Reconciliation evidence'
        );
    }


    public function test_confirmed_journal_export_cannot_be_recovered(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            [
                'label' => 'Authorize Tally Journal Recovery',
                'module' => 'finance',
            ]
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-TEST-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999001,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'confirmed',
            'export_reference' => 'TEST-CONFIRMED-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
            'confirmed_at' => now(),
        ]);

        try {
            app(TallyJournalRecoveryService::class)->authorizeRecovery(
                $export->id,
                $user->id,
                'Attempted recovery of confirmed export',
                'Tally verification performed'
            );

            $this->fail('Confirmed export recovery was not blocked.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'confirmed',
                $export->fresh()->status
            );

            $this->assertDatabaseMissing(
                'tally_journal_export_attempts',
                ['tally_export_id' => $export->id]
            );
        }
    }


    public function test_unknown_export_without_tally_response_cannot_be_recovered(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            [
                'label' => 'Authorize Tally Journal Recovery',
                'module' => 'finance',
            ]
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-TEST-002',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999002,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-UNKNOWN-002',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
            'tally_response' => null,
        ]);

        try {
            app(TallyJournalRecoveryService::class)->authorizeRecovery(
                $export->id,
                $user->id,
                'Transmission failed',
                'No matching voucher identified'
            );

            $this->fail('Uncertain transmission was incorrectly authorized.');
        } catch (ValidationException $exception) {
            $this->assertSame('unknown', $export->fresh()->status);
            $this->assertDatabaseMissing(
                'tally_journal_export_attempts',
                ['tally_export_id' => $export->id]
            );
        }
    }


    public function test_explicitly_rejected_journal_can_be_authorized_for_review(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            [
                'label' => 'Authorize Tally Journal Recovery',
                'module' => 'finance',
            ]
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-TEST-003',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999003,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $response = '<RESPONSE>'
            .'<LINEERROR>Voucher rejected</LINEERROR>'
            .'<CREATED>0</CREATED>'
            .'<ALTERED>0</ALTERED>'
            .'<DELETED>0</DELETED>'
            .'<LASTVCHID>0</LASTVCHID>'
            .'</RESPONSE>';

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-REJECTED-003',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
            'tally_response' => $response,
        ]);

        $attempt = app(TallyJournalRecoveryService::class)
            ->authorizeRecovery(
                $export->id,
                $user->id,
                'Original Tally import rejected',
                'Tally Day Book checked in Tura Christian Hospital ERP -Test: JRN-RECOVERY-TEST-003 absent; no matching voucher in October 2026'
            );

        $this->assertSame('authorized', $attempt->status);
        $this->assertSame($export->id, $attempt->tally_export_id);
        $this->assertSame($response, $attempt->tally_response);
        $this->assertSame('unknown', $export->fresh()->status);
    }


    public function test_duplicate_recovery_authorization_is_blocked(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            ['label' => 'Authorize Tally Journal Recovery', 'module' => 'finance']
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-DUP-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999004,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-DUPLICATE-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
            'tally_response' =>
                '<RESPONSE><LINEERROR>Rejected</LINEERROR>'
                .'<CREATED>0</CREATED><ALTERED>0</ALTERED>'
                .'<DELETED>0</DELETED><LASTVCHID>0</LASTVCHID>'
                .'</RESPONSE>',
        ]);

        $service = app(TallyJournalRecoveryService::class);

        $service->authorizeRecovery(
            $export->id, $user->id,
            'Tally rejected voucher', 'Tally Day Book checked in Tura Christian Hospital ERP -Test: JRN-RECOVERY-DUP-001 absent; no matching voucher in October 2026'
        );

        try {
            $service->authorizeRecovery(
                $export->id, $user->id,
                'Second authorization', 'Repeated verification'
            );

            $this->fail('Duplicate authorization was permitted.');
        } catch (ValidationException $e) {
            $this->assertDatabaseCount('tally_journal_export_attempts', 1);
            $this->assertSame('unknown', $export->fresh()->status);
        }
    }


    public function test_ambiguous_tally_vouchers_block_recovery(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            ['label' => 'Authorize Tally Journal Recovery', 'module' => 'finance']
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-AMB-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999006,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-AMB-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
            'tally_response' =>
                '<RESPONSE><LINEERROR>Rejected</LINEERROR>'
                .'<CREATED>0</CREATED><ALTERED>0</ALTERED>'
                .'<DELETED>0</DELETED><LASTVCHID>0</LASTVCHID>'
                .'</RESPONSE>',
        ]);

        $mock = \Mockery::mock(
            \App\Services\Tally\TallyJournalReconciliationService::class
        );

        $mock->shouldReceive('reconcile')
            ->once()
            ->andReturn(['status' => 'ambiguous']);

        $this->app->instance(
            \App\Services\Tally\TallyJournalReconciliationService::class,
            $mock
        );

        try {
            app(TallyJournalRecoveryService::class)->authorizeRecovery(
                $export->id,
                $user->id,
                'Original import rejected',
                'Multiple possible Tally vouchers discovered'
            );

            $this->fail('Ambiguous reconciliation was incorrectly authorized.');
        } catch (ValidationException $exception) {
            $this->assertSame('unknown', $export->fresh()->status);
            $this->assertDatabaseMissing(
                'tally_journal_export_attempts',
                ['tally_export_id' => $export->id]
            );
        }
    }

    public function test_matching_tally_voucher_blocks_recovery(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            ['label' => 'Authorize Tally Journal Recovery', 'module' => 'finance']
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-MATCH-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999005,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-MATCH-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
            'tally_response' =>
                '<RESPONSE><LINEERROR>Rejected</LINEERROR>'
                .'<CREATED>0</CREATED><ALTERED>0</ALTERED>'
                .'<DELETED>0</DELETED><LASTVCHID>0</LASTVCHID>'
                .'</RESPONSE>',
        ]);

        $mock = \Mockery::mock(
            \App\Services\Tally\TallyJournalReconciliationService::class
        );

        $mock->shouldReceive('reconcile')
            ->once()
            ->andReturn(['status' => 'difference']);

        $this->app->instance(
            \App\Services\Tally\TallyJournalReconciliationService::class,
            $mock
        );

        $this->expectException(ValidationException::class);

        try {
            app(TallyJournalRecoveryService::class)->authorizeRecovery(
                $export->id,
                $user->id,
                'Original import rejected',
                'Matching Tally voucher independently identified'
            );
        } finally {
            $this->assertSame('unknown', $export->fresh()->status);
            $this->assertDatabaseMissing(
                'tally_journal_export_attempts',
                ['tally_export_id' => $export->id]
            );
        }
    }


    public function test_recovery_authorization_can_be_claimed_only_once(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            [
                'label' => 'Authorize Tally Journal Recovery',
                'module' => 'finance',
            ]
        );

        $user->permissions()->syncWithoutDetaching([
            $permission->id
        ]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-CLAIM-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999007,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-CLAIM-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        \App\Models\TallyJournalExportAttempt::create([
            'tally_export_id' => $export->id,
            'attempt_number' => 1,
            'tally_company' => config('tally.company'),
            'status' => 'authorized',
            'recovery_authorized_by' => $user->id,
            'recovery_authorized_at' => now(),
            'recovery_reason' => 'Original voucher rejected',
            'reconciliation_evidence' => 'Verified absence in Tally',
        ]);

        $service = app(
            \App\Services\Tally\TallyJournalRecoveryExecutionService::class
        );

        $claimed = $service->claim($export->id, $user->id);

        $this->assertSame('claimed', $claimed->status);
        $this->assertNotNull($claimed->claimed_at);

        try {
            $service->claim($export->id, $user->id);
            $this->fail('Authorization was claimed twice.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount(
                'tally_journal_export_attempts',
                1
            );

            $this->assertDatabaseHas(
                'tally_journal_export_attempts',
                [
                    'tally_export_id' => $export->id,
                    'status' => 'claimed',
                ]
            );
        }
    }


    public function test_unauthorized_user_cannot_claim_recovery(): void
    {
        $user = User::factory()->create();

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-UNAUTH-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999008,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-UNAUTH-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $attempt = \App\Models\TallyJournalExportAttempt::create([
            'tally_export_id' => $export->id,
            'attempt_number' => 1,
            'status' => 'authorized',
            'recovery_authorized_by' => $user->id,
            'recovery_authorized_at' => now(),
            'recovery_reason' => 'Test',
            'reconciliation_evidence' => 'Test evidence',
        ]);

        try {
            app(
                \App\Services\Tally\TallyJournalRecoveryExecutionService::class
            )->claim($export->id, $user->id);

            $this->fail('Unauthorized recovery was permitted.');
        } catch (ValidationException $exception) {
            $this->assertSame('authorized', $attempt->fresh()->status);
            $this->assertSame('unknown', $export->fresh()->status);
        }
    }


    public function test_another_authorized_user_cannot_claim_recovery(): void
    {
        $approver = User::factory()->create();
        $otherUser = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            [
                'label' => 'Authorize Tally Journal Recovery',
                'module' => 'finance',
            ]
        );

        $approver->permissions()->syncWithoutDetaching([$permission->id]);
        $otherUser->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-CROSSUSER-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999009,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $approver->id,
            'posted_by' => $approver->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-CROSSUSER-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $attempt = \App\Models\TallyJournalExportAttempt::create([
            'tally_export_id' => $export->id,
            'attempt_number' => 1,
            'status' => 'authorized',
            'recovery_authorized_by' => $approver->id,
            'recovery_authorized_at' => now(),
            'recovery_reason' => 'Rejected voucher',
            'reconciliation_evidence' => 'Tally reconciliation evidence',
        ]);

        try {
            app(
                \App\Services\Tally\TallyJournalRecoveryExecutionService::class
            )->claim($export->id, $otherUser->id);

            $this->fail('Another user claimed the recovery.');
        } catch (ValidationException $exception) {
            $this->assertSame('authorized', $attempt->fresh()->status);
            $this->assertSame('unknown', $export->fresh()->status);
        }
    }


    public function test_retry_preparation_preserves_original_export(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            ['label' => 'Authorize Tally Journal Recovery', 'module' => 'finance']
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-PREP-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999010,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $originalId = (string) \Illuminate\Support\Str::uuid();

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-ORIGINAL-001',
            'remote_id' => $originalId,
        ]);

        \App\Models\TallyJournalExportAttempt::create([
            'tally_export_id' => $export->id,
            'attempt_number' => 1,
            'tally_company' => config('tally.company'),
            'status' => 'authorized',
            'recovery_authorized_by' => $user->id,
            'recovery_authorized_at' => now(),
            'recovery_reason' => 'Rejected export',
            'reconciliation_evidence' => 'Independently verified',
        ]);

        // Mocked eligibility for isolated transmission test; real GL/mapping integration still required.
        $eligibility = \Mockery::mock(\App\Services\Tally\TallyJournalExportService::class);
        $eligibility->shouldReceive('checkEligibility')->once()
            ->andReturn(['eligible' => true, 'errors' => ['This journal already has a Tally export record.']]);
        $this->app->instance(\App\Services\Tally\TallyJournalExportService::class, $eligibility);

        $attempt = app(
            \App\Services\Tally\TallyJournalRecoveryExecutionService::class
        )->prepareRetry($export->id, $user->id);

        $this->assertSame('sending', $attempt->status);
        $this->assertNotNull($attempt->claimed_at);
        $this->assertNotNull($attempt->remote_id);
        $this->assertNotSame($originalId, $attempt->remote_id);
        $this->assertSame('unknown', $export->fresh()->status);
        $this->assertSame($originalId, $export->fresh()->remote_id);
        $this->assertDatabaseCount('tally_journal_export_attempts', 1);
    }


    public function test_uncertain_recovery_response_remains_unknown(): void
    {
        $user = User::factory()->create();

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-OUTCOME-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999011,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-OUTCOME-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $attempt = \App\Models\TallyJournalExportAttempt::create([
            'tally_export_id' => $export->id,
            'attempt_number' => 1,
            'status' => 'sending',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $response = '<RESPONSE>'
            .'<LINEERROR>Voucher rejected</LINEERROR>'
            .'<CREATED>0</CREATED>'
            .'<ALTERED>0</ALTERED>'
            .'<DELETED>0</DELETED>'
            .'<IGNORED>0</IGNORED>'
            .'<ERRORS>0</ERRORS>'
            .'<CANCELLED>0</CANCELLED>'
            .'</RESPONSE>';

        $result = app(
            \App\Services\Tally\TallyJournalRecoveryOutcomeService::class
        )->record($attempt->id, $response);

        $this->assertSame('unknown', $result->status);
        $this->assertSame($response, $result->tally_response);
        $this->assertSame('unknown', $export->fresh()->status);
    }


    public function test_successful_recovery_response_confirms_attempt(): void
    {
        $user = User::factory()->create();

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-SUCCESS-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999012,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-SUCCESS-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $attempt = \App\Models\TallyJournalExportAttempt::create([
            'tally_export_id' => $export->id,
            'attempt_number' => 1,
            'status' => 'sending',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $response = '<RESPONSE>'
            .'<CREATED>1</CREATED>'
            .'<ALTERED>0</ALTERED>'
            .'<IGNORED>0</IGNORED>'
            .'<ERRORS>0</ERRORS>'
            .'<CANCELLED>0</CANCELLED>'
            .'<LASTVCHID>9</LASTVCHID>'
            .'</RESPONSE>';

        $result = app(
            \App\Services\Tally\TallyJournalRecoveryOutcomeService::class
        )->record($attempt->id, $response);

        $this->assertSame('confirmed', $result->status);
        $this->assertNotNull($result->confirmed_at);
        $this->assertSame($response, $result->tally_response);

        $updatedExport = $export->fresh();

        $this->assertSame('confirmed', $updatedExport->status);
        $this->assertSame('9', (string) $updatedExport->tally_voucher_id);
        $this->assertNotNull($updatedExport->confirmed_at);
        $this->assertSame(
            $export->remote_id,
            $updatedExport->remote_id
        );
    }


    public function test_recovery_http_failure_is_recorded_as_unknown(): void
    {
        $user = User::factory()->create();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'tally.journal-recovery.authorize'],
            ['label' => 'Authorize Tally Journal Recovery', 'module' => 'finance']
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $journal = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-RECOVERY-HTTP-001',
            'journal_date' => '2026-10-26',
            'source_type' => 'recovery_test',
            'source_id' => 999013,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-HTTP-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        \App\Models\TallyJournalExportAttempt::create([
            'tally_export_id' => $export->id,
            'attempt_number' => 1,
            'tally_company' => config('tally.company'),
            'status' => 'authorized',
            'recovery_authorized_by' => $user->id,
            'recovery_authorized_at' => now(),
            'recovery_reason' => 'Rejected original transmission',
            'reconciliation_evidence' => 'Verified in Tally test company',
        ]);

        $builder = \Mockery::mock(
            \App\Services\Tally\TallyJournalXmlBuilder::class
        );

        $builder->shouldReceive('build')
            ->once()
            ->andReturn('<ENVELOPE><BODY>TEST</BODY></ENVELOPE>');

        $this->app->instance(
            \App\Services\Tally\TallyJournalXmlBuilder::class,
            $builder
        );

        $client = \Mockery::mock(
            \App\Services\Tally\TallyHttpClient::class
        );

        $client->shouldReceive('postXml')
            ->once()
            ->andThrow(new \RuntimeException('Simulated network failure'));

        $this->app->instance(
            \App\Services\Tally\TallyHttpClient::class,
            $client
        );

        // Mocked eligibility for isolated transmission test; real GL/mapping integration still required.
        $eligibility = \Mockery::mock(\App\Services\Tally\TallyJournalExportService::class);
        $eligibility->shouldReceive('checkEligibility')->once()
            ->andReturn(['eligible' => true, 'errors' => ['This journal already has a Tally export record.']]);
        $this->app->instance(\App\Services\Tally\TallyJournalExportService::class, $eligibility);

        $result = app(
            \App\Services\Tally\TallyJournalRecoveryTransmissionService::class
        )->send($export->id, $user->id);

        $this->assertSame('unknown', $result['status']);

        $this->assertDatabaseHas('tally_journal_export_attempts', [
            'tally_export_id' => $export->id,
            'status' => 'unknown',
        ]);

        $this->assertSame('unknown', $export->fresh()->status);
    }

}
