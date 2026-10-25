<?php

namespace App\Services\Investigation;

use InvalidArgumentException;

/** Monetary values are integer paise, never floating-point currency. */
final class InvestigationRefundAllocationPolicy
{
    public function available(
        int $invoiceTotal,
        int $totalPaid,
        int $executedCancellations,
        int $completedRefunds,
        int $currentCancellation,
        int $refundedForCancellation,
        int $originalPayment,
        int $refundedForPayment
    ): int {
        foreach (func_get_args() as $value) {
            if ($value < 0) {
                throw new InvalidArgumentException('Negative accounting balances are invalid.');
            }
        }
        if ($executedCancellations > $invoiceTotal || $totalPaid > $invoiceTotal
            || $completedRefunds > $totalPaid || $refundedForCancellation > $currentCancellation
            || $refundedForPayment > $originalPayment) {
            throw new InvalidArgumentException('Unreconciled invoice or refund totals.');
        }
        $netBill = $invoiceTotal - $executedCancellations;
        $overallAvailable = max(0, $totalPaid - $netBill - $completedRefunds);
        return min($overallAvailable, $currentCancellation - $refundedForCancellation,
            $originalPayment - $refundedForPayment);
    }
}
