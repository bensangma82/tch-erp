<?php

namespace App\Services\Accounting;

use InvalidArgumentException;

final class MhisPackageVarianceCalculator
{
    public function calculate(
        string $billAmount,
        string $approvedAmount
    ): array {
        $bill = $this->toPaise($billAmount);
        $approved = $this->toPaise($approvedAmount);

        return [
            'bill_amount' => $this->money($bill),
            'approved_amount' => $this->money($approved),

            // Portion transferred from GL 1200 to GL 1210.
            'bill_transfer' => $this->money(
                min($bill, $approved)
            ),

            // Additional contractual package consideration.
            'additional_revenue' => $this->money(
                max($approved - $bill, 0)
            ),

            // Unrecoverable contractual package shortfall.
            'contractual_allowance' => $this->money(
                max($bill - $approved, 0)
            ),
        ];
    }

    private function toPaise(string $amount): int
    {
        if (! preg_match(
            '/^\d{1,10}(?:\.\d{1,2})?$/',
            $amount
        )) {
            throw new InvalidArgumentException(
                'Invalid MHIS accounting amount.'
            );
        }

        [$rupees, $paise] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        return ((int) $rupees * 100)
            + (int) str_pad($paise, 2, '0');
    }

    private function money(int $paise): string
    {
        return number_format($paise / 100, 2, '.', '');
    }
}
