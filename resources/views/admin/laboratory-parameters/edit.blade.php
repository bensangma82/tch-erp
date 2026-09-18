<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Configure Laboratory Parameters
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $service->name }}
                    @if ($service->code)
                        · {{ $service->code }}
                    @endif
                </p>
            </div>

            <a
                href="{{ route('admin.laboratory-parameters.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back to Parameter Master
            </a>

        </div>
    </x-slot>


    @php
        $oldParameters = old('parameters');

        if (is_array($oldParameters)) {
            $parameterRows = $oldParameters;
        } elseif ($service->laboratoryTestParameters->count()) {
            $parameterRows = $service->laboratoryTestParameters
                ->map(function ($parameter) {
                    return [
                        'id' => $parameter->id,
                        'parameter_name' => $parameter->parameter_name,
                        'unit' => $parameter->unit,
                        'reference_range' => $parameter->reference_range,
                        'low_value' => $parameter->low_value,
                        'high_value' => $parameter->high_value,
                        'critical_low' => $parameter->critical_low,
                        'critical_high' => $parameter->critical_high,
                        'is_active' => $parameter->is_active,
                    ];
                })
                ->values()
                ->toArray();
        } else {
            $parameterRows = [
                [
                    'id' => null,
                    'parameter_name' => '',
                    'unit' => '',
                    'reference_range' => '',
                    'low_value' => '',
                    'high_value' => '',
                    'critical_low' => '',
                    'critical_high' => '',
                    'is_active' => true,
                ],
            ];
        }
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


            <div class="mb-6 grid gap-4 md:grid-cols-4">

                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Investigation
                    </div>

                    <div class="mt-1 text-base font-bold text-slate-900">
                        {{ $service->name }}
                    </div>
                </div>


                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Service Code
                    </div>

                    <div class="mt-1 text-base font-bold text-slate-900">
                        {{ $service->code ?: '—' }}
                    </div>
                </div>


                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Sample Required
                    </div>

                    <div class="mt-1">
                        @if ($service->requires_sample)
                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                Yes
                            </span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                No
                            </span>
                        @endif
                    </div>
                </div>


                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Active Parameters
                    </div>

                    <div class="mt-1 text-base font-bold text-slate-900">
                        {{ $service->laboratoryTestParameters->where('is_active', true)->count() }}
                    </div>
                </div>

            </div>


            <form
                method="POST"
                action="{{ route('admin.laboratory-parameters.update', $service) }}"
                id="parameter-form"
            >

                @csrf
                @method('PUT')


                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h3 class="text-base font-semibold text-slate-900">
                                Result Parameters
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Configure units, reference ranges and thresholds used for automatic abnormal-result flagging.
                            </p>
                        </div>


                        <button
                            type="button"
                            id="add-parameter"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            + Add Parameter
                        </button>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="min-w-[1450px] w-full">

                            <thead class="border-b border-slate-200 bg-white">

                                <tr>

                                    <th class="w-[240px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Parameter
                                    </th>

                                    <th class="w-[120px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Unit
                                    </th>

                                    <th class="w-[180px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Display Range
                                    </th>

                                    <th class="w-[130px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Low
                                    </th>

                                    <th class="w-[130px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        High
                                    </th>

                                    <th class="w-[130px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Critical Low
                                    </th>

                                    <th class="w-[130px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Critical High
                                    </th>

                                    <th class="w-[100px] px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Active
                                    </th>

                                    <th class="w-[90px] px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody
                                id="parameter-rows"
                                class="divide-y divide-slate-100"
                            >

                                @foreach ($parameterRows as $index => $parameter)

                                    <tr class="parameter-row">

                                        <td class="px-4 py-3">

                                            <input
                                                type="hidden"
                                                data-field="id"
                                                name="parameters[{{ $index }}][id]"
                                                value="{{ $parameter['id'] ?? '' }}"
                                            >

                                            <input
                                                type="text"
                                                data-field="parameter_name"
                                                name="parameters[{{ $index }}][parameter_name]"
                                                value="{{ $parameter['parameter_name'] ?? '' }}"
                                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="e.g. Hemoglobin"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="text"
                                                data-field="unit"
                                                name="parameters[{{ $index }}][unit]"
                                                value="{{ $parameter['unit'] ?? '' }}"
                                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="g/dL"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="text"
                                                data-field="reference_range"
                                                name="parameters[{{ $index }}][reference_range]"
                                                value="{{ $parameter['reference_range'] ?? '' }}"
                                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="12 - 16"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="number"
                                                step="any"
                                                data-field="low_value"
                                                name="parameters[{{ $index }}][low_value]"
                                                value="{{ $parameter['low_value'] ?? '' }}"
                                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="12"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="number"
                                                step="any"
                                                data-field="high_value"
                                                name="parameters[{{ $index }}][high_value]"
                                                value="{{ $parameter['high_value'] ?? '' }}"
                                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                placeholder="16"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="number"
                                                step="any"
                                                data-field="critical_low"
                                                name="parameters[{{ $index }}][critical_low]"
                                                value="{{ $parameter['critical_low'] ?? '' }}"
                                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-400 focus:ring-red-400"
                                                placeholder="Critical low"
                                            >

                                        </td>


                                        <td class="px-4 py-3">

                                            <input
                                                type="number"
                                                step="any"
                                                data-field="critical_high"
                                                name="parameters[{{ $index }}][critical_high]"
                                                value="{{ $parameter['critical_high'] ?? '' }}"
                                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-400 focus:ring-red-400"
                                                placeholder="Critical high"
                                            >

                                        </td>


                                        <td class="px-4 py-3 text-center">

                                            <input
                                                type="hidden"
                                                data-active-hidden
                                                name="parameters[{{ $index }}][is_active]"
                                                value="0"
                                            >

                                            <input
                                                type="checkbox"
                                                data-field="is_active"
                                                name="parameters[{{ $index }}][is_active]"
                                                value="1"
                                                @checked((bool) ($parameter['is_active'] ?? true))
                                                class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500"
                                            >

                                        </td>


                                        <td class="px-4 py-3 text-center">

                                            <button
                                                type="button"
                                                class="remove-parameter inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                                            >
                                                Remove
                                            </button>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">

                        <div class="grid gap-4 md:grid-cols-2">

                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                <div class="text-sm font-semibold text-blue-800">
                                    Display Reference Range
                                </div>

                                <p class="mt-1 text-xs leading-5 text-blue-700">
                                    This is what appears on the printed laboratory report, for example:
                                    <strong>12.0 - 16.0</strong>.
                                </p>
                            </div>


                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <div class="text-sm font-semibold text-amber-800">
                                    Numeric Limits
                                </div>

                                <p class="mt-1 text-xs leading-5 text-amber-700">
                                    Low, High and Critical limits will later allow automatic L, H, CL and CH result flags.
                                </p>
                            </div>

                        </div>

                    </div>


                    <div class="flex flex-col gap-3 border-t border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                        <a
                            href="{{ route('admin.laboratory-parameters.index') }}"
                            class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            Save Parameters
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <template id="parameter-row-template">

        <tr class="parameter-row">

            <td class="px-4 py-3">

                <input
                    type="hidden"
                    data-field="id"
                    value=""
                >

                <input
                    type="text"
                    data-field="parameter_name"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    placeholder="e.g. Hemoglobin"
                >

            </td>


            <td class="px-4 py-3">

                <input
                    type="text"
                    data-field="unit"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    placeholder="g/dL"
                >

            </td>


            <td class="px-4 py-3">

                <input
                    type="text"
                    data-field="reference_range"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    placeholder="12 - 16"
                >

            </td>


            <td class="px-4 py-3">

                <input
                    type="number"
                    step="any"
                    data-field="low_value"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >

            </td>


            <td class="px-4 py-3">

                <input
                    type="number"
                    step="any"
                    data-field="high_value"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >

            </td>


            <td class="px-4 py-3">

                <input
                    type="number"
                    step="any"
                    data-field="critical_low"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-400 focus:ring-red-400"
                >

            </td>


            <td class="px-4 py-3">

                <input
                    type="number"
                    step="any"
                    data-field="critical_high"
                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-400 focus:ring-red-400"
                >

            </td>


            <td class="px-4 py-3 text-center">

                <input
                    type="hidden"
                    data-active-hidden
                    value="0"
                >

                <input
                    type="checkbox"
                    data-field="is_active"
                    value="1"
                    checked
                    class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500"
                >

            </td>


            <td class="px-4 py-3 text-center">

                <button
                    type="button"
                    class="remove-parameter inline-flex rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                >
                    Remove
                </button>

            </td>

        </tr>

    </template>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const rowsContainer =
                document.getElementById('parameter-rows');

            const addButton =
                document.getElementById('add-parameter');

            const template =
                document.getElementById('parameter-row-template');


            function renumberRows() {

                const rows =
                    rowsContainer.querySelectorAll('.parameter-row');

                rows.forEach(function (row, index) {

                    row.querySelectorAll('[data-field]').forEach(function (field) {

                        const key =
                            field.dataset.field;

                        field.name =
                            `parameters[${index}][${key}]`;

                    });


                    const hidden =
                        row.querySelector('[data-active-hidden]');

                    if (hidden) {
                        hidden.name =
                            `parameters[${index}][is_active]`;
                    }

                });

            }


            function addRow() {

                const clone =
                    template.content.cloneNode(true);

                rowsContainer.appendChild(clone);

                renumberRows();

                const rows =
                    rowsContainer.querySelectorAll('.parameter-row');

                rows[
                    rows.length - 1
                ]
                    ?.querySelector('[data-field="parameter_name"]')
                    ?.focus();

            }


            function removeRow(button) {

                const rows =
                    rowsContainer.querySelectorAll('.parameter-row');

                if (rows.length === 1) {

                    const row =
                        button.closest('.parameter-row');

                    row.querySelectorAll(
                        'input[type="text"], input[type="number"]'
                    ).forEach(function (input) {
                        input.value = '';
                    });

                    row.querySelector(
                        '[data-field="id"]'
                    ).value = '';

                    const checkbox =
                        row.querySelector(
                            '[data-field="is_active"]'
                        );

                    if (checkbox) {
                        checkbox.checked = true;
                    }

                    return;
                }

                button.closest('.parameter-row')?.remove();

                renumberRows();

            }


            addButton?.addEventListener(
                'click',
                addRow
            );


            rowsContainer?.addEventListener(
                'click',
                function (event) {

                    const button =
                        event.target.closest('.remove-parameter');

                    if (!button) {
                        return;
                    }

                    removeRow(button);

                }
            );


            document
                .getElementById('parameter-form')
                ?.addEventListener(
                    'submit',
                    renumberRows
                );


            renumberRows();

        });
    </script>

</x-app-layout>