<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    GRN Register
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Goods Receipt Notes and pharmacy stock receipts
                </p>
            </div>

            <a
                href="{{ route('pharmacy.purchase-orders.index') }}"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                Purchase Orders
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- FILTERS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('pharmacy.grns.index') }}"
                    class="grid gap-4 lg:grid-cols-12"
                >

                    <div class="lg:col-span-5">

                        <label
                            for="search"
                            class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            Search
                        </label>

                        <input
                            id="search"
                            name="search"
                            type="text"
                            value="{{ request('search') }}"
                            placeholder="GRN no, PO no, invoice, supplier..."
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div class="lg:col-span-3">

                        <label
                            for="from_date"
                            class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            From Date
                        </label>

                        <input
                            id="from_date"
                            name="from_date"
                            type="date"
                            value="{{ request('from_date') }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div class="lg:col-span-3">

                        <label
                            for="to_date"
                            class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            To Date
                        </label>

                        <input
                            id="to_date"
                            name="to_date"
                            type="date"
                            value="{{ request('to_date') }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div class="flex items-end gap-2 lg:col-span-12">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Apply Filters
                        </button>

                        <a
                            href="{{ route('pharmacy.grns.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>



            {{-- ========================================================= --}}
            {{-- REGISTER --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Goods Receipt Register
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Latest GRNs are shown first
                            </p>

                        </div>

                        <div class="text-sm text-slate-500">
                            {{ $grns->total() }}
                            {{ Str::plural('record', $grns->total()) }}
                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    GRN Date
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    GRN Number
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Purchase Order
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Supplier
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Supplier Invoice
                                </th>

                                <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Items
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
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

                            @forelse ($grns as $grn)

                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">
                                        {{ $grn->grn_date?->format('d M Y') ?? '—' }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $grn->grn_no }}
                                        </div>

                                        <div class="mt-1">

                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                {{ ucfirst($grn->status) }}
                                            </span>

                                        </div>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4">

                                        @if ($grn->purchaseOrder)

                                            <a
                                                href="{{ route('pharmacy.purchase-orders.show', $grn->purchaseOrder) }}"
                                                class="font-semibold text-blue-700 hover:text-blue-900"
                                            >
                                                {{ $grn->purchaseOrder->po_no }}
                                            </a>

                                        @else

                                            <span class="text-slate-400">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $grn->supplier?->name ?? '—' }}
                                        </div>

                                        @if ($grn->supplier?->code)

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $grn->supplier->code }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-medium text-slate-800">
                                            {{ $grn->supplier_invoice_no ?: '—' }}
                                        </div>

                                        @if ($grn->supplier_invoice_date)

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $grn->supplier_invoice_date->format('d M Y') }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4 text-center">

                                        <span class="inline-flex min-w-8 justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ $grn->items_count }}
                                        </span>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="font-bold text-slate-900">
                                            ₹{{ number_format((float) $grn->total_amount, 2) }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-medium text-slate-800">
                                            {{ $grn->createdBy?->name ?? 'System' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $grn->created_at?->format('d M Y, h:i A') }}
                                        </div>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <a
                                            href="{{ route('pharmacy.grns.show', $grn) }}"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="9"
                                        class="px-6 py-16 text-center"
                                    >

                                        <div class="text-base font-semibold text-slate-700">
                                            No GRNs found
                                        </div>

                                        <div class="mt-2 text-sm text-slate-500">
                                            Goods Receipt Notes will appear here after medicines are received against approved Purchase Orders.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($grns->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $grns->links() }}
                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>