<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\MhisSettlementImportBatch;
use App\Services\MhisSettlementExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MhisSettlementImportController extends Controller
{
    public function index()
    {
        $batches = MhisSettlementImportBatch::query()
            ->latest('id')
            ->paginate(20);

        return view('finance.mhis-settlement-import.index', compact('batches'));
    }

    public function store(
        Request $request,
        MhisSettlementExcelImportService $importer
    ) {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:10240',
            ],
        ]);

        $file = $validated['file'];

        $batch = $importer->import(
            $file->getRealPath(),
            $request->user()?->id,
            $file->getClientOriginalName()
        );

        return redirect()
            ->route('finance.mhis-settlement-import.show', $batch)
            ->with('success', 'MHIS settlement file imported successfully.');
    }

    public function match(
        MhisSettlementImportBatch $batch,
        \App\Services\Mhis\MhisSettlementMatchingService $matcher
    ) {
        if ($batch->status !== 'imported') {
            abort(409, 'This batch is not eligible for matching.');
        }

        $stats = $matcher->match($batch);

        return redirect()
            ->route('finance.mhis-settlement-import.show', $batch)
            ->with(
                'success',
                'Matching completed. ' .
                $stats['matched'] . ' matched; ' .
                $stats['unmatched'] . ' unmatched; ' .
                $stats['ambiguous'] . ' ambiguous; ' .
                $stats['review_required'] . ' require review.'
            );
    }

    public function show(MhisSettlementImportBatch $batch)
    {
        $matchSummary = $batch->rows()
            ->selectRaw('match_status, COUNT(*) AS total')
            ->groupBy('match_status')
            ->pluck('total', 'match_status');

        $rows = $batch->rows()
            ->orderBy('excel_row_number')
            ->paginate(50);

        return view('finance.mhis-settlement-import.show', compact(
            'batch',
            'rows',
            'matchSummary'
        ));
    }
}
