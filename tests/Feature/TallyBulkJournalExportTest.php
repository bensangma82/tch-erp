<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TallyBulkJournalExportTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['_token' => 'tally-bulk-test-token'])
            ->withHeader('X-CSRF-TOKEN', 'tally-bulk-test-token');

        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame(
            'testing',
            DB::connection()->getDatabaseName()
        );

        config()->set('tally.journal_export_enabled', true);
    }

    public function test_guest_cannot_bulk_export(): void
    {
        $this->post(route('finance.tally.journals.send-selected'), [
            'journal_ids' => [1],
        ])->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_bulk_export(): void
    {
        $user = User::factory()->create([
            'role' => 'reception',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('finance.tally.journals.send-selected'), [
                'journal_ids' => [1],
            ])
            ->assertForbidden();
    }

    public function test_empty_selection_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('finance.tally.journals.send-selected'), [
                'journal_ids' => [],
            ])
            ->assertSessionHasErrors('journal_ids');
    }

    public function test_more_than_twenty_journals_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('finance.tally.journals.send-selected'), [
                'journal_ids' => range(1, 21),
            ])
            ->assertSessionHasErrors('journal_ids');
    }

    public function test_duplicate_ids_are_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('finance.tally.journals.send-selected'), [
                'journal_ids' => [1, 1],
            ])
            ->assertSessionHasErrors('journal_ids.1');
    }

    private function makeBulkJournal(User $user, int $sourceId): \App\Models\JournalEntry
    {
        return \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-BULK-TEST-'.$sourceId,
            'journal_date' => '2026-11-06',
            'source_type' => 'bulk_export_test',
            'source_id' => $sourceId,
            'source_event' => 'test',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);
    }

    private function makeBulkAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_uncertain_transmission_stops_remaining_batch(): void
    {
        $user = $this->makeBulkAdmin();

        $first = $this->makeBulkJournal($user, 990101);
        $second = $this->makeBulkJournal($user, 990102);

        $eligibility = \Mockery::mock(
            \App\Services\Tally\TallyJournalExportService::class
        );
        $eligibility->shouldReceive('checkEligibility')
            ->andReturn(['eligible' => true, 'errors' => []]);

        $this->app->instance(
            \App\Services\Tally\TallyJournalExportService::class,
            $eligibility
        );

        $router = \Mockery::mock(
            \App\Services\Tally\TallyVoucherXmlRouter::class
        );
        $router->shouldReceive('build')
            ->once()
            ->andReturn('<ENVELOPE><BODY>TEST</BODY></ENVELOPE>');

        $this->app->instance(
            \App\Services\Tally\TallyVoucherXmlRouter::class,
            $router
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

        $response = $this->actingAs($user)
            ->from(route('finance.tally.journals.index'))
            ->post(route('finance.tally.journals.send-selected'), [
                'journal_ids' => [$first->id, $second->id],
            ]);

        $response->assertRedirect(route('finance.tally.journals.index'));
        $response->assertSessionHas('tally_bulk_stopped', true);

        $results = session('tally_bulk_results');

        $this->assertCount(1, $results);
        $this->assertSame('needs_review', $results[0]['status']);

        $this->assertDatabaseHas('tally_exports', [
            'journal_entry_id' => $first->id,
            'status' => 'unknown',
        ]);

        $this->assertDatabaseMissing('tally_exports', [
            'journal_entry_id' => $second->id,
        ]);
    }

    public function test_previously_exported_journal_is_skipped(): void
    {
        $user = $this->makeBulkAdmin();
        $journal = $this->makeBulkJournal($user, 990103);

        \App\Models\TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-BULK-EXISTING-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $client = \Mockery::mock(
            \App\Services\Tally\TallyHttpClient::class
        );
        $client->shouldNotReceive('postXml');

        $this->app->instance(
            \App\Services\Tally\TallyHttpClient::class,
            $client
        );

        $response = $this->actingAs($user)
            ->from(route('finance.tally.journals.index'))
            ->post(route('finance.tally.journals.send-selected'), [
                'journal_ids' => [$journal->id],
            ]);

        $response->assertRedirect(route('finance.tally.journals.index'));
        $response->assertSessionHas('tally_bulk_stopped', false);

        $results = session('tally_bulk_results');

        $this->assertCount(1, $results);
        $this->assertSame('skipped', $results[0]['status']);

        $this->assertSame(
            1,
            \App\Models\TallyExport::where(
                'journal_entry_id', $journal->id
            )->count()
        );
    }


    public function test_two_successful_transmissions_are_confirmed(): void
    {
        $user = $this->makeBulkAdmin();

        $first = $this->makeBulkJournal($user, 990104);
        $second = $this->makeBulkJournal($user, 990105);

        $eligibility = \Mockery::mock(
            \App\Services\Tally\TallyJournalExportService::class
        );

        $eligibility->shouldReceive('checkEligibility')
            ->andReturn(['eligible' => true, 'errors' => []]);

        $this->app->instance(
            \App\Services\Tally\TallyJournalExportService::class,
            $eligibility
        );

        $router = \Mockery::mock(
            \App\Services\Tally\TallyVoucherXmlRouter::class
        );

        $router->shouldReceive('build')
            ->twice()
            ->andReturn('<ENVELOPE><BODY>TEST</BODY></ENVELOPE>');

        $this->app->instance(
            \App\Services\Tally\TallyVoucherXmlRouter::class,
            $router
        );

        $client = \Mockery::mock(
            \App\Services\Tally\TallyHttpClient::class
        );

        $client->shouldReceive('postXml')
            ->twice()
            ->andReturn('<ENVELOPE><RESPONSE>SUCCESS</RESPONSE></ENVELOPE>');

        $this->app->instance(
            \App\Services\Tally\TallyHttpClient::class,
            $client
        );

        $parser = \Mockery::mock(
            \App\Services\Tally\TallyResponseParser::class
        );

        $parser->shouldReceive('parse')
            ->twice()
            ->andReturn([
                'success' => true,
                'created' => 1,
                'altered' => 0,
                'ignored' => 0,
                'errors' => 0,
                'cancelled' => 0,
                'last_voucher_id' => '501',
                'raw' => '<ENVELOPE>CONFIRMED</ENVELOPE>',
                'message' => null,
            ]);

        $this->app->instance(
            \App\Services\Tally\TallyResponseParser::class,
            $parser
        );

        $response = $this->actingAs($user)
            ->from(route('finance.tally.journals.index'))
            ->post(route('finance.tally.journals.send-selected'), [
                'journal_ids' => [$first->id, $second->id],
            ]);

        $response->assertRedirect(route('finance.tally.journals.index'));
        $response->assertSessionHas('tally_bulk_stopped', false);

        $results = session('tally_bulk_results');

        $this->assertCount(2, $results);
        $this->assertSame('confirmed', $results[0]['status']);
        $this->assertSame('confirmed', $results[1]['status']);

        foreach ([$first, $second] as $journal) {
            $this->assertDatabaseHas('tally_exports', [
                'journal_entry_id' => $journal->id,
                'status' => 'confirmed',
            ]);
        }
    }


    public function test_ipd_advance_dependency_requires_confirmed_sales(): void
    {
        $user = $this->makeBulkAdmin();

        $sales = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-BULK-IPD-990201',
            'journal_date' => '2026-11-06',
            'source_type' => 'ip_billing_account',
            'source_id' => 990201,
            'source_event' => 'finalized',
            'reference_no' => 'IPF-BULK-TEST-001',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $advance = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-BULK-IPAPPLY-990202',
            'journal_date' => '2026-11-06',
            'source_type' => 'ip_billing_account',
            'source_id' => 990202,
            'source_event' => 'advance_applied',
            'reference_no' => 'IPF-BULK-TEST-001',
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $preflight = app(
            \App\Services\Tally\TallyJournalExportPreflightService::class
        );

        $message = $preflight->failure($advance);

        $this->assertNotNull($message);
        $this->assertStringContainsString(
            $sales->journal_no,
            $message
        );

        \App\Models\TallyExport::create([
            'journal_entry_id' => $sales->id,
            'status' => 'unknown',
            'export_reference' => 'TEST-IPD-UNKNOWN-001',
            'remote_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $this->assertNotNull($preflight->failure($advance));

        \App\Models\TallyExport::where(
            'journal_entry_id', $sales->id
        )->update(['status' => 'confirmed']);

        $this->assertNull($preflight->failure($advance));
    }

    public function test_individual_xml_preparation_failure_returns_redirect(): void
    {
        $user = $this->makeBulkAdmin();
        $journal = $this->makeBulkJournal($user, 990203);

        $eligibility = \Mockery::mock(
            \App\Services\Tally\TallyJournalExportService::class
        );

        $eligibility->shouldReceive('checkEligibility')
            ->andReturn(['eligible' => true, 'errors' => []]);

        $this->app->instance(
            \App\Services\Tally\TallyJournalExportService::class,
            $eligibility
        );

        $router = \Mockery::mock(
            \App\Services\Tally\TallyVoucherXmlRouter::class
        );

        $router->shouldReceive('build')
            ->once()
            ->andThrow(new \RuntimeException(
                'Final bill or patient reference is invalid.'
            ));

        $this->app->instance(
            \App\Services\Tally\TallyVoucherXmlRouter::class,
            $router
        );

        $client = \Mockery::mock(
            \App\Services\Tally\TallyHttpClient::class
        );

        $client->shouldNotReceive('postXml');

        $this->app->instance(
            \App\Services\Tally\TallyHttpClient::class,
            $client
        );

        $response = $this->actingAs($user)
            ->from(route('finance.tally.journals.index'))
            ->post(
                route('finance.tally.journals.send', $journal->id),
                [
                    'tally_accounting_date' =>
                        $journal->journal_date->format('Y-m-d'),
                ]
            );

        $response->assertRedirect(route('finance.tally.journals.index'));
        $response->assertSessionHas('error', function ($message) {
            return str_contains(
                $message,
                'Final bill or patient reference is invalid.'
            );
        });

        $this->assertDatabaseMissing('tally_exports', [
            'journal_entry_id' => $journal->id,
        ]);
    }


    public function test_cancelled_receipt_is_skipped_before_preparation_and_batch_continues(): void
    {
        $this->assertGuardBlocksBeforePreparation('received', 990301);
    }

    public function test_cancelled_reversal_is_skipped_before_preparation_and_batch_continues(): void
    {
        $this->assertGuardBlocksBeforePreparation('reversed', 990302);
    }

    private function assertGuardBlocksBeforePreparation(string $event, int $sourceId): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $user = $this->makeBulkAdmin();
        $blocked = \App\Models\JournalEntry::create([
            'journal_no' => 'JRN-CANCEL-MERGE-'.$sourceId,
            'journal_date' => '2026-11-17',
            'source_type' => 'ip_billing_payment',
            'source_id' => $sourceId,
            'source_event' => $event,
            'status' => 'posted',
            'created_by' => $user->id,
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);
        $next = $this->makeBulkJournal($user, $sourceId + 10);
        $reason = 'Cancelled receipt pair: exclude from new export.';
        $guard = \Mockery::mock(\App\Services\Tally\TallyCancelledReceiptGuard::class);
        $guard->shouldReceive('check')->once()
            ->with(\Mockery::on(fn ($journal) => $journal->id === $blocked->id && $journal->source_event === $event))
            ->andReturn(['allowed' => false, 'reason' => $reason]);
        $this->app->instance(\App\Services\Tally\TallyCancelledReceiptGuard::class, $guard);

        // Only the next journal reaches eligibility; the cancelled one must never be prepared.
        $service = \Mockery::mock(\App\Services\Tally\TallyJournalExportService::class);
        $service->shouldReceive('checkEligibility')->once()
            ->with(\Mockery::on(fn ($journal) => $journal->id === $next->id))
            ->andReturn(['eligible' => false, 'errors' => ['Test-only ineligible follow-on journal.']]);
        $this->app->instance(\App\Services\Tally\TallyJournalExportService::class, $service);
        $router = \Mockery::mock(\App\Services\Tally\TallyVoucherXmlRouter::class);
        $router->shouldNotReceive('build');
        $this->app->instance(\App\Services\Tally\TallyVoucherXmlRouter::class, $router);
        $client = \Mockery::mock(\App\Services\Tally\TallyHttpClient::class);
        $client->shouldNotReceive('postXml');
        $this->app->instance(\App\Services\Tally\TallyHttpClient::class, $client);

        $response = $this->actingAs($user)->from(route('finance.tally.journals.index'))
            ->post(route('finance.tally.journals.send-selected'), ['journal_ids' => [$blocked->id, $next->id]]);
        $response->assertRedirect(route('finance.tally.journals.index'));
        $response->assertSessionHas('tally_bulk_stopped', false);
        $results = session('tally_bulk_results');
        $this->assertCount(2, $results);
        $this->assertSame($blocked->journal_no, $results[0]['journal']);
        $this->assertSame('skipped', $results[0]['status']);
        $this->assertSame($reason, $results[0]['message']);
        $this->assertSame($next->journal_no, $results[1]['journal']);
        $this->assertSame('Journal eligibility check failed.', $results[1]['message']);
        foreach ([$blocked, $next] as $journal) {
            $this->assertDatabaseMissing('tally_exports', ['journal_entry_id' => $journal->id]);
        }
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

}
