<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\IpBillingAdvance;
use App\Models\IpBillingMhisClaim;
use App\Models\IpBillingMhisReceipt;
use App\Models\IpBillingPayment;
use App\Models\Payment;
use App\Models\PharmacyReturn;
use App\Models\PharmacySale;
use App\Services\Finance\BillingFinanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceReportController extends Controller
{
    public function index(
        Request $request,
        BillingFinanceService $billingFinanceService
    ): View {
        /*
        |--------------------------------------------------------------------------
        | Report Period
        |--------------------------------------------------------------------------
        */

        $dateFrom = $request->input(
            'date_from',
            now()->startOfMonth()->toDateString()
        );

        $dateTo = $request->input(
            'date_to',
            now()->toDateString()
        );

        /*
        |--------------------------------------------------------------------------
        | OPD / Investigation Billing Collections
        |--------------------------------------------------------------------------
        */

        $billingPayments = Payment::query()
            ->with([
                'invoice.items.service',
            ])
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->orderBy('payment_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Direct Pharmacy Sales
        |--------------------------------------------------------------------------
        |
        | Only direct pharmacy sales are included here.
        |
        | Pharmacy items issued through IP Billing are excluded because their
        | revenue is already recognized through the inpatient billing flow.
        |
        */

        $pharmacySales = PharmacySale::query()
            ->where('status', 'completed')
            ->whereNull('admission_id')
            ->where('paid_amount', '>', 0)
            ->whereDate('sale_at', '>=', $dateFrom)
            ->whereDate('sale_at', '<=', $dateTo)
            ->orderBy('sale_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Direct Pharmacy Returns
        |--------------------------------------------------------------------------
        |
        | Only returns belonging to direct pharmacy sales are included.
        |
        | This deliberately excludes old returns against IP-billed pharmacy
        | issues even if those historical records contain refund_mode = cash.
        |
        */

        $pharmacyReturns = PharmacyReturn::query()
    ->with('sale')
    ->where('status', 'completed')
    ->whereHas('sale', function ($query) {
        $query
            ->whereNull('admission_id')
            ->where(function ($query) {
                $query
                    ->whereNull('payment_mode')
                    ->orWhere('payment_mode', '!=', 'ip_billing');
            });
    })
            ->whereDate('returned_at', '>=', $dateFrom)
            ->whereDate('returned_at', '<=', $dateTo)
            ->orderBy('returned_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Inpatient Advances
        |--------------------------------------------------------------------------
        */

        $ipAdvances = IpBillingAdvance::query()
            ->where('status', 'active')
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->orderBy('payment_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Finalized IP Patient Payments
        |--------------------------------------------------------------------------
        */

        $ipPayments = IpBillingPayment::query()
            ->where('status', 'active')
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->orderBy('payment_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | MHIS Actual Receipts
        |--------------------------------------------------------------------------
        */

        $mhisReceipts = IpBillingMhisReceipt::query()
            ->with([
                'mhisClaim',
                'billingAccount',
                'patient',
            ])
            ->where('status', 'active')
            ->whereDate('receipt_date', '>=', $dateFrom)
            ->whereDate('receipt_date', '<=', $dateTo)
            ->orderBy('receipt_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | MHIS Claims / Receivables
        |--------------------------------------------------------------------------
        */

        $mhisClaims = IpBillingMhisClaim::query()
            ->with([
                'receipts' => function ($query) {
                    $query->where('status', 'active');
                },
                'billingAccount',
                'patient',
            ])
            ->where('status', 'settled')
            ->whereDate('settlement_date', '>=', $dateFrom)
            ->whereDate('settlement_date', '<=', $dateTo)
            ->orderBy('settlement_date')
            ->get();

        $mhisSettledTotal = 0.0;
        $mhisReceivedTotal = 0.0;
        $mhisReceivableTotal = 0.0;

        foreach ($mhisClaims as $claim) {
            $settledAmount = round(
                (float) $claim->settlement_amount,
                2
            );

            /*
             * Active receipts are deliberately not restricted to the
             * report period here.
             *
             * This gives the current outstanding receivable for claims
             * settled during the selected period.
             */
            $receivedAmount = round(
                (float) $claim->receipts->sum('amount'),
                2
            );

            $receivableAmount = max(
                0,
                round(
                    $settledAmount - $receivedAmount,
                    2
                )
            );

            $claim->report_settled_amount =
                $settledAmount;

            $claim->report_received_amount =
                $receivedAmount;

            $claim->report_receivable_amount =
                $receivableAmount;

            $mhisSettledTotal +=
                $settledAmount;

            $mhisReceivedTotal +=
                $receivedAmount;

            $mhisReceivableTotal +=
                $receivableAmount;
        }

        /*
        |--------------------------------------------------------------------------
        | Billing Revenue Allocation
        |--------------------------------------------------------------------------
        */

        $billingRevenueByHead = [];

        foreach ($billingPayments as $payment) {
            $allocations =
                $billingFinanceService
                    ->allocateRevenue($payment);

            foreach ($allocations as $allocation) {
                $headId =
                    (int) $allocation['finance_head_id'];

                if (!isset(
                    $billingRevenueByHead[$headId]
                )) {
                    $billingRevenueByHead[$headId] =
                        0.0;
                }

                $billingRevenueByHead[$headId] +=
                    (float) $allocation['amount'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Collection Totals
        |--------------------------------------------------------------------------
        |
        | Collection-basis pharmacy income:
        |
        | direct payments received
        | minus
        | direct-sale refunds issued
        |
        */

        $pharmacySalesTotal =
            round(
                (float) $pharmacySales->sum(
                    fn (PharmacySale $sale) =>
                        (float) $sale->paid_amount
                ),
                2
            );

        $pharmacyRefundTotal =
            round(
                (float) $pharmacyReturns->sum(
                    fn (PharmacyReturn $return) =>
                        (float) $return->refund_amount
                ),
                2
            );

        $pharmacyNetCollectionTotal =
            round(
                $pharmacySalesTotal
                - $pharmacyRefundTotal,
                2
            );

        /*
        |--------------------------------------------------------------------------
        | IP / MHIS Collection Totals
        |--------------------------------------------------------------------------
        */

        $ipAdvanceTotal =
            (float) $ipAdvances->sum(
                fn (IpBillingAdvance $advance) =>
                    (float) $advance->amount
            );

        $ipPaymentTotal =
            (float) $ipPayments->sum(
                fn (IpBillingPayment $payment) =>
                    (float) $payment->amount
            );

        $ipPatientCollectionTotal =
            $ipAdvanceTotal
            + $ipPaymentTotal;

        $mhisPeriodReceiptTotal =
            (float) $mhisReceipts->sum(
                fn (IpBillingMhisReceipt $receipt) =>
                    (float) $receipt->amount
            );

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Income Head Allocation
        |--------------------------------------------------------------------------
        */

        $pharmacyIncomeHead = FinanceHead::query()
            ->where('code', 'INC-PHARM')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (
            $pharmacyIncomeHead
            && $pharmacyNetCollectionTotal != 0
        ) {
            $billingRevenueByHead[
                $pharmacyIncomeHead->id
            ] =
                (
                    $billingRevenueByHead[
                        $pharmacyIncomeHead->id
                    ]
                    ?? 0
                )
                + $pharmacyNetCollectionTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | IP Income Head Allocation
        |--------------------------------------------------------------------------
        */

        $ipIncomeHead = FinanceHead::query()
            ->where('code', 'INC-IPD')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (
            $ipIncomeHead
            && $ipPatientCollectionTotal > 0
        ) {
            $billingRevenueByHead[
                $ipIncomeHead->id
            ] =
                (
                    $billingRevenueByHead[
                        $ipIncomeHead->id
                    ]
                    ?? 0
                )
                + $ipPatientCollectionTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | MHIS Income Head Allocation
        |--------------------------------------------------------------------------
        */

        $mhisIncomeHead = FinanceHead::query()
            ->where('code', 'INC-MHIS')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (
            $mhisIncomeHead
            && $mhisPeriodReceiptTotal > 0
        ) {
            $billingRevenueByHead[
                $mhisIncomeHead->id
            ] =
                (
                    $billingRevenueByHead[
                        $mhisIncomeHead->id
                    ]
                    ?? 0
                )
                + $mhisPeriodReceiptTotal;
        }

        /*
        |--------------------------------------------------------------------------
        | Income by Head
        |--------------------------------------------------------------------------
        |
        | This is collection income for the selected period.
        |
        | Includes:
        | - Manual Finance receipts
        | - OPD / investigation collections
        | - Direct Pharmacy net collections
        | - IP advances
        | - Finalized IP patient payments
        | - Actual MHIS receipts
        |
        | MHIS receivable is reported separately and is not added here.
        |
        */

        $incomeHeads = FinanceHead::query()
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->withSum([
                'vouchers as manual_report_total' =>
                    function ($query) use (
                        $dateFrom,
                        $dateTo
                    ) {
                        $query
                            ->where('status', 'posted')
                            ->where(
                                'voucher_type',
                                'receipt'
                            )
                            ->whereBetween(
                                'voucher_date',
                                [
                                    $dateFrom,
                                    $dateTo,
                                ]
                            );
                    },
            ], 'amount')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function (
                FinanceHead $head
            ) use (
                $billingRevenueByHead
            ) {
                $manualTotal =
                    (float) (
                        $head->manual_report_total
                        ?? 0
                    );

                $integratedTotal =
                    (float) (
                        $billingRevenueByHead[
                            $head->id
                        ]
                        ?? 0
                    );

                $head->manual_report_total =
                    $manualTotal;

                $head->billing_report_total =
                    $integratedTotal;

                $head->report_total =
                    $manualTotal
                    + $integratedTotal;

                return $head;
            });

        $totalIncome =
            (float) $incomeHeads->sum(
                fn (FinanceHead $head) =>
                    (float) $head->report_total
            );

        /*
        |--------------------------------------------------------------------------
        | Expenses by Head
        |--------------------------------------------------------------------------
        |
        | Pharmacy refunds are NOT recorded here as an expense.
        | They already reduce Pharmacy income above.
        |
        */

        $expenseHeads = FinanceHead::query()
            ->where('head_type', 'expense')
            ->where('is_active', true)
            ->withSum([
                'vouchers as report_total' =>
                    function ($query) use (
                        $dateFrom,
                        $dateTo
                    ) {
                        $query
                            ->where('status', 'posted')
                            ->where(
                                'voucher_type',
                                'payment'
                            )
                            ->whereBetween(
                                'voucher_date',
                                [
                                    $dateFrom,
                                    $dateTo,
                                ]
                            );
                    },
            ], 'amount')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $totalExpenses =
            (float) $expenseHeads->sum(
                fn (FinanceHead $head) =>
                    (float) (
                        $head->report_total
                        ?? 0
                    )
            );

        /*
        |--------------------------------------------------------------------------
        | Net Collection Surplus / Deficit
        |--------------------------------------------------------------------------
        */

        $netSurplus =
            $totalIncome
            - $totalExpenses;

        /*
        |--------------------------------------------------------------------------
        | Integrated Account Receipts / Payments
        |--------------------------------------------------------------------------
        */

        $integratedReceiptsByAccount = [];
        $integratedPaymentsByAccount = [];

        /*
        |--------------------------------------------------------------------------
        | OPD / Investigation Collections by Finance Account
        |--------------------------------------------------------------------------
        */

        foreach ($billingPayments as $payment) {
            $account =
                $billingFinanceService
                    ->resolveAccount($payment);

            if (!$account) {
                continue;
            }

            if (!isset(
                $integratedReceiptsByAccount[
                    $account->id
                ]
            )) {
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
        | IP Cash Collections -> Main Cash
        |--------------------------------------------------------------------------
        |
        | Cash IP advances and cash finalized patient payments are mapped
        | automatically to CASH-MAIN.
        |
        | UPI / card remain unmapped until a specific settlement account
        | is configured.
        |
        */

        $mainCashAccount = FinanceAccount::query()
            ->where('code', 'CASH-MAIN')
            ->where('is_active', true)
            ->first();

        if ($mainCashAccount) {
            $ipCashAdvanceTotal =
                (float) $ipAdvances
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

            $ipCashPaymentTotal =
                (float) $ipPayments
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

            $ipCashCollectionTotal =
                $ipCashAdvanceTotal
                + $ipCashPaymentTotal;

            if ($ipCashCollectionTotal > 0) {
                $integratedReceiptsByAccount[
                    $mainCashAccount->id
                ] =
                    (
                        $integratedReceiptsByAccount[
                            $mainCashAccount->id
                        ]
                        ?? 0
                    )
                    + $ipCashCollectionTotal;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Direct Pharmacy Cash -> Pharmacy Cash
        |--------------------------------------------------------------------------
        |
        | Only CASH transactions are mapped automatically.
        |
        | Direct cash sales increase CASH-PHARM.
        | Direct cash refunds decrease CASH-PHARM.
        |
        | UPI / card transactions remain unmapped until their settlement
        | account is explicitly configured.
        |
        */

        $pharmacyCashAccount = FinanceAccount::query()
            ->where('code', 'CASH-PHARM')
            ->where('is_active', true)
            ->first();

        $pharmacyCashSalesTotal = 0.0;
        $pharmacyCashRefundTotal = 0.0;

        if ($pharmacyCashAccount) {
            $pharmacyCashSalesTotal =
                round(
                    (float) $pharmacySales
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

            $pharmacyCashRefundTotal =
                round(
                    (float) $pharmacyReturns
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

            if ($pharmacyCashSalesTotal > 0) {
                $integratedReceiptsByAccount[
                    $pharmacyCashAccount->id
                ] =
                    (
                        $integratedReceiptsByAccount[
                            $pharmacyCashAccount->id
                        ]
                        ?? 0
                    )
                    + $pharmacyCashSalesTotal;
            }

            if ($pharmacyCashRefundTotal > 0) {
                $integratedPaymentsByAccount[
                    $pharmacyCashAccount->id
                ] =
                    (
                        $integratedPaymentsByAccount[
                            $pharmacyCashAccount->id
                        ]
                        ?? 0
                    )
                    + $pharmacyCashRefundTotal;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Account Movements
        |--------------------------------------------------------------------------
        |
        | MHIS receipts are intentionally not assigned to an account yet
        | because no MHIS bank account mapping has been configured.
        |
        | UPI / card IP and Pharmacy collections are also left unmapped.
        |
        */

        $accountMovements = FinanceAccount::query()
            ->where('is_active', true)
            ->orderBy('account_type')
            ->orderBy('name')
            ->get()
            ->map(
                function (
                    FinanceAccount $account
                ) use (
                    $dateFrom,
                    $dateTo,
                    $integratedReceiptsByAccount,
                    $integratedPaymentsByAccount
                ) {
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
                            ->whereBetween(
                                'voucher_date',
                                [
                                    $dateFrom,
                                    $dateTo,
                                ]
                            )
                            ->sum('amount');

                    $integratedReceipts =
                        (float) (
                            $integratedReceiptsByAccount[
                                $account->id
                            ]
                            ?? 0
                        );

                    $receipts =
                        $manualReceipts
                        + $integratedReceipts;

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
                            ->whereBetween(
                                'voucher_date',
                                [
                                    $dateFrom,
                                    $dateTo,
                                ]
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
                            ->whereBetween(
                                'voucher_date',
                                [
                                    $dateFrom,
                                    $dateTo,
                                ]
                            )
                            ->sum('amount');

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
                            ->whereBetween(
                                'voucher_date',
                                [
                                    $dateFrom,
                                    $dateTo,
                                ]
                            )
                            ->sum('amount');

                    $account->report_receipts =
                        $receipts;

                    $account->report_manual_receipts =
                        $manualReceipts;

                    /*
                     * Keep the old property name because the existing
                     * Finance report Blade already uses it.
                     */
                    $account->report_billing_receipts =
                        $integratedReceipts;

                    $account->report_integrated_receipts =
                        $integratedReceipts;

                    $account->report_manual_payments =
                        $manualPayments;

                    $account->report_integrated_payments =
                        $integratedPayments;

                    $account->report_payments =
                        $payments;

                    $account->report_transfers_in =
                        $transfersIn;

                    $account->report_transfers_out =
                        $transfersOut;

                    $account->report_net_movement =
                        $receipts
                        - $payments
                        + $transfersIn
                        - $transfersOut;

                    return $account;
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Posted Manual Finance Transactions
        |--------------------------------------------------------------------------
        */

        $transactions = FinanceVoucher::query()
            ->with([
                'financeHead',
                'financeAccount',
                'destinationAccount',
            ])
            ->where('status', 'posted')
            ->whereBetween(
                'voucher_date',
                [
                    $dateFrom,
                    $dateTo,
                ]
            )
            ->latest('voucher_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'finance.reports.index',
            [
                'dateFrom' =>
                    $dateFrom,

                'dateTo' =>
                    $dateTo,

                'incomeHeads' =>
                    $incomeHeads,

                'expenseHeads' =>
                    $expenseHeads,

                'totalIncome' =>
                    $totalIncome,

                'totalExpenses' =>
                    $totalExpenses,

                'netSurplus' =>
                    $netSurplus,

                'accountMovements' =>
                    $accountMovements,

                'transactions' =>
                    $transactions,

                /*
                |--------------------------------------------------------------------------
                | OPD / Investigation
                |--------------------------------------------------------------------------
                */

                'billingPayments' =>
                    $billingPayments,

                'billingCollectionsTotal' =>
                    (float) $billingPayments->sum(
                        fn (Payment $payment) =>
                            (float) $payment->amount
                    ),

                /*
                |--------------------------------------------------------------------------
                | Pharmacy
                |--------------------------------------------------------------------------
                */

                'pharmacySales' =>
                    $pharmacySales,

                'pharmacyReturns' =>
                    $pharmacyReturns,

                'pharmacySalesTotal' =>
                    $pharmacySalesTotal,

                'pharmacyRefundTotal' =>
                    $pharmacyRefundTotal,

                'pharmacyNetCollectionTotal' =>
                    $pharmacyNetCollectionTotal,

                'pharmacyCashSalesTotal' =>
                    $pharmacyCashSalesTotal,

                'pharmacyCashRefundTotal' =>
                    $pharmacyCashRefundTotal,

                /*
                |--------------------------------------------------------------------------
                | Inpatient
                |--------------------------------------------------------------------------
                */

                'ipAdvances' =>
                    $ipAdvances,

                'ipAdvanceTotal' =>
                    $ipAdvanceTotal,

                'ipPayments' =>
                    $ipPayments,

                'ipPaymentTotal' =>
                    $ipPaymentTotal,

                'ipPatientCollectionTotal' =>
                    $ipPatientCollectionTotal,

                /*
                |--------------------------------------------------------------------------
                | MHIS
                |--------------------------------------------------------------------------
                */

                'mhisReceipts' =>
                    $mhisReceipts,

                'mhisPeriodReceiptTotal' =>
                    $mhisPeriodReceiptTotal,

                'mhisClaims' =>
                    $mhisClaims,

                'mhisSettledTotal' =>
                    $mhisSettledTotal,

                'mhisReceivedTotal' =>
                    $mhisReceivedTotal,

                'mhisReceivableTotal' =>
                    $mhisReceivableTotal,
            ]
        );
    }
}