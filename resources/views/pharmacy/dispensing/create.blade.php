<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    New Pharmacy Dispensing
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    @if ($admission)
                        Dispense medicines for the inpatient and post them directly to the IP running bill
                    @else
                        Enter medicines from the paper prescription and complete pharmacy billing
                    @endif
                </p>
            </div>


            <a
                href="{{ route('pharmacy.dispensing.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back to Dispensing
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- VALIDATION ERRORS --}}
            {{-- ========================================================= --}}

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



            <form
                method="POST"
                action="{{ route('pharmacy.dispensing.store') }}"
                id="dispensingForm"
                class="space-y-6"
            >

                @csrf



                {{-- ========================================================= --}}
                {{-- PATIENT / ENCOUNTER --}}
                {{-- ========================================================= --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            {{ $admission ? 'Inpatient / Admission' : 'Patient / Encounter' }}
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            @if ($admission)
                                Medicines dispensed here will be added to the patient's inpatient running bill.
                            @else
                                Select today's OPD encounter where available.
                            @endif
                        </p>

                    </div>


                    <div class="p-6">

                        <input
                            type="hidden"
                            name="patient_id"
                            id="patient_id"
                            value="{{ old('patient_id', $patient?->id) }}"
                        >

                        <input
                            type="hidden"
                            name="encounter_id"
                            id="encounter_id"
                            value="{{ old('encounter_id', $encounter?->id) }}"
                        >

                        <input
                            type="hidden"
                            name="admission_id"
                            id="admission_id"
                            value="{{ old('admission_id', $admission?->id) }}"
                        >


                        @if ($admission)

                            @php
                                $currentBed =
                                    $admission->currentBedAllocation?->bed
                                    ?? $admission->bed;

                                $currentWard =
                                    $currentBed?->ward;
                            @endphp

                            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5">

                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                                    <div>

                                        <div class="flex flex-wrap items-center gap-2">

                                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-700">
                                                IP Billing
                                            </span>

                                            <span class="font-mono text-sm font-bold text-indigo-800">
                                                {{ $admission->admission_no }}
                                            </span>

                                        </div>

                                        <div class="mt-4 text-lg font-bold text-slate-900">
                                            {{ $patient?->full_name }}
                                        </div>

                                        <div class="mt-1 text-sm text-slate-600">
                                            UHID: {{ $patient?->uhid }}

                                            @if ($patient?->mrd_number)
                                                · MRD: {{ $patient->mrd_number }}
                                            @endif
                                        </div>

                                    </div>


                                    <div class="grid gap-3 text-sm sm:grid-cols-3 lg:text-right">

                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                Department
                                            </div>
                                            <div class="mt-1 font-semibold text-slate-800">
                                                {{ $admission->department?->name ?? '—' }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                Consultant
                                            </div>
                                            <div class="mt-1 font-semibold text-slate-800">
                                                {{ $admission->consultant?->name ?? '—' }}
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                Ward / Bed
                                            </div>
                                            <div class="mt-1 font-semibold text-slate-800">
                                                {{ $currentWard?->name ?? '—' }}
                                                @if ($currentBed?->bed_number)
                                                    / {{ $currentBed->bed_number }}
                                                @elseif ($currentBed?->name)
                                                    / {{ $currentBed->name }}
                                                @endif
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        @else

                            <label
                                for="encounter_selector"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Patient / Encounter
                            </label>

                                       <div class="mb-4">

    <label
        for="encounter_search"
        class="mb-2 block text-sm font-semibold text-slate-700"
    >
        Search Patient
    </label>

    <input
        type="text"
        id="encounter_search"
        placeholder="Search by patient name, UHID, MRD, encounter, department or doctor..."
        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
    >

    <p class="mt-1 text-xs text-slate-500">
        Includes encounters from the last 6 months for repeat and chronic medication patients.
    </p>

</div>
                            <select
                                id="encounter_selector"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select patient / encounter
                                </option>


                                @foreach ($recentEncounters as $item)

                                    <option
                                        value="{{ $item->id }}"
                                        data-patient-id="{{ $item->patient_id }}"
                                        data-patient-name="{{ $item->patient->full_name }}"
                                        data-uhid="{{ $item->patient->uhid }}"
                                        data-mrd="{{ $item->patient->mrd_number ?? '' }}"
                                        data-encounter-no="{{ $item->encounter_no }}"
                                        data-department="{{ $item->department?->name ?? '' }}"
                                        data-doctor="{{ $item->doctor?->name ?? '' }}"
                                        @selected(
                                            old(
                                                'encounter_id',
                                                $encounter?->id
                                            ) == $item->id
                                        )
                                    >
                                        {{ $item->patient->full_name }}
                                        — {{ $item->patient->uhid }}
                                        — {{ $item->encounter_no }}
                                        — {{ $item->department?->name ?? 'Department' }}
                                    </option>

                                @endforeach

                            </select>


                            <div
                                id="selectedPatientBox"
                                class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
                            >

                                @if ($patient)

                                    <div class="font-semibold text-slate-900">
                                        {{ $patient->full_name }}
                                    </div>

                                    <div class="mt-1 text-sm text-slate-500">

                                        UHID: {{ $patient->uhid }}

                                        @if ($patient->mrd_number)
                                            · MRD: {{ $patient->mrd_number }}
                                        @endif

                                    </div>

                                @else

                                    <div class="text-sm text-slate-500">
                                        No patient selected.
                                    </div>

                                @endif

                            </div>

                        @endif

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- MEDICINES --}}
                {{-- ========================================================= --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>

                                <h3 class="font-semibold text-slate-900">
                                    Medicines
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    Add medicines and quantities from the patient's paper prescription
                                </p>

                            </div>


                            <button
                                type="button"
                                id="addMedicineButton"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                            >
                                Add Medicine
                            </button>

                        </div>

                    </div>



                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Medicine
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Available
                                    </th>

                                    @unless ($admission)
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Rate
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            GST
                                        </th>
                                    @endunless

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Qty
                                    </th>

                                    @unless ($admission)
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                            Amount
                                        </th>
                                    @endunless

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody
                                id="medicineRows"
                                class="divide-y divide-slate-100 bg-white"
                            >
                            </tbody>

                        </table>

                    </div>



                    <div class="border-t border-slate-100 bg-slate-50 px-6 py-4">

                        <p class="text-xs leading-5 text-slate-500">
                            @if ($admission)
                                Medicines are issued using FEFO — First Expiry, First Out.
                                Select the medicine and enter only the quantity required.
                            @else
                                Medicines are issued using FEFO — First Expiry, First Out.
                                Rates shown here are GST-inclusive estimates. The server recalculates
                                the final amount and tax breakup from the actual batch or batches used.
                            @endif
                        </p>

                    </div>

                </div>



                {{-- ========================================================= --}}
                {{-- PAYMENT + SALE SUMMARY --}}
                {{-- ========================================================= --}}

                <div class="grid gap-6 lg:grid-cols-2">


                    {{-- PAYMENT / IP BILLING --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ $admission ? 'IP Billing' : 'Payment' }}
                        </h3>


                        <div class="mt-5 space-y-5">


                            <div>

                                <label
                                    for="discount"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Discount
                                </label>


                                <div class="relative">

                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        ₹
                                    </span>

                                    <input
                                        id="discount"
                                        name="discount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value="{{ old('discount', 0) }}"
                                        class="w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >

                                </div>

                            </div>


                            @if ($admission)

                                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">

                                    <div class="flex items-start gap-3">

                                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 font-bold text-indigo-700">
                                            IP
                                        </div>

                                        <div>

                                            <div class="font-semibold text-indigo-900">
                                                Charge to IP Running Bill
                                            </div>

                                            <p class="mt-1 text-sm leading-5 text-indigo-700">
                                                No payment is collected at the pharmacy counter.
                                                The final dispensed value will be posted automatically
                                                to this admission's inpatient billing account.
                                            </p>

                                        </div>

                                    </div>

                                </div>

                            @else

                                <div>

                                    <label
                                        for="payment_mode"
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Payment Mode
                                    </label>


                                    <select
                                        id="payment_mode"
                                        name="payment_mode"
                                        required
                                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >

                                        <option
                                            value="cash"
                                            @selected(old('payment_mode', 'cash') === 'cash')
                                        >
                                            Cash
                                        </option>

                                        <option
                                            value="upi"
                                            @selected(old('payment_mode') === 'upi')
                                        >
                                            UPI
                                        </option>

                                        <option
                                            value="card"
                                            @selected(old('payment_mode') === 'card')
                                        >
                                            Card
                                        </option>

                                        <option
                                            value="credit"
                                            @selected(old('payment_mode') === 'credit')
                                        >
                                            Credit
                                        </option>

                                        <option
                                            value="mhis"
                                            @selected(old('payment_mode') === 'mhis')
                                        >
                                            MHIS
                                        </option>

                                    </select>

                                </div>


                                <div id="cashReceivedWrapper">

                                    <label
                                        for="cash_received"
                                        class="mb-2 block text-sm font-semibold text-slate-700"
                                    >
                                        Cash Received
                                    </label>


                                    <div class="relative">

                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            ₹
                                        </span>

                                        <input
                                            id="cash_received"
                                            name="cash_received"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value="{{ old('cash_received') }}"
                                            placeholder="Enter amount received"
                                            class="w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >

                                    </div>

                                </div>


                                <div id="transactionReferenceWrapper">

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
                                        placeholder="UPI / card reference if applicable"
                                        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >

                                </div>

                            @endif


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
                                    rows="3"
                                    placeholder="Optional pharmacy note"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >{{ old('remarks') }}</textarea>

                            </div>

                        </div>

                    </div>


                    {{-- SALE SUMMARY --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                        <div class="flex items-center justify-between gap-4">

                            <h3 class="text-lg font-semibold text-slate-900">
                                Sale Summary
                            </h3>

                            <span
                                id="paymentStatusBadge"
                                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                            >
                                {{ $admission ? 'Charge to IP bill' : 'Awaiting medicines' }}
                            </span>

                        </div>



                        @if ($admission)
                            <div class="mt-6 space-y-4">

                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">

                                <span class="text-sm text-slate-500">
                                    Medicines Selected
                                </span>

                                <span
                                    id="selectedCount"
                                    class="font-semibold text-slate-900"
                                >
                                    0
                                </span>

                            </div>


                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">

                                <span class="text-sm text-slate-500">
                                    Total Quantity
                                </span>

                                <span
                                    id="totalQuantity"
                                    class="font-semibold text-slate-900"
                                >
                                    0
                                </span>

                            </div>


                            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">

                                <div class="text-sm font-semibold text-indigo-800">
                                    Billing handled automatically
                                </div>

                                <p class="mt-1 text-xs leading-5 text-indigo-700">
                                    Medicine rates, GST and charge amounts are calculated by the system
                                    from the actual FEFO batch used and posted to the IP running bill.
                                </p>

                            </div>

                            {{-- Hidden calculation targets retained for JavaScript --}}
                            <div class="hidden">
                                <span id="subtotalDisplay">₹0.00</span>
                                <span id="discountDisplay">₹0.00</span>
                                <span id="taxableDisplay">₹0.00</span>
                                <span id="cgstDisplay">₹0.00</span>
                                <span id="sgstDisplay">₹0.00</span>
                                <span id="totalDisplay">₹0.00</span>
                                <div id="cashSummary" class="hidden">
                                    <span id="cashReceivedDisplay">₹0.00</span>
                                    <span id="changeLabel">Change</span>
                                    <span id="changeDisplay">₹0.00</span>
                                </div>
                            </div>

                        </div>


                        @else
                        <div class="mt-6 space-y-4">


                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">

                                <span class="text-sm text-slate-500">
                                    Medicines Selected
                                </span>

                                <span
                                    id="selectedCount"
                                    class="font-semibold text-slate-900"
                                >
                                    0
                                </span>

                            </div>



                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">

                                <span class="text-sm text-slate-500">
                                    Total Quantity
                                </span>

                                <span
                                    id="totalQuantity"
                                    class="font-semibold text-slate-900"
                                >
                                    0
                                </span>

                            </div>



                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Gross Amount
                                </span>

                                <span
                                    id="subtotalDisplay"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>



                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Discount
                                </span>

                                <span
                                    id="discountDisplay"
                                    class="font-semibold text-slate-700"
                                >
                                    ₹0.00
                                </span>

                            </div>



                            <div class="my-2 border-t border-slate-200"></div>



                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Taxable Value
                                </span>

                                <span
                                    id="taxableDisplay"
                                    class="font-semibold text-slate-700"
                                >
                                    ₹0.00
                                </span>

                            </div>



                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    CGST
                                </span>

                                <span
                                    id="cgstDisplay"
                                    class="font-semibold text-slate-700"
                                >
                                    ₹0.00
                                </span>

                            </div>



                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    SGST
                                </span>

                                <span
                                    id="sgstDisplay"
                                    class="font-semibold text-slate-700"
                                >
                                    ₹0.00
                                </span>

                            </div>



                            <div class="border-y border-slate-200 py-4">

                                <div class="flex items-center justify-between">

                                    <span class="text-base font-bold text-slate-900">
                                        {{ $admission ? 'IP Bill Amount' : 'Total Payable' }}
                                    </span>

                                    <span
                                        id="totalDisplay"
                                        class="text-2xl font-bold text-slate-900"
                                    >
                                        ₹0.00
                                    </span>

                                </div>

                                <p class="mt-1 text-right text-xs text-slate-400">
                                    GST included
                                </p>

                            </div>



                            <div
                                id="cashSummary"
                                class="space-y-3 {{ $admission ? 'hidden' : '' }}"
                            >

                                <div class="flex items-center justify-between">

                                    <span class="text-sm text-slate-500">
                                        Cash Received
                                    </span>

                                    <span
                                        id="cashReceivedDisplay"
                                        class="font-semibold text-slate-900"
                                    >
                                        ₹0.00
                                    </span>

                                </div>


                                <div class="flex items-center justify-between">

                                    <span
                                        id="changeLabel"
                                        class="text-sm text-slate-500"
                                    >
                                        Change
                                    </span>

                                    <span
                                        id="changeDisplay"
                                        class="font-bold text-emerald-700"
                                    >
                                        ₹0.00
                                    </span>

                                </div>

                            </div>



                            <div class="rounded-xl bg-blue-50 p-4">

                                <div class="text-sm font-semibold text-blue-800">
                                    GST-inclusive FEFO dispensing
                                </div>

                                <p class="mt-1 text-xs leading-5 text-blue-700">
                                    GST is reverse-calculated from the GST-inclusive selling price
                                    after discount. The figures shown here are an estimate for cashier
                                    convenience. The server performs the final calculation before stock
                                    is deducted.
                                </p>

                            </div>

                        </div>



                        @endif


                        <div class="mt-8">

                            <button
                                type="submit"
                                id="completeSaleButton"
                                class="w-full rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                {{ $admission ? 'Dispense & Add to IP Bill' : 'Complete Sale & Dispense' }}
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MEDICINE ROW TEMPLATE --}}
    {{-- ========================================================= --}}

    <template id="medicineRowTemplate">

        <tr class="medicine-row">


            <td class="px-5 py-4">

                <select
                    class="medicine-select w-full min-w-[320px] rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                >

                    <option
                        value=""
                        data-stock="0"
                        data-price="0"
                        data-gst="0"
                        data-hsn=""
                    >
                        Select medicine
                    </option>


                    @foreach ($medicines as $medicine)

                        @php

                            $estimatedBatch = $medicine
                                ->stockBatches()
                                ->where('is_active', true)
                                ->where('quantity_available', '>', 0)
                                ->where(function ($query) {
                                    $query
                                        ->whereNull('expiry_date')
                                        ->orWhereDate(
                                            'expiry_date',
                                            '>=',
                                            today()
                                        );
                                })
                                ->orderByRaw(
                                    'CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END'
                                )
                                ->orderBy('expiry_date')
                                ->orderBy('received_date')
                                ->orderBy('id')
                                ->first();


                            $estimatedPrice =
                                $estimatedBatch?->selling_price
                                ?? $medicine->default_selling_price
                                ?? 0;


                            $gstPercent =
                                (float) (
                                    $medicine->gst_percent
                                    ?? 0
                                );

                        @endphp


                        <option
                            value="{{ $medicine->id }}"
                            data-stock="{{ (int) ($medicine->available_stock ?? 0) }}"
                            data-price="{{ (float) $estimatedPrice }}"
                            data-gst="{{ $gstPercent }}"
                            data-hsn="{{ $medicine->hsn_code ?? '' }}"
                        >

                            {{ $medicine->generic_name }}

                            @if ($medicine->brand_name)
                                — {{ $medicine->brand_name }}
                            @endif

                            @if ($medicine->strength)
                                — {{ $medicine->strength }}
                            @endif

                            — GST {{ number_format($gstPercent, 2) }}%

                            (Stock: {{ (int) ($medicine->available_stock ?? 0) }})

                        </option>

                    @endforeach

                </select>

            </td>



            <td class="px-4 py-4 text-right">

                <span class="available-stock font-semibold text-slate-700">
                    0
                </span>

            </td>



            @unless ($admission)

                <td class="px-4 py-4 text-right">

                    <div class="estimated-rate font-semibold text-slate-700">
                        ₹0.00
                    </div>

                    <div class="mt-1 text-[10px] text-slate-400">
                        incl. GST
                    </div>

                </td>



                <td class="px-4 py-4 text-right">

                    <span class="gst-rate font-semibold text-slate-700">
                        0.00%
                    </span>

                </td>

            @endunless



            <td class="px-4 py-4 text-right">

                <input
                    type="number"
                    min="1"
                    step="1"
                    value="1"
                    class="quantity-input w-24 rounded-lg border-slate-300 text-right shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                >

            </td>



            @unless ($admission)

                <td class="px-4 py-4 text-right">

                    <span class="line-total font-bold text-slate-900">
                        ₹0.00
                    </span>

                </td>

            @endunless



            <td class="px-4 py-4 text-right">

                <button
                    type="button"
                    class="remove-row rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                >
                    Remove
                </button>

            </td>

        </tr>

    </template>



    {{-- ========================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ========================================================= --}}

    <script>
document.addEventListener('DOMContentLoaded', function () {

    const isInpatient =
        @json((bool) $admission);

    const rowsContainer =
        document.getElementById('medicineRows');

    const template =
        document.getElementById('medicineRowTemplate');

    const addButton =
        document.getElementById('addMedicineButton');

    const encounterSelector =
        document.getElementById('encounter_selector');

    const encounterSearch =
        document.getElementById('encounter_search');

    const patientInput =
        document.getElementById('patient_id');

    const encounterInput =
        document.getElementById('encounter_id');

    const selectedPatientBox =
        document.getElementById('selectedPatientBox');

    const paymentMode =
        document.getElementById('payment_mode');

    const cashWrapper =
        document.getElementById('cashReceivedWrapper');

    const cashInput =
        document.getElementById('cash_received');

    const transactionReferenceWrapper =
        document.getElementById(
            'transactionReferenceWrapper'
        );

    const discountInput =
        document.getElementById('discount');

    const selectedCount =
        document.getElementById('selectedCount');

    const totalQuantity =
        document.getElementById('totalQuantity');

    const subtotalDisplay =
        document.getElementById('subtotalDisplay');

    const discountDisplay =
        document.getElementById('discountDisplay');

    const taxableDisplay =
        document.getElementById('taxableDisplay');

    const cgstDisplay =
        document.getElementById('cgstDisplay');

    const sgstDisplay =
        document.getElementById('sgstDisplay');

    const totalDisplay =
        document.getElementById('totalDisplay');

    const cashSummary =
        document.getElementById('cashSummary');

    const cashReceivedDisplay =
        document.getElementById('cashReceivedDisplay');

    const changeLabel =
        document.getElementById('changeLabel');

    const changeDisplay =
        document.getElementById('changeDisplay');

    const paymentStatusBadge =
        document.getElementById('paymentStatusBadge');

    const completeSaleButton =
        document.getElementById('completeSaleButton');

    const dispensingForm =
        document.getElementById('dispensingForm');

    const oldMedicines =
        @json(old('medicines', []));


    function roundMoney(value)
    {
        return Math.round(
            (
                Number(value || 0)
                + Number.EPSILON
            )
            * 100
        ) / 100;
    }


    function money(value)
    {
        return '₹' +
            Number(value || 0)
                .toLocaleString(
                    'en-IN',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );
    }


    function escapeHtml(value)
    {
        const div =
            document.createElement('div');

        div.textContent =
            value ?? '';

        return div.innerHTML;
    }


    /*
    |--------------------------------------------------------------------------
    | Patient / encounter
    |--------------------------------------------------------------------------
    */

    function updatePatient()
    {
        if (isInpatient)
        {
            calculateSummary();
            return;
        }

        if (! encounterSelector)
        {
            return;
        }

        const option =
            encounterSelector.options[
                encounterSelector.selectedIndex
            ];

        if (
            ! option
            || ! option.value
        )
        {
            if (patientInput)
            {
                patientInput.value = '';
            }

            if (encounterInput)
            {
                encounterInput.value = '';
            }

            if (selectedPatientBox)
            {
                selectedPatientBox.innerHTML = `
                    <div class="text-sm text-slate-500">
                        No patient selected.
                    </div>
                `;
            }

            calculateSummary();
            return;
        }

        if (encounterInput)
        {
            encounterInput.value =
                option.value;
        }

        if (patientInput)
        {
            patientInput.value =
                option.dataset.patientId ?? '';
        }

        const patientName =
            escapeHtml(
                option.dataset.patientName ?? ''
            );

        const uhid =
            escapeHtml(
                option.dataset.uhid ?? ''
            );

        const mrd =
            escapeHtml(
                option.dataset.mrd ?? ''
            );

        const encounterNo =
            escapeHtml(
                option.dataset.encounterNo ?? ''
            );

        const department =
            escapeHtml(
                option.dataset.department ?? ''
            );

        const doctor =
            escapeHtml(
                option.dataset.doctor ?? ''
            );

        if (selectedPatientBox)
        {
            selectedPatientBox.innerHTML = `
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                    <div>

                        <div class="font-semibold text-slate-900">
                            ${patientName}
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            UHID: ${uhid}
                            ${mrd ? ' · MRD: ' + mrd : ''}
                        </div>

                    </div>

                    <div class="text-left sm:text-right">

                        <div class="text-sm font-semibold text-slate-700">
                            ${encounterNo}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            ${department}
                            ${doctor ? ' · ' + doctor : ''}
                        </div>

                    </div>

                </div>
            `;
        }

        calculateSummary();
    }


    /*
    |--------------------------------------------------------------------------
    | Search patient / old encounter
    |--------------------------------------------------------------------------
    */

    function filterEncounters()
    {
        if (
            ! encounterSearch
            || ! encounterSelector
        )
        {
            return;
        }

        const search =
            encounterSearch.value
                .trim()
                .toLowerCase();

        const options =
            encounterSelector
                .querySelectorAll('option');

        let firstMatch = null;

        options.forEach(
            function (option)
            {
                if (! option.value)
                {
                    option.hidden = false;
                    return;
                }

                const searchableText = [
                    option.dataset.patientName,
                    option.dataset.uhid,
                    option.dataset.mrd,
                    option.dataset.encounterNo,
                    option.dataset.department,
                    option.dataset.doctor,
                    option.textContent,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();

                const matches =
                    search === ''
                    ||
                    searchableText.includes(search);

                option.hidden =
                    ! matches;

                if (
                    matches
                    && ! firstMatch
                )
                {
                    firstMatch =
                        option;
                }
            }
        );

        /*
         * If current selection is hidden,
         * clear it.
         */
        const selected =
            encounterSelector.options[
                encounterSelector.selectedIndex
            ];

        if (
            selected
            && selected.value
            && selected.hidden
        )
        {
            encounterSelector.value = '';
            updatePatient();
        }

        /*
         * If search produces exactly one visible
         * encounter, select it automatically.
         */
        const visibleOptions =
            Array.from(
                encounterSelector.options
            )
            .filter(
                option =>
                    option.value
                    && ! option.hidden
            );

        if (
            search !== ''
            && visibleOptions.length === 1
        )
        {
            encounterSelector.value =
                visibleOptions[0].value;

            updatePatient();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Medicine rows
    |--------------------------------------------------------------------------
    */

    function renumberRows()
    {
        const rows =
            rowsContainer.querySelectorAll(
                '.medicine-row'
            );

        rows.forEach(
            function (row, index)
            {
                const select =
                    row.querySelector(
                        '.medicine-select'
                    );

                const quantity =
                    row.querySelector(
                        '.quantity-input'
                    );

                select.name =
                    `medicines[${index}][medicine_id]`;

                quantity.name =
                    `medicines[${index}][quantity]`;
            }
        );

        calculateSummary();
    }


    function updateRow(row)
    {
        const select =
            row.querySelector(
                '.medicine-select'
            );

        const available =
            row.querySelector(
                '.available-stock'
            );

        const rate =
            row.querySelector(
                '.estimated-rate'
            );

        const gstRate =
            row.querySelector(
                '.gst-rate'
            );

        const quantity =
            row.querySelector(
                '.quantity-input'
            );

        const lineTotal =
            row.querySelector(
                '.line-total'
            );

        const option =
            select.options[
                select.selectedIndex
            ];

        const stock =
            Number(
                option?.dataset?.stock
                ?? 0
            );

        const price =
            Number(
                option?.dataset?.price
                ?? 0
            );

        const gst =
            Number(
                option?.dataset?.gst
                ?? 0
            );

        const qty =
            Math.max(
                0,
                Number(
                    quantity.value
                    ?? 0
                )
            );

        if (available)
        {
            available.textContent =
                stock.toLocaleString(
                    'en-IN'
                );
        }

        if (rate)
        {
            rate.textContent =
                money(price);
        }

        if (gstRate)
        {
            gstRate.textContent =
                gst.toFixed(2) + '%';
        }

        if (select.value)
        {
            quantity.max =
                stock;
        }
        else
        {
            quantity.removeAttribute(
                'max'
            );
        }

        if (lineTotal)
        {
            lineTotal.textContent =
                money(
                    roundMoney(
                        price * qty
                    )
                );
        }

        if (
            select.value
            &&
            (
                qty <= 0
                || qty > stock
            )
        )
        {
            quantity.classList.add(
                'border-red-400',
                'bg-red-50'
            );
        }
        else
        {
            quantity.classList.remove(
                'border-red-400',
                'bg-red-50'
            );
        }

        calculateSummary();
    }


    function addRow(
        medicineId = '',
        quantityValue = 1
    )
    {
        if (
            ! template
            || ! rowsContainer
        )
        {
            return;
        }

        const clone =
            template.content.cloneNode(
                true
            );

        const row =
            clone.querySelector(
                '.medicine-row'
            );

        const select =
            row.querySelector(
                '.medicine-select'
            );

        const quantity =
            row.querySelector(
                '.quantity-input'
            );

        const remove =
            row.querySelector(
                '.remove-row'
            );

        if (medicineId)
        {
            select.value =
                String(medicineId);
        }

        quantity.value =
            quantityValue || 1;

        select.addEventListener(
            'change',
            function ()
            {
                updateRow(row);
            }
        );

        quantity.addEventListener(
            'input',
            function ()
            {
                updateRow(row);
            }
        );

        remove.addEventListener(
            'click',
            function ()
            {
                row.remove();

                renumberRows();

                if (
                    rowsContainer
                        .querySelectorAll(
                            '.medicine-row'
                        )
                        .length === 0
                )
                {
                    addRow();
                }
            }
        );

        rowsContainer.appendChild(
            row
        );

        updateRow(row);

        renumberRows();
    }


    /*
    |--------------------------------------------------------------------------
    | Sale summary
    |--------------------------------------------------------------------------
    */

    function calculateSummary()
    {
        if (! rowsContainer)
        {
            return;
        }

        const rows =
            Array.from(
                rowsContainer
                    .querySelectorAll(
                        '.medicine-row'
                    )
            );

        const selectedLines = [];

        let medicineCount = 0;
        let quantityCount = 0;
        let subtotal = 0;
        let invalidStock = false;

        rows.forEach(
            function (row)
            {
                const select =
                    row.querySelector(
                        '.medicine-select'
                    );

                const quantity =
                    row.querySelector(
                        '.quantity-input'
                    );

                if (! select.value)
                {
                    return;
                }

                const option =
                    select.options[
                        select.selectedIndex
                    ];

                const stock =
                    Number(
                        option?.dataset?.stock
                        ?? 0
                    );

                const price =
                    Number(
                        option?.dataset?.price
                        ?? 0
                    );

                const gst =
                    Number(
                        option?.dataset?.gst
                        ?? 0
                    );

                const qty =
                    Math.max(
                        0,
                        Number(
                            quantity.value
                            ?? 0
                        )
                    );

                const gross =
                    roundMoney(
                        price * qty
                    );

                medicineCount++;

                quantityCount +=
                    qty;

                subtotal =
                    roundMoney(
                        subtotal + gross
                    );

                selectedLines.push({
                    gross: gross,
                    gst: gst
                });

                if (
                    qty <= 0
                    || qty > stock
                )
                {
                    invalidStock = true;
                }
            }
        );


        const enteredDiscount =
            Math.max(
                0,
                Number(
                    discountInput?.value
                    ?? 0
                )
            );

        const appliedDiscount =
            roundMoney(
                Math.min(
                    enteredDiscount,
                    subtotal
                )
            );

        const finalTotal =
            roundMoney(
                Math.max(
                    0,
                    subtotal
                    - appliedDiscount
                )
            );


        /*
        |--------------------------------------------------------------------------
        | GST
        |--------------------------------------------------------------------------
        */

        let totalTaxable = 0;
        let totalCgst = 0;
        let totalSgst = 0;
        let discountAllocated = 0;

        selectedLines.forEach(
            function (line, index)
            {
                let lineDiscount = 0;

                if (
                    appliedDiscount > 0
                    && subtotal > 0
                )
                {
                    if (
                        index ===
                        selectedLines.length - 1
                    )
                    {
                        lineDiscount =
                            roundMoney(
                                appliedDiscount
                                - discountAllocated
                            );
                    }
                    else
                    {
                        lineDiscount =
                            roundMoney(
                                appliedDiscount
                                * (
                                    line.gross
                                    / subtotal
                                )
                            );

                        discountAllocated =
                            roundMoney(
                                discountAllocated
                                + lineDiscount
                            );
                    }
                }

                const discountedGross =
                    roundMoney(
                        Math.max(
                            0,
                            line.gross
                            - lineDiscount
                        )
                    );

                let taxable =
                    discountedGross;

                let cgst = 0;
                let sgst = 0;

                if (line.gst > 0)
                {
                    taxable =
                        roundMoney(
                            discountedGross
                            / (
                                1
                                + (
                                    line.gst
                                    / 100
                                )
                            )
                        );

                    const gstAmount =
                        roundMoney(
                            discountedGross
                            - taxable
                        );

                    cgst =
                        roundMoney(
                            gstAmount / 2
                        );

                    sgst =
                        roundMoney(
                            gstAmount
                            - cgst
                        );
                }

                totalTaxable =
                    roundMoney(
                        totalTaxable
                        + taxable
                    );

                totalCgst =
                    roundMoney(
                        totalCgst
                        + cgst
                    );

                totalSgst =
                    roundMoney(
                        totalSgst
                        + sgst
                    );
            }
        );


        const cashReceived =
            Math.max(
                0,
                Number(
                    cashInput?.value
                    ?? 0
                )
            );


        if (selectedCount)
        {
            selectedCount.textContent =
                medicineCount;
        }

        if (totalQuantity)
        {
            totalQuantity.textContent =
                quantityCount
                    .toLocaleString(
                        'en-IN'
                    );
        }

        if (subtotalDisplay)
        {
            subtotalDisplay.textContent =
                money(subtotal);
        }

        if (discountDisplay)
        {
            discountDisplay.textContent =
                money(
                    appliedDiscount
                );
        }

        if (taxableDisplay)
        {
            taxableDisplay.textContent =
                money(
                    totalTaxable
                );
        }

        if (cgstDisplay)
        {
            cgstDisplay.textContent =
                money(totalCgst);
        }

        if (sgstDisplay)
        {
            sgstDisplay.textContent =
                money(totalSgst);
        }

        if (totalDisplay)
        {
            totalDisplay.textContent =
                money(finalTotal);
        }

        if (cashReceivedDisplay)
        {
            cashReceivedDisplay.textContent =
                money(cashReceived);
        }


        const currentPaymentMode =
            isInpatient
                ? 'ip_billing'
                : (
                    paymentMode?.value
                    ?? 'cash'
                );


        /*
        |--------------------------------------------------------------------------
        | Status badge
        |--------------------------------------------------------------------------
        */

        if (isInpatient)
        {
            if (cashSummary)
            {
                cashSummary.classList.add(
                    'hidden'
                );
            }

            if (
                medicineCount > 0
                && ! invalidStock
            )
            {
                paymentStatusBadge.textContent =
                    'Charge to IP bill';

                paymentStatusBadge.className =
                    'rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700';
            }
            else if (invalidStock)
            {
                paymentStatusBadge.textContent =
                    'Check stock';

                paymentStatusBadge.className =
                    'rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700';
            }
            else
            {
                paymentStatusBadge.textContent =
                    'Awaiting medicines';

                paymentStatusBadge.className =
                    'rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600';
            }
        }
        else if (
            currentPaymentMode ===
            'cash'
        )
        {
            if (cashSummary)
            {
                cashSummary.classList.remove(
                    'hidden'
                );
            }

            const difference =
                roundMoney(
                    cashReceived
                    - finalTotal
                );

            if (
                medicineCount > 0
                && cashReceived >=
                    finalTotal
                && ! invalidStock
            )
            {
                changeLabel.textContent =
                    'Change';

                changeDisplay.textContent =
                    money(
                        Math.max(
                            0,
                            difference
                        )
                    );

                changeDisplay.className =
                    'font-bold text-emerald-700';

                paymentStatusBadge.textContent =
                    'Ready for payment';

                paymentStatusBadge.className =
                    'rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700';
            }
            else if (
                medicineCount > 0
            )
            {
                const shortage =
                    roundMoney(
                        Math.max(
                            0,
                            finalTotal
                            - cashReceived
                        )
                    );

                changeLabel.textContent =
                    'Amount Short';

                changeDisplay.textContent =
                    money(shortage);

                changeDisplay.className =
                    'font-bold text-red-700';

                paymentStatusBadge.textContent =
                    invalidStock
                        ? 'Check stock'
                        : 'Cash incomplete';

                paymentStatusBadge.className =
                    'rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700';
            }
            else
            {
                changeLabel.textContent =
                    'Change';

                changeDisplay.textContent =
                    money(0);

                paymentStatusBadge.textContent =
                    'Awaiting medicines';

                paymentStatusBadge.className =
                    'rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600';
            }
        }
        else
        {
            if (cashSummary)
            {
                cashSummary.classList.add(
                    'hidden'
                );
            }

            if (
                medicineCount > 0
                && ! invalidStock
            )
            {
                paymentStatusBadge.textContent =
                    (
                        currentPaymentMode ===
                        'credit'
                        ||
                        currentPaymentMode ===
                        'mhis'
                    )
                        ? 'Authorized credit'
                        : 'Ready for payment';

                paymentStatusBadge.className =
                    'rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700';
            }
            else if (invalidStock)
            {
                paymentStatusBadge.textContent =
                    'Check stock';

                paymentStatusBadge.className =
                    'rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700';
            }
            else
            {
                paymentStatusBadge.textContent =
                    'Awaiting medicines';

                paymentStatusBadge.className =
                    'rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600';
            }
        }


        updateSubmitState(
            medicineCount,
            finalTotal,
            cashReceived,
            invalidStock
        );
    }


    function updateSubmitState(
        medicineCount,
        finalTotal,
        cashReceived,
        invalidStock
    )
    {
        const hasPatient =
            patientInput
            &&
            patientInput.value !== '';

        let disabled =
            ! hasPatient;

        if (medicineCount <= 0)
        {
            disabled = true;
        }

        if (invalidStock)
        {
            disabled = true;
        }

        if (
            ! isInpatient
            &&
            paymentMode?.value ===
            'cash'
            &&
            cashReceived <
            finalTotal
        )
        {
            disabled = true;
        }

        if (completeSaleButton)
        {
            completeSaleButton.disabled =
                disabled;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Payment fields
    |--------------------------------------------------------------------------
    */

    function updatePaymentFields()
    {
        if (isInpatient)
        {
            calculateSummary();
            return;
        }

        if (! paymentMode)
        {
            return;
        }

        const mode =
            paymentMode.value;

        if (cashWrapper)
        {
            cashWrapper.classList.toggle(
                'hidden',
                mode !== 'cash'
            );
        }

        if (
            transactionReferenceWrapper
        )
        {
            transactionReferenceWrapper
                .classList
                .toggle(
                    'hidden',
                    ! (
                        mode === 'upi'
                        || mode === 'card'
                    )
                );
        }

        calculateSummary();
    }


    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    if (addButton)
    {
        addButton.addEventListener(
            'click',
            function ()
            {
                addRow();
            }
        );
    }


    if (encounterSelector)
    {
        encounterSelector.addEventListener(
            'change',
            updatePatient
        );
    }


    if (encounterSearch)
    {
        encounterSearch.addEventListener(
            'input',
            filterEncounters
        );
    }


    if (paymentMode)
    {
        paymentMode.addEventListener(
            'change',
            updatePaymentFields
        );
    }


    if (discountInput)
    {
        discountInput.addEventListener(
            'input',
            calculateSummary
        );
    }


    if (cashInput)
    {
        cashInput.addEventListener(
            'input',
            calculateSummary
        );
    }


    if (dispensingForm)
    {
        dispensingForm.addEventListener(
            'submit',
            function (event)
            {
                const selectedMedicines =
                    Array.from(
                        rowsContainer
                            .querySelectorAll(
                                '.medicine-select'
                            )
                    )
                    .filter(
                        function (select)
                        {
                            return select.value !== '';
                        }
                    );

                if (
                    ! patientInput
                    || ! patientInput.value
                )
                {
                    event.preventDefault();

                    alert(
                        isInpatient
                            ? 'The inpatient record is missing a patient.'
                            : 'Please select a patient / encounter.'
                    );

                    return;
                }

                if (
                    selectedMedicines.length ===
                    0
                )
                {
                    event.preventDefault();

                    alert(
                        'Please select at least one medicine.'
                    );

                    return;
                }

                if (
                    ! confirm(
                        isInpatient
                            ? 'Dispense these medicines, deduct stock, and add the charges to the IP running bill?'
                            : 'Complete this pharmacy sale and deduct stock?'
                    )
                )
                {
                    event.preventDefault();
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restore medicines after validation error
    |--------------------------------------------------------------------------
    */

    if (
        Array.isArray(oldMedicines)
        &&
        oldMedicines.length > 0
    )
    {
        oldMedicines.forEach(
            function (item)
            {
                addRow(
                    item.medicine_id ?? '',
                    item.quantity ?? 1
                );
            }
        );
    }
    else
    {
        addRow();
    }


    /*
    |--------------------------------------------------------------------------
    | Initialize
    |--------------------------------------------------------------------------
    */

    updatePatient();

    updatePaymentFields();

    calculateSummary();

});
</script>

</x-app-layout>