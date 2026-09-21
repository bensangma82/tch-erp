<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryTestParameter;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LaboratoryTestParameterController extends Controller
{
    /**
     * List laboratory services and their parameter counts.
     */
    public function index()
    {
        $services = Service::query()
            ->where('category', 'laboratory')
            ->withCount([
                'laboratoryTestParameters as active_parameter_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->orderBy('name')
            ->get();

        return view(
            'admin.laboratory-parameters.index',
            compact('services')
        );
    }

    /**
     * Show parameter editor for a laboratory service.
     */
    public function edit(Service $service)
    {
        if ($service->category !== 'laboratory') {
            return redirect()
                ->route('admin.laboratory-parameters.index')
                ->withErrors([
                    'service' => 'This service is not a laboratory investigation.',
                ]);
        }

        $service->load([
            'laboratoryTestParameters' => function ($query) {
                $query
                    ->orderBy('sort_order')
                    ->orderBy('id');
            },
        ]);

        $resultTypes = [
            'numeric' => 'Numeric',
            'text' => 'Text',
            'positive_negative' => 'Positive / Negative',
            'select' => 'Select / Categorical',
        ];

        return view(
            'admin.laboratory-parameters.edit',
            compact(
                'service',
                'resultTypes'
            )
        );
    }

    /**
     * Save the full parameter set for a laboratory service.
     */
    public function update(
        Request $request,
        Service $service
    ) {
        if ($service->category !== 'laboratory') {
            return redirect()
                ->route('admin.laboratory-parameters.index')
                ->withErrors([
                    'service' => 'This service is not a laboratory investigation.',
                ]);
        }

        $resultTypes = [
            'numeric',
            'text',
            'positive_negative',
            'select',
        ];

        $validated = $request->validate([
            'parameters' => [
                'nullable',
                'array',
                'max:200',
            ],

            'parameters.*.id' => [
                'nullable',
                'integer',
            ],

            'parameters.*.parameter_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'parameters.*.unit' => [
                'nullable',
                'string',
                'max:100',
            ],

            'parameters.*.method' => [
                'nullable',
                'string',
                'max:255',
            ],

            'parameters.*.result_type' => [
                'nullable',
                'string',
                Rule::in($resultTypes),
            ],

            'parameters.*.reference_range' => [
                'nullable',
                'string',
                'max:255',
            ],

            'parameters.*.low_value' => [
                'nullable',
                'numeric',
            ],

            'parameters.*.high_value' => [
                'nullable',
                'numeric',
            ],

            'parameters.*.critical_low' => [
                'nullable',
                'numeric',
            ],

            'parameters.*.critical_high' => [
                'nullable',
                'numeric',
            ],

            'parameters.*.is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $parameters = collect(
            $validated['parameters'] ?? []
        )
            ->map(function ($parameter) {
                return [
                    'id' =>
                        $parameter['id']
                        ?? null,

                    'parameter_name' =>
                        trim(
                            (string) (
                                $parameter['parameter_name']
                                ?? ''
                            )
                        ),

                    'unit' =>
                        trim(
                            (string) (
                                $parameter['unit']
                                ?? ''
                            )
                        ),

                    'method' =>
                        trim(
                            (string) (
                                $parameter['method']
                                ?? ''
                            )
                        ),

                    'result_type' =>
                        $parameter['result_type']
                        ?? 'numeric',

                    'reference_range' =>
                        trim(
                            (string) (
                                $parameter['reference_range']
                                ?? ''
                            )
                        ),

                    'low_value' =>
                        $parameter['low_value']
                        ?? null,

                    'high_value' =>
                        $parameter['high_value']
                        ?? null,

                    'critical_low' =>
                        $parameter['critical_low']
                        ?? null,

                    'critical_high' =>
                        $parameter['critical_high']
                        ?? null,

                    'is_active' =>
                        (bool) (
                            $parameter['is_active']
                            ?? false
                        ),
                ];
            })
            ->filter(function ($parameter) {
                return $parameter['parameter_name'] !== '';
            })
            ->values();

        DB::transaction(function () use (
            $service,
            $parameters
        ) {
            $savedIds = [];

            foreach (
                $parameters as $index => $parameter
            ) {
                $existing = null;

                if ($parameter['id']) {
                    $existing =
                        LaboratoryTestParameter::query()
                            ->where(
                                'service_id',
                                $service->id
                            )
                            ->whereKey(
                                $parameter['id']
                            )
                            ->first();
                }

                if (! $existing) {
                    $existing =
                        new LaboratoryTestParameter();

                    $existing->service_id =
                        $service->id;
                }

                $existing->parameter_name =
                    $parameter['parameter_name'];

                $existing->unit =
                    $parameter['unit'] !== ''
                        ? $parameter['unit']
                        : null;

                $existing->method =
                    $parameter['method'] !== ''
                        ? $parameter['method']
                        : null;

                $existing->result_type =
                    $parameter['result_type'];

                $existing->reference_range =
                    $parameter['reference_range'] !== ''
                        ? $parameter['reference_range']
                        : null;

                $existing->low_value =
                    $parameter['low_value'];

                $existing->high_value =
                    $parameter['high_value'];

                $existing->critical_low =
                    $parameter['critical_low'];

                $existing->critical_high =
                    $parameter['critical_high'];

                $existing->sort_order =
                    $index + 1;

                $existing->is_active =
                    $parameter['is_active'];

                $existing->save();

                $savedIds[] =
                    $existing->id;
            }

            LaboratoryTestParameter::query()
                ->where(
                    'service_id',
                    $service->id
                )
                ->when(
                    count($savedIds) > 0,
                    function ($query) use ($savedIds) {
                        $query->whereNotIn(
                            'id',
                            $savedIds
                        );
                    }
                )
                ->delete();
        });

        return redirect()
            ->route(
                'admin.laboratory-parameters.edit',
                $service
            )
            ->with(
                'success',
                'Laboratory test parameters updated successfully.'
            );
    }
}
