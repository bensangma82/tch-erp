<?php

namespace App\Services\Finance;

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
use App\Models\PharmacySupplierPayment;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacySupplierPayable;

class FinanceHealthService
{
    public function __construct(
        private BillingFinanceService $billingFinanceService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Liquidity
    |--------------------------------------------------------------------------
    */

    public function liquidity(): array
    {
        $cashAndBank =
            $this->cashAndBankBalance();

        $pharmacyInventory =
            $this->pharmacyInventoryAtCost();

        $mhisReceivables =
            $this->mhisReceivables();

        $supplierPayables =
            $this->supplierPayables();

        /*
         * Current Assets
         *
         * Cash / Bank
         * + Pharmacy Inventory
         * + MHIS Receivables
         */
        $currentAssets =
            $cashAndBank
            + $pharmacyInventory
            + $mhisReceivables;

        /*
         * Quick Assets
         *
         * Inventory is excluded.
         */
        $quickAssets =
            $cashAndBank
            + $mhisReceivables;

        /*
         * Current Liabilities
         *
         * At present, supplier payables are the current
         * liability source available in the ERP.
         *
         * Additional liabilities can be added here as
         * those modules are introduced.
         */
        $currentLiabilities =
            $supplierPayables;

        /*
         * Never manufacture an infinite ratio.
         *
         * If there are no current liabilities,
         * the ratio is returned as null.
         */
        $currentRatio =
            $currentLiabilities > 0
                ? round(
                    $currentAssets
                    / $currentLiabilities,
                    2
                )
                : null;

        $quickRatio =
            $currentLiabilities > 0
                ? round(
                    $quickAssets
                    / $currentLiabilities,
                    2
                )
                : null;

        return [
            'cash_and_bank' =>
                round($cashAndBank, 2),

            'pharmacy_inventory' =>
                round($pharmacyInventory, 2),

            'mhis_receivables' =>
                round($mhisReceivables, 2),

            'current_assets' =>
                round($currentAssets, 2),

            'quick_assets' =>
                round($quickAssets, 2),

            'supplier_payables' =>
                round($supplierPayables, 2),

            'current_liabilities' =>
                round($currentLiabilities, 2),

            'current_ratio' =>
                $currentRatio,

            'quick_ratio' =>
                $quickRatio,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Cash and Bank Balance
    |--------------------------------------------------------------------------
    |
    | Uses the same accounting logic as the Finance Dashboard:
    |
    | Opening Balance
    | + Manual Posted Receipts
    | + Integrated Receipts
    | - Manual Posted Payments
    | - Integrated Payments / Refunds
    | - Transfers Out
    | + Transfers In
    |
    */

    private function cashAndBankBalance(): float
    {
        /*
         * OPD / Investigation collections.
         */
        $allBillingPayments = Payment::query()
            ->with([
                'invoice.items.service',
            ])
            ->get();

        /*
         * IP collections.
         */
        $allIpAdvances = IpBillingAdvance::query()
            ->where('status', 'active')
            ->get();

        $allIpPayments = IpBillingPayment::query()
            ->where('status', 'active')
            ->get();

        /*
         * Direct Pharmacy sales.
         *
         * IP-billed pharmacy transactions are excluded.
         */
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

        /*
         * Direct Pharmacy returns.
         */
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



            /*
 * Pharmacy supplier payments.
 *
 * These are account outflows only. They are not treated here
 * as operating expenses because settling a supplier payable
 * is separate from recognising inventory expense.
 */
$allPharmacySupplierPayments =
    PharmacySupplierPayment::query()
        ->whereNotNull('finance_account_id')
        ->get();

        $integratedReceiptsByAccount = [];
        $integratedPaymentsByAccount = [];

        /*
        |--------------------------------------------------------------------------
        | OPD / Investigation Collections by Finance Account
        |--------------------------------------------------------------------------
        */

        foreach ($allBillingPayments as $payment) {
            $account =
                $this->billingFinanceService
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
        | IP Cash Collections -> Main Cash
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
                                    (string)
                                    $advance->payment_mode
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
                                    (string)
                                    $payment->payment_mode
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
        | Pharmacy Cash
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
                                        (string)
                                        $sale->payment_mode
                                    )
                                ) === 'cash'
                        )
                        ->sum(
                            fn (PharmacySale $sale) =>
                                (float)
                                $sale->paid_amount
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
                                        (string)
                                        $return->refund_mode
                                    )
                                ) === 'cash'
                        )
                        ->sum(
                            fn (PharmacyReturn $return) =>
                                (float)
                                $return->refund_amount
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
| Pharmacy Supplier Payments by Finance Account
|--------------------------------------------------------------------------
|
| Supplier payments reduce the exact Finance Account selected
| when the payment was recorded.
|
*/

foreach ($allPharmacySupplierPayments as $supplierPayment) {

    $accountId =
        (int) $supplierPayment->finance_account_id;

    if ($accountId <= 0) {
        continue;
    }

    $integratedPaymentsByAccount[$accountId] =
        (
            $integratedPaymentsByAccount[$accountId]
            ?? 0
        )
        + (float) $supplierPayment->amount;
}
        /*
         * MHIS receipts are intentionally NOT assigned
         * to a Finance Account until the receiving bank
         * account is configured.
         *
         * UPI/card transactions are likewise not guessed.
         */

        $accounts = FinanceAccount::query()
            ->where('is_active', true)
            ->get();

        $totalBalance = 0.0;

        foreach ($accounts as $account) {
            $openingBalance =
                (float) $account->opening_balance;

            $manualReceipts =
                (float) FinanceVoucher::query()
                    ->where('status', 'posted')
                    ->where('voucher_type', 'receipt')
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
                    ->where('voucher_type', 'payment')
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

            $transfersOut =
                (float) FinanceVoucher::query()
                    ->where('status', 'posted')
                    ->where('voucher_type', 'transfer')
                    ->where(
                        'finance_account_id',
                        $account->id
                    )
                    ->sum('amount');

            $transfersIn =
                (float) FinanceVoucher::query()
                    ->where('status', 'posted')
                    ->where('voucher_type', 'transfer')
                    ->where(
                        'destination_account_id',
                        $account->id
                    )
                    ->sum('amount');

            $balance =
                $openingBalance
                + $manualReceipts
                + $integratedReceipts
                - $manualPayments
                - $integratedPayments
                - $transfersOut
                + $transfersIn;

            $totalBalance += $balance;
        }

        return round(
            $totalBalance,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Pharmacy Inventory at Cost
    |--------------------------------------------------------------------------
    |
    | quantity_available is maintained in BASE UNITS.
    |
    | New GRNs now store purchase_price on the stock batch as
    | effective acquisition cost PER BASE UNIT.
    |
    | Historical dummy test batches created before that correction
    | may therefore produce an inaccurate test inventory valuation.
    |
    */

    private function pharmacyInventoryAtCost(): float
    {
        $inventoryValue =
            PharmacyStockBatch::query()
                ->where('quantity_available', '>', 0)
                ->get()
                ->sum(
                    function (
                        PharmacyStockBatch $batch
                    ) {
                        return
                            (float)
                            $batch->quantity_available
                            *
                            (float)
                            $batch->purchase_price;
                    }
                );

        return round(
            (float) $inventoryValue,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MHIS Receivables
    |--------------------------------------------------------------------------
    |
    | Receivable =
    | Approved Claim Amount
    | - Actual Active MHIS Receipts
    |
    | Negative balances are never allowed.
    |
    */

    private function mhisReceivables(): float
    {
        $claims = IpBillingMhisClaim::query()
            ->with([
                'receipts' => function ($query) {
                    $query->where(
                        'status',
                        'active'
                    );
                },
            ])
            ->get();

        $receivable = 0.0;

        foreach ($claims as $claim) {
            $approvedAmount =
                (float) $claim->approved_amount;

            if ($approvedAmount <= 0) {
                continue;
            }

            $receivedAmount =
                (float) $claim->receipts
                    ->sum(
                        fn (
                            IpBillingMhisReceipt $receipt
                        ) =>
                            (float) $receipt->amount
                    );

            $outstanding =
                max(
                    0,
                    $approvedAmount
                    - $receivedAmount
                );

            $receivable += $outstanding;
        }

        return round(
            $receivable,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Supplier Payables
    |--------------------------------------------------------------------------
    */

    private function supplierPayables(): float
    {
        $amount =
            (float) PharmacySupplierPayable::query()
                ->where(
                    'outstanding_amount',
                    '>',
                    0
                )
                ->sum('outstanding_amount');

        return round(
            $amount,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Month-to-Date Integrated Revenue
    |--------------------------------------------------------------------------
    */

    private function monthToDateRevenue(): float
    {
        $year = now()->year;
        $month = now()->month;

        /*
         * Manual Finance receipts.
         */
        $manualReceipts =
            (float) FinanceVoucher::query()
                ->where('status', 'posted')
                ->where('voucher_type', 'receipt')
                ->whereYear(
                    'voucher_date',
                    $year
                )
                ->whereMonth(
                    'voucher_date',
                    $month
                )
                ->sum('amount');

        /*
         * OPD / Investigation collections.
         */
        $billingReceipts =
            (float) Payment::query()
                ->whereYear(
                    'payment_date',
                    $year
                )
                ->whereMonth(
                    'payment_date',
                    $month
                )
                ->sum('amount');

        /*
         * Direct Pharmacy sales.
         */
        $pharmacySales =
            (float) PharmacySale::query()
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
                ->whereYear(
                    'sale_at',
                    $year
                )
                ->whereMonth(
                    'sale_at',
                    $month
                )
                ->sum('paid_amount');

        /*
         * Direct Pharmacy refunds.
         */
        $pharmacyReturns =
            (float) PharmacyReturn::query()
                ->where('status', 'completed')
                ->whereHas(
                    'sale',
                    function ($query) {
                        $query
                            ->whereNull(
                                'admission_id'
                            )
                            ->where(
                                function ($query) {
                                    $query
                                        ->whereNull(
                                            'payment_mode'
                                        )
                                        ->orWhere(
                                            'payment_mode',
                                            '!=',
                                            'ip_billing'
                                        );
                                }
                            );
                    }
                )
                ->whereYear(
                    'returned_at',
                    $year
                )
                ->whereMonth(
                    'returned_at',
                    $month
                )
                ->sum('refund_amount');

        $pharmacyNet =
            $pharmacySales
            - $pharmacyReturns;

        /*
         * IP advances.
         */
        $ipAdvances =
            (float) IpBillingAdvance::query()
                ->where('status', 'active')
                ->whereYear(
                    'payment_date',
                    $year
                )
                ->whereMonth(
                    'payment_date',
                    $month
                )
                ->sum('amount');

        /*
         * Finalized IP patient payments.
         */
        $ipPayments =
            (float) IpBillingPayment::query()
                ->where('status', 'active')
                ->whereYear(
                    'payment_date',
                    $year
                )
                ->whereMonth(
                    'payment_date',
                    $month
                )
                ->sum('amount');

        /*
         * Actual MHIS receipts.
         */
        $mhisReceipts =
            (float) IpBillingMhisReceipt::query()
                ->where('status', 'active')
                ->whereYear(
                    'receipt_date',
                    $year
                )
                ->whereMonth(
                    'receipt_date',
                    $month
                )
                ->sum('amount');

        return round(
            $manualReceipts
            + $billingReceipts
            + $pharmacyNet
            + $ipAdvances
            + $ipPayments
            + $mhisReceipts,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Break-even Analysis
    |--------------------------------------------------------------------------
    */

    public function breakEven(): array
    {
        $year = now()->year;
        $month = now()->month;

        /*
        |--------------------------------------------------------------------------
        | Expense Head Configuration
        |--------------------------------------------------------------------------
        */

        $expenseHeads = FinanceHead::query()
            ->where(
                'head_type',
                'expense'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'include_in_break_even',
                true
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Validate Expense Classification
        |--------------------------------------------------------------------------
        */

        $unclassifiedHeads =
            $expenseHeads
                ->filter(
                    function (
                        FinanceHead $head
                    ) {
                        if (
                            !$head->cost_behavior
                        ) {
                            return true;
                        }

                        if (
                            $head->cost_behavior
                                === 'mixed'
                            &&
                            $head->variable_percentage
                                === null
                        ) {
                            return true;
                        }

                        return false;
                    }
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Current Month Operating Expenses
        |--------------------------------------------------------------------------
        */

        $expenseVouchers =
            FinanceVoucher::query()
                ->with('financeHead')
                ->where(
                    'status',
                    'posted'
                )
                ->where(
                    'voucher_type',
                    'payment'
                )
                ->whereYear(
                    'voucher_date',
                    $year
                )
                ->whereMonth(
                    'voucher_date',
                    $month
                )
                ->whereHas(
                    'financeHead',
                    function ($query) {
                        $query
                            ->where(
                                'head_type',
                                'expense'
                            )
                            ->where(
                                'include_in_break_even',
                                true
                            );
                    }
                )
                ->get();

        $totalExpenses = 0.0;
        $fixedCosts = 0.0;
        $variableCosts = 0.0;

        /*
        |--------------------------------------------------------------------------
        | Fixed / Variable Cost Split
        |--------------------------------------------------------------------------
        */

        foreach (
            $expenseVouchers
            as $voucher
        ) {
            $head =
                $voucher->financeHead;

            if (!$head) {
                continue;
            }

            $amount =
                (float) $voucher->amount;

            $totalExpenses +=
                $amount;

            /*
             * Fixed.
             */
            if (
                $head->cost_behavior
                    === 'fixed'
            ) {
                $fixedCosts +=
                    $amount;

                continue;
            }

            /*
             * Variable.
             */
            if (
                $head->cost_behavior
                    === 'variable'
            ) {
                $variableCosts +=
                    $amount;

                continue;
            }

            /*
             * Mixed.
             */
            if (
                $head->cost_behavior
                    === 'mixed'
            ) {
                $variablePercentage =
                    (float)
                    $head->variable_percentage;

                $variablePart =
                    $amount
                    *
                    $variablePercentage
                    / 100;

                $fixedPart =
                    $amount
                    - $variablePart;

                $variableCosts +=
                    $variablePart;

                $fixedCosts +=
                    $fixedPart;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Integrated Month-to-Date Revenue
        |--------------------------------------------------------------------------
        */

        $monthRevenue =
            $this->monthToDateRevenue();

        /*
        |--------------------------------------------------------------------------
        | Readiness
        |--------------------------------------------------------------------------
        */

        $ready =
            $expenseVouchers->isNotEmpty()
            &&
            $unclassifiedHeads->isEmpty();

        $contributionMargin = null;
        $contributionMarginRatio = null;
        $breakEvenRevenue = null;
        $marginOfSafety = null;
        $marginOfSafetyPercentage = null;

        /*
        |--------------------------------------------------------------------------
        | Break-even Calculation
        |--------------------------------------------------------------------------
        */

        if (
            $ready
            && $monthRevenue > 0
        ) {
            $contributionMargin =
                $monthRevenue
                - $variableCosts;

            if (
                $contributionMargin > 0
            ) {
                $contributionMarginRatio =
                    $contributionMargin
                    / $monthRevenue;

                if (
                    $contributionMarginRatio
                    > 0
                ) {
                    $breakEvenRevenue =
                        $fixedCosts
                        /
                        $contributionMarginRatio;

                    $marginOfSafety =
                        $monthRevenue
                        - $breakEvenRevenue;

                    $marginOfSafetyPercentage =
                        (
                            $marginOfSafety
                            / $monthRevenue
                        )
                        * 100;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if (
            !$unclassifiedHeads->isEmpty()
        ) {
            $status =
                'configuration_required';
        } elseif (
            $expenseVouchers->isEmpty()
        ) {
            $status =
                'insufficient_expense_data';
        } elseif (
            $monthRevenue <= 0
        ) {
            $status =
                'insufficient_revenue_data';
        } elseif (
            $contributionMargin === null
            || $contributionMargin <= 0
        ) {
            $status =
                'negative_contribution_margin';
        } else {
            $status = 'ready';
        }

        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        return [
            'ready' =>
                $ready
                && $monthRevenue > 0,

            'status' =>
                $status,

            'period' =>
                now()->format('F Y')
                . ' MTD',

            'revenue' =>
                round(
                    $monthRevenue,
                    2
                ),

            'total_expenses' =>
                round(
                    $totalExpenses,
                    2
                ),

            'fixed_costs' =>
                round(
                    $fixedCosts,
                    2
                ),

            'variable_costs' =>
                round(
                    $variableCosts,
                    2
                ),

            'contribution_margin' =>
                $contributionMargin !== null
                    ? round(
                        $contributionMargin,
                        2
                    )
                    : null,

            'contribution_margin_ratio' =>
                $contributionMarginRatio !== null
                    ? round(
                        $contributionMarginRatio
                        * 100,
                        2
                    )
                    : null,

            'break_even_revenue' =>
                $breakEvenRevenue !== null
                    ? round(
                        $breakEvenRevenue,
                        2
                    )
                    : null,

            'margin_of_safety' =>
                $marginOfSafety !== null
                    ? round(
                        $marginOfSafety,
                        2
                    )
                    : null,

            'margin_of_safety_percentage' =>
                $marginOfSafetyPercentage !== null
                    ? round(
                        $marginOfSafetyPercentage,
                        2
                    )
                    : null,

            'unclassified_heads' =>
                $unclassifiedHeads
                    ->map(
                        fn (
                            FinanceHead $head
                        ) => [
                            'code' =>
                                $head->code,

                            'name' =>
                                $head->name,
                        ]
                    )
                    ->values()
                    ->all(),

            'expense_voucher_count' =>
                $expenseVouchers->count(),
        ];
    }

      /*
    |--------------------------------------------------------------------------
    | Financial Risk / Crisis Indicator
    |--------------------------------------------------------------------------
    |
    | This is a management warning indicator, not an audit opinion.
    |
    | It deliberately distinguishes incomplete financial data from
    | genuine financial risk so that missing data cannot produce a
    | misleading green or red status.
    |
    */

    public function financialRisk(): array
    {
        $liquidity = $this->liquidity();
        $breakEven = $this->breakEven();

        $warnings = [];
        $criticalIssues = [];

        /*
        |--------------------------------------------------------------------------
        | Data Readiness
        |--------------------------------------------------------------------------
        */

        $liquidityReady =
            $liquidity['current_liabilities'] > 0;

        $breakEvenReady =
            $breakEven['ready'] === true;

        /*
         * If break-even cannot yet be calculated, record the reason
         * without treating missing data as a financial crisis.
         */
        if (!$breakEvenReady) {
            switch ($breakEven['status']) {
                case 'configuration_required':
                    $warnings[] =
                        'Break-even analysis requires completion of expense classifications.';
                    break;

                case 'insufficient_expense_data':
                    $warnings[] =
                        'Break-even analysis is unavailable because no operating expenses have been posted for the current month.';
                    break;

                case 'insufficient_revenue_data':
                    $warnings[] =
                        'Break-even analysis is unavailable because no operating revenue has been recorded for the current month.';
                    break;

                case 'negative_contribution_margin':
                    $criticalIssues[] =
                        'Variable costs equal or exceed operating revenue.';
                    break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Liquidity Assessment
        |--------------------------------------------------------------------------
        |
        | Initial management thresholds:
        |
        | Current Ratio
        | < 1.00  = critical
        | 1.00–1.49 = warning
        |
        | Quick Ratio
        | < 0.75 = critical
        | 0.75–0.99 = warning
        |
        | These thresholds can later be moved into configurable
        | Finance settings.
        |
        */

        if ($liquidityReady) {
            $currentRatio =
                $liquidity['current_ratio'];

            $quickRatio =
                $liquidity['quick_ratio'];

            if (
                $currentRatio !== null
                && $currentRatio < 1.00
            ) {
                $criticalIssues[] =
                    'Current assets are below current liabilities.';
            } elseif (
                $currentRatio !== null
                && $currentRatio < 1.50
            ) {
                $warnings[] =
                    'Current ratio is below the preferred management threshold of 1.50.';
            }

            if (
                $quickRatio !== null
                && $quickRatio < 0.75
            ) {
                $criticalIssues[] =
                    'Quick assets are materially below current liabilities.';
            } elseif (
                $quickRatio !== null
                && $quickRatio < 1.00
            ) {
                $warnings[] =
                    'Quick ratio is below 1.00.';
            }
        } else {
            $warnings[] =
                'Liquidity ratios cannot be meaningfully assessed because no current liabilities are recorded.';
        }

        /*
        |--------------------------------------------------------------------------
        | Break-even / Margin of Safety Assessment
        |--------------------------------------------------------------------------
        |
        | Only evaluate this when the break-even engine has sufficient
        | revenue, expense and classification data.
        |
        */

        if ($breakEvenReady) {
            $marginOfSafetyPercentage =
                $breakEven[
                    'margin_of_safety_percentage'
                ];

            if (
                $marginOfSafetyPercentage !== null
                && $marginOfSafetyPercentage < 0
            ) {
                $criticalIssues[] =
                    'Month-to-date revenue is below the calculated break-even level.';
            } elseif (
                $marginOfSafetyPercentage !== null
                && $marginOfSafetyPercentage < 10
            ) {
                $warnings[] =
                    'Margin of safety is below 10%.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Overall Status
        |--------------------------------------------------------------------------
        |
        | critical          = evidence of an actual high-risk condition
        | warning           = measurable warning condition
        | stable            = sufficient data and no warning triggered
        | insufficient_data = required operating data is incomplete
        |
        */

        if (!empty($criticalIssues)) {
            $status = 'critical';
        } elseif (
            !$breakEvenReady
            || !$liquidityReady
        ) {
            $status = 'insufficient_data';
        } elseif (!empty($warnings)) {
            $status = 'warning';
        } else {
            $status = 'stable';
        }

        /*
        |--------------------------------------------------------------------------
        | Display Label
        |--------------------------------------------------------------------------
        */

        $label = match ($status) {
            'critical' =>
                'Critical Financial Risk',

            'warning' =>
                'Financial Warning',

            'stable' =>
                'Financially Stable',

            default =>
                'Insufficient Financial Data',
        };

        return [
            'status' =>
                $status,

            'label' =>
                $label,

            'liquidity_ready' =>
                $liquidityReady,

            'break_even_ready' =>
                $breakEvenReady,

            'warnings' =>
                $warnings,

            'critical_issues' =>
                $criticalIssues,

            'current_ratio' =>
                $liquidity['current_ratio'],

            'quick_ratio' =>
                $liquidity['quick_ratio'],

            'break_even_revenue' =>
                $breakEven['break_even_revenue'],

            'margin_of_safety_percentage' =>
                $breakEven[
                    'margin_of_safety_percentage'
                ],

            'period' =>
                $breakEven['period'],
        ];
    }
    public function dashboardCharts(): array
{
    $breakEven = $this->breakEven();

    /*
    |--------------------------------------------------------------------------
    | Revenue vs Expenses
    |--------------------------------------------------------------------------
    |
    | Uses the same month-to-date figures already calculated by the
    | financial health service so the chart always agrees with the
    | dashboard financial indicators.
    |
    */

    $revenueVsExpenses = [
        'labels' => [
            'Revenue',
            'Operating Expenses',
        ],

        'values' => [
            round((float) ($breakEven['revenue'] ?? 0), 2),
            round((float) ($breakEven['total_expenses'] ?? 0), 2),
        ],
    ];


    /*
    |--------------------------------------------------------------------------
    | Break-even Position
    |--------------------------------------------------------------------------
    |
    | Break-even revenue is deliberately left null when the calculation
    | is not ready. The frontend must not invent a break-even target.
    |
    */

    $breakEvenPosition = [
        'ready' => (bool) ($breakEven['ready'] ?? false),

        'labels' => [
            'Actual Revenue',
            'Break-even Revenue',
        ],

        'values' => [
            round((float) ($breakEven['revenue'] ?? 0), 2),

            ($breakEven['ready'] ?? false)
                && $breakEven['break_even_revenue'] !== null
                    ? round(
                        (float) $breakEven['break_even_revenue'],
                        2
                    )
                    : null,
        ],

        'status' =>
            $breakEven['status'] ?? 'insufficient_data',

        'margin_of_safety' =>
            $breakEven['margin_of_safety'] ?? null,

        'margin_of_safety_percentage' =>
            $breakEven['margin_of_safety_percentage'] ?? null,
    ];


    /*
    |--------------------------------------------------------------------------
    | Expense Composition
    |--------------------------------------------------------------------------
    */

    $expenseComposition = [
        'ready' =>
            ((float) ($breakEven['total_expenses'] ?? 0)) > 0,

        'labels' => [
            'Fixed Costs',
            'Variable Costs',
        ],

        'values' => [
            round(
                (float) ($breakEven['fixed_costs'] ?? 0),
                2
            ),

            round(
                (float) ($breakEven['variable_costs'] ?? 0),
                2
            ),
        ],
    ];


    return [
        'period' =>
            $breakEven['period'] ?? now()->format('F Y'),

        'revenue_vs_expenses' =>
            $revenueVsExpenses,

        'break_even' =>
            $breakEvenPosition,

        'expense_composition' =>
            $expenseComposition,
    ];
}  
}