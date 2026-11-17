<?php

namespace App\Support;

use InvalidArgumentException;

final class MhisLegacySettlementAmounts
{
    public static function paise(string $amount): int
    {
        if (! preg_match('/\A(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,2})?\z/D', $amount)) {
            throw new InvalidArgumentException('Invalid INR amount: use up to two decimal places.');
        }

        [$rupees, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return (int) $rupees * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function rupees(int $paise): string
    {
        if ($paise < 0 || $paise > 999999999999999999) {
            throw new InvalidArgumentException('Amount exceeds database decimal(18,2) capacity.');
        }
        return intdiv($paise, 100).'.'.str_pad((string) ($paise % 100), 2, '0', STR_PAD_LEFT);
    }
}
