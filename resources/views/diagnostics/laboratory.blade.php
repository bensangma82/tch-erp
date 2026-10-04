<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Laboratory Worklist</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Paid and authorized laboratory orders grouped by order number.
                </p>
            </div>

            <a
                href="{{ route('imaging.index') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
            >
                Imaging Worklist
            </a>
        </div>
    </x-slot>

    <div class="w-full py-6">
        <div class="w-full px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                    <ul class="list-inside list-disc text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">
                            Laboratory Orders
                        </h3>

                        <p class="mt-0.5 text-xs text-gray-500">
                            One row per order. Open an order to work with its individual investigations.
                        </p>
                    </div>

                    <div class="text-sm text-gray-600">
                        Orders:
                        <span class="font-semibold text-gray-900">
                            {{ $orders->count() }}
                        </span>
                    </div>
                </div>

                <table class="w-full table-fixed border-collapse">

                    <colgroup>
                        <col style="width:15%">
                        <col style="width:19%">
                        <col style="width:23%">
                        <col style="width:18%">
                        <col style="width:17%">
                        <col style="width:8%">
                    </colgroup>

                    <thead class="bg-gray-50">
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Order
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Patient
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Doctor / Department
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Tests
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Progress
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Action
                            </th>
                        </tr>
                    </thead>

                    @forelse ($orders as $group)

                        @php
                            $order = $group->order;
                            $orderItems = $group->items;

                            $patient = $order?->patient;
                            $encounter = $order?->encounter;
                            $admission = $order?->admission;

                            $orderSource = $admission
                                ? 'IPD'
                                : ($encounter ? 'OPD' : 'OTHER');

                            $departmentName =
                                $admission?->department?->name
                                ?? $encounter?->department?->name
                                ?? '—';

                            $doctorName =
                                $admission?->consultant?->full_name
                                ?? $admission?->consultant?->name
                                ?? $encounter?->doctor?->full_name
                                ?? $encounter?->doctor?->name
                                ?? 'Unassigned';

                            $speciality =
                                $admission?->consultant?->speciality
                                ?? $encounter?->doctor?->speciality;

                            $totalTests = $orderItems->count();

                            /*
                            |--------------------------------------------------------------------------
                            | Awaiting Sample
                            |--------------------------------------------------------------------------
                            */

                            $awaitingSample = $orderItems
                                ->filter(function ($item) {
                                    if (
                                        ! $item->requires_sample
                                        || $item->status !== 'ordered'
                                    ) {
                                        return false;
                                    }

                                    $sample = $item->diagnosticSample;

                                    return ! $sample
                                        || $sample->status !== 'collected';
                                })
                                ->count();

                            /*
                            |--------------------------------------------------------------------------
                            | Ready for Processing
                            |--------------------------------------------------------------------------
                            */

                            $readyItems = $orderItems->filter(function ($item) {
                                if ($item->status !== 'ordered') {
                                    return false;
                                }

                                if (! $item->requires_sample) {
                                    return true;
                                }

                                return $item->diagnosticSample
                                    && $item->diagnosticSample->status === 'collected';
                            });

                            $ready = $readyItems->count();

                            $resultEntryItems = $orderItems
    ->filter(function ($item) {
        return $item->status === 'in_process';
    })
    ->values();

$firstResultEntryItem =
    $resultEntryItems->first();

                            /*
                            |--------------------------------------------------------------------------
                            | Result / Processing Progress
                            |--------------------------------------------------------------------------
                            */

                            $draft = $orderItems
                                ->filter(function ($item) {
                                    return $item->status === 'in_process'
                                        && $item->diagnosticResult
                                        && $item->diagnosticResult->status === 'draft';
                                })
                                ->count();

                            $inProcess = $orderItems
                                ->filter(function ($item) {
                                    return $item->status === 'in_process'
                                        && (
                                            ! $item->diagnosticResult
                                            || $item->diagnosticResult->status !== 'draft'
                                        );
                                })
                                ->count();

                            $completed =
                                $orderItems
                                    ->where('status', 'completed')
                                    ->count();

                            $printableCompleted = $orderItems
                                ->filter(function ($item) {
                                    return $item->status === 'completed'
                                        && $item->diagnosticResult
                                        && in_array(
                                            $item->diagnosticResult->status,
                                            ['final', 'verified'],
                                            true
                                        );
                                })
                                ->count();

                            $detailsId =
                                'lab-order-' .
                                ($order?->id ?? $loop->index);
                        @endphp

                        <tbody
                            id="{{ $detailsId }}"
                            x-data="{
                                open: {{ (int) request('open_order') === (int) $order?->id ? 'true' : 'false' }}
                            }"
                            class="border-b border-gray-200 last:border-b-0"
                        >

                            {{-- ORDER SUMMARY ROW --}}
                            <tr class="align-top hover:bg-gray-50">

                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap items-center gap-2">

                                        <span class="break-all font-mono text-xs font-semibold text-gray-900">
                                            {{ $order?->order_no ?? '—' }}
                                        </span>

                                        @if ($orderSource === 'IPD')
                                            <span
                                                class="rounded-full px-2 py-0.5 text-[10px] font-bold text-white"
                                                style="background-color: #7c3aed;"
                                            >
                                                IPD
                                            </span>
                                        @elseif ($orderSource === 'OPD')
                                            <span
                                                class="rounded-full px-2 py-0.5 text-[10px] font-bold text-white"
                                                style="background-color: #2563eb;"
                                            >
                                                OPD
                                            </span>
                                        @endif

                                    </div>

                                    <div class="mt-1 text-[11px] leading-4 text-gray-400">
                                        {{ $order?->ordered_at?->format('d M Y') ?? '—' }}
                                    </div>

                                    <div class="text-[11px] leading-4 text-gray-400">
                                        {{ $order?->ordered_at?->format('h:i A') ?? '' }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $patient?->full_name ?? '—' }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-gray-500">
                                        {{ $patient?->age !== null ? $patient->age . ' yrs' : 'Age —' }}
                                        /
                                        {{ $patient?->sex ? ucfirst($patient->sex) : '—' }}
                                    </div>

                                    <div class="mt-0.5 break-all font-mono text-[11px] text-gray-500">
                                        {{ $patient?->uhid ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-800">
                                        {{ $doctorName }}
                                    </div>

                                    @if ($speciality)
                                        <div class="mt-0.5 text-[11px] text-gray-400">
                                            {{ $speciality }}
                                        </div>
                                    @endif

                                    <div class="mt-1 text-xs font-semibold text-gray-600">
                                        {{ $departmentName }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $totalTests }}
                                        {{ Str::plural('test', $totalTests) }}
                                    </div>

                                    <div class="mt-1 line-clamp-2 text-[11px] leading-4 text-gray-500">
                                        {{ $orderItems->pluck('service_name')->join(', ') }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1">

                                        @if ($awaitingSample)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                                {{ $awaitingSample }} Awaiting
                                            </span>
                                        @endif

                                        @if ($ready)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                                                {{ $ready }} Ready
                                            </span>
                                        @endif

                                        @if ($inProcess)
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                                {{ $inProcess }} In Process
                                            </span>
                                        @endif

                                        @if ($draft)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                                {{ $draft }} Draft
                                            </span>
                                        @endif

                                        @if ($completed)
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">
                                                {{ $completed }} Completed
                                            </span>
                                        @endif

                                    </div>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <div class="flex flex-col items-end gap-2">

                                        @if ($printableCompleted > 0)
                                            <a
                                                href="{{ route('diagnostics.orders.results.group', $order->id) }}"
                                                class="inline-flex whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-slate-800"
                                            >
                                                Print {{ $printableCompleted }}
                                            </a>
                                        @endif

                                        <button
                                            type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800"
                                        >
                                            <span x-text="open ? 'Close' : 'Open'">
                                                Open
                                            </span>

                                            <svg
                                                class="h-4 w-4 transition-transform"
                                                :class="{ 'rotate-180': open }"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>

                                    </div>
                                </td>
                            </tr>

                            {{-- EXPANDED ORDER --}}
                            <tr x-show="open" x-cloak>
                                <td colspan="6" class="bg-gray-50 px-5 py-4">

                                    @php
                                        /*
                                        |--------------------------------------------------------------------------
                                        | Pending Laboratory Samples
                                        |--------------------------------------------------------------------------
                                        */

                                        $pendingSampleItems = $orderItems->filter(function ($item) {
                                            if (
                                                ! $item->requires_sample
                                                || $item->status !== 'ordered'
                                            ) {
                                                return false;
                                            }

                                            $sample = $item->diagnosticSample;

                                            return ! $sample
                                                || $sample->status !== 'collected';
                                        });

                                        $configuredPendingItems =
                                            $pendingSampleItems->filter(
                                                fn ($item) => filled($item->specimen_type)
                                            );

                                        $unconfiguredPendingItems =
                                            $pendingSampleItems->filter(
                                                fn ($item) => blank($item->specimen_type)
                                            );

                                        $specimenGroups =
                                            $configuredPendingItems
                                                ->groupBy('specimen_type')
                                                ->sortKeys();
                                    @endphp

                                    {{-- SAMPLE COLLECTION PANEL --}}
                                    @if ($pendingSampleItems->isNotEmpty())

                                        <div class="mb-4 overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50">

                                            <div class="flex flex-col gap-3 border-b border-indigo-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">

                                                <div>
                                                    <div class="text-sm font-semibold text-indigo-950">
                                                        Sample Collection
                                                    </div>

                                                    <div class="mt-0.5 text-xs text-indigo-700">
                                                        {{ $pendingSampleItems->count() }}
                                                        {{ $pendingSampleItems->count() === 1 ? 'investigation' : 'investigations' }}
                                                        awaiting collection
                                                    </div>
                                                </div>

                                                @if ($configuredPendingItems->isNotEmpty())
                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'diagnostics.orders.samples.bulk-store',
                                                            $order->id
                                                        ) }}"
                                                    >
                                                        @csrf

                                                        @foreach ($configuredPendingItems as $pendingItem)
                                                            <input
                                                                type="hidden"
                                                                name="item_ids[]"
                                                                value="{{ $pendingItem->id }}"
                                                            >
                                                        @endforeach

                                                        <button
                                                            type="submit"
                                                            onclick="return confirm('Record collection of all configured samples for this order?')"
                                                            class="inline-flex items-center justify-center rounded-lg bg-indigo-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-800"
                                                        >
                                                            Collect All Samples
                                                        </button>
                                                    </form>
                                                @endif

                                            </div>

                                            @if ($specimenGroups->isNotEmpty())

                                                <div class="grid gap-3 p-4 lg:grid-cols-2">

                                                    @foreach ($specimenGroups as $specimenType => $specimenItems)

                                                        @php
                                                            $container =
                                                                $specimenItems
                                                                    ->pluck('sample_container')
                                                                    ->filter()
                                                                    ->unique()
                                                                    ->join(', ');
                                                        @endphp

                                                        <div class="rounded-lg border border-indigo-200 bg-white p-4">

                                                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                                                                <div class="min-w-0">

                                                                    <div class="flex flex-wrap items-center gap-2">

                                                                        <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-800">
                                                                            {{ $specimenType }}
                                                                        </span>

                                                                        <span class="text-xs text-gray-500">
                                                                            {{ $specimenItems->count() }}
                                                                            {{ $specimenItems->count() === 1 ? 'test' : 'tests' }}
                                                                        </span>

                                                                    </div>

                                                                    @if ($container)
                                                                        <div class="mt-2 text-xs text-gray-500">
                                                                            Container:
                                                                            <span class="font-medium text-gray-700">
                                                                                {{ $container }}
                                                                            </span>
                                                                        </div>
                                                                    @endif

                                                                    <div class="mt-3 space-y-1">

                                                                        @foreach ($specimenItems as $specimenItem)

                                                                            <div class="flex items-start gap-2 text-sm text-gray-700">

                                                                                <svg
                                                                                    class="mt-0.5 h-4 w-4 flex-none text-indigo-500"
                                                                                    viewBox="0 0 20 20"
                                                                                    fill="currentColor"
                                                                                >
                                                                                    <path
                                                                                        fill-rule="evenodd"
                                                                                        d="M16.704 5.29a1 1 0 010 1.414l-7.5 7.5a1 1 0 01-1.414 0l-3.5-3.5a1 1 0 011.414-1.414L8.5 12.086l6.793-6.796a1 1 0 011.411 0z"
                                                                                        clip-rule="evenodd"
                                                                                    />
                                                                                </svg>

                                                                                <span>
                                                                                    {{ $specimenItem->service_name }}
                                                                                </span>

                                                                            </div>

                                                                        @endforeach

                                                                    </div>
                                                                </div>

                                                                <form
                                                                    method="POST"
                                                                    action="{{ route(
                                                                        'diagnostics.orders.samples.bulk-store',
                                                                        $order->id
                                                                    ) }}"
                                                                    class="flex-none"
                                                                >
                                                                    @csrf

                                                                    @foreach ($specimenItems as $specimenItem)
                                                                        <input
                                                                            type="hidden"
                                                                            name="item_ids[]"
                                                                            value="{{ $specimenItem->id }}"
                                                                        >
                                                                    @endforeach

                                                                    <button
                                                                        type="submit"
                                                                        class="inline-flex min-w-[120px] items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                                                    >
                                                                        Collect {{ $specimenType }}
                                                                    </button>
                                                                </form>

                                                            </div>
                                                        </div>

                                                    @endforeach

                                                </div>

                                            @endif

                                            @if ($unconfiguredPendingItems->isNotEmpty())

                                                <div class="border-t border-amber-200 bg-amber-50 px-4 py-3">

                                                    <div class="text-xs font-semibold text-amber-800">
                                                        Specimen configuration required
                                                    </div>

                                                    <div class="mt-1 text-xs text-amber-700">
                                                        The following investigations cannot use quick collection until a
                                                        default specimen type is configured in the Service Master:
                                                    </div>

                                                    <div class="mt-2 flex flex-wrap gap-2">

                                                        @foreach ($unconfiguredPendingItems as $unconfiguredItem)

                                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                                                {{ $unconfiguredItem->service_name }}
                                                            </span>

                                                        @endforeach

                                                    </div>
                                                </div>

                                            @endif

                                        </div>

                                    @endif

                                    @php
                                        /*
                                        |--------------------------------------------------------------------------
                                        | Collected Specimen Summary
                                        |--------------------------------------------------------------------------
                                        */

                                        $collectedSpecimenGroups =
                                            $orderItems
                                                ->filter(function ($item) {
                                                    return $item->diagnosticSample
                                                        && $item->diagnosticSample->status === 'collected'
                                                        && filled($item->diagnosticSample->sample_no);
                                                })
                                                ->groupBy(
                                                    fn ($item) => $item->diagnosticSample->sample_no
                                                );
                                    @endphp

                                    {{-- COLLECTED SPECIMENS --}}
                                    @if ($collectedSpecimenGroups->isNotEmpty())

                                        <div class="mb-4">

                                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                Collected Specimens
                                            </div>

                                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">

                                                @foreach ($collectedSpecimenGroups as $sampleNo => $sampleItems)

                                                    @php
                                                        $firstSample =
                                                            $sampleItems
                                                                ->first()
                                                                ?->diagnosticSample;

                                                        $specimenType =
                                                            $firstSample?->specimen_type
                                                            ?? 'Specimen';
                                                    @endphp

                                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">

                                                        <div class="flex flex-wrap items-center justify-between gap-2">

                                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                                                {{ $specimenType }}
                                                            </span>

                                                            <div class="flex items-center gap-2">

                                                                <span class="font-mono text-xs font-semibold text-emerald-800">
                                                                    {{ $sampleNo }}
                                                                </span>

                                                                <a
                                                                    href="{{ route('diagnostics.samples.label', $sampleNo) }}"
                                                                    target="_blank"
                                                                    class="inline-flex items-center rounded-lg border border-emerald-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-100"
                                                                >
                                                                    Print Label
                                                                </a>

                                                            </div>
                                                        </div>

                                                        <div class="mt-3 space-y-1">

                                                            @foreach ($sampleItems as $sampleItem)
                                                                <div class="text-sm text-gray-700">
                                                                    {{ $sampleItem->service_name }}
                                                                </div>
                                                            @endforeach

                                                        </div>
                                                    </div>

                                                @endforeach

                                            </div>
                                        </div>

                                    @endif

                                    {{-- BULK START PROCESSING --}}
                                    @if ($readyItems->isNotEmpty())

                                        <div class="mb-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">

                                            <div>
                                                <div class="text-sm font-semibold text-slate-900">
                                                    Ready for Processing
                                                </div>

                                                <div class="mt-0.5 text-xs text-slate-500">
                                                    {{ $readyItems->count() }}
                                                    {{ $readyItems->count() === 1 ? 'investigation' : 'investigations' }}
                                                    ready
                                                </div>
                                            </div>

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'diagnostics.orders.start-processing',
                                                    $order->id
                                                ) }}"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800"
                                                >
                                                    Start Processing All
                                                </button>
                                            </form>

                                        </div>

                                    @endif

                                    {{-- ORDER RESULT ENTRY --}}
@if ($resultEntryItems->isNotEmpty())

    <div class="mb-4 flex flex-col gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <div class="text-sm font-semibold text-blue-950">
                Laboratory Result Entry
            </div>

            <div class="mt-0.5 text-xs text-blue-700">
                {{ $resultEntryItems->count() }}
                {{ $resultEntryItems->count() === 1 ? 'investigation' : 'investigations' }}
                ready for result entry
            </div>
        </div>

        <a
            href="{{ route(
                'diagnostics.items.result.edit',
                $firstResultEntryItem
            ) }}"
            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700"
        >
            Enter Results – {{ $resultEntryItems->count() }} Tests
        </a>

    </div>

@endif

                                    {{-- INVESTIGATION TABLE --}}
                                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">

                                        <table class="w-full table-fixed">

                                            <colgroup>
                                                <col style="width:38%">
                                                <col style="width:25%">
                                                <col style="width:17%">
                                                <col style="width:20%">
                                            </colgroup>

                                            <thead class="bg-gray-50">
                                                <tr class="border-b border-gray-200">

                                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                        Investigation
                                                    </th>

                                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                        Sample
                                                    </th>

                                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                        Status
                                                    </th>

                                                    <th class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                        Action
                                                    </th>

                                                </tr>
                                            </thead>

                                            <tbody class="divide-y divide-gray-100">

                                                @foreach ($orderItems as $item)

                                                    @php
                                                        $sample = $item->diagnosticSample;
                                                        $result = $item->diagnosticResult;

                                                        $requiresSample =
                                                            (bool) $item->requires_sample;

                                                        $sampleCollected =
                                                            $sample
                                                            && $sample->status === 'collected';

                                                        $sampleRejected =
                                                            $sample
                                                            && $sample->status === 'rejected';
                                                    @endphp

                                                    <tr>

                                                        <td class="px-4 py-3">
                                                            <div class="text-sm font-semibold text-gray-900">
                                                                {{ $item->service_name }}
                                                            </div>

                                                            <div class="font-mono text-[11px] text-gray-500">
                                                                {{ $item->service_code }}
                                                            </div>
                                                        </td>

                                                        <td class="px-4 py-3">

                                                            @if ($requiresSample)

                                                                @if ($sampleCollected)

                                                                    <div class="text-[11px] font-semibold text-emerald-700">
                                                                        {{ $sample->specimen_type ?? 'Collected' }}
                                                                    </div>

                                                                    <div class="text-[11px] text-gray-500">
                                                                        Sample collected
                                                                    </div>

                                                                @elseif ($sampleRejected)

                                                                    <div class="text-[11px] font-semibold text-red-600">
                                                                        Rejected

                                                                        @if ($sample->sample_no)
                                                                            · {{ $sample->sample_no }}
                                                                        @endif
                                                                    </div>

                                                                @else

                                                                    <div class="text-[11px] font-semibold text-amber-600">
                                                                        Sample Required
                                                                    </div>

                                                                @endif

                                                            @else

                                                                <div class="text-[11px] text-gray-400">
                                                                    No sample required
                                                                </div>

                                                            @endif

                                                        </td>

                                                        <td class="px-4 py-3">

                                                            @if ($result && $result->status === 'draft')

                                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                                                    Draft Result
                                                                </span>

                                                            @elseif ($item->status === 'ordered')

                                                                @if ($requiresSample && ! $sampleCollected)

                                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                                                        Awaiting Sample
                                                                    </span>

                                                                @else

                                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                                                                        Ready
                                                                    </span>

                                                                @endif

                                                            @elseif ($item->status === 'in_process')

                                                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                                                    In Process
                                                                </span>

                                                            @elseif ($item->status === 'completed')

                                                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">
                                                                    Completed
                                                                </span>

                                                            @else

                                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold capitalize text-gray-700">
                                                                    {{ str_replace('_', ' ', $item->status) }}
                                                                </span>

                                                            @endif

                                                        </td>

                                                        <td class="px-4 py-3 text-right">
                                                            <div class="flex flex-col items-end gap-1">

                                                                @if ($item->status === 'ordered')

                                                                    @if ($requiresSample)

                                                                        @if (! $sample || $sampleRejected)

                                                                            <a
                                                                                href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                                                class="inline-flex rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700"
                                                                            >
                                                                                {{ $sampleRejected ? 'Recollect Sample' : 'Collect Sample' }}
                                                                            </a>

                                                                        @elseif ($sampleCollected)

                                                                            <a
                                                                                href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                                                class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-900"
                                                                            >
                                                                                View Sample
                                                                            </a>

                                                                        @else

                                                                            <a
                                                                                href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                                                class="inline-flex rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700"
                                                                            >
                                                                                Manage Sample
                                                                            </a>

                                                                        @endif

                                                                    @else

                                                                        <span class="text-[11px] font-semibold text-slate-500">
                                                                            Ready
                                                                        </span>

                                                                    @endif

                                                                @elseif ($item->status === 'in_process')

    @if ($result && $result->status === 'draft')

        <span class="text-[11px] font-semibold text-amber-700">
            Draft saved
        </span>

    @else

        <span class="text-[11px] font-semibold text-blue-600">
            Ready for result
        </span>

    @endif

                                                                @elseif ($item->status === 'completed')

                                                                    @if ($result)

                                                                        <div class="flex items-center justify-end gap-2">

                                                                            <a
                                                                                href="{{ route('diagnostics.items.result.show', $item) }}"
                                                                                class="inline-flex rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                                                            >
                                                                                View
                                                                            </a>

                                                                            <a
                                                                                href="{{ route('diagnostics.items.result.show', $item) }}?print=1"
                                                                                target="_blank"
                                                                                class="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800"
                                                                            >
                                                                                Print
                                                                            </a>

                                                                        </div>

                                                                    @else

                                                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">
                                                                            Completed
                                                                        </span>

                                                                    @endif

                                                                @endif

                                                            </div>
                                                        </td>

                                                    </tr>

                                                @endforeach

                                            </tbody>
                                        </table>
                                    </div>

                                </td>
                            </tr>

                        </tbody>

                    @empty

                        <tbody>
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">

                                    <div class="text-sm font-medium text-gray-700">
                                        No laboratory investigations are waiting.
                                    </div>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Paid or authorized laboratory orders will appear here automatically.
                                    </p>

                                </td>
                            </tr>
                        </tbody>

                    @endforelse

                </table>
            </div>
        </div>
    </div>
</x-app-layout>