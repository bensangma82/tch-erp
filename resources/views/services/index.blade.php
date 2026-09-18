<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Service Master
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Manage laboratory, radiology, procedure and other hospital services.
                </p>

            </div>

            <a
                href="{{ route('services.create') }}"
                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Add Service
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))

                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            {{-- FILTERS --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('services.index') }}"
                    class="grid grid-cols-1 gap-4 md:grid-cols-4"
                >

                    <div class="md:col-span-2">

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Service code, name or category"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Category
                        </label>

                        <select
                            name="category"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                            <option value="">
                                All Categories
                            </option>

                            <option
                                value="laboratory"
                                @selected(request('category') === 'laboratory')
                            >
                                Laboratory
                            </option>

                            <option
                                value="radiology"
                                @selected(request('category') === 'radiology')
                            >
                                Radiology
                            </option>

                            <option
                                value="procedure"
                                @selected(request('category') === 'procedure')
                            >
                                Procedure
                            </option>

                            <option
                                value="consultation"
                                @selected(request('category') === 'consultation')
                            >
                                Consultation
                            </option>

                            <option
                                value="other"
                                @selected(request('category') === 'other')
                            >
                                Other
                            </option>

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
                            href="{{ route('services.index') }}"
                            class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Clear
                        </a>

                    </div>

                </form>

            </div>


            {{-- SERVICES TABLE --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="font-semibold text-gray-800">
                                Hospital Services
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Prices configured here will later be used by the billing counter.
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

                                <th class="min-w-[220px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Service
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Category
                                </th>

                                <th class="min-w-[160px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Department
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Price
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Sample
                                </th>

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

                                <tr class="hover:bg-gray-50">

                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        {{ $service->code }}
                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-gray-900">
                                            {{ $service->name }}
                                        </div>

                                        @if ($service->unit)

                                            <div class="mt-1 text-xs text-gray-500">
                                                Unit: {{ $service->unit }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        @php
                                            $category = strtolower($service->category);
                                        @endphp

                                        @if ($category === 'laboratory')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                                Laboratory
                                            </span>

                                        @elseif ($category === 'radiology')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                Radiology
                                            </span>

                                        @elseif ($category === 'procedure')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                                Procedure
                                            </span>

                                        @elseif ($category === 'consultation')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Consultation
                                            </span>

                                        @else

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                                {{ ucfirst($service->category) }}
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $service->department?->name ?? '—' }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-gray-900">
                                        ₹{{ number_format((float) $service->price, 2) }}
                                    </td>


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
                                            class="inline-flex whitespace-nowrap rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        >
                                            Edit
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="8"
                                        class="px-6 py-14 text-center"
                                    >

                                        <div class="text-sm font-medium text-gray-700">
                                            No hospital services found.
                                        </div>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Add your first laboratory, radiology or procedure service.
                                        </p>

                                        <a
                                            href="{{ route('services.create') }}"
                                            class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                        >
                                            Add Service
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