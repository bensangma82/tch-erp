<?php

namespace App\Services\Accounting;

use App\Support\MhisLegacySettlementAmounts as Money;
use DomainException;

/**
 * Pure, non-posting journal blueprint for VERIFIED historical settlements.
 * The actual write adapter must use the project's JournalPostingService.
 * This class deliberately cannot post journals or send Tally exports.
 */
final class MhisLegacySettlementJournalBlueprint
{
    public static function build(
        string $status,
        string $net,
        string $tds,
        string $deduction,
        string $gross,
        bool $openingReconciled,
        bool $openingLedgerAlreadyCarriedForward
    ): array {
        if ($status !== 'verified') {
            throw new DomainException('Settlement must be independently verified.');
        }
        if (! $openingReconciled) {
            throw new DomainException('Approved opening balance must be reconciled to the closing trial balance.');
        }
        if (! $openingLedgerAlreadyCarriedForward) {
            throw new DomainException('Opening receivable is not confirmed in GL 1210; opening TB transition must be completed first.');
        }

        $values = [
            '1130' => Money::paise($net),
            '1220' => Money::paise($tds),
            '5301' => Money::paise($deduction),
        ];
        $expected = Money::paise($gross);
        if ($expected <= 0 || array_sum($values) !== $expected) {
            throw new DomainException('Gross does not equal net receipt plus TDS and approved deductions.');
        }

        $lines = [];
        foreach ($values as $account => $amount) {
            if ($amount > 0) {
                $lines[] = ['account_code' => $account, 'debit' => Money::rupees($amount), 'credit' => '0.00'];
            }
        }
        $lines[] = ['account_code' => '1210', 'debit' => '0.00', 'credit' => Money::rupees($expected)];

        return [
            'lines' => $lines,
            'total_debit' => Money::rupees(array_sum($values)),
            'total_credit' => Money::rupees($expected),
            'posts_journal' => false,
            'exports_to_tally' => false,
        ];
    }
}
