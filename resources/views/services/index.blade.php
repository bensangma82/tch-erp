<x-app-layout>

    @php
        $isChargeMaster = ($scope ?? request('scope')) === 'charges';

        $chargeCategories = [
            'procedure' => 'Procedure',
            'consultation' => 'Consultation',
            'nursing' => 'Nursing',
            'equipment' => 'Equipment / Device Use',
            'consumable' => 'Consumable',
            'facility' => 'Facility / Miscellaneous',
            'other' => 'Other',
        ];

        $serviceCategories = [
            'laboratory' => 'Laboratory',
            'radiology' => 'Radiology',
            'procedure' => 'Procedure',
            'consultation' => 'Consultation',
            'nursing' => 'Nursing',
            'equipment' => 'Equipment / Device Use',
            'consumable' => 'Consumable',
            'facility' => 'Facility / Miscellaneous',
            'other' => 'Other',
        ];

        $visibleCategories = $isChargeMaster
            ? $chargeCategories
            : $serviceCategories;
    @endphp


    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="flex flex-wrap items-center gap-3">

                    <h2 class="text-xl font-semibold text-gray-800">
                        {{ $isChargeMaster ? 'Charge Master' : 'Service Master' }}
                    </h2>

                    @if ($isChargeMaster)
                        <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            Inpatient Billing
                        </span>
                    @endif

                </div>

                <p class="mt-1 text-sm text-gray-500">
                    @if ($isChargeMaster)
                        Maintain standard inpatient charges, departments, units and authoritative billing rates.
                    @else
                        Manage laboratory, radiology and other hospital services used throughout the ERP.
                    @endif
                </p>

            </div>


            <div class="flex flex-wrap items-center gap-2">

                @if ($isChargeMaster)

                    <a
                        href="{{ route('services.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        View Full Service Master
                    </a>

                @endif


                <a
                    href="{{ route('services.create', $isChargeMaster ? ['scope' => 'charges'] : []) }}"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    {{ $isChargeMaster ? 'Add Charge' : 'Add Service' }}
                </a>

            </div>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))

                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            {{-- CHARGE MASTER INFORMATION --}}
            @if ($isChargeMaster)

                <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-5">

                    <div class="flex items-start gap-3">

                        <div class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                            ₹
                        </div>

                        <div>

                            <h3 class="font-semibold text-blue-900">
                                Standard Inpatient Charge Master
                            </h3>

                            <p class="mt-1 text-sm leading-6 text-blue-800">
                                Charges maintained here are available in
                                <strong>IP Billing → Add Charge</strong>.
                                The ERP uses the Service Master record as the authoritative source for
                                charge code, description and rate.
                            </p>

                            <p class="mt-1 text-xs text-blue-700">
                                Laboratory and Radiology are intentionally excluded because they are billed through the investigation workflow.
                            </p>

                        </div>

                    </div>

                </div>

            @endif


            {{-- FILTERS --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('services.index') }}"
                    class="grid grid-cols-1 gap-4 md:grid-cols-4"
                >

                    @if ($isChargeMaster)
                        <input
                            type="hidden"
                            name="scope"
                            value="charges"
                        >
                    @endif


                    <div class="md:col-span-2">

                        <label
                            for="service-search"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Search
                        </label>

                        <input
                            id="service-search"
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="{{ $isChargeMaster ? 'Charge code, name or category' : 'Service code, name or category' }}"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                    </div>


                    <div>

                        <label
                            for="service-category"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Category
                        </label>

                        <select
                            id="service-category"
                            name="category"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                            <option value="">
                                All Categories
                            </option>

                            @foreach ($visibleCategories as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(request('category') === $value)
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="flex items-end gap-2">

                        <button
                            type="submit"
                            class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('services.index', $isChargeMaster ? ['scope' => 'charges'] : []) }}"
                            class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Clear
                        </a>

                    </div>

                </form>

            </div>


            {{-- MASTER TABLE --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-gray-800">
                                {{ $isChargeMaster ? 'Standard Charges' : 'Hospital Services' }}
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                @if ($isChargeMaster)
                                    Rates shown here are used when a charge is posted to an inpatient billing account.
                                @else
                                    Service details and prices configured here are used by ERP billing and diagnostic workflows.
                                @endif
                            </p>

                        </div>


                        <div class="text-sm text-gray-600">

                            Total:

                            <span class="font-semibold text-gray-900">
                                {{ $services->total() }}
                            </span>

                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-white">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Code
                                </th>

                                <th class="min-w-[240px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $isChargeMaster ? 'Charge Name' : 'Service' }}
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Category
                                </th>

                                <th class="min-w-[160px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Department
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Unit
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    {{ $isChargeMaster ? 'Rate' : 'Price' }}
                                </th>

                                @if (! $isChargeMaster)

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Sample
                                    </th>

                                @endif

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @forelse ($services as $service)

                                @php
                                    $category = strtolower((string) $service->category);

                                    $categoryLabel =
                                        $serviceCategories[$category]
                                        ?? ucfirst(str_replace('_', ' ', $category));

                                    $badgeClass = match ($category) {
                                        'laboratory' =>
                                            'bg-purple-100 text-purple-700',

                                        'radiology' =>
                                            'bg-blue-100 text-blue-700',

                                        'procedure' =>
                                            'bg-amber-100 text-amber-700',

                                        'consultation' =>
                                            'bg-green-100 text-green-700',

                                        'nursing' =>
                                            'bg-cyan-100 text-cyan-700',

                                        'equipment' =>
                                            'bg-indigo-100 text-indigo-700',

                                        'consumable' =>
                                            'bg-orange-100 text-orange-700',

                                        'facility' =>
                                            'bg-teal-100 text-teal-700',

                                        default =>
                                            'bg-gray-100 text-gray-700',
                                    };
                                @endphp


                                <tr class="hover:bg-gray-50">

                                    <td class="whitespace-nowrap px-5 py-4">

                                        <div class="font-mono text-sm font-semibold text-gray-900">
                                            {{ $service->code }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-gray-900">
                                            {{ $service->name }}
                                        </div>

                                        @if ($service->description)

                                            <div class="mt-1 max-w-md truncate text-xs text-gray-500">
                                                {{ $service->description }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        <span class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                                            {{ $categoryLabel }}
                                        </span>

                                    </td>


                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $service->department?->name ?? '—' }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $service->unit ?: '—' }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="text-sm font-bold text-gray-900">
                                            ₹{{ number_format((float) $service->price, 2) }}
                                        </div>

                                    </td>


                                    @if (! $isChargeMaster)

                                        <td class="px-5 py-4">

                                            @if ($service->requires_sample)

                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                    Yes
                                                </span>

                                            @else

                                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                                    No
                                                </span>

                                            @endif

                                        </td>

                                    @endif


                                    <td class="px-5 py-4">

                                        @if ($service->is_active)

                                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Active
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4 text-right">

                                        <a
                                            href="{{ route('services.edit', $service) }}"
                                            class="inline-flex whitespace-nowrap rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                        >
                                            Edit
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="{{ $isChargeMaster ? 8 : 9 }}"
                                        class="px-6 py-14 text-center"
                                    >

                                        <div class="text-sm font-medium text-gray-700">
                                            {{ $isChargeMaster ? 'No inpatient charges found.' : 'No hospital services found.' }}
                                        </div>

                                        <p class="mt-1 text-sm text-gray-500">
                                            @if ($isChargeMaster)
                                                Add your first standard procedure, consultation, nursing, equipment or facility charge.
                                            @else
                                                Add your first laboratory, radiology, procedure or other hospital service.
                                            @endif
                                        </p>

                                        <a
                                            href="{{ route('services.create', $isChargeMaster ? ['scope' => 'charges'] : []) }}"
                                            class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                        >
                                            {{ $isChargeMaster ? 'Add Charge' : 'Add Service' }}
                                        </a>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($services->hasPages())

                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $services->links() }}
                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>
