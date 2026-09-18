<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                New Internal Stock Transfer
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Create a transfer between hospital stock locations.
            </p>
        </div>
    </x-slot>


    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="font-semibold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <form
                method="POST"
                action="{{ route('pharmacy.stock-transfers.store') }}"
            >
                @csrf


                <div class="mb-6 rounded-xl bg-white p-6 shadow-sm">

                    <h3 class="mb-5 text-lg font-semibold text-gray-900">
                        Transfer Details
                    </h3>


                    <div class="grid gap-5 md:grid-cols-3">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Transfer Date
                            </label>

                            <input
                                type="date"
                                name="transfer_date"
                                value="{{ old('transfer_date', now()->toDateString()) }}"
                                required
                                class="w-full rounded-lg border-gray-300"
                            >
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                From Location
                            </label>

                            <select
                                name="from_location_id"
                                required
                                class="w-full rounded-lg border-gray-300"
                            >
                                <option value="">
                                    Select source
                                </option>

                                @foreach ($locations as $location)
                                    <option
                                        value="{{ $location->id }}"
                                        @selected(
                                            old('from_location_id') == $location->id
                                        )
                                    >
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                To Location
                            </label>

                            <select
                                name="to_location_id"
                                required
                                class="w-full rounded-lg border-gray-300"
                            >
                                <option value="">
                                    Select destination
                                </option>

                                @foreach ($locations as $location)
                                    <option
                                        value="{{ $location->id }}"
                                        @selected(
                                            old('to_location_id') == $location->id
                                        )
                                    >
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>


                    <div class="mt-5">
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Remarks
                        </label>

                        <textarea
                            name="remarks"
                            rows="2"
                            class="w-full rounded-lg border-gray-300"
                            placeholder="Optional transfer note"
                        >{{ old('remarks') }}</textarea>
                    </div>

                </div>


                <div class="mb-6 overflow-hidden rounded-xl bg-white shadow-sm">

                    <div class="flex items-center justify-between border-b border-gray-200 p-5">

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Medicines
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Add one or more medicine batches.
                            </p>
                        </div>


                        <button
                            type="button"
                            id="addRow"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            + Add Medicine
                        </button>
                    </div>


                    <div class="overflow-x-auto">
                        <table class="min-w-full">

                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                        Medicine / Batch
                                    </th>

                                    <th class="w-40 px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                        Quantity
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                        Remarks
                                    </th>

                                    <th class="w-20 px-4 py-3"></th>
                                </tr>
                            </thead>


                            <tbody
                                id="itemsBody"
                                class="divide-y divide-gray-100"
                            >
                            </tbody>

                        </table>
                    </div>

                </div>


                <div class="flex justify-end gap-3">

                    <a
                        href="{{ route('pharmacy.stock-transfers.index') }}"
                        class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Create Draft Transfer
                    </button>

                </div>

            </form>

        </div>
    </div>


    @php
        $batchOptions = $batches->map(function ($batch) {

            $medicine =
                $batch->medicine?->generic_name
                ?? 'Medicine';

            if ($batch->medicine?->brand_name) {
                $medicine .=
                    ' — '
                    . $batch->medicine->brand_name;
            }

            if ($batch->medicine?->strength) {
                $medicine .=
                    ' '
                    . $batch->medicine->strength;
            }

            $medicine .=
                ' | Batch: '
                . $batch->batch_number;

            if ($batch->expiry_date) {
                $medicine .=
                    ' | Exp: '
                    . $batch->expiry_date->format('m/Y');
            }

            return [
                'id' => $batch->id,
                'label' => $medicine,
            ];
        })->values();
    @endphp


    <script>
        const batches = @json($batchOptions);

        let rowIndex = 0;


        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        }


        function batchOptionsHtml() {

            let html =
                '<option value="">Select medicine / batch</option>';


            batches.forEach(batch => {

                html +=
                    '<option value="' +
                    batch.id +
                    '">' +
                    escapeHtml(batch.label) +
                    '</option>';
            });


            return html;
        }


        function addRow() {

            const tbody =
                document.getElementById('itemsBody');


            const tr =
                document.createElement('tr');


            tr.innerHTML = `
                <td class="px-4 py-3">
                    <select
                        name="items[${rowIndex}][pharmacy_stock_batch_id]"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                        ${batchOptionsHtml()}
                    </select>
                </td>

                <td class="px-4 py-3">
                    <input
                        type="number"
                        min="1"
                        name="items[${rowIndex}][quantity_requested]"
                        required
                        class="w-full rounded-lg border-gray-300 text-sm"
                    >
                </td>

                <td class="px-4 py-3">
                    <input
                        type="text"
                        name="items[${rowIndex}][remarks]"
                        class="w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Optional"
                    >
                </td>

                <td class="px-4 py-3 text-center">
                    <button
                        type="button"
                        class="removeRow text-sm font-semibold text-red-600"
                    >
                        Remove
                    </button>
                </td>
            `;


            tbody.appendChild(tr);


            tr.querySelector(
                '.removeRow'
            ).addEventListener(
                'click',
                function () {

                    tr.remove();


                    if (
                        document.querySelectorAll(
                            '#itemsBody tr'
                        ).length === 0
                    ) {
                        addRow();
                    }
                }
            );


            rowIndex++;
        }


        document
            .getElementById('addRow')
            .addEventListener(
                'click',
                addRow
            );


        addRow();
    </script>

</x-app-layout>