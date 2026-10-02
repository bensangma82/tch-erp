<?php

namespace App\Services\Tally;

use App\Models\FinanceVoucher;

class TallyExportService
{
    /**
     * Determine whether a Finance voucher is ready for Tally export.
     *
     * @return array{
     *     eligible: bool,
     *     errors: array<int, string>
     * }
     */
    public function checkEligibility(FinanceVoucher $voucher): array
    {
        $voucher->loadMissing([
            'financeHead.tallyMapping',
            'financeAccount.tallyMapping',
            'destinationAccount.tallyMapping',
            'tallyExport',
        ]);

        $errors = [];

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

        if ($voucher->tallyExport !== null) {
            $errors[] = 'This Finance voucher already has a Tally export record.';
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
        ];
    }
}
