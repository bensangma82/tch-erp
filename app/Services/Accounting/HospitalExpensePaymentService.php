<?php

namespace App\Services\Accounting;

use App\Models\FinanceAccount;
use App\Models\FinanceVoucher;
use App\Models\GeneralLedgerAccount;
use App\Models\HospitalExpense;
use App\Models\HospitalExpensePayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Finance\FinanceVoucherNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HospitalExpensePaymentService
{
    public function __construct(
        private readonly JournalPostingService $journalPostingService,
        private readonly FinanceVoucherNumberService $voucherNumberService,
    ) {}

    /**
     * Record and post a supplier payment.
     *
     * Creates the payment record, Finance Voucher and
     * General Ledger journal in one database transaction.
     */
    public function pay(
        HospitalExpense $expense,
        FinanceAccount $financeAccount,
        string $paymentDate,
        string $amount,
        string $paymentMode,
        ?string $referenceNo,
        ?string $remarks,
        int $userId,
    ): HospitalExpensePayment {
        if (
            ! auth()->user()?->hasPermission('hospital_expenses.pay')
            || (int) auth()->id() !== $userId
        ) {
            abort(403, 'Not authorized to record supplier payments.');
        }

        $amountCents = $this->toCents($amount);

        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use (
            $expense,
            $financeAccount,
            $paymentDate,
            $amountCents,
            $paymentMode,
            $referenceNo,
            $remarks,
            $userId
        ) {
            $expense = HospitalExpense::query()
                ->whereKey($expense->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $expense->status !== 'posted'
                || $expense->expense_type !== 'operating'
                || ! $expense->journal_entry_id
            ) {
                throw ValidationException::withMessages([
                    'expense' => 'Only posted operating expenses with a GL journal can be paid.',
                ]);
            }

            $financeAccount = FinanceAccount::query()
                ->whereKey($financeAccount->id)
                ->firstOrFail();

            if (
                ! $financeAccount->is_active
                || ! in_array($financeAccount->account_type, ['cash', 'bank'], true)
            ) {
                throw ValidationException::withMessages([
                    'finance_account_id' => 'Select an active cash or bank account.',
                ]);
            }

            $cashGl = GeneralLedgerAccount::query()
                ->whereKey($financeAccount->general_ledger_account_id)
                ->first();

            if (
                ! $cashGl
                || ! $cashGl->is_active
                || ! $cashGl->allow_posting
                || $cashGl->account_type !== 'asset'
            ) {
                throw ValidationException::withMessages([
                    'finance_account_id' => 'The cash/bank GL mapping is missing or invalid.',
                ]);
            }

            $payableGl = GeneralLedgerAccount::query()
                ->where('account_code', '2200')
                ->first();

            if (
                ! $payableGl
                || ! $payableGl->is_active
                || ! $payableGl->allow_posting
                || $payableGl->account_type !== 'liability'
            ) {
                throw ValidationException::withMessages([
                    'expense' => 'Accounts Payable GL 2200 is not ready for posting.',
                ]);
            }

            $alreadyPaidCents = 0;

            $existingPayments = HospitalExpensePayment::query()
                ->where('hospital_expense_id', $expense->id)
                ->whereIn('status', ['recorded', 'posted'])
                ->get(['amount']);

            foreach ($existingPayments as $existingPayment) {
                $alreadyPaidCents += $this->toCents(
                    (string) $existingPayment->amount
                );
            }

            $outstandingCents = $this->toCents(
                (string) $expense->total_amount
            ) - $alreadyPaidCents;

            if (
                $outstandingCents <= 0
                || $amountCents > $outstandingCents
            ) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment exceeds the outstanding supplier balance.',
                ]);
            }

            $amountDecimal = number_format(
                $amountCents / 100,
                2,
                '.',
                ''
            );

            $payment = new HospitalExpensePayment();
            $payment->hospital_expense_id = $expense->id;
            $payment->payment_date = $paymentDate;
            $payment->amount = $amountDecimal;
            $payment->payment_mode = $paymentMode;
            $payment->reference_no = $referenceNo;
            $payment->remarks = $remarks;
            $payment->status = 'recorded';
            $payment->created_by = $userId;
            $payment->save();

            /*
             * Create the Finance Voucher.
             *
             * This is a settlement of an existing payable,
             * not recognition of a new expense.
             */
            $voucher = new FinanceVoucher();
            $voucher->voucher_no = $this->voucherNumberService
                ->generate('payment', $paymentDate);
            $voucher->voucher_type = 'payment';
            $voucher->voucher_date = $paymentDate;
            $voucher->origin = 'erp_internal';
            $voucher->source_type = 'hospital_expense_payment';
            $voucher->source_id = $payment->id;
            $voucher->source_event = 'supplier_payment';
            $voucher->finance_head_id = null;
            $voucher->finance_account_id = $financeAccount->id;
            $voucher->amount = $amountDecimal;
            $voucher->payment_mode = $paymentMode;
            $voucher->reference_no = $referenceNo;
            $voucher->party_name = $expense->supplier_name;
            $voucher->narration =
                'Supplier payment against '.$expense->expense_no;
            $voucher->status = 'posted';
            $voucher->created_by = $userId;
            $voucher->posted_by = $userId;
            $voucher->posted_at = now();
            $voucher->save();

            /*
             * Record settlement in the General Ledger.
             *
             * DR Accounts Payable
             * CR Cash / Bank
             */
            $journal = JournalEntry::create([
                'journal_no' => 'JRN-HEPAY-'.$payment->id,
                'journal_date' => $paymentDate,
                'source_type' => 'hospital_expense_payment',
                'source_id' => $payment->id,
                'source_event' => 'supplier_payment',
                'finance_voucher_id' => $voucher->id,
                'reference_no' => $voucher->voucher_no,
                'narration' =>
                    'Payment against '.$expense->expense_no,
                'status' => 'draft',
                'created_by' => $userId,
            ]);
// Lock the draft journal before creating accounting lines.
$journal = JournalEntry::query()
    ->whereKey($journal->id)
    ->lockForUpdate()
    ->firstOrFail();
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' => $payableGl->id,
                'department_id' => null,
                'debit' => $amountDecimal,
                'credit' => '0.00',
                'description' =>
                    'Settlement of '.$expense->expense_no,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,
                'general_ledger_account_id' => $cashGl->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $amountDecimal,
                'description' =>
                    'Supplier payment from '.$financeAccount->name,
            ]);
// Reload the journal and its lines before posting.
$journal->load('lines.generalLedgerAccount');
            $this->journalPostingService->post($journal);

            $payment->finance_voucher_id = $voucher->id;
            $payment->status = 'posted';
            $payment->save();

            return $payment->fresh();
        }, 3);
    }

    /**
     * Calculate unpaid supplier balance.
     */
    public function outstanding(HospitalExpense $expense): string
    {
        $paidCents = 0;

        $payments = HospitalExpensePayment::query()
            ->where('hospital_expense_id', $expense->id)
            ->whereIn('status', ['recorded', 'posted'])
            ->get(['amount']);

        foreach ($payments as $payment) {
            $paidCents += $this->toCents(
                (string) $payment->amount
            );
        }

        $balanceCents = max(
            0,
            $this->toCents((string) $expense->total_amount)
                - $paidCents
        );

        return number_format(
            $balanceCents / 100,
            2,
            '.',
            ''
        );
    }

    private function toCents(string $amount): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages([
                'amount' => 'Enter a valid amount with up to two decimal places.',
            ]);
        }

        [$rupees, $paise] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        if (strlen($rupees) > 12) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds the permitted limit.',
            ]);
        }

        return ((int) $rupees * 100)
            + (int) str_pad($paise, 2, '0');
    }
}
