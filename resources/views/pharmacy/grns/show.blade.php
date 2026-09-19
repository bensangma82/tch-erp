<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Goods Receipt Note
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pharmacyGrn->grn_no }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2 no-print">

                <a
                    href="{{ route('pharmacy.grns.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    GRN Register
                </a>

                <a
                    href="{{ route('pharmacy.purchase-orders.show', $pharmacyGrn->purchaseOrder) }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    View PO
                </a>

                <button
                    type="button"
                    onclick="window.print()"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Print GRN
                </button>

            </div>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="no-print rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- GRN HEADER --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Goods Receipt Note
                            </div>

                            <div class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $pharmacyGrn->grn_no }}
                            </div>

                        </div>

                        <span class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                            {{ ucfirst($pharmacyGrn->status) }}
                        </span>

                    </div>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            GRN Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->grn_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Purchase Order
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->purchaseOrder?->po_no ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Supplier
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->supplier?->name ?? '—' }}
                        </div>

                        @if ($pharmacyGrn->supplier?->code)
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $pharmacyGrn->supplier->code }}
                            </div>
                        @endif
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Created By
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->createdBy?->name ?? 'System' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Supplier Invoice No.
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->supplier_invoice_no ?: '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Supplier Invoice Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->supplier_invoice_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- ITEMS --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Received Medicines
                    </h3>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[1550px] w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Medicine
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Batch
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Expiry
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Purchase Qty
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Bonus Qty
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Units / Pack
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Net Qty
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Purchase Rate / Pack
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Selling Price
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    GST
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Total
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @foreach ($pharmacyGrn->items as $item)

                                <tr>

                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $item->medicine_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">

                                            @if ($item->brand_name)
                                                {{ $item->brand_name }}
                                            @endif

                                            @if ($item->strength)
                                                · {{ $item->strength }}
                                            @endif

                                        </div>

                                    </td>


                                    <td class="px-4 py-4 font-medium text-slate-800">
                                        {{ $item->batch_number }}
                                    </td>


                                    <td class="px-4 py-4 text-slate-700">
                                        {{ $item->expiry_date?->format('d M Y') ?? '—' }}
                                    </td>


                                    <td class="px-4 py-4 text-right font-semibold text-slate-900">
                                        {{ number_format((int) ($item->purchase_qty ?? 0)) }}
                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">
                                        {{ number_format((int) ($item->bonus_qty ?? 0)) }}
                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">
                                        {{ number_format((int) ($item->units_per_pack ?? 1)) }}
                                    </td>


                                    <td class="px-4 py-4 text-right font-bold text-emerald-700">
                                        {{ number_format(
                                            (int) (
                                                ($item->received_units ?? 0) > 0
                                                    ? $item->received_units
                                                    : $item->quantity_received
                                            )
                                        ) }}
                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">
                                        ₹{{ number_format((float) $item->purchase_price, 2) }}
                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">
                                        ₹{{ number_format((float) $item->selling_price, 2) }}
                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">
                                        {{ number_format((float) $item->gst_percent, 2) }}%
                                    </td>


                                    <td class="px-4 py-4 text-right font-bold text-slate-900">
                                        ₹{{ number_format((float) $item->line_total, 2) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- TOTALS + REMARKS --}}
            {{-- ========================================================= --}}

            <div class="grid gap-6 lg:grid-cols-2">


                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h3 class="font-semibold text-slate-900">
                        Remarks
                    </h3>

                    <div class="mt-4 text-sm leading-6 text-slate-600">
                        {{ $pharmacyGrn->remarks ?: 'No remarks.' }}
                    </div>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h3 class="font-semibold text-slate-900">
                        GRN Summary
                    </h3>


                    <div class="mt-5 space-y-3">

                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Gross Subtotal</span>
                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyGrn->subtotal, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Discount</span>
                            <span class="font-semibold text-red-700">
                                ₹{{ number_format((float) $pharmacyGrn->discount_amount, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Taxable Amount</span>
                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyGrn->taxable_amount, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">CGST</span>
                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyGrn->cgst_amount, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">SGST</span>
                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyGrn->sgst_amount, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">IGST</span>
                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyGrn->igst_amount, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between border-t border-slate-200 pt-4">
                            <span class="font-bold text-slate-900">
                                GRN Total
                            </span>

                            <span class="text-2xl font-bold text-blue-700">
                                ₹{{ number_format((float) $pharmacyGrn->total_amount, 2) }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- ACTIONS --}}
            {{-- ========================================================= --}}

            <div class="no-print flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                <div class="text-xs leading-5 text-slate-500">
                    Stock has been posted to the pharmacy ledger in base units for this GRN.
                </div>


                <div class="flex flex-wrap gap-3">

                    <a
                        href="{{ route('pharmacy.grns.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        GRN Register
                    </a>
<a
    href="{{ route('pharmacy.purchase-returns.create', $pharmacyGrn) }}"
    class="rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700"
>
    Return to Supplier
</a>
                   

<form
    method="POST"
    action="{{ route('pharmacy.supplier-payables.create-from-grn', $pharmacyGrn) }}"
>
    @csrf

    <button
        type="submit"
        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
    >
        Create / View Payable
    </button>
</form>


<button
                        type="button"
                        onclick="window.print()"
                        class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Print GRN
                    </button>

                </div>

            </div>


        </div>

    </div>


    <style>
        @media print {

            aside,
            nav,
            header,
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .min-h-screen {
                min-height: auto !important;
                background: white !important;
            }

            .shadow-sm {
                box-shadow: none !important;
            }

        }
    </style>

</x-app-layout>