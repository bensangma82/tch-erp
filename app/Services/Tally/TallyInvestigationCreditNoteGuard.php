<?php

namespace App\Services\Tally;

use App\Models\InvestigationCancellationRequest;
use App\Models\JournalEntry;
use App\Models\TallyExport;
use RuntimeException;

class TallyInvestigationCreditNoteGuard
{
    public function check(JournalEntry $journal): void
    {
        if (
            $journal->source_type !== 'investigation_cancellation_request'
            || $journal->source_event !== 'revenue_reversed'
            || $journal->status !== 'posted'
        ) {
            throw new RuntimeException(
                'Invalid investigation cancellation journal.'
            );
        }

        $cancellation = InvestigationCancellationRequest::query()
            ->whereKey($journal->source_id)
            ->firstOrFail();

        if (
            $cancellation->status !== 'executed'
            || $cancellation->executed_at === null
        ) {
            throw new RuntimeException(
                'Investigation cancellation is not executed.'
            );
        }

        $original = JournalEntry::query()
            ->where('source_type', 'invoice')
            ->where('source_id', $cancellation->invoice_id)
            ->where('source_event', 'issued')
            ->where('status', 'posted')
            ->first();

        if (
            ! $original
            || (string) $original->reference_no
                !== (string) $journal->reference_no
        ) {
            throw new RuntimeException(
                'Original posted Sales invoice is missing or mismatched.'
            );
        }

        $confirmed = TallyExport::query()
            ->where('journal_entry_id', $original->id)
            ->where('status', 'confirmed')
            ->whereNotNull('confirmed_at')
            ->whereNotNull('tally_voucher_id')
            ->exists();

        if (! $confirmed) {
            throw new RuntimeException(
                'Original Sales invoice is not confirmed in Tally. '
                .'Credit Note transmission is blocked.'
            );
        }
    }
}
