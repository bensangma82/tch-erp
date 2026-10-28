<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class DiagnosticActivityReportController extends Controller
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
            ])
            ->where('category', 'radiology')
            ->whereHas('serviceOrder', function ($query) use ($from, $to) {
                $query->whereDate('ordered_at', '>=', $from)
                    ->whereDate('ordered_at', '<=', $to);
            })
            ->get()
            ->filter(fn ($item) =>
                $this->examinationType($item) !== null
            );

        $quantity = fn ($records) => $records->sum(
            fn ($item) => (float) ($item->quantity ?? 1)
        );

        $summary = function ($records) use ($quantity) {
            return [
                'total' => $quantity($records),
                'completed' => $quantity(
                    $records->where('status', 'completed')
                ),
                'in_process' => $quantity(
                    $records->where('status', 'in_process')
                ),
                'ordered' => $quantity(
                    $records->where('status', 'ordered')
                ),
                'types' => $records->pluck('service_code')
                    ->unique()
                    ->count(),
                'items' => $records,
            ];
        };

        $doctor = fn ($item) =>
            $item->serviceOrder?->admission?->consultant
            ?? $item->serviceOrder?->encounter?->doctor;

        $department = fn ($item) =>
            $item->serviceOrder?->admission?->department
            ?? $item->serviceOrder?->encounter?->department;

        $byExamination = $items
            ->groupBy(fn ($item) => $this->examinationType($item))
            ->map(fn ($records) => $summary($records));

        $byTest = $items
            ->groupBy(fn ($item) =>
                $item->service_code ?: $item->service_name
            )
            ->map(function ($records) use ($summary) {
                return [
                    'name' => $records->first()->service_name,
                    ...$summary($records),
                ];
            })
            ->sortBy('name');

        $byDoctor = $items
            ->groupBy(fn ($item) => $doctor($item)?->id ?? 'unassigned')
            ->map(function ($records) use ($doctor, $summary) {
                return [
                    'name' => $doctor($records->first())?->full_name
                        ?? 'Unassigned',
                    ...$summary($records),
                ];
            })
            ->sortBy('name');

        $byDepartment = $items
            ->groupBy(fn ($item) => $department($item)?->id ?? 'unassigned')
            ->map(function ($records) use ($department, $summary) {
                return [
                    'name' => $department($records->first())?->name
                        ?? 'Unassigned',
                    ...$summary($records),
                ];
            })
            ->sortBy('name');

        $bySource = $items
            ->groupBy(function ($item) {
                $order = $item->serviceOrder;

                return $order?->admission_id
                    ? 'IPD'
                    : ($order?->encounter_id ? 'OPD' : 'Other');
            })
            ->map(fn ($records) => $summary($records));

        $totals = $summary($items);

        return view('reports.diagnostic-activity', compact(
            'byExamination',
            'byTest',
            'byDoctor',
            'byDepartment',
            'bySource',
            'totals',
            'from',
            'to'
        ));
    }

    private function examinationType(ServiceOrderItem $item): ?string
    {
        $code = strtoupper(trim((string) $item->service_code));
        $name = strtolower((string) $item->service_name);

        if ($code === 'ECG' || preg_match('/\becg\b|electrocardiogram/', $name)) {
            return 'ECG';
        }

        if (str_starts_with($code, 'USG')
            || str_contains($name, 'ultrasound')
            || preg_match('/\busg\b/', $name)) {
            return 'Ultrasound';
        }

        if ($code === 'CXR'
            || str_contains($name, 'x-ray')
            || str_contains($name, 'xray')) {
            return 'X-ray';
        }

        return null;
    }
}
