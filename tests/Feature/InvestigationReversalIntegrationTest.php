<?php

namespace Tests\Feature;

use App\Models\InvestigationCancellationRequest;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\Investigation\InvestigationCancellationAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Run ONLY on the isolated PostgreSQL database named testing.
 * All writes in these tests are rolled back by DatabaseTransactions.
 * No actual cash/bank refund is performed.
 */
class InvestigationReversalIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::getDefaultConnection(), 'PostgreSQL required');
        $this->assertSame('testing', DB::selectOne('select current_database() as name')->name,
            'REFUSING TO RUN: current DB is not testing');
    }

    
public function test_unprivileged_user_cannot_execute_revenue_reversal(): void
{
    $actor = User::factory()->create([
        'role' => 'reception',
    ]);

    $cancellation = new InvestigationCancellationRequest();

    $before = JournalEntry::count();

    try {
        app(InvestigationCancellationAccountingService::class)
            ->reverseRevenue($cancellation, $actor);

        $this->fail('Unprivileged actor was allowed');

    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {

        $this->assertSame(403, $e->getStatusCode());

        $this->assertSame(
            $before,
            JournalEntry::count(),
            'Unauthorized reversal must not create a journal entry.'
        );
    }
}


    public function test_unprivileged_user_cannot_record_refund(): void
    {
        $actor = User::factory()->create(['role' => 'reception']);
        $before = JournalEntry::count();
        try {
            app(InvestigationCancellationAccountingService::class)->recordCompletedRefund(
                new InvestigationCancellationRequest(), new Payment(), $actor, 'TEST-NOT-PAID', '500.00'
            );
            $this->fail('Unprivileged actor was allowed');
        } catch (ValidationException $e) {
            $this->assertSame($before, JournalEntry::count());
            $this->assertArrayHasKey('cancellation', $e->errors());
        }
    }

    public function test_reversal_posts_balanced_journal_and_second_attempt_is_rejected_when_fixture_exists(): void
    {
        // Never copy real patient invoices to the test database automatically.
        // Create an independently authorized, synthetic, approved investigation fixture
        // in testing first. This test will skip if one does not exist.
        $fixtureId = env('TCH_TEST_APPROVED_INVESTIGATION_CANCELLATION_ID');
        if (!$fixtureId || !ctype_digit((string) $fixtureId)) {
            $this->markTestSkipped('Set TCH_TEST_APPROVED_INVESTIGATION_CANCELLATION_ID to an approved synthetic record in testing.');
        }
        $request = InvestigationCancellationRequest::query()->findOrFail((int) $fixtureId);
        $this->assertSame('approved', $request->status);
        $this->assertNull($request->executed_at);
        $this->assertSame('investigation', $request->invoice->invoice_type);
        $this->assertStringStartsWith('TEST-', (string) $request->reason,
            'Fixture reason must start with TEST-; never use a real record');

        // Admin override permits financial execution in existing RBAC, so use only
        // in isolated synthetic tests. Production permission design is separate.
        $actor = User::factory()->create(['role' => 'admin']);
        $originalPayments = Payment::where('invoice_id', $request->invoice_id)
            ->orderBy('id')->get(['id', 'amount', 'receipt_no'])->toArray();
        $invoiceTotal = (string) $request->invoice->total_amount;
        $journal = app(InvestigationCancellationAccountingService::class)->reverseRevenue($request, $actor);
        $this->assertSame('posted', $journal->status);
        $this->assertSame('revenue_reversed', $journal->source_event);
        $this->assertSame('executed', $request->fresh()->status);
        $debits = (int) round($journal->lines->sum('debit') * 100);
        $credits = (int) round($journal->lines->sum('credit') * 100);
        $this->assertSame($debits, $credits);
        $this->assertSame((int) round((float) $request->approved_amount * 100), $debits);
        $this->assertSame($originalPayments, Payment::where('invoice_id', $request->invoice_id)
            ->orderBy('id')->get(['id', 'amount', 'receipt_no'])->toArray());
        $this->assertSame($invoiceTotal, (string) $request->invoice->fresh()->total_amount);
        try {
            app(InvestigationCancellationAccountingService::class)->reverseRevenue($request, $actor);
            $this->fail('Duplicate cancellation reversal succeeded');
        } catch (ValidationException $e) {
            $this->assertSame(1, JournalEntry::where('source_type', 'investigation_cancellation_request')
                ->where('source_id', $request->id)->where('source_event', 'revenue_reversed')->count());
        }
        // DatabaseTransactions rolls back this entire synthetic test.
    }
}
