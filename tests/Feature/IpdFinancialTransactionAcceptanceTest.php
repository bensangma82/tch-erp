<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\IpBillingAdvance;
use App\Models\IpBillingMhisClaim;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Patient;
use App\Models\User;
use App\Services\Accounting\IpAdvanceAccountingService;
use App\Services\Accounting\IpAdvanceApplicationAccountingService;
use App\Services\Accounting\IpBillingAccountingService;
use App\Services\Accounting\JournalPostingService;
use App\Services\Accounting\MhisReceivableAccountingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class IpdFinancialTransactionAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    private function fixture(string $status = 'open'): array
    {
        $this->assertSame('testing', DB::connection()->getDatabaseName(), 'Refusing to touch a non-testing database.');
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($user);
        $key = 'IPDTX-'.strtoupper(bin2hex(random_bytes(5)));
        $patient = Patient::create(['uhid' => $key, 'first_name' => 'Financial', 'last_name' => 'Acceptance', 'sex' => 'male', 'is_active' => true]);
        $admission = Admission::create(['admission_no' => $key, 'patient_id' => $patient->id, 'admitted_at' => now(), 'status' => 'admitted']);
        $account = IpBillingAccount::create([
            'account_no' => $key, 'opened_at' => now(), 'admission_id' => $admission->id,
            'patient_id' => $patient->id, 'status' => $status,
            'net_amount' => '1000.00', 'subtotal' => '1000.00',
            'final_bill_no' => $status === 'finalized' ? 'FIN-'.$key : null,
            'finalized_at' => $status === 'finalized' ? now() : null,
            'paid_amount' => '0.00', 'balance_amount' => '1000.00',
        ]);
        return compact('user', 'patient', 'admission', 'account', 'key');
    }

    private function ledger(string $code): GeneralLedgerAccount
    {
        $names = [
            '1100' => ['Main Cash', 'asset', 'debit'],
            '1110' => ['UPI Settlement Clearing', 'asset', 'debit'],
            '1120' => ['Card Settlement Clearing', 'asset', 'debit'],
            '1200' => ['Patient Receivables', 'asset', 'debit'],
            '2100' => ['Patient Advances / Deposits', 'liability', 'credit'],
        ];
        [$name, $type, $normal] = $names[$code];
        $gl = GeneralLedgerAccount::firstOrCreate(['account_code' => $code], [
            'account_name' => $name, 'account_type' => $type,
            'account_group' => $type === 'asset' ? 'Current Assets' : 'Current Liabilities',
            'normal_balance' => $normal, 'is_active' => true, 'allow_posting' => true,
        ]);
        $this->assertTrue((bool) $gl->is_active, "GL {$code} inactive in testing DB");
        $this->assertTrue((bool) $gl->allow_posting, "GL {$code} posting disabled in testing DB");
        return $gl;
    }

    private function advance(array $f, string $mode = 'cash', string $amount = '250.00'): IpBillingAdvance
    {
        return IpBillingAdvance::create([
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'receipt_no' => 'ADV-'.$f['key'], 'payment_date' => now(),
            'amount' => $amount, 'payment_mode' => $mode,
            'status' => 'active', 'received_by' => $f['user']->id,
        ]);
    }

    private function assertBalanced(JournalEntry $journal, string $amount): void
    {
        $this->assertSame('posted', $journal->fresh()->status);
        $lines = $journal->lines()->get();
        $this->assertCount(2, $lines);
        $this->assertEquals($amount, $lines->sum('debit'));
        $this->assertEquals($amount, $lines->sum('credit'));
    }

    public function test_cash_upi_card_advance_receipts_post_to_correct_gl_and_do_not_duplicate(): void
    {
        foreach (['cash' => '1100', 'upi' => '1110', 'card' => '1120'] as $mode => $debitCode) {
            $f = $this->fixture();
            $this->ledger($debitCode);
            $liability = $this->ledger('2100');
            $advance = $this->advance($f, $mode);
            $beforeExports = DB::table('tally_exports')->count();
            $journal = app(IpAdvanceAccountingService::class)->postAdvance($advance);
            $this->assertBalanced($journal, '250.00');
            $lines = $journal->lines()->orderBy('line_no')->get();
            $this->assertSame($debitCode, (string) $lines[0]->generalLedgerAccount->account_code);
            $this->assertSame('2100', (string) $lines[1]->generalLedgerAccount->account_code);
            $second = app(IpAdvanceAccountingService::class)->postAdvance($advance);
            $this->assertSame($journal->id, $second->id);
            $this->assertSame(1, JournalEntry::where('source_type', 'ip_billing_advance')->where('source_id', (string) $advance->id)->where('source_event', 'received')->count());
            $this->assertSame($beforeExports, DB::table('tally_exports')->count());
        }
    }

    public function test_advance_application_posts_2100_debit_and_1200_credit_once(): void
    {
        $f = $this->fixture('finalized');
        $this->ledger('1100');
        $this->ledger('2100');
        $receivable = $this->ledger('1200');
        $advance = $this->advance($f);
        app(IpAdvanceAccountingService::class)->postAdvance($advance);

        // A separately posted final-bill revenue journal is an explicit prerequisite.
        // This synthetic posted journal does not test final-bill computation.
        $revenue = JournalEntry::create([
            'journal_no' => 'JRN-IPD-'.$f['key'], 'journal_date' => now()->toDateString(),
            'source_type' => 'ip_billing_account', 'source_id' => $f['account']->id,
            'source_event' => 'finalized', 'status' => 'draft', 'created_by' => $f['user']->id,
        ]);
        $income = GeneralLedgerAccount::firstOrCreate(['account_code' => '4301'], [
            'account_name' => 'IPD Services Revenue', 'account_type' => 'income',
            'account_group' => 'Clinical Revenue', 'normal_balance' => 'credit',
            'is_active' => true, 'allow_posting' => true,
        ]);
        foreach ([[$receivable->id, '1000.00', '0.00'], [$income->id, '0.00', '1000.00']] as $i => [$id, $debit, $credit]) {
            JournalEntryLine::create(['journal_entry_id' => $revenue->id, 'line_no' => $i + 1,
                'general_ledger_account_id' => $id, 'debit' => $debit, 'credit' => $credit]);
        }
        app(JournalPostingService::class)->post($revenue);

        $beforeExports = DB::table('tally_exports')->count();
        $application = app(IpAdvanceApplicationAccountingService::class)->apply($f['account']);
        $this->assertNotNull($application);
        $this->assertBalanced($application, '250.00');
        $lines = $application->lines()->orderBy('line_no')->get();
        $this->assertSame('2100', (string) $lines[0]->generalLedgerAccount->account_code);
        $this->assertSame('1200', (string) $lines[1]->generalLedgerAccount->account_code);
        $this->assertSame($application->id, app(IpAdvanceApplicationAccountingService::class)->apply($f['account'])->id);
        $this->assertSame($beforeExports, DB::table('tally_exports')->count());
    }

    public function test_unfinalized_bill_cannot_generate_final_bill_journal(): void
    {
        $f = $this->fixture();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('finalized');
        app(IpBillingAccountingService::class)->postFinalBill($f['account']);
    }

    public function test_mhis_approval_cannot_post_before_final_bill(): void
    {
        $f = $this->fixture();
        $claim = IpBillingMhisClaim::create([
            'ip_billing_account_id' => $f['account']->id,
            'admission_id' => $f['admission']->id,
            'patient_id' => $f['patient']->id,
            'claim_no' => 'MHIS-'.$f['key'], 'status' => 'approved',
            'claim_amount' => '750.00', 'approved_amount' => '750.00',
            'approval_date' => now()->toDateString(),
            'created_by' => $f['user']->id,
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('finalized');
        app(MhisReceivableAccountingService::class)->recognize($claim);
    }
}
