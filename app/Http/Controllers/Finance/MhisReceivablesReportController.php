<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\IpBillingMhisClaim;
use App\Services\Accounting\MhisSettlementBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MhisReceivablesReportController extends Controller
{
    public function index(
        MhisSettlementBalanceService $balanceService
    ): View {
        $claims = IpBillingMhisClaim::query()
            ->with(['receipts', 'admission'])
            ->where('approved_amount', '>', 0)
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($claims as $claim) {
            $receipts = $claim->receipts->where('status', 'active');

            $approved = (int) round((float) $claim->approved_amount * 100);
            $gross = (int) round((float) $receipts->sum('amount') * 100);
            $tds = (int) round((float) $receipts->sum('tds_amount') * 100);
            $adjusted = $balanceService->postedAdjustmentsPaise($claim->id);

            $outstanding = $approved - $gross - $adjusted;

            $sourceIds = [
                'ip_billing_mhis_claim' => [$claim->id],
                'ip_billing_mhis_receipt' => $receipts->pluck('id')->all(),
                'mhis_settlement_adjustment' => DB::table('mhis_settlement_adjustments')
                    ->where('ip_billing_mhis_claim_id', $claim->id)
                    ->where('status', 'posted')
                    ->pluck('id')->all(),
            ];

            $glBalance = 0;

            foreach ($sourceIds as $type => $ids) {
                if (empty($ids)) {
                    continue;
                }

                $journals = DB::table('journal_entries as j')
                    ->join('journal_entry_lines as l', 'l.journal_entry_id', '=', 'j.id')
                    ->join('general_ledger_accounts as g', 'g.id', '=', 'l.general_ledger_account_id')
                    ->where('j.status', 'posted')
                    ->where('j.source_type', $type)
                    ->whereIn('j.source_id', array_map('strval', $ids))
                    ->where('g.account_code', '1210')
                    ->select('l.debit', 'l.credit')
                    ->get();

                foreach ($journals as $line) {
                    $glBalance += (int) round((float) $line->debit * 100);
                    $glBalance -= (int) round((float) $line->credit * 100);
                }
            }

            $rows[] = [
                'claim' => $claim,
                'approved' => $approved / 100,
                'gross' => $gross / 100,
                'tds' => $tds / 100,
                'net_bank' => ($gross - $tds) / 100,
                'adjusted' => $adjusted / 100,
                'outstanding' => $outstanding / 100,
                'gl_balance' => $glBalance / 100,
                'difference' => ($outstanding - $glBalance) / 100,
            ];
        }

        return view('finance.reports.mhis-receivables', [
            'rows' => $rows,
        ]);
    }
}
