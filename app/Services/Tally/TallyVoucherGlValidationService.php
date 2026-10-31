<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use RuntimeException;

class TallyVoucherGlValidationService
{
    public function validate(JournalEntry $journal): array
    {
        if ($journal->status !== 'posted') {
            throw new RuntimeException(
                'Only posted journals can be validated.'
            );
        }

        $journal->loadMissing('lines.generalLedgerAccount');

        $key = $journal->source_type.':'.$journal->source_event;

        if ($key !== 'ip_billing_account:finalized') {
            throw new RuntimeException(
                'GL validation not implemented for '.$key
            );
        }

        $debit = 0;
        $credit = 0;
        $receivableDebit = 0;
        $revenueCredit = 0;

        foreach ($journal->lines as $line) {
            $gl = $line->generalLedgerAccount;

            if (! $gl) {
                throw new RuntimeException('Missing GL account.');
            }

            $toPaise = static function ($value): int {
                $value = (string) $value;

                if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                    throw new RuntimeException('Invalid monetary amount.');
                }

                [$whole, $fraction] = array_pad(
                    explode('.', $value, 2), 2, '0'
                );

                return ((int) $whole * 100)
                    + (int) str_pad($fraction, 2, '0');
            };

            $dr = $toPaise($line->debit);
            $cr = $toPaise($line->credit);

            if (($dr > 0) === ($cr > 0)) {
                throw new RuntimeException('Invalid journal line.');
            }

            $debit += $dr;
            $credit += $cr;

            if ($gl->account_code === '1200') {
                if ($cr !== 0) {
                    throw new RuntimeException(
                        'Unexpected receivable credit.'
                    );
                }

                $receivableDebit += $dr;
            }

            if ($gl->account_type === 'income') {
                $revenueCredit += $cr;
            }
        }

        if ($debit <= 0 || $debit !== $credit) {
            throw new RuntimeException('Journal is not balanced.');
        }

        if ($receivableDebit !== $debit) {
            throw new RuntimeException(
                'IPD Sales voucher has unexpected debit accounts.'
            );
        }

        if ($revenueCredit <= 0) {
            throw new RuntimeException(
                'IPD Sales voucher has no revenue credit.'
            );
        }

        return [
            'valid' => true,
            'voucher_type' => 'Sales',
            'journal_id' => $journal->id,
            'amount' => number_format($debit / 100, 2, '.', ''),
            'transmitted' => false,
        ];
    }
}
