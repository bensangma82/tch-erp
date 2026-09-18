<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyReturn;
use App\Models\PharmacySale;
use App\Models\PharmacyStockBatch;
use Illuminate\View\View;

class PharmacyDashboardController extends Controller
{
    public function index(): View
    {
        $today = today();

        $nearExpiryDate =
            $today->copy()->addDays(90);


        /*
        |--------------------------------------------------------------------------
        | Today's Sales
        |--------------------------------------------------------------------------
        */

        $todaySalesAmount =
            PharmacySale::query()
                ->whereDate(
                    'sale_at',
                    $today
                )
                ->whereIn(
                    'status',
                    [
                        'completed',
                        'credit',
                    ]
                )
                ->sum(
                    'total_amount'
                );


        $todayDispensingCount =
            PharmacySale::query()
                ->whereDate(
                    'sale_at',
                    $today
                )
                ->whereIn(
                    'status',
                    [
                        'completed',
                        'credit',
                    ]
                )
                ->count();



        /*
        |--------------------------------------------------------------------------
        | Today's Returns
        |--------------------------------------------------------------------------
        */

        $todayRefundAmount =
            PharmacyReturn::query()
                ->whereDate(
                    'returned_at',
                    $today
                )
                ->where(
                    'status',
                    'completed'
                )
                ->sum(
                    'refund_amount'
                );


        $todayReturnCount =
            PharmacyReturn::query()
                ->whereDate(
                    'returned_at',
                    $today
                )
                ->where(
                    'status',
                    'completed'
                )
                ->count();



        /*
        |--------------------------------------------------------------------------
        | Net Sales
        |--------------------------------------------------------------------------
        */

        $todayNetSales =
            round(
                (float) $todaySalesAmount
                - (float) $todayRefundAmount,
                2
            );



        /*
        |--------------------------------------------------------------------------
        | Low Stock
        |--------------------------------------------------------------------------
        |
        | Some stock remains, but quantity has reached or fallen
        | below the configured reorder level.
        |
        */

        $lowStockCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereColumn(
                    'quantity_available',
                    '<=',
                    'reorder_level'
                )
                ->count();



        /*
        |--------------------------------------------------------------------------
        | Out of Stock
        |--------------------------------------------------------------------------
        */

        $outOfStockCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '<=',
                    0
                )
                ->count();



        /*
        |--------------------------------------------------------------------------
        | Near Expiry
        |--------------------------------------------------------------------------
        |
        | Active stock expiring from today through the next 90 days.
        |
        */

        $nearExpiryCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereNotNull(
                    'expiry_date'
                )
                ->whereDate(
                    'expiry_date',
                    '>=',
                    $today
                )
                ->whereDate(
                    'expiry_date',
                    '<=',
                    $nearExpiryDate
                )
                ->count();



        /*
        |--------------------------------------------------------------------------
        | Expired Stock
        |--------------------------------------------------------------------------
        |
        | Only expired batches that still contain physical stock
        | are counted as an operational alert.
        |
        */

        $expiredCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereNotNull(
                    'expiry_date'
                )
                ->whereDate(
                    'expiry_date',
                    '<',
                    $today
                )
                ->count();



        /*
        |--------------------------------------------------------------------------
        | Recent Sales
        |--------------------------------------------------------------------------
        */

        $recentSales =
            PharmacySale::query()
                ->with([
                    'patient',
                    'createdBy',
                ])
                ->orderByDesc(
                    'sale_at'
                )
                ->limit(8)
                ->get();



        /*
        |--------------------------------------------------------------------------
        | Recent Returns
        |--------------------------------------------------------------------------
        */

        $recentReturns =
            PharmacyReturn::query()
                ->with([
                    'patient',
                    'sale',
                    'createdBy',
                ])
                ->where(
                    'status',
                    'completed'
                )
                ->orderByDesc(
                    'returned_at'
                )
                ->limit(8)
                ->get();



        /*
        |--------------------------------------------------------------------------
        | Low Stock Alert List
        |--------------------------------------------------------------------------
        */

        $lowStockBatches =
            PharmacyStockBatch::query()
                ->with(
                    'medicine'
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereColumn(
                    'quantity_available',
                    '<=',
                    'reorder_level'
                )
                ->orderBy(
                    'quantity_available'
                )
                ->limit(10)
                ->get();



        /*
        |--------------------------------------------------------------------------
        | Near Expiry Alert List
        |--------------------------------------------------------------------------
        */

        $nearExpiryBatches =
            PharmacyStockBatch::query()
                ->with(
                    'medicine'
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereNotNull(
                    'expiry_date'
                )
                ->whereDate(
                    'expiry_date',
                    '>=',
                    $today
                )
                ->whereDate(
                    'expiry_date',
                    '<=',
                    $nearExpiryDate
                )
                ->orderBy(
                    'expiry_date'
                )
                ->limit(10)
                ->get();



        /*
        |--------------------------------------------------------------------------
        | Expired Stock Alert List
        |--------------------------------------------------------------------------
        */

        $expiredBatches =
            PharmacyStockBatch::query()
                ->with(
                    'medicine'
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereNotNull(
                    'expiry_date'
                )
                ->whereDate(
                    'expiry_date',
                    '<',
                    $today
                )
                ->orderBy(
                    'expiry_date'
                )
                ->limit(10)
                ->get();



        /*
        |--------------------------------------------------------------------------
        | Dashboard View
        |--------------------------------------------------------------------------
        */

        return view(
            'pharmacy.dashboard',
            compact(
                'todaySalesAmount',
                'todayDispensingCount',
                'todayRefundAmount',
                'todayReturnCount',
                'todayNetSales',
                'lowStockCount',
                'outOfStockCount',
                'nearExpiryCount',
                'expiredCount',
                'recentSales',
                'recentReturns',
                'lowStockBatches',
                'nearExpiryBatches',
                'expiredBatches'
            )
        );
    }
}