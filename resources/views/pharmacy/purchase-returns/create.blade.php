<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Purchase Return
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Return medicines to supplier against {{ $pharmacyGrn->grn_no }}
                </p>
            </div>

            <a
                href="{{ route('pharmacy.grns.show', $pharmacyGrn) }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to GRN
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



            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">
                        GRN Summary
                    </h3>
                </div>

                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            GRN Number
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->grn_no }}
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
                            Purchase Order
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->purchaseOrder?->po_no ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            GRN Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyGrn->grn_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                </div>

            </div>



            <form
                method="POST"
                action="{{ route('pharmacy.purchase-returns.store', $pharmacyGrn) }}"
                id="purchaseReturnForm"
                class="space-y-6"
            >

                @csrf


                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Return Details
                        </h3>

                    </div>


                    <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-5">


                        <div>

                            <label
                                for="return_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Return Date *
                            </label>

                            <input
                                id="return_date"
                                name="return_date"
                                type="date"
                                required
                                value="{{ old('return_date', today()->format('Y-m-d')) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div>

                            <label
                                for="reason"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Return Reason *
                            </label>

                            <select
                                id="reason"
                                name="reason"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select reason
                                </option>

                                <option value="damaged" {{ old('reason') === 'damaged' ? 'selected' : '' }}>
                                    Damaged
                                </option>

                                <option value="wrong_item" {{ old('reason') === 'wrong_item' ? 'selected' : '' }}>
                                    Wrong Item
                                </option>

                                <option value="excess_supply" {{ old('reason') === 'excess_supply' ? 'selected' : '' }}>
                                    Excess Supply
                                </option>

                                <option value="quality_issue" {{ old('reason') === 'quality_issue' ? 'selected' : '' }}>
                                    Quality Issue
                                </option>

                                <option value="recall" {{ old('reason') === 'recall' ? 'selected' : '' }}>
                                    Recall
                                </option>

                                <option value="near_expiry" {{ old('reason') === 'near_expiry' ? 'selected' : '' }}>
                                    Near Expiry
                                </option>

                                <option value="other" {{ old('reason') === 'other' ? 'selected' : '' }}>
                                    Other
                                </option>

                            </select>

                        </div>


                        <div>

                            <label
                                for="supplier_credit_note_no"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Supplier Credit Note No.
                            </label>

                            <input
                                id="supplier_credit_note_no"
                                name="supplier_credit_note_no"
                                type="text"
                                value="{{ old('supplier_credit_note_no') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Optional"
                            >

                        </div>


                        <div>

                            <label
                                for="supplier_credit_note_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Credit Note Date
                            </label>

                            <input
                                id="supplier_credit_note_date"
                                name="supplier_credit_note_date"
                                type="date"
                                value="{{ old('supplier_credit_note_date') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div>

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



                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Medicines to Return
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Return quantity cannot exceed either the remaining GRN quantity or the stock currently available in the batch.
                        </p>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="min-w-[1250px] w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Medicine
                                    </th>

                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Batch
                                    </th>

                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Expiry
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        GRN Qty
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Already Returned
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Available Stock
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Max Return
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Return Now
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Purchase Price
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        GST
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Return Total
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @foreach ($pharmacyGrn->items as $index => $item)

                                    @php

                                        $returned =
                                            (int) (
                                                $alreadyReturned[$item->id]
                                                ?? 0
                                            );

                                        $remainingGrn =
                                            max(
                                                0,
                                                (int) $item->quantity_received
                                                - $returned
                                            );

                                        $available =
                                            (int) (
                                                $item->stockBatch?->quantity_available
                                                ?? 0
                                            );

                                        $maxReturn =
                                            min(
                                                $remainingGrn,
                                                $available
                                            );

                                    @endphp


                                    <tr
                                        class="return-row"
                                        data-max-return="{{ $maxReturn }}"
                                        data-price="{{ (float) $item->purchase_price }}"
                                        data-discount="{{ (float) $item->discount_percent }}"
                                        data-gst="{{ (float) $item->gst_percent }}"
                                    >

                                        <td class="px-4 py-4 align-top">

                                            <input
                                                type="hidden"
                                                name="items[{{ $index }}][grn_item_id]"
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


                                        <td class="px-3 py-4 align-top font-semibold text-slate-800">
                                            {{ $item->batch_number }}
                                        </td>


                                        <td class="px-3 py-4 align-top text-slate-700">
                                            {{ $item->expiry_date?->format('d M Y') ?? '—' }}
                                        </td>


                                        <td class="px-3 py-4 text-right align-top font-semibold text-slate-900">
                                            {{ number_format($item->quantity_received) }}
                                        </td>


                                        <td class="px-3 py-4 text-right align-top text-slate-600">
                                            {{ number_format($returned) }}
                                        </td>


                                        <td class="px-3 py-4 text-right align-top text-slate-600">
                                            {{ number_format($available) }}
                                        </td>


                                        <td class="px-3 py-4 text-right align-top">

                                            <span class="font-bold {{ $maxReturn > 0 ? 'text-blue-700' : 'text-slate-400' }}">
                                                {{ number_format($maxReturn) }}
                                            </span>

                                        </td>


                                        <td class="px-3 py-4 align-top">

                                            <input
                                                type="number"
                                                name="items[{{ $index }}][quantity_returned]"
                                                min="0"
                                                max="{{ $maxReturn }}"
                                                step="1"
                                                value="{{ old("items.$index.quantity_returned", 0) }}"
                                                class="return-qty w-28 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                {{ $maxReturn <= 0 ? 'readonly' : '' }}
                                            >

                                        </td>


                                        <td class="px-3 py-4 text-right align-top text-slate-700">
                                            ₹{{ number_format((float) $item->purchase_price, 2) }}
                                        </td>


                                        <td class="px-3 py-4 text-right align-top text-slate-700">
                                            {{ number_format((float) $item->gst_percent, 2) }}%
                                        </td>


                                        <td class="px-3 py-4 text-right align-top">

                                            <div class="line-total font-bold text-slate-900">
                                                ₹0.00
                                            </div>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>



                    <div class="border-t border-slate-100 bg-slate-50 px-6 py-5">

                        <div class="ml-auto max-w-md space-y-3">

                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">
                                    Gross Subtotal
                                </span>

                                <span id="subtotalPreview" class="font-semibold text-slate-900">
                                    ₹0.00
                                </span>
                            </div>


                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">
                                    Discount
                                </span>

                                <span id="discountPreview" class="font-semibold text-red-700">
                                    ₹0.00
                                </span>
                            </div>


                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">
                                    Taxable
                                </span>

                                <span id="taxablePreview" class="font-semibold text-slate-900">
                                    ₹0.00
                                </span>
                            </div>


                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">
                                    GST
                                </span>

                                <span id="gstPreview" class="font-semibold text-slate-900">
                                    ₹0.00
                                </span>
                            </div>


                            <div class="flex justify-between border-t border-slate-300 pt-3">

                                <span class="font-bold text-slate-900">
                                    Return Total
                                </span>

                                <span id="grandTotalPreview" class="text-2xl font-bold text-amber-700">
                                    ₹0.00
                                </span>

                            </div>

                        </div>

                    </div>

                </div>



                <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                    <div class="max-w-2xl text-xs leading-5 text-slate-500">
                        Completing this purchase return will immediately reduce the selected stock batch and create a Purchase Return entry in the pharmacy stock ledger.
                    </div>


                    <div class="flex gap-3">

                        <a
                            href="{{ route('pharmacy.grns.show', $pharmacyGrn) }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            id="completeReturnButton"
                            disabled
                            class="rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Complete Purchase Return
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
                        '.return-row'
                    );


                const button =
                    document.getElementById(
                        'completeReturnButton'
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

                    let hasReturn = false;
                    let valid = true;


                    rows.forEach(
                        function (row) {

                            const maxReturn =
                                Number(
                                    row.dataset.maxReturn
                                    || 0
                                );


                            const purchasePrice =
                                Number(
                                    row.dataset.price
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
                                    '.return-qty'
                                );


                            const quantity =
                                Math.max(
                                    0,
                                    Number(
                                        quantityInput.value
                                        || 0
                                    )
                                );


                            if (quantity > 0) {

                                hasReturn = true;

                                if (quantity > maxReturn) {
                                    valid = false;
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


                            subtotal += gross;
                            discountTotal += discount;
                            taxableTotal += taxable;
                            gstTotal += gst;
                            grandTotal += total;
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


                    const reason =
                        document.getElementById(
                            'reason'
                        ).value;


                    button.disabled =
                        ! hasReturn
                        || ! valid
                        || ! reason;
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
                    'reason'
                ).addEventListener(
                    'change',
                    calculate
                );


                document.getElementById(
                    'purchaseReturnForm'
                ).addEventListener(
                    'submit',
                    function (event) {

                        if (
                            ! confirm(
                                'Complete this purchase return? Pharmacy stock will be reduced immediately.'
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