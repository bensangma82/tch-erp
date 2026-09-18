<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Purchase Orders
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Pharmacy purchase order register
                </p>

            </div>


            <a
                href="{{ route('pharmacy.purchase-orders.create') }}"
                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                New Purchase Order
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            @if (session('error'))

                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- FILTERS --}}
            {{-- ========================================================= --}}

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('pharmacy.purchase-orders.index') }}"
                    class="grid gap-4 md:grid-cols-2 lg:grid-cols-5"
                >

                    <div class="lg:col-span-2">

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="PO number, supplier name or code"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option value="draft" @selected(request('status') === 'draft')>
                                Draft
                            </option>

                            <option value="approved" @selected(request('status') === 'approved')>
                                Approved
                            </option>

                            <option value="partially_received" @selected(request('status') === 'partially_received')>
                                Partially Received
                            </option>

                            <option value="received" @selected(request('status') === 'received')>
                                Received
                            </option>

                            <option value="cancelled" @selected(request('status') === 'cancelled')>
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            From Date
                        </label>

                        <input
                            type="date"
                            name="from_date"
                            value="{{ request('from_date') }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            To Date
                        </label>

                        <input
                            type="date"
                            name="to_date"
                            value="{{ request('to_date') }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div class="flex items-end gap-2 lg:col-span-5">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Apply Filters
                        </button>

                        <a
                            href="{{ route('pharmacy.purchase-orders.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>



            {{-- ========================================================= --}}
            {{-- PURCHASE ORDER REGISTER --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Purchase Order Register
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Latest purchase orders are shown first
                            </p>

                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    PO Date
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    PO Number
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Supplier
                                </th>

                                <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Items
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Created By
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($purchaseOrders as $purchaseOrder)

                                @php

                                    $statusLabel =
                                        match ($purchaseOrder->status) {

                                            'draft' =>
                                                'Draft',

                                            'approved' =>
                                                'Approved',

                                            'partially_received' =>
                                                'Partially Received',

                                            'received' =>
                                                'Received',

                                            'cancelled' =>
                                                'Cancelled',

                                            default =>
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $purchaseOrder->status
                                                    )
                                                ),
                                        };


                                    $statusClass =
                                        match ($purchaseOrder->status) {

                                            'draft' =>
                                                'bg-amber-50 text-amber-700',

                                            'approved' =>
                                                'bg-blue-50 text-blue-700',

                                            'partially_received' =>
                                                'bg-purple-50 text-purple-700',

                                            'received' =>
                                                'bg-emerald-50 text-emerald-700',

                                            'cancelled' =>
                                                'bg-red-50 text-red-700',

                                            default =>
                                                'bg-slate-100 text-slate-700',
                                        };

                                @endphp


                                <tr class="transition hover:bg-slate-50">


                                    <td class="whitespace-nowrap px-5 py-4">

                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ $purchaseOrder->po_date?->format('d M Y') }}
                                        </div>

                                        @if ($purchaseOrder->expected_delivery_date)

                                            <div class="mt-1 text-xs text-slate-400">
                                                Expected:
                                                {{ $purchaseOrder->expected_delivery_date->format('d M Y') }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4">

                                        <div class="font-bold text-slate-900">
                                            {{ $purchaseOrder->po_no }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $purchaseOrder->supplier?->name ?? '—' }}
                                        </div>

                                        @if ($purchaseOrder->supplier?->code)

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $purchaseOrder->supplier->code }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-center">

                                        <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                                            {{ number_format($purchaseOrder->items_count ?? 0) }}
                                        </span>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="font-bold text-slate-900">
                                            ₹{{ number_format((float) $purchaseOrder->total_amount, 2) }}
                                        </div>

                                        @if ((float) $purchaseOrder->discount_amount > 0)

                                            <div class="mt-1 text-xs text-slate-400">
                                                Discount
                                                ₹{{ number_format((float) $purchaseOrder->discount_amount, 2) }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}"
                                        >
                                            {{ $statusLabel }}
                                        </span>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="text-sm text-slate-700">
                                            {{ $purchaseOrder->createdBy?->name ?? 'System' }}
                                        </div>

                                        @if ($purchaseOrder->approved_at)

                                            <div class="mt-1 text-xs text-slate-400">
                                                Approved
                                                {{ $purchaseOrder->approved_at->format('d M Y') }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <a
                                            href="{{ route('pharmacy.purchase-orders.show', $purchaseOrder) }}"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            View
                                        </a>

                                    </td>


                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="8"
                                        class="px-6 py-12 text-center"
                                    >

                                        <div class="text-sm font-semibold text-slate-700">
                                            No purchase orders found.
                                        </div>

                                        <div class="mt-2 text-xs text-slate-500">
                                            Create the first pharmacy purchase order to begin procurement.
                                        </div>

                                        <div class="mt-5">

                                            <a
                                                href="{{ route('pharmacy.purchase-orders.create') }}"
                                                class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                            >
                                                New Purchase Order
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($purchaseOrders->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $purchaseOrders->links() }}
                    </div>

                @endif


            </div>

        </div>

    </div>

</x-app-layout>