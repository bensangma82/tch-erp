<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Goods Receipt Note
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Receive medicines against {{ $pharmacyPurchaseOrder->po_no }}
                </p>
            </div>

            <a
                href="{{ route('pharmacy.purchase-orders.show', $pharmacyPurchaseOrder) }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to Purchase Order
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- PO SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Purchase Order Summary
                    </h3>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            PO Number
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseOrder->po_no }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Supplier
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseOrder->supplier?->name ?? '—' }}
                        </div>

                        @if ($pharmacyPurchaseOrder->supplier?->code)
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $pharmacyPurchaseOrder->supplier->code }}
                            </div>
                        @endif

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            PO Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseOrder->po_date?->format('d M Y') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Status
                        </div>

                        <div class="mt-2 font-bold text-blue-700">
                            {{ ucwords(str_replace('_', ' ', $pharmacyPurchaseOrder->status)) }}
                        </div>

                    </div>


                </div>

            </div>



            <form
                method="POST"
                action="{{ route('pharmacy.grns.store', $pharmacyPurchaseOrder) }}"
                id="grnForm"
                class="space-y-6"
            >

                @csrf



                {{-- ========================================================= --}}
                {{-- GRN HEADER --}}
                {{-- ========================================================= --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Receipt Details
                        </h3>

                    </div>


                    <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-4">


                        <div>

                            <label
                                for="grn_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                GRN Date *
                            </label>

                            <input
                                id="grn_date"
                                name="grn_date"
                                type="date"
                                required
                                value="{{ old('grn_date', today()->format('Y-m-d')) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div>

                            <label
                                for="supplier_invoice_no"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Supplier Invoice No.
                            </label>

                            <input
                                id="supplier_invoice_no"
                                name="supplier_invoice_no"
                                type="text"
                                value="{{ old('supplier_invoice_no') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Invoice number"
                            >

                        </div>


                        <div>

                            <label
                                for="supplier_invoice_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Invoice Date
                            </label>

                            <input
                                id="supplier_invoice_date"
                                name="supplier_invoice_date"
                                type="date"
                                value="{{ old('supplier_invoice_date') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div class="md:col-span-2 lg:col-span-1">

                            <label
                                for="remarks"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Remarks
                            </label>

                            <input
                                id="remarks"
                                name="remarks"
                                type="text"
                                value="{{ old('remarks') }}"
                                maxlength="3000"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Optional remarks"
                            >

                        </div>


                    </div>

                </div>



                {{-- ========================================================= --}}
                {{-- RECEIVING TABLE --}}
                {{-- ========================================================= --}}

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Medicines to Receive
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Enter quantity received, batch number, expiry, purchase price and selling price.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="min-w-[1300px] w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Medicine
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Ordered
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Already Received
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Remaining
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Receive Now
                                    </th>

                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Batch
                                    </th>

                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Expiry
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Purchase Price
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Selling Price
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        GST
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Line Total
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @foreach ($pharmacyPurchaseOrder->items as $index => $item)

                                    @php
                                        $remaining =
                                            max(
                                                0,
                                                (int) $item->quantity_ordered
                                                - (int) $item->quantity_received
                                            );
                                    @endphp


                                    <tr
                                        class="grn-row"
                                        data-index="{{ $index }}"
                                        data-remaining="{{ $remaining }}"
                                        data-discount="{{ (float) $item->discount_percent }}"
                                        data-gst="{{ (float) $item->gst_percent }}"
                                    >

                                        <td class="px-4 py-4 align-top">

                                            <input
                                                type="hidden"
                                                name="items[{{ $index }}][purchase_order_item_id]"
                                                value="{{ $item->id }}"
                                            >

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


                                        <td class="px-3 py-4 text-right align-top font-semibold text-slate-900">
                                            {{ number_format($item->quantity_ordered) }}
                                        </td>


                                        <td class="px-3 py-4 text-right align-top text-slate-600">
                                            {{ number_format($item->quantity_received) }}
                                        </td>


                                        <td class="px-3 py-4 text-right align-top">

                                            <span class="font-bold {{ $remaining > 0 ? 'text-blue-700' : 'text-emerald-700' }}">
                                                {{ number_format($remaining) }}
                                            </span>

                                        </td>


                                        <td class="px-3 py-4 align-top">

                                            <input
                                                type="number"
                                                min="0"
                                                max="{{ $remaining }}"
                                                step="1"
                                                name="items[{{ $index }}][quantity_received]"
                                                value="{{ old("items.$index.quantity_received", 0) }}"
                                                class="receive-qty w-28 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                {{ $remaining <= 0 ? 'readonly' : '' }}
                                            >

                                        </td>


                                        <td class="px-3 py-4 align-top">

                                            <input
                                                type="text"
                                                name="items[{{ $index }}][batch_number]"
                                                value="{{ old("items.$index.batch_number") }}"
                                                class="batch-input w-36 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Batch"
                                                {{ $remaining <= 0 ? 'readonly' : '' }}
                                            >

                                        </td>


                                        <td class="px-3 py-4 align-top">

                                            <input
                                                type="date"
                                                name="items[{{ $index }}][expiry_date]"
                                                value="{{ old("items.$index.expiry_date") }}"
                                                class="expiry-input w-40 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                {{ $remaining <= 0 ? 'readonly' : '' }}
                                            >

                                        </td>


                                        <td class="px-3 py-4 align-top">

                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                name="items[{{ $index }}][purchase_price]"
                                                value="{{ old("items.$index.purchase_price", $item->unit_cost) }}"
                                                class="purchase-price w-32 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                {{ $remaining <= 0 ? 'readonly' : '' }}
                                            >

                                        </td>


                                        <td class="px-3 py-4 align-top">

                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                name="items[{{ $index }}][selling_price]"
                                                value="{{ old("items.$index.selling_price", 0) }}"
                                                class="selling-price w-32 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                {{ $remaining <= 0 ? 'readonly' : '' }}
                                            >

                                        </td>


                                        <td class="px-3 py-4 text-right align-top text-sm text-slate-700">

                                            {{ number_format((float) $item->gst_percent, 2) }}%

                                        </td>


                                        <td class="px-3 py-4 text-right align-top">

                                            <div class="line-total pt-2 font-bold text-slate-900">
                                                ₹0.00
                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>



                    {{-- ========================================================= --}}
                    {{-- TOTAL PREVIEW --}}
                    {{-- ========================================================= --}}

                    <div class="border-t border-slate-100 bg-slate-50 px-6 py-5">

                        <div class="ml-auto max-w-md space-y-3">


                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Gross Subtotal
                                </span>

                                <span
                                    id="subtotalPreview"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Discount
                                </span>

                                <span
                                    id="discountPreview"
                                    class="font-semibold text-red-700"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    Taxable
                                </span>

                                <span
                                    id="taxablePreview"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex justify-between text-sm">

                                <span class="text-slate-500">
                                    GST
                                </span>

                                <span
                                    id="gstPreview"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex justify-between border-t border-slate-300 pt-3">

                                <span class="font-bold text-slate-900">
                                    GRN Total
                                </span>

                                <span
                                    id="grandTotalPreview"
                                    class="text-2xl font-bold text-blue-700"
                                >
                                    ₹0.00
                                </span>

                            </div>


                        </div>

                    </div>

                </div>



                {{-- ========================================================= --}}
                {{-- ACTIONS --}}
                {{-- ========================================================= --}}

                <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                    <div class="max-w-2xl text-xs leading-5 text-slate-500">
                        Completing this GRN will immediately increase pharmacy stock,
                        create or update medicine batches, post Stock In ledger entries,
                        and update the Purchase Order receiving status.
                    </div>


                    <div class="flex gap-3">

                        <a
                            href="{{ route('pharmacy.purchase-orders.show', $pharmacyPurchaseOrder) }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            id="completeGrnButton"
                            disabled
                            class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Complete GRN
                        </button>

                    </div>

                </div>


            </form>

        </div>

    </div>



    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const rows =
                    document.querySelectorAll(
                        '.grn-row'
                    );


                const button =
                    document.getElementById(
                        'completeGrnButton'
                    );


                function money(value) {

                    return '₹'
                        + Number(value || 0)
                            .toLocaleString(
                                'en-IN',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            );
                }



                function calculate() {

                    let subtotal = 0;
                    let discountTotal = 0;
                    let taxableTotal = 0;
                    let gstTotal = 0;
                    let grandTotal = 0;

                    let hasReceipt = false;
                    let valid = true;


                    rows.forEach(
                        function (row) {

                            const remaining =
                                Number(
                                    row.dataset.remaining
                                    || 0
                                );


                            const discountPercent =
                                Number(
                                    row.dataset.discount
                                    || 0
                                );


                            const gstPercent =
                                Number(
                                    row.dataset.gst
                                    || 0
                                );


                            const quantityInput =
                                row.querySelector(
                                    '.receive-qty'
                                );


                            const batchInput =
                                row.querySelector(
                                    '.batch-input'
                                );


                            const expiryInput =
                                row.querySelector(
                                    '.expiry-input'
                                );


                            const priceInput =
                                row.querySelector(
                                    '.purchase-price'
                                );


                            const quantity =
                                Math.max(
                                    0,
                                    Number(
                                        quantityInput.value
                                        || 0
                                    )
                                );


                            const purchasePrice =
                                Math.max(
                                    0,
                                    Number(
                                        priceInput.value
                                        || 0
                                    )
                                );


                            if (quantity > 0) {

                                hasReceipt =
                                    true;


                                if (
                                    quantity > remaining
                                    || ! batchInput.value.trim()
                                    || ! expiryInput.value
                                ) {

                                    valid =
                                        false;
                                }
                            }


                            const gross =
                                quantity
                                * purchasePrice;


                            const discount =
                                gross
                                * discountPercent
                                / 100;


                            const taxable =
                                gross
                                - discount;


                            const gst =
                                taxable
                                * gstPercent
                                / 100;


                            const total =
                                taxable
                                + gst;


                            row.querySelector(
                                '.line-total'
                            ).textContent =
                                money(total);


                            subtotal +=
                                gross;

                            discountTotal +=
                                discount;

                            taxableTotal +=
                                taxable;

                            gstTotal +=
                                gst;

                            grandTotal +=
                                total;
                        }
                    );


                    document.getElementById(
                        'subtotalPreview'
                    ).textContent =
                        money(subtotal);


                    document.getElementById(
                        'discountPreview'
                    ).textContent =
                        money(discountTotal);


                    document.getElementById(
                        'taxablePreview'
                    ).textContent =
                        money(taxableTotal);


                    document.getElementById(
                        'gstPreview'
                    ).textContent =
                        money(gstTotal);


                    document.getElementById(
                        'grandTotalPreview'
                    ).textContent =
                        money(grandTotal);


                    button.disabled =
                        ! hasReceipt
                        || ! valid;
                }



                rows.forEach(
                    function (row) {

                        row.querySelectorAll(
                            'input'
                        ).forEach(
                            function (input) {

                                input.addEventListener(
                                    'input',
                                    calculate
                                );

                                input.addEventListener(
                                    'change',
                                    calculate
                                );
                            }
                        );
                    }
                );


                document.getElementById(
                    'grnForm'
                ).addEventListener(
                    'submit',
                    function (event) {

                        if (
                            ! confirm(
                                'Complete this GRN? Pharmacy stock will be updated immediately.'
                            )
                        ) {

                            event.preventDefault();
                        }
                    }
                );


                calculate();
            }
        );

    </script>

</x-app-layout>