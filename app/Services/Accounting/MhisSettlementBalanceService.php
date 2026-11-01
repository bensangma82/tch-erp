<?php

namespace App\Services\Accounting;

use App\Models\MhisSettlementAdjustment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MhisSettlementBalanceService
{
    public function postedAdjustmentsPaise(int $claimId): int
    {
        $adjustments = MhisSettlementAdjustment::query()
            ->where('ip_billing_mhis_claim_id', $claimId)
            ->where('status', 'posted')
            ->get();

        $total = 0;

        foreach ($adjustments as $adjustment) {
            $journal = DB::table('journal_entries')
                ->where('source_type', 'mhis_settlement_adjustment')
                ->where('source_id', (string) $adjustment->id)
                ->where('source_event', 'posted')
                ->where('status', 'posted')
                ->first();

            if (! $journal) {
                throw new RuntimeException(
                    'Posted MHIS adjustment has no posted journal.'
                );
            }

            $amounts = [];

            foreach ([
                '1220' => $adjustment->tds_amount,
                '5301' => $adjustment->accepted_deduction_amount,
            ] as $code => $amount) {
                $value = (string) $amount;

                if (! preg_match('/^\d+\.\d{2}$/', $value)) {
                    throw new RuntimeException(
                        'Invalid MHIS adjustment amount.'
                    );
                }

                [$whole, $fraction] = explode('.', $value);

                $amounts[$code] =
                    ((int) $whole * 100) + (int) $fraction;
            }

            $adjustmentTotal = array_sum($amounts);

            if ($adjustmentTotal <= 0) {
                throw new RuntimeException(
                    'Invalid posted MHIS adjustment total.'
                );
            }

            $expected = [];

            foreach ($amounts as $code => $paise) {
                if ($paise > 0) {
                    $expected[$code] = $paise;
                }
            }

            $expected['1210'] = -$adjustmentTotal;

            $lines = DB::table('journal_entry_lines as l')
                ->join(
                    'general_ledger_accounts as g',
                    'g.id',
                    '=',
                    'l.general_ledger_account_id'
                )
                ->where('l.journal_entry_id', $journal->id)
                ->select('g.account_code', 'l.debit', 'l.credit')
                ->get();

            $actual = [];

            foreach ($lines as $line) {
                $code = (string) $line->account_code;

                if (array_key_exists($code, $actual)) {
                    throw new RuntimeException(
                        'Duplicate MHIS adjustment ledger line.'
                    );
                }

                $debit = (int) round((float) $line->debit * 100);
                $credit = (int) round((float) $line->credit * 100);

                if (
                    $debit < 0
                    || $credit < 0
                    || ($debit > 0 && $credit > 0)
                ) {
                    throw new RuntimeException(
                        'Invalid MHIS adjustment journal amounts.'
                    );
                }

                $actual[$code] = $debit - $credit;
            }

            ksort($expected);
            ksort($actual);

            if ($actual !== $expected) {
                throw new RuntimeException(
                    'Posted MHIS adjustment GL does not reconcile.'
                );
            }

            $total += $adjustmentTotal;
        }

        return $total;
    }
}
