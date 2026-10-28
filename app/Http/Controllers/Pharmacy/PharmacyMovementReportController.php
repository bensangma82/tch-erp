<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\PharmacySaleItem;
use App\Models\PharmacyReturnItem;
use Illuminate\Http\Request;

class PharmacyMovementReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $from = today()->subDays($days - 1)->startOfDay();
        $to = today()->endOfDay();

        $sales = PharmacySaleItem::query()
            ->with('sale')
            ->whereHas('sale', fn ($q) =>
                $q->whereIn('status', ['completed', 'credit'])
                  ->whereBetween('sale_at', [$from, $to])
            )
            ->get()
            ->groupBy('medicine_id');

        $returns = PharmacyReturnItem::query()
            ->whereHas('pharmacyReturn', fn ($q) =>
                $q->where('status', 'completed')
                  ->whereBetween('returned_at', [$from, $to])
            )
            ->get()
            ->groupBy('medicine_id');

        $medicines = Medicine::query()
            ->with('stockBatches.locationBalances')
            ->get();

        $items = $medicines->map(function ($medicine) use ($sales, $returns, $days) {
            $sold = $sales->get($medicine->id, collect());
            $returned = $returns->get($medicine->id, collect());

            $soldQty = (int) $sold->sum('quantity');
            $returnedQty = (int) $returned->sum('quantity');
            $netQty = $soldQty - $returnedQty;

            $salesValue = (float) $sold->sum('amount');
            $refundValue = (float) $returned->sum('refund_amount');

            $stock = $medicine->stockBatches->sum(function ($batch) {
                return $batch->locationBalances->isNotEmpty()
                    ? $batch->locationBalances->sum('quantity_available')
                    : $batch->quantity_available;
            });

            $daily = $netQty / $days;

            return [
                'medicine_id' => $medicine->id,
                'molecule' => $medicine->generic_name ?: 'Unspecified',
                'brand' => $medicine->brand_name ?: 'Unspecified',
                'strength' => $medicine->strength,
                'form' => $medicine->dosage_form,
                'sold' => $soldQty,
                'returned' => $returnedQty,
                'net_qty' => $netQty,
                'net_sales' => round($salesValue - $refundValue, 2),
                'stock' => (int) $stock,
                'daily_consumption' => round($daily, 2),
                'coverage_days' => $daily > 0
                    ? round($stock / $daily, 1)
                    : null,
            ];
        });

        $byMolecule = $items->groupBy('molecule')->map(fn ($rows, $name) => [
            'name' => $name,
            'quantity' => $rows->sum('net_qty'),
            'sales' => round($rows->sum('net_sales'), 2),
            'stock' => $rows->sum('stock'),
            'brands' => $rows->pluck('brand')->unique()->count(),
        ])->sortByDesc('quantity');

        $byBrand = $items->groupBy(fn ($r) =>
            $r['brand'].' | '.$r['molecule'].' | '.$r['strength']
        )->map(fn ($rows, $name) => [
            'name' => $name,
            'quantity' => $rows->sum('net_qty'),
            'sales' => round($rows->sum('net_sales'), 2),
            'stock' => $rows->sum('stock'),
        ])->sortByDesc('quantity');

        $fastMoving = $items
            ->filter(fn ($r) => $r['net_qty'] > 0)
            ->sortByDesc('net_qty')
            ->take(20);

        $slowMoving = $items
            ->filter(fn ($r) =>
                $r['stock'] > 0 && $r['net_qty'] > 0
            )
            ->sortBy('daily_consumption')
            ->take(20);

        $nonMoving = $items
            ->filter(fn ($r) =>
                $r['stock'] > 0 && $r['net_qty'] === 0
            )
            ->sortByDesc('stock');

        return view('pharmacy.movement-report', compact(
            'days',
            'from',
            'to',
            'items',
            'byMolecule',
            'byBrand',
            'fastMoving',
            'slowMoving',
            'nonMoving'
        ));
    }
}
