<?php

namespace App\Services\Tally;

use App\Models\TallyImportedVoucher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TallyImportService
{
    public function __construct(
        private readonly TallyQueryService $tallyQueryService
    ) {}

    /**
     * Synchronize the currently available Tally vouchers into the ERP.
     *
     * This service only mirrors Tally data.
     *
     * It deliberately does not perform or modify reconciliation.
     * Existing reconciliation results therefore survive subsequent
     * Tally synchronization runs.
     *
     * @return array{
     *     received: int,
     *     imported: int,
     *     updated: int,
     *     skipped: int
     * }
     */
    public function sync(): array
    {
        $vouchers = $this->tallyQueryService->vouchers();

        $summary = [
            'received' => count($vouchers),
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        foreach ($vouchers as $voucher) {
            /*
             * GUID is our durable inbound Tally identifier.
             *
             * We deliberately skip vouchers without a GUID rather than
             * attempting an unsafe identity match from mutable fields such
             * as date, voucher number or amount.
             */
            $guid = trim(
                (string) (
                    $voucher['guid']
                    ?? ''
                )
            );

            if ($guid === '') {
                $summary['skipped']++;

                continue;
            }

            DB::transaction(function () use (
                $voucher,
                $guid,
                &$summary
            ): void {
                $remoteId = trim(
                    (string) (
                        $voucher['remote_id']
                        ?? ''
                    )
                );

                $voucherDate = $this->parseVoucherDate(
                    $voucher['date']
                    ?? null
                );

                /*
                 * These are Tally-source fields only.
                 *
                 * Reconciliation fields are intentionally excluded so
                 * synchronization cannot destroy an existing automatic
                 * or manual ERP/Tally match.
                 */
                $sourceAttributes = [
                    'remote_id' => $remoteId !== ''
                        ? $remoteId
                        : null,
                    'voucher_date' => $voucherDate,
                    'voucher_type' => $voucher['voucher_type']
                        ?? null,
                    'voucher_number' => $voucher['voucher_number']
                        ?? null,
                    'party_ledger' => $voucher['party_ledger']
                        ?? null,
                    'narration' => $voucher['narration']
                        ?? null,
                    'last_seen_at' => now(),
                ];

                $existing = TallyImportedVoucher::query()
                    ->where('guid', $guid)
                    ->first();

                if ($existing === null) {
                    $importedVoucher = TallyImportedVoucher::create(
                        array_merge(
                            [
                                'guid' => $guid,
                                'reconciliation_status' => 'tally_only',
                                'first_seen_at' => now(),
                            ],
                            $sourceAttributes
                        )
                    );

                    $summary['imported']++;
                } else {
                    $existing->update(
                        $sourceAttributes
                    );

                    $importedVoucher = $existing;

                    $summary['updated']++;
                }

                /*
                 * Rebuild the child ledger lines from the latest Tally
                 * representation so edited Tally vouchers remain accurate.
                 */
                $importedVoucher
                    ->entries()
                    ->delete();

                foreach (
                    $voucher['ledger_entries']
                        ?? [] as $index => $entry
                ) {
                    $ledgerName = trim(
                        (string) (
                            $entry['ledger_name']
                            ?? ''
                        )
                    );

                    if ($ledgerName === '') {
                        continue;
                    }

                    $importedVoucher
                        ->entries()
                        ->create([
                            'ledger_name' => $ledgerName,
                            'amount' => (float) (
                                $entry['amount']
                                ?? 0
                            ),
                            'is_deemed_positive' => $entry['is_deemed_positive']
                                    ?? null,
                            'line_no' => $index + 1,
                        ]);
                }
            });
        }

        return $summary;
    }

    /**
     * Convert Tally's YYYYMMDD date into a database date.
     */
    private function parseVoucherDate(
        ?string $value
    ): ?string {
        if (
            $value === null
            || trim($value) === ''
        ) {
            return null;
        }

        $value = trim($value);

        if (
            strlen($value) !== 8
            || ! ctype_digit($value)
        ) {
            throw new RuntimeException(
                'Invalid Tally voucher date: '.$value
            );
        }

        $year = substr(
            $value,
            0,
            4
        );

        $month = substr(
            $value,
            4,
            2
        );

        $day = substr(
            $value,
            6,
            2
        );

        if (
            ! checkdate(
                (int) $month,
                (int) $day,
                (int) $year
            )
        ) {
            throw new RuntimeException(
                'Invalid Tally voucher date: '.$value
            );
        }

        return $year.'-'.$month.'-'.$day;
    }
}
