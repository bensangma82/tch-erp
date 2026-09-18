<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Dispose / Write-off Stock
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Record expired, damaged, recalled or unusable pharmacy stock
                </p>

            </div>


            <a
                href="{{ route('pharmacy.stock-batches.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back to Pharmacy Stock
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- BATCH SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-5">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Medicine
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $stockBatch->medicine?->generic_name ?? '—' }}
                        </div>

                        @if ($stockBatch->medicine?->brand_name)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $stockBatch->medicine->brand_name }}
                            </div>

                        @endif

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Batch
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $stockBatch->batch_number }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Expiry
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $stockBatch->expiry_date?->format('d M Y') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Hospital Stock
                        </div>

                        <div class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($stockBatch->quantity_available) }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            All locations combined
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Purchase Price
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            ₹{{ number_format((float) $stockBatch->purchase_price, 2) }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            per unit
                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- LOCATION STOCK SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Stock by Location
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Disposal must be recorded against the location where the medicine is physically held.
                    </p>

                </div>


                <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">

                    @forelse ($locationBalances as $balance)

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                            <div class="text-sm font-semibold text-slate-900">
                                {{ $balance->location?->name ?? 'Unknown Location' }}
                            </div>

                            <div class="mt-2 text-2xl font-bold text-slate-900">
                                {{ number_format($balance->quantity_available) }}
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                units available
                            </div>

                        </div>

                    @empty

                        <div class="sm:col-span-2 lg:col-span-3">

                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                No location currently has available stock for this batch.
                            </div>

                        </div>

                    @endforelse

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- DISPOSAL FORM --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-red-200 bg-white shadow-sm">

                <form
                    method="POST"
                    action="{{ route('pharmacy.disposals.store', $stockBatch) }}"
                    id="disposalForm"
                >

                    @csrf


                    <div class="border-b border-red-100 bg-red-50 px-6 py-5">

                        <h3 class="font-semibold text-red-900">
                            Disposal Details
                        </h3>

                        <p class="mt-1 text-xs text-red-700">
                            This action permanently reduces stock from the selected physical location and from total hospital inventory.
                        </p>

                    </div>



                    <div class="space-y-6 p-6">


                        {{-- LOCATION --}}

                        <div>

                            <label
                                for="pharmacy_stock_location_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Stock Location
                            </label>

                            <select
                                id="pharmacy_stock_location_id"
                                name="pharmacy_stock_location_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                                <option value="">
                                    Select location
                                </option>


                                @foreach ($locationBalances as $balance)

                                    <option
                                        value="{{ $balance->pharmacy_stock_location_id }}"
                                        data-available="{{ (int) $balance->quantity_available }}"
                                        @selected(
                                            old(
                                                'pharmacy_stock_location_id',
                                                $selectedLocationId
                                            )
                                            == $balance->pharmacy_stock_location_id
                                        )
                                    >
                                        {{ $balance->location?->name }}
                                        — {{ number_format($balance->quantity_available) }} available
                                    </option>

                                @endforeach

                            </select>


                            <div
                                id="locationAvailabilityText"
                                class="mt-2 text-xs text-slate-500"
                            >
                                Select the location where the stock is physically present.
                            </div>

                        </div>



                        {{-- REASON --}}

                        <div>

                            <label
                                for="reason"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Disposal Reason
                            </label>

                            <select
                                id="reason"
                                name="reason"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                                <option value="">
                                    Select reason
                                </option>

                                <option
                                    value="expired"
                                    @selected(old('reason') === 'expired')
                                >
                                    Expired Stock
                                </option>

                                <option
                                    value="damaged"
                                    @selected(old('reason') === 'damaged')
                                >
                                    Damaged Stock
                                </option>

                                <option
                                    value="contaminated"
                                    @selected(old('reason') === 'contaminated')
                                >
                                    Contaminated Stock
                                </option>

                                <option
                                    value="broken"
                                    @selected(old('reason') === 'broken')
                                >
                                    Broken / Spillage
                                </option>

                                <option
                                    value="recall"
                                    @selected(old('reason') === 'recall')
                                >
                                    Product Recall
                                </option>

                                <option
                                    value="other"
                                    @selected(old('reason') === 'other')
                                >
                                    Other
                                </option>

                            </select>

                        </div>



                        {{-- QUANTITY --}}

                        <div>

                            <label
                                for="quantity"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Quantity to Dispose
                            </label>

                            <input
                                id="quantity"
                                name="quantity"
                                type="number"
                                min="1"
                                step="1"
                                value="{{ old('quantity') }}"
                                required
                                disabled
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500 disabled:bg-slate-100"
                                placeholder="Select a stock location first"
                            >


                            <div
                                id="maximumAvailableText"
                                class="mt-2 text-xs text-slate-500"
                            >
                                Maximum available will be based on the selected location.
                            </div>

                        </div>



                        {{-- REMARKS --}}

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
                                maxlength="2000"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                placeholder="Optional details such as damage description, recall notice, destruction method, etc."
                            >{{ old('remarks') }}</textarea>

                        </div>



                        {{-- PREVIEW --}}

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                            <div class="text-sm font-semibold text-slate-900">
                                Disposal Preview
                            </div>


                            <div class="mt-5 grid gap-5 sm:grid-cols-4">

                                <div>

                                    <div class="text-xs uppercase tracking-wide text-slate-400">
                                        Location
                                    </div>

                                    <div
                                        id="locationPreview"
                                        class="mt-2 text-lg font-bold text-slate-900"
                                    >
                                        —
                                    </div>

                                </div>


                                <div>

                                    <div class="text-xs uppercase tracking-wide text-slate-400">
                                        Location Stock
                                    </div>

                                    <div
                                        id="locationStockPreview"
                                        class="mt-2 text-2xl font-bold text-slate-900"
                                    >
                                        —
                                    </div>

                                </div>


                                <div>

                                    <div class="text-xs uppercase tracking-wide text-slate-400">
                                        Disposal
                                    </div>

                                    <div
                                        id="disposalQuantityPreview"
                                        class="mt-2 text-2xl font-bold text-red-700"
                                    >
                                        —
                                    </div>

                                </div>


                                <div>

                                    <div class="text-xs uppercase tracking-wide text-slate-400">
                                        Remaining at Location
                                    </div>

                                    <div
                                        id="remainingStockPreview"
                                        class="mt-2 text-2xl font-bold text-slate-900"
                                    >
                                        —
                                    </div>

                                </div>

                            </div>


                            <div class="mt-5 border-t border-slate-200 pt-5">

                                <div class="grid gap-4 sm:grid-cols-2">


                                    <div>

                                        <div class="text-xs uppercase tracking-wide text-slate-400">
                                            Hospital Stock After Disposal
                                        </div>

                                        <div
                                            id="hospitalRemainingPreview"
                                            class="mt-2 text-xl font-bold text-slate-900"
                                        >
                                            {{ number_format($stockBatch->quantity_available) }}
                                        </div>

                                    </div>


                                    <div class="sm:text-right">

                                        <div class="text-xs uppercase tracking-wide text-slate-400">
                                            Estimated Write-off Value
                                        </div>

                                        <div
                                            id="writeOffValuePreview"
                                            class="mt-2 text-xl font-bold text-red-700"
                                        >
                                            ₹0.00
                                        </div>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Based on purchase price
                                        </p>

                                    </div>

                                </div>

                            </div>

                        </div>



                        {{-- WARNING --}}

                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">

                            <div class="text-sm font-semibold text-amber-800">
                                Permanent stock write-off
                            </div>

                            <p class="mt-1 text-xs leading-5 text-amber-700">
                                The disposed quantity will be deducted from the selected stock location
                                and from total hospital stock. It will also be recorded in the stock
                                movement ledger and linked to a permanent pharmacy disposal record.
                                Do not use this function for medicines returned by a patient.
                            </p>

                        </div>

                    </div>



                    <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-5 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('pharmacy.stock-batches.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            id="disposeButton"
                            disabled
                            class="inline-flex items-center justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Complete Disposal
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const hospitalStock =
                    {{ (int) $stockBatch->quantity_available }};


                const purchasePrice =
                    {{ (float) $stockBatch->purchase_price }};


                const locationInput =
                    document.getElementById(
                        'pharmacy_stock_location_id'
                    );


                const quantityInput =
                    document.getElementById(
                        'quantity'
                    );


                const reasonInput =
                    document.getElementById(
                        'reason'
                    );


                const locationAvailabilityText =
                    document.getElementById(
                        'locationAvailabilityText'
                    );


                const maximumAvailableText =
                    document.getElementById(
                        'maximumAvailableText'
                    );


                const locationPreview =
                    document.getElementById(
                        'locationPreview'
                    );


                const locationStockPreview =
                    document.getElementById(
                        'locationStockPreview'
                    );


                const quantityPreview =
                    document.getElementById(
                        'disposalQuantityPreview'
                    );


                const remainingPreview =
                    document.getElementById(
                        'remainingStockPreview'
                    );


                const hospitalRemainingPreview =
                    document.getElementById(
                        'hospitalRemainingPreview'
                    );


                const writeOffPreview =
                    document.getElementById(
                        'writeOffValuePreview'
                    );


                const disposeButton =
                    document.getElementById(
                        'disposeButton'
                    );


                const form =
                    document.getElementById(
                        'disposalForm'
                    );


                function money(value) {

                    return '₹'
                        + Number(
                            value || 0
                        ).toLocaleString(
                            'en-IN',
                            {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        );
                }


                function getSelectedLocation() {

                    const option =
                        locationInput
                            .options[
                                locationInput.selectedIndex
                            ];


                    if (
                        ! option
                        ||
                        ! option.value
                    ) {

                        return null;
                    }


                    return {

                        id:
                            option.value,

                        name:
                            option.text
                                .split('—')[0]
                                .trim(),

                        available:
                            Number(
                                option.dataset.available
                                || 0
                            )
                    };
                }


                function updatePreview() {

                    const location =
                        getSelectedLocation();


                    const quantity =
                        Math.max(
                            0,
                            Number(
                                quantityInput.value
                                || 0
                            )
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | No Location Selected
                    |--------------------------------------------------------------------------
                    */

                    if (! location) {

                        quantityInput.disabled =
                            true;


                        quantityInput.removeAttribute(
                            'max'
                        );


                        quantityInput.placeholder =
                            'Select a stock location first';


                        locationAvailabilityText.textContent =
                            'Select the location where the stock is physically present.';


                        maximumAvailableText.textContent =
                            'Maximum available will be based on the selected location.';


                        locationPreview.textContent =
                            '—';


                        locationStockPreview.textContent =
                            '—';


                        quantityPreview.textContent =
                            '—';


                        remainingPreview.textContent =
                            '—';


                        hospitalRemainingPreview.textContent =
                            hospitalStock.toLocaleString(
                                'en-IN'
                            );


                        writeOffPreview.textContent =
                            money(0);


                        disposeButton.disabled =
                            true;


                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Location Selected
                    |--------------------------------------------------------------------------
                    */

                    quantityInput.disabled =
                        false;


                    quantityInput.max =
                        location.available;


                    quantityInput.placeholder =
                        'Enter quantity';


                    locationAvailabilityText.textContent =
                        location.name
                        + ' currently has '
                        + location.available.toLocaleString(
                            'en-IN'
                        )
                        + ' units.';


                    maximumAvailableText.textContent =
                        'Maximum available at '
                        + location.name
                        + ': '
                        + location.available.toLocaleString(
                            'en-IN'
                        );


                    locationPreview.textContent =
                        location.name;


                    locationStockPreview.textContent =
                        location.available.toLocaleString(
                            'en-IN'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Validate Quantity
                    |--------------------------------------------------------------------------
                    */

                    const invalidQuantity =
                        quantity <= 0
                        ||
                        quantity > location.available
                        ||
                        quantity > hospitalStock;


                    quantityPreview.textContent =
                        quantity > 0
                            ? '-'
                                + quantity.toLocaleString(
                                    'en-IN'
                                )
                            : '—';


                    const locationRemaining =
                        Math.max(
                            0,
                            location.available
                            - quantity
                        );


                    remainingPreview.textContent =
                        locationRemaining.toLocaleString(
                            'en-IN'
                        );


                    const hospitalRemaining =
                        Math.max(
                            0,
                            hospitalStock
                            - quantity
                        );


                    hospitalRemainingPreview.textContent =
                        hospitalRemaining.toLocaleString(
                            'en-IN'
                        );


                    writeOffPreview.textContent =
                        money(
                            quantity
                            * purchasePrice
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Highlight Over-disposal
                    |--------------------------------------------------------------------------
                    */

                    if (
                        quantity
                        > location.available
                    ) {

                        remainingPreview.className =
                            'mt-2 text-2xl font-bold text-red-700';

                    } else {

                        remainingPreview.className =
                            'mt-2 text-2xl font-bold text-slate-900';
                    }


                    if (
                        quantity
                        > hospitalStock
                    ) {

                        hospitalRemainingPreview.className =
                            'mt-2 text-xl font-bold text-red-700';

                    } else {

                        hospitalRemainingPreview.className =
                            'mt-2 text-xl font-bold text-slate-900';
                    }


                    disposeButton.disabled =
                        invalidQuantity
                        ||
                        ! reasonInput.value;
                }


                locationInput.addEventListener(
                    'change',
                    function () {

                        quantityInput.value =
                            '';

                        updatePreview();
                    }
                );


                quantityInput.addEventListener(
                    'input',
                    updatePreview
                );


                reasonInput.addEventListener(
                    'change',
                    updatePreview
                );


                form.addEventListener(
                    'submit',
                    function (event) {

                        const location =
                            getSelectedLocation();


                        if (! location) {

                            event.preventDefault();

                            return;
                        }


                        const quantity =
                            Number(
                                quantityInput.value
                                || 0
                            );


                        if (
                            quantity <= 0
                            ||
                            quantity > location.available
                        ) {

                            event.preventDefault();

                            return;
                        }


                        const confirmed =
                            confirm(
                                'Dispose '
                                + quantity
                                + ' unit(s) from '
                                + location.name
                                + '? This permanently removes the stock from hospital inventory.'
                            );


                        if (! confirmed) {

                            event.preventDefault();
                        }
                    }
                );


                updatePreview();
            }
        );

    </script>

</x-app-layout>