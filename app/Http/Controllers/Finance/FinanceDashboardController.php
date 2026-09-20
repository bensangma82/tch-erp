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
use App\Models\PharmacyReturn;
use App\Models\PharmacySale;
use App\Services\Finance\BillingFinanceService;
use App\Services\Finance\FinanceHealthService;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function index(
        BillingFinanceService $billingFinanceService,
        FinanceHealthService $financeHealthService
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
        | Direct Pharmacy Sales
        |--------------------------------------------------------------------------
        |
        | IP-billed pharmacy transactions are excluded using both
        | admission_id and payment_mode safeguards.
        |
        */

        $pharmacyTodaySales = PharmacySale::query()
            ->where('status', 'completed')
            ->whereNull('admission_id')
            ->where(function ($query) {
                $query
                    ->whereNull('payment_mode')
                    ->orWhere(
                        'payment_mode',
                        '!=',
                        'ip_billing'
                    );
            })
            ->where('paid_amount', '>', 0)
            ->whereDate('sale_at', $today)
            ->get();

        $pharmacyMonthSales = PharmacySale::query()
            ->where('status', 'completed')
            ->whereNull('admission_id')
            ->where(function ($query) {
                $query
                    ->whereNull('payment_mode')
                    ->orWhere(
                        'payment_mode',
                        '!=',
                        'ip_billing'
                    );
            })
            ->where('paid_amount', '>', 0)
            ->whereYear('sale_at', $year)
            ->whereMonth('sale_at', $month)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Direct Pharmacy Returns
        |--------------------------------------------------------------------------
        */

        $pharmacyTodayReturns = PharmacyReturn::query()
            ->with('sale')
            ->where('status', 'completed')
            ->whereHas('sale', function ($query) {
                $query
                    ->whereNull('admission_id')
                    ->where(function ($query) {
                        $query
                            ->whereNull('payment_mode')
                            ->orWhere(
                                'payment_mode',
                                '!=',
                                'ip_billing'
                            );
                    });
            })
            ->whereDate('returned_at', $today)
            ->get();

        $pharmacyMonthReturns = PharmacyReturn::query()
            ->with('sale')
            ->where('status', 'completed')
            ->whereHas('sale', function ($query) {
                $query
                    ->whereNull('admission_id')
                    ->where(function ($query) {
                        $query
                            ->whereNull('payment_mode')
                            ->orWhere(
                                'payment_mode',
                                '!=',
                                'ip_billing'
                            );
                    });
            })
            ->whereYear('returned_at', $year)
            ->whereMonth('returned_at', $month)
            ->get();

        $pharmacyTodaySalesTotal =
            round(
                (float) $pharmacyTodaySales->sum(
                    fn (PharmacySale $sale) =>
                        (float) $sale->paid_amount
                ),
                2
            );

        $pharmacyTodayRefundTotal =
            round(
                (float) $pharmacyTodayReturns->sum(
                    fn (PharmacyReturn $return) =>
                        (float) $return->refund_amount
                ),
                2
            );

        $pharmacyTodayNetTotal =
            round(
                $pharmacyTodaySalesTotal
                - $pharmacyTodayRefundTotal,
                2
            );

        $pharmacyMonthSalesTotal =
            round(
                (float) $pharmacyMonthSales->sum(
                    fn (PharmacySale $sale) =>
                        (float) $sale->paid_amount
                ),
                2
            );

        $pharmacyMonthRefundTotal =
            round(
                (float) $pharmacyMonthReturns->sum(
                    fn (PharmacyReturn $return) =>
                        (float) $return->refund_amount
                ),
                2
            );

        $pharmacyMonthNetTotal =
            round(
                $pharmacyMonthSalesTotal
                - $pharmacyMonthRefundTotal,
                2
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
            + $pharmacyTodayNetTotal
            + $ipTodayCollectionTotal
            + $mhisTodayReceiptTotal;

        $monthReceipts =
            $manualMonthReceipts
            + $billingMonthReceipts
            + $pharmacyMonthNetTotal
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

                $billingMonthRevenueByHead[
                    $code
                ]['amount'] +=
                    (float) $allocation['amount'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Revenue -> INC-PHARM
        |--------------------------------------------------------------------------
        */

        $pharmacyIncomeHead = FinanceHead::query()
            ->where('code', 'INC-PHARM')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (
            $pharmacyIncomeHead
            && $pharmacyMonthNetTotal != 0
        ) {
            if (
                !isset(
                    $billingMonthRevenueByHead[
                        'INC-PHARM'
                    ]
                )
            ) {
                $billingMonthRevenueByHead[
                    'INC-PHARM'
                ] = [
                    'code' => 'INC-PHARM',
                    'name' => $pharmacyIncomeHead->name,
                    'amount' => 0.0,
                ];
            }

            $billingMonthRevenueByHead[
                'INC-PHARM'
            ]['amount'] +=
                $pharmacyMonthNetTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | IP Revenue -> INC-IPD
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
                    $billingMonthRevenueByHead[
                        'INC-IPD'
                    ]
                )
            ) {
                $billingMonthRevenueByHead[
                    'INC-IPD'
                ] = [
                    'code' => 'INC-IPD',
                    'name' => $ipIncomeHead->name,
                    'amount' => 0.0,
                ];
            }

            $billingMonthRevenueByHead[
                'INC-IPD'
            ]['amount'] +=
                $ipMonthCollectionTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | MHIS Revenue -> INC-MHIS
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
                    $billingMonthRevenueByHead[
                        'INC-MHIS'
                    ]
                )
            ) {
                $billingMonthRevenueByHead[
                    'INC-MHIS'
                ] = [
                    'code' => 'INC-MHIS',
                    'name' => $mhisIncomeHead->name,
                    'amount' => 0.0,
                ];
            }

            $billingMonthRevenueByHead[
                'INC-MHIS'
            ]['amount'] +=
                $mhisMonthReceiptTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | Finance Master Counts
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
        | All Integrated Transactions for Account Balances
        |--------------------------------------------------------------------------
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

        $allPharmacySales = PharmacySale::query()
            ->where('status', 'completed')
            ->whereNull('admission_id')
            ->where(function ($query) {
                $query
                    ->whereNull('payment_mode')
                    ->orWhere(
                        'payment_mode',
                        '!=',
                        'ip_billing'
                    );
            })
            ->where('paid_amount', '>', 0)
            ->get();

        $allPharmacyReturns = PharmacyReturn::query()
            ->with('sale')
            ->where('status', 'completed')
            ->whereHas('sale', function ($query) {
                $query
                    ->whereNull('admission_id')
                    ->where(function ($query) {
                        $query
                            ->whereNull('payment_mode')
                            ->orWhere(
                                'payment_mode',
                                '!=',
                                'ip_billing'
                            );
                    });
            })
            ->get();

        $integratedReceiptsByAccount = [];
        $integratedPaymentsByAccount = [];

        /*
        |--------------------------------------------------------------------------
        | OPD / Investigation Collections by Account
        |--------------------------------------------------------------------------
        */

        foreach ($allBillingPayments as $payment) {
            $account =
                $billingFinanceService
                    ->resolveAccount($payment);

            if (!$account) {
                continue;
            }

            if (
                !isset(
                    $integratedReceiptsByAccount[
                        $account->id
                    ]
                )
            ) {
                $integratedReceiptsByAccount[
                    $account->id
                ] = 0.0;
            }

            $integratedReceiptsByAccount[
                $account->id
            ] +=
                (float) $payment->amount;
        }

        /*
        |--------------------------------------------------------------------------
        | IP Cash Collections -> CASH-MAIN
        |--------------------------------------------------------------------------
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
                                trim(
                                    (string) $advance->payment_mode
                                )
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
                                trim(
                                    (string) $payment->payment_mode
                                )
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
                $integratedReceiptsByAccount[
                    $mainCashAccount->id
                ] =
                    (
                        $integratedReceiptsByAccount[
                            $mainCashAccount->id
                        ]
                        ?? 0
                    )
                    + $allIpCashCollections;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Cash -> CASH-PHARM
        |--------------------------------------------------------------------------
        */

        $pharmacyCashAccount = FinanceAccount::query()
            ->where('code', 'CASH-PHARM')
            ->where('is_active', true)
            ->first();

        if ($pharmacyCashAccount) {
            $allPharmacyCashSales =
                round(
                    (float) $allPharmacySales
                        ->filter(
                            fn (PharmacySale $sale) =>
                                strtolower(
                                    trim(
                                        (string) $sale->payment_mode
                                    )
                                ) === 'cash'
                        )
                        ->sum(
                            fn (PharmacySale $sale) =>
                                (float) $sale->paid_amount
                        ),
                    2
                );

            $allPharmacyCashRefunds =
                round(
                    (float) $allPharmacyReturns
                        ->filter(
                            fn (PharmacyReturn $return) =>
                                strtolower(
                                    trim(
                                        (string) $return->refund_mode
                                    )
                                ) === 'cash'
                        )
                        ->sum(
                            fn (PharmacyReturn $return) =>
                                (float) $return->refund_amount
                        ),
                    2
                );

            if ($allPharmacyCashSales > 0) {
                $integratedReceiptsByAccount[
                    $pharmacyCashAccount->id
                ] =
                    (
                        $integratedReceiptsByAccount[
                            $pharmacyCashAccount->id
                        ]
                        ?? 0
                    )
                    + $allPharmacyCashSales;
            }

            if ($allPharmacyCashRefunds > 0) {
                $integratedPaymentsByAccount[
                    $pharmacyCashAccount->id
                ] =
                    (
                        $integratedPaymentsByAccount[
                            $pharmacyCashAccount->id
                        ]
                        ?? 0
                    )
                    + $allPharmacyCashRefunds;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Finance Account Balances
        |--------------------------------------------------------------------------
        |
        | MHIS receipts are intentionally not assigned to an account
        | until the receiving bank account is explicitly configured.
        |
        | UPI/card transactions are also not guessed.
        |
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
                    $integratedReceiptsByAccount,
                    $integratedPaymentsByAccount
                ) {
                    $openingBalance =
                        (float) $account->opening_balance;

                    $manualReceipts =
                        (float) FinanceVoucher::query()
                            ->where('status', 'posted')
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
                            $integratedReceiptsByAccount[
                                $account->id
                            ]
                            ?? 0
                        );

                    $manualPayments =
                        (float) FinanceVoucher::query()
                            ->where('status', 'posted')
                            ->where(
                                'voucher_type',
                                'payment'
                            )
                            ->where(
                                'finance_account_id',
                                $account->id
                            )
                            ->sum('amount');

                    $integratedPayments =
                        (float) (
                            $integratedPaymentsByAccount[
                                $account->id
                            ]
                            ?? 0
                        );

                    $payments =
                        $manualPayments
                        + $integratedPayments;

                    $transfersOut =
                        (float) FinanceVoucher::query()
                            ->where('status', 'posted')
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
                            ->where('status', 'posted')
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

                    /*
                     * Preserve the property already used by the Blade.
                     */
                    $account->billing_receipt_total =
                        $integratedReceipts;

                    $account->integrated_receipt_total =
                        $integratedReceipts;

                    $account->manual_payment_total =
                        $manualPayments;

                    $account->integrated_payment_total =
                        $integratedPayments;

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
                    (float) $account->calculated_balance
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
        | Financial Health
        |--------------------------------------------------------------------------
        */

        $liquidity =
            $financeHealthService->liquidity();

        $breakEven =
            $financeHealthService->breakEven();

        $financialRisk =
            $financeHealthService->financialRisk();

        $dashboardCharts =
            $financeHealthService->dashboardCharts();

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

            'pharmacyTodaySalesTotal' =>
                $pharmacyTodaySalesTotal,

            'pharmacyTodayRefundTotal' =>
                $pharmacyTodayRefundTotal,

            'pharmacyTodayNetTotal' =>
                $pharmacyTodayNetTotal,

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

            'pharmacyMonthSalesTotal' =>
                $pharmacyMonthSalesTotal,

            'pharmacyMonthRefundTotal' =>
                $pharmacyMonthRefundTotal,

            'pharmacyMonthNetTotal' =>
                $pharmacyMonthNetTotal,

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

            'liquidity' =>
                $liquidity,

            'breakEven' =>
                $breakEven,

            'financialRisk' =>
                $financialRisk,

            'dashboardCharts' =>
                $dashboardCharts,
        ]);
    }
}