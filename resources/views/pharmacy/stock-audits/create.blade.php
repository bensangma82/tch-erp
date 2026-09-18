<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Start Stock Audit
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Create a physical stock verification snapshot for a specific stock location
                </p>

            </div>

            <a
                href="{{ route('pharmacy.stock-audits.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Stock Audit Register
            </a>

        </div>

    </x-slot>


    @php

        /*
        |--------------------------------------------------------------------------
        | Open audits indexed by stock location
        |--------------------------------------------------------------------------
        |
        | Multiple locations may have open audits simultaneously.
        | Only the location which already has an open audit should be blocked.
        |
        */

        $openAuditsCollection = collect($openAudits ?? []);

        $openAuditByLocation = $openAuditsCollection
            ->filter(fn ($audit) => ! empty($audit->pharmacy_stock_location_id))
            ->keyBy('pharmacy_stock_location_id');


        /*
        |--------------------------------------------------------------------------
        | Selected location
        |--------------------------------------------------------------------------
        */

        $selectedLocationId = old('pharmacy_stock_location_id');


        /*
        |--------------------------------------------------------------------------
        | Location batch counts
        |--------------------------------------------------------------------------
        */

        $locationBatchCounts = $locationBatchCounts ?? [];

    @endphp


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- VALIDATION ERRORS --}}
            {{-- ========================================================= --}}

            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

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
            {{-- OPEN LOCATION AUDITS --}}
            {{-- ========================================================= --}}

            @if ($openAuditsCollection->isNotEmpty())

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">

                    <div class="flex items-start gap-4">

                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                />
                            </svg>
                        </div>


                        <div class="min-w-0 flex-1">

                            <div class="font-semibold text-amber-900">
                                Open stock audits
                            </div>

                            <p class="mt-1 text-sm leading-6 text-amber-700">
                                A location cannot start another audit until its current audit
                                is completed or cancelled. Other locations may still be audited.
                            </p>


                            <div class="mt-4 space-y-3">

                                @foreach ($openAuditsCollection as $existingAudit)

                                    <div
                                        class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-white/70 p-4 sm:flex-row sm:items-center sm:justify-between"
                                    >

                                        <div>

                                            <div class="font-semibold text-slate-900">
                                                {{ $existingAudit->audit_no }}
                                            </div>

                                            <div class="mt-1 text-sm text-slate-600">

                                                Location:

                                                <span class="font-semibold">
                                                    {{ $existingAudit->location?->name ?? 'Legacy / Unassigned' }}
                                                </span>

                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">

                                                Status:

                                                {{ ucwords(str_replace('_', ' ', $existingAudit->status)) }}

                                            </div>

                                        </div>


                                        <a
                                            href="{{ route('pharmacy.stock-audits.show', $existingAudit) }}"
                                            class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                                        >
                                            Continue Audit
                                        </a>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- SUMMARY CARDS --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 md:grid-cols-3">


                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Active Hospital Batches
                    </div>

                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($activeBatchCount ?? 0) }}
                    </div>

                    <div class="mt-2 text-sm text-slate-500">
                        Active medicine batches available to the stock audit system.
                    </div>

                </div>



                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                        Selected Location Stock
                    </div>

                    <div
                        id="selected-location-batch-count"
                        class="mt-2 text-3xl font-bold text-emerald-900"
                    >
                        —
                    </div>

                    <div
                        id="selected-location-description"
                        class="mt-2 text-sm text-emerald-700"
                    >
                        Select a stock location below.
                    </div>

                </div>



                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-6 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-500">
                        Audit Method
                    </div>

                    <div class="mt-2 text-xl font-bold text-blue-900">
                        Location Physical Count
                    </div>

                    <div class="mt-2 text-sm leading-6 text-blue-700">
                        The selected location is counted independently.
                        Adjustments are posted only after review and approval.
                    </div>

                </div>


            </div>



            {{-- ========================================================= --}}
            {{-- AUDIT FORM --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Audit Details
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Select the physical stock location that will be counted.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('pharmacy.stock-audits.store') }}"
                    class="p-6"
                    id="stock-audit-form"
                >

                    @csrf


                    <div class="grid gap-6 md:grid-cols-2">


                        {{-- STOCK LOCATION --}}

                        <div class="md:col-span-2">

                            <label
                                for="pharmacy_stock_location_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Stock Location *
                            </label>


                            <select
                                id="pharmacy_stock_location_id"
                                name="pharmacy_stock_location_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select stock location
                                </option>


                                @foreach ($locations as $location)

                                    @php

                                        $existingLocationAudit =
                                            $openAuditByLocation->get($location->id);

                                        $batchCount =
                                            (int) (
                                                $locationBatchCounts[$location->id]
                                                ?? 0
                                            );

                                    @endphp


                                    <option
                                        value="{{ $location->id }}"
                                        data-location-name="{{ $location->name }}"
                                        data-location-code="{{ $location->code }}"
                                        data-batch-count="{{ $batchCount }}"
                                        data-has-open-audit="{{ $existingLocationAudit ? '1' : '0' }}"
                                        {{ (string) $selectedLocationId === (string) $location->id ? 'selected' : '' }}
                                        {{ $existingLocationAudit ? 'disabled' : '' }}
                                    >

                                        {{ $location->name }}
                                        ({{ $location->code }})

                                        @if ($existingLocationAudit)

                                            — Audit {{ $existingLocationAudit->audit_no }} already open

                                        @else

                                            — {{ number_format($batchCount) }} stocked batch{{ $batchCount === 1 ? '' : 'es' }}

                                        @endif

                                    </option>

                                @endforeach

                            </select>


                            <p class="mt-2 text-xs leading-5 text-slate-500">

                                Only stock physically belonging to this location will be
                                compared with the count.

                                Locations with an existing open audit cannot be selected.

                            </p>

                        </div>



                        {{-- AUDIT DATE --}}

                        <div>

                            <label
                                for="audit_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Audit Date *
                            </label>

                            <input
                                id="audit_date"
                                name="audit_date"
                                type="date"
                                required
                                value="{{ old('audit_date', today()->format('Y-m-d')) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        {{-- AUDIT TYPE --}}

                        <div>

                            <label
                                for="audit_type"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Audit Type *
                            </label>

                            <select
                                id="audit_type"
                                name="audit_type"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option
                                    value="full"
                                    {{ old('audit_type', 'full') === 'full' ? 'selected' : '' }}
                                >
                                    Full Stock Audit
                                </option>

                            </select>

                        </div>



                        {{-- REMARKS --}}

                        <div class="md:col-span-2">

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
                                maxlength="3000"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Optional audit notes, team details, counting instructions..."
                            >{{ old('remarks') }}</textarea>

                        </div>


                    </div>



                    {{-- ================================================= --}}
                    {{-- LOCATION INFORMATION --}}
                    {{-- ================================================= --}}

                    <div
                        id="selected-location-panel"
                        class="mt-6 hidden rounded-xl border border-emerald-200 bg-emerald-50 p-5"
                    >

                        <div class="font-semibold text-emerald-900">
                            Location selected for physical count
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-3">

                            <div>

                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Location
                                </div>

                                <div
                                    id="selected-location-name"
                                    class="mt-1 font-semibold text-emerald-900"
                                >
                                    —
                                </div>

                            </div>


                            <div>

                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Code
                                </div>

                                <div
                                    id="selected-location-code"
                                    class="mt-1 font-semibold text-emerald-900"
                                >
                                    —
                                </div>

                            </div>


                            <div>

                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Stocked Batches
                                </div>

                                <div
                                    id="selected-location-panel-count"
                                    class="mt-1 font-semibold text-emerald-900"
                                >
                                    —
                                </div>

                            </div>

                        </div>

                    </div>



                    {{-- ================================================= --}}
                    {{-- OPERATIONAL WARNING --}}
                    {{-- ================================================= --}}

                    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">

                        <div class="font-semibold text-amber-800">
                            Important operational note
                        </div>

                        <div class="mt-2 text-sm leading-6 text-amber-700">

                            For the most reliable audit, stock movement affecting the
                            selected location should be paused while the physical count
                            is being performed.

                            This includes dispensing, GRNs, purchase returns,
                            stock transfers, disposals and manual stock adjustments
                            involving that location.

                            If its stock changes after the audit snapshot, the system
                            will block final posting to protect inventory integrity.

                        </div>

                    </div>



                    {{-- ================================================= --}}
                    {{-- ACTIONS --}}
                    {{-- ================================================= --}}

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('pharmacy.stock-audits.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            id="start-audit-button"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Start Stock Audit
                        </button>

                    </div>

                </form>

            </div>



            {{-- ========================================================= --}}
            {{-- WORKFLOW --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h3 class="font-semibold text-slate-900">
                    What happens next?
                </h3>


                <div class="mt-4 grid gap-4 md:grid-cols-4">


                    <div class="rounded-xl bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Step 1
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            Snapshot
                        </div>

                        <div class="mt-1 text-xs leading-5 text-slate-500">
                            The selected location's current system quantities are
                            recorded in the audit.
                        </div>

                    </div>



                    <div class="rounded-xl bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Step 2
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            Physical Count
                        </div>

                        <div class="mt-1 text-xs leading-5 text-slate-500">
                            Staff enter the physical quantity found at that location
                            for every audited batch.
                        </div>

                    </div>



                    <div class="rounded-xl bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Step 3
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            Review & Approve
                        </div>

                        <div class="mt-1 text-xs leading-5 text-slate-500">
                            Variances are reviewed, explanations documented and
                            independently approved.
                        </div>

                    </div>



                    <div class="rounded-xl bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Step 4
                        </div>

                        <div class="mt-1 font-semibold text-slate-800">
                            Post
                        </div>

                        <div class="mt-1 text-xs leading-5 text-slate-500">
                            Approved variances update both the selected location
                            balance and hospital-wide stock.
                        </div>

                    </div>


                </div>

            </div>


        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- LOCATION UI SCRIPT --}}
    {{-- ============================================================= --}}

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const locationSelect =
                document.getElementById(
                    'pharmacy_stock_location_id'
                );

            const locationPanel =
                document.getElementById(
                    'selected-location-panel'
                );

            const locationName =
                document.getElementById(
                    'selected-location-name'
                );

            const locationCode =
                document.getElementById(
                    'selected-location-code'
                );

            const locationPanelCount =
                document.getElementById(
                    'selected-location-panel-count'
                );

            const summaryCount =
                document.getElementById(
                    'selected-location-batch-count'
                );

            const summaryDescription =
                document.getElementById(
                    'selected-location-description'
                );

            const form =
                document.getElementById(
                    'stock-audit-form'
                );


            function updateLocationDisplay() {

                const option =
                    locationSelect.options[
                        locationSelect.selectedIndex
                    ];


                if (
                    ! option ||
                    ! option.value
                ) {

                    locationPanel.classList.add(
                        'hidden'
                    );

                    summaryCount.textContent =
                        '—';

                    summaryDescription.textContent =
                        'Select a stock location below.';

                    return;
                }


                const name =
                    option.dataset.locationName || '';

                const code =
                    option.dataset.locationCode || '';

                const batchCount =
                    parseInt(
                        option.dataset.batchCount || '0',
                        10
                    );


                locationName.textContent =
                    name;

                locationCode.textContent =
                    code;

                locationPanelCount.textContent =
                    batchCount.toLocaleString();

                summaryCount.textContent =
                    batchCount.toLocaleString();

                summaryDescription.textContent =
                    name + ' currently has ' +
                    batchCount.toLocaleString() +
                    ' stocked batch' +
                    (batchCount === 1 ? '.' : 'es.');


                locationPanel.classList.remove(
                    'hidden'
                );
            }


            locationSelect.addEventListener(
                'change',
                updateLocationDisplay
            );


            form.addEventListener(
                'submit',
                function (event) {

                    const option =
                        locationSelect.options[
                            locationSelect.selectedIndex
                        ];


                    if (
                        ! option ||
                        ! option.value
                    ) {

                        event.preventDefault();

                        alert(
                            'Please select the stock location to audit.'
                        );

                        locationSelect.focus();

                        return;
                    }


                    const locationName =
                        option.dataset.locationName ||
                        option.textContent.trim();


                    const confirmed =
                        confirm(
                            'Start a stock audit for "' +
                            locationName +
                            '"?\n\n' +
                            'The current stock at this location will be snapshotted for physical verification.'
                        );


                    if (! confirmed) {

                        event.preventDefault();

                    }

                }
            );


            updateLocationDisplay();

        });

    </script>

</x-app-layout>