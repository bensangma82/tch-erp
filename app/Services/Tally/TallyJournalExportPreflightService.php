<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;

class TallyJournalExportPreflightService
{
    public function failure(JournalEntry $journal): ?string
    {
        if (
            $journal->source_type !== 'ip_billing_account'
            || $journal->source_event !== 'advance_applied'
        ) {
            return null;
        }

        $invoiceNo = trim((string) $journal->reference_no);

        if ($invoiceNo === '') {
            return 'Cannot export advance application: '
                .'the final invoice reference is missing.';
        }

        $sales = JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_event', 'finalized')
            ->where('reference_no', $invoiceNo)
            ->where('status', 'posted')
            ->first();

        if (! $sales) {
            return 'Cannot export '.$journal->journal_no
                .': the posted IPD Sales journal for '
                .$invoiceNo.' was not found.';
        }

        $confirmed = TallyExport::query()
            ->where('journal_entry_id', $sales->id)
            ->where('status', 'confirmed')
            ->exists();

        if (! $confirmed) {
            return 'Cannot export '.$journal->journal_no
                .'. Export and confirm IPD Sales journal '
                .$sales->journal_no.' for '.$invoiceNo.' first.';
        }

        return null;
    }
}
