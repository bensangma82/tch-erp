<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LaboratoryActivityReportController extends Controller
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

        $items = ServiceOrderItem::query()
            ->with([
                'serviceOrder.patient',
                'serviceOrder.encounter.doctor',
                'serviceOrder.encounter.department',
                'serviceOrder.admission.consultant',
                'serviceOrder.admission.department',
                'serviceOrder.createdBy',
            ])
            ->where('category', 'laboratory')
            ->whereHas('serviceOrder', function ($query) use ($from, $to) {
                $query->whereDate('ordered_at', '>=', $from)
                    ->whereDate('ordered_at', '<=', $to);
            })
            ->get();

        $quantity = fn ($records) => $records->sum(
            fn ($item) => (float) ($item->quantity ?? 1)
        );

        $summary = function ($records) use ($quantity) {
            return [
                'tests' => $quantity($records),
                'completed' => $quantity(
                    $records->where('status', 'completed')
                ),
                'in_process' => $quantity(
                    $records->where('status', 'in_process')
                ),
                'ordered' => $quantity(
                    $records->where('status', 'ordered')
                ),
                'types' => $records
                    ->map(fn ($i) => $i->service_code ?: $i->service_name)
                    ->unique()
                    ->count(),
                'items' => $records,
            ];
        };

        $source = function ($item) {
            $order = $item->serviceOrder;

            if ($order?->admission_id) {
                return 'IPD';
            }

            if ($order?->encounter_id) {
                return 'OPD';
            }

            return 'Other';
        };

        $doctor = function ($item) {
            $order = $item->serviceOrder;

            return $order?->admission?->consultant
                ?? $order?->encounter?->doctor;
        };

        $department = function ($item) {
            $order = $item->serviceOrder;

            return $order?->admission?->department
                ?? $order?->encounter?->department;
        };

        $byTest = $items
            ->groupBy(fn ($i) => $i->service_code ?: $i->service_name)
            ->map(function ($records) use ($summary) {
                return [
                    'name' => $records->first()->service_name,
                    ...$summary($records),
                ];
            })
            ->sortBy('name');

        $byDoctor = $items
            ->groupBy(fn ($i) => $doctor($i)?->id ?? 'unassigned')
            ->map(function ($records) use ($doctor, $summary) {
                return [
                    'name' => $doctor($records->first())?->full_name
                        ?? 'Unassigned',
                    ...$summary($records),
                ];
            })
            ->sortBy('name');

        $byDepartment = $items
            ->groupBy(fn ($i) => $department($i)?->id ?? 'unassigned')
            ->map(function ($records) use ($department, $summary) {
                return [
                    'name' => $department($records->first())?->name
                        ?? 'Unassigned',
                    ...$summary($records),
                ];
            })
            ->sortBy('name');

        $bySource = $items
            ->groupBy($source)
            ->map(fn ($records) => $summary($records));

        $totals = $summary($items);

        return view('reports.laboratory-activity', compact(
            'byTest',
            'byDoctor',
            'byDepartment',
            'bySource',
            'totals',
            'from',
            'to'
        ));
    }
}
