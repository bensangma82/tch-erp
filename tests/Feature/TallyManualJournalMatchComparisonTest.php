<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\TallyImportedVoucher;
use App\Models\TallyImportedVoucherEntry;
use App\Services\Tally\TallyJournalReconciliationService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TallyManualJournalMatchComparisonTest extends TestCase
{
    private function records(): array
    {
        $journal = new JournalEntry(['journal_date' => '2026-11-06']);
        $journal->journal_date = Carbon::parse('2026-11-06');
        $cash = new GeneralLedgerAccount(['tally_ledger_name' => 'Cash']);
        $advances = new GeneralLedgerAccount(['tally_ledger_name' => 'Patient Advances']);
        $debit = new JournalEntryLine(['debit' => '427.80', 'credit' => '0.00']);
        $debit->setRelation('generalLedgerAccount', $cash);
        $credit = new JournalEntryLine(['debit' => '0.00', 'credit' => '427.80']);
        $credit->setRelation('generalLedgerAccount', $advances);
        $journal->setRelation('lines', collect([$debit, $credit]));

        $voucher = new TallyImportedVoucher(['voucher_date' => '2026-11-06']);
        $in = new TallyImportedVoucherEntry(['ledger_name' => 'Cash', 'amount' => '-427.80', 'is_deemed_positive' => true]);
        $out = new TallyImportedVoucherEntry(['ledger_name' => 'Patient Advances', 'amount' => '427.80', 'is_deemed_positive' => false]);
        $voucher->setRelation('entries', collect([$in, $out]));
        return [$journal, $voucher];
    }

    public function test_matching_imported_gl_entries_are_accepted(): void
    {
        [$journal, $voucher] = $this->records();
        $this->assertSame([], app(TallyJournalReconciliationService::class)->compareVoucher($journal, $voucher));
    }

    public function test_mismatched_imported_amount_is_rejected(): void
    {
        [$journal, $voucher] = $this->records();
        $voucher->entries[1]->amount = '427.81';
        $this->assertNotEmpty(app(TallyJournalReconciliationService::class)->compareVoucher($journal, $voucher));
    }
}
