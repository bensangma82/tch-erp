<x-app-layout>
<div class="mx-auto max-w-[1800px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                GST Purchase Report
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                GST Submission Details on Medicine Purchases
            </p>
        </div>

        <a
            href="{{ route('pharmacy.reports.gst-purchases.export', request()->query()) }}"
            class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
        >
            Export Excel
        </a>
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <form
            method="GET"
            action="{{ route('pharmacy.reports.gst-purchases.index') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-4"
        >
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">
                    From Date
                </label>

                <input
                    type="date"
                    name="from_date"
                    value="{{ $fromDate }}"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">
                    To Date
                </label>

                <input
                    type="date"
                    name="to_date"
                    value="{{ $toDate }}"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">
                    Supplier
                </label>

                <select
                    name="supplier_id"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">
                        All Suppliers
                    </option>

                    @foreach ($suppliers as $supplier)
                        <option
                            value="{{ $supplier->id }}"
                            @selected((string) $supplierId === (string) $supplier->id)
                        >
                            {{ $supplier->name }}
                            @if ($supplier->gstin)
                                — {{ $supplier->gstin }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    Generate Report
                </button>

                <a
                    href="{{ route('pharmacy.reports.gst-purchases.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Reporting Period --}}
    <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <div class="text-center">
            <div class="text-lg font-bold text-slate-900">
                TURA CHRISTIAN HOSPITAL
            </div>

            <div class="text-sm text-slate-500">
                Tura, Meghalaya
            </div>

            <div class="mt-2 font-semibold text-slate-800">
                GST Submission Details on Medicine Purchases
            </div>

            <div class="mt-1 text-sm text-slate-500">
                Between
                {{ \Illuminate\Support\Carbon::parse($fromDate)->format('d M Y') }}
                and
                {{ \Illuminate\Support\Carbon::parse($toDate)->format('d M Y') }}
            </div>
        </div>
    </div>

    {{-- Totals --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Taxable Value
            </div>

            <div class="mt-2 text-xl font-bold text-slate-900">
                ₹{{ number_format((float) ($totals->taxable_amount ?? 0), 2) }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                CGST
            </div>

            <div class="mt-2 text-xl font-bold text-slate-900">
                ₹{{ number_format((float) ($totals->cgst_amount ?? 0), 2) }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                SGST
            </div>

            <div class="mt-2 text-xl font-bold text-slate-900">
                ₹{{ number_format((float) ($totals->sgst_amount ?? 0), 2) }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                IGST
            </div>

            <div class="mt-2 text-xl font-bold text-slate-900">
                ₹{{ number_format((float) ($totals->igst_amount ?? 0), 2) }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Purchase Total
            </div>

            <div class="mt-2 text-xl font-bold text-slate-900">
                ₹{{ number_format((float) ($totals->line_total ?? 0), 2) }}
            </div>
        </div>

    </div>

    {{-- Detailed Report --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-900">
                Purchase Details
            </h2>

            <p class="mt-1 text-xs text-slate-500">
                Only completed GRNs within the selected supplier invoice date range are included.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1800px] divide-y divide-slate-200 text-sm">

                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <th class="px-3 py-3">Sl.</th>
                        <th class="px-3 py-3">Invoice Date</th>
                        <th class="px-3 py-3">GRN No.</th>
                        <th class="px-3 py-3">Invoice No.</th>
                        <th class="px-3 py-3">Supplier</th>
                        <th class="px-3 py-3">GSTIN</th>
                        <th class="px-3 py-3">Medicine / Brand</th>
                        <th class="px-3 py-3">Batch</th>
                        <th class="px-3 py-3">Expiry</th>
                        <th class="px-3 py-3 text-right">Qty.</th>
                        <th class="px-3 py-3 text-right">Bonus</th>
                        <th class="px-3 py-3 text-right">Units/Pack</th>
                        <th class="px-3 py-3 text-right">Cost Price</th>
                        <th class="px-3 py-3 text-right">MRP</th>
                        <th class="px-3 py-3">HSN/SAC</th>
                        <th class="px-3 py-3 text-right">GST %</th>
                        <th class="px-3 py-3 text-right">Taxable</th>
                        <th class="px-3 py-3 text-right">CGST</th>
                        <th class="px-3 py-3 text-right">SGST</th>
                        <th class="px-3 py-3 text-right">IGST</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse ($items as $item)

                        <tr class="hover:bg-slate-50">

                            <td class="whitespace-nowrap px-3 py-3 text-slate-500">
                                {{ $items->firstItem() + $loop->index }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3">
                                {{ $item->supplier_invoice_date
                                    ? \Illuminate\Support\Carbon::parse($item->supplier_invoice_date)->format('d-m-Y')
                                    : '—'
                                }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 font-medium text-slate-800">
                                {{ $item->grn_no }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3">
                                {{ $item->supplier_invoice_no ?: '—' }}
                            </td>

                            <td class="px-3 py-3">
                                {{ $item->supplier_name ?: '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3">
                                {{ $item->supplier_gstin ?: '—' }}
                            </td>

                            <td class="px-3 py-3 font-medium text-slate-800">
                                {{ $item->brand_name ?: $item->medicine_name }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3">
                                {{ $item->batch_number ?: '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3">
                                {{ $item->expiry_date
                                    ? \Illuminate\Support\Carbon::parse($item->expiry_date)->format('m-Y')
                                    : '—'
                                }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                {{ number_format((float) $item->purchase_qty, 0) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                {{ number_format((float) $item->bonus_qty, 0) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                {{ number_format((float) $item->units_per_pack, 0) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) $item->purchase_price, 2) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) ($item->mrp_per_pack ?? 0), 2) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3">
                                {{ $item->hsn_code ?: '—' }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                {{ number_format((float) $item->gst_percent, 2) }}%
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) $item->taxable_amount, 2) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) $item->cgst_amount, 2) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) $item->sgst_amount, 2) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) $item->igst_amount, 2) }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="20"
                                class="px-6 py-12 text-center text-sm text-slate-500"
                            >
                                No completed pharmacy purchases were found for this period.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

                @if ($items->count())
                    <tfoot class="border-t-2 border-slate-300 bg-slate-50 font-semibold text-slate-900">
                        <tr>
                            <td
                                colspan="16"
                                class="px-3 py-3 text-right"
                            >
                                REPORT TOTAL
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) ($totals->taxable_amount ?? 0), 2) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) ($totals->cgst_amount ?? 0), 2) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) ($totals->sgst_amount ?? 0), 2) }}
                            </td>

                            <td class="whitespace-nowrap px-3 py-3 text-right">
                                ₹{{ number_format((float) ($totals->igst_amount ?? 0), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif

            </table>
        </div>

        @if ($items->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $items->links() }}
            </div>
        @endif

    </div>

</div>
</x-app-layout>