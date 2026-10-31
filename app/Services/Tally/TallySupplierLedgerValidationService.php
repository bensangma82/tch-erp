<?php

namespace App\Services\Tally;

use App\Models\Supplier;
use App\Models\TallyLedgerMapping;
use App\Models\TallyLedgerMaster;
use Illuminate\Support\Carbon;

class TallySupplierLedgerValidationService
{
    public function validate(Supplier $supplier): array
    {
        $errors = [];
        $company = trim((string) config('tally.company'));

        if (! $supplier->exists || ! $supplier->is_active) {
            $errors[] = 'Supplier is missing or inactive.';
        }

        if ($company === '') {
            $errors[] = 'Tally company is not configured.';
        }

        $mapping = null;

        if ($supplier->exists && $company !== '') {
            $mapping = TallyLedgerMapping::query()
                ->where('supplier_id', $supplier->id)
                ->where('tally_company', $company)
                ->where('is_active', true)
                ->first();

            if (! $mapping) {
                $errors[] = 'Active supplier Tally mapping not found.';
            }
        }

        if ($mapping) {
            $ledger = TallyLedgerMaster::query()
                ->where('tally_company', $company)
                ->where('ledger_name', $mapping->tally_ledger_name)
                ->first();

            if (! $ledger) {
                $errors[] = 'Mapped supplier ledger not found in Tally master.';
            } elseif (trim((string) $ledger->parent_group) !== 'Sundry Creditors') {
                $errors[] = 'Supplier ledger is not under Sundry Creditors.';
            }

            if (
                trim((string) $mapping->tally_group_name)
                    !== 'Sundry Creditors'
            ) {
                $errors[] = 'Supplier mapping group is invalid.';
            }
        }

        if ($company !== '') {
            $latestSync = TallyLedgerMaster::query()
                ->where('tally_company', $company)
                ->max('last_synced_at');

            if (
                ! $latestSync
                || Carbon::parse($latestSync)->lt(now()->subHours(24))
            ) {
                $errors[] = 'Tally Ledger Master is outdated.';
            }
        }

        return [
            'valid' => count($errors) === 0,
            'supplier_id' => $supplier->id,
            'tally_company' => $company,
            'ledger_name' => $mapping?->tally_ledger_name,
            'errors' => $errors,
        ];
    }
}
