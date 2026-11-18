<?php
namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Services\Tally\TallyRecoveryLiveCheck;
use App\Services\Tally\TallyRecoveryFailurePolicy;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TallySimpleRecoveryLiveCheckTest extends TestCase
{
    private function export(): TallyExport
    {
        config(['tally.url' => 'http://tally.test:9000', 'tally.company' => 'Hospital -Test']);
        $export = new TallyExport;
        $export->setRawAttributes(['id' => 515, 'remote_id' => 'original-guid', 'export_reference' => 'TALLY-ORIGINAL']);
        $journal = new JournalEntry;
        $journal->setRawAttributes(['journal_no' => 'JRN-INV-333', 'reference_no' => 'INV-20261117-000016']);
        $export->setRelation('journalEntry', $journal);
        $export->setRelation('journalExportAttempts', new \Illuminate\Database\Eloquent\Collection());
        return $export;
    }

    private function responses(string $vouchers, string $company = 'Hospital -Test'): void
    {
        Http::preventStrayRequests();
        Http::fake(function ($request) use ($vouchers, $company) {
            if (str_contains($request->body(), 'Recovery Companies')) {
                return Http::response('<ENVELOPE><BODY><DATA><COLLECTION><COMPANY NAME="'.$company.'"/></COLLECTION></DATA></BODY></ENVELOPE>');
            }
            $this->assertStringContainsString('19000101', $request->body());
            $this->assertStringContainsString('20991231', $request->body());
            return Http::response($vouchers);
        });
    }

    public function test_connection_refused_is_distinguished_from_timeout_and_unknown_response(): void
    {
        $error = 'Transmission outcome uncertain: Unable to connect to TallyPrime: cURL error 7: Failed to connect to 10.10.10.1 port 9000 after 0 ms: Could not connect to server';
        $this->assertTrue(TallyRecoveryFailurePolicy::connectionNeverEstablished($error, null, null, null));
        $this->assertFalse(TallyRecoveryFailurePolicy::connectionNeverEstablished('cURL error 28: Operation timed out', null, null, null));
        $this->assertFalse(TallyRecoveryFailurePolicy::connectionNeverEstablished($error, '<RESPONSE/>', null, null));
        $this->assertFalse(TallyRecoveryFailurePolicy::connectionNeverEstablished($error, null, '123', null));
        $this->assertFalse(TallyRecoveryFailurePolicy::connectionNeverEstablished($error, null, null, now()));
        $this->assertFalse(TallyRecoveryFailurePolicy::connectionNeverEstablished('Unable to connect', null, null, null));
    }

    public function test_live_absence_generates_evidence_without_transmission(): void
    {
        $export = $this->export();
        $this->responses('<ENVELOPE><BODY><DATA><COLLECTION/></DATA></BODY></ENVELOPE>');
        $evidence = app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
        $this->assertStringContainsString('[AUTO-LIVE] Day Book', $evidence);
        $this->assertStringContainsString('JRN-INV-333', $evidence);
        $this->assertStringContainsString('SHA256', $evidence);
        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => str_contains($request->body(), '<TALLYREQUEST>Import'));
    }

    public function test_live_existing_remote_id_blocks_retry(): void
    {
        $export = $this->export();
        $this->responses('<ENVELOPE><BODY><DATA><COLLECTION><VOUCHER REMOTEID="original-guid"><VOUCHERNUMBER>20</VOUCHERNUMBER></VOUCHER></COLLECTION></DATA></BODY></ENVELOPE>');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
    }

    public function test_existing_invoice_reference_blocks_even_with_a_different_remote_id(): void
    {
        $export = $this->export();
        $this->responses('<ENVELOPE><BODY><DATA><COLLECTION><VOUCHER><REFERENCE>INV-20261117-000016</REFERENCE></VOUCHER></COLLECTION></DATA></BODY></ENVELOPE>');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
    }

    public function test_ipd_sales_recovery_ignores_receipt_that_only_references_the_bill(): void
    {
        $export = $this->ipdSalesExport();
        $this->responses('<ENVELOPE><BODY><DATA><COLLECTION><VOUCHER><VOUCHERTYPENAME>Receipt</VOUCHERTYPENAME>'
            .'<VOUCHERNUMBER>100</VOUCHERNUMBER><DATE>20261024</DATE><REFERENCE>IPF-20261018-000001</REFERENCE>'
            .'</VOUCHER></COLLECTION></DATA></BODY></ENVELOPE>');

        $evidence = app(TallyRecoveryLiveCheck::class)->assertAbsent($export);

        $this->assertStringContainsString('bill-only references in Receipts excluded: 1', $evidence);
        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => str_contains($request->body(), '<TALLYREQUEST>Import'));
    }

    public function test_ipd_sales_recovery_blocks_sales_voucher_with_the_same_bill(): void
    {
        $export = $this->ipdSalesExport();
        $this->responses('<ENVELOPE><BODY><DATA><COLLECTION><VOUCHER><VOUCHERTYPENAME>Sales</VOUCHERTYPENAME>'
            .'<VOUCHERNUMBER>101</VOUCHERNUMBER><REFERENCE>IPF-20261018-000001</REFERENCE>'
            .'</VOUCHER></COLLECTION></DATA></BODY></ENVELOPE>');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
    }

    public function test_ipd_sales_recovery_blocks_receipt_with_original_remote_identity(): void
    {
        $export = $this->ipdSalesExport();
        $this->responses('<ENVELOPE><BODY><DATA><COLLECTION><VOUCHER REMOTEID="original-guid">'
            .'<VOUCHERTYPENAME>Receipt</VOUCHERTYPENAME><REFERENCE>IPF-20261018-000001</REFERENCE>'
            .'</VOUCHER></COLLECTION></DATA></BODY></ENVELOPE>');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
    }

    private function ipdSalesExport(): TallyExport
    {
        $export = $this->export();
        $export->journalEntry->setRawAttributes([
            'journal_no' => 'JRN-IPD-12',
            'reference_no' => 'IPF-20261018-000001',
            'source_type' => 'ip_billing_account',
            'source_event' => 'finalized',
        ]);

        return $export;
    }

    public function test_company_not_loaded_blocks_retry(): void
    {
        $export = $this->export();
        $this->responses('<ENVELOPE><BODY><DATA><COLLECTION/></DATA></BODY></ENVELOPE>', 'Another company -Test');
        $this->expectException(\RuntimeException::class);
        app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
    }

    public function test_server_running_text_is_not_proof_of_absence(): void
    {
        $export = $this->export();
        $this->responses('<RESPONSE>TallyPrime Server is Running</RESPONSE>');
        $this->expectException(\RuntimeException::class);
        app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
    }

    public function test_tally_query_error_is_not_proof_of_absence(): void
    {
        $export = $this->export();
        $this->responses('<ENVELOPE><BODY><DATA><LINEERROR>Company unavailable</LINEERROR><COLLECTION/></DATA></BODY></ENVELOPE>');
        $this->expectException(\RuntimeException::class);
        app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
    }

    public function test_connection_failure_during_live_check_blocks_retry(): void
    {
        $export = $this->export();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('connection unavailable'));
        $this->expectException(\RuntimeException::class);
        app(TallyRecoveryLiveCheck::class)->assertAbsent($export);
    }

    public function test_non_test_company_is_blocked_without_any_request(): void
    {
        $export = $this->export();
        config(['tally.company' => 'Hospital Production']);
        Http::fake();
        try { app(TallyRecoveryLiveCheck::class)->assertAbsent($export); $this->fail('Production recovery allowed.'); }
        catch (\RuntimeException $e) { Http::assertNothingSent(); $this->assertStringContainsString('test company', $e->getMessage()); }
    }
}
