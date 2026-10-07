<?php

namespace App\Services\Tally;

use App\Models\FinanceVoucher;
use App\Models\TallyImportedVoucher;
use App\Models\TallyLedgerMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TallyReconciliationService
{
    /**
     * Reconcile posted ERP Finance vouchers against the read-only
     * Tally voucher mirror.
     *
     * Matching is deliberately conservative:
     *
     * 1. Exact ERP REMOTEID when available and preserved by Tally.
     * 2. Otherwise a unique secondary match using:
     *    - voucher date
     *    - voucher type
     *    - transaction amount
     *    - mapped Tally ledgers
     *    - narration, when the ERP voucher has narration
     *
     * Ambiguous candidates are never matched automatically.
     *
     * @return array{
     *     processed: int,
     *     matched: int,
     *     exact_remote_id: int,
     *     secondary_match: int,
     *     erp_only: int,
     *     ambiguous: int,
     *     unmapped: int
     *     difference: int
     * }
     */
    /**
 * Return the current reconciliation health summary without modifying
 * any ERP or Tally reconciliation state.
 *
 * This is safe for dashboards and management reporting.
 *
 * @return array{
 *     matched: int,
 *     tally_only: int,
 *     erp_only: int,
 *     ambiguous: int,
 *     unmapped: int,
 *     difference: int
 * }
 */
public function summary(): array
{
    $matched = TallyImportedVoucher::query()
        ->where('reconciliation_status', 'matched')
        ->count();

    $tallyOnly = TallyImportedVoucher::query()
        ->whereNull('finance_voucher_id')
        ->where('reconciliation_status', 'tally_only')
        ->count();

    $differences = TallyImportedVoucher::query()
        ->where('reconciliation_status', 'difference')
        ->count();

    /*
     * Posted ERP vouchers with no currently linked imported
     * Tally voucher still need read-only classification.
     */
    $unmatchedErp = FinanceVoucher::query()
        ->with([
            'financeHead',
            'financeAccount',
            'destinationAccount',
            'tallyExport',
        ])
        ->where('status', 'posted')
        ->whereDoesntHave('tallyImportedVouchers')
        ->get();

    $erpOnly = 0;
    $ambiguous = 0;
    $unmapped = 0;

    foreach ($unmatchedErp as $financeVoucher) {
        $diagnostic = $this->unmatchedStatus(
            $financeVoucher
        );

        match ($diagnostic['status']) {
            'ambiguous' => $ambiguous++,
            'unmapped' => $unmapped++,
            default => $erpOnly++,
        };
    }

    return [
        'matched' => $matched,
        'tally_only' => $tallyOnly,
        'erp_only' => $erpOnly,
        'ambiguous' => $ambiguous,
        'unmapped' => $unmapped,
        'difference' => $differences,
    ];
}
    public function reconcile(): array
    {
        $summary = [
            'processed' => 0,
            'matched' => 0,
            'exact_remote_id' => 0,
            'secondary_match' => 0,
            'erp_only' => 0,
            'ambiguous' => 0,
            'unmapped' => 0,
            'difference' => 0,
        ];

        /*
         * Reconciliation should always start from the current imported
         * Tally mirror.
         *
         * Existing automatic matches are cleared and rebuilt so changes
         * in Tally or ledger mappings cannot leave stale relationships.
         *
         * Manual matches, if introduced later, should be protected from
         * this reset.
         */
        TallyImportedVoucher::query()
            ->where(function ($query) {
                $query
                    ->whereNull('match_method')
                    ->orWhere(
                        'match_method',
                        '!=',
                        'manual'
                    );
            })
            ->update([
                'finance_voucher_id' => null,
                'reconciliation_status' => 'tally_only',
                'match_method' => null,
                'match_confidence' => null,
                'reconciliation_notes' => null,
            ]);

        $financeVouchers = FinanceVoucher::query()
            ->with([
                'financeHead',
                'financeAccount',
                'destinationAccount',
                'tallyExport',
            ])
            ->where('status', 'posted')
            ->orderBy('voucher_date')
            ->orderBy('id')
            ->get();

        foreach ($financeVouchers as $financeVoucher) {
            $summary['processed']++;
            /*
 * A manual reconciliation is an authorised accounting decision.
 * Never attempt to automatically reconcile the same ERP voucher
 * again while that protected manual relationship exists.
 */
            $hasManualMatch = TallyImportedVoucher::query()
                ->where(
                    'finance_voucher_id',
                    $financeVoucher->id
                )
                ->where(
                    'match_method',
                    'manual'
                )
                ->exists();

            if ($hasManualMatch) {
                $summary['matched']++;

                continue;
            }

            /*
             * First preference: an exact REMOTEID match.
             *
             * This will work whenever the identifier returned by Tally
             * exactly matches the stable REMOTEID stored by the ERP.
             */
            $exactMatch = $this->findExactRemoteIdMatch(
                $financeVoucher
            );

            if ($exactMatch !== null) {
                $exactMatch->loadMissing('entries');

                $differences = $this->accountingDifferences(
                    $financeVoucher,
                    $exactMatch
                );

                if ($differences !== []) {
                    $exactMatch->update([
                        'finance_voucher_id' => $financeVoucher->id,
                        'reconciliation_status' => 'difference',
                        'match_method' => 'exact_remote_id',
                        'match_confidence' => 100.00,
                        'reconciliation_notes' => implode(
                            ' ',
                            $differences
                        ),
                    ]);

                    $summary['difference']++;

                    continue;
                }

                $this->markMatched(
                    $exactMatch,
                    $financeVoucher,
                    'exact_remote_id',
                    100.00,
                    'Matched automatically using the exact ERP/Tally REMOTEID; accounting content agrees.'
                );

                $summary['matched']++;
                $summary['exact_remote_id']++;

                continue;
            }

            /*
             * Secondary matching requires ledger mappings. We do not
             * attempt amount-only matching because that can create false
             * accounting relationships.
             */
            $expectedLedgers = $this->expectedTallyLedgers(
                $financeVoucher
            );

            if ($expectedLedgers === null) {
                $summary['erp_only']++;
                $summary['unmapped']++;

                continue;
            }
            $candidates = $this->secondaryCandidates(
                $financeVoucher,
                $expectedLedgers
            );

            if ($candidates->count() === 1) {
                /** @var TallyImportedVoucher $candidate */
                $candidate = $candidates->first();

                $confidence = $this->secondaryConfidence(
                    $financeVoucher,
                    $candidate
                );

                $this->markMatched(
                    $candidate,
                    $financeVoucher,
                    'secondary_match',
                    $confidence,
                    'Matched automatically using a unique date, voucher type, amount and mapped-ledger combination.'
                );

                $summary['matched']++;
                $summary['secondary_match']++;

                continue;
            }

            if ($candidates->count() > 1) {
                $summary['ambiguous']++;
                $summary['erp_only']++;

                continue;
            }

            $summary['erp_only']++;
        }

        return $summary;
    }

    private function findExactRemoteIdMatch(
        FinanceVoucher $financeVoucher
    ): ?TallyImportedVoucher {
        $remoteId = $financeVoucher
            ->tallyExport
            ?->remote_id;

        if (
            $remoteId === null
            || trim((string) $remoteId) === ''
        ) {
            return null;
        }

        return TallyImportedVoucher::query()
            ->whereNull('finance_voucher_id')
            ->where(
                'remote_id',
                (string) $remoteId
            )
            ->first();
    }

    /**
     * Determine the Tally ledgers expected for an ERP Finance voucher.
     *
     * @return array<int, string>|null
     */
    private function expectedTallyLedgers(
        FinanceVoucher $financeVoucher
    ): ?array {
        $ledgerNames = [];

        if ($financeVoucher->finance_head_id !== null) {
            $headLedger = $this->ledgerForFinanceHead(
                $financeVoucher->finance_head_id
            );

            if ($headLedger === null) {
                return null;
            }

            $ledgerNames[] = $headLedger;
        }

        if ($financeVoucher->finance_account_id !== null) {
            $accountLedger = $this->ledgerForFinanceAccount(
                $financeVoucher->finance_account_id
            );

            if ($accountLedger === null) {
                return null;
            }

            $ledgerNames[] = $accountLedger;
        }

        if ($financeVoucher->destination_account_id !== null) {
            $destinationLedger = $this->ledgerForFinanceAccount(
                $financeVoucher->destination_account_id
            );

            if ($destinationLedger === null) {
                return null;
            }

            $ledgerNames[] = $destinationLedger;
        }

        $ledgerNames = array_values(
            array_unique(
                array_filter(
                    $ledgerNames,
                    fn ($name) => trim((string) $name) !== ''
                )
            )
        );

        if (count($ledgerNames) < 2) {
            return null;
        }

        sort(
            $ledgerNames,
            SORT_NATURAL | SORT_FLAG_CASE
        );

        return $ledgerNames;
    }

    private function expectedTallyVoucherType(string $erpVoucherType): string
    {
        return match (strtolower(trim($erpVoucherType))) {
            'receipt' => 'receipt',
            'payment' => 'payment',
            'transfer' => 'contra',
            'journal' => 'journal',
            default => strtolower(trim($erpVoucherType)),
        };
    }

    private function ledgerForFinanceHead(
        int $financeHeadId
    ): ?string {
        return TallyLedgerMapping::query()
            ->where(
                'finance_head_id',
                $financeHeadId
            )
            ->where('is_active', true)
            ->value('tally_ledger_name');
    }

    private function ledgerForFinanceAccount(
        int $financeAccountId
    ): ?string {
        return TallyLedgerMapping::query()
            ->where(
                'finance_account_id',
                $financeAccountId
            )
            ->where('is_active', true)
            ->value('tally_ledger_name');
    }

    /**
     * @param  array<int, string>  $expectedLedgers
     * @return Collection<int, TallyImportedVoucher>
     */
    private function secondaryCandidates(
        FinanceVoucher $financeVoucher,
        array $expectedLedgers
    ): Collection {
        $candidates = TallyImportedVoucher::query()
            ->with('entries')
            ->whereNull('finance_voucher_id')
            ->whereDate(
                'voucher_date',
                $financeVoucher->voucher_date
            )
            ->whereRaw(
                'LOWER(voucher_type) = ?',
                [
                    $this->expectedTallyVoucherType(
                        (string) $financeVoucher->voucher_type
                    ),
                ]
            )
            ->get();

        return $candidates
            ->filter(
                function (
                    TallyImportedVoucher $candidate
                ) use (
                    $financeVoucher,
                    $expectedLedgers
                ): bool {
                    if (
                        ! $this->amountMatches(
                            $financeVoucher,
                            $candidate
                        )
                    ) {
                        return false;
                    }

                    if (
                        ! $this->ledgerSetMatches(
                            $candidate,
                            $expectedLedgers
                        )
                    ) {
                        return false;
                    }

                    /*
                     * If ERP narration exists, require it to match.
                     *
                     * This prevents a ₹1 Test ERP receipt from being
                     * confused with another ₹1 receipt on the same day
                     * using the same ledgers.
                     *
                     * Blank ERP narration does not disqualify an otherwise
                     * unique accounting match.
                     */
                    $erpNarration = $this->normaliseText(
                        $financeVoucher->narration
                    );

                    if ($erpNarration !== null) {
                        $tallyNarration = $this->normaliseText(
                            $candidate->narration
                        );

                        if (
                            $tallyNarration === null
                            || $tallyNarration !== $erpNarration
                        ) {
                            return false;
                        }
                    }

                    return true;
                }
            )
            ->values();
    }

    /**
     * Compare the accounting content of an ERP voucher with an imported
     * Tally voucher whose identity has already been established.
     *
     * @return array<int, string>
     */
    private function accountingDifferences(
        FinanceVoucher $financeVoucher,
        TallyImportedVoucher $tallyVoucher
    ): array {
        $differences = [];

        /*
         * Date
         */
        $erpDate = $financeVoucher->voucher_date?->format('Y-m-d');
        $tallyDate = $tallyVoucher->voucher_date?->format('Y-m-d');

        if ($erpDate !== $tallyDate) {
            $differences[] = sprintf(
                'Date differs: ERP %s; Tally %s.',
                $erpDate ?? 'blank',
                $tallyDate ?? 'blank'
            );
        }

        /*
         * Voucher type
         */
        $expectedType = $this->expectedTallyVoucherType(
            (string) $financeVoucher->voucher_type
        );

        $actualType = strtolower(
            trim(
                (string) $tallyVoucher->voucher_type
            )
        );

        if ($expectedType !== $actualType) {
            $differences[] = sprintf(
                'Voucher type differs: ERP expects %s; Tally has %s.',
                $expectedType,
                $actualType !== '' ? $actualType : 'blank'
            );
        }

        /*
         * Amount
         */
        if (! $this->amountMatches(
            $financeVoucher,
            $tallyVoucher
        )) {
            $erpAmount = round(
                abs((float) $financeVoucher->amount),
                2
            );

            $tallyAmount = round(
                $tallyVoucher->entries->sum(
                    fn ($entry) => abs(
                        (float) $entry->amount
                    )
                ) / 2,
                2
            );

            $differences[] = sprintf(
                'Amount differs: ERP %.2f; Tally %.2f.',
                $erpAmount,
                $tallyAmount
            );
        }

        /*
         * Ledger allocation.
         *
         * If ERP ledger mappings are incomplete, that itself is a
         * reconciliation difference for an identified voucher pair.
         */
        $expectedLedgers = $this->expectedTallyLedgers(
            $financeVoucher
        );

        if ($expectedLedgers === null) {
            $differences[] =
                'ERP ledger mapping is incomplete for this voucher.';
        } elseif (! $this->ledgerSetMatches(
            $tallyVoucher,
            $expectedLedgers
        )) {
            $actualLedgers = $tallyVoucher
                ->entries
                ->pluck('ledger_name')
                ->map(
                    fn ($name) => trim(
                        (string) $name
                    )
                )
                ->filter()
                ->unique()
                ->values()
                ->all();

            sort(
                $actualLedgers,
                SORT_NATURAL | SORT_FLAG_CASE
            );

            $differences[] = sprintf(
                'Ledger allocation differs: ERP expects [%s]; Tally has [%s].',
                implode(', ', $expectedLedgers),
                implode(', ', $actualLedgers)
            );
        }

        return $differences;
    }

    private function amountMatches(
        FinanceVoucher $financeVoucher,
        TallyImportedVoucher $candidate
    ): bool {
        $erpAmount = round(
            abs(
                (float) $financeVoucher->amount
            ),
            2
        );

        /*
         * In a balanced double-entry voucher the sum of the absolute
         * ledger amounts is twice the transaction amount.
         */
        $tallyAmount = round(
            $candidate->entries->sum(
                fn ($entry) => abs(
                    (float) $entry->amount
                )
            ) / 2,
            2
        );

        return abs(
            $erpAmount - $tallyAmount
        ) < 0.01;
    }

    /**
     * @param  array<int, string>  $expectedLedgers
     */
    private function ledgerSetMatches(
        TallyImportedVoucher $candidate,
        array $expectedLedgers
    ): bool {
        $actualLedgers = $candidate
            ->entries
            ->pluck('ledger_name')
            ->map(
                fn ($name) => trim(
                    (string) $name
                )
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        sort(
            $actualLedgers,
            SORT_NATURAL | SORT_FLAG_CASE
        );

        return $actualLedgers === $expectedLedgers;
    }

    /**
     * Determine why a posted ERP voucher is currently unmatched.
     *
     * This method is read-only and uses the same conservative matching
     * rules as the reconciliation process.
     *
     * @return array{
     *     status: string,
     *     candidate_count: int
     * }
     */
    public function unmatchedStatus(
        FinanceVoucher $financeVoucher
    ): array {
        $expectedLedgers = $this->expectedTallyLedgers(
            $financeVoucher
        );

        if ($expectedLedgers === null) {
            return [
                'status' => 'unmapped',
                'candidate_count' => 0,
            ];
        }

        $candidates = $this->secondaryCandidates(
            $financeVoucher,
            $expectedLedgers
        );

        if ($candidates->count() > 1) {
            return [
                'status' => 'ambiguous',
                'candidate_count' => $candidates->count(),
            ];
        }

        return [
            'status' => 'erp_only',
            'candidate_count' => $candidates->count(),
        ];
    }

    /**
     * Return the current Tally candidates for an unmatched ERP voucher.
     *
     * This is read-only and uses the same conservative matching rules
     * used by the reconciliation process.
     */
    public function candidatesFor(
        FinanceVoucher $financeVoucher
    ) {
        $expectedLedgers = $this->expectedTallyLedgers(
            $financeVoucher
        );

        if ($expectedLedgers === null) {
            return collect();
        }

        return $this->secondaryCandidates(
            $financeVoucher,
            $expectedLedgers
        )->values();
    }

    /**
     * Manually reconcile one posted ERP Finance voucher with one
     * imported Tally voucher.
     *
     * Manual reconciliation is deliberately restricted to candidates
     * that satisfy the same conservative accounting rules used by the
     * automatic reconciliation process.
     */
    public function manualMatch(
        FinanceVoucher $financeVoucher,
        TallyImportedVoucher $tallyVoucher,
        int $userId,
        ?string $notes = null
    ): TallyImportedVoucher {
        return DB::transaction(
            function () use (
                $financeVoucher,
                $tallyVoucher,
                $userId,
                $notes
            ): TallyImportedVoucher {
                $lockedFinanceVoucher = FinanceVoucher::query()
                    ->with([
                        'financeHead',
                        'financeAccount',
                        'destinationAccount',
                        'tallyExport',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $financeVoucher->id
                    );

                $lockedTallyVoucher = TallyImportedVoucher::query()
                    ->with('entries')
                    ->lockForUpdate()
                    ->findOrFail(
                        $tallyVoucher->id
                    );

                if ($lockedFinanceVoucher->status !== 'posted') {
                    throw new RuntimeException(
                        'Only posted ERP Finance vouchers can be reconciled.'
                    );
                }

                $erpAlreadyMatched = TallyImportedVoucher::query()
                    ->where(
                        'finance_voucher_id',
                        $lockedFinanceVoucher->id
                    )
                    ->lockForUpdate()
                    ->exists();

                if ($erpAlreadyMatched) {
                    throw new RuntimeException(
                        'This ERP Finance voucher is already reconciled with a Tally voucher.'
                    );
                }

                if ($lockedTallyVoucher->finance_voucher_id !== null) {
                    throw new RuntimeException(
                        'This Tally voucher is already reconciled with an ERP Finance voucher.'
                    );
                }

                $candidateIds = $this
                    ->candidatesFor(
                        $lockedFinanceVoucher
                    )
                    ->pluck('id');

                if (
                    ! $candidateIds->contains(
                        $lockedTallyVoucher->id
                    )
                ) {
                    throw new RuntimeException(
                        'The selected Tally voucher is not a valid reconciliation candidate for this ERP Finance voucher.'
                    );
                }

                $auditNote = 'Manually reconciled by an authorised ERP user.';

                if (
                    $notes !== null
                    && trim($notes) !== ''
                ) {
                    $auditNote .= ' '.trim($notes);
                }

                $lockedTallyVoucher->update([
                    'finance_voucher_id' => $lockedFinanceVoucher->id,
                    'reconciliation_status' => 'matched',
                    'match_method' => 'manual',
                    'match_confidence' => null,
                    'reconciliation_notes' => $auditNote,
                    'reconciled_by' => $userId,
                    'reconciled_at' => now(),
                ]);

                return $lockedTallyVoucher->fresh([
                    'entries',
                    'financeVoucher',
                    'reconciledBy',
                ]);
            }
        );
    }

    private function secondaryConfidence(
        FinanceVoucher $financeVoucher,
        TallyImportedVoucher $candidate
    ): float {
        $erpNarration = $this->normaliseText(
            $financeVoucher->narration
        );

        $tallyNarration = $this->normaliseText(
            $candidate->narration
        );

        if (
            $erpNarration !== null
            && $tallyNarration !== null
            && $erpNarration === $tallyNarration
        ) {
            return 95.00;
        }

        return 90.00;
    }

    private function markMatched(
        TallyImportedVoucher $tallyVoucher,
        FinanceVoucher $financeVoucher,
        string $method,
        float $confidence,
        string $notes
    ): void {
        $tallyVoucher->update([
            'finance_voucher_id' => $financeVoucher->id,
            'reconciliation_status' => 'matched',
            'match_method' => $method,
            'match_confidence' => $confidence,
            'reconciliation_notes' => $notes,
        ]);
    }

    private function normaliseText(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = preg_replace(
            '/\s+/u',
            ' ',
            trim($value)
        );

        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        return mb_strtolower(
            $value
        );
    }
}
