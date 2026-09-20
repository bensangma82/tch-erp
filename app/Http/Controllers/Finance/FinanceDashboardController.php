<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\IpBillingAdvance;
use App\Models\IpBillingMhisReceipt;
use App\Models\IpBillingPayment;
use App\Models\Payment;
use App\Services\Finance\BillingFinanceService;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function index(
        BillingFinanceService $billingFinanceService
    ): View {
        $today = now()->toDateString();
        $year = now()->year;
        $month = now()->month;

        /*
        |--------------------------------------------------------------------------
        | Manual Finance Vouchers
        |--------------------------------------------------------------------------
        */

        $manualTodayReceipts = (float) FinanceVoucher::query()
            ->where('status', 'posted')
            ->where('voucher_type', 'receipt')
            ->whereDate('voucher_date', $today)
            ->sum('amount');

        $todayPayments = (float) FinanceVoucher::query()
            ->where('status', 'posted')
            ->where('voucher_type', 'payment')
            ->whereDate('voucher_date', $today)
            ->sum('amount');

        $manualMonthReceipts = (float) FinanceVoucher::query()
            ->where('status', 'posted')
            ->where('voucher_type', 'receipt')
            ->whereYear('voucher_date', $year)
            ->whereMonth('voucher_date', $month)
            ->sum('amount');

        $monthPayments = (float) FinanceVoucher::query()
            ->where('status', 'posted')
            ->where('voucher_type', 'payment')
            ->whereYear('voucher_date', $year)
            ->whereMonth('voucher_date', $month)
            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | OPD / Investigation Billing Collections
        |--------------------------------------------------------------------------
        |
        | Payment remains the source of truth.
        | These transactions are not copied into FinanceVoucher.
        |
        */

        $billingTodayPayments = Payment::query()
            ->with([
                'invoice.items.service',
            ])
            ->whereDate('payment_date', $today)
            ->get();

        $billingMonthPayments = Payment::query()
            ->with([
                'invoice.items.service',
            ])
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->get();

        $billingTodayReceipts =
            (float) $billingTodayPayments->sum(
                fn (Payment $payment) =>
                    (float) $payment->amount
            );

        $billingMonthReceipts =
            (float) $billingMonthPayments->sum(
                fn (Payment $payment) =>
                    (float) $payment->amount
            );

        /*
        |--------------------------------------------------------------------------
        | Inpatient Advances
        |--------------------------------------------------------------------------
        */

        $ipTodayAdvances = IpBillingAdvance::query()
            ->where('status', 'active')
            ->whereDate('payment_date', $today)
            ->get();

        $ipMonthAdvances = IpBillingAdvance::query()
            ->where('status', 'active')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->get();

        $ipTodayAdvanceTotal =
            (float) $ipTodayAdvances->sum(
                fn (IpBillingAdvance $advance) =>
                    (float) $advance->amount
            );

        $ipMonthAdvanceTotal =
            (float) $ipMonthAdvances->sum(
                fn (IpBillingAdvance $advance) =>
                    (float) $advance->amount
            );

        /*
        |--------------------------------------------------------------------------
        | Finalized IP Patient Payments
        |--------------------------------------------------------------------------
        */

        $ipTodayPayments = IpBillingPayment::query()
            ->where('status', 'active')
            ->whereDate('payment_date', $today)
            ->get();

        $ipMonthPayments = IpBillingPayment::query()
            ->where('status', 'active')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->get();

        $ipTodayPaymentTotal =
            (float) $ipTodayPayments->sum(
                fn (IpBillingPayment $payment) =>
                    (float) $payment->amount
            );

        $ipMonthPaymentTotal =
            (float) $ipMonthPayments->sum(
                fn (IpBillingPayment $payment) =>
                    (float) $payment->amount
            );

        $ipTodayCollectionTotal =
            $ipTodayAdvanceTotal
            + $ipTodayPaymentTotal;

        $ipMonthCollectionTotal =
            $ipMonthAdvanceTotal
            + $ipMonthPaymentTotal;

        /*
        |--------------------------------------------------------------------------
        | MHIS Actual Receipts
        |--------------------------------------------------------------------------
        |
        | Only actual active MHIS receipts are included here.
        | Claim settlement amounts are not added again.
        |
        */

        $mhisTodayReceipts = IpBillingMhisReceipt::query()
            ->where('status', 'active')
            ->whereDate('receipt_date', $today)
            ->get();

        $mhisMonthReceipts = IpBillingMhisReceipt::query()
            ->where('status', 'active')
            ->whereYear('receipt_date', $year)
            ->whereMonth('receipt_date', $month)
            ->get();

        $mhisTodayReceiptTotal =
            (float) $mhisTodayReceipts->sum(
                fn (IpBillingMhisReceipt $receipt) =>
                    (float) $receipt->amount
            );

        $mhisMonthReceiptTotal =
            (float) $mhisMonthReceipts->sum(
                fn (IpBillingMhisReceipt $receipt) =>
                    (float) $receipt->amount
            );

        /*
        |--------------------------------------------------------------------------
        | Combined Receipt Totals
        |--------------------------------------------------------------------------
        */

        $todayReceipts =
            $manualTodayReceipts
            + $billingTodayReceipts
            + $ipTodayCollectionTotal
            + $mhisTodayReceiptTotal;

        $monthReceipts =
            $manualMonthReceipts
            + $billingMonthReceipts
            + $ipMonthCollectionTotal
            + $mhisMonthReceiptTotal;

        /*
        |--------------------------------------------------------------------------
        | Monthly Revenue by Finance Head
        |--------------------------------------------------------------------------
        */

        $billingMonthRevenueByHead = [];

        foreach ($billingMonthPayments as $payment) {
            $allocations =
                $billingFinanceService
                    ->allocateRevenue($payment);

            foreach ($allocations as $allocation) {
                $code =
                    $allocation['finance_head_code'];

                if (
                    !isset(
                        $billingMonthRevenueByHead[$code]
                    )
                ) {
                    $billingMonthRevenueByHead[$code] = [
                        'code' =>
                            $allocation[
                                'finance_head_code'
                            ],

                        'name' =>
                            $allocation[
                                'finance_head_name'
                            ],

                        'amount' => 0.0,
                    ];
                }

                $billingMonthRevenueByHead[$code]['amount'] +=
                    (float) $allocation['amount'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Add IP Collections to INC-IPD
        |--------------------------------------------------------------------------
        */

        $ipIncomeHead = FinanceHead::query()
            ->where('code', 'INC-IPD')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (
            $ipIncomeHead
            && $ipMonthCollectionTotal > 0
        ) {
            if (
                !isset(
                    $billingMonthRevenueByHead['INC-IPD']
                )
            ) {
                $billingMonthRevenueByHead['INC-IPD'] = [
                    'code' => 'INC-IPD',
                    'name' => $ipIncomeHead->name,
                    'amount' => 0.0,
                ];
            }

            $billingMonthRevenueByHead[
                'INC-IPD'
            ]['amount'] += $ipMonthCollectionTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | Add MHIS Receipts to INC-MHIS
        |--------------------------------------------------------------------------
        */

        $mhisIncomeHead = FinanceHead::query()
            ->where('code', 'INC-MHIS')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (
            $mhisIncomeHead
            && $mhisMonthReceiptTotal > 0
        ) {
            if (
                !isset(
                    $billingMonthRevenueByHead['INC-MHIS']
                )
            ) {
                $billingMonthRevenueByHead['INC-MHIS'] = [
                    'code' => 'INC-MHIS',
                    'name' => $mhisIncomeHead->name,
                    'amount' => 0.0,
                ];
            }

            $billingMonthRevenueByHead[
                'INC-MHIS'
            ]['amount'] += $mhisMonthReceiptTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | Master Data Counts
        |--------------------------------------------------------------------------
        */

        $activeAccounts = FinanceAccount::query()
            ->where('is_active', true)
            ->count();

        $activeIncomeHeads = FinanceHead::query()
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->count();

        $activeExpenseHeads = FinanceHead::query()
            ->where('head_type', 'expense')
            ->where('is_active', true)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Finance Account Balances
        |--------------------------------------------------------------------------
        |
        | Balance =
        | Opening Balance
        | + Manual Posted Receipts
        | + Integrated Collections assigned to the account
        | - Manual Posted Payments
        | - Transfers Out
        | + Transfers In
        |
        */

        $allBillingPayments = Payment::query()
            ->with([
                'invoice.items.service',
            ])
            ->get();

        $allIpAdvances = IpBillingAdvance::query()
            ->where('status', 'active')
            ->get();

        $allIpPayments = IpBillingPayment::query()
            ->where('status', 'active')
            ->get();

        $billingReceiptsByAccount = [];

        /*
         * OPD / investigation collections.
         */
        foreach ($allBillingPayments as $payment) {
            $account =
                $billingFinanceService
                    ->resolveAccount($payment);

            /*
             * Unknown payment modes are deliberately excluded
             * rather than silently mapped to the wrong account.
             */
            if (!$account) {
                continue;
            }

            if (
                !isset(
                    $billingReceiptsByAccount[
                        $account->id
                    ]
                )
            ) {
                $billingReceiptsByAccount[
                    $account->id
                ] = 0.0;
            }

            $billingReceiptsByAccount[
                $account->id
            ] += (float) $payment->amount;
        }

        /*
        |--------------------------------------------------------------------------
        | IP Cash Collections -> Main Cash
        |--------------------------------------------------------------------------
        |
        | Only cash is mapped automatically.
        | UPI/card remain unassigned until explicit bank
        | account mappings are configured.
        |
        */

        $mainCashAccount = FinanceAccount::query()
            ->where('code', 'CASH-MAIN')
            ->where('is_active', true)
            ->first();

        if ($mainCashAccount) {
            $allIpCashAdvances =
                (float) $allIpAdvances
                    ->filter(
                        fn (IpBillingAdvance $advance) =>
                            strtolower(
                                (string) $advance->payment_mode
                            ) === 'cash'
                    )
                    ->sum(
                        fn (IpBillingAdvance $advance) =>
                            (float) $advance->amount
                    );

            $allIpCashPayments =
                (float) $allIpPayments
                    ->filter(
                        fn (IpBillingPayment $payment) =>
                            strtolower(
                                (string) $payment->payment_mode
                            ) === 'cash'
                    )
                    ->sum(
                        fn (IpBillingPayment $payment) =>
                            (float) $payment->amount
                    );

            $allIpCashCollections =
                $allIpCashAdvances
                + $allIpCashPayments;

            if ($allIpCashCollections > 0) {
                if (
                    !isset(
                        $billingReceiptsByAccount[
                            $mainCashAccount->id
                        ]
                    )
                ) {
                    $billingReceiptsByAccount[
                        $mainCashAccount->id
                    ] = 0.0;
                }

                $billingReceiptsByAccount[
                    $mainCashAccount->id
                ] += $allIpCashCollections;
            }
        }

        /*
         * MHIS receipts are intentionally not assigned to a
         * Finance Account until the receiving bank account is
         * explicitly configured.
         */

        $accountBalances = FinanceAccount::query()
            ->where('is_active', true)
            ->orderBy('account_type')
            ->orderBy('name')
            ->get()
            ->map(
                function (
                    FinanceAccount $account
                ) use (
                    $billingReceiptsByAccount
                ) {
                    $openingBalance =
                        (float) $account
                            ->opening_balance;

                    $manualReceipts =
                        (float) FinanceVoucher::query()
                            ->where(
                                'status',
                                'posted'
                            )
                            ->where(
                                'voucher_type',
                                'receipt'
                            )
                            ->where(
                                'finance_account_id',
                                $account->id
                            )
                            ->sum('amount');

                    $integratedReceipts =
                        (float) (
                            $billingReceiptsByAccount[
                                $account->id
                            ]
                            ?? 0
                        );

                    $payments =
                        (float) FinanceVoucher::query()
                            ->where(
                                'status',
                                'posted'
                            )
                            ->where(
                                'voucher_type',
                                'payment'
                            )
                            ->where(
                                'finance_account_id',
                                $account->id
                            )
                            ->sum('amount');

                    $transfersOut =
                        (float) FinanceVoucher::query()
                            ->where(
                                'status',
                                'posted'
                            )
                            ->where(
                                'voucher_type',
                                'transfer'
                            )
                            ->where(
                                'finance_account_id',
                                $account->id
                            )
                            ->sum('amount');

                    $transfersIn =
                        (float) FinanceVoucher::query()
                            ->where(
                                'status',
                                'posted'
                            )
                            ->where(
                                'voucher_type',
                                'transfer'
                            )
                            ->where(
                                'destination_account_id',
                                $account->id
                            )
                            ->sum('amount');

                    $totalReceipts =
                        $manualReceipts
                        + $integratedReceipts;

                    $account->calculated_balance =
                        $openingBalance
                        + $totalReceipts
                        - $payments
                        - $transfersOut
                        + $transfersIn;

                    $account->receipt_total =
                        $totalReceipts;

                    $account->manual_receipt_total =
                        $manualReceipts;

                    $account->billing_receipt_total =
                        $integratedReceipts;

                    $account->payment_total =
                        $payments;

                    $account->transfer_out_total =
                        $transfersOut;

                    $account->transfer_in_total =
                        $transfersIn;

                    return $account;
                }
            );

        $totalAccountBalance =
            $accountBalances->sum(
                fn (FinanceAccount $account) =>
                    (float) $account
                        ->calculated_balance
            );

        /*
        |--------------------------------------------------------------------------
        | Recent Manual Finance Transactions
        |--------------------------------------------------------------------------
        */

        $recentVouchers = FinanceVoucher::query()
            ->with([
                'financeHead',
                'financeAccount',
                'destinationAccount',
            ])
            ->latest('voucher_date')
            ->latest('id')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view('finance.dashboard', [
            'todayReceipts' =>
                $todayReceipts,

            'todayPayments' =>
                $todayPayments,

            'todayNet' =>
                $todayReceipts
                - $todayPayments,

            'monthReceipts' =>
                $monthReceipts,

            'monthPayments' =>
                $monthPayments,

            'monthNet' =>
                $monthReceipts
                - $monthPayments,

            'manualTodayReceipts' =>
                $manualTodayReceipts,

            'billingTodayReceipts' =>
                $billingTodayReceipts,

            'ipTodayAdvanceTotal' =>
                $ipTodayAdvanceTotal,

            'ipTodayPaymentTotal' =>
                $ipTodayPaymentTotal,

            'ipTodayCollectionTotal' =>
                $ipTodayCollectionTotal,

            'mhisTodayReceiptTotal' =>
                $mhisTodayReceiptTotal,

            'manualMonthReceipts' =>
                $manualMonthReceipts,

            'billingMonthReceipts' =>
                $billingMonthReceipts,

            'ipMonthAdvanceTotal' =>
                $ipMonthAdvanceTotal,

            'ipMonthPaymentTotal' =>
                $ipMonthPaymentTotal,

            'ipMonthCollectionTotal' =>
                $ipMonthCollectionTotal,

            'mhisMonthReceiptTotal' =>
                $mhisMonthReceiptTotal,

            'billingMonthRevenueByHead' =>
                collect(
                    $billingMonthRevenueByHead
                )
                    ->sortByDesc('amount')
                    ->values(),

            'activeAccounts' =>
                $activeAccounts,

            'activeIncomeHeads' =>
                $activeIncomeHeads,

            'activeExpenseHeads' =>
                $activeExpenseHeads,

            'accountBalances' =>
                $accountBalances,

            'totalAccountBalance' =>
                $totalAccountBalance,

            'recentVouchers' =>
                $recentVouchers,
        ]);
    }
}