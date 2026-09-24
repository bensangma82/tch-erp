<?php

namespace App\Services\Finance;

use App\Models\FinanceVoucher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceVoucherNumberService
{
    /**
     * Generate the next voucher number for the voucher date.
     *
     * Examples:
     * REC-20260920-00001
     * PAY-20260920-00001
     * TRF-20260920-00001
     */
    public function generate(
        string $voucherType,
        string $voucherDate
    ): string {
        $prefix = match ($voucherType) {
            'receipt' => 'REC',
            'payment' => 'PAY',
            'transfer' => 'TRF',
            default => 'VOU',
        };

        $date = Carbon::parse($voucherDate)
            ->format('Ymd');

        /*
         * PostgreSQL transaction-level advisory lock.
         *
         * The lock is specific to the voucher type and voucher date.
         * This prevents two concurrent processes from generating
         * the same voucher number.
         *
         * This method should be called from inside a database
         * transaction so the advisory lock remains active until
         * the transaction is committed.
         */
        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            ["finance-voucher-{$prefix}-{$date}"]
        );

        $pattern = "{$prefix}-{$date}-%";

        $lastVoucher = FinanceVoucher::query()
            ->where('voucher_no', 'like', $pattern)
            ->orderByDesc('voucher_no')
            ->first();

        $sequence = 1;

        if ($lastVoucher) {
            $lastSequence = (int) substr(
                $lastVoucher->voucher_no,
                -5
            );

            $sequence = $lastSequence + 1;
        }

        return sprintf(
            '%s-%s-%05d',
            $prefix,
            $date,
            $sequence
        );
    }
}