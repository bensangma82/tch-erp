<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Purchase Return
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pharmacyPurchaseReturn->return_no }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2 no-print">

                <a
                    href="{{ route('pharmacy.purchase-returns.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Purchase Return Register
                </a>

                <button
                    type="button"
                    onclick="window.print()"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Print Return
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



            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Purchase Return
                            </div>

                            <div class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $pharmacyPurchaseReturn->return_no }}
                            </div>

                        </div>

                        <span class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                            {{ ucfirst($pharmacyPurchaseReturn->status) }}
                        </span>

                    </div>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Return Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseReturn->return_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Supplier
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseReturn->supplier?->name ?? '—' }}
                        </div>

                        @if ($pharmacyPurchaseReturn->supplier?->code)
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $pharmacyPurchaseReturn->supplier->code }}
                            </div>
                        @endif
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Credit Note No.
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseReturn->supplier_credit_note_no ?: '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Credit Note Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseReturn->supplier_credit_note_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Reason
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ ucwords(str_replace('_', ' ', $pharmacyPurchaseReturn->reason ?? '—')) }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Created By
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseReturn->createdBy?->name ?? 'System' }}
                        </div>
                    </div>

                </div>

            </div>



            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Returned Medicines
                    </h3>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Medicine
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Batch
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    GRN
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Qty Returned
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Purchase Price
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

                            @foreach ($pharmacyPurchaseReturn->items as $item)

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
                                        {{ $item->grnItem?->grn?->grn_no ?? '—' }}
                                    </td>


                                    <td class="px-4 py-4 text-right font-bold text-red-700">
                                        -{{ number_format($item->quantity_returned) }}
                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">
                                        ₹{{ number_format((float) $item->purchase_price, 2) }}
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



            <div class="grid gap-6 lg:grid-cols-2">


                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h3 class="font-semibold text-slate-900">
                        Remarks
                    </h3>

                    <div class="mt-4 text-sm leading-6 text-slate-600">
                        {{ $pharmacyPurchaseReturn->remarks ?: 'No remarks.' }}
                    </div>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h3 class="font-semibold text-slate-900">
                        Return Summary
                    </h3>


                    <div class="mt-5 space-y-3">

                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                Gross Subtotal
                            </span>

                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseReturn->subtotal, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                Discount
                            </span>

                            <span class="font-semibold text-red-700">
                                ₹{{ number_format((float) $pharmacyPurchaseReturn->discount_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                Taxable Amount
                            </span>

                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseReturn->taxable_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">CGST</span>
                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseReturn->cgst_amount, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">SGST</span>
                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseReturn->sgst_amount, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">IGST</span>
                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseReturn->igst_amount, 2) }}
                            </span>
                        </div>


                        <div class="flex justify-between border-t border-slate-200 pt-4">

                            <span class="font-bold text-slate-900">
                                Return Total
                            </span>

                            <span class="text-2xl font-bold text-amber-700">
                                ₹{{ number_format((float) $pharmacyPurchaseReturn->total_amount, 2) }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>



            <div class="no-print flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                <div class="text-xs leading-5 text-slate-500">
                    Stock has been deducted from the pharmacy ledger for this supplier return.
                </div>


                <div class="flex flex-wrap gap-3">

                    <a
                        href="{{ route('pharmacy.purchase-returns.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Purchase Return Register
                    </a>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Print Return
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