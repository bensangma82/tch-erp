<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Add Inpatient Investigations
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Select laboratory and radiology services to add directly to the inpatient running bill.
                </p>
            </div>

            <a
                href="{{ route('ip-billing.show', $admission) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back to Running Bill
            </a>

        </div>
    </x-slot>


    @php
        $patient = $admission->patient;

        $currentAllocation =
            $admission->currentBedAllocation;

        $currentBed =
            $currentAllocation?->bed
            ?? $admission->bed;

        $ward =
            $currentBed?->ward;

        $laboratoryServices =
            $services->where('category', 'laboratory');

        $radiologyServices =
            $services->where('category', 'radiology');
    @endphp


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <ul class="list-inside list-disc text-sm text-red-700">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- PATIENT / ADMISSION SUMMARY --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="grid gap-0 lg:grid-cols-4">

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
                        </div>

                    </div>


                    <div class="border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Admission
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ $admission->admission_no }}
                        </div>

                        <div class="mt-3 space-y-1 text-sm text-slate-600">
                            <div>
                                Admitted:
                                <span class="font-semibold text-slate-800">
                                    {{ $admission->admitted_at?->format('d M Y, h:i A') ?? '—' }}
                                </span>
                            </div>

                            <div>
                                Type:
                                <span class="font-semibold text-slate-800">
                                    {{ ucfirst($admission->admission_type ?? 'IPD') }}
                                </span>
                            </div>
                        </div>

                    </div>


                    <div class="border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Ward / Bed
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ $ward?->name ?? '—' }}
                        </div>

                        <div class="mt-3 space-y-1 text-sm text-slate-600">
                            <div>
                                Bed:
                                <span class="font-semibold text-slate-800">
                                    {{ $currentBed?->bed_number ?? '—' }}
                                </span>
                            </div>

                            <div>
                                Type:
                                <span class="font-semibold text-slate-800">
                                    {{ $currentBed?->bed_type ?? '—' }}
                                </span>
                            </div>
                        </div>

                    </div>


                    <div class="p-5">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Billing Account
                        </div>

                        <div class="mt-1 font-mono text-lg font-bold text-slate-900">
                            {{ $account->account_no }}
                        </div>

                        <div class="mt-3">
                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                {{ ucfirst($account->status) }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>


            {{-- INFORMATION --}}
            <div class="rounded-2xl border border-teal-200 bg-teal-50 p-5 shadow-sm">

                <div class="flex items-start gap-3">

                    <div>
                        <h3 class="text-sm font-semibold text-teal-900">
                            Inpatient investigation workflow
                        </h3>

                        <p class="mt-1 text-sm leading-6 text-teal-800">
                            Selected tests are authorized immediately against this inpatient account.
                            No separate OPD-style payment step is required. Charges are posted automatically
                            to the running IP bill and the investigations become available to the laboratory
                            or radiology worklist.
                        </p>
                    </div>

                </div>

            </div>


            <form
                method="POST"
                action="{{ route('ip-billing.investigations.store', $admission) }}"
                id="investigation-form"
                class="space-y-6"
            >
                @csrf


                {{-- FILTERS --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">

                        <div>
                            <label for="service-search" class="block text-sm font-semibold text-slate-700">
                                Search investigations
                            </label>

                            <input
                                id="service-search"
                                type="search"
                                placeholder="Search by test name, code or category..."
                                autocomplete="off"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                            >
                        </div>


                        <div class="flex flex-wrap gap-2">

                            <button
                                type="button"
                                data-filter="all"
                                class="investigation-filter rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
                            >
                                All
                            </button>

                            <button
                                type="button"
                                data-filter="laboratory"
                                class="investigation-filter rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                            >
                                Laboratory
                            </button>

                            <button
                                type="button"
                                data-filter="radiology"
                                class="investigation-filter rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                            >
                                Radiology
                            </button>

                        </div>

                    </div>

                </div>


                {{-- SERVICE LIST --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                            <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    Investigation Services
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Prices are taken from the active Service Master.
                                </p>
                            </div>

                            <div class="text-sm text-slate-600">
                                <span class="font-semibold">{{ $services->count() }}</span>
                                available
                            </div>

                        </div>

                    </div>


                    @if ($services->isEmpty())

                        <div class="px-6 py-12 text-center">

                            <div class="text-base font-semibold text-slate-700">
                                No active Laboratory or Radiology services found
                            </div>

                            <p class="mt-1 text-sm text-slate-500">
                                Check the Service Master and make sure the category is
                                <span class="font-mono">laboratory</span> or
                                <span class="font-mono">radiology</span>.
                            </p>

                        </div>

                    @else

                        <div class="overflow-x-auto">

                            <table class="min-w-[900px] w-full">

                                <thead class="border-b border-slate-200 bg-white">

                                    <tr>
                                        <th class="w-16 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Select
                                        </th>

                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Investigation
                                        </th>

                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Category
                                        </th>

                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Rate
                                        </th>

                                        <th class="w-36 px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Quantity
                                        </th>
                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-slate-100">

                                    @foreach ($services as $service)

                                        @php
                                            $selected =
                                                old(
                                                    'services.' . $service->id . '.service_id'
                                                ) !== null;

                                            $quantity =
                                                old(
                                                    'services.' . $service->id . '.quantity',
                                                    1
                                                );
                                        @endphp

                                        <tr
                                            class="investigation-row hover:bg-slate-50"
                                            data-category="{{ $service->category }}"
                                            data-search="{{ strtolower(($service->code ?? '') . ' ' . $service->name . ' ' . $service->category) }}"
                                            data-price="{{ number_format((float) $service->price, 2, '.', '') }}"
                                        >

                                            <td class="px-5 py-4 text-center">

                                                <input
                                                    type="checkbox"
                                                    name="services[{{ $service->id }}][service_id]"
                                                    value="{{ $service->id }}"
                                                    @checked($selected)
                                                    class="investigation-checkbox h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                    aria-label="Select {{ $service->name }}"
                                                >

                                            </td>


                                            <td class="px-5 py-4">

                                                <div class="font-semibold text-slate-900">
                                                    {{ $service->name }}
                                                </div>

                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ $service->code ?: 'No service code' }}
                                                </div>

                                            </td>


                                            <td class="px-5 py-4">

                                                @if ($service->category === 'laboratory')

                                                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                        Laboratory
                                                    </span>

                                                @else

                                                    <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">
                                                        Radiology
                                                    </span>

                                                @endif

                                            </td>


                                            <td class="px-5 py-4 text-right font-semibold text-slate-900">
                                                ₹{{ number_format((float) $service->price, 2) }}
                                            </td>


                                            <td class="px-5 py-4 text-right">

                                                <input
                                                    type="number"
                                                    name="services[{{ $service->id }}][quantity]"
                                                    value="{{ $quantity }}"
                                                    min="1"
                                                    max="100"
                                                    step="1"
                                                    @disabled(! $selected)
                                                    class="investigation-quantity ml-auto block w-24 rounded-lg border-slate-300 text-right shadow-sm focus:border-teal-500 focus:ring-teal-500 disabled:bg-slate-100 disabled:text-slate-400"
                                                >

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @endif

                </div>


                {{-- REMARKS --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <label for="remarks" class="block text-sm font-semibold text-slate-700">
                        Order Remarks
                    </label>

                    <textarea
                        id="remarks"
                        name="remarks"
                        rows="3"
                        maxlength="2000"
                        placeholder="Optional clinical or billing remarks..."
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    >{{ old('remarks') }}</textarea>

                </div>


                {{-- ORDER SUMMARY / SUBMIT --}}
                <div class="sticky bottom-4 z-20 rounded-2xl border border-slate-200 bg-white/95 p-5 shadow-xl backdrop-blur">

                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                        <div class="grid grid-cols-2 gap-4 sm:flex sm:gap-8">

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selected
                                </div>

                                <div id="selected-count" class="mt-1 text-xl font-bold text-slate-900">
                                    0
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Estimated Total
                                </div>

                                <div id="selected-total" class="mt-1 text-xl font-bold text-teal-700">
                                    ₹0.00
                                </div>
                            </div>

                        </div>


                        <div class="flex flex-col gap-2 sm:flex-row">

                            <a
                                href="{{ route('ip-billing.show', $admission) }}"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                id="submit-investigations"
                                class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                Authorize & Add to IP Bill
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const searchInput =
                document.getElementById('service-search');

            const rows =
                Array.from(
                    document.querySelectorAll('.investigation-row')
                );

            const filterButtons =
                Array.from(
                    document.querySelectorAll('.investigation-filter')
                );

            const selectedCount =
                document.getElementById('selected-count');

            const selectedTotal =
                document.getElementById('selected-total');

            const submitButton =
                document.getElementById('submit-investigations');

            let activeFilter = 'all';


            function updateSelectionSummary() {

                let count = 0;
                let total = 0;

                rows.forEach(function (row) {

                    const checkbox =
                        row.querySelector('.investigation-checkbox');

                    const quantityInput =
                        row.querySelector('.investigation-quantity');

                    const checked =
                        checkbox.checked;

                    quantityInput.disabled =
                        ! checked;

                    if (! checked) {
                        return;
                    }

                    count += 1;

                    const price =
                        parseFloat(
                            row.dataset.price || '0'
                        );

                    const quantity =
                        Math.max(
                            1,
                            parseInt(
                                quantityInput.value || '1',
                                10
                            )
                        );

                    total +=
                        price * quantity;
                });


                selectedCount.textContent =
                    count.toString();

                selectedTotal.textContent =
                    new Intl.NumberFormat(
                        'en-IN',
                        {
                            style: 'currency',
                            currency: 'INR',
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        }
                    ).format(total);

                submitButton.disabled =
                    count === 0;
            }


            function applyFilters() {

                const query =
                    (searchInput.value || '')
                        .trim()
                        .toLowerCase();


                rows.forEach(function (row) {

                    const category =
                        row.dataset.category || '';

                    const searchable =
                        row.dataset.search || '';

                    const categoryMatches =
                        activeFilter === 'all'
                        || category === activeFilter;

                    const searchMatches =
                        query === ''
                        || searchable.includes(query);

                    row.classList.toggle(
                        'hidden',
                        ! (categoryMatches && searchMatches)
                    );
                });
            }


            searchInput.addEventListener(
                'input',
                applyFilters
            );


            filterButtons.forEach(function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        activeFilter =
                            button.dataset.filter || 'all';


                        filterButtons.forEach(
                            function (item) {

                                const selected =
                                    item === button;

                                item.className =
                                    selected
                                        ? 'investigation-filter rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm'
                                        : 'investigation-filter rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50';
                            }
                        );


                        applyFilters();
                    }
                );
            });


            rows.forEach(function (row) {

                const checkbox =
                    row.querySelector('.investigation-checkbox');

                const quantityInput =
                    row.querySelector('.investigation-quantity');


                checkbox.addEventListener(
                    'change',
                    updateSelectionSummary
                );


                quantityInput.addEventListener(
                    'input',
                    updateSelectionSummary
                );
            });


            updateSelectionSummary();
            applyFilters();
        });
    </script>

</x-app-layout>
