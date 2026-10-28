<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class IpdActivityReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $from = $filters['from'] ?? today()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? today()->toDateString();

        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            throw ValidationException::withMessages([
                'to' => 'End date cannot precede start date.',
            ]);
        }

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $admissions = Admission::query()
            ->with(['patient', 'consultant', 'department'])
            ->where('admitted_at', '<=', $end)
            ->get();

        $summarise = function ($records) use ($start, $end) {
            $admitted = $records->filter(
                fn ($a) => $a->admitted_at?->betweenIncluded($start, $end)
            );

            $closures = $records->filter(
                fn ($a) => $a->closed_at?->betweenIncluded($start, $end)
            );

            $discharged = $closures->where('status', 'discharged');
            $referred = $closures->where('status', 'referred');
            $deaths = $closures->where('status', 'death');

            $inpatients = $records->filter(
                fn ($a) => $a->admitted_at <= $end
                    && (
                        $a->discharged_at === null
                        || $a->discharged_at > $end
                    )
                    && (
                        $a->closed_at === null
                        || $a->closed_at > $end
                    )
            );

            return [
                'admissions' => $admitted->count(),
                'discharges' => $discharged->count(),
                'referrals' => $referred->count(),
                'deaths' => $deaths->count(),
                'inpatients' => $inpatients->count(),
                'patients' => $records->filter(
                    fn ($a) => $admitted->contains('id', $a->id)
                        || $closures->contains('id', $a->id)
                        || $inpatients->contains('id', $a->id)
                ),
            ];
        };

        $byDoctor = $admissions
            ->groupBy(fn ($a) => $a->consultant_id ?? 'unassigned')
            ->map(function ($records) use ($summarise) {
                return [
                    'name' => $records->first()->consultant?->full_name
                        ?? 'Unassigned',
                    ...$summarise($records),
                ];
            })
            ->sortBy('name');

        $byDepartment = $admissions
            ->groupBy(fn ($a) => $a->department_id ?? 'unassigned')
            ->map(function ($records) use ($summarise) {
                return [
                    'name' => $records->first()->department?->name
                        ?? 'Unassigned',
                    ...$summarise($records),
                ];
            })
            ->sortBy('name');

        $totals = $summarise($admissions);

        return view('reports.ipd-activity', compact(
            'byDoctor',
            'byDepartment',
            'totals',
            'from',
            'to'
        ));
    }
}
