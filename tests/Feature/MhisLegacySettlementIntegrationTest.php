<?php

namespace Tests\Feature;

use App\Models\MhisLegacySettlement;
use App\Models\MhisOpeningReceivable;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MhisLegacySettlementIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDatabaseName() !== 'testing') {
            $this->fail('Refusing database integration tests outside the PostgreSQL testing database.');
        }
    }

    private function actor(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function opening(User $maker, string $amount = '1000.00'): MhisOpeningReceivable
    {
        return MhisOpeningReceivable::create([
            'opening_date' => '2027-04-01',
            'financial_year' => '2027-28',
            'opening_amount' => $amount,
            'statement_reference' => 'TEST-CLOSING-TB',
            'status' => 'approved',
            'created_by' => $maker->id,
            'approved_by' => $this->actor('finance_manager')->id,
            'approved_at' => now(),
        ]);
    }

    private function payload(MhisOpeningReceivable $opening, string $reference = 'TEST-UTR-001'): array
    {
        return [
            'mhis_opening_receivable_id' => $opening->id,
            'settlement_date' => '2027-04-15',
            'settlement_reference' => $reference,
            'historical_claim_reference' => 'OLD-MHIS-CLAIM-001',
            'net_amount' => '850.00',
            'tds_amount' => '100.00',
            'approved_deduction' => '50.00',
        ];
    }

    public function test_finance_can_create_a_draft_without_journal_or_tally_export(): void
    {
        $maker = $this->actor('finance');
        $opening = $this->opening($maker);
        $journals = DB::table('journal_entries')->count();
        $exports = DB::table('tally_exports')->count();

        $this->actingAs($maker)->post(route('finance.mhis-legacy-settlements.store'), $this->payload($opening))
            ->assertRedirect(route('finance.mhis-legacy-settlements.index'));

        $settlement = MhisLegacySettlement::query()->where('settlement_reference', 'TEST-UTR-001')->firstOrFail();
        $this->assertSame('draft', $settlement->status);
        $this->assertSame('1000.00', (string) $settlement->gross_settled_amount);
        $this->assertSame($maker->id, (int) $settlement->created_by);
        $this->assertSame($journals, DB::table('journal_entries')->count());
        $this->assertSame($exports, DB::table('tally_exports')->count());
    }

    public function test_duplicate_settlement_claim_reference_is_rejected(): void
    {
        $maker = $this->actor('finance');
        $opening = $this->opening($maker);
        $data = $this->payload($opening);

        $this->actingAs($maker)->post(route('finance.mhis-legacy-settlements.store'), $data)->assertRedirect();
        $this->actingAs($maker)->post(route('finance.mhis-legacy-settlements.store'), $data)
            ->assertSessionHasErrors('settlement_reference');

        $this->assertSame(1, MhisLegacySettlement::query()->where('settlement_reference', 'TEST-UTR-001')->count());
    }

    public function test_creator_cannot_verify_their_own_settlement(): void
    {
        $maker = $this->actor('finance_manager');
        $opening = $this->opening($maker);
        $settlement = $this->draft($opening, $maker);

        $this->actingAs($maker)->post(route('finance.mhis-legacy-settlement.verify', $settlement))
            ->assertSessionHasErrors();

        $this->assertSame('draft', $settlement->fresh()->status);
    }

    public function test_independent_finance_manager_can_verify_without_posting(): void
    {
        $maker = $this->actor('finance');
        $checker = $this->actor('finance_manager');
        $opening = $this->opening($maker);
        $settlement = $this->draft($opening, $maker);
        $journals = DB::table('journal_entries')->count();
        $exports = DB::table('tally_exports')->count();

        $this->actingAs($checker)->post(route('finance.mhis-legacy-settlement.verify', $settlement))
            ->assertRedirect();

        $this->assertSame('verified', $settlement->fresh()->status);
        $this->assertSame($checker->id, (int) $settlement->fresh()->verified_by);
        $this->assertSame($journals, DB::table('journal_entries')->count());
        $this->assertSame($exports, DB::table('tally_exports')->count());
    }

    public function test_verification_rejects_a_settlement_exceeding_opening_balance(): void
    {
        $maker = $this->actor('finance');
        $checker = $this->actor('finance_manager');
        $opening = $this->opening($maker, '500.00');
        $settlement = $this->draft($opening, $maker);

        $this->actingAs($checker)->post(route('finance.mhis-legacy-settlement.verify', $settlement))
            ->assertSessionHasErrors();

        $this->assertSame('draft', $settlement->fresh()->status);
    }

    private function draft(MhisOpeningReceivable $opening, User $maker): MhisLegacySettlement
    {
        return MhisLegacySettlement::create([
            'mhis_opening_receivable_id' => $opening->id,
            'settlement_date' => '2027-04-15',
            'settlement_reference' => 'TEST-UTR-001',
            'historical_claim_reference' => 'OLD-MHIS-CLAIM-001',
            'net_amount' => '850.00',
            'tds_amount' => '100.00',
            'approved_deduction' => '50.00',
            'gross_settled_amount' => '1000.00',
            'status' => 'draft',
            'created_by' => $maker->id,
        ]);
    }
    public function test_guest_cannot_access_settlement_register_or_create_draft(): void
    {
        $this->get(route('finance.mhis-legacy-settlements.index'))->assertRedirect();
        $this->post(route('finance.mhis-legacy-settlements.store'), [])->assertRedirect();
    }

    public function test_finance_maker_cannot_verify_another_users_settlement(): void
    {
        $maker = $this->actor('finance');
        $otherFinance = $this->actor('finance');
        $opening = $this->opening($maker);
        $settlement = $this->draft($opening, $maker);

        $this->actingAs($otherFinance)
            ->post(route('finance.mhis-legacy-settlement.verify', $settlement))
            ->assertForbidden();
        $this->assertSame('draft', $settlement->fresh()->status);
    }

    public function test_draft_cannot_be_created_against_unapproved_opening(): void
    {
        $maker = $this->actor('finance');
        $opening = $this->opening($maker);
        $opening->update(['status' => 'draft', 'approved_by' => null, 'approved_at' => null]);

        $this->actingAs($maker)
            ->post(route('finance.mhis-legacy-settlements.store'), $this->payload($opening))
            ->assertSessionHasErrors('mhis_opening_receivable_id');
        $this->assertSame(0, MhisLegacySettlement::query()->where('mhis_opening_receivable_id', $opening->id)->count());
    }

    public function test_draft_cannot_precede_opening_date(): void
    {
        $maker = $this->actor('finance');
        $opening = $this->opening($maker);
        $payload = $this->payload($opening);
        $payload['settlement_date'] = '2027-03-31';
        $this->actingAs($maker)
            ->post(route('finance.mhis-legacy-settlements.store'), $payload)
            ->assertSessionHasErrors('settlement_date');
    }

    public function test_zero_gross_settlement_is_rejected(): void
    {
        $maker = $this->actor('finance');
        $opening = $this->opening($maker);
        $payload = $this->payload($opening);
        foreach (['net_amount', 'tds_amount', 'approved_deduction'] as $field) {
            $payload[$field] = '0.00';
        }
        $this->actingAs($maker)
            ->post(route('finance.mhis-legacy-settlements.store'), $payload)
            ->assertSessionHasErrors('net_amount');
    }

    public function test_excess_decimal_precision_is_rejected_at_http_boundary(): void
    {
        $maker = $this->actor('finance');
        $opening = $this->opening($maker);
        $payload = $this->payload($opening);
        $payload['net_amount'] = '850.001';
        $this->actingAs($maker)
            ->post(route('finance.mhis-legacy-settlements.store'), $payload)
            ->assertSessionHasErrors('net_amount');
    }

    public function test_verified_settlement_cannot_be_verified_twice(): void
    {
        $maker = $this->actor('finance');
        $checker = $this->actor('finance_manager');
        $opening = $this->opening($maker);
        $settlement = $this->draft($opening, $maker);
        $this->actingAs($checker)
            ->post(route('finance.mhis-legacy-settlement.verify', $settlement))
            ->assertRedirect();
        $this->actingAs($checker)
            ->post(route('finance.mhis-legacy-settlement.verify', $settlement))
            ->assertSessionHasErrors();
        $this->assertSame('verified', $settlement->fresh()->status);
    }

    public function test_two_settlements_cannot_be_verified_above_opening_balance(): void
    {
        $maker = $this->actor('finance');
        $checker = $this->actor('finance_manager');
        $opening = $this->opening($maker, '1500.00');
        $first = $this->draft($opening, $maker);
        $this->actingAs($checker)
            ->post(route('finance.mhis-legacy-settlement.verify', $first))
            ->assertRedirect();
        $second = $this->draft($opening, $maker);
        $second->update(['settlement_reference' => 'TEST-UTR-002', 'historical_claim_reference' => 'OLD-MHIS-CLAIM-002']);
        $this->actingAs($checker)
            ->post(route('finance.mhis-legacy-settlement.verify', $second))
            ->assertSessionHasErrors();
        $this->assertSame('draft', $second->fresh()->status);
    }

}
