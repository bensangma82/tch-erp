<?php

namespace App\Services\Tally;

use App\Models\HospitalExpense;
use App\Models\JournalEntry;
use RuntimeException;

class TallyHospitalExpenseResolver
{
    public function resolve(HospitalExpense $expense): array
    {
        if (
            $expense->status !== 'posted'
            || ! $expense->journal_entry_id
            || blank($expense->expense_no)
        ) {
            throw new RuntimeException(
                'Hospital expense is not posted or lacks a journal.'
            );
        }

        $expense->loadMissing('supplier');

        $supplier = $expense->supplier;

        if (
            ! $supplier
            || ! $supplier->is_active
            || ! $expense->supplier_id
        ) {
            throw new RuntimeException(
                'Verified shared supplier linkage is missing.'
            );
        }

        $journal = JournalEntry::find($expense->journal_entry_id);

        if (
            ! $journal
            || $journal->status !== 'posted'
            || $journal->source_type !== 'hospital_expense'
            || $journal->source_event !== 'accrual_posted'
            || (string) $journal->source_id !== (string) $expense->id
            || (string) $journal->reference_no !== (string) $expense->expense_no
        ) {
            throw new RuntimeException(
                'Hospital expense accrual journal does not match.'
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
            'bill_reference' => $expense->expense_no,
            'supplier_invoice' => $expense->invoice_no,
            'expense_id' => $expense->id,
            'journal_id' => $journal->id,
        ];
    }
}
