<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TallyAutomaticJournalHttpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName());
    }

    public function test_guest_cannot_confirm_automatic_match(): void
    {
        $this->post(route(
            'finance.tally.reconciliation.journal.automatic-match',
            ['journalEntry' => 999999]
        ), [
            'tally_imported_voucher_id' => 999999,
            'confirm_no_transmission' => '1',
        ])->assertRedirect();
    }

    public function test_confirmation_requires_authentication_and_authorisation(): void
    {
        $user = User::factory()->create();

        $journal = JournalEntry::create([
            'journal_no' => 'JRN-HTTP-RBAC-TEST',
            'journal_date' => '2026-11-06',
            'source_type' => 'test',
            'source_id' => 'http-rbac-test',
            'source_event' => 'created',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route(
            'finance.tally.reconciliation.journal.automatic-match',
            ['journalEntry' => $journal->id]
        ), [
            'tally_imported_voucher_id' => 999999,
            'confirm_no_transmission' => '1',
        ]);

        $response->assertForbidden();
    }
}
