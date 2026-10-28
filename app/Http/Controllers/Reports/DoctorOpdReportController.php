<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DoctorOpdReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $from = $filters['from'] ?? today()->toDateString();
        $to = $filters['to'] ?? $from;

        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            throw ValidationException::withMessages([
                'to' => 'The end date must not precede the start date.',
            ]);
        }

        $encounters = Encounter::query()
            ->with(['patient', 'doctor', 'department'])
            ->where('encounter_type', 'OPD')
            ->whereDate('encounter_date', '>=', $from)
            ->whereDate('encounter_date', '<=', $to)
            ->orderBy('encounter_date')
            ->orderBy('queue_number')
            ->get();

        $doctors = $encounters
            ->groupBy('doctor_id')
            ->map(function ($visits) {
                return [
                    'doctor' => $visits->first()->doctor,
                    'registered' => $visits->count(),
                    'seen' => $visits->where('status', 'completed')->count(),
                    'pending' => $visits->where('status', '!=', 'completed')->count(),
                    'patients' => $visits,
                ];
            })
            ->sortBy(fn ($row) => $row['doctor']?->full_name ?? 'Unassigned');

        $totals = [
            'registered' => $encounters->count(),
            'seen' => $encounters->where('status', 'completed')->count(),
            'pending' => $encounters->where('status', '!=', 'completed')->count(),
        ];

        return view('reports.doctor-opd', compact(
            'doctors',
            'totals',
            'from',
            'to'
        ));
    }
}
