<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpdDepartmentRevenueController extends Controller
{
    public function index(Request $request): View
    {
        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
        ]);

        $dateFrom = $validated['date_from']
            ?? now()->startOfMonth()->toDateString();

        $dateTo = $validated['date_to']
            ?? now()->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Initialize Department Totals
        |--------------------------------------------------------------------------
        */

        $departments = Department::query()
            ->orderBy('name')
            ->get();

        $report = [];

        foreach ($departments as $department) {
            $report[$department->id] = [
                'department' => $department->name,
                'billed' => 0.00,
                'collected' => 0.00,
                'outstanding' => 0.00,
                'invoice_count' => 0,
            ];
        }

        $report['unassigned'] = [
            'department' => 'Unassigned Department',
            'billed' => 0.00,
            'collected' => 0.00,
            'outstanding' => 0.00,
            'invoice_count' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | OPD Invoices
        |--------------------------------------------------------------------------
        |
        | Select invoices billed within the requested date range.
        |
        | Payments are included up to the reporting end date,
        | even if collected after the original invoice date.
        |
        | Only OPD-CONS invoice items count as consultation.
        | Registration charges are excluded.
        |
        */

        $invoices = Invoice::query()
            ->where('invoice_type', 'OPD')
            ->whereBetween('invoice_date', [
                $dateFrom,
                $dateTo,
            ])
            ->whereHas('encounter', function ($query) {
                $query->where(
                    'encounter_type',
                    'OPD'
                );
            })
            ->with([
                'encounter:id,department_id',
                'items',
                'payments' => function ($query) use ($dateTo) {
                    $query->whereDate(
                        'payment_date',
                        '<=',
                        $dateTo
                    );
                },
            ])
            ->orderBy('id')
            ->lazy(200);

        /*
        |--------------------------------------------------------------------------
        | Revenue Calculation
        |--------------------------------------------------------------------------
        |
        | Allocation example:
        |
        | Consultation = 180
        | Registration = 20
        | Invoice total = 200
        | Payment received = 100
        |
        | Consultation collected = 90
        | Consultation outstanding = 90
        |
        | If invoice-level discounts exist, their effect is
        | allocated proportionally across invoice items.
        |
        */

        foreach ($invoices as $invoice) {
            $consultationGross = round(
                (float) $invoice->items
                    ->where('code', 'OPD-CONS')
                    ->sum('amount'),
                2
            );

            if ($consultationGross <= 0) {
                continue;
            }

            $itemsTotal = round(
                (float) $invoice->items->sum('amount'),
                2
            );

            $invoiceTotal = max(
                0,
                round(
                    (float) $invoice->total_amount,
                    2
                )
            );

            if ($itemsTotal <= 0) {
                continue;
            }

            /*
             * Apply invoice-level discounts proportionally.
             */
            $consultationBilled = round(
                $invoiceTotal
                * ($consultationGross / $itemsTotal),
                2
            );

            $paymentsReceived = max(
                0,
                round(
                    (float) $invoice->payments->sum('amount'),
                    2
                )
            );

            $paymentsReceived = min(
                $paymentsReceived,
                $invoiceTotal
            );

            $consultationCollected = round(
                $paymentsReceived
                * ($consultationGross / $itemsTotal),
                2
            );

            $consultationCollected = min(
                $consultationCollected,
                $consultationBilled
            );

            $consultationOutstanding = max(
                0,
                round(
                    $consultationBilled - $consultationCollected,
                    2
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Department Attribution
            |--------------------------------------------------------------------------
            */

            $departmentId =
                $invoice->encounter?->department_id;

            $key = $departmentId !== null
                && array_key_exists($departmentId, $report)
                    ? $departmentId
                    : 'unassigned';

            $report[$key]['billed'] +=
                $consultationBilled;

            $report[$key]['collected'] +=
                $consultationCollected;

            $report[$key]['outstanding'] +=
                $consultationOutstanding;

            $report[$key]['invoice_count']++;
        }

        /*
        |--------------------------------------------------------------------------
        | Final Totals
        |--------------------------------------------------------------------------
        */

        $report = collect($report)
            ->map(function ($row) {
                $row['billed'] = round(
                    $row['billed'],
                    2
                );

                $row['collected'] = round(
                    $row['collected'],
                    2
                );

                $row['outstanding'] = round(
                    $row['outstanding'],
                    2
                );

                return $row;
            })
            ->values();

        $totals = [
            'billed' => round(
                $report->sum('billed'),
                2
            ),
            'collected' => round(
                $report->sum('collected'),
                2
            ),
            'outstanding' => round(
                $report->sum('outstanding'),
                2
            ),
            'invoice_count' => $report->sum(
                'invoice_count'
            ),
        ];

        return view(
            'finance.reports.opd-department-revenue',
            [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'report' => $report,
                'totals' => $totals,
            ]
        );
    }
}
