<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Laboratory Result Entry
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Enter structured laboratory results using the configured test parameter master.
                </p>
            </div>

            <a
                href="{{ route('laboratory.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back to Laboratory
            </a>

        </div>
    </x-slot>


    @php
        $order = $serviceOrderItem->serviceOrder;
        $patient = $order?->patient;
        $encounter = $order?->encounter;
        $existingResult = $serviceOrderItem->diagnosticResult;
        $sample = $serviceOrderItem->diagnosticSample;

        $masterParameters =
            $serviceOrderItem->service?->laboratoryTestParameters
                ?->where('is_active', true)
                ->sortBy('sort_order')
                ->values()
                ?? collect();

        $oldParameters = old('parameters');

        if (is_array($oldParameters) && count($oldParameters) > 0) {

            $parameterRows = collect($oldParameters)->map(function ($parameter) {
                return [
                    'parameter_name' => $parameter['parameter_name'] ?? '',
                    'result_value' => $parameter['result_value'] ?? '',
                    'unit' => $parameter['unit'] ?? '',
                    'reference_range' => $parameter['reference_range'] ?? '',
                    'flag' => $parameter['flag'] ?? '',
                    'remarks' => $parameter['remarks'] ?? '',
                    'low_value' => $parameter['low_value'] ?? '',
                    'high_value' => $parameter['high_value'] ?? '',
                    'critical_low' => $parameter['critical_low'] ?? '',
                    'critical_high' => $parameter['critical_high'] ?? '',
                ];
            })->values();

        } elseif ($existingResult && $existingResult->items->count() > 0) {

            $parameterRows = $existingResult->items
                ->map(function ($item) use ($masterParameters) {

                    $master = $masterParameters->first(function ($parameter) use ($item) {
                        return trim(strtolower($parameter->parameter_name))
                            === trim(strtolower($item->parameter_name));
                    });

                    return [
                        'parameter_name' => $item->parameter_name,
                        'result_value' => $item->result_value,
                        'unit' => $item->unit,
                        'reference_range' => $item->reference_range,
                        'flag' => $item->flag,
                        'remarks' => $item->remarks,
                        'low_value' => $master?->low_value,
                        'high_value' => $master?->high_value,
                        'critical_low' => $master?->critical_low,
                        'critical_high' => $master?->critical_high,
                    ];
                })
                ->values();

        } elseif ($masterParameters->count() > 0) {

            $parameterRows = $masterParameters
                ->map(function ($parameter) {
                    return [
                        'parameter_name' => $parameter->parameter_name,
                        'result_value' => '',
                        'unit' => $parameter->unit,
                        'reference_range' => $parameter->reference_range,
                        'flag' => '',
                        'remarks' => '',
                        'low_value' => $parameter->low_value,
                        'high_value' => $parameter->high_value,
                        'critical_low' => $parameter->critical_low,
                        'critical_high' => $parameter->critical_high,
                    ];
                })
                ->values();

        } else {

            $parameterRows = collect([
                [
                    'parameter_name' => '',
                    'result_value' => '',
                    'unit' => '',
                    'reference_range' => '',
                    'flag' => '',
                    'remarks' => '',
                    'low_value' => '',
                    'high_value' => '',
                    'critical_low' => '',
                    'critical_high' => '',
                ],
            ]);
        }

        $overallComment = old(
            'overall_comment',
            $existingResult?->result_text
        );
    @endphp


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <div class="font-semibold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-inside list-disc text-sm text-red-700">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif


            <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="grid gap-0 lg:grid-cols-3">

                    <div class="border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Patient
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ $patient?->full_name ?? '—' }}
                        </div>

                        <div class="mt-3 space-y-1 text-sm text-slate-600">
                            <div>
                                UHID:
                                <span class="font-semibold text-slate-800">
                                    {{ $patient?->uhid ?? '—' }}
                                </span>
                            </div>

                            <div>
                                MRD:
                                <span class="font-semibold text-slate-800">
                                    {{ $patient?->mrd_number ?: '—' }}
                                </span>
                            </div>

                            <div>
                                @if ($patient?->age !== null)
                                    {{ $patient->age }} yrs
                                @else
                                    Age —
                                @endif
                                /
                                {{ $patient?->sex ?: '—' }}
                            </div>
                        </div>

                    </div>


                    <div class="border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Investigation
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ $serviceOrderItem->service_name }}
                        </div>

                        <div class="mt-3 space-y-1 text-sm text-slate-600">

                            <div>
                                Code:
                                <span class="font-semibold text-slate-800">
                                    {{ $serviceOrderItem->service_code ?: '—' }}
                                </span>
                            </div>

                            <div>
                                Order:
                                <span class="font-semibold text-slate-800">
                                    {{ $order?->order_no ?? '—' }}
                                </span>
                            </div>

                            <div>
                                Doctor:
                                <span class="font-semibold text-slate-800">
                                    {{ $encounter?->doctor?->full_name ?? 'Unassigned' }}
                                </span>
                            </div>

                        </div>

                    </div>


                    <div class="p-5">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Sample
                        </div>

                        @if ($serviceOrderItem->requires_sample && $sample)

                            <div class="mt-1 text-lg font-bold text-emerald-700">
                                {{ $sample->sample_no }}
                            </div>

                            <div class="mt-3 space-y-1 text-sm text-slate-600">

                                <div>
                                    Specimen:
                                    <span class="font-semibold text-slate-800">
                                        {{ $sample->specimen_type ?? '—' }}
                                    </span>
                                </div>

                                <div>
                                    Collected:
                                    <span class="font-semibold text-slate-800">
                                        {{ $sample->collected_at?->format('d M Y, h:i A') ?? '—' }}
                                    </span>
                                </div>

                                <div>
                                    By:
                                    <span class="font-semibold text-slate-800">
                                        {{ $sample->collectedBy?->name ?? '—' }}
                                    </span>
                                </div>

                            </div>

                        @elseif ($serviceOrderItem->requires_sample)

                            <div class="mt-2 text-sm font-semibold text-amber-700">
                                Sample information unavailable
                            </div>

                        @else

                            <div class="mt-2 text-sm text-slate-500">
                                No sample required
                            </div>

                        @endif

                    </div>

                </div>

            </div>


            @if ($masterParameters->count() === 0)

                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">

                    <div class="font-semibold text-amber-800">
                        No parameter master configured
                    </div>

                    <p class="mt-1 text-sm text-amber-700">
                        This test has no active Laboratory Parameter Master entries. You may still add rows manually, but configuring the master is recommended.
                    </p>

                </div>

            @else

                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4">

                    <div class="font-semibold text-emerald-800">
                        Parameter Master Loaded
                    </div>

                    <p class="mt-1 text-sm text-emerald-700">
                        {{ $masterParameters->count() }}
                        configured parameter{{ $masterParameters->count() === 1 ? '' : 's' }}
                        loaded automatically for
                        {{ $serviceOrderItem->service_name }}.
                    </p>

                </div>

            @endif


            @if ($existingResult?->status === 'draft')

                <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">

                    <div class="font-semibold text-blue-800">
                        Draft Result
                    </div>

                    <p class="mt-1 text-sm text-blue-700">
                        Saved values have been restored and may still be edited.
                    </p>

                </div>

            @endif


            <form
                id="laboratory-result-form"
                method="POST"
                action="{{ route('diagnostics.items.result.save', $serviceOrderItem) }}"
            >

                @csrf

                <textarea
                    id="result_text"
                    name="result_text"
                    class="hidden"
                >{{ old('result_text', $existingResult?->result_text) }}</textarea>


                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h3 class="text-base font-semibold text-slate-900">
                                Test Results
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Enter values. Flags are calculated automatically from configured numeric limits when possible.
                            </p>
                        </div>


                        <button
                            type="button"
                            id="add-result-row"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            + Add Parameter
                        </button>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="min-w-[1150px] w-full">

                            <thead class="border-b border-slate-200 bg-white">

                                <tr>
                                    <th class="w-[260px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Parameter
                                    </th>

                                    <th class="w-[160px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Result
                                    </th>

                                    <th class="w-[130px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Unit
                                    </th>

                                    <th class="w-[200px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Reference Range
                                    </th>

                                    <th class="w-[150px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Flag
                                    </th>

                                    <th class="w-[100px] px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Action
                                    </th>
                                </tr>

                            </thead>


                            <tbody
                                id="result-rows"
                                class="divide-y divide-slate-100"
                            >

                                @foreach ($parameterRows as $index => $parameter)

                                    <tr class="result-row">

                                        <td class="px-4 py-3">

                                            <input
                                                type="text"
                                                data-field="parameter_name"
                                                name="parameters[{{ $index }}][parameter_name]"
                                                value="{{ $parameter['parameter_name'] ?? '' }}"
                                                class="parameter-name block w-full rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="Parameter name"
                                            >

                                            <input
                                                type="hidden"
                                                data-field="low_value"
                                                name="parameters[{{ $index }}][low_value]"
                                                value="{{ $parameter['low_value'] ?? '' }}"
                                            >

                                            <input
                                                type="hidden"
                                                data-field="high_value"
                                                name="parameters[{{ $index }}][high_value]"
                                                value="{{ $parameter['high_value'] ?? '' }}"
                                            >

                                            <input
                                                type="hidden"
                                                data-field="critical_low"
                                                name="parameters[{{ $index }}][critical_low]"
                                                value="{{ $parameter['critical_low'] ?? '' }}"
                                            >

                                            <input
                                                type="hidden"
                                                data-field="critical_high"
                                                name="parameters[{{ $index }}][critical_high]"
                                                value="{{ $parameter['critical_high'] ?? '' }}"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="text"
                                                data-field="result_value"
                                                name="parameters[{{ $index }}][result_value]"
                                                value="{{ $parameter['result_value'] ?? '' }}"
                                                class="result-value block w-full rounded-lg border-slate-300 text-sm font-bold shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="Result"
                                                autocomplete="off"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="text"
                                                data-field="unit"
                                                name="parameters[{{ $index }}][unit]"
                                                value="{{ $parameter['unit'] ?? '' }}"
                                                class="unit block w-full rounded-lg border-slate-300 bg-slate-50 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="Unit"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="text"
                                                data-field="reference_range"
                                                name="parameters[{{ $index }}][reference_range]"
                                                value="{{ $parameter['reference_range'] ?? '' }}"
                                                class="reference-range block w-full rounded-lg border-slate-300 bg-slate-50 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="Reference range"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <select
                                                data-field="flag"
                                                name="parameters[{{ $index }}][flag]"
                                                class="flag block w-full rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                            >
                                                <option value="" @selected(($parameter['flag'] ?? '') === '')>
                                                    Normal
                                                </option>

                                                <option value="low" @selected(($parameter['flag'] ?? '') === 'low')>
                                                    Low
                                                </option>

                                                <option value="high" @selected(($parameter['flag'] ?? '') === 'high')>
                                                    High
                                                </option>

                                                <option value="critical_low" @selected(($parameter['flag'] ?? '') === 'critical_low')>
                                                    Critical Low
                                                </option>

                                                <option value="critical_high" @selected(($parameter['flag'] ?? '') === 'critical_high')>
                                                    Critical High
                                                </option>

                                                <option value="abnormal" @selected(($parameter['flag'] ?? '') === 'abnormal')>
                                                    Abnormal
                                                </option>
                                            </select>

                                        </td>


                                        <td class="px-4 py-3 text-center">

                                            <button
                                                type="button"
                                                class="remove-result-row inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                                            >
                                                Remove
                                            </button>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    <div class="border-t border-slate-200 px-6 py-5">

                        <label
                            for="overall_comment"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Comments / Interpretation
                        </label>

                        <p class="mt-1 text-xs text-slate-500">
                            Optional overall laboratory comment or interpretation.
                        </p>

                        <textarea
                            id="overall_comment"
                            name="overall_comment"
                            rows="3"
                            maxlength="10000"
                            class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Optional laboratory comment..."
                        >{{ $overallComment }}</textarea>

                    </div>


                    <div class="grid gap-4 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:grid-cols-2">

                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <div class="text-sm font-semibold text-blue-800">
                                Save Draft
                            </div>

                            <p class="mt-1 text-xs leading-5 text-blue-700">
                                Saves all structured values and keeps the investigation in process.
                            </p>
                        </div>


                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div class="text-sm font-semibold text-amber-800">
                                Finalize Result
                            </div>

                            <p class="mt-1 text-xs leading-5 text-amber-700">
                                At least one result value is required. Finalizing currently marks the investigation completed.
                            </p>
                        </div>

                    </div>


                    <div class="flex flex-col gap-3 border-t border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                        <a
                            href="{{ route('laboratory.index') }}"
                            class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Back to Laboratory
                        </a>


                        <div class="flex flex-col gap-3 sm:flex-row">

                            <button
                                type="submit"
                                name="action"
                                value="save_draft"
                                class="inline-flex justify-center rounded-lg border border-blue-600 bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50"
                            >
                                Save Draft
                            </button>


                            <button
                                type="submit"
                                name="action"
                                value="finalize"
                                class="inline-flex justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                Finalize Result
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <template id="result-row-template">

        <tr class="result-row">

            <td class="px-4 py-3">

                <input
                    type="text"
                    data-field="parameter_name"
                    class="parameter-name block w-full rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    placeholder="Parameter name"
                >

                <input type="hidden" data-field="low_value" value="">
                <input type="hidden" data-field="high_value" value="">
                <input type="hidden" data-field="critical_low" value="">
                <input type="hidden" data-field="critical_high" value="">

            </td>


            <td class="px-4 py-3">

                <input
                    type="text"
                    data-field="result_value"
                    class="result-value block w-full rounded-lg border-slate-300 text-sm font-bold shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    placeholder="Result"
                    autocomplete="off"
                >

            </td>


            <td class="px-4 py-3">

                <input
                    type="text"
                    data-field="unit"
                    class="unit block w-full rounded-lg border-slate-300 bg-slate-50 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    placeholder="Unit"
                >

            </td>


            <td class="px-4 py-3">

                <input
                    type="text"
                    data-field="reference_range"
                    class="reference-range block w-full rounded-lg border-slate-300 bg-slate-50 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    placeholder="Reference range"
                >

            </td>


            <td class="px-4 py-3">

                <select
                    data-field="flag"
                    class="flag block w-full rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >
                    <option value="">Normal</option>
                    <option value="low">Low</option>
                    <option value="high">High</option>
                    <option value="critical_low">Critical Low</option>
                    <option value="critical_high">Critical High</option>
                    <option value="abnormal">Abnormal</option>
                </select>

            </td>


            <td class="px-4 py-3 text-center">

                <button
                    type="button"
                    class="remove-result-row inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                >
                    Remove
                </button>

            </td>

        </tr>

    </template>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const form =
                document.getElementById('laboratory-result-form');

            const rowsContainer =
                document.getElementById('result-rows');

            const addButton =
                document.getElementById('add-result-row');

            const template =
                document.getElementById('result-row-template');

            const hiddenResult =
                document.getElementById('result_text');

            const overallComment =
                document.getElementById('overall_comment');


            function renumberRows() {

                rowsContainer
                    .querySelectorAll('.result-row')
                    .forEach(function (row, index) {

                        row.querySelectorAll('[data-field]').forEach(function (field) {

                            field.name =
                                `parameters[${index}][${field.dataset.field}]`;

                        });

                    });

            }


            function parseNumeric(value) {

                if (value === null || value === undefined) {
                    return null;
                }

                const cleaned =
                    String(value)
                        .replace(/,/g, '')
                        .trim();

                if (cleaned === '') {
                    return null;
                }

                const number =
                    Number(cleaned);

                return Number.isFinite(number)
                    ? number
                    : null;

            }


            function calculateFlag(row) {

                const resultInput =
                    row.querySelector('[data-field="result_value"]');

                const flagSelect =
                    row.querySelector('[data-field="flag"]');

                if (!resultInput || !flagSelect) {
                    return;
                }

                const value =
                    parseNumeric(resultInput.value);

                if (value === null) {
                    return;
                }

                const low =
                    parseNumeric(
                        row.querySelector('[data-field="low_value"]')?.value
                    );

                const high =
                    parseNumeric(
                        row.querySelector('[data-field="high_value"]')?.value
                    );

                const criticalLow =
                    parseNumeric(
                        row.querySelector('[data-field="critical_low"]')?.value
                    );

                const criticalHigh =
                    parseNumeric(
                        row.querySelector('[data-field="critical_high"]')?.value
                    );


                if (
                    criticalLow !== null
                    && value < criticalLow
                ) {
                    flagSelect.value = 'critical_low';

                } else if (
                    criticalHigh !== null
                    && value > criticalHigh
                ) {
                    flagSelect.value = 'critical_high';

                } else if (
                    low !== null
                    && value < low
                ) {
                    flagSelect.value = 'low';

                } else if (
                    high !== null
                    && value > high
                ) {
                    flagSelect.value = 'high';

                } else {
                    flagSelect.value = '';
                }

            }


            function calculateAllFlags() {

                rowsContainer
                    .querySelectorAll('.result-row')
                    .forEach(calculateFlag);

            }


            function addRow() {

                const clone =
                    template.content.cloneNode(true);

                rowsContainer.appendChild(clone);

                renumberRows();

                const rows =
                    rowsContainer.querySelectorAll('.result-row');

                rows[rows.length - 1]
                    ?.querySelector('[data-field="parameter_name"]')
                    ?.focus();

            }


            function removeRow(button) {

                const rows =
                    rowsContainer.querySelectorAll('.result-row');

                if (rows.length === 1) {

                    const row =
                        button.closest('.result-row');

                    row.querySelectorAll(
                        'input[type="text"], input[type="hidden"]'
                    ).forEach(function (input) {
                        input.value = '';
                    });

                    const flag =
                        row.querySelector('[data-field="flag"]');

                    if (flag) {
                        flag.value = '';
                    }

                    return;
                }

                button.closest('.result-row')?.remove();

                renumberRows();

            }


            function buildCompatibilityResultText() {

                const lines = [];

                rowsContainer
                    .querySelectorAll('.result-row')
                    .forEach(function (row) {

                        const parameter =
                            row.querySelector('[data-field="parameter_name"]')
                                ?.value
                                .trim()
                            || '';

                        const result =
                            row.querySelector('[data-field="result_value"]')
                                ?.value
                                .trim()
                            || '';

                        const unit =
                            row.querySelector('[data-field="unit"]')
                                ?.value
                                .trim()
                            || '';

                        if (!parameter && !result) {
                            return;
                        }

                        let line =
                            (parameter || 'Result')
                            + ': '
                            + (result || '—');

                        if (unit) {
                            line += ' ' + unit;
                        }

                        lines.push(line);

                    });

                const comment =
                    overallComment?.value.trim()
                    || '';

                if (comment) {

                    if (lines.length) {
                        lines.push('');
                    }

                    lines.push(
                        'Comment: ' + comment
                    );

                }

                hiddenResult.value =
                    lines.join('\n');

            }


            addButton?.addEventListener(
                'click',
                addRow
            );


            rowsContainer?.addEventListener(
                'click',
                function (event) {

                    const button =
                        event.target.closest('.remove-result-row');

                    if (!button) {
                        return;
                    }

                    removeRow(button);

                }
            );


            rowsContainer?.addEventListener(
                'input',
                function (event) {

                    if (
                        event.target.matches(
                            '[data-field="result_value"]'
                        )
                    ) {
                        calculateFlag(
                            event.target.closest('.result-row')
                        );
                    }

                }
            );


            form?.addEventListener(
                'submit',
                function (event) {

                    renumberRows();

                    calculateAllFlags();

                    buildCompatibilityResultText();

                    if (
                        event.submitter
                        && event.submitter.value === 'finalize'
                    ) {

                        const hasResult =
                            Array.from(
                                rowsContainer.querySelectorAll(
                                    '[data-field="result_value"]'
                                )
                            ).some(function (input) {
                                return input.value.trim() !== '';
                            });

                        if (!hasResult) {

                            event.preventDefault();

                            alert(
                                'Enter at least one laboratory result before finalizing.'
                            );

                            return;
                        }


                        if (
                            ! confirm(
                                'Finalize this laboratory result? The investigation will be marked completed.'
                            )
                        ) {
                            event.preventDefault();
                            return;
                        }
                    }

                }
            );


            renumberRows();

            calculateAllFlags();

        });
    </script>

</x-app-layout>
