<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Investigation Billing
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Enter investigations from the doctor's paper requisition.
                </p>

            </div>

            <a
                href="{{ route('billing.index') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                Back to Billing Counter
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- PATIENT / ENCOUNTER DETAILS --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <h3 class="font-semibold text-gray-800">
                        Patient Encounter
                    </h3>

                </div>


                <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Patient
                        </div>

                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $encounter->patient->full_name }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            @if ($encounter->patient->age !== null)
                                {{ $encounter->patient->age }} yrs
                            @else
                                Age —
                            @endif

                            /

                            {{ $encounter->patient->sex ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            UHID / MRD
                        </div>

                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $encounter->patient->uhid }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            MRD: {{ $encounter->patient->mrd_number ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Department
                        </div>

                        <div class="mt-1 text-sm text-gray-900">
                            {{ $encounter->department?->name ?? '—' }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            Queue: {{ $encounter->queue_number }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Doctor
                        </div>

                        <div class="mt-1 text-sm text-gray-900">
                            {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            {{ $encounter->encounter_no }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">


                {{-- SERVICE SELECTION --}}
                <div class="lg:col-span-2">

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                                <div>

                                    <h3 class="font-semibold text-gray-800">
                                        Available Services
                                    </h3>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Select investigations written by the doctor.
                                    </p>

                                </div>


                                <div class="w-full sm:w-72">

                                    <input
                                        id="serviceSearch"
                                        type="text"
                                        placeholder="Search test or service..."
                                        autocomplete="off"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    >

                                </div>

                            </div>

                        </div>


                        <div class="max-h-[650px] overflow-y-auto">

                            <table class="min-w-full divide-y divide-gray-200">

                                <thead class="sticky top-0 bg-white">

                                    <tr>

                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Service
                                        </th>

                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Category
                                        </th>

                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Price
                                        </th>

                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody
                                    id="serviceTable"
                                    class="divide-y divide-gray-100"
                                >

                                    @forelse ($services as $service)

                                        <tr
                                            class="service-row hover:bg-gray-50"
                                            data-search="{{ strtolower(
                                                $service->code . ' ' .
                                                $service->name . ' ' .
                                                $service->category . ' ' .
                                                ($service->department?->name ?? '')
                                            ) }}"
                                        >

                                            <td class="px-5 py-4">

                                                <div class="text-sm font-semibold text-gray-900">
                                                    {{ $service->name }}
                                                </div>

                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ $service->code }}

                                                    @if ($service->department)

                                                        · {{ $service->department->name }}

                                                    @endif
                                                </div>

                                            </td>


                                            <td class="px-5 py-4">

                                                @if ($service->category === 'laboratory')

                                                    <span class="inline-flex whitespace-nowrap rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                                        Laboratory
                                                    </span>

                                                @elseif ($service->category === 'radiology')

                                                    <span class="inline-flex whitespace-nowrap rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                        Radiology
                                                    </span>

                                                @elseif ($service->category === 'procedure')

                                                    <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                                        Procedure
                                                    </span>

                                                @else

                                                    <span class="inline-flex whitespace-nowrap rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                                        {{ ucfirst($service->category) }}
                                                    </span>

                                                @endif

                                            </td>


                                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-gray-900">
                                                ₹{{ number_format((float) $service->price, 2) }}
                                            </td>


                                            <td class="px-5 py-4 text-right">

                                                <button
                                                    type="button"
                                                    class="add-service inline-flex whitespace-nowrap rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                                    data-id="{{ $service->id }}"
                                                    data-code="{{ $service->code }}"
                                                    data-name="{{ $service->name }}"
                                                    data-category="{{ $service->category }}"
                                                    data-price="{{ $service->price }}"
                                                >
                                                    Add
                                                </button>

                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td
                                                colspan="4"
                                                class="px-6 py-14 text-center text-sm text-gray-500"
                                            >
                                                No active services available.
                                            </td>

                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                {{-- SELECTED ITEMS --}}
                <div>

                    <form
    method="POST"
    action="{{ route('billing.store', $encounter) }}"
    id="billingForm"
>

                        @csrf


                        <div class="sticky top-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                            <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">

                                <h3 class="font-semibold text-gray-800">
                                    Selected Investigations
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Review before creating the bill.
                                </p>

                            </div>


                            <div class="p-5">

                                <div
                                    id="emptySelection"
                                    class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500"
                                >
                                    No investigations selected.
                                </div>


                                <div
                                    id="selectedItems"
                                    class="space-y-3"
                                ></div>


                                <div class="mt-5 border-t border-gray-200 pt-5">

                                    <div class="flex items-center justify-between">

                                        <span class="text-sm font-medium text-gray-600">
                                            Total
                                        </span>

                                        <span
                                            id="grandTotal"
                                            class="text-xl font-bold text-gray-900"
                                        >
                                            ₹0.00
                                        </span>

                                    </div>

                                </div>


                                <div class="mt-5">

                                    <label
                                        for="remarks"
                                        class="mb-1 block text-sm font-medium text-gray-700"
                                    >
                                        Billing Remarks
                                    </label>

                                    <textarea
                                        id="remarks"
                                        name="remarks"
                                        rows="2"
                                        placeholder="Optional"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    ></textarea>

                                </div>


                                <button
    type="submit"
    disabled
    id="continueButton"
    class="mt-5 w-full rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white opacity-50"
>
    Continue to Payment
</button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const selected = new Map();

            const searchInput =
                document.getElementById('serviceSearch');

            const serviceRows =
                document.querySelectorAll('.service-row');

            const selectedItems =
                document.getElementById('selectedItems');

            const emptySelection =
                document.getElementById('emptySelection');

            const grandTotal =
                document.getElementById('grandTotal');

            const continueButton =
                document.getElementById('continueButton');


            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            searchInput.addEventListener('input', function () {

                const term =
                    this.value
                        .trim()
                        .toLowerCase();

                serviceRows.forEach(function (row) {

                    const haystack =
                        row.dataset.search || '';

                    if (
                        term === '' ||
                        haystack.includes(term)
                    ) {
                        row.classList.remove('hidden');
                    } else {
                        row.classList.add('hidden');
                    }

                });

            });


            /*
            |--------------------------------------------------------------------------
            | Add Service
            |--------------------------------------------------------------------------
            */

            document
                .querySelectorAll('.add-service')
                .forEach(function (button) {

                    button.addEventListener(
                        'click',
                        function () {

                            const id =
                                this.dataset.id;

                            if (selected.has(id)) {
                                return;
                            }

                            selected.set(id, {
                                id: id,
                                code: this.dataset.code,
                                name: this.dataset.name,
                                category: this.dataset.category,
                                price: parseFloat(
                                    this.dataset.price
                                ),
                                quantity: 1
                            });

                            renderSelected();

                        }
                    );

                });


            /*
            |--------------------------------------------------------------------------
            | Render Selected Items
            |--------------------------------------------------------------------------
            */

            function renderSelected() {

                selectedItems.innerHTML = '';

                let total = 0;


                selected.forEach(function (item) {

                    const amount =
                        item.price * item.quantity;

                    total += amount;


                    const wrapper =
                        document.createElement('div');

                    wrapper.className =
                        'rounded-lg border border-gray-200 p-3';

                    wrapper.innerHTML = `

                        <input
                            type="hidden"
                            name="services[${item.id}][service_id]"
                            value="${item.id}"
                        >

                        <input
                            type="hidden"
                            name="services[${item.id}][quantity]"
                            value="${item.quantity}"
                        >

                        <div class="flex items-start justify-between gap-3">

                            <div class="min-w-0">

                                <div class="text-sm font-semibold text-gray-900">
                                    ${escapeHtml(item.name)}
                                </div>

                                <div class="mt-1 text-xs text-gray-500">
                                    ${escapeHtml(item.code)}
                                </div>

                            </div>

                            <button
                                type="button"
                                data-remove="${item.id}"
                                class="remove-service text-xs font-semibold text-red-600 hover:text-red-800"
                            >
                                Remove
                            </button>

                        </div>


                        <div class="mt-3 flex items-center justify-between">

                            <div class="text-xs text-gray-500">
                                ₹${item.price.toFixed(2)}
                            </div>

                            <div class="text-sm font-semibold text-gray-900">
                                ₹${amount.toFixed(2)}
                            </div>

                        </div>
                    `;

                    selectedItems.appendChild(wrapper);

                });


                /*
                |--------------------------------------------------------------------------
                | Remove Buttons
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll('.remove-service')
                    .forEach(function (button) {

                        button.addEventListener(
                            'click',
                            function () {

                                selected.delete(
                                    this.dataset.remove
                                );

                                renderSelected();

                            }
                        );

                    });


                /*
                |--------------------------------------------------------------------------
                | Empty / Total / Continue
                |--------------------------------------------------------------------------
                */

                if (selected.size === 0) {

                    emptySelection.classList.remove(
                        'hidden'
                    );

                    continueButton.disabled = true;

                    continueButton.classList.add(
                        'opacity-50'
                    );

                } else {

                    emptySelection.classList.add(
                        'hidden'
                    );

                    continueButton.disabled = false;

                    continueButton.classList.remove(
                        'opacity-50'
                    );

                }


                grandTotal.textContent =
                    '₹' + total.toFixed(2);

            }


            /*
            |--------------------------------------------------------------------------
            | Basic HTML Escaping
            |--------------------------------------------------------------------------
            */

            function escapeHtml(value) {

                const div =
                    document.createElement('div');

                div.textContent =
                    value ?? '';

                return div.innerHTML;

            }

        });
    </script>

</x-app-layout>