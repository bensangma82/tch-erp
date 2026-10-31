<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use App\Models\IpBillingPayment;

class TallyCancelledReceiptGuard
{
    public function check(JournalEntry $journal): array
    {
        if ($journal->source_type !== 'ip_billing_payment') {
            return ['allowed' => true, 'reason' => null];
        }

        $original = $journal->source_event === 'reversed'
            ? $journal->reversalOf
            : $journal;

        if (
            ! $original
            || $original->source_event !== 'received'
        ) {
            return [
                'allowed' => false,
                'reason' => 'Unsupported payment journal event.',
            ];
        }

        $payment = IpBillingPayment::find($original->source_id);

        if (! $payment) {
            return [
                'allowed' => false,
                'reason' => 'Payment record missing.',
            ];
        }

        if ($payment->status === 'active') {
            return [
                'allowed' => $journal->source_event === 'received',
                'reason' => $journal->source_event === 'received'
                    ? null
                    : 'Reversal exists for an active payment.',
            ];
        }

        if ($payment->status !== 'cancelled') {
            return [
                'allowed' => false,
                'reason' => 'Payment status is unsupported.',
            ];
        }

        $reversals = JournalEntry::where(
            'reversal_of_id',
            $original->id
        )->get();

        if (
            $original->status !== 'posted'
            || $reversals->count() !== 1
            || $reversals->first()->status !== 'posted'
        ) {
            return [
                'allowed' => false,
                'reason' => 'Cancellation pair is incomplete.',
            ];
        }

        $reversal = $reversals->first();

        if (
            $reversal->source_type !== 'ip_billing_payment'
            || $reversal->source_event !== 'reversed'
            || (string) $reversal->source_id !== (string) $original->source_id
            || (string) $reversal->reference_no !== (string) $original->reference_no
        ) {
            return [
                'allowed' => false,
                'reason' => 'Cancellation reversal references do not match.',
            ];
        }

        $original->load('lines');
        $reversal->load('lines');

        $amounts = [];

        foreach ([$original, $reversal] as $entry) {
            foreach ($entry->lines as $line) {
                $code = (string) $line->general_ledger_account_id;

                foreach (['debit', 'credit'] as $field) {
                    if (! preg_match(
                        '/^\d+(?:\.\d{1,2})?$/',
                        (string) $line->$field
                    )) {
                        return [
                            'allowed' => false,
                            'reason' => 'Invalid reversal GL amount.',
                        ];
                    }
                }

                $toPaise = static function ($value): int {
                    [$whole, $fraction] = array_pad(
                        explode('.', (string) $value, 2),
                        2,
                        '0'
                    );

                    return ((int) $whole * 100)
                        + (int) str_pad($fraction, 2, '0');
                };

                $amounts[$code] = ($amounts[$code] ?? 0)
                    + $toPaise($line->debit)
                    - $toPaise($line->credit);
            }
        }

        if (
            $original->lines->isEmpty()
            || $reversal->lines->isEmpty()
            || collect($amounts)->contains(fn ($net) => $net !== 0)
        ) {
            return [
                'allowed' => false,
                'reason' => 'Cancellation GL entries do not offset.',
            ];
        }

        if ($original->tallyExport()->exists()
            || $reversal->tallyExport()->exists()
            || $original->finance_voucher_id !== null
            || $reversal->finance_voucher_id !== null
        ) {
            return [
                'allowed' => false,
                'reason' => 'Cancellation requires Tally reconciliation.',
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Cancelled receipt pair: exclude from new export.',
        ];
    }
}
