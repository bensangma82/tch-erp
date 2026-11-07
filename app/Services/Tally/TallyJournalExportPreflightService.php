<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\TallyExport;

class TallyJournalExportPreflightService
{
    public function failure(JournalEntry $journal): ?string
    {
        // OPD Receipt Sales dependency.
        if (
            $journal->source_type === 'payment'
            && $journal->source_event === 'received'
        ) {
            $payment = \App\Models\Payment::with('invoice')
                ->find($journal->source_id);

            if (! $payment || ! $payment->invoice) {
                return 'Cannot export OPD Receipt: payment or invoice missing.';
            }

            $invoice = $payment->invoice;

            if (
                ! $invoice->invoice_no
                || (string) $journal->reference_no !== (string) $payment->receipt_no
                || (string) $payment->invoice_id !== (string) $invoice->id
                || (string) $payment->patient_id !== (string) $invoice->patient_id
            ) {
                return 'Cannot export OPD Receipt: invalid invoice or patient reference.';
            }

            $sales = JournalEntry::query()
                ->where('source_type', 'invoice')
                ->where('source_event', 'issued')
                ->where('source_id', (string) $invoice->id)
                ->where('status', 'posted')
                ->first();

            if (! $sales) {
                return 'Cannot export OPD Receipt: posted Sales journal missing.';
            }

            $confirmed = TallyExport::query()
                ->where('journal_entry_id', $sales->id)
                ->where('status', 'confirmed')
                ->exists();

            if (! $confirmed) {
                return 'Cannot export OPD Receipt. Confirm Sales voucher '
                    .$sales->journal_no.' in Tally first.';
            }
        }

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
