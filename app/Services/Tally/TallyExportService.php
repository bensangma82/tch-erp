<?php

namespace App\Services\Tally;

use App\Models\FinanceVoucher;
use App\Models\JournalEntry;

class TallyExportService
{
    /**
     * Determine whether a Finance voucher is ready for Tally export.
     *
     * @return array{
     *     eligible: bool,
     *     errors: array<int, string>,
     *     solutions?: array<int, array{label: string, route: string, parameters: array<string, mixed>, fragment?: string}>
     * }
     */
   public function checkEligibility(
    FinanceVoucher $voucher,
    bool $forRetry = false
): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($voucher->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        $voucher->loadMissing([
            'financeHead.tallyMapping',
            'financeAccount.tallyMapping',
            'destinationAccount.tallyMapping',
            'tallyExport',
        ]);

        $errors = [];
        $solutions = [];

        /*
|--------------------------------------------------------------------------
| Hospital Supplier Payment Export Policy
|--------------------------------------------------------------------------
| Supplier payments must be exported through their GL journals,
| never through the linked Finance Voucher.
|--------------------------------------------------------------------------
*/

if (
    $voucher->source_type === 'hospital_expense_payment'
    && $voucher->source_event === 'supplier_payment'
) {
    $journal = $this->supplierPaymentJournal($voucher);
    if ($journal !== null) {
        $solutions[count($errors)] = [
            'label' => 'Open linked journal in Tally Export',
            'route' => 'finance.tally.journals.index',
            'parameters' => ['status' => 'all', 'search' => $journal->journal_no],
        ];
    }

    $errors[] = 'Supplier payment Finance Vouchers cannot be '
        .'exported to Tally. Export the linked GL Journal instead.';
}
        /*
|--------------------------------------------------------------------------
| Transaction Origin Validation
|--------------------------------------------------------------------------
*/

        if ($voucher->origin === 'tally_import') {
            $errors[] = 'Tally-imported vouchers cannot be exported back to Tally.';
        }

        if (blank($voucher->origin)) {
            $errors[] = 'Voucher origin is unknown. Classify it before Tally export.';
        }

        /*
        |--------------------------------------------------------------------------
        | Voucher status
        |--------------------------------------------------------------------------
        */

        if ($voucher->status !== 'posted') {
            $errors[] = 'Only posted Finance vouchers can be exported to Tally.';
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate export protection
        |--------------------------------------------------------------------------
        */
if ($forRetry) {
    if (
        $voucher->tallyExport === null
        || $voucher->tallyExport->status !== 'failed'
    ) {
        $errors[] = 'Only Finance vouchers with a failed Tally export can be retried.';
    }
} elseif ($voucher->tallyExport !== null) {
    $errors[] = 'This Finance voucher already has a Tally export record. Reconciliation is required before another export.';
}

        /*
|--------------------------------------------------------------------------
| Cross-Workflow Duplicate Export Protection
|--------------------------------------------------------------------------
| Prevent a Finance Voucher from being exported if its source
| accounting event already has a GL Journal export record.
|--------------------------------------------------------------------------
*/

if (
    filled($voucher->source_type)
    && filled($voucher->source_id)
    && filled($voucher->source_event)
) {
    $matchingJournalExport = JournalEntry::query()
        ->where('source_type', $voucher->source_type)
        ->where('source_id', $voucher->source_id)
        ->where('source_event', $voucher->source_event)
        ->whereHas('tallyExport')
        ->exists();

    if ($matchingJournalExport) {
        $errors[] = 'A GL Journal for this accounting event already '
            .'has a Tally export record. Finance Voucher export '
            .'is blocked to prevent duplicate accounting.';
    }
}
        /*
        |--------------------------------------------------------------------------
        | Receipt / Payment
        |--------------------------------------------------------------------------
        */

        if (in_array($voucher->voucher_type, ['receipt', 'payment'], true)) {
            if (! $voucher->financeHead) {
                $errors[] = 'Finance Head is missing.';
            } elseif (
                ! $voucher->financeHead->tallyMapping
                || ! $voucher->financeHead->tallyMapping->is_active
                || blank($voucher->financeHead->tallyMapping->tally_ledger_name)
            ) {
                $solutions[count($errors)] = $this->mappingSolution('head', $voucher->financeHead->id);
                $errors[] = sprintf(
                    'Finance Head %s has no active Tally ledger mapping.',
                    $voucher->financeHead->code
                );
            }

            if (! $voucher->financeAccount) {
                $errors[] = 'Finance Account is missing.';
            } elseif (
                ! $voucher->financeAccount->tallyMapping
                || ! $voucher->financeAccount->tallyMapping->is_active
                || blank($voucher->financeAccount->tallyMapping->tally_ledger_name)
            ) {
                $solutions[count($errors)] = $this->mappingSolution('account', $voucher->financeAccount->id);
                $errors[] = sprintf(
                    'Finance Account %s has no active Tally ledger mapping.',
                    $voucher->financeAccount->code
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Transfer
        |--------------------------------------------------------------------------
        */

        elseif ($voucher->voucher_type === 'transfer') {
            if (! $voucher->financeAccount) {
                $errors[] = 'Source Finance Account is missing.';
            } elseif (
                ! $voucher->financeAccount->tallyMapping
                || ! $voucher->financeAccount->tallyMapping->is_active
                || blank($voucher->financeAccount->tallyMapping->tally_ledger_name)
            ) {
                $solutions[count($errors)] = $this->mappingSolution('account', $voucher->financeAccount->id);
                $errors[] = sprintf(
                    'Source Finance Account %s has no active Tally ledger mapping.',
                    $voucher->financeAccount->code
                );
            }

            if (! $voucher->destinationAccount) {
                $errors[] = 'Destination Finance Account is missing.';
            } elseif (
                ! $voucher->destinationAccount->tallyMapping
                || ! $voucher->destinationAccount->tallyMapping->is_active
                || blank($voucher->destinationAccount->tallyMapping->tally_ledger_name)
            ) {
                $solutions[count($errors)] = $this->mappingSolution('account', $voucher->destinationAccount->id);
                $errors[] = sprintf(
                    'Destination Finance Account %s has no active Tally ledger mapping.',
                    $voucher->destinationAccount->code
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Unsupported voucher type
        |--------------------------------------------------------------------------
        */

        else {
            $errors[] = sprintf(
                'Voucher type "%s" is not supported for Tally export.',
                $voucher->voucher_type
            );
        }

        return [
            'eligible' => $errors === [],
            'errors' => $errors,
            'solutions' => $solutions,
        ];
    }

    private function mappingSolution(string $kind, int $id): array
    {
        return [
            'label' => 'Fix ledger mapping',
            'route' => 'finance.tally.index',
            'parameters' => [],
            'fragment' => "tally-{$kind}-{$id}-row",
        ];
    }

    private function supplierPaymentJournal(FinanceVoucher $voucher): ?JournalEntry
    {
        $journals = JournalEntry::query()
            ->where('finance_voucher_id', $voucher->id)
            ->where('source_type', $voucher->source_type)
            ->where('source_id', $voucher->source_id)
            ->where('source_event', $voucher->source_event)
            ->where('status', 'posted')
            ->limit(2)
            ->get(['id', 'journal_no']);

        return $journals->count() === 1 ? $journals->first() : null;
    }
}
