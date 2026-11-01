<?php

namespace App\Services\Tally;

use App\Models\InvestigationCancellationRefund;
use App\Models\JournalEntry;
use App\Models\TallyExport;
use RuntimeException;

class TallyInvestigationRefundGuard
{
    public function check(JournalEntry $journal): void
    {
        if (
            $journal->status !== 'posted'
            || $journal->source_type !== 'investigation_cancellation_refund'
            || $journal->source_event !== 'paid'
        ) {
            throw new RuntimeException('Invalid posted investigation refund.');
        }

        $refund = InvestigationCancellationRefund::with([
            'cancellationRequest',
            'payment',
        ])->findOrFail($journal->source_id);

        $cancellation = $refund->cancellationRequest;
        $payment = $refund->payment;

        if (
            $refund->status !== 'completed'
            || $refund->processed_at === null
            || ! $cancellation
            || $cancellation->status !== 'executed'
            || $cancellation->executed_at === null
            || ! $payment
            || (int) $payment->invoice_id !== (int) $cancellation->invoice_id
        ) {
            throw new RuntimeException(
                'Refund settlement or source relationships are invalid.'
            );
        }

        $dependencies = [
            ['invoice', $cancellation->invoice_id, 'issued', 'Sales'],
            ['payment', $payment->id, 'received', 'Receipt'],
            [
                'investigation_cancellation_request',
                $cancellation->id,
                'revenue_reversed',
                'Credit Note',
            ],
        ];

        foreach ($dependencies as [$source, $id, $event, $label]) {
            $original = JournalEntry::query()
                ->where('source_type', $source)
                ->where('source_id', $id)
                ->where('source_event', $event)
                ->where('status', 'posted')
                ->first();

            if (! $original) {
                throw new RuntimeException(
                    $label.' source journal is missing or unposted.'
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
                    $label.' is not confirmed in Tally. '
                    .'Investigation refund export is blocked.'
                );
            }
        }
    }
}
