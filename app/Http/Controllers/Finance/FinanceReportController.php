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
        | Inpatient Advances
        |--------------------------------------------------------------------------
        |
        | Advances are actual patient collections.
        | Finalized bill totals are not treated as cash receipts.
        |
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
        |
        | These are actual payments collected against the patient
        | balance after an inpatient bill has been finalized.
        |
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
        |
        | A settled MHIS claim represents recognized MHIS settlement.
        | Actual active receipts reduce the outstanding receivable.
        |
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
             * Active receipts are deliberately not restricted
             * to the report period here.
             *
             * This represents the current outstanding receivable
             * for claims settled within the selected period.
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

                if (!isset($billingRevenueByHead[$headId])) {
                    $billingRevenueByHead[$headId] = 0.0;
                }

                $billingRevenueByHead[$headId] +=
                    (float) $allocation['amount'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | IP / MHIS Income Head Allocation
        |--------------------------------------------------------------------------
        |
        | Both IP advances and finalized IP patient payments are
        | actual inpatient collections.
        |
        | MHIS receipts are actual scheme collections.
        |
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

        $ipIncomeHead = FinanceHead::query()
            ->where('code', 'INC-IPD')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (
            $ipIncomeHead
            && $ipPatientCollectionTotal > 0
        ) {
            $billingRevenueByHead[$ipIncomeHead->id] =
                (
                    $billingRevenueByHead[
                        $ipIncomeHead->id
                    ]
                    ?? 0
                )
                + $ipPatientCollectionTotal;
        }

        $mhisIncomeHead = FinanceHead::query()
            ->where('code', 'INC-MHIS')
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (
            $mhisIncomeHead
            && $mhisPeriodReceiptTotal > 0
        ) {
            $billingRevenueByHead[$mhisIncomeHead->id] =
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
        | This is COLLECTION income for the selected period.
        |
        | It includes:
        | - Manual Finance receipts
        | - OPD / investigation Billing payments
        | - IP advances
        | - Finalized IP patient payments
        | - Actual MHIS receipts
        |
        | MHIS receivable is reported separately and is NOT
        | added again here.
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
                        $billingRevenueByHead[$head->id]
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
        | Billing Collections by Finance Account
        |--------------------------------------------------------------------------
        */

        $billingReceiptsByAccount = [];

        foreach ($billingPayments as $payment) {
            $account =
                $billingFinanceService
                    ->resolveAccount($payment);

            if (!$account) {
                continue;
            }

            if (!isset(
                $billingReceiptsByAccount[$account->id]
            )) {
                $billingReceiptsByAccount[
                    $account->id
                ] = 0.0;
            }

            $billingReceiptsByAccount[$account->id] +=
                (float) $payment->amount;
        }

        /*
        |--------------------------------------------------------------------------
        | IP Cash Collections -> Main Cash
        |--------------------------------------------------------------------------
        |
        | Cash IP advances and cash finalized patient payments
        | are mapped automatically to CASH-MAIN.
        |
        | UPI / card remain unmapped until a specific bank or
        | settlement account is configured.
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
                $billingReceiptsByAccount[
                    $mainCashAccount->id
                ] =
                    (
                        $billingReceiptsByAccount[
                            $mainCashAccount->id
                        ]
                        ?? 0
                    )
                    + $ipCashCollectionTotal;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Account Movements
        |--------------------------------------------------------------------------
        |
        | MHIS receipts are intentionally NOT assigned to an
        | account yet because no MHIS bank account mapping has
        | been configured.
        |
        | UPI / card IP collections are also left unmapped until
        | the appropriate bank account mapping is configured.
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
                    $billingReceiptsByAccount
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
                            $billingReceiptsByAccount[
                                $account->id
                            ]
                            ?? 0
                        );

                    $receipts =
                        $manualReceipts
                        + $integratedReceipts;

                    $payments =
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

                    $account->report_billing_receipts =
                        $integratedReceipts;

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

                'billingPayments' =>
                    $billingPayments,

                'billingCollectionsTotal' =>
                    (float) $billingPayments->sum(
                        fn (Payment $payment) =>
                            (float) $payment->amount
                    ),

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