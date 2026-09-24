<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticResult;
use App\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosticWorklistController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Laboratory Worklist
    |--------------------------------------------------------------------------
    */

    public function laboratory()
    {
        $items = ServiceOrderItem::with([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'serviceOrder.admission.department',
            'serviceOrder.admission.consultant',
            'diagnosticResult',
            'diagnosticSample',
        ])
            ->where('category', 'laboratory')
            ->whereHas('serviceOrder', function ($query) {
                $query->whereIn('status', ['paid', 'authorized']);
            })
            ->orderByDesc('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Group Laboratory Items by Order
        |--------------------------------------------------------------------------
        |
        | The laboratory worklist should show one main entry per service order
        | rather than repeating the patient/doctor for every investigation.
        | Individual ServiceOrderItems remain unchanged and are still used for
        | sample collection, processing and result entry.
        |
        */
        $orders = $items
            ->groupBy('service_order_id')
            ->map(function ($orderItems) {
                $firstItem = $orderItems->first();

                return (object) [
                    'order' => $firstItem?->serviceOrder,
                    'items' => $orderItems->values(),
                ];
            })
            ->values();

        return view('diagnostics.laboratory', compact('orders'));
    }

    /*
    |--------------------------------------------------------------------------
    | Imaging Worklist
    |--------------------------------------------------------------------------
    */

    public function imaging()
    {
        $items = ServiceOrderItem::with([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'diagnosticResult',
        ])
            ->where('category', 'radiology')
            ->whereHas('serviceOrder', function ($query) {
                $query->whereIn('status', ['paid', 'authorized']);
            })
            ->orderByDesc('id')
            ->get();

        return view('diagnostics.imaging', compact('items'));
    }

    public function editImagingReport(ServiceOrderItem $serviceOrderItem)
    {
        $serviceOrderItem->load([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'diagnosticResult',
        ]);

        if ($serviceOrderItem->category !== 'radiology') {
            return redirect()
                ->route('imaging.index')
                ->withErrors([
                    'report' => 'This investigation is not an imaging study.',
                ]);
        }

        if (
            ! in_array(
                $serviceOrderItem->serviceOrder?->status,
                ['paid', 'authorized'],
                true
            )
        ) {
            return redirect()
                ->route('imaging.index')
                ->withErrors([
                    'report' => 'This imaging investigation is not authorized for reporting.',
                ]);
        }

        if ($serviceOrderItem->status === 'completed') {
            return redirect()
                ->route(
                    'diagnostics.items.imaging-report.show',
                    $serviceOrderItem
                );
        }

        if ($serviceOrderItem->status !== 'in_process') {
            return redirect()
                ->route('imaging.index')
                ->withErrors([
                    'report' => 'Start processing this imaging investigation before entering a report.',
                ]);
        }

        return view(
            'diagnostics.imaging-report-entry',
            compact('serviceOrderItem')
        );
    }

    public function saveImagingReport(
        Request $request,
        ServiceOrderItem $serviceOrderItem
    ) {
        $serviceOrderItem->load([
            'serviceOrder',
            'diagnosticResult',
        ]);

        if ($serviceOrderItem->category !== 'radiology') {
            return redirect()
                ->route('imaging.index')
                ->withErrors([
                    'report' => 'This investigation is not an imaging study.',
                ]);
        }

        if (
            ! in_array(
                $serviceOrderItem->serviceOrder?->status,
                ['paid', 'authorized'],
                true
            )
        ) {
            return back()->withErrors([
                'findings' => 'This imaging investigation is not authorized for reporting.',
            ]);
        }

        if ($serviceOrderItem->status !== 'in_process') {
            return back()->withErrors([
                'findings' => 'Only imaging investigations that are in process can be edited or finalized.',
            ]);
        }

        $validated = $request->validate([
            'action' => [
                'required',
                'in:save_draft,finalize',
            ],
            'findings' => [
                'nullable',
                'string',
                'max:15000',
            ],
            'impression' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'structured_data' => [
                'nullable',
                'array',
            ],
        ]);

        if (
            $validated['action'] === 'finalize'
            && blank($validated['findings'] ?? null)
            && blank($validated['impression'] ?? null)
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'findings' => 'Enter findings or impression before finalizing the imaging report.',
                ]);
        }

        if ($validated['action'] === 'save_draft') {
            DiagnosticResult::updateOrCreate(
                [
                    'service_order_item_id' => $serviceOrderItem->id,
                ],
                [
                    'findings' => $validated['findings'] ?? null,
                    'impression' => $validated['impression'] ?? null,
                    'structured_data' => $validated['structured_data'] ?? null,
                    'status' => 'draft',
                    'entered_by' => auth()->id(),
                    'entered_at' => now(),
                    'verified_by' => null,
                    'verified_at' => null,
                ]
            );

            return redirect()
                ->route(
                    'diagnostics.items.imaging-report.edit',
                    $serviceOrderItem
                )
                ->with(
                    'success',
                    'Draft imaging report saved successfully.'
                );
        }

        DB::transaction(function () use (
            $validated,
            $serviceOrderItem
        ) {
            DiagnosticResult::updateOrCreate(
                [
                    'service_order_item_id' => $serviceOrderItem->id,
                ],
                [
                    'findings' => $validated['findings'] ?? null,
                    'impression' => $validated['impression'] ?? null,
                    'structured_data' => $validated['structured_data'] ?? null,
                    'status' => 'final',
                    'entered_by' => auth()->id(),
                    'entered_at' => now(),
                    'verified_by' => null,
                    'verified_at' => null,
                ]
            );

            $serviceOrderItem->update([
                'status' => 'completed',
            ]);
        });

        return redirect()
            ->route('imaging.index')
            ->with(
                'success',
                'Imaging report finalized and investigation completed.'
            );
    }

    public function showImagingReport(ServiceOrderItem $serviceOrderItem)
    {
        $serviceOrderItem->load([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'diagnosticResult.enteredBy',
            'diagnosticResult.verifiedBy',
        ]);

        if ($serviceOrderItem->category !== 'radiology') {
            return redirect()
                ->route('imaging.index')
                ->withErrors([
                    'report' => 'This investigation is not an imaging study.',
                ]);
        }

        if (! $serviceOrderItem->diagnosticResult) {
            return redirect()
                ->route('imaging.index')
                ->withErrors([
                    'report' => 'No imaging report has been entered for this investigation.',
                ]);
        }

        if ($serviceOrderItem->diagnosticResult->status === 'draft') {
            return redirect()
                ->route(
                    'diagnostics.items.imaging-report.edit',
                    $serviceOrderItem
                )
                ->with(
                    'success',
                    'This imaging report is still a draft and may be edited.'
                );
        }

        return view(
            'diagnostics.imaging-report-view',
            compact('serviceOrderItem')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Investigation Status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        Request $request,
        ServiceOrderItem $serviceOrderItem
    ) {
        $validated = $request->validate([
            'status' => [
                'required',
                'in:ordered,in_process,completed',
            ],
        ]);

        $serviceOrderItem->load([
            'serviceOrder',
            'diagnosticSample',
        ]);

        if (
            ! in_array(
                $serviceOrderItem->serviceOrder?->status,
                ['paid', 'authorized'],
                true
            )
        ) {
            return back()->withErrors([
                'status' => 'This investigation has not been released for processing.',
            ]);
        }

        if (
            $serviceOrderItem->category === 'laboratory'
            && $serviceOrderItem->requires_sample
            && $validated['status'] === 'in_process'
            && (
                ! $serviceOrderItem->diagnosticSample
                || $serviceOrderItem->diagnosticSample->status !== 'collected'
            )
        ) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'Sample must be collected before this investigation can be started.',
                ]);
        }

        $allowedTransitions = [
            'ordered' => ['in_process'],
            'in_process' => [],
            'completed' => [],
        ];

        if (
            ! in_array(
                $validated['status'],
                $allowedTransitions[$serviceOrderItem->status] ?? [],
                true
            )
        ) {
            return back()->withErrors([
                'status' => 'This status change is not allowed.',
            ]);
        }

        $serviceOrderItem->update([
            'status' => $validated['status'],
        ]);

        return back()->with(
            'success',
            'Investigation status updated successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Laboratory Result Entry Page
    |--------------------------------------------------------------------------
    */

    public function editResult(ServiceOrderItem $serviceOrderItem)
    {
        $serviceOrderItem->load([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'diagnosticResult.items',
            'diagnosticSample.collectedBy',
        ]);

        if ($serviceOrderItem->category !== 'laboratory') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'result' => 'This investigation is not a laboratory test.',
                ]);
        }

        if (
            ! in_array(
                $serviceOrderItem->serviceOrder?->status,
                ['paid', 'authorized'],
                true
            )
        ) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'result' => 'This investigation is not authorized for result entry.',
                ]);
        }

        if (
            $serviceOrderItem->requires_sample
            && (
                ! $serviceOrderItem->diagnosticSample
                || $serviceOrderItem->diagnosticSample->status !== 'collected'
            )
        ) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'A collected sample is required before entering laboratory results.',
                ]);
        }

        if ($serviceOrderItem->status === 'completed') {
            return redirect()
                ->route(
                    'diagnostics.items.result.show',
                    $serviceOrderItem
                );
        }

        if ($serviceOrderItem->status !== 'in_process') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'result' => 'Start processing this investigation before entering a result.',
                ]);
        }

        return view(
            'diagnostics.result-entry',
            compact('serviceOrderItem')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Save Draft / Finalize Laboratory Result
    |--------------------------------------------------------------------------
    */

    public function saveResult(
        Request $request,
        ServiceOrderItem $serviceOrderItem
    ) {
        $serviceOrderItem->load([
            'serviceOrder',
            'diagnosticResult.items',
            'diagnosticSample',
        ]);

        if ($serviceOrderItem->category !== 'laboratory') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'result' => 'This investigation is not a laboratory test.',
                ]);
        }

        if (
            ! in_array(
                $serviceOrderItem->serviceOrder?->status,
                ['paid', 'authorized'],
                true
            )
        ) {
            return back()->withErrors([
                'result' => 'This investigation is not authorized for result entry.',
            ]);
        }

        if (
            $serviceOrderItem->requires_sample
            && (
                ! $serviceOrderItem->diagnosticSample
                || $serviceOrderItem->diagnosticSample->status !== 'collected'
            )
        ) {
            return back()->withErrors([
                'sample' => 'A collected sample is required before entering laboratory results.',
            ]);
        }

        if ($serviceOrderItem->status !== 'in_process') {
            return back()->withErrors([
                'result' => 'Only investigations that are in process can be edited or finalized.',
            ]);
        }

        $validated = $request->validate([
            'action' => [
                'required',
                'in:save_draft,finalize',
            ],
            'overall_comment' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'result_text' => [
                'nullable',
                'string',
                'max:15000',
            ],
            'parameters' => [
                'nullable',
                'array',
                'max:100',
            ],
            'parameters.*.parameter_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'parameters.*.result_value' => [
                'nullable',
                'string',
                'max:500',
            ],
            'parameters.*.unit' => [
                'nullable',
                'string',
                'max:100',
            ],
            'parameters.*.reference_range' => [
                'nullable',
                'string',
                'max:255',
            ],
            'parameters.*.flag' => [
                'nullable',
                'in:low,high,critical_low,critical_high,abnormal',
            ],
            'parameters.*.remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $parameters = collect($validated['parameters'] ?? [])
            ->map(function ($parameter) {
                return [
                    'parameter_name' => trim((string) ($parameter['parameter_name'] ?? '')),
                    'result_value' => trim((string) ($parameter['result_value'] ?? '')),
                    'unit' => trim((string) ($parameter['unit'] ?? '')),
                    'reference_range' => trim((string) ($parameter['reference_range'] ?? '')),
                    'flag' => $this->determineLaboratoryFlag(
                        trim((string) ($parameter['result_value'] ?? '')),
                        trim((string) ($parameter['reference_range'] ?? '')),
                        $parameter['flag'] ?? null
                    ),
                    'remarks' => trim((string) ($parameter['remarks'] ?? '')),
                ];
            })
            ->filter(function ($parameter) {
                return $parameter['parameter_name'] !== ''
                    || $parameter['result_value'] !== ''
                    || $parameter['unit'] !== ''
                    || $parameter['reference_range'] !== ''
                    || ! empty($parameter['flag'])
                    || $parameter['remarks'] !== '';
            })
            ->values();

        if (
            $validated['action'] === 'finalize'
            && ! $parameters->contains(function ($parameter) {
                return $parameter['result_value'] !== '';
            })
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'parameters' => 'Enter at least one laboratory result value before finalizing.',
                ]);
        }

        $status =
            $validated['action'] === 'finalize'
                ? 'final'
                : 'draft';

        DB::transaction(function () use (
            $validated,
            $parameters,
            $serviceOrderItem,
            $status
        ) {
            $diagnosticResult = DiagnosticResult::updateOrCreate(
                [
                    'service_order_item_id' => $serviceOrderItem->id,
                ],
                [
                    'result_text' => $validated['overall_comment'] ?? null,
                    'findings' => null,
                    'impression' => null,
                    'status' => $status,
                    'entered_by' => auth()->id(),
                    'entered_at' => now(),
                    'verified_by' => null,
                    'verified_at' => null,
                ]
            );

            $diagnosticResult->items()->delete();

            foreach ($parameters as $index => $parameter) {
                $diagnosticResult->items()->create([
                    'parameter_name' =>
                        $parameter['parameter_name'] !== ''
                            ? $parameter['parameter_name']
                            : 'Result',

                    'result_value' =>
                        $parameter['result_value'] !== ''
                            ? $parameter['result_value']
                            : null,

                    'unit' =>
                        $parameter['unit'] !== ''
                            ? $parameter['unit']
                            : null,

                    'reference_range' =>
                        $parameter['reference_range'] !== ''
                            ? $parameter['reference_range']
                            : null,

                    'flag' =>
                        ! empty($parameter['flag'])
                            ? $parameter['flag']
                            : null,

                    'sort_order' => $index + 1,

                    'remarks' =>
                        $parameter['remarks'] !== ''
                            ? $parameter['remarks']
                            : null,
                ]);
            }

            if ($status === 'final') {
                $serviceOrderItem->update([
                    'status' => 'completed',
                ]);
            }
        });

        if ($validated['action'] === 'save_draft') {
            return redirect()
                ->route(
                    'diagnostics.items.result.edit',
                    $serviceOrderItem
                )
                ->with(
                    'success',
                    'Draft laboratory result saved successfully.'
                );
        }

        return redirect()
            ->route('laboratory.index')
            ->with(
                'success',
                'Laboratory result finalized and investigation completed.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | View Final Laboratory Result
    |--------------------------------------------------------------------------
    */

    public function showResult(ServiceOrderItem $serviceOrderItem)
    {
        $serviceOrderItem->load([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'service.laboratoryTestParameters',
            'diagnosticResult.enteredBy',
            'diagnosticResult.verifiedBy',
            'diagnosticResult.items',
            'diagnosticSample.collectedBy',
        ]);

        if ($serviceOrderItem->category !== 'laboratory') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'result' => 'This investigation is not a laboratory test.',
                ]);
        }

        if (! $serviceOrderItem->diagnosticResult) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'result' => 'No result has been entered for this investigation.',
                ]);
        }

        if ($serviceOrderItem->diagnosticResult->status === 'draft') {
            return redirect()
                ->route(
                    'diagnostics.items.result.edit',
                    $serviceOrderItem
                )
                ->with(
                    'success',
                    'This result is still a draft and may be edited.'
                );
        }

        return view(
            'diagnostics.result-view',
            compact('serviceOrderItem')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | View / Print Grouped Laboratory Results
    |--------------------------------------------------------------------------
    |
    | Shows all finalized laboratory results belonging to one service order.
    | Incomplete/draft investigations are deliberately excluded.
    |
    */
    public function showGroupedResults(int $serviceOrderId)
    {
        $items = ServiceOrderItem::with([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'serviceOrder.admission.department',
            'serviceOrder.admission.consultant',
            'diagnosticResult.enteredBy',
            'diagnosticResult.verifiedBy',
            'diagnosticResult.items',
            'diagnosticSample.collectedBy',
        ])
            ->where('service_order_id', $serviceOrderId)
            ->where('category', 'laboratory')
            ->where('status', 'completed')
            ->whereHas('diagnosticResult', function ($query) {
                $query->whereIn('status', ['final', 'verified']);
            })
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'result' => 'No completed laboratory results are available for this order.',
                ]);
        }

        $order = $items->first()->serviceOrder;

        if (! in_array($order?->status, ['paid', 'authorized'], true)) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'result' => 'This laboratory order is not released for reporting.',
                ]);
        }

        return view(
            'diagnostics.result-group',
            compact('order', 'items')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Automatic Laboratory Result Flagging
    |--------------------------------------------------------------------------
    |
    | Automatically derives HIGH / LOW storage flags from common numeric
    | reference-range formats. The database continues to store the existing
    | canonical values "high" and "low"; report views can display these as
    | HIGH and LOW.
    |
    | Supported examples:
    |   <4.5
    |   <=4.5
    |   >10
    |   >=10
    |   0.6 - 1.2
    |   40–45
    |
    | If the result or reference range cannot be interpreted numerically,
    | an explicitly supplied manual flag is preserved.
    |
    */
    private function determineLaboratoryFlag(
        string $resultValue,
        string $referenceRange,
        ?string $manualFlag = null
    ): ?string {
        $result = $this->extractNumericLaboratoryValue($resultValue);

        if ($result === null || trim($referenceRange) === '') {
            return $manualFlag;
        }

        $range = trim(
            str_replace(
                ["\u{2013}", "\u{2014}", "\u{2212}"],
                '-',
                $referenceRange
            )
        );

        if (preg_match('/^\s*<=\s*(-?\d+(?:\.\d+)?)\s*$/u', $range, $matches)) {
            return $result > (float) $matches[1] ? 'high' : null;
        }

        if (preg_match('/^\s*<\s*(-?\d+(?:\.\d+)?)\s*$/u', $range, $matches)) {
            return $result >= (float) $matches[1] ? 'high' : null;
        }

        if (preg_match('/^\s*>=\s*(-?\d+(?:\.\d+)?)\s*$/u', $range, $matches)) {
            return $result < (float) $matches[1] ? 'low' : null;
        }

        if (preg_match('/^\s*>\s*(-?\d+(?:\.\d+)?)\s*$/u', $range, $matches)) {
            return $result <= (float) $matches[1] ? 'low' : null;
        }

        if (
            preg_match(
                '/^\s*(-?\d+(?:\.\d+)?)\s*-\s*(-?\d+(?:\.\d+)?)\s*$/u',
                $range,
                $matches
            )
        ) {
            $low = (float) $matches[1];
            $high = (float) $matches[2];

            if ($low > $high) {
                [$low, $high] = [$high, $low];
            }

            if ($result < $low) {
                return 'low';
            }

            if ($result > $high) {
                return 'high';
            }

            return null;
        }

        return $manualFlag;
    }

    private function extractNumericLaboratoryValue(string $value): ?float
    {
        $value = trim(str_replace(',', '', $value));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            return (float) $value;
        }

        return null;
    }

}
