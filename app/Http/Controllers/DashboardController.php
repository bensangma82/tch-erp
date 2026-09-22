<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use App\Models\FinanceVoucher;
use App\Models\IpBillingAdvance;
use App\Models\IpBillingMhisClaim;
use App\Models\IpBillingMhisReceipt;
use App\Models\IpBillingPayment;
use App\Models\Payment;
use App\Models\PharmacyReturn;
use App\Models\PharmacySale;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $metrics = $this->getMetrics();

        return view('dashboard', $metrics);
    }


    public function metrics()
    {
        return response()->json(
            array_merge(
                $this->getMetrics(),
                [
                    'updated_at' => now()->format('h:i:s A'),
                ]
            )
        );
    }


    private function getMetrics(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Operational Metrics
        |--------------------------------------------------------------------------
        */

        $todayOpd = Encounter::whereDate(
            'encounter_date',
            today()
        )->count();


        $awaitingVitals = Encounter::whereDate(
            'encounter_date',
            today()
        )
            ->where('status', 'waiting')
            ->count();


        $waitingForDoctor = Encounter::whereDate(
            'encounter_date',
            today()
        )
            ->where('status', 'waiting_for_doctor')
            ->count();


        $pendingLaboratory = ServiceOrderItem::where(
            'category',
            'laboratory'
        )
            ->whereIn(
                'status',
                [
                    'ordered',
                    'in_process',
                ]
            )
            ->whereHas(
                'serviceOrder',
                function ($query) {
                    $query->whereIn(
                        'status',
                        [
                            'paid',
                            'authorized',
                        ]
                    );
                }
            )
            ->count();


        $pendingImaging = ServiceOrderItem::where(
            'category',
            'radiology'
        )
            ->whereIn(
                'status',
                [
                    'ordered',
                    'in_process',
                ]
            )
            ->whereHas(
                'serviceOrder',
                function ($query) {
                    $query->whereIn(
                        'status',
                        [
                            'paid',
                            'authorized',
                        ]
                    );
                }
            )
            ->count();


        $awaitingPayment = ServiceOrder::where(
            'status',
            'pending_payment'
        )->count();


        $metrics = [
            'todayOpd' => $todayOpd,
            'awaitingVitals' => $awaitingVitals,
            'waitingForDoctor' => $waitingForDoctor,
            'pendingLaboratory' => $pendingLaboratory,
            'pendingImaging' => $pendingImaging,
            'awaitingPayment' => $awaitingPayment,

            /*
             * Financial data is deliberately absent unless the logged-in
             * user is authorised to see the finance dashboard.
             */
            'showFinancialSnapshot' =>
                $this->canViewFinancialMetrics(),
        ];


        if ($metrics['showFinancialSnapshot']) {
            $metrics = array_merge(
                $metrics,
                $this->getFinancialMetrics()
            );
        }


        return $metrics;
    }


    /**
     * Financial dashboard visibility.
     *
     * Admin always has access.
     * Finance-role users retain broad role access.
     * Other roles (for example Management) may be granted
     * finance.dashboard through Role & Permissions.
     */
    private function canViewFinancialMetrics(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->isAdmin()
            || $user->hasRole('finance')
            || $user->hasPermission('finance.dashboard');
    }


    /**
     * Management-level financial snapshot.
     *
     * Collections are actual receipts only:
     * - manual posted receipt vouchers
     * - OPD / investigation payments
     * - direct pharmacy collections net of direct-sale refunds
     * - active IP advances
     * - active finalized IP payments
     * - actual MHIS receipts
     *
     * Approved MHIS is NOT counted as cash received.
     */
    private function getFinancialMetrics(): array
    {
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

        $manualMonthReceipts = (float) FinanceVoucher::query()
            ->where('status', 'posted')
            ->where('voucher_type', 'receipt')
            ->whereYear('voucher_date', $year)
            ->whereMonth('voucher_date', $month)
            ->sum('amount');

        $todayPayments = (float) FinanceVoucher::query()
            ->where('status', 'posted')
            ->where('voucher_type', 'payment')
            ->whereDate('voucher_date', $today)
            ->sum('amount');

        $monthPayments = (float) FinanceVoucher::query()
            ->where('status', 'posted')
            ->where('voucher_type', 'payment')
            ->whereYear('voucher_date', $year)
            ->whereMonth('voucher_date', $month)
            ->sum('amount');


        /*
        |--------------------------------------------------------------------------
        | OPD / Investigation Collections
        |--------------------------------------------------------------------------
        */

        $billingTodayReceipts = (float) Payment::query()
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $billingMonthReceipts = (float) Payment::query()
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('amount');


        /*
        |--------------------------------------------------------------------------
        | Direct Pharmacy Collections
        |--------------------------------------------------------------------------
        |
        | IP-billed pharmacy issues are excluded because they are collected
        | through the inpatient billing workflow.
        |--------------------------------------------------------------------------
        */

        $pharmacyTodaySales = (float) PharmacySale::query()
            ->where('status', 'completed')
            ->whereNull('admission_id')
            ->where('paid_amount', '>', 0)
            ->whereDate('sale_at', $today)
            ->sum('paid_amount');

        $pharmacyMonthSales = (float) PharmacySale::query()
            ->where('status', 'completed')
            ->whereNull('admission_id')
            ->where('paid_amount', '>', 0)
            ->whereYear('sale_at', $year)
            ->whereMonth('sale_at', $month)
            ->sum('paid_amount');

        $pharmacyTodayRefunds = (float) PharmacyReturn::query()
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
            ->sum('refund_amount');

        $pharmacyMonthRefunds = (float) PharmacyReturn::query()
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
            ->sum('refund_amount');

        $pharmacyTodayNet =
            $pharmacyTodaySales
            - $pharmacyTodayRefunds;

        $pharmacyMonthNet =
            $pharmacyMonthSales
            - $pharmacyMonthRefunds;


        /*
        |--------------------------------------------------------------------------
        | Inpatient Collections
        |--------------------------------------------------------------------------
        */

        $ipTodayAdvances = (float) IpBillingAdvance::query()
            ->where('status', 'active')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $ipMonthAdvances = (float) IpBillingAdvance::query()
            ->where('status', 'active')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('amount');

        $ipTodayPayments = (float) IpBillingPayment::query()
            ->where('status', 'active')
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $ipMonthPayments = (float) IpBillingPayment::query()
            ->where('status', 'active')
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('amount');

        $ipTodayCollections =
            $ipTodayAdvances
            + $ipTodayPayments;

        $ipMonthCollections =
            $ipMonthAdvances
            + $ipMonthPayments;


        /*
        |--------------------------------------------------------------------------
        | MHIS Actual Receipts
        |--------------------------------------------------------------------------
        */

        $mhisTodayReceipts = (float) IpBillingMhisReceipt::query()
            ->where('status', 'active')
            ->whereDate('receipt_date', $today)
            ->sum('amount');

        $mhisMonthReceipts = (float) IpBillingMhisReceipt::query()
            ->where('status', 'active')
            ->whereYear('receipt_date', $year)
            ->whereMonth('receipt_date', $month)
            ->sum('amount');


        /*
        |--------------------------------------------------------------------------
        | Combined Actual Collections
        |--------------------------------------------------------------------------
        */

        $todayCollections =
            $manualTodayReceipts
            + $billingTodayReceipts
            + $pharmacyTodayNet
            + $ipTodayCollections
            + $mhisTodayReceipts;

        $monthCollections =
            $manualMonthReceipts
            + $billingMonthReceipts
            + $pharmacyMonthNet
            + $ipMonthCollections
            + $mhisMonthReceipts;

        $monthNet =
            $monthCollections
            - $monthPayments;


        /*
        |--------------------------------------------------------------------------
        | MHIS Outstanding Receivable
        |--------------------------------------------------------------------------
        |
        | Approved amount less actual active MHIS receipts.
        |--------------------------------------------------------------------------
        */

        $mhisApproved = (float) IpBillingMhisClaim::query()
            ->whereIn(
                'status',
                [
                    'approved',
                    'submitted',
                    'settled',
                ]
            )
            ->sum('approved_amount');

        $mhisReceived = (float) IpBillingMhisReceipt::query()
            ->where('status', 'active')
            ->sum('amount');

        $mhisOutstanding = max(
            0,
            $mhisApproved - $mhisReceived
        );


        return [
            'todayCollections' =>
                round($todayCollections, 2),

            'monthCollections' =>
                round($monthCollections, 2),

            'todayFinancePayments' =>
                round($todayPayments, 2),

            'monthFinancePayments' =>
                round($monthPayments, 2),

            'monthFinancialNet' =>
                round($monthNet, 2),

            'mhisOutstanding' =>
                round($mhisOutstanding, 2),

            'billingMonthReceipts' =>
                round($billingMonthReceipts, 2),

            'pharmacyMonthNet' =>
                round($pharmacyMonthNet, 2),

            'ipMonthCollections' =>
                round($ipMonthCollections, 2),

            'mhisMonthReceipts' =>
                round($mhisMonthReceipts, 2),
        ];
    }
}
