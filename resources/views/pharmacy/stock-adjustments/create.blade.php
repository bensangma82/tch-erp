<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Stock Adjustment
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Record a controlled manual stock movement at a specific stock location
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('pharmacy.stock-batches.movements', $stockBatch) }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Stock History
                </a>

                <a
                    href="{{ route('pharmacy.stock-batches.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Back to Stock
                </a>

            </div>

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
            {{-- BATCH INFORMATION --}}
            {{-- ========================================================= --}}

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Stock Batch
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Verify the medicine and batch before making an adjustment
                    </p>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


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

                        @if ($stockBatch->medicine?->strength)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $stockBatch->medicine->strength }}
                            </div>

                        @endif

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Batch Number
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


                        @if (
                            $stockBatch->expiry_date
                            && $stockBatch->expiry_date->copy()->startOfDay()->lt(today())
                        )

                            <div class="mt-1 text-xs font-bold text-red-600">
                                Expired
                            </div>

                        @elseif (
                            $stockBatch->expiry_date
                            && $stockBatch->expiry_date
                                ->copy()
                                ->startOfDay()
                                ->lte(today()->copy()->addDays(90))
                        )

                            <div class="mt-1 text-xs font-semibold text-orange-600">
                                Near expiry
                            </div>

                        @endif

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Hospital Stock
                        </div>

                        <div class="mt-2 text-3xl font-bold text-slate-900">
                            {{ number_format($stockBatch->quantity_available) }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            All locations combined
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
                        Current physical balance for this batch at each stock location
                    </p>

                </div>


                <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">

                    @foreach ($locations as $location)

                        @php
                            $balance =
                                $locationBalances->get(
                                    $location->id
                                );

                            $quantity =
                                $balance
                                    ? (int) $balance->quantity_available
                                    : 0;
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                            <div class="text-sm font-semibold text-slate-900">
                                {{ $location->name }}
                            </div>

                            <div class="mt-2 text-2xl font-bold text-slate-900">
                                {{ number_format($quantity) }}
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                units available
                            </div>

                        </div>

                    @endforeach

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- ADJUSTMENT FORM --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <form
                    method="POST"
                    action="{{ route('pharmacy.stock-batches.adjust.store', $stockBatch) }}"
                    id="adjustmentForm"
                >

                    @csrf


                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Adjustment Details
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Every adjustment creates a permanent stock ledger entry
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
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select location
                                </option>


                                @foreach ($locations as $location)

                                    @php
                                        $balance =
                                            $locationBalances->get(
                                                $location->id
                                            );

                                        $locationQuantity =
                                            $balance
                                                ? (int) $balance->quantity_available
                                                : 0;
                                    @endphp


                                    <option
                                        value="{{ $location->id }}"
                                        data-balance="{{ $locationQuantity }}"
                                        @selected(
                                            old('pharmacy_stock_location_id')
                                            == $location->id
                                        )
                                    >
                                        {{ $location->name }}
                                        — {{ number_format($locationQuantity) }} available
                                    </option>

                                @endforeach

                            </select>


                            <div
                                id="locationHelp"
                                class="mt-2 text-xs text-slate-500"
                            >
                                Select the physical location where this adjustment applies.
                            </div>

                        </div>



                        {{-- ADJUSTMENT TYPE --}}

                        <div>

                            <label
                                for="adjustment_type"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Adjustment Type
                            </label>


                            <select
                                id="adjustment_type"
                                name="adjustment_type"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select adjustment type
                                </option>


                                <optgroup label="Increase Stock">

                                    <option
                                        value="stock_in"
                                        @selected(old('adjustment_type') === 'stock_in')
                                    >
                                        Stock In
                                    </option>

                                    <option
                                        value="correction_plus"
                                        @selected(old('adjustment_type') === 'correction_plus')
                                    >
                                        Stock Correction +
                                    </option>

                                    <option
                                        value="other_plus"
                                        @selected(old('adjustment_type') === 'other_plus')
                                    >
                                        Other Increase
                                    </option>

                                </optgroup>


                                <optgroup label="Decrease Stock">

                                    <option
                                        value="correction_minus"
                                        @selected(old('adjustment_type') === 'correction_minus')
                                    >
                                        Stock Correction −
                                    </option>

                                    <option
                                        value="damaged"
                                        @selected(old('adjustment_type') === 'damaged')
                                    >
                                        Damaged Stock
                                    </option>

                                    <option
                                        value="expired_writeoff"
                                        @selected(old('adjustment_type') === 'expired_writeoff')
                                    >
                                        Expired Stock Write-off
                                    </option>

                                    <option
                                        value="lost"
                                        @selected(old('adjustment_type') === 'lost')
                                    >
                                        Lost / Missing Stock
                                    </option>

                                    <option
                                        value="other_minus"
                                        @selected(old('adjustment_type') === 'other_minus')
                                    >
                                        Other Decrease
                                    </option>

                                </optgroup>

                            </select>


                            <div
                                id="adjustmentTypeHelp"
                                class="mt-2 text-xs text-slate-500"
                            >
                                Select whether the adjustment should increase or decrease stock.
                            </div>

                        </div>



                        {{-- QUANTITY --}}

                        <div>

                            <label
                                for="quantity"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Quantity
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
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100"
                                placeholder="Select a stock location first"
                            >

                            <div
                                id="quantityHelp"
                                class="mt-2 text-xs text-slate-500"
                            >
                                Select a location before entering the quantity.
                            </div>

                        </div>



                        {{-- REASON --}}

                        <div>

                            <label
                                for="reason"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Reason
                            </label>

                            <input
                                id="reason"
                                name="reason"
                                type="text"
                                value="{{ old('reason') }}"
                                required
                                maxlength="255"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Example: Physical count correction"
                            >

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
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Optional additional details"
                            >{{ old('remarks') }}</textarea>

                        </div>



                        {{-- ================================================= --}}
                        {{-- PREVIEW --}}
                        {{-- ================================================= --}}

                        <div
                            id="previewBox"
                            class="rounded-xl border border-slate-200 bg-slate-50 p-5"
                        >

                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                <div>

                                    <div class="text-sm font-semibold text-slate-900">
                                        Adjustment Preview
                                    </div>

                                    <div
                                        id="previewMessage"
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        Select a location, adjustment type and quantity.
                                    </div>

                                </div>


                                <span
                                    id="directionBadge"
                                    class="hidden rounded-full px-3 py-1 text-xs font-semibold"
                                >
                                </span>

                            </div>


                            <div class="mt-5 grid gap-5 sm:grid-cols-4">


                                <div>

                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
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

                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Location Balance
                                    </div>

                                    <div
                                        id="currentLocationBalancePreview"
                                        class="mt-2 text-2xl font-bold text-slate-900"
                                    >
                                        —
                                    </div>

                                </div>



                                <div>

                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Adjustment
                                    </div>

                                    <div
                                        id="changePreview"
                                        class="mt-2 text-2xl font-bold text-slate-400"
                                    >
                                        —
                                    </div>

                                </div>



                                <div>

                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        New Location Balance
                                    </div>

                                    <div
                                        id="newLocationBalancePreview"
                                        class="mt-2 text-2xl font-bold text-slate-900"
                                    >
                                        —
                                    </div>

                                </div>

                            </div>


                            <div class="mt-5 border-t border-slate-200 pt-5">

                                <div class="grid gap-5 sm:grid-cols-2">

                                    <div>

                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Current Hospital Stock
                                        </div>

                                        <div
                                            id="currentHospitalBalancePreview"
                                            class="mt-2 text-xl font-bold text-slate-900"
                                        >
                                            {{ number_format($stockBatch->quantity_available) }}
                                        </div>

                                    </div>


                                    <div>

                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Hospital Stock After Adjustment
                                        </div>

                                        <div
                                            id="newHospitalBalancePreview"
                                            class="mt-2 text-xl font-bold text-slate-900"
                                        >
                                            {{ number_format($stockBatch->quantity_available) }}
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>



                        {{-- INVALID WARNING --}}

                        <div
                            id="invalidWarning"
                            class="hidden rounded-xl border border-red-200 bg-red-50 p-4"
                        >

                            <div class="text-sm font-semibold text-red-800">
                                Invalid adjustment
                            </div>

                            <p
                                id="invalidWarningText"
                                class="mt-1 text-xs leading-5 text-red-700"
                            >
                                The requested stock reduction is greater than the current location balance.
                            </p>

                        </div>



                        {{-- AUDIT NOTICE --}}

                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">

                            <div class="text-sm font-semibold text-amber-800">
                                Audit-controlled transaction
                            </div>

                            <p class="mt-1 text-xs leading-5 text-amber-700">
                                Stock balances are never silently overwritten. Completing this
                                adjustment changes both the selected location balance and the
                                hospital-wide stock balance, and creates a permanent ledger entry
                                containing the movement type, quantity, reason, user and timestamp.
                            </p>

                        </div>


                    </div>



                    {{-- ===================================================== --}}
                    {{-- ACTIONS --}}
                    {{-- ===================================================== --}}

                    <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-5 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('pharmacy.stock-batches.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            id="submitButton"
                            disabled
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Complete Stock Adjustment
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ============================================================= --}}

    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const hospitalBalance =
                    {{ (int) $stockBatch->quantity_available }};


                const locationInput =
                    document.getElementById(
                        'pharmacy_stock_location_id'
                    );


                const typeInput =
                    document.getElementById(
                        'adjustment_type'
                    );


                const quantityInput =
                    document.getElementById(
                        'quantity'
                    );


                const locationHelp =
                    document.getElementById(
                        'locationHelp'
                    );


                const quantityHelp =
                    document.getElementById(
                        'quantityHelp'
                    );


                const locationPreview =
                    document.getElementById(
                        'locationPreview'
                    );


                const currentLocationBalancePreview =
                    document.getElementById(
                        'currentLocationBalancePreview'
                    );


                const changePreview =
                    document.getElementById(
                        'changePreview'
                    );


                const newLocationBalancePreview =
                    document.getElementById(
                        'newLocationBalancePreview'
                    );


                const newHospitalBalancePreview =
                    document.getElementById(
                        'newHospitalBalancePreview'
                    );


                const previewMessage =
                    document.getElementById(
                        'previewMessage'
                    );


                const directionBadge =
                    document.getElementById(
                        'directionBadge'
                    );


                const invalidWarning =
                    document.getElementById(
                        'invalidWarning'
                    );


                const invalidWarningText =
                    document.getElementById(
                        'invalidWarningText'
                    );


                const submitButton =
                    document.getElementById(
                        'submitButton'
                    );


                const form =
                    document.getElementById(
                        'adjustmentForm'
                    );


                const increaseTypes = [
                    'stock_in',
                    'correction_plus',
                    'other_plus',
                ];


                const decreaseTypes = [
                    'correction_minus',
                    'damaged',
                    'expired_writeoff',
                    'lost',
                    'other_minus',
                ];



                function formatted(value) {

                    return Number(
                        value || 0
                    ).toLocaleString(
                        'en-IN'
                    );
                }



                function getSelectedLocation() {

                    const option =
                        locationInput.options[
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

                        balance:
                            Number(
                                option.dataset.balance
                                || 0
                            )
                    };
                }



                function updatePreview() {

                    const location =
                        getSelectedLocation();


                    const type =
                        typeInput.value;


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
                    | No Location
                    |--------------------------------------------------------------------------
                    */

                    if (! location) {

                        quantityInput.disabled =
                            true;


                        quantityInput.placeholder =
                            'Select a stock location first';


                        locationHelp.textContent =
                            'Select the physical location where this adjustment applies.';


                        quantityHelp.textContent =
                            'Select a location before entering the quantity.';


                        locationPreview.textContent =
                            '—';


                        currentLocationBalancePreview.textContent =
                            '—';


                        changePreview.textContent =
                            '—';


                        newLocationBalancePreview.textContent =
                            '—';


                        newHospitalBalancePreview.textContent =
                            formatted(
                                hospitalBalance
                            );


                        directionBadge.className =
                            'hidden';


                        previewMessage.textContent =
                            'Select a location, adjustment type and quantity.';


                        invalidWarning.classList.add(
                            'hidden'
                        );


                        submitButton.disabled =
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


                    quantityInput.placeholder =
                        'Enter quantity';


                    locationHelp.textContent =
                        location.name
                        + ' currently has '
                        + formatted(
                            location.balance
                        )
                        + ' units.';


                    locationPreview.textContent =
                        location.name;


                    currentLocationBalancePreview.textContent =
                        formatted(
                            location.balance
                        );


                    let newLocationBalance =
                        location.balance;


                    let newHospitalBalance =
                        hospitalBalance;


                    let invalid =
                        false;


                    /*
                    |--------------------------------------------------------------------------
                    | Increase
                    |--------------------------------------------------------------------------
                    */

                    if (
                        increaseTypes.includes(
                            type
                        )
                    ) {

                        newLocationBalance =
                            location.balance
                            + quantity;


                        newHospitalBalance =
                            hospitalBalance
                            + quantity;


                        changePreview.textContent =
                            quantity > 0
                                ? '+'
                                    + formatted(
                                        quantity
                                    )
                                : '—';


                        changePreview.className =
                            'mt-2 text-2xl font-bold text-emerald-700';


                        directionBadge.textContent =
                            'Stock Increase';


                        directionBadge.className =
                            'rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700';


                        previewMessage.textContent =
                            quantity > 0
                                ? location.name
                                    + ' and hospital stock will each increase by '
                                    + formatted(
                                        quantity
                                    )
                                    + ' units.'
                                : 'Enter the quantity to add.';


                        quantityHelp.textContent =
                            'Increase adjustments can add stock to this location.';


                    /*
                    |--------------------------------------------------------------------------
                    | Decrease
                    |--------------------------------------------------------------------------
                    */

                    } else if (
                        decreaseTypes.includes(
                            type
                        )
                    ) {

                        newLocationBalance =
                            location.balance
                            - quantity;


                        newHospitalBalance =
                            hospitalBalance
                            - quantity;


                        changePreview.textContent =
                            quantity > 0
                                ? '-'
                                    + formatted(
                                        quantity
                                    )
                                : '—';


                        changePreview.className =
                            'mt-2 text-2xl font-bold text-red-700';


                        directionBadge.textContent =
                            'Stock Decrease';


                        directionBadge.className =
                            'rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700';


                        previewMessage.textContent =
                            quantity > 0
                                ? location.name
                                    + ' and hospital stock will each decrease by '
                                    + formatted(
                                        quantity
                                    )
                                    + ' units.'
                                : 'Enter the quantity to deduct.';


                        quantityHelp.textContent =
                            'Maximum decrease at '
                            + location.name
                            + ': '
                            + formatted(
                                location.balance
                            )
                            + ' units.';


                        if (
                            quantity
                            > location.balance
                        ) {

                            invalid =
                                true;


                            invalidWarningText.textContent =
                                'The requested reduction exceeds the stock available at '
                                + location.name
                                + '.';
                        }


                        if (
                            quantity
                            > hospitalBalance
                        ) {

                            invalid =
                                true;


                            invalidWarningText.textContent =
                                'The requested reduction exceeds the hospital-wide stock balance.';
                        }


                    /*
                    |--------------------------------------------------------------------------
                    | No Type Selected
                    |--------------------------------------------------------------------------
                    */

                    } else {

                        changePreview.textContent =
                            '—';


                        changePreview.className =
                            'mt-2 text-2xl font-bold text-slate-400';


                        directionBadge.className =
                            'hidden';


                        previewMessage.textContent =
                            'Select an adjustment type and quantity.';


                        quantityHelp.textContent =
                            'Select whether stock should increase or decrease.';
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Preview Balances
                    |--------------------------------------------------------------------------
                    */

                    newLocationBalancePreview.textContent =
                        formatted(
                            Math.max(
                                0,
                                newLocationBalance
                            )
                        );


                    newHospitalBalancePreview.textContent =
                        formatted(
                            Math.max(
                                0,
                                newHospitalBalance
                            )
                        );


                    if (invalid) {

                        newLocationBalancePreview.className =
                            'mt-2 text-2xl font-bold text-red-700';


                        newHospitalBalancePreview.className =
                            'mt-2 text-xl font-bold text-red-700';


                        invalidWarning.classList.remove(
                            'hidden'
                        );

                    } else {

                        newLocationBalancePreview.className =
                            'mt-2 text-2xl font-bold text-slate-900';


                        newHospitalBalancePreview.className =
                            'mt-2 text-xl font-bold text-slate-900';


                        invalidWarning.classList.add(
                            'hidden'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Enable Submit
                    |--------------------------------------------------------------------------
                    */

                    submitButton.disabled =
                        ! location
                        ||
                        ! type
                        ||
                        quantity <= 0
                        ||
                        invalid;
                }



                locationInput.addEventListener(
                    'change',
                    function () {

                        quantityInput.value =
                            '';

                        updatePreview();
                    }
                );


                typeInput.addEventListener(
                    'change',
                    updatePreview
                );


                quantityInput.addEventListener(
                    'input',
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


                        if (
                            ! confirm(
                                'Complete this stock adjustment at '
                                + location.name
                                + '? This will create a permanent stock ledger entry.'
                            )
                        ) {

                            event.preventDefault();
                        }
                    }
                );


                updatePreview();
            }
        );

    </script>

</x-app-layout>