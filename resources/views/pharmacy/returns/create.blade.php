<x-app-layout>

    @php
        $isInpatient = ! empty($sale->admission_id);
    @endphp

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Pharmacy Return
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $isInpatient
                        ? 'Return inpatient medicines and reverse the running IP bill automatically'
                        : 'Return medicines against an existing pharmacy sale' }}
                </p>
            </div>

            <a
                href="{{ route('pharmacy.dispensing.show', $sale) }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back to Sale
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4">

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

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                        <div>

                            <div class="text-sm font-semibold uppercase tracking-wide text-slate-400">
                                Original Pharmacy Sale
                            </div>

                            <div class="mt-1 text-xl font-bold text-slate-900">
                                {{ $sale->sale_no }}
                            </div>

                            <div class="mt-2 text-sm text-slate-500">
                                {{ $sale->sale_at?->format('d M Y, h:i A') }}
                            </div>

                        </div>


                        <div class="text-left sm:text-right">

                            <div class="font-semibold text-slate-900">
                                {{ $sale->patient->full_name }}
                            </div>

                            <div class="mt-1 text-sm text-slate-500">
                                UHID: {{ $sale->patient->uhid }}
                            </div>

                            @if ($sale->patient->mrd_number)
                                <div class="text-sm text-slate-500">
                                    MRD: {{ $sale->patient->mrd_number }}
                                </div>
                            @endif

                        </div>

                    </div>

                </div>



                <form
                    method="POST"
                    action="{{ route('pharmacy.returns.store', $sale) }}"
                    id="returnForm"
                >

                    @csrf



                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Medicine
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Batch
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Sold
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Already Returned
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Returnable
                                    </th>

                                    @unless ($isInpatient)
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Rate
                                        </th>
                                    @endunless

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Return Qty
                                    </th>

                                    @unless ($isInpatient)
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Est. Refund
                                        </th>
                                    @endunless

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100 bg-white">

                                @foreach ($returnableItems as $index => $item)

                                    <tr class="return-row">

                                        <td class="px-6 py-4">

                                            <div class="font-semibold text-slate-900">
                                                {{ $item->medicine_name }}
                                            </div>

                                            @if ($item->brand_name)
                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ $item->brand_name }}
                                                </div>
                                            @endif

                                            @if ($item->strength)
                                                <div class="text-xs text-slate-500">
                                                    {{ $item->strength }}
                                                </div>
                                            @endif

                                            <input
                                                type="hidden"
                                                name="items[{{ $index }}][sale_item_id]"
                                                value="{{ $item->id }}"
                                            >

                                        </td>


                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            {{ $item->batch_number }}
                                        </td>


                                        <td class="px-4 py-4 text-right font-semibold text-slate-700">
                                            {{ $item->quantity }}
                                        </td>


                                        <td class="px-4 py-4 text-right text-slate-600">
                                            {{ $item->already_returned }}
                                        </td>


                                        <td class="px-4 py-4 text-right font-semibold text-slate-900">
                                            {{ $item->returnable_quantity }}
                                        </td>


                                        @unless ($isInpatient)
                                            <td class="px-4 py-4 text-right text-slate-700">

                                                ₹{{ number_format((float) $item->unit_price, 2) }}

                                                @if ((float) $item->gst_percent > 0)
                                                    <div class="mt-1 text-[10px] text-slate-400">
                                                        GST {{ number_format((float) $item->gst_percent, 2) }}%
                                                    </div>
                                                @endif

                                            </td>
                                        @endunless


                                        <td class="px-4 py-4 text-right">

                                            <input
                                                type="number"
                                                name="items[{{ $index }}][quantity]"
                                                value="{{ old("items.$index.quantity", 0) }}"
                                                min="0"
                                                max="{{ $item->returnable_quantity }}"
                                                step="1"
                                                data-unit-price="{{ (float) $item->unit_price }}"
                                                data-max="{{ $item->returnable_quantity }}"
                                                class="return-quantity w-24 rounded-lg border-slate-300 text-right shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100"
                                                @disabled($item->returnable_quantity <= 0)
                                            >

                                        </td>


                                        @unless ($isInpatient)
                                            <td class="px-4 py-4 text-right">

                                                <span class="estimated-refund font-bold text-slate-900">
                                                    ₹0.00
                                                </span>

                                            </td>
                                        @endunless

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>



                    <div class="grid gap-6 border-t border-slate-100 p-6 lg:grid-cols-2">


                        <div class="space-y-5">

                            <div>

                                <label
                                    for="reason"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Reason for Return
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

                                    <option value="Wrong medicine issued">
                                        Wrong medicine issued
                                    </option>

                                    <option value="Excess quantity issued">
                                        Excess quantity issued
                                    </option>

                                    <option value="Doctor changed prescription">
                                        Doctor changed prescription
                                    </option>

                                    <option value="Patient did not require medicine">
                                        Patient did not require medicine
                                    </option>

                                    <option value="Billing correction">
                                        Billing correction
                                    </option>

                                    <option value="Other">
                                        Other
                                    </option>

                                </select>

                            </div>



                            @unless ($isInpatient)

                                <div>

                                    <label
                                        for="refund_mode"
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Refund Mode
                                    </label>

                                    <select
                                        id="refund_mode"
                                        name="refund_mode"
                                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >

                                        <option value="cash">Cash</option>
                                        <option value="upi">UPI</option>
                                        <option value="card">Card</option>
                                        <option value="credit">Credit Adjustment</option>
                                        <option value="mhis">MHIS Adjustment</option>
                                        <option value="none">No Immediate Refund</option>

                                    </select>

                                </div>


                                <div
                                    id="transactionReferenceWrapper"
                                    class="hidden"
                                >

                                    <label
                                        for="transaction_reference"
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Transaction Reference
                                    </label>

                                    <input
                                        id="transaction_reference"
                                        name="transaction_reference"
                                        type="text"
                                        value="{{ old('transaction_reference') }}"
                                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="UPI / card refund reference"
                                    >

                                </div>

                            @else

                                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                                    <div class="text-sm font-semibold text-blue-800">
                                        IP Billing Reversal
                                    </div>
                                    <p class="mt-1 text-xs leading-5 text-blue-700">
                                        No cash, UPI or card refund is collected here.
                                        The returned medicine value will be reversed automatically
                                        from the patient's running inpatient bill.
                                    </p>
                                </div>

                            @endunless



                            <div>

                                <label
                                    for="remarks"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Remarks
                                </label>

                                <textarea
                                    id="remarks"
                                    name="remarks"
                                    rows="4"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Optional additional remarks"
                                >{{ old('remarks') }}</textarea>

                            </div>

                        </div>



                        <div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                                <div class="text-sm font-semibold text-slate-900">
                                    Return Summary
                                </div>

                                <div class="mt-5 space-y-4">

                                    <div class="flex items-center justify-between">

                                        <span class="text-sm text-slate-500">
                                            Medicines Selected
                                        </span>

                                        <span
                                            id="selectedItemCount"
                                            class="font-semibold text-slate-900"
                                        >
                                            0
                                        </span>

                                    </div>


                                    <div class="flex items-center justify-between">

                                        <span class="text-sm text-slate-500">
                                            Total Quantity
                                        </span>

                                        <span
                                            id="returnQuantityTotal"
                                            class="font-semibold text-slate-900"
                                        >
                                            0
                                        </span>

                                    </div>


                                    <div class="border-t border-slate-200 pt-4">

                                        @if ($isInpatient)

                                            <div>
                                                <div class="font-bold text-slate-900">
                                                    IP Billing
                                                </div>
                                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                                    The return value will be calculated from the original pharmacy sale
                                                    and reversed automatically from the running IP bill.
                                                </p>
                                            </div>

                                            <span id="estimatedRefundTotal" class="hidden">₹0.00</span>

                                        @else

                                            <div class="flex items-center justify-between">

                                                <span class="font-bold text-slate-900">
                                                    Estimated Refund
                                                </span>

                                                <span
                                                    id="estimatedRefundTotal"
                                                    class="text-2xl font-bold text-slate-900"
                                                >
                                                    ₹0.00
                                                </span>

                                            </div>

                                        @endif

                                    </div>

                                </div>

                            </div>



                            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">

                                <div class="text-sm font-semibold text-amber-800">
                                    Important
                                </div>

                                <p class="mt-1 text-xs leading-5 text-amber-700">
                                    @if ($isInpatient)
                                        Completing this return will restore the selected medicines to Pharmacy stock,
                                        create permanent stock movement records, and post an auditable reversal to
                                        the running IP bill. The original sale and original IP charge remain unchanged.
                                    @else
                                        Completing this return will add the selected medicines
                                        back to their original stock batches and create permanent
                                        stock movement records. The original sale will not be deleted.
                                    @endif
                                </p>

                            </div>



                            <button
                                type="submit"
                                id="completeReturnButton"
                                disabled
                                class="mt-6 w-full rounded-xl bg-red-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                {{ $isInpatient
                                    ? 'Complete Return & Reverse IP Bill'
                                    : 'Complete Return & Restore Stock' }}
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const isInpatient = @json($isInpatient);

                const form =
                    document.getElementById(
                        'returnForm'
                    );

                const rows =
                    document.querySelectorAll(
                        '.return-row'
                    );

                const selectedItemCount =
                    document.getElementById(
                        'selectedItemCount'
                    );

                const returnQuantityTotal =
                    document.getElementById(
                        'returnQuantityTotal'
                    );

                const estimatedRefundTotal =
                    document.getElementById(
                        'estimatedRefundTotal'
                    );

                const completeButton =
                    document.getElementById(
                        'completeReturnButton'
                    );

                const refundMode =
                    document.getElementById(
                        'refund_mode'
                    );

                const transactionWrapper =
                    document.getElementById(
                        'transactionReferenceWrapper'
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

                    let selected = 0;
                    let quantityTotal = 0;
                    let refundTotal = 0;
                    let invalid = false;


                    rows.forEach(
                        function (row) {

                            const input =
                                row.querySelector(
                                    '.return-quantity'
                                );

                            const refund =
                                row.querySelector(
                                    '.estimated-refund'
                                );


                            const qty =
                                Math.max(
                                    0,
                                    Number(
                                        input.value
                                        || 0
                                    )
                                );


                            const max =
                                Number(
                                    input.dataset.max
                                    || 0
                                );


                            const price =
                                Number(
                                    input.dataset.unitPrice
                                    || 0
                                );


                            if (qty > 0) {

                                selected++;

                                quantityTotal += qty;

                                refundTotal +=
                                    qty * price;
                            }


                            if (qty > max) {

                                invalid = true;

                                input.classList.add(
                                    'border-red-400',
                                    'bg-red-50'
                                );

                            } else {

                                input.classList.remove(
                                    'border-red-400',
                                    'bg-red-50'
                                );
                            }


                            if (refund) {
                                refund.textContent =
                                    money(
                                        qty * price
                                    );
                            }
                        }
                    );


                    selectedItemCount.textContent =
                        selected;

                    returnQuantityTotal.textContent =
                        quantityTotal;

                    estimatedRefundTotal.textContent =
                        money(
                            refundTotal
                        );


                    completeButton.disabled =
                        selected === 0
                        || invalid;
                }



                function updateRefundMode() {

                    if (
                        ! refundMode
                        || ! transactionWrapper
                    ) {
                        return;
                    }

                    if (
                        refundMode.value === 'upi'
                        || refundMode.value === 'card'
                    ) {

                        transactionWrapper.classList.remove(
                            'hidden'
                        );

                    } else {

                        transactionWrapper.classList.add(
                            'hidden'
                        );
                    }
                }



                document
                    .querySelectorAll(
                        '.return-quantity'
                    )
                    .forEach(
                        function (input) {

                            input.addEventListener(
                                'input',
                                calculate
                            );
                        }
                    );


                if (refundMode) {
                    refundMode.addEventListener(
                        'change',
                        updateRefundMode
                    );
                }


                form.addEventListener(
                    'submit',
                    function (event) {

                        if (
                            ! confirm(
                                isInpatient
                                    ? 'Complete this inpatient medicine return, restore stock and reverse the IP bill?'
                                    : 'Complete this medicine return and restore stock?'
                            )
                        ) {

                            event.preventDefault();
                        }
                    }
                );


                calculate();

                updateRefundMode();
            }
        );

    </script>

</x-app-layout>