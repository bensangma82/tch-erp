<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Stock Audit
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pharmacyStockAudit->audit_no }}
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('pharmacy.stock-audits.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Stock Audit Register
                </a>


                <a
                    href="{{ route('pharmacy.stock-batches.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Pharmacy Stock
                </a>


                <a
                    href="{{ route('pharmacy.stock-audits.print', $pharmacyStockAudit) }}"
                    target="_blank"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Print Audit
                </a>

            </div>

        </div>

    </x-slot>



    @php

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        $statusLabel =
            match ($pharmacyStockAudit->status) {

                'draft' =>
                    'Draft',

                'counting' =>
                    'Counting',

                'review' =>
                    'Under Review',

                'approved' =>
                    'Approved',

                'posted' =>
                    'Posted',

                'cancelled' =>
                    'Cancelled',

                default =>
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $pharmacyStockAudit->status
                        )
                    ),
            };


        $statusClass =
            match ($pharmacyStockAudit->status) {

                'draft' =>
                    'bg-slate-100 text-slate-700',

                'counting' =>
                    'bg-blue-50 text-blue-700',

                'review' =>
                    'bg-amber-50 text-amber-700',

                'approved' =>
                    'bg-purple-50 text-purple-700',

                'posted' =>
                    'bg-emerald-50 text-emerald-700',

                'cancelled' =>
                    'bg-red-50 text-red-700',

                default =>
                    'bg-slate-100 text-slate-700',
            };


        /*
        |--------------------------------------------------------------------------
        | PERMISSIONS
        |--------------------------------------------------------------------------
        */

        $canManageAudit =
            auth()->user()?->hasPermission(
                'pharmacy.stock-audit.create'
            ) ?? false;


        $canApproveAudit =
            auth()->user()?->hasPermission(
                'pharmacy.stock-audit.approve'
            ) ?? false;


        $canPostAudit =
            auth()->user()?->hasPermission(
                'pharmacy.stock-audit.post'
            ) ?? false;


        /*
        |--------------------------------------------------------------------------
        | COUNT EDITABILITY
        |--------------------------------------------------------------------------
        */

        $canEditCounts =
            $canManageAudit
            &&
            in_array(
                $pharmacyStockAudit->status,
                [
                    'draft',
                    'counting',
                ],
                true
            );


        /*
        |--------------------------------------------------------------------------
        | LOCATION
        |--------------------------------------------------------------------------
        */

        $auditLocation =
            $pharmacyStockAudit->location;

        $isLegacyAudit =
            empty(
                $pharmacyStockAudit->pharmacy_stock_location_id
            );

    @endphp



    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- FLASH MESSAGES --}}
            {{-- ========================================================= --}}

            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            @if (session('error'))

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>

            @endif


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
            {{-- LEGACY AUDIT WARNING --}}
            {{-- ========================================================= --}}

            @if ($isLegacyAudit)

                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">

                    <div class="font-semibold text-red-800">
                        Legacy hospital-wide audit
                    </div>

                    <div class="mt-2 text-sm leading-6 text-red-700">

                        This audit was created before location-based inventory
                        was introduced and does not have a stock location assigned.

                        It may be viewed for historical purposes, but it should
                        not be posted into the new location-based inventory system.

                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- AUDIT SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Stock Audit
                            </div>

                            <div class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $pharmacyStockAudit->audit_no }}
                            </div>

                        </div>


                        <span
                            class="inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $statusClass }}"
                        >
                            {{ $statusLabel }}
                        </span>

                    </div>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    {{-- LOCATION --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Stock Location
                        </div>

                        <div class="mt-2 font-bold text-slate-900">

                            @if ($auditLocation)

                                {{ $auditLocation->name }}

                            @else

                                Legacy / Unassigned

                            @endif

                        </div>

                        @if ($auditLocation)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $auditLocation->code }}
                            </div>

                        @endif

                    </div>



                    {{-- DATE --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Audit Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyStockAudit->audit_date?->format('d M Y') ?? '—' }}
                        </div>

                    </div>



                    {{-- TYPE --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Audit Type
                        </div>

                        <div class="mt-2 font-bold text-slate-900">

                            {{
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $pharmacyStockAudit->audit_type
                                    )
                                )
                            }}

                        </div>

                    </div>



                    {{-- CREATED BY --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Created By
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyStockAudit->createdBy?->name ?? 'System' }}
                        </div>

                    </div>



                    {{-- TOTAL BATCHES --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Total Batches
                        </div>

                        <div class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($pharmacyStockAudit->items->count()) }}
                        </div>

                    </div>



                    {{-- APPROVED BY --}}

                    @if ($pharmacyStockAudit->approved_at)

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Approved By
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $pharmacyStockAudit->approvedBy?->name ?? '—' }}
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $pharmacyStockAudit->approved_at->format('d M Y, h:i A') }}
                            </div>

                        </div>

                    @endif



                    {{-- POSTED BY --}}

                    @if ($pharmacyStockAudit->posted_at)

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Posted By
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $pharmacyStockAudit->postedBy?->name ?? '—' }}
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $pharmacyStockAudit->posted_at->format('d M Y, h:i A') }}
                            </div>

                        </div>

                    @endif



                    {{-- POSTED AT --}}

                    @if ($pharmacyStockAudit->posted_at)

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Posted At
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $pharmacyStockAudit->posted_at->format('d M Y, h:i A') }}
                            </div>

                        </div>

                    @endif


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- LOCATION BANNER --}}
            {{-- ========================================================= --}}

            @if ($auditLocation)

                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-blue-500">
                                Physical Location Being Audited
                            </div>

                            <div class="mt-2 text-xl font-bold text-blue-900">
                                {{ $auditLocation->name }}
                            </div>

                            <div class="mt-1 text-sm text-blue-700">
                                Location Code:
                                <span class="font-semibold">
                                    {{ $auditLocation->code }}
                                </span>
                            </div>

                        </div>


                        <div class="rounded-xl bg-white/70 px-4 py-3 text-sm text-blue-800">

                            All system quantities below refer to stock assigned to
                            <span class="font-bold">
                                {{ $auditLocation->name }}
                            </span>
                            at the time this audit was created.

                        </div>

                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- SUMMARY CARDS --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Location System Qty
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        {{ number_format($totalSystemQty) }}
                    </div>

                </div>



                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Physical Count
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        {{ number_format($totalCountedQty) }}
                    </div>

                </div>



                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-500">
                        Counted Batches
                    </div>

                    <div class="mt-2 text-2xl font-bold text-blue-800">

                        {{ number_format($countedItems) }}

                        /

                        {{ number_format($pharmacyStockAudit->items->count()) }}

                    </div>

                </div>



                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-500">
                        Variance Batches
                    </div>

                    <div class="mt-2 text-2xl font-bold text-amber-800">
                        {{ number_format($varianceItems) }}
                    </div>

                </div>



                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Net Variance
                    </div>

                    <div
                        class="mt-2 text-2xl font-bold
                        {{
                            $netVarianceQty > 0
                                ? 'text-emerald-700'
                                : (
                                    $netVarianceQty < 0
                                        ? 'text-red-700'
                                        : 'text-slate-900'
                                )
                        }}"
                    >
                        {{ $netVarianceQty > 0 ? '+' : '' }}{{ number_format($netVarianceQty) }}
                    </div>


                    <div class="mt-1 text-xs text-slate-500">

                        Value:

                        <span
                            class="
                            {{
                                $netVarianceValue > 0
                                    ? 'text-emerald-700'
                                    : (
                                        $netVarianceValue < 0
                                            ? 'text-red-700'
                                            : ''
                                    )
                            }}
                            "
                        >
                            ₹{{ number_format((float) $netVarianceValue, 2) }}
                        </span>

                    </div>

                </div>


            </div>



            {{-- ========================================================= --}}
            {{-- WORKFLOW / SAFETY MESSAGE --}}
            {{-- ========================================================= --}}

            @if ($pharmacyStockAudit->status === 'approved')

                <div class="rounded-2xl border border-purple-200 bg-purple-50 p-5">

                    <div class="font-semibold text-purple-800">
                        Audit approved — stock has not changed yet
                    </div>

                    <div class="mt-2 text-sm leading-6 text-purple-700">

                        Review the approved variance for

                        <span class="font-semibold">
                            {{ $auditLocation?->name ?? 'this audit' }}
                        </span>

                        carefully before final posting.

                        @if ($canPostAudit && ! $isLegacyAudit)

                            Posting will immediately apply the approved variance
                            to both this location's stock balance and the
                            hospital-wide batch balance.

                        @elseif ($isLegacyAudit)

                            This legacy audit cannot be posted because it has no
                            stock location assigned.

                        @else

                            Final posting must be completed by an authorised user.

                        @endif

                    </div>

                </div>


            @elseif (
                in_array(
                    $pharmacyStockAudit->status,
                    [
                        'draft',
                        'counting',
                    ],
                    true
                )
            )

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">

                    <div class="font-semibold text-amber-800">
                        Physical counting in progress
                    </div>

                    <div class="mt-2 text-sm leading-6 text-amber-700">

                        Avoid stock movements affecting

                        <span class="font-semibold">
                            {{ $auditLocation?->name ?? 'the audited stock' }}
                        </span>

                        until the audit is completed.

                        This includes dispensing, GRN receipts, purchase returns,
                        internal transfers, disposal and manual stock adjustments
                        involving this location.

                        Final posting will be blocked if the audited location's
                        live stock changes after the audit snapshot.

                        @if (! $canManageAudit)

                            <span class="font-semibold">
                                Your account has read-only access to this audit.
                            </span>

                        @endif

                    </div>

                </div>


            @elseif ($pharmacyStockAudit->status === 'review')

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">

                    <div class="font-semibold text-amber-800">
                        Audit awaiting approval
                    </div>

                    <div class="mt-2 text-sm leading-6 text-amber-700">

                        Physical counts are locked.

                        @if ($canApproveAudit)

                            Review every variance and its supporting reason before approval.

                        @else

                            Approval must be completed by an authorised user.

                        @endif

                    </div>

                </div>


            @elseif ($pharmacyStockAudit->status === 'posted')

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">

                    <div class="font-semibold text-emerald-800">
                        Audit posted successfully
                    </div>

                    <div class="mt-2 text-sm leading-6 text-emerald-700">

                        Approved variances have been applied to

                        <span class="font-semibold">
                            {{ $auditLocation?->name ?? 'the audited stock' }}
                        </span>

                        and to the hospital-wide batch balance.

                        Stock ledger entries have also been created.

                    </div>

                </div>


            @elseif ($pharmacyStockAudit->status === 'cancelled')

                <div class="rounded-2xl border border-red-200 bg-red-50 p-5">

                    <div class="font-semibold text-red-800">
                        Audit cancelled
                    </div>

                    <div class="mt-2 text-sm leading-6 text-red-700">
                        This audit will not affect inventory balances.
                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- COUNTING TABLE --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('pharmacy.stock-audits.counts.update', $pharmacyStockAudit) }}"
                id="auditCountForm"
                class="space-y-6"
            >

                @csrf
                @method('PUT')


                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                            <div>

                                <h3 class="font-semibold text-slate-900">
                                    Physical Stock Count
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">

                                    @if ($canEditCounts)

                                        Enter the quantity physically present at

                                        <span class="font-semibold">
                                            {{ $auditLocation?->name ?? 'the audited location' }}
                                        </span>

                                        for every batch.

                                    @else

                                        Physical counts are shown in read-only mode.

                                    @endif

                                </p>

                            </div>


                            @if ($canEditCounts)

                                <button
                                    type="submit"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                                >
                                    Save Counts
                                </button>

                            @endif

                        </div>

                    </div>



                    <div class="overflow-x-auto">

                        <table class="min-w-[1500px] w-full divide-y divide-slate-200">

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
                                        Location System Qty
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Physical Count
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Variance
                                    </th>

                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Variance Value
                                    </th>

                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Reason
                                    </th>

                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Remarks
                                    </th>

                                </tr>

                            </thead>



                            <tbody class="divide-y divide-slate-100">

                                @forelse ($pharmacyStockAudit->items as $index => $item)

                                    @php

                                        $variance =
                                            $item->variance_quantity;


                                        $varianceClass =
                                            $variance === null
                                                ? 'text-slate-400'
                                                : (
                                                    $variance > 0
                                                        ? 'text-emerald-700'
                                                        : (
                                                            $variance < 0
                                                                ? 'text-red-700'
                                                                : 'text-slate-700'
                                                        )
                                                );

                                    @endphp


                                    <tr
                                        class="audit-row"
                                        data-system="{{ $item->system_quantity }}"
                                        data-price="{{ (float) $item->purchase_price }}"
                                    >

                                        <td class="px-4 py-4 align-top">

                                            <input
                                                type="hidden"
                                                name="items[{{ $index }}][audit_item_id]"
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



                                        <td class="px-3 py-4 text-right align-top font-bold text-slate-900">
                                            {{ number_format($item->system_quantity) }}
                                        </td>



                                        <td class="px-3 py-4 align-top">

                                            <input
                                                type="number"
                                                min="0"
                                                step="1"
                                                name="items[{{ $index }}][counted_quantity]"
                                                value="{{ old("items.$index.counted_quantity", $item->counted_quantity) }}"
                                                class="count-input w-28 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                {{ $canEditCounts ? '' : 'readonly' }}
                                            >

                                        </td>



                                        <td class="px-3 py-4 text-right align-top">

                                            <div class="variance-qty font-bold {{ $varianceClass }}">

                                                @if ($variance === null)

                                                    —

                                                @else

                                                    {{ $variance > 0 ? '+' : '' }}{{ number_format($variance) }}

                                                @endif

                                            </div>

                                        </td>



                                        <td class="px-3 py-4 text-right align-top">

                                            <div class="variance-value font-semibold {{ $varianceClass }}">

                                                @if ($item->variance_value === null)

                                                    —

                                                @else

                                                    ₹{{ number_format((float) $item->variance_value, 2) }}

                                                @endif

                                            </div>

                                        </td>



                                        <td class="px-3 py-4 align-top">

                                            <select
                                                name="items[{{ $index }}][variance_reason]"
                                                class="reason-input w-44 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                {{ $canEditCounts ? '' : 'disabled' }}
                                            >

                                                <option value="">
                                                    No reason
                                                </option>


                                                @foreach ([
                                                    'shortage' => 'Shortage',
                                                    'excess' => 'Excess',
                                                    'counting_error' => 'Counting Error',
                                                    'breakage' => 'Breakage',
                                                    'expired' => 'Expired',
                                                    'missing' => 'Missing',
                                                    'documentation_error' => 'Documentation Error',
                                                    'other' => 'Other',
                                                ] as $reasonValue => $reasonLabel)

                                                    <option
                                                        value="{{ $reasonValue }}"
                                                        @selected(
                                                            old(
                                                                "items.$index.variance_reason",
                                                                $item->variance_reason
                                                            ) === $reasonValue
                                                        )
                                                    >
                                                        {{ $reasonLabel }}
                                                    </option>

                                                @endforeach

                                            </select>

                                        </td>



                                        <td class="px-3 py-4 align-top">

                                            <input
                                                type="text"
                                                name="items[{{ $index }}][remarks]"
                                                value="{{ old("items.$index.remarks", $item->remarks) }}"
                                                maxlength="1000"
                                                placeholder="Optional"
                                                class="w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                {{ $canEditCounts ? '' : 'readonly' }}
                                            >

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td
                                            colspan="9"
                                            class="px-6 py-10 text-center text-sm text-slate-500"
                                        >
                                            No stock batches were included in this audit.
                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>



                @if ($canEditCounts)

                    <div class="flex justify-end">

                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                        >
                            Save Physical Counts
                        </button>

                    </div>

                @endif

            </form>



            {{-- ========================================================= --}}
            {{-- WORKFLOW ACTIONS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">


                    <div>

                        <div class="font-semibold text-slate-900">
                            Audit Workflow
                        </div>


                        <div class="mt-1 text-xs leading-5 text-slate-500">

                            @if (
                                in_array(
                                    $pharmacyStockAudit->status,
                                    [
                                        'draft',
                                        'counting',
                                    ],
                                    true
                                )
                            )

                                @if ($canManageAudit)

                                    Complete all physical counts and document
                                    reasons for every variance before submitting
                                    for review.

                                @else

                                    Physical counting is being handled by an
                                    authorised pharmacy user.

                                @endif


                            @elseif ($pharmacyStockAudit->status === 'review')

                                @if ($canApproveAudit)

                                    Counts are locked. Review all location variances
                                    carefully before approval.

                                @else

                                    Counts are locked and the audit is awaiting
                                    approval by an authorised user.

                                @endif


                            @elseif ($pharmacyStockAudit->status === 'approved')

                                @if ($canPostAudit && ! $isLegacyAudit)

                                    Audit is approved. Inventory remains unchanged
                                    until final posting.

                                @elseif ($isLegacyAudit)

                                    This legacy audit has no stock location and
                                    cannot be posted.

                                @else

                                    Audit is approved and awaiting final stock
                                    posting by an authorised user.

                                @endif


                            @elseif ($pharmacyStockAudit->status === 'posted')

                                Audit has been posted and approved variances
                                have been applied to the audited location and
                                hospital-wide stock.


                            @elseif ($pharmacyStockAudit->status === 'cancelled')

                                This audit has been cancelled and cannot be posted.


                            @else

                                Current status:
                                {{ $statusLabel }}.

                            @endif

                        </div>

                    </div>



                    <div class="flex flex-wrap gap-3">


                        {{-- ================================================= --}}
                        {{-- DRAFT / COUNTING --}}
                        {{-- ================================================= --}}

                        @if (
                            $canManageAudit
                            &&
                            in_array(
                                $pharmacyStockAudit->status,
                                [
                                    'draft',
                                    'counting',
                                ],
                                true
                            )
                        )

                            <form
                                method="POST"
                                action="{{ route('pharmacy.stock-audits.cancel', $pharmacyStockAudit) }}"
                                onsubmit="return confirm('Cancel this stock audit?');"
                            >

                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100"
                                >
                                    Cancel Audit
                                </button>

                            </form>



                            <form
                                method="POST"
                                action="{{ route('pharmacy.stock-audits.submit-review', $pharmacyStockAudit) }}"
                                onsubmit="return confirm('Submit this stock audit for review? Counts will be locked.');"
                            >

                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700"
                                >
                                    Submit for Review
                                </button>

                            </form>

                        @endif



                        {{-- ================================================= --}}
                        {{-- REVIEW --}}
                        {{-- ================================================= --}}

                        @if ($pharmacyStockAudit->status === 'review')


                            @if ($canManageAudit)

                                <form
                                    method="POST"
                                    action="{{ route('pharmacy.stock-audits.cancel', $pharmacyStockAudit) }}"
                                    onsubmit="return confirm('Cancel this stock audit?');"
                                >

                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100"
                                    >
                                        Cancel Audit
                                    </button>

                                </form>

                            @endif



                            @if ($canApproveAudit)

                                <form
                                    method="POST"
                                    action="{{ route('pharmacy.stock-audits.approve', $pharmacyStockAudit) }}"
                                    onsubmit="return confirm('Approve this stock audit? Stock will remain unchanged until final posting.');"
                                >

                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700"
                                    >
                                        Approve Audit
                                    </button>

                                </form>

                            @endif

                        @endif



                        {{-- ================================================= --}}
                        {{-- APPROVED --}}
                        {{-- ================================================= --}}

                        @if ($pharmacyStockAudit->status === 'approved')


                            @if ($canManageAudit)

                                <form
                                    method="POST"
                                    action="{{ route('pharmacy.stock-audits.cancel', $pharmacyStockAudit) }}"
                                    onsubmit="return confirm('Cancel this approved audit? No stock has been posted yet.');"
                                >

                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100"
                                    >
                                        Cancel Audit
                                    </button>

                                </form>

                            @endif



                            @if ($canPostAudit && ! $isLegacyAudit)

                                <form
                                    method="POST"
                                    action="{{ route('pharmacy.stock-audits.post', $pharmacyStockAudit) }}"
                                    onsubmit="return confirm('POST THIS STOCK AUDIT? Approved variances will immediately change the audited location stock and hospital-wide stock, and create stock ledger entries.');"
                                >

                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                                    >
                                        Post Variances to Stock
                                    </button>

                                </form>

                            @endif

                        @endif


                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- REMARKS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h3 class="font-semibold text-slate-900">
                    Audit Remarks
                </h3>

                <div class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">
                    {{ $pharmacyStockAudit->remarks ?: 'No remarks.' }}
                </div>

            </div>


        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- LIVE VARIANCE CALCULATOR --}}
    {{-- ============================================================= --}}

    @if ($canEditCounts)

        <script>

            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    const rows =
                        document.querySelectorAll(
                            '.audit-row'
                        );


                    function money(value) {

                        const number =
                            Number(value || 0);


                        const sign =
                            number > 0
                                ? '+'
                                : '';


                        return sign
                            + '₹'
                            + number.toLocaleString(
                                'en-IN',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            );
                    }


                    function updateRow(row) {

                        const systemQty =
                            Number(
                                row.dataset.system || 0
                            );


                        const price =
                            Number(
                                row.dataset.price || 0
                            );


                        const input =
                            row.querySelector(
                                '.count-input'
                            );


                        const varianceElement =
                            row.querySelector(
                                '.variance-qty'
                            );


                        const varianceValueElement =
                            row.querySelector(
                                '.variance-value'
                            );


                        const reasonInput =
                            row.querySelector(
                                '.reason-input'
                            );


                        if (
                            input.value === ''
                            ||
                            input.value === null
                        ) {

                            varianceElement.textContent =
                                '—';


                            varianceValueElement.textContent =
                                '—';


                            reasonInput.required =
                                false;


                            return;
                        }


                        const counted =
                            Math.max(
                                0,
                                Number(input.value)
                            );


                        const variance =
                            counted - systemQty;


                        const varianceValue =
                            variance * price;


                        varianceElement.textContent =
                            (
                                variance > 0
                                    ? '+'
                                    : ''
                            )
                            +
                            variance.toLocaleString(
                                'en-IN'
                            );


                        varianceValueElement.textContent =
                            money(
                                varianceValue
                            );


                        varianceElement.classList.remove(
                            'text-emerald-700',
                            'text-red-700',
                            'text-slate-700',
                            'text-slate-400'
                        );


                        varianceValueElement.classList.remove(
                            'text-emerald-700',
                            'text-red-700',
                            'text-slate-700',
                            'text-slate-400'
                        );


                        let className =
                            'text-slate-700';


                        if (variance > 0) {

                            className =
                                'text-emerald-700';

                        } else if (variance < 0) {

                            className =
                                'text-red-700';

                        }


                        varianceElement.classList.add(
                            className
                        );


                        varianceValueElement.classList.add(
                            className
                        );


                        reasonInput.required =
                            variance !== 0;
                    }


                    rows.forEach(
                        function (row) {

                            const input =
                                row.querySelector(
                                    '.count-input'
                                );


                            if (! input) {
                                return;
                            }


                            input.addEventListener(
                                'input',
                                function () {
                                    updateRow(row);
                                }
                            );


                            input.addEventListener(
                                'change',
                                function () {
                                    updateRow(row);
                                }
                            );


                            updateRow(row);
                        }
                    );

                }
            );

        </script>

    @endif

</x-app-layout>