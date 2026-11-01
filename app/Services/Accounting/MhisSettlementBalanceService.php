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

            foreach ([
                $adjustment->tds_amount,
                $adjustment->accepted_deduction_amount
            ] as $amount) {
                $value = (string) $amount;

                if (! preg_match('/^\d+\.\d{2}$/', $value)) {
                    throw new RuntimeException(
                        'Invalid MHIS adjustment amount.'
                    );
                }

                [$whole, $fraction] = explode('.', $value);
                $total += ((int) $whole * 100) + (int) $fraction;
            }
        }

        return $total;
    }
}
