<?php

namespace App\Http\Controllers;

use App\Models\MhisProcedure;
use Illuminate\Http\Request;

class MhisProcedureController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $specialty = trim((string) $request->query('specialty', ''));

        $query = MhisProcedure::query()
            ->where('is_active', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $term = '%' . $search . '%';

                $q->where('procedure_code', 'ilike', $term)
                    ->orWhere('procedure_name', 'ilike', $term)
                    ->orWhere('package_name', 'ilike', $term)
                    ->orWhere('specialty', 'ilike', $term);
            });
        }

        if ($specialty !== '') {
            $query->where('specialty', $specialty);
        }

        $procedures = $query
            ->orderBy('specialty')
            ->orderBy('procedure_code')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $specialties = MhisProcedure::query()
            ->where('is_active', true)
            ->whereNotNull('specialty')
            ->distinct()
            ->orderBy('specialty')
            ->pluck('specialty');

        return view('mhis.procedures.index', compact(
            'procedures',
            'specialties',
            'search',
            'specialty'
        ));
    }


    public function searchForModal(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $search = trim($validated['q']);

        $procedures = MhisProcedure::query()
            ->where('is_active', true)
            ->where(function ($query) use ($search) {
                $term = '%' . $search . '%';

                $query->where('procedure_code', 'ilike', $term)
                    ->orWhere('procedure_name', 'ilike', $term)
                    ->orWhere('package_name', 'ilike', $term)
                    ->orWhere('specialty', 'ilike', $term);
            })
            ->orderBy('procedure_code')
            ->orderBy('specialty')
            ->orderBy('id')
            ->limit(25)
            ->get([
                'id',
                'procedure_code',
                'specialty',
                'package_name',
                'procedure_name',
                'procedure_price',
                'source_price_text',
                'stratification_options',
            ]);

        $regular = $procedures->map(function ($p) {
            $p->source_type = 'regular';
            return $p;
        })->toArray();

        $high = \App\Models\MhisHighEndItem::query()
            ->where('is_active', true)
            ->where(function ($q) use ($search) {
                $term = '%' . $search . '%';
                $q->where('procedure_code', 'ilike', $term)
                    ->orWhere('item_name', 'ilike', $term)
                    ->orWhere('specialty', 'ilike', $term);
            })
            ->orderBy('procedure_code')->orderBy('id')
            ->limit(25)->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'source_type' => 'high_end',
                'procedure_code' => $i->procedure_code,
                'procedure_name' => $i->item_name,
                'package_name' => $i->item_name,
                'specialty' => $i->specialty,
                'procedure_price' => $i->reference_price,
                'source_price_text' => $i->source_price_text,
                'hospital_type' => $i->hospital_type,
                'opd_ipd' => $i->opd_ipd,
                'maximum_multiplier' => $i->maximum_multiplier,
            ])->toArray();

        return response()->json([
            'results' => array_slice(array_merge($regular, $high), 0, 50),
        ]);
    }

    public function show(MhisProcedure $procedure)
    {
        abort_unless($procedure->is_active, 404);

        return view('mhis.procedures.show', compact('procedure'));
    }
}
