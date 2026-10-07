<?php

namespace App\Services\Tally;

use App\Models\FinanceVoucher;
use App\Models\TallyImportedVoucher;
use App\Models\TallyLedgerMapping;
use Illuminate\Support\Collection;

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
     * }
     */
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
             * First preference: an exact REMOTEID match.
             *
             * This will work whenever the identifier returned by Tally
             * exactly matches the stable REMOTEID stored by the ERP.
             */
            $exactMatch = $this->findExactRemoteIdMatch(
                $financeVoucher
            );

            if ($exactMatch !== null) {
                $this->markMatched(
                    $exactMatch,
                    $financeVoucher,
                    'exact_remote_id',
                    100.00,
                    'Matched automatically using the exact ERP/Tally REMOTEID.'
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
