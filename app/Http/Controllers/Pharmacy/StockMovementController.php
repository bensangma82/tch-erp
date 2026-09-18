<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStockBatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(
        Request $request,
        PharmacyStockBatch $stockBatch
    ): View {
        $stockBatch->load('medicine');

        $movements = $stockBatch->movements()
            ->with('createdBy')
            ->orderByDesc('movement_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view(
            'pharmacy.stock-movements.index',
            compact(
                'stockBatch',
                'movements'
            )
        );
    }
}