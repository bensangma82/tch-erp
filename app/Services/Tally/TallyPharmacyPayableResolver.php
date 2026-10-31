<?php

namespace App\Services\Tally;

use App\Models\PharmacyGrn;
use App\Models\PharmacySupplierPayable;
use App\Services\Tally\TallySupplierLedgerValidationService;
use RuntimeException;

class TallyPharmacyPayableResolver
{
    public function resolve(PharmacyGrn $grn): array
    {
        if ($grn->status !== 'completed') {
            throw new RuntimeException('GRN is not completed.');
        }

        $grn->loadMissing('supplier.supplier');

        $supplier = $grn->supplier?->supplier;

        if (! $supplier) {
            throw new RuntimeException(
                'Shared supplier mapping is missing.'
            );
        }

        $payables = PharmacySupplierPayable::query()
            ->where('pharmacy_grn_id', $grn->id)
            ->get();

        if ($payables->count() !== 1) {
            throw new RuntimeException(
                'GRN must have exactly one supplier payable.'
            );
        }

        $payable = $payables->first();

        if (
            (string) $payable->pharmacy_supplier_id
                !== (string) $grn->pharmacy_supplier_id
            || blank($payable->payable_no)
            || (string) $payable->original_amount
                !== (string) $grn->total_amount
        ) {
            throw new RuntimeException(
                'GRN and supplier payable do not reconcile.'
            );
        }

        $validation = app(
            TallySupplierLedgerValidationService::class
        )->validate($supplier);

        if (! $validation['valid']) {
            throw new RuntimeException(
                implode(' ', $validation['errors'])
            );
        }

        return [
            'supplier_id' => $supplier->id,
            'supplier_ledger' => $validation['ledger_name'],
            'bill_reference' => $payable->payable_no,
            'supplier_invoice' => $payable->supplier_invoice_no,
            'payable_id' => $payable->id,
        ];
    }
}
