<?php

namespace App\Services\Tally;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TallyExportLock
{
    /**
     * Acquire a transaction-scoped lock for an
     * accounting source event.
     *
     * Used by GL Journal and source-linked
     * Finance Voucher export workflows.
     *
     * Must be called inside DB::transaction().
     */
    public static function acquire(
        ?string $sourceType,
        int|string|null $sourceId,
        ?string $sourceEvent
    ): void {
        if (
            blank($sourceType)
            || blank($sourceId)
            || blank($sourceEvent)
        ) {
            throw new RuntimeException(
                'Accounting source identity is required for safe Tally export.'
            );
        }

        $key = implode('|', [
            'tally-source',
            trim($sourceType),
            trim((string) $sourceId),
            trim($sourceEvent),
        ]);

        self::lock($key);
    }

    /**
     * Acquire the correct lock for a Finance Voucher.
     *
     * Source-linked vouchers share a lock with GL journals.
     * Manual vouchers use their own persistent voucher ID.
     *
     * Incomplete identities on non-manual vouchers
     * are rejected.
     */
    public static function acquireForFinanceVoucher(
        \App\Models\FinanceVoucher $voucher
    ): void {
        $hasSourceType = filled($voucher->source_type);
        $hasSourceId = filled($voucher->source_id);
        $hasSourceEvent = filled($voucher->source_event);

        if (
            $hasSourceType
            && $hasSourceId
            && $hasSourceEvent
        ) {
            self::acquire(
                $voucher->source_type,
                $voucher->source_id,
                $voucher->source_event
            );

            return;
        }

        if (
            $voucher->origin === 'erp_manual'
            && ! $hasSourceType
            && ! $hasSourceId
            && ! $hasSourceEvent
        ) {
            self::lock(
                'tally-manual-finance-voucher|'.$voucher->id
            );

            return;
        }

        throw new RuntimeException(
            'Finance Voucher has an incomplete accounting source identity.'
        );
    }

    /**
     * PostgreSQL transaction-level advisory lock.
     *
     * Automatically released at transaction completion.
     */
    private static function lock(string $key): void
    {
        DB::select(
            'SELECT pg_advisory_xact_lock(hashtextextended(?, 0))',
            [$key]
        );
    }
}
