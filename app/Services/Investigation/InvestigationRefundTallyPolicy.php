<?php

namespace App\Services\Investigation;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Append-only refunds may follow confirmed journal exports. Source journals are never edited or re-exported. */
final class InvestigationRefundTallyPolicy
{
    public static function assertCompatible(JournalEntry $journal): void
    {
        if ($journal->finance_voucher_id !== null) {
            throw ValidationException::withMessages(['refund' =>
                'Finance reconciliation required for '.$journal->journal_no.': a finance-voucher link exists.']);
        }

        $query = $journal->tallyExport();
        if (DB::connection()->transactionLevel() > 0) {
            $query->lockForUpdate();
        }
        foreach ($query->get() as $export) {
            if ($export->status !== 'confirmed' || $export->finance_voucher_id !== null) {
                throw ValidationException::withMessages(['refund' =>
                    'Finance reconciliation required for '.$journal->journal_no
                    .': Tally export #'.$export->id.' is '.($export->status ?: 'unclassified')
                    .' or belongs to a finance-voucher workflow.']);
            }
        }
    }
}
