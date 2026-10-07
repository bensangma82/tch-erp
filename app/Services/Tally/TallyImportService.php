<?php

namespace App\Services\Tally;

use App\Models\TallyExport;
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
     * This operation is read-only with respect to Tally and does not
     * create or modify FinanceVoucher records.
     *
     * @return array{
     *     received: int,
     *     imported: int,
     *     updated: int,
     *     matched: int,
     *     tally_only: int,
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
            'matched' => 0,
            'tally_only' => 0,
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
            $guid = $voucher['guid'] ?? null;

            if ($guid === null || trim($guid) === '') {
                $summary['skipped']++;

                continue;
            }

            DB::transaction(function () use (
                $voucher,
                $guid,
                &$summary
            ): void {
                $remoteId = $voucher['remote_id'] ?? null;

                $tallyExport = null;

                if (
                    $remoteId !== null
                    && trim($remoteId) !== ''
                    && preg_match(
                        '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                        trim($remoteId)
                    ) === 1
                ) {
                    $tallyExport = TallyExport::query()
                        ->where(
                            'remote_id',
                            trim($remoteId)
                        )
                        ->first();
                }

                $existing = TallyImportedVoucher::query()
                    ->where('guid', $guid)
                    ->first();

                $voucherDate = $this->parseVoucherDate(
                    $voucher['date'] ?? null
                );

                $attributes = [
                    'remote_id' => $remoteId !== ''
                        ? $remoteId
                        : null,
                    'voucher_date' => $voucherDate,
                    'voucher_type' => $voucher['voucher_type'] ?? null,
                    'voucher_number' => $voucher['voucher_number'] ?? null,
                    'party_ledger' => $voucher['party_ledger'] ?? null,
                    'narration' => $voucher['narration'] ?? null,
                    'last_seen_at' => now(),
                ];

                if ($tallyExport !== null) {
                    $attributes['finance_voucher_id']
                        = $tallyExport->finance_voucher_id;

                    $attributes['reconciliation_status']
                        = 'matched';

                    $attributes['match_method']
                        = 'exact_remote_id';

                    $attributes['match_confidence']
                        = 100.00;

                    $attributes['reconciliation_notes']
                        = 'Matched automatically using the ERP-generated Tally REMOTEID.';
                } else {
                    $attributes['finance_voucher_id'] = null;
                    $attributes['reconciliation_status'] = 'tally_only';
                    $attributes['match_method'] = null;
                    $attributes['match_confidence'] = null;
                    $attributes['reconciliation_notes'] = null;
                }

                if ($existing === null) {
                    $importedVoucher = TallyImportedVoucher::create(
                        array_merge(
                            [
                                'guid' => $guid,
                                'first_seen_at' => now(),
                            ],
                            $attributes
                        )
                    );

                    $summary['imported']++;
                } else {
                    $existing->update(
                        $attributes
                    );

                    $importedVoucher = $existing;

                    $summary['updated']++;
                }

                /*
                 * Rebuild the child ledger lines from the latest Tally
                 * representation so edited Tally vouchers remain accurate.
                 */
                $importedVoucher->entries()->delete();

                foreach (
                    $voucher['ledger_entries'] ?? [] as $index => $entry
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

                    $importedVoucher->entries()->create([
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

                if ($tallyExport !== null) {
                    $summary['matched']++;
                } else {
                    $summary['tally_only']++;
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
        if ($value === null || trim($value) === '') {
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

        $year = substr($value, 0, 4);
        $month = substr($value, 4, 2);
        $day = substr($value, 6, 2);

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
