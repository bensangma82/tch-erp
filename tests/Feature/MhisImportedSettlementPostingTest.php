<?php

namespace Tests\Feature;

use App\Models\{Admission, GeneralLedgerAccount, IpBillingAccount, IpBillingMhisClaim, IpBillingMhisReceipt, JournalEntry, JournalEntryLine, MhisSettlementImportBatch, MhisSettlementImportRow, Patient, User};
use App\Services\Accounting\JournalPostingService;
use App\Services\Mhis\MhisImportedSettlementPostingService;
use App\Services\Tally\TallyVoucherXmlRouter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MhisImportedSettlementPostingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName(), 'Never run financial acceptance tests on an operational database.');
    }

    private function fixture(bool $recognition = true): array
    {
        $actor = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($actor);
        $key = 'IMP-'.bin2hex(random_bytes(6));
        $patient = Patient::create(['uhid' => $key, 'first_name' => 'Import', 'last_name' => 'Test', 'sex' => 'male', 'is_active' => true]);
        $admission = Admission::create(['admission_no' => $key, 'patient_id' => $patient->id, 'admitted_at' => now(), 'status' => 'admitted']);
        $account = IpBillingAccount::create([
            'account_no' => $key, 'admission_id' => $admission->id, 'patient_id' => $patient->id,
            'opened_at' => now(), 'status' => 'finalized', 'subtotal' => '3000.00', 'net_amount' => '3000.00',
            'paid_amount' => '0.00', 'balance_amount' => '3000.00', 'final_bill_no' => 'FIN-'.$key, 'finalized_at' => now(),
        ]);
        $claim = IpBillingMhisClaim::create([
            'ip_billing_account_id' => $account->id, 'admission_id' => $admission->id, 'patient_id' => $patient->id,
            'mhis_registration_id' => $key, 'claim_no' => $key, 'claim_amount' => '3000.00',
            'expected_amount' => '3000.00', 'approved_amount' => '3000.00', 'status' => 'approved',
            'approval_date' => today()->subDays(2), 'created_by' => $actor->id,
        ]);
        $gl = [];
        foreach (['1130', '1200', '1210', '1220'] as $code) {
            $gl[$code] = GeneralLedgerAccount::firstOrCreate(['account_code' => $code], [
                'account_name' => 'Import Test '.$code, 'account_type' => 'asset', 'account_group' => 'Current Assets',
                'normal_balance' => 'debit', 'is_active' => true, 'allow_posting' => true,
            ]);
            $gl[$code]->update(['tally_ledger_name' => 'Test Ledger '.$code]);
        }
        if ($recognition) {
            $journal = JournalEntry::create([
                'journal_no' => 'REC-'.$key, 'journal_date' => today()->subDays(2),
                'source_type' => 'ip_billing_mhis_claim', 'source_id' => (string) $claim->id,
                'source_event' => 'approved', 'status' => 'draft', 'created_by' => $actor->id,
            ]);
            foreach ([['1210', '3000.00', '0.00'], ['1200', '0.00', '3000.00']] as $i => [$code, $dr, $cr]) {
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'line_no' => $i + 1,
                    'general_ledger_account_id' => $gl[$code]->id, 'debit' => $dr, 'credit' => $cr]);
            }
            app(JournalPostingService::class)->post($journal);
        }
        $batch = MhisSettlementImportBatch::create(['original_filename' => 'test.xlsx', 'file_sha256' => hash('sha256', $key), 'status' => 'imported', 'total_rows' => 1, 'uploaded_by' => $actor->id]);
        $row = $batch->rows()->create([
            'excel_row_number' => 2, 'registration_id' => $key, 'initiated_amount' => '3000.00',
            'approved_amount' => '3000.00', 'tds_amount' => '300.00', 'net_amount' => '2700.00',
            'utr_reference' => 'UTR-'.$key, 'payment_date' => today()->subDay(),
            'payment_status' => 'Payment Accomplished', 'match_status' => 'unmatched',
        ]);
        return compact('claim', 'batch', 'row', 'gl');
    }

    public function test_posts_gross_tds_and_net_once_and_routes_to_tally_receipt(): void
    {
        $f = $this->fixture();
        $service = app(MhisImportedSettlementPostingService::class);
        $this->assertSame(1, $service->postBatch($f['batch'], true)['posted']);
        $row = $f['row']->fresh();
        $receipt = IpBillingMhisReceipt::findOrFail($row->posted_receipt_id);
        $this->assertSame('3000.00', $receipt->amount);
        $this->assertSame('300.00', $receipt->tds_amount);
        $this->assertSame('posted', $row->match_status);
        $this->assertNotNull($row->bank_verified_by);
        $this->assertSame('settled', $f['claim']->fresh()->status);
        $this->assertSame('3000.00', $f['claim']->fresh()->settlement_amount);
        $journal = JournalEntry::with('lines.generalLedgerAccount')->findOrFail($row->posted_journal_id);
        $this->assertSame('posted', $journal->status);
        $lines = $journal->lines->keyBy(fn ($line) => $line->generalLedgerAccount->account_code);
        $this->assertSame('2700.00', $lines['1130']->debit);
        $this->assertSame('300.00', $lines['1220']->debit);
        $this->assertSame('3000.00', $lines['1210']->credit);
        config(['tally.company' => 'TEST ONLY']);
        $xml = app(\App\Services\Tally\TallyMhisReceiptXmlBuilder::class)->build($journal);
        $this->assertStringContainsString('VCHTYPE="Receipt"', $xml);
        $this->assertStringContainsString('-2700.00', $xml);
        $this->assertStringContainsString('-300.00', $xml);
        $this->assertSame(0, $service->postBatch($f['batch'], true)['posted']);
        $this->assertSame(1, IpBillingMhisReceipt::where('ip_billing_mhis_claim_id', $f['claim']->id)->count());
    }

    public function test_missing_recognition_rolls_back_receipt_claim_and_journal(): void
    {
        $f = $this->fixture(false);
        $this->assertSame(1, app(MhisImportedSettlementPostingService::class)->postBatch($f['batch'], true)['review_required']);
        $this->assertSame(0, IpBillingMhisReceipt::where('ip_billing_mhis_claim_id', $f['claim']->id)->count());
        $this->assertSame('approved', $f['claim']->fresh()->status);
        $this->assertNull($f['row']->fresh()->posted_journal_id);
        $this->assertStringContainsString('posted MHIS approval journal', $f['row']->fresh()->match_notes);
    }

    public function test_invalid_net_plus_tds_does_not_post(): void
    {
        $f = $this->fixture();
        $f['row']->update(['net_amount' => '2600.00']);
        $this->assertSame(1, app(MhisImportedSettlementPostingService::class)->postBatch($f['batch'], true)['review_required']);
        $this->assertNull($f['row']->fresh()->posted_receipt_id);
        $this->assertSame('approved', $f['claim']->fresh()->status);
    }

    public function test_existing_receipt_is_not_duplicated(): void
    {
        $f = $this->fixture();
        IpBillingMhisReceipt::create([
            'ip_billing_mhis_claim_id' => $f['claim']->id, 'ip_billing_account_id' => $f['claim']->ip_billing_account_id,
            'admission_id' => $f['claim']->admission_id, 'patient_id' => $f['claim']->patient_id,
            'receipt_date' => today(), 'amount' => '2700.00', 'tds_amount' => '0.00', 'status' => 'active', 'received_by' => auth()->id(),
        ]);
        $this->assertSame(0, app(MhisImportedSettlementPostingService::class)->postBatch($f['batch'], true)['posted']);
        $this->assertSame('review_required', $f['row']->fresh()->match_status);
        $this->assertSame(1, IpBillingMhisReceipt::where('ip_billing_mhis_claim_id', $f['claim']->id)->count());
    }

    public function test_http_requires_bank_verification(): void
    {
        $f = $this->fixture();
        $this->withSession(['_token' => 'mhis-test-token'])
            ->post(route('finance.mhis-settlement-import.post', $f['batch']), [
                '_token' => 'mhis-test-token',
            ])->assertSessionHasErrors('bank_verified');
        $this->assertNull($f['row']->fresh()->posted_receipt_id);
    }

    public function test_http_denies_actor_without_post_permission(): void
    {
        $f = $this->fixture();
        $this->actingAs(User::factory()->create(['role' => 'reception', 'is_active' => true, 'email_verified_at' => now()]));
        $this->withSession(['_token' => 'mhis-test-token'])
            ->post(route('finance.mhis-settlement-import.post', $f['batch']), [
                '_token' => 'mhis-test-token',
                'bank_verified' => '1',
            ])->assertForbidden();
        $this->assertNull($f['row']->fresh()->posted_receipt_id);
    }

    public function test_duplicate_batch_claim_link_is_not_posted_twice(): void
    {
        $f = $this->fixture();
        $service = app(MhisImportedSettlementPostingService::class);
        $service->postBatch($f['batch'], true);
        $batch = MhisSettlementImportBatch::create(['original_filename' => 'copy.xlsx', 'file_sha256' => hash('sha256', 'copy-'.$f['batch']->id), 'status' => 'imported']);
        $row = $f['row']->replicate(['posted_receipt_id', 'posted_journal_id', 'posted_by', 'posted_at', 'bank_verified_by', 'bank_verified_at']);
        $row->batch_id = $batch->id;
        $row->match_status = 'unmatched';
        $row->matched_claim_id = null;
        $row->save();
        $this->assertSame(0, $service->postBatch($batch, true)['posted']);
        $this->assertSame('review_required', $row->fresh()->match_status);
        $this->assertSame(1, IpBillingMhisReceipt::where('ip_billing_mhis_claim_id', $f['claim']->id)->count());
    }
    public function test_future_payment_date_requires_review(): void
    {
        $f = $this->fixture();
        $f['row']->update(['payment_date' => today()->addDay()]);
        $this->assertSame(1, app(MhisImportedSettlementPostingService::class)->postBatch($f['batch'], true)['review_required']);
        $this->assertNull($f['row']->fresh()->posted_receipt_id);
    }

    public function test_amount_mismatch_does_not_change_approved_claim(): void
    {
        $f = $this->fixture();
        $f['claim']->update(['approved_amount' => '2700.00']);
        $this->assertSame(0, app(MhisImportedSettlementPostingService::class)->postBatch($f['batch'], true)['posted']);
        $this->assertSame('2700.00', $f['claim']->fresh()->approved_amount);
        $this->assertNull($f['row']->fresh()->posted_receipt_id);
        $this->assertSame('review_required', $f['row']->fresh()->match_status);
    }

    public function test_non_finalized_bill_rolls_back_settlement(): void
    {
        $f = $this->fixture();
        IpBillingAccount::whereKey($f['claim']->ip_billing_account_id)->update(['status' => 'open']);
        $this->assertSame(1, app(MhisImportedSettlementPostingService::class)->postBatch($f['batch'], true)['review_required']);
        $this->assertSame('approved', $f['claim']->fresh()->status);
        $this->assertSame(0, IpBillingMhisReceipt::where('ip_billing_mhis_claim_id', $f['claim']->id)->count());
    }

}
