<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\TallyExport;
use App\Models\TallyImportedVoucher;
use App\Models\TallyImportedVoucherEntry;
use App\Models\User;
use App\Services\Accounting\JournalPostingService;
use App\Services\Tally\TallyAutomaticJournalMatchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class TallyAutomaticJournalMatchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::connection()->getDatabaseName(),
            'Refusing automatic reconciliation test outside isolated testing database.');
        config()->set('tally.company', 'TCH-AUTOMATED-TEST-COMPANY');
    }

    protected function fixture(bool $invoiceSource = false): array
    {
        $user = User::factory()->create();
        $tag = strtoupper(bin2hex(random_bytes(6)));
        $cash = GeneralLedgerAccount::firstOrCreate(['account_code' => '1100'], [
            'account_name' => 'Main Cash', 'account_type' => 'asset',
            'normal_balance' => 'debit', 'is_active' => true, 'allow_posting' => true,
        ]);
        $advance = GeneralLedgerAccount::firstOrCreate(['account_code' => '2100'], [
            'account_name' => 'Patient Advances', 'account_type' => 'liability',
            'normal_balance' => 'credit', 'is_active' => true, 'allow_posting' => true,
        ]);
        $cash->update(['tally_ledger_name' => 'Test Main Cash']);
        $advance->update(['tally_ledger_name' => 'Test Patient Advances']);
        $invoiceId = null;

        if ($invoiceSource) {
            $patientId = DB::table('patients')->insertGetId([
                'uhid' => 'AUTO-'.$tag,
                'first_name' => 'Reconciliation Test',
                'sex' => 'Male',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_no' => 'INV-AUTO-'.$tag,
                'patient_id' => $patientId,
                'invoice_date' => '2026-11-06',
                'invoice_type' => 'opd',
                'subtotal' => 427.80,
                'discount' => 0,
                'total_amount' => 427.80,
                'paid_amount' => 0,
                'balance_amount' => 427.80,
                'status' => 'issued',
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $journal = JournalEntry::create([
            'journal_no' => 'JRN-TCH-TEST-'.$tag,
            'journal_date' => '2026-11-06',
            'source_type' => $invoiceSource ? 'invoice' : 'ip_billing_advance',
            'source_id' => $invoiceSource ? (string) $invoiceId : 'TEST-'.$tag,
            'source_event' => $invoiceSource ? 'issued' : 'received', 'status' => 'draft', 'created_by' => $user->id,
        ]);
        foreach ([[$cash->id, '427.80', '0.00'], [$advance->id, '0.00', '427.80']] as $i => [$id, $debit, $credit]) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id, 'line_no' => $i + 1,
                'general_ledger_account_id' => $id, 'debit' => $debit, 'credit' => $credit,
            ]);
        }
        $this->actingAs($user);
        app(JournalPostingService::class)->post($journal);
        $journal->refresh();
        $voucher = TallyImportedVoucher::create([
            'tally_company' => 'TCH-AUTOMATED-TEST-COMPANY',
            'guid' => 'AUTO-TEST-GUID-'.$tag, 'voucher_date' => '2026-11-06',
            'voucher_type' => 'Receipt', 'voucher_number' => 'TEST-'.$tag,
            'narration' => 'ERP '.$journal->journal_no.' | existing Tally entry',
            'reconciliation_status' => 'pending',
        ]);
        foreach ([['Test Main Cash', '-427.80', true], ['Test Patient Advances', '427.80', false]] as $i => [$ledger, $amount, $debit]) {
            TallyImportedVoucherEntry::create([
                'tally_imported_voucher_id' => $voucher->id, 'line_no' => $i + 1,
                'ledger_name' => $ledger, 'amount' => $amount, 'is_deemed_positive' => $debit,
            ]);
        }
        return [$journal, $voucher, $user];
    }

    public function test_ledger_mismatch_is_rejected_without_export(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $voucher->entries()->where('is_deemed_positive', true)
            ->firstOrFail()->update(['ledger_name' => 'Incorrect Cash Ledger']);

        $result = app(TallyAutomaticJournalMatchService::class)
            ->preview($journal->fresh());

        $this->assertSame('ledger_mismatch', $result['status']);
        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(0,
            TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_confirmed_match_cannot_be_applied_twice(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $service = app(TallyAutomaticJournalMatchService::class);

        $service->apply($journal->id, $voucher->id, $user->id);

        $this->expectException(RuntimeException::class);

        try {
            $service->apply($journal->id, $voucher->id, $user->id);
        } finally {
            $this->assertSame(1,
                TallyExport::where('journal_entry_id', $journal->id)->count());
            $this->assertSame('matched',
                $voucher->fresh()->reconciliation_status);
        }
    }

    public function test_authorised_http_confirmation_requires_checkbox(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'finance.reconciliation.manage'],
            [
                'module' => 'finance',
                'label' => 'Manage Tally Reconciliation',
                'description' => 'Test permission',
            ]
        );

        $user->forceFill([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        DB::table('role_permission')->updateOrInsert(
            [
                'role' => 'admin',
                'permission_id' => $permission->id,
            ],
            ['updated_at' => now()]
        );

        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertTrue($user->fresh()->hasPermission('finance.reconciliation.manage'));
        $this->assertTrue((bool) $user->fresh()->is_active);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user)->post(route(
            'finance.tally.reconciliation.journal.automatic-match',
            $journal
        ), [
            'tally_imported_voucher_id' => $voucher->id,
        ])->assertSessionHasErrors('confirm_no_transmission');

        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(0,
            TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_authorised_http_confirmation_creates_one_match(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => 'finance.reconciliation.manage'],
            [
                'module' => 'finance',
                'label' => 'Manage Tally Reconciliation',
                'description' => 'Test permission',
            ]
        );

        $user->forceFill([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        DB::table('role_permission')->updateOrInsert(
            [
                'role' => 'admin',
                'permission_id' => $permission->id,
            ],
            ['updated_at' => now()]
        );

        $url = route(
            'finance.tally.reconciliation.journal.automatic-match',
            $journal
        );

        $data = [
            'tally_imported_voucher_id' => $voucher->id,
            'confirm_no_transmission' => '1',
        ];

        $this->actingAs($user)->post($url, $data)
            ->assertRedirect(route('finance.tally.reconciliation'));

        $this->assertSame('matched',
            $voucher->fresh()->reconciliation_status);
        $this->assertSame('automatic_gl',
            $voucher->fresh()->match_method);
        $this->assertSame(1,
            TallyExport::where('journal_entry_id', $journal->id)->count());

        $this->post($url, $data)
            ->assertSessionHas('error');

        $this->assertSame(1,
            TallyExport::where('journal_entry_id', $journal->id)->count());
    }


    public function test_conflicting_remote_id_without_invoice_identity_is_blocked(): void
    {
        [$journal, $voucher, $user] = $this->fixture();
        TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'confirmed',
            'export_reference' => 'CONFLICT-REMOTE-'.$journal->id,
            'remote_id' => '11111111-1111-4111-8111-111111111111',
            'tally_voucher_id' => '128',
            'tally_accounting_date' => $journal->journal_date,
            'confirmed_at' => now(),
        ]);
        $decision = app(TallyAutomaticJournalMatchService::class)->preview($journal->fresh());
        $this->assertSame('duplicate_ambiguous', $decision['status']);
        $this->assertNull($voucher->fresh()->journal_entry_id);
    }


    public function test_confirmed_invoice_export_matches_tally_source_reference(): void
    {
        [$journal, $voucher, $user] = $this->fixture(true);

        $invoice = DB::table('invoices')
            ->where('id', (int) $journal->source_id)
            ->first();

        $this->assertNotNull($invoice);

        $voucher->forceFill([
            'voucher_type' => 'Sales',
            'narration' => 'TCH ERP OPD Invoice '
                .$invoice->invoice_no
                .' | Patient ID '.$invoice->patient_id,
        ])->save();

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'confirmed',
            'export_reference' => 'INVOICE-TEST-'.$journal->id,
            'remote_id' => '11111111-1111-4111-8111-111111111111',
            'tally_voucher_id' => '128',
            'tally_accounting_date' => $journal->journal_date,
            'confirmed_at' => now(),
            'exported_at' => now(),
            'remarks' => 'Original confirmed Tally export',
        ]);

        $original = $export->fresh()->getAttributes();
        $service = app(TallyAutomaticJournalMatchService::class);

        $preview = $service->preview($journal->fresh());
        $this->assertSame('ready', $preview['status'], $preview['reason']);

        $result = $service->apply(
            $journal->id, $voucher->id, $user->id
        );

        $this->assertSame($export->id, $result->id);
        $this->assertSame(
            $original,
            $export->fresh()->getAttributes()
        );
        $this->assertSame(
            $journal->id,
            (int) $voucher->fresh()->journal_entry_id
        );
        $this->assertSame(
            1,
            TallyExport::where('journal_entry_id', $journal->id)->count()
        );

        $this->expectException(RuntimeException::class);
        $service->apply($journal->id, $voucher->id, $user->id);
    }

    public function test_existing_confirmed_export_is_linked_without_modification(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'confirmed',
            'export_reference' => 'TEST-CONFIRMED-'.$journal->id,
            'tally_voucher_id' => '128',
            'tally_accounting_date' => $journal->journal_date,
            'confirmed_at' => now(),
            'exported_at' => now(),
            'remarks' => 'Original confirmed transmission',
            'tally_response' => '<RESPONSE><CREATED>1</CREATED></RESPONSE>',
        ]);

        $original = $export->fresh()->getAttributes();
        $service = app(TallyAutomaticJournalMatchService::class);

        $this->assertSame(
            'ready',
            $service->preview($journal->fresh())['status']
        );

        $result = $service->apply(
            $journal->id, $voucher->id, $user->id
        );

        $this->assertSame($export->id, $result->id);
        $this->assertSame(
            $original,
            $export->fresh()->getAttributes()
        );
        $this->assertSame(
            $journal->id,
            (int) $voucher->fresh()->journal_entry_id
        );
        $this->assertSame(
            1,
            TallyExport::where('journal_entry_id', $journal->id)->count()
        );
        $this->assertSame(
            'matched',
            $service->preview($journal->fresh())['status']
        );
    }

    public function test_confirmed_export_with_wrong_gl_is_not_linked(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'confirmed',
            'export_reference' => 'TEST-CONFIRMED-'.$journal->id,
            'tally_voucher_id' => '128',
            'tally_accounting_date' => $journal->journal_date,
            'confirmed_at' => now(),
        ]);

        $voucher->entries()
            ->where('is_deemed_positive', true)
            ->firstOrFail()
            ->update(['ledger_name' => 'Incorrect Ledger']);

        $service = app(TallyAutomaticJournalMatchService::class);

        $this->assertSame(
            'ledger_mismatch',
            $service->preview($journal->fresh())['status']
        );

        try {
            $service->apply($journal->id, $voucher->id, $user->id);
            $this->fail('Incorrect GL must not be reconciled.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString(
                'not eligible', strtolower($e->getMessage())
            );
        }

        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(
            1,
            TallyExport::where('journal_entry_id', $journal->id)->count()
        );
    }

    public function test_amount_mismatch_is_classified_without_creating_export(): void
    {
        [$journal, $voucher, $user] = $this->fixture();

        $voucher->entries()->where('is_deemed_positive', true)
            ->firstOrFail()->update(['amount' => '-400.00']);

        $result = app(TallyAutomaticJournalMatchService::class)
            ->preview($journal->fresh());

        $this->assertSame('amount_mismatch', $result['status']);
        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame(
            0,
            TallyExport::where('journal_entry_id', $journal->id)->count()
        );
    }

    public function test_unique_verified_match_is_confirmed_without_transmission(): void
    {
        [$journal, $voucher, $user] = $this->fixture();
        $service = app(TallyAutomaticJournalMatchService::class);
        $this->assertSame('ready', $service->preview($journal)['status']);
        $export = $service->apply($journal->id, $voucher->id, $user->id);
        $this->assertSame('confirmed', $export->status);
        $this->assertNull($export->exported_at);
        $this->assertSame('automatic_gl', $voucher->fresh()->match_method);
        $this->assertSame('matched', $service->preview($journal->fresh())['status']);
        $this->assertSame(1, TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    public function test_duplicate_erp_narration_is_ambiguous_and_never_applied(): void
    {
        [$journal, $voucher, $user] = $this->fixture();
        $copy = $voucher->replicate();
        $copy->guid = 'DUP-'.$voucher->guid;
        $copy->voucher_number = 'OTHER-'.$voucher->voucher_number;
        $copy->save();
        foreach ($voucher->entries as $entry) {
            $line = $entry->replicate();
            $line->tally_imported_voucher_id = $copy->id;
            $line->save();
        }
        $service = app(TallyAutomaticJournalMatchService::class);
        $this->assertSame('duplicate_ambiguous', $service->preview($journal)['status']);
        try {
            $service->apply($journal->id, $voucher->id, $user->id);
            $this->fail('Ambiguous candidate should never be applied.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('not eligible', $e->getMessage());
        }
        $this->assertSame(0, TallyExport::where('journal_entry_id', $journal->id)->count());
    }

    private function ipdSalesFixture(): array
    {
        [$unused, $voucher, $user] = $this->fixture();
        $tag = strtoupper(bin2hex(random_bytes(5)));

        $patientId = DB::table('patients')->insertGetId([
            'uhid' => 'IPD-AUTO-'.$tag,
            'first_name' => 'IPD Reconciliation Test',
            'sex' => 'male',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admissionId = DB::table('admissions')->insertGetId([
            'admission_no' => 'ADM-AUTO-'.$tag,
            'patient_id' => $patientId,
            'admitted_at' => now(),
            'status' => 'admitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bill = 'IPF-AUTO-'.$tag;

        $accountId = DB::table('ip_billing_accounts')->insertGetId([
            'account_no' => 'IPB-AUTO-'.$tag,
            'final_bill_no' => $bill,
            'patient_id' => $patientId,
            'admission_id' => $admissionId,
            'opened_at' => now(),
            'finalized_at' => now(),
            'status' => 'finalized',
            'subtotal' => '427.80',
            'net_amount' => '427.80',
            'paid_amount' => '0.00',
            'balance_amount' => '427.80',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accounts = [
            '1200' => ['Patient Receivables', 'asset', 'debit', 'Test IPD Receivables'],
            '4206' => ['Procedure Revenue', 'income', 'credit', 'Test IPD Revenue'],
        ];

        foreach ($accounts as $code => [$name, $type, $normal, $ledger]) {
            $gl = GeneralLedgerAccount::firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => $name,
                    'account_type' => $type,
                    'account_group' => $type === 'asset' ? 'Current Assets' : 'Clinical Revenue',
                    'normal_balance' => $normal,
                    'is_active' => true,
                    'allow_posting' => true,
                ]
            );

            $this->assertTrue((bool) $gl->is_active);
            $this->assertTrue((bool) $gl->allow_posting);
            $gl->update(['tally_ledger_name' => $ledger]);
            $accounts[$code] = $gl;
        }

        $journal = JournalEntry::create([
            'journal_no' => 'JRN-IPD-AUTO-'.$tag,
            'journal_date' => '2026-11-06',
            'source_type' => 'ip_billing_account',
            'source_id' => (string) $accountId,
            'source_event' => 'finalized',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        foreach ([
            [$accounts['1200']->id, '427.80', '0.00'],
            [$accounts['4206']->id, '0.00', '427.80'],
        ] as $i => [$glId, $dr, $cr]) {
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => $i + 1,
                'general_ledger_account_id' => $glId,
                'debit' => $dr,
                'credit' => $cr,
            ]);
        }

        app(JournalPostingService::class)->post($journal);
        $journal->refresh();

        $voucher->update([
            'voucher_type' => 'Sales',
            'narration' => "TCH ERP IPD Bill {$bill} | Patient ID {$patientId}",
        ]);

        $voucher->entries()->where('line_no', 1)
            ->update(['ledger_name' => 'Test IPD Receivables']);
        $voucher->entries()->where('line_no', 2)
            ->update(['ledger_name' => 'Test IPD Revenue']);

        $export = TallyExport::create([
            'journal_entry_id' => $journal->id,
            'status' => 'confirmed',
            'export_reference' => 'IPD-AUTO-'.$tag,
            'remote_id' => 'DIFFERENT-EXPORT-GUID-'.$tag,
            'tally_voucher_id' => '458',
            'tally_accounting_date' => $journal->journal_date,
            'confirmed_at' => now(),
        ]);

        return [$journal, $voucher, $user, $export, $bill, $patientId];
    }

    public function test_confirmed_ipd_bill_matches_exact_source_identity(): void
    {
        [$journal, $voucher, $user, $export] = $this->ipdSalesFixture();
        $original = $export->fresh()->getAttributes();
        $service = app(TallyAutomaticJournalMatchService::class);

        $preview = $service->preview($journal->fresh());
        $this->assertSame('ready', $preview['status'], $preview['reason']);
        $this->assertSame($voucher->id, $preview['voucher_id']);

        $service->apply($journal->id, $voucher->id, $user->id);

        $this->assertSame('matched', $voucher->fresh()->reconciliation_status);
        $this->assertSame($journal->id, (int) $voucher->fresh()->journal_entry_id);
        $this->assertSame($original, $export->fresh()->getAttributes());
        $this->assertSame(1, TallyExport::where('journal_entry_id', $journal->id)->count());

        $this->expectException(RuntimeException::class);
        $service->apply($journal->id, $voucher->id, $user->id);
    }

    public function test_ipd_identity_and_ledger_safeguards(): void
    {
        [$journal, $voucher, $user, $export, $bill, $patientId] =
            $this->ipdSalesFixture();

        $service = app(TallyAutomaticJournalMatchService::class);

        $voucher->update([
            'narration' => "TCH ERP IPD Bill {$bill} | Patient ID 999999999",
        ]);
        $this->assertNotSame('ready', $service->preview($journal->fresh())['status']);

        $voucher->update([
            'narration' => "TCH ERP IPD Bill WRONG-BILL | Patient ID {$patientId}",
        ]);
        $this->assertNotSame('ready', $service->preview($journal->fresh())['status']);

        $voucher->update([
            'narration' => "TCH ERP IPD Bill {$bill} | Patient ID {$patientId}",
        ]);

        $voucher->entries()->where('line_no', 2)
            ->update(['ledger_name' => 'Incorrect Revenue Ledger']);
        $this->assertSame(
            'ledger_mismatch',
            $service->preview($journal->fresh())['status']
        );

        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertSame('confirmed', $export->fresh()->status);
    }

    public function test_duplicate_ipd_source_candidates_are_rejected(): void
    {
        [$journal, $voucher] = $this->ipdSalesFixture();

        $duplicate = $voucher->replicate();
        $duplicate->guid = 'DUPLICATE-CANDIDATE-'.bin2hex(random_bytes(6));
        $duplicate->remote_id = null;
        $duplicate->voucher_number = 'DUP-'.bin2hex(random_bytes(4));
        $duplicate->save();

        $decision = app(TallyAutomaticJournalMatchService::class)
            ->preview($journal->fresh());

        $this->assertSame('duplicate_ambiguous', $decision['status']);
        $this->assertNull($voucher->fresh()->journal_entry_id);
        $this->assertNull($duplicate->fresh()->journal_entry_id);
    }

}
