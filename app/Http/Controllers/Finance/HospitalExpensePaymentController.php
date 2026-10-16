<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\HospitalExpense;
use App\Services\Accounting\HospitalExpensePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HospitalExpensePaymentController extends Controller
{
    /**
 * List posted hospital expenses with supplier payment balances.
 */
public function index(
    HospitalExpensePaymentService $paymentService
): View {
    abort_unless(
        auth()->user()->hasPermission('hospital_expenses.pay'),
        403
    );

    $expenses = HospitalExpense::query()
        ->where('status', 'posted')
        ->where('expense_type', 'operating')
        ->whereNotNull('journal_entry_id')
        ->with('payments')
        ->orderByDesc('expense_date')
        ->paginate(20);

    return view(
        'finance.hospital-expenses.payments.index',
        compact('expenses', 'paymentService')
    );
}
    /**
     * Display the supplier payment form and payment history.
     */
    public function create(
        HospitalExpense $hospitalExpense,
        HospitalExpensePaymentService $paymentService
    ): View {
        abort_unless(
            auth()->user()->hasPermission('hospital_expenses.pay'),
            403
        );

        abort_unless(
            $hospitalExpense->status === 'posted'
                && $hospitalExpense->expense_type === 'operating'
                && $hospitalExpense->journal_entry_id !== null,
            422,
            'Only posted operating expenses can be paid.'
        );

        $accounts = FinanceAccount::query()
            ->where('is_active', true)
            ->whereIn('account_type', ['cash', 'bank'])
            ->whereHas('generalLedgerAccount', function ($query) {
                $query->where('is_active', true)
                    ->where('allow_posting', true)
                    ->where('account_type', 'asset');
            })
            ->orderBy('name')
            ->get();

        $payments = $hospitalExpense->payments()
            ->with(['financeVoucher', 'createdBy'])
            ->latest('id')
            ->get();

        $outstanding = $paymentService->outstanding(
            $hospitalExpense
        );

        return view(
            'finance.hospital-expenses.payments.create',
            compact(
                'hospitalExpense',
                'accounts',
                'payments',
                'outstanding'
            )
        );
    }

    /**
     * Record supplier payment and post its accounting journal.
     */
    public function store(
        Request $request,
        HospitalExpense $hospitalExpense,
        HospitalExpensePaymentService $paymentService
    ): RedirectResponse {
        abort_unless(
            auth()->user()->hasPermission('hospital_expenses.pay'),
            403
        );

        $validated = $request->validate([
            'finance_account_id' => [
                'required',
                'integer',
                'exists:finance_accounts,id',
            ],
            'payment_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
            ],
            'amount' => [
                'required',
                'regex:/^\d+(?:\.\d{1,2})?$/',
            ],
            'payment_mode' => [
                'required',
                'string',
                'max:50',
            ],
            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $financeAccount = FinanceAccount::query()
            ->findOrFail($validated['finance_account_id']);

        $payment = $paymentService->pay(
            expense: $hospitalExpense,
            financeAccount: $financeAccount,
            paymentDate: $validated['payment_date'],
            amount: (string) $validated['amount'],
            paymentMode: $validated['payment_mode'],
            referenceNo: $validated['reference_no'] ?? null,
            remarks: $validated['remarks'] ?? null,
            userId: (int) auth()->id(),
        );

        return redirect()
            ->route(
                'finance.hospital-expenses.payments.create',
                $hospitalExpense
            )
            ->with(
                'success',
                'Supplier payment recorded and GL journal posted. '
                .'Payment ID: '.$payment->id
            );
    }
}
