<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\HospitalExpense;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class HospitalExpensePostingService
{
    public function __construct(
        private readonly JournalPostingService $postingService
    ) {}

    public function post(
        HospitalExpense $hospitalExpense,
        int $postingUserId,
        ?int $capitalSupplierId = null,
        bool $capitalizeTax = false,
        ?string $capitalInvoiceNo = null
    ): JournalEntry {
        if (
            ! auth()->check()
            || (int) auth()->id() !== $postingUserId
            || ! auth()->user()->hasPermission('hospital_expenses.post')
        ) {
            abort(403, 'Not authorized to post hospital expenses.');
        }

        return DB::transaction(function () use (
            $hospitalExpense,
            $postingUserId,
            $capitalSupplierId,
            $capitalizeTax,
            $capitalInvoiceNo
        ) {
            /*
            |--------------------------------------------------------------------------
            | Lock Expense and Verify Approval
            |--------------------------------------------------------------------------
            */

            $expense = HospitalExpense::query()
                ->lockForUpdate()
                ->findOrFail($hospitalExpense->id);

            if ($expense->status !== 'approved') {
                throw ValidationException::withMessages([
                    'expense' => 'Only approved expenses can be posted.',
                ]);
            }

            if (! in_array($expense->expense_type, HospitalExpense::SUPPLIER_PAYABLE_TYPES, true)) {
                throw ValidationException::withMessages([
                    'expense' => 'Inventory purchases require '
                        .'a separate accounting workflow.',
                ]);
            }

            if ($expense->journal_entry_id !== null) {
                throw ValidationException::withMessages([
                    'expense' => 'This expense is already linked to a journal.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Source Journals
            |--------------------------------------------------------------------------
            */

            $existingJournal = JournalEntry::query()
                ->where('source_type', 'hospital_expense')
                ->where('source_id', (string) $expense->id)
                ->exists();

            if ($existingJournal) {
                throw ValidationException::withMessages([
                    'expense' => 'An accounting journal already exists for this expense.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Restrict Posting to Manual Expenses
            |--------------------------------------------------------------------------
            |
            | ERP-generated expenses must be posted by their source
            | integration to prevent duplicate recognition.
            |
            */

            if (
                $expense->source_type !== null
                || $expense->source_id !== null
                || $expense->source_event !== null
            ) {
                throw ValidationException::withMessages([
                    'expense' => 'Source-linked expenses require their '
                        .'dedicated accounting integration.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Expense and Accounts Payable GL
            |--------------------------------------------------------------------------
            */

            $expenseAccount = GeneralLedgerAccount::query()
                ->whereKey($expense->general_ledger_account_id)
                ->firstOrFail();

            $isCapital = $expense->expense_type === 'capital';

            $payableAccount = GeneralLedgerAccount::query()
                ->where('account_code', '2200')
                ->first();

            if (
                ! $expenseAccount->is_active
                || ! $expenseAccount->allow_posting
                || ($isCapital ? ! $expenseAccount->isFixedAsset() : $expenseAccount->account_type !== 'expense')
                || $expenseAccount->normal_balance !== 'debit'
            ) {
                throw ValidationException::withMessages([
                    'expense' => $isCapital
                        ? 'Select a postable Fixed Assets account for a capital purchase.'
                        : 'Invalid expense General Ledger account.',
                ]);
            }

            if (
                ! $payableAccount
                || ! $payableAccount->is_active
                || ! $payableAccount->allow_posting
                || $payableAccount->account_type !== 'liability'
                || $payableAccount->normal_balance !== 'credit'
            ) {
                throw ValidationException::withMessages([
                    'expense' => 'Accounts Payable GL 2200 is not configured.',
                ]);
            }

            if (
                $expenseAccount->finance_head_id !== null
                && (int) $expenseAccount->finance_head_id
                    !== (int) $expense->finance_head_id
            ) {
                throw ValidationException::withMessages([
                    'expense' => 'Expense GL and Finance Head do not match.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Monetary Amounts and Allocations
            |--------------------------------------------------------------------------
            */

            $toPaise = static function ($amount): int {
                $parts = explode('.', (string) $amount, 2);

                return ((int) $parts[0] * 100)
                    + (int) str_pad($parts[1] ?? '', 2, '0');
            };

            $totalPaise = $toPaise($expense->total_amount);
            $subtotalPaise = $toPaise($expense->subtotal);
            $taxPaise = $toPaise($expense->tax_amount);

            if (
                $totalPaise <= 0
                || $subtotalPaise + $taxPaise !== $totalPaise
            ) {
                throw ValidationException::withMessages([
                    'expense' => 'Expense amounts do not reconcile.',
                ]);
            }

            if ($isCapital) {
                if ($taxPaise > 0 && ! $capitalizeTax) {
                    throw ValidationException::withMessages([
                        'expense' => 'Confirm that the invoice tax is non-recoverable before capitalizing it.',
                    ]);
                }

                $this->validateCapitalInvoice($expense, $capitalSupplierId, $capitalInvoiceNo);
            }

            $allocations = $expense->allocations()
                ->orderBy('id')
                ->get();

            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages([
                    'expense' => 'Department allocations are missing.',
                ]);
            }

            $allocatedPaise = 0;
            $percentageUnits = 0;

            foreach ($allocations as $allocation) {
                $amount = $toPaise($allocation->allocated_amount);

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'expense' => 'Invalid department allocation amount.',
                    ]);
                }

                $allocatedPaise += $amount;

                $percentageUnits += (int) round(
                    (float) $allocation->percentage * 10000
                );
            }

            if (
                $allocatedPaise !== $totalPaise
                || $percentageUnits !== 1000000
            ) {
                throw ValidationException::withMessages([
                    'expense' => 'Department allocations do not reconcile '
                        .'with the expense total.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Prepare Posted Accounting Journal
            |--------------------------------------------------------------------------
            */

            $journalNo = 'JRN-HE-'.$expense->id;

            if (
                JournalEntry::query()
                    ->where('journal_no', $journalNo)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'expense' => 'The expense journal number already exists.',
                ]);
            }

            $money = static fn (int $paise): string => number_format($paise / 100, 2, '.', '');

            $journal = JournalEntry::create([
                'journal_no' => $journalNo,
                'journal_date' => $expense->accounting_date->toDateString(),
                'source_type' => 'hospital_expense',
                'source_id' => (string) $expense->id,
                'source_event' => 'accrual_posted',
                'reference_no' => $expense->expense_no,
                'narration' => ($isCapital ? 'Capital purchase accrual: ' : 'Hospital expense accrual: ')
                    .$expense->description,
                'status' => 'draft',
                'created_by' => $postingUserId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Debit Expense by Department
            |--------------------------------------------------------------------------
            */

            $lineNo = 1;

            foreach ($allocations as $allocation) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'general_ledger_account_id' => $expenseAccount->id,
                    'department_id' => $allocation->department_id,
                    'debit' => $money(
                        $toPaise($allocation->allocated_amount)
                    ),
                    'credit' => '0.00',
                    'reference_no' => $expense->expense_no,
                    'description' => $expense->description,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Credit Accounts Payable
            |--------------------------------------------------------------------------
            */

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => $lineNo,
                'general_ledger_account_id' => $payableAccount->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $money($totalPaise),
                'reference_no' => $expense->expense_no,
                'description' => 'Accounts payable — '.$expense->supplier_name,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Post and Link Journal
            |--------------------------------------------------------------------------
            */

            $postedJournal = $this->postingService->post($journal);

            if ($postedJournal->status !== 'posted') {
                throw new RuntimeException(
                    'Expense journal was not posted successfully.'
                );
            }

            $expense->journal_entry_id = $postedJournal->id;
            $expense->status = 'posted';
            $expense->posted_by = $postingUserId;
            $expense->posted_at = now();
            $expense->save();

            return $postedJournal;
        }, 3);
    }
    private function validateCapitalInvoice(
        HospitalExpense $expense,
        ?int $supplierId,
        ?string $invoiceNo
    ): void {
        $invoiceNo = trim((string) $invoiceNo);
        $recordedInvoice = trim((string) $expense->invoice_no);

        if ($recordedInvoice !== '' && $invoiceNo !== '' && $recordedInvoice !== $invoiceNo) {
            throw ValidationException::withMessages([
                'expense' => 'The invoice number differs from the approved expense.',
            ]);
        }

        $invoiceNo = $recordedInvoice !== '' ? $recordedInvoice : $invoiceNo;
        if ($invoiceNo === '' || mb_strlen($invoiceNo) > 100) {
            throw ValidationException::withMessages([
                'expense' => 'A supplier invoice number is required to post a capital purchase.',
            ]);
        }

        if ($expense->supplier_id !== null && $supplierId !== null
            && (int) $expense->supplier_id !== $supplierId) {
            throw ValidationException::withMessages([
                'expense' => 'The selected supplier differs from the linked supplier.',
            ]);
        }

        $supplier = Supplier::query()
            ->whereKey($expense->supplier_id ?? $supplierId)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (! $supplier || (filled($expense->supplier_name)
            && mb_strtolower(trim($expense->supplier_name)) !== mb_strtolower(trim($supplier->supplier_name)))) {
            throw ValidationException::withMessages([
                'expense' => 'Select an active supplier whose master name matches the approved invoice payee.',
            ]);
        }

        $duplicateInvoice = HospitalExpense::query()
            ->where('supplier_id', $supplier->id)
            ->where('status', 'posted')
            ->whereRaw('LOWER(TRIM(invoice_no)) = LOWER(?)', [$invoiceNo])
            ->exists();

        if ($duplicateInvoice) {
            throw ValidationException::withMessages([
                'expense' => 'A posted expense already exists for this supplier invoice.',
            ]);
        }

        $expense->supplier()->associate($supplier);
        $expense->supplier_name = $supplier->supplier_name;
        $expense->invoice_no = $invoiceNo;
    }
}
