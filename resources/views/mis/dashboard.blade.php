
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                <svg class="h-6 w-6" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24" stroke-width="1.8">
                    <path d="M3 20V4m0 16h18M7 16v-5m5 5V7m5 9V9"/>
                </svg>
            </div>

            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Management Information System
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Tura Christian Hospital — Executive and Departmental Reporting
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $misTabs = [
            ['key' => 'overview', 'name' => 'Overview'],
            ['key' => 'clinical', 'name' => 'Clinical Activity'],
            ['key' => 'departments', 'name' => 'Departments'],
            ['key' => 'beds', 'name' => 'Bed Utilization'],
            ['key' => 'finance', 'name' => 'Finance'],
            ['key' => 'diagnostics', 'name' => 'Diagnostics'],
            ['key' => 'pharmacy', 'name' => 'Pharmacy'],
            ['key' => 'expenses', 'name' => 'Expenses'],
        ];

        $misKpis = [
            [
                'label' => 'OPD Registered',
                'value' => number_format($clinicalOpdRegistered),
                'detail' => 'Registered OPD encounters',
                'color' => 'text-blue-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'OPD Seen',
                'value' => number_format($clinicalOpdSeen),
                'detail' => 'Consultations confirmed as completed',
                'color' => 'text-emerald-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'OPD Pending',
                'value' => number_format($clinicalOpdPending),
                'detail' => 'Consultations not marked completed',
                'color' => 'text-amber-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'IPD Census',
                'value' => number_format($clinicalIpdCensus),
                'detail' => 'Inpatients at the reporting cutoff',
                'color' => 'text-indigo-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'Laboratory Tests',
                'value' => number_format($clinicalLaboratoryTests),
                'detail' => 'Laboratory test quantities ordered',
                'color' => 'text-violet-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'Ultrasound',
                'value' => number_format($clinicalUltrasound),
                'detail' => 'Ultrasound examination quantities',
                'color' => 'text-teal-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'X-ray',
                'value' => number_format($clinicalXray),
                'detail' => 'X-ray examination quantities',
                'color' => 'text-sky-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'ECG',
                'value' => number_format($clinicalEcg),
                'detail' => 'ECG examination quantities',
                'color' => 'text-rose-700',
                'tab' => 'clinical',
            ],

            [
                'label' => 'OPD Visits',
                'value' => number_format($opdVisits),
                'detail' => 'Registered outpatient encounters',
                'color' => 'text-blue-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'Unique OPD Patients',
                'value' => number_format($opdPatients),
                'detail' => 'Distinct patients in the reporting period',
                'color' => 'text-indigo-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'IPD Admissions',
                'value' => number_format($ipdAdmissions),
                'detail' => 'Admissions within the selected dates',
                'color' => 'text-emerald-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'IPD Discharges',
                'value' => number_format($ipdDischarges),
                'detail' => 'Recorded discharges within the period',
                'color' => 'text-sky-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'Emergency Visits',
                'value' => number_format($emergencyVisits),
                'detail' => 'Registered emergency arrivals',
                'color' => 'text-rose-700',
                'tab' => 'clinical',
            ],
            [
                'label' => 'Average Length of Stay',
                'value' => $averageLengthOfStay !== null
                    ? number_format((float) $averageLengthOfStay, 2) . ' days'
                    : 'N/A',
                'detail' => 'Completed stays discharged in the period',
                'color' => 'text-violet-700',
                'tab' => 'clinical',
            ],
        ];

      $futureReports = [
    'expenses' => [
        'title' => 'Hospital Expenses MIS',
        'description' => 'Clinical, non-clinical and shared expenditure, departmental cost allocations and expense accounting.',
    ],
];
    @endphp

    <div class="py-6">
        <div
            class="mx-auto max-w-screen-2xl space-y-5 px-4 sm:px-6 lg:px-8"
            x-data="{
                activeTab: 'overview',
                goToTab(tab) {
                    this.activeTab = tab;
                    this.$nextTick(() => {
                        document.getElementById('mis-report-content')
                            ?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                    });
                }
            }"
        >

            {{-- =============================================== --}}
            {{-- REPORTING PERIOD --}}
            {{-- =============================================== --}}

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-bold text-slate-900">
                                Hospital Performance Overview
                            </h3>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                MIS
                            </span>
                        </div>

                        <p class="mt-2 text-sm text-slate-500">
                            Reporting period:
                            <span class="font-semibold text-slate-700">
                                {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                                —
                                {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                            </span>
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            All clinical activity reports use the selected dates.
                            Current bed-status reporting is a live snapshot.
                        </p>
                    </div>

                    <form method="GET"
                          action="{{ route('mis.dashboard') }}"
                          class="flex flex-col gap-3 sm:flex-row sm:items-end">

                        <div>
                            <label for="mis_from"
                                   class="mb-1 block text-xs font-semibold text-slate-600">
                                From Date
                            </label>
                            <input
                                id="mis_from"
                                type="date"
                                name="from"
                                value="{{ $from }}"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label for="mis_to"
                                   class="mb-1 block text-xs font-semibold text-slate-600">
                                To Date
                            </label>
                            <input
                                id="mis_to"
                                type="date"
                                name="to"
                                value="{{ $to }}"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <button type="submit"
                                class="rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-800">
                            Generate Report
                        </button>
                    </form>
                </div>
            </section>

            {{-- =============================================== --}}
            {{-- CLICKABLE MAIN REPORT TABS --}}
            {{-- =============================================== --}}

            <nav aria-label="MIS report sections"
                 class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">

                <div class="flex flex-wrap gap-2">
                    @foreach($misTabs as $tab)
                        <button
                            type="button"
                            @click="goToTab('{{ $tab['key'] }}')"
                            :aria-current="activeTab === '{{ $tab['key'] }}' ? 'page' : null"
                            :class="activeTab === '{{ $tab['key'] }}'
                                ? 'bg-indigo-700 text-white shadow-sm'
                                : 'bg-slate-50 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700'"
                            class="rounded-lg px-4 py-2.5 text-sm font-semibold transition"
                        >
                            {{ $tab['name'] }}
                        </button>
                    @endforeach
                </div>
            </nav>

            <div id="mis-report-content" class="scroll-mt-6 space-y-5">

                {{-- =========================================== --}}
                {{-- TAB: EXECUTIVE OVERVIEW --}}
                {{-- =========================================== --}}

                <section x-show="activeTab === 'overview'" class="space-y-5">

                    <div>
                        <h3 class="text-xl font-bold text-slate-900">
                            Executive Hospital Overview
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Key clinical and operational indicators for hospital management.
                            Select any card to open its detailed reporting section.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($misKpis as $kpi)
                            <button
                                type="button"
                                @click="goToTab('{{ $kpi['tab'] }}')"
                                class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-indigo-300 hover:shadow-md"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                        {{ $kpi['label'] }}
                                    </p>
                                    <span class="text-indigo-600">↗</span>
                                </div>

                                <p class="mt-4 text-3xl font-bold tabular-nums {{ $kpi['color'] }}">
                                    {{ $kpi['value'] }}
                                </p>

                                <p class="mt-2 text-xs leading-5 text-slate-500">
                                    {{ $kpi['detail'] }}
                                </p>

                                <p class="mt-4 border-t border-slate-100 pt-3 text-xs font-semibold text-indigo-700">
                                    View Clinical Report →
                                </p>
                            </button>
                        @endforeach
                    </div>

                    {{-- Overview operational summary --}}

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

                        <button type="button"
                                @click="goToTab('beds')"
                                class="rounded-2xl border border-slate-200 bg-white p-6 text-left shadow-sm transition hover:border-indigo-300 hover:shadow-md">

                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-slate-900">
                                    Current Bed Occupancy
                                </h4>
                                <span class="text-sm font-semibold text-indigo-700">
                                    Details →
                                </span>
                            </div>

                            <div class="mt-4 flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-3xl font-bold tabular-nums text-blue-700">
                                        @if($currentOccupancyPercentage !== null)
                                            {{ number_format($currentOccupancyPercentage, 2) }}%
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                    <p class="mt-2 text-xs text-slate-500">
                                        {{ $occupiedBeds }} occupied of
                                        {{ $operationalBeds }} operational beds
                                    </p>
                                </div>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    Live
                                </span>
                            </div>

                            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-blue-600"
                                     style="width: {{ min(100, max(0, $currentOccupancyPercentage ?? 0)) }}%">
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-slate-500">
                                Snapshot based on active registered ERP beds.
                            </p>
                        </button>

                        <button type="button"
                                @click="goToTab('beds')"
                                class="rounded-2xl border border-slate-200 bg-white p-6 text-left shadow-sm transition hover:border-indigo-300 hover:shadow-md">

                            <div class="flex items-center justify-between gap-3">
                                <h4 class="font-bold text-slate-900">
                                    Historical Bed Utilization
                                </h4>
                                <span class="text-sm font-semibold text-indigo-700">
                                    Details →
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-slate-500">
                                        Occupied Bed-Days
                                    </p>
                                    <p class="mt-2 text-2xl font-bold tabular-nums text-indigo-700">
                                        {{ number_format((float) $occupiedBedDays, 2) }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-500">
                                        Average Daily Occupied Beds
                                    </p>
                                    <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-700">
                                        {{ $averageDailyCensus !== null
                                            ? number_format((float) $averageDailyCensus, 2)
                                            : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <p class="mt-5 border-t border-slate-100 pt-3 text-xs text-amber-700">
                                Provisional — based on recorded bed-allocation intervals
                            </p>
                        </button>
                    </div>

                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-5">
                        <h4 class="text-sm font-bold text-indigo-900">
                            MIS Navigation
                        </h4>
                        <p class="mt-2 text-xs leading-6 text-indigo-800">
                            Use the tabs above for detailed clinical, departmental
                            and bed-utilization reports. Finance, Diagnostics,
                            Pharmacy and MHIS reporting will become available as
                            their data sources and calculations are validated.
                        </p>
                    </div>
                </section>

                {{-- =========================================== --}}
                {{-- TAB: CLINICAL ACTIVITY --}}
                {{-- =========================================== --}}

                <section x-show="activeTab === 'clinical'"
                         x-cloak
                         class="space-y-5">

                    <div>
                        <h3 class="text-xl font-bold text-slate-900">
                            Clinical Activity Report
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Outpatient, inpatient and emergency service activity.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($misKpis as $kpi)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                    {{ $kpi['label'] }}
                                </p>
                                <p class="mt-4 text-3xl font-bold tabular-nums {{ $kpi['color'] }}">
                                    {{ $kpi['value'] }}
                                </p>
                                <p class="mt-2 text-xs leading-5 text-slate-500">
                                    {{ $kpi['detail'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h4 class="font-bold text-slate-900">
                                Outpatient Activity
                            </h4>
                            <div class="mt-5 space-y-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">OPD Encounters</span>
                                    <span class="font-bold text-slate-900">
                                        {{ number_format($opdVisits) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Unique Patients</span>
                                    <span class="font-bold text-slate-900">
                                        {{ number_format($opdPatients) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h4 class="font-bold text-slate-900">
                                Inpatient &amp; Emergency Activity
                            </h4>
                            <div class="mt-5 space-y-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Admissions</span>
                                    <span class="font-bold text-slate-900">
                                        {{ number_format($ipdAdmissions) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Discharges</span>
                                    <span class="font-bold text-slate-900">
                                        {{ number_format($ipdDischarges) }}
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Emergency Visits</span>
                                    <span class="font-bold text-slate-900">
                                        {{ number_format($emergencyVisits) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs leading-6 text-slate-600">
                        Clinical activity is filtered by the selected reporting
                        dates. Average Length of Stay is calculated from qualifying
                        completed admissions and may be unavailable where no
                        discharges have been recorded.
                    </p>
                </section>

                {{-- =========================================== --}}
                {{-- TAB: DEPARTMENTS --}}
                {{-- =========================================== --}}

                <section x-show="activeTab === 'departments'"
                         x-cloak
                         class="space-y-5">

                    <div>
                        <h3 class="text-xl font-bold text-slate-900">
                            Departmental Performance
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Department-wise OPD attendance and inpatient admissions.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

                        {{-- OPD departments --}}

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h4 class="font-bold text-slate-900">
                                    Department-wise OPD Visits
                                </h4>
                                <p class="mt-1 text-xs text-slate-500">
                                    All registered departments and their OPD volume
                                </p>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                                                Department
                                            </th>
                                            <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                                Visits
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($departmentOpd as $department)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-5 py-3 text-sm text-slate-700">
                                                    {{ $department->name }}
                                                </td>
                                                <td class="px-5 py-3 text-right text-sm font-bold tabular-nums text-slate-900">
                                                    {{ number_format($department->opd_visits) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="px-5 py-8 text-center text-sm text-slate-500">
                                                    No department records found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- IPD departments --}}

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h4 class="font-bold text-slate-900">
                                    Department-wise IPD Admissions
                                </h4>
                                <p class="mt-1 text-xs text-slate-500">
                                    Admitting departments, including those with zero activity
                                </p>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                                                Department
                                            </th>
                                            <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                                Admissions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($departmentIpd as $department)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-5 py-3 text-sm text-slate-700">
                                                    {{ $department->name }}
                                                </td>
                                                <td class="px-5 py-3 text-right text-sm font-bold tabular-nums text-slate-900">
                                                    {{ number_format($department->admissions) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="px-5 py-8 text-center text-sm text-slate-500">
                                                    No inpatient department records found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- =========================================== --}}
                {{-- TAB: BED UTILIZATION --}}
                {{-- =========================================== --}}

                <section x-show="activeTab === 'beds'"
                         x-cloak
                         class="space-y-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">
                                Bed Occupancy &amp; Utilization
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Current ward occupancy and provisional historical utilization.
                            </p>
                        </div>

                        <span class="w-fit rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                            Live Snapshot + Historical Report
                        </span>
                    </div>

                    {{-- Current status cards --}}

                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                        @foreach([
                            ['name' => 'Registered', 'value' => $totalActiveBeds],
                            ['name' => 'Operational', 'value' => $operationalBeds],
                            ['name' => 'Occupied', 'value' => $occupiedBeds],
                            ['name' => 'Available', 'value' => $availableBeds],
                            ['name' => 'Cleaning / Reserved', 'value' => $cleaningBeds + $reservedBeds],
                            ['name' => 'Maintenance', 'value' => $maintenanceBeds],
                        ] as $metric)
                            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="text-xs font-semibold text-slate-500">
                                    {{ $metric['name'] }}
                                </p>
                                <p class="mt-3 text-2xl font-bold tabular-nums text-slate-900">
                                    {{ number_format($metric['value']) }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Current occupancy --}}

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-slate-900">
                                    Current Bed Occupancy
                                </h4>
                                <p class="mt-1 text-xs text-slate-500">
                                    Occupied beds divided by operational registered beds
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold tabular-nums text-blue-700">
                                    {{ $currentOccupancyPercentage !== null
                                        ? number_format($currentOccupancyPercentage, 2) . '%'
                                        : 'N/A' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $occupiedBeds }} / {{ $operationalBeds }} beds
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-blue-600"
                                 style="width: {{ min(100, max(0, $currentOccupancyPercentage ?? 0)) }}%">
                            </div>
                        </div>

                        <p class="mt-3 text-xs leading-5 text-slate-500">
                            Maintenance beds are excluded from the operational
                            denominator. Cleaning and reserved beds remain
                            in the denominator but are not counted as occupied.
                        </p>
                    </div>

                    {{-- Ward-wise table --}}

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h4 class="font-bold text-slate-900">
                                Ward-wise Bed Utilization
                            </h4>
                            <p class="mt-1 text-xs text-slate-500">
                                Current bed distribution by registered ward
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                                            Ward
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500">
                                            Total
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500">
                                            Occupied
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500">
                                            Available
                                        </th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500">
                                            Other
                                        </th>
                                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                            Occupancy
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">
                                    @forelse($wardOccupancy as $ward)
                                        @php
                                            $otherBeds =
                                                (int) $ward->cleaning_beds
                                                + (int) $ward->reserved_beds
                                                + (int) $ward->maintenance_beds;
                                        @endphp

                                        <tr class="hover:bg-slate-50">
                                            <td class="px-5 py-4 text-sm font-semibold text-slate-800">
                                                {{ $ward->name }}
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm text-slate-700">
                                                {{ $ward->total_beds }}
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm font-bold text-rose-700">
                                                {{ $ward->occupied_beds }}
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm font-bold text-emerald-700">
                                                {{ $ward->available_beds }}
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm text-slate-700">
                                                {{ $otherBeds }}
                                            </td>
                                            <td class="px-5 py-4 text-right text-sm font-bold text-slate-900">
                                                {{ $ward->occupancy_percentage !== null
                                                    ? number_format($ward->occupancy_percentage, 1) . '%'
                                                    : 'N/A' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                                No active ward beds registered.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Historical utilization --}}

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="font-bold text-slate-900">
                                    Historical Bed Utilization
                                </h4>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                                    —
                                    {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                                </p>
                            </div>

                            <span class="rounded-full px-3 py-1.5 text-xs font-semibold
                                {{ $historicalPeriodComplete
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-amber-50 text-amber-700' }}">
                                {{ $historicalPeriodComplete
                                    ? 'Completed Period'
                                    : 'Ongoing / Future Period' }}
                            </span>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-xl bg-blue-50 p-5">
                                <p class="text-xs font-semibold text-blue-800">
                                    Occupied Bed-Days
                                </p>
                                <p class="mt-3 text-3xl font-bold tabular-nums text-blue-700">
                                    {{ number_format((float) $occupiedBedDays, 2) }}
                                </p>
                                <p class="mt-2 text-xs text-blue-800">
                                    24-hour bed-time equivalents
                                </p>
                            </div>

                            <div class="rounded-xl bg-emerald-50 p-5">
                                <p class="text-xs font-semibold text-emerald-800">
                                    Average Daily Occupied Beds
                                </p>
                                <p class="mt-3 text-3xl font-bold tabular-nums text-emerald-700">
                                    {{ $averageDailyCensus !== null
                                        ? number_format((float) $averageDailyCensus, 2)
                                        : 'N/A' }}
                                </p>
                                <p class="mt-2 text-xs text-emerald-800">
                                    Based on elapsed reporting time
                                </p>
                            </div>

                            <div class="rounded-xl bg-violet-50 p-5">
                                <p class="text-xs font-semibold text-violet-800">
                                    Elapsed Reporting Days
                                </p>
                                <p class="mt-3 text-3xl font-bold tabular-nums text-violet-700">
                                    {{ number_format((float) $elapsedDays, 2) }}
                                </p>
                                <p class="mt-2 text-xs text-violet-800">
                                    Period duration used in the calculation
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-5">
                        <h4 class="text-sm font-bold text-amber-900">
                            Provisional Historical Utilization
                        </h4>
                        <p class="mt-2 text-xs leading-6 text-amber-800">
                            Historical figures use recorded bed-allocation intervals.
                            Overlapping intervals for the same bed are merged.
                            They represent occupied time expressed in 24-hour
                            equivalents, not an official midnight patient census.
                        </p>
                        <p class="mt-2 text-xs leading-6 text-amber-800">
                            Historical occupancy percentage is intentionally omitted
                            because historical operational-bed capacity and complete
                            legacy allocation records have not been established.
                        </p>
                    </div>
                </section>


{{-- =========================================== --}}
{{-- TAB: DIAGNOSTICS MIS --}}
{{-- =========================================== --}}

<section x-show="activeTab === 'diagnostics'"
         x-cloak
         class="space-y-5">

    <div>
        <h3 class="text-xl font-bold text-slate-900">
            Diagnostics Management Information System
        </h3>
        <p class="mt-1 text-sm text-slate-500">
            Investigation workload and current completion status
            for orders placed within the selected reporting period.
        </p>
    </div>

    {{-- Summary indicators --}}

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        @foreach([
            ['name' => 'Total Investigations', 'value' => number_format($diagnosticTotal), 'color' => 'text-indigo-700'],
            ['name' => 'Completed', 'value' => number_format($diagnosticCompleted), 'color' => 'text-emerald-700'],
            ['name' => 'In Process', 'value' => number_format($diagnosticInProcess), 'color' => 'text-amber-700'],
            ['name' => 'Ordered', 'value' => number_format($diagnosticOrdered), 'color' => 'text-blue-700'],
            ['name' => 'Other Statuses', 'value' => number_format($diagnosticOtherStatuses), 'color' => 'text-slate-700'],
            ['name' => 'Completion Rate', 'value' => $diagnosticCompletionRate !== null ? number_format($diagnosticCompletionRate, 1) . '%' : 'N/A', 'color' => 'text-violet-700'],
        ] as $metric)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    {{ $metric['name'] }}
                </p>
                <p class="mt-3 text-2xl font-bold tabular-nums {{ $metric['color'] }}">
                    {{ $metric['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- OPD / IPD origin --}}

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-600">OPD-linked Investigations</p>
            <p class="mt-3 text-3xl font-bold text-blue-700">
                {{ number_format($diagnosticOpd) }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-600">IPD-linked Investigations</p>
            <p class="mt-3 text-3xl font-bold text-emerald-700">
                {{ number_format($diagnosticIpd) }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-600">Unlinked Investigations</p>
            <p class="mt-3 text-3xl font-bold text-slate-700">
                {{ number_format($diagnosticUnlinked) }}
            </p>
        </div>
    </div>

    {{-- Category-wise performance --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Category-wise Investigation Performance
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach(['Category', 'Total', 'Completed', 'In Process', 'Ordered'] as $heading)
                            <th class="px-5 py-3 {{ $loop->first ? 'text-left' : 'text-right' }} text-xs font-bold text-slate-500">
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($diagnosticCategories as $category)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ $category['category'] }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">{{ number_format($category['total']) }}</td>
                            <td class="px-5 py-3 text-right text-sm text-emerald-700">{{ number_format($category['completed']) }}</td>
                            <td class="px-5 py-3 text-right text-sm text-amber-700">{{ number_format($category['in_process']) }}</td>
                            <td class="px-5 py-3 text-right text-sm text-blue-700">{{ number_format($category['ordered']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">
                                No investigations found for the selected reporting period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Service-wise workload --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Service-wise Investigation Workload
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">Investigation</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">Category</th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">Ordered Quantity</th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">Completed Quantity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($diagnosticServices as $service)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ $service['name'] }}
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-600">
                                {{ $service['category'] }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($service['total']) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-emerald-700">
                                {{ number_format($service['completed']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-sm text-slate-500">
                                No diagnostic services recorded in this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>



{{-- =========================================== --}}
{{-- ADVANCED DIAGNOSTICS: SAMPLE COLLECTION --}}
{{-- =========================================== --}}

<div class="border-t border-slate-200 pt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-lg font-bold text-slate-900">
                Sample Collection &amp; Turnaround Time
            </h4>
            <p class="mt-1 text-sm text-slate-500">
                Laboratory specimen collection performance for the
                selected reporting period.
            </p>
        </div>

        <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">
            Advanced Diagnostics
        </span>
    </div>
</div>

{{-- Sample collection KPI cards --}}

<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">

    @foreach([
        [
            'name' => 'Sample Required',
            'value' => number_format($sampleRequired),
            'color' => 'text-indigo-700',
        ],
        [
            'name' => 'Sample Documented',
            'value' => number_format($sampleDocumented),
            'color' => 'text-blue-700',
        ],
        [
            'name' => 'Undocumented',
            'value' => number_format($sampleUndocumented),
            'color' => 'text-amber-700',
        ],
        [
            'name' => 'Collected',
            'value' => number_format($sampleCollected),
            'color' => 'text-emerald-700',
        ],
        [
            'name' => 'Rejected',
            'value' => number_format($sampleRejected),
            'color' => 'text-rose-700',
        ],
        [
            'name' => 'Documentation Coverage',
            'value' => $sampleCoverageRate !== null
                ? number_format($sampleCoverageRate, 1) . '%'
                : 'N/A',
            'color' => 'text-violet-700',
        ],
    ] as $metric)

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['name'] }}
            </p>

            <p class="mt-3 text-2xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>

    @endforeach
</div>

{{-- Collection turnaround time --}}

<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-sm font-bold text-slate-900">
            Average Order-to-Collection Time
        </h4>

        <p class="mt-4 text-3xl font-bold tabular-nums text-indigo-700">
            {{ $sampleAverageCollectionTat !== null
                ? number_format($sampleAverageCollectionTat, 1) . ' min'
                : 'N/A' }}
        </p>

        <p class="mt-3 text-xs leading-5 text-slate-500">
            Calculated from {{ number_format($sampleTatRecords) }}
            sample records with valid order and collection timestamps.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-sm font-bold text-slate-900">
            Sample Documentation Coverage
        </h4>

        <div class="mt-4 flex items-center justify-between gap-3">
            <p class="text-3xl font-bold tabular-nums text-emerald-700">
                {{ $sampleCoverageRate !== null
                    ? number_format($sampleCoverageRate, 1) . '%'
                    : 'N/A' }}
            </p>

            <span class="text-xs text-slate-500">
                {{ $sampleDocumented }} of {{ $sampleRequired }}
            </span>
        </div>

        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
            <div
                class="h-full rounded-full bg-emerald-600"
                style="width: {{ min(100, max(0, $sampleCoverageRate ?? 0)) }}%"
            ></div>
        </div>

        <p class="mt-3 text-xs text-slate-500">
            Percentage of sample-required investigation items
            with an associated sample record.
        </p>
    </div>

</div>

{{-- Specimen-wise collection performance --}}

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Specimen-wise Sample Collection Performance
        </h4>

        <p class="mt-1 text-xs text-slate-500">
            Collection documentation by specimen type.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">

            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Specimen
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Required
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Documented
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Collected
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Rejected
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">

                @forelse($specimenPerformance as $specimen)

                    <tr class="hover:bg-slate-50">

                        <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                            {{ $specimen['specimen'] }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-slate-700">
                            {{ number_format($specimen['required']) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-blue-700">
                            {{ number_format($specimen['documented']) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-emerald-700">
                            {{ number_format($specimen['collected']) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-rose-700">
                            {{ number_format($specimen['rejected']) }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5"
                            class="px-5 py-8 text-center text-sm text-slate-500">
                            No sample-required laboratory investigations
                            found for this period.
                        </td>
                    </tr>

                @endforelse

            </tbody>
        </table>
    </div>
</div>

{{-- =========================================== --}}
{{-- ADVANCED DIAGNOSTICS: LABORATORY REPORT TAT --}}
{{-- =========================================== --}}

<div class="border-t border-slate-200 pt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-lg font-bold text-slate-900">
                Laboratory Report Turnaround Time
            </h4>
            <p class="mt-1 text-sm text-slate-500">
                Collection-to-result-entry performance for laboratory
                investigations ordered within the selected reporting period.
            </p>
        </div>

        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
            Provisional TAT Data
        </span>
    </div>
</div>

{{-- Result reporting indicators --}}

<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
    @foreach([
        [
            'name' => 'Result Records',
            'value' => number_format($labResultCount),
            'color' => 'text-indigo-700',
        ],
        [
            'name' => 'Finalized Results',
            'value' => number_format($labFinalizedCount),
            'color' => 'text-emerald-700',
        ],
        [
            'name' => 'Results Entered',
            'value' => number_format($labEnteredCount),
            'color' => 'text-blue-700',
        ],
        [
            'name' => 'Verification Timestamps',
            'value' => number_format($labVerifiedTimestampCount),
            'color' => 'text-violet-700',
        ],
        [
            'name' => 'Measurable TAT',
            'value' => number_format($labTatMeasuredCount),
            'color' => 'text-sky-700',
        ],
        [
            'name' => 'TAT Not Measurable',
            'value' => number_format($labTatMissingCount),
            'color' => 'text-amber-700',
        ],
    ] as $metric)

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['name'] }}
            </p>

            <p class="mt-3 text-2xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- TAT statistics --}}

<div class="grid grid-cols-1 gap-5 md:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold text-slate-600">
            Average Collection-to-Entry TAT
        </p>
        <p class="mt-4 text-3xl font-bold tabular-nums text-indigo-700">
            {{ $labAverageTatMinutes !== null
                ? number_format($labAverageTatMinutes, 2) . ' min'
                : 'N/A' }}
        </p>
        <p class="mt-2 text-xs text-slate-500">
            Based on {{ number_format($labTatMeasuredCount) }}
            valid timestamp pairs.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold text-slate-600">
            Minimum Recorded TAT
        </p>
        <p class="mt-4 text-3xl font-bold tabular-nums text-emerald-700">
            {{ $labMinimumTatMinutes !== null
                ? number_format($labMinimumTatMinutes, 2) . ' min'
                : 'N/A' }}
        </p>
        <p class="mt-2 text-xs text-slate-500">
            Shortest valid collection-to-entry interval.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold text-slate-600">
            Maximum Recorded TAT
        </p>
        <p class="mt-4 text-3xl font-bold tabular-nums text-rose-700">
            {{ $labMaximumTatMinutes !== null
                ? number_format($labMaximumTatMinutes, 2) . ' min'
                : 'N/A' }}
        </p>
        <p class="mt-2 text-xs text-slate-500">
            Longest valid collection-to-entry interval.
        </p>
    </div>
</div>

{{-- TAT measurement coverage --}}

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-sm font-bold text-slate-900">
                Laboratory TAT Measurement Coverage
            </h4>
            <p class="mt-1 text-xs text-slate-500">
                Result records with valid collection and result-entry timestamps.
            </p>
        </div>

        <p class="text-2xl font-bold tabular-nums text-indigo-700">
            {{ $labTatCoverageRate !== null
                ? number_format($labTatCoverageRate, 1) . '%'
                : 'N/A' }}
        </p>
    </div>

    <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
        <div
            class="h-full rounded-full bg-indigo-600"
            style="width: {{ min(100, max(0, $labTatCoverageRate ?? 0)) }}%"
        ></div>
    </div>

    <p class="mt-3 text-xs text-slate-500">
        {{ number_format($labTatMeasuredCount) }} measurable
        of {{ number_format($labResultCount) }} laboratory result records.
    </p>
</div>

<div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
    <h4 class="text-sm font-bold text-amber-900">
        Laboratory TAT — Data Quality Notice
    </h4>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        TAT measures the elapsed time from recorded sample collection
        to result entry, not necessarily the time to report release.
        Results without valid timestamp pairs are excluded from
        TAT statistics.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        Preliminary records show exceptionally short intervals.
        These figures should not be treated as validated laboratory
        processing times until timestamp recording practices have
        been reviewed. A result marked final is not automatically
        considered formally verified when verified_at is absent.
    </p>
</div>

<div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
    <h4 class="text-sm font-bold text-amber-900">
        Sample Collection Reporting Notes
    </h4>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        Sample-required investigations are identified using the
        requires_sample field. Missing sample records are classified
        as undocumented, not definitively uncollected.
        Collection turnaround time measures the interval between
        investigation ordering and the recorded sample collection.
        It does not represent laboratory report turnaround time.
    </p>
</div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
        <h4 class="text-sm font-bold text-amber-900">
            Reporting Methodology
        </h4>
        <p class="mt-2 text-xs leading-6 text-amber-800">
            Investigations are selected by the parent service order's
            ordered_at timestamp. Statuses reflect their current workflow
            status, not necessarily their status at the end of the
            reporting period. Only recorded ERP diagnostic activity is
            included. An absent category does not imply zero hospital-wide
            activity.
        </p>
    </div>

</section>


{{-- =========================================== --}}
{{-- TAB: FINANCE MIS --}}
{{-- =========================================== --}}


<section
    x-show="activeTab === 'finance'"
    x-cloak
    x-data="{ financeTab: 'overview' }"
    class="space-y-5"
>
    <!-- Finance MIS secondary navigation -->
    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <nav aria-label="Finance MIS report sections"
             class="flex flex-wrap gap-2">

            <button
                type="button"
                @click="financeTab = 'overview'"
                :class="financeTab === 'overview'
                    ? 'bg-indigo-700 text-white'
                    : 'bg-slate-50 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700'"
                class="rounded-lg px-4 py-2.5 text-sm font-semibold transition"
            >
                Overview
            </button>

            <button
                type="button"
                @click="financeTab = 'collections'"
                :class="financeTab === 'collections'
                    ? 'bg-indigo-700 text-white'
                    : 'bg-slate-50 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700'"
                class="rounded-lg px-4 py-2.5 text-sm font-semibold transition"
            >
                Collections
            </button>

            <button
                type="button"
                @click="financeTab = 'receivables'"
                :class="financeTab === 'receivables'
                    ? 'bg-indigo-700 text-white'
                    : 'bg-slate-50 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700'"
                class="rounded-lg px-4 py-2.5 text-sm font-semibold transition"
            >
                Receivables
            </button>

            <button
                type="button"
                @click="financeTab = 'mhis'"
                :class="financeTab === 'mhis'
                    ? 'bg-indigo-700 text-white'
                    : 'bg-slate-50 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700'"
                class="rounded-lg px-4 py-2.5 text-sm font-semibold transition"
            >
                MHIS
            </button>

            <button
                type="button"
                @click="financeTab = 'welfare'"
                :class="financeTab === 'welfare'
                    ? 'bg-indigo-700 text-white'
                    : 'bg-slate-50 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700'"
                class="rounded-lg px-4 py-2.5 text-sm font-semibold transition"
            >
                Charity &amp; Staff
            </button>

        </nav>
    </div>


    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="text-xl font-bold text-slate-900">
                Finance Management Information System
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                Accrual-based financial performance from posted ERP
                General Ledger journals for the selected reporting period.
            </p>
        </div>

        <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
            Provisional ERP Financial Report
        </span>
    </div>
<div x-show="financeTab === 'overview'" x-cloak class="space-y-5">
    {{-- Executive financial indicators --}}

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Recognized Revenue
            </p>
            <p class="mt-4 text-3xl font-bold tabular-nums text-emerald-700">
                ₹{{ number_format($financeRecognizedRevenue, 2) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">
                Income account credits less debits
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Recorded Expenditure
            </p>
            <p class="mt-4 text-3xl font-bold tabular-nums text-rose-700">
                ₹{{ number_format($financeRecordedExpenditure, 2) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">
                Expense account debits less credits
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Provisional Surplus / Deficit
            </p>
            <p class="mt-4 text-3xl font-bold tabular-nums
                {{ $financeProvisionalSurplus >= 0
                    ? 'text-indigo-700'
                    : 'text-rose-700' }}">
                ₹{{ number_format($financeProvisionalSurplus, 2) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">
                Recognized revenue less recorded expenditure
            </p>
        </div>
    </div>

    {{-- Revenue attribution --}}

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-600">
                Department-assigned Revenue
            </p>
            <p class="mt-3 text-2xl font-bold text-emerald-700">
                ₹{{ number_format($financeAssignedRevenue, 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-600">
                Unassigned Revenue
            </p>
            <p class="mt-3 text-2xl font-bold text-amber-700">
                ₹{{ number_format($financeUnassignedRevenue, 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-600">
                Revenue Attribution Rate
            </p>
            <p class="mt-3 text-2xl font-bold text-indigo-700">
                {{ $financeAttributionRate !== null
                    ? number_format($financeAttributionRate, 1) . '%'
                    : 'N/A' }}
            </p>
        </div>
    </div>

    {{-- Attribution progress --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <h4 class="font-bold text-slate-900">
                Department Revenue Attribution
            </h4>
            <span class="text-sm font-semibold text-indigo-700">
                {{ $financeAttributionRate !== null
                    ? number_format($financeAttributionRate, 1) . '%'
                    : 'N/A' }}
            </span>
        </div>

        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
            <div
                class="h-full rounded-full bg-emerald-600"
                style="width: {{ min(100, max(0, $financeAttributionRate ?? 0)) }}%"
            ></div>
        </div>

        <p class="mt-3 text-xs leading-5 text-slate-500">
            Percentage of net recognized revenue assigned to a
            department through General Ledger journal lines.
            Unassigned revenue is shown separately and is not
            allocated by estimation.
        </p>
    </div>

    {{-- Department-wise revenue --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Department-wise Recognized Revenue
            </h4>
            <p class="mt-1 text-xs text-slate-500">
                Net income account credits less debits by department
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                            Department
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Journal Lines
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Recognized Revenue
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($financeDepartmentRevenue as $department)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-sm font-semibold
                                {{ $department->department_id === null
                                    ? 'text-amber-700'
                                    : 'text-slate-800' }}">
                                {{ $department->department_name }}
                            </td>

                            <td class="px-5 py-3 text-right text-sm text-slate-700">
                                {{ number_format($department->journal_lines) }}
                            </td>

                            <td class="px-5 py-3 text-right text-sm font-semibold tabular-nums text-slate-900">
                                ₹{{ number_format($department->recognized_revenue, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"
                                class="px-5 py-8 text-center text-sm text-slate-500">
                                No posted income journal lines found
                                for the selected reporting period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Journal integrity --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="font-bold text-slate-900">
            General Ledger Posting &amp; Reconciliation
        </h4>

        <div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach([
                [
                    'label' => 'Posted Journals',
                    'value' => number_format($financePostedJournals),
                ],
                [
                    'label' => 'Total Debits',
                    'value' => '₹' . number_format($financeTotalDebits, 2),
                ],
                [
                    'label' => 'Total Credits',
                    'value' => '₹' . number_format($financeTotalCredits, 2),
                ],
                [
                    'label' => 'Debit–Credit Difference',
                    'value' => '₹' . number_format($financeJournalDifference, 2),
                ],
            ] as $metric)
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold text-slate-500">
                        {{ $metric['label'] }}
                    </p>
                    <p class="mt-3 text-xl font-bold tabular-nums text-slate-900">
                        {{ $metric['value'] }}
                    </p>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-xs text-slate-500">
            A zero debit–credit difference confirms aggregate balance
            for the selected posted journals. It does not establish
            that each journal or the underlying transactions have
            been independently reconciled.
        </p>
    </div>

    {{-- GL coverage --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="font-bold text-slate-900">
            General Ledger Reporting Coverage
        </h4>

        <div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div>
                <p class="text-xs text-slate-500">Income Accounts</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ number_format($financeIncomeAccountCount) }}
                </p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Expense Accounts</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ number_format($financeExpenseAccountCount) }}
                </p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Income Journal Lines</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ number_format($financeIncomeLines) }}
                </p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Department-assigned Income Lines</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">
                    {{ number_format($financeAssignedIncomeLines) }}
                </p>
            </div>
        </div>
    </div>

    </div>

<div x-show="financeTab === 'collections'" x-cloak class="space-y-5">

{{-- =========================================== --}}
{{-- FINANCE MIS: COLLECTIONS & RECONCILIATION --}}
{{-- =========================================== --}}

<div class="border-t border-slate-200 pt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-lg font-bold text-slate-900">
                Cash Collections &amp; Reconciliation
            </h4>
            <p class="mt-1 text-sm text-slate-500">
                Posted Finance receipt vouchers within the selected
                reporting period.
            </p>
        </div>

        <span class="rounded-full bg-indigo-50 px-3 py-1.5
                     text-xs font-semibold text-indigo-700">
            Finance Voucher Reporting
        </span>
    </div>
</div>

{{-- Receipt summary --}}

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
    @foreach([
        [
            'name' => 'Posted Receipts',
            'value' => number_format($financeReceiptCount),
            'color' => 'text-indigo-700',
        ],
        [
            'name' => 'Receipt Amount',
            'value' => '₹' . number_format($financeReceiptTotal, 2),
            'color' => 'text-emerald-700',
        ],
        [
            'name' => 'Origin Unspecified',
            'value' => '₹' . number_format($financeUnspecifiedReceiptTotal, 2),
            'color' => 'text-amber-700',
        ],
        [
            'name' => 'GL-linked Receipts',
            'value' => number_format($financeLinkedReceiptCount),
            'color' => 'text-blue-700',
        ],
        [
            'name' => 'Unlinked Receipts',
            'value' => number_format($financeUnlinkedReceiptCount),
            'color' => 'text-rose-700',
        ],
        [
            'name' => 'GL Linkage Rate',
            'value' => $financeReceiptLinkageRate !== null
                ? number_format($financeReceiptLinkageRate, 1) . '%'
                : 'N/A',
            'color' => 'text-violet-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white
                    p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['name'] }}
            </p>

            <p class="mt-3 text-xl font-bold tabular-nums
                      {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- Account and payment-mode analysis --}}

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

    <div class="overflow-hidden rounded-2xl border
                border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Account-wise Receipt Collections
            </h4>
            <p class="mt-1 text-xs text-slate-500">
                Finance account credited by each posted receipt
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs
                                   font-bold text-slate-500">
                            Account
                        </th>
                        <th class="px-5 py-3 text-right text-xs
                                   font-bold text-slate-500">
                            Vouchers
                        </th>
                        <th class="px-5 py-3 text-right text-xs
                                   font-bold text-slate-500">
                            Amount
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($financeReceiptAccounts as $account)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-sm font-semibold
                                       text-slate-800">
                                {{ $account->account_name }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($account->voucher_count) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm
                                       font-semibold text-slate-900">
                                ₹{{ number_format($account->total_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center
                                                  text-sm text-slate-500">
                                No posted receipt vouchers in this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border
                border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Payment-mode Distribution
            </h4>
            <p class="mt-1 text-xs text-slate-500">
                Normalized payment modes from posted receipts
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs
                                   font-bold text-slate-500">
                            Payment Mode
                        </th>
                        <th class="px-5 py-3 text-right text-xs
                                   font-bold text-slate-500">
                            Vouchers
                        </th>
                        <th class="px-5 py-3 text-right text-xs
                                   font-bold text-slate-500">
                            Amount
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($financeReceiptModes as $mode)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-sm font-semibold
                                       text-slate-800">
                                {{ ucfirst($mode->payment_mode) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($mode->voucher_count) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm
                                       font-semibold text-slate-900">
                                ₹{{ number_format($mode->total_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center
                                                  text-sm text-slate-500">
                                No payment-mode data for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Receipt origins --}}

<div class="overflow-hidden rounded-2xl border
            border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Receipt Origin Analysis
        </h4>
        <p class="mt-1 text-xs text-slate-500">
            Identifies manual, imported and unspecified receipt origins
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs
                               font-bold text-slate-500">
                        Origin
                    </th>
                    <th class="px-5 py-3 text-right text-xs
                               font-bold text-slate-500">
                        Vouchers
                    </th>
                    <th class="px-5 py-3 text-right text-xs
                               font-bold text-slate-500">
                        Amount
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($financeReceiptOrigins as $origin)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-semibold
                            {{ $origin->receipt_origin === 'unspecified'
                                ? 'text-amber-700'
                                : 'text-slate-800' }}">
                            {{ $origin->receipt_origin }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm">
                            {{ number_format($origin->voucher_count) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm
                                   font-semibold text-slate-900">
                            ₹{{ number_format($origin->total_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-8 text-center
                                              text-sm text-slate-500">
                            No receipt-origin records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- GL reconciliation summary --}}

<div class="rounded-2xl border border-slate-200
            bg-white p-6 shadow-sm">
    <h4 class="font-bold text-slate-900">
        Receipt-to-General Ledger Linkage
    </h4>

    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-emerald-50 p-4">
            <p class="text-xs font-semibold text-emerald-800">
                Linked Receipt Amount
            </p>
            <p class="mt-3 text-2xl font-bold tabular-nums
                      text-emerald-700">
                ₹{{ number_format($financeLinkedReceiptAmount, 2) }}
            </p>
        </div>

        <div class="rounded-xl bg-rose-50 p-4">
            <p class="text-xs font-semibold text-rose-800">
                Unlinked Receipt Amount
            </p>
            <p class="mt-3 text-2xl font-bold tabular-nums
                      text-rose-700">
                ₹{{ number_format($financeUnlinkedReceiptAmount, 2) }}
            </p>
        </div>

        <div class="rounded-xl bg-indigo-50 p-4">
            <p class="text-xs font-semibold text-indigo-800">
                Direct GL Linkage Coverage
            </p>
            <p class="mt-3 text-2xl font-bold tabular-nums
                      text-indigo-700">
                {{ $financeReceiptLinkageRate !== null
                    ? number_format($financeReceiptLinkageRate, 1) . '%'
                    : 'N/A' }}
            </p>
        </div>
    </div>

    <p class="mt-4 text-xs leading-6 text-slate-500">
        Linkage is established only when a posted journal entry
        directly references the Finance receipt voucher through
        finance_voucher_id. Unlinked does not conclusively mean
        unaccounted or unreconciled in Tally.
    </p>
</div>

<div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
    <h4 class="text-sm font-bold text-amber-900">
        Collections Reporting — Important Limitations
    </h4>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        This section includes posted Finance receipt vouchers only.
        It does not establish total hospital collections because
        OPD, IPD, Pharmacy and MHIS payment records may be stored
        in their respective billing modules.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        Unspecified origins and receipts without directly linked
        posted journals require review. The report does not
        automatically create accounting entries, reconcile Tally,
        or classify receipt vouchers as recognized revenue.
    </p>
</div>

</div>

<div x-show="financeTab === 'receivables'" x-cloak class="space-y-5">

{{-- =============================================== --}}
{{-- FINANCE MIS: PATIENT RECEIVABLES & ADVANCES --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-lg font-bold text-slate-900">
                Patient Receivables &amp; Advances
            </h4>
            <p class="mt-1 text-sm text-slate-500">
                Inpatient billing balances, posted General Ledger
                receivables and patient advance liabilities.
            </p>
        </div>

        <span class="rounded-full bg-indigo-50 px-3 py-1.5
                     text-xs font-semibold text-indigo-700">
            Finance MIS — Receivables
        </span>
    </div>
</div>

{{-- Patient receivable summary --}}

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
    @foreach([
        [
            'name' => 'Finalized IPD Accounts',
            'value' => number_format($ipdFinalizedAccountCount),
            'color' => 'text-indigo-700',
        ],
        [
            'name' => 'Finalized Bill Value',
            'value' => '₹' . number_format($ipdFinalizedBillTotal, 2),
            'color' => 'text-blue-700',
        ],
        [
            'name' => 'IPD Stored Outstanding',
            'value' => '₹' . number_format($ipdStoredOutstanding, 2),
            'color' => 'text-amber-700',
        ],
        [
            'name' => 'GL Patient Receivables',
            'value' => '₹' . number_format($glPatientReceivables, 2),
            'color' => 'text-emerald-700',
        ],
        [
            'name' => 'GL Advance Liability',
            'value' => '₹' . number_format($glPatientAdvanceLiability, 2),
            'color' => 'text-violet-700',
        ],
        [
            'name' => 'GL – Billing Difference',
            'value' => '₹' . number_format($patientReceivableDifference, 2),
            'color' => 'text-rose-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['name'] }}
            </p>
            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- Patient versus MHIS operational balances --}}

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-sm font-bold text-slate-900">
            Self-Paying IPD Outstanding
        </h4>
        <p class="mt-4 text-3xl font-bold text-amber-700">
            ₹{{ number_format($ipdSelfPayOutstanding, 2) }}
        </p>
        <p class="mt-2 text-xs leading-5 text-slate-500">
            Current stored billing balances for finalized
            accounts without an MHIS category.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-sm font-bold text-slate-900">
            MHIS-category IPD Billing Balance
        </h4>
        <p class="mt-4 text-3xl font-bold text-indigo-700">
            ₹{{ number_format($ipdMhisAccountOutstanding, 2) }}
        </p>
        <p class="mt-2 text-xs leading-5 text-slate-500">
            Current stored balance on MHIS-category billing accounts.
            This is not the same as MHIS claims outstanding.
        </p>
    </div>
</div>

{{-- Advance reporting --}}

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Patient Advance Transactions
        </h4>
        <p class="mt-1 text-xs text-slate-500">
            Advance receipts and posted advance applications
            during the selected reporting period.
        </p>
    </div>

    <div class="grid grid-cols-2 gap-4 p-5 md:grid-cols-4">
        <div class="rounded-xl bg-blue-50 p-4">
            <p class="text-xs font-semibold text-blue-800">
                Advance Receipts
            </p>
            <p class="mt-3 text-2xl font-bold text-blue-700">
                {{ number_format($patientAdvanceReceiptCount) }}
            </p>
        </div>

        <div class="rounded-xl bg-emerald-50 p-4">
            <p class="text-xs font-semibold text-emerald-800">
                Advances Received
            </p>
            <p class="mt-3 text-2xl font-bold text-emerald-700">
                ₹{{ number_format($patientAdvancesReceived, 2) }}
            </p>
        </div>

        <div class="rounded-xl bg-indigo-50 p-4">
            <p class="text-xs font-semibold text-indigo-800">
                Application Journals
            </p>
            <p class="mt-3 text-2xl font-bold text-indigo-700">
                {{ number_format($patientAdvanceApplicationCount) }}
            </p>
        </div>

        <div class="rounded-xl bg-violet-50 p-4">
            <p class="text-xs font-semibold text-violet-800">
                Advances Applied
            </p>
            <p class="mt-3 text-2xl font-bold text-violet-700">
                ₹{{ number_format($patientAdvancesApplied, 2) }}
            </p>
        </div>
    </div>

    <p class="border-t border-slate-100 px-5 py-4 text-xs leading-6 text-slate-500">
        Advance applications reduce previously recorded deposits
        and patient receivables. They are not new cash collections.
        The net posted liability is shown in the GL Advance Liability card.
    </p>
</div>

</div>

<div x-show="financeTab === 'mhis'" x-cloak class="space-y-5">

{{-- MHIS claim indicators --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        MHIS Claims &amp; Reimbursement Position
    </h4>
    <p class="mt-1 text-sm text-slate-500">
        Current claim records, reimbursement receipts through the
        selected end date, and provisional outstanding amounts.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
    @foreach([
        [
            'name' => 'MHIS Claims',
            'value' => number_format($mhisClaimCount),
            'color' => 'text-indigo-700',
        ],
        [
            'name' => 'Total Claimed',
            'value' => '₹' . number_format($mhisTotalClaimed, 2),
            'color' => 'text-blue-700',
        ],
        [
            'name' => 'Total Expected',
            'value' => '₹' . number_format($mhisTotalExpected, 2),
            'color' => 'text-sky-700',
        ],
        [
            'name' => 'Total Approved',
            'value' => '₹' . number_format($mhisTotalApproved, 2),
            'color' => 'text-emerald-700',
        ],
        [
            'name' => 'MHIS Received',
            'value' => '₹' . number_format($mhisAmountReceived, 2),
            'color' => 'text-violet-700',
        ],
        [
            'name' => 'Approved Outstanding',
            'value' => '₹' . number_format($mhisApprovedOutstanding, 2),
            'color' => 'text-amber-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['name'] }}
            </p>
            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-bold text-slate-900">
        MHIS Submitted — Pending Approval
    </p>
    <p class="mt-3 text-3xl font-bold text-amber-700">
        ₹{{ number_format($mhisPendingApprovalExpected, 2) }}
    </p>
    <p class="mt-2 text-xs leading-5 text-slate-500">
        Expected reimbursement on submitted claims with no recorded
        approved amount. This is not confirmed MHIS receivable income.
    </p>
</div>

{{-- MHIS claims by status --}}

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            MHIS Claim Status Summary
        </h4>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Claim Status
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Claims
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Expected Amount
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Approved Amount
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($mhisClaimsByStatus as $claim)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                            {{ ucfirst($claim->status ?? 'Unknown') }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm">
                            {{ number_format($claim->claim_count) }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm">
                            ₹{{ number_format($claim->expected_amount, 2) }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm">
                            ₹{{ number_format($claim->approved_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-8 text-center text-sm text-slate-500">
                            No MHIS claims recorded.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- =============================================== --}}
{{-- FINANCE MIS: PATIENT VS MHIS RECONCILIATION --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-lg font-bold text-slate-900">
                Patient vs MHIS Receivable Reconciliation
            </h4>

            <p class="mt-1 text-sm text-slate-500">
                Reconciliation of recorded patient-payable balances
                against posted General Ledger receivables.
            </p>
        </div>

        <span class="rounded-full bg-amber-50 px-3 py-1.5
                     text-xs font-semibold text-amber-700">
            Provisional Reconciliation
        </span>
    </div>
</div>

{{-- Reconciliation indicators --}}

<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
            GL Patient Receivables
        </p>

        <p class="mt-4 text-2xl font-bold tabular-nums text-indigo-700">
            ₹{{ number_format($glPatientReceivables, 2) }}
        </p>

        <p class="mt-2 text-xs text-slate-500">
            Posted GL account 1200 through the selected end date.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
            Stored Patient Outstanding
        </p>

        <p class="mt-4 text-2xl font-bold tabular-nums text-amber-700">
            ₹{{ number_format($ipdStoredOutstanding, 2) }}
        </p>

        <p class="mt-2 text-xs text-slate-500">
            Current finalized IPD billing account balances.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
            Identified Pending MHIS Exposure
        </p>

        <p class="mt-4 text-2xl font-bold tabular-nums text-blue-700">
            ₹{{ number_format($mhisPendingReceivableExposure, 2) }}
        </p>

        <p class="mt-2 text-xs text-slate-500">
            GL exposure associated with submitted, unapproved MHIS claims.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
            Remaining Reconciliation Difference
        </p>

        <p class="mt-4 text-2xl font-bold tabular-nums
            {{ abs($patientUnexplainedReconciliationDifference) < 0.01
                ? 'text-emerald-700'
                : 'text-rose-700' }}">
            ₹{{ number_format($patientUnexplainedReconciliationDifference, 2) }}
        </p>

        <p class="mt-2 text-xs text-slate-500">
            GL-to-billing difference less identified pending MHIS exposure.
        </p>
    </div>

</div>

{{-- Pending MHIS exposure details --}}

<div class="overflow-hidden rounded-2xl border border-slate-200
            bg-white shadow-sm">

    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Pending MHIS Receivable Exposure by Billing Account
        </h4>

        <p class="mt-1 text-xs text-slate-500">
            Submitted, unapproved claims and associated
            posted patient receivables.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">

            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Billing Account
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Expected MHIS
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        GL Receivable
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Patient Balance
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Pending Exposure
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">

                @forelse($mhisPendingReceivableDetails as $account)

                    <tr class="hover:bg-slate-50">

                        <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                            {{ $account['account_no'] }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-slate-700">
                            ₹{{ number_format($account['expected_amount'], 2) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-slate-700">
                            ₹{{ number_format($account['gl_receivable'], 2) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-slate-700">
                            ₹{{ number_format($account['billing_balance'], 2) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm
                                   font-bold text-blue-700">
                            ₹{{ number_format($account['pending_exposure'], 2) }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5"
                            class="px-5 py-8 text-center text-sm text-slate-500">
                            No submitted, unapproved MHIS claims found.
                        </td>
                    </tr>

                @endforelse

            </tbody>
        </table>
    </div>
</div>

{{-- Reconciliation methodology --}}

<div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

    <h4 class="text-sm font-bold text-amber-900">
        Receivable Reconciliation — Interpretation
    </h4>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        The identified pending MHIS exposure represents differences
        between posted patient receivable journals and the current
        patient-payable balances of billing accounts with submitted,
        unapproved MHIS claims.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        It is not a confirmed MHIS reimbursement and should not
        be treated as an approved MHIS receivable. Expected
        reimbursement may differ from the amount ultimately approved.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        This calculation is provisional because the current billing
        snapshots and date-filtered GL balances may cover different
        points in time. A zero remaining difference does not
        establish that every account or underlying journal has
        been independently reconciled.
    </p>

</div>

{{-- Reconciliation and data scope notice --}}

<div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
    <h4 class="text-sm font-bold text-amber-900">
        Patient Receivables — Reconciliation &amp; Reporting Notes
    </h4>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        IPD finalized bill totals and stored balances are current
        operational snapshots. GL patient receivables and advance
        liabilities are posted journal balances calculated through
        {{ \Carbon\Carbon::parse($receivablesAsOfDate)->format('d M Y') }}.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        The GL-to-billing difference is a preliminary comparison
        of datasets with potentially different scopes and dates.
        It is not automatically an accounting error.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        MHIS claimed, expected, approved, settled and received
        amounts represent different workflow stages. Current
        claim balances do not yet incorporate every possible
        adjustment or historical approval event.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        Submitted but unapproved claims are reported separately
        from approved outstanding reimbursement. Neither should
        be added automatically to patient receivables.
    </p>
</div>

</div>

<div x-show="financeTab === 'welfare'" x-cloak class="space-y-5">

{{-- =============================================== --}}
{{-- FINANCE MIS: CHARITY & STAFF MEDICAL BENEFITS --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="text-lg font-bold text-slate-900">
                Charity Assistance &amp; Staff Medical Benefits
            </h4>
            <p class="mt-1 text-sm text-slate-500">
                Patient financial assistance and employee medical
                benefit utilization.
            </p>
        </div>

        <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">
            Welfare &amp; Benefits MIS
        </span>
    </div>
</div>

{{-- =============================================== --}}
{{-- CHARITY ASSISTANCE --}}
{{-- =============================================== --}}

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h4 class="text-base font-bold text-slate-900">
        Charity &amp; Patient Assistance
    </h4>
    <p class="mt-1 text-xs text-slate-500">
        Requests, approvals and assistance applied within the selected period.
    </p>

    <div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
        @foreach([
            [
                'label' => 'Requests',
                'value' => number_format($charityRequestCount),
                'color' => 'text-indigo-700',
            ],
            [
                'label' => 'Amount Requested',
                'value' => '₹' . number_format($charityRequestedAmount, 2),
                'color' => 'text-blue-700',
            ],
            [
                'label' => 'Approved Assistance',
                'value' => '₹' . number_format($charityApprovedAmount, 2),
                'color' => 'text-emerald-700',
            ],
            [
                'label' => 'Applied Assistance',
                'value' => '₹' . number_format($charityAppliedAmount, 2),
                'color' => 'text-violet-700',
            ],
            [
                'label' => 'Patients Assisted',
                'value' => number_format($charityPatientsAssisted),
                'color' => 'text-sky-700',
            ],
        ] as $metric)
            <div class="rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-semibold text-slate-500">
                    {{ $metric['label'] }}
                </p>
                <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                    {{ $metric['value'] }}
                </p>
            </div>
        @endforeach
    </div>
</div>

{{-- Current approval backlog --}}

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-semibold text-slate-600">
            Pending Charity Requests
        </p>
        <p class="mt-3 text-3xl font-bold text-amber-700">
            {{ number_format($charityPendingCount) }}
        </p>
        <p class="mt-2 text-xs text-slate-500">
            Current requests awaiting approval.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-semibold text-slate-600">
            Approved — Not Applied
        </p>
        <p class="mt-3 text-3xl font-bold text-blue-700">
            {{ number_format($charityApprovedNotAppliedCount) }}
        </p>
        <p class="mt-2 text-xs text-slate-500">
            Currently approved requests awaiting application.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-semibold text-slate-600">
            Approved — Unapplied Amount
        </p>
        <p class="mt-3 text-3xl font-bold text-indigo-700">
            ₹{{ number_format($charityApprovedNotAppliedAmount, 2) }}
        </p>
        <p class="mt-2 text-xs text-slate-500">
            Approved assistance not yet applied to billing.
        </p>
    </div>
</div>

{{-- OPD and IPD charity distribution --}}

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Charity Assistance by Billing Source
        </h4>
        <p class="mt-1 text-xs text-slate-500">
            Applied charity assistance by source during the reporting period.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Billing Source
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Adjustments
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Applied Amount
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($charityBySource as $source)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                            {{ $source->source_name }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm">
                            {{ number_format($source->adjustments) }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm font-semibold">
                            ₹{{ number_format($source->applied_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3"
                            class="px-5 py-8 text-center text-sm text-slate-500">
                            No applied charity assistance recorded for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- =============================================== --}}
{{-- STAFF MEDICAL BENEFIT UTILIZATION --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        Staff Medical Benefit Utilization
    </h4>
    <p class="mt-1 text-sm text-slate-500">
        Employee and dependent medical benefits utilized during
        the selected reporting period, net of reversals.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
    @foreach([
        [
            'label' => 'Utilization Transactions',
            'value' => number_format($staffBenefitUtilizationCount),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'Gross Utilization',
            'value' => '₹' . number_format($staffBenefitUtilizedAmount, 2),
            'color' => 'text-blue-700',
        ],
        [
            'label' => 'Reversal Transactions',
            'value' => number_format($staffBenefitReversalCount),
            'color' => 'text-amber-700',
        ],
        [
            'label' => 'Reversed Amount',
            'value' => '₹' . number_format($staffBenefitReversedAmount, 2),
            'color' => 'text-rose-700',
        ],
        [
            'label' => 'Net Benefit Utilized',
            'value' => '₹' . number_format($staffBenefitNetUtilization, 2),
            'color' => 'text-emerald-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['label'] }}
            </p>
            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- Employee vs dependent utilization --}}

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Employee vs Dependent Benefit Utilization
        </h4>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Beneficiary Type
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Utilizations
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Net Utilized
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($staffBenefitByBeneficiary as $beneficiary)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                            {{ ucfirst($beneficiary->beneficiary_type ?? 'Unknown') }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm">
                            {{ number_format($beneficiary->utilization_count) }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm font-semibold">
                            ₹{{ number_format($beneficiary->net_utilization, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3"
                            class="px-5 py-8 text-center text-sm text-slate-500">
                            No staff medical benefit transactions recorded for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- =============================================== --}}
{{-- ANNUAL STAFF MEDICAL BENEFIT ENTITLEMENTS --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        Staff Medical Benefit Entitlement Position
    </h4>
    <p class="mt-1 text-sm text-slate-500">
        Financial-year entitlements, cumulative utilization and
        remaining limits for accounts eligible on
        {{ \Carbon\Carbon::parse($to)->format('d M Y') }}.
    </p>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-semibold text-slate-600">
        Active Eligible Benefit Accounts
    </p>
    <p class="mt-3 text-3xl font-bold text-indigo-700">
        {{ number_format($staffEntitlementAccountCount) }}
    </p>
</div>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="font-bold text-slate-900">
            Employee Medical Benefit Pool
        </h4>

        <div class="mt-5 space-y-4">
            <div class="flex justify-between gap-3 text-sm">
                <span class="text-slate-500">Authorized entitlement</span>
                <span class="font-bold text-slate-900">
                    ₹{{ number_format($staffEmployeeEntitlement, 2) }}
                </span>
            </div>

            <div class="flex justify-between gap-3 text-sm">
                <span class="text-slate-500">Cumulative net utilization</span>
                <span class="font-bold text-amber-700">
                    ₹{{ number_format($staffEmployeeCumulativeUsed, 2) }}
                </span>
            </div>

            <div class="flex justify-between gap-3 border-t border-slate-100 pt-4 text-sm">
                <span class="font-semibold text-slate-700">Remaining entitlement</span>
                <span class="font-bold text-emerald-700">
                    ₹{{ number_format($staffEmployeeRemaining, 2) }}
                </span>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="font-bold text-slate-900">
            Dependent Family Medical Benefit Pool
        </h4>

        <div class="mt-5 space-y-4">
            <div class="flex justify-between gap-3 text-sm">
                <span class="text-slate-500">Authorized entitlement</span>
                <span class="font-bold text-slate-900">
                    ₹{{ number_format($staffDependentEntitlement, 2) }}
                </span>
            </div>

            <div class="flex justify-between gap-3 text-sm">
                <span class="text-slate-500">Cumulative net utilization</span>
                <span class="font-bold text-amber-700">
                    ₹{{ number_format($staffDependentCumulativeUsed, 2) }}
                </span>
            </div>

            <div class="flex justify-between gap-3 border-t border-slate-100 pt-4 text-sm">
                <span class="font-semibold text-slate-700">Remaining entitlement</span>
                <span class="font-bold text-emerald-700">
                    ₹{{ number_format($staffDependentRemaining, 2) }}
                </span>
            </div>
        </div>
    </div>

</div>

<div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
    <h4 class="text-sm font-bold text-amber-900">
        Charity &amp; Staff Benefits — Reporting Notes
    </h4>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        Charity requests, approvals and applications are separate
        events measured using their respective timestamps.
        Pending and approved-not-applied figures are current
        workflow snapshots.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        Staff medical benefit utilization includes active transactions
        and deducts recorded reversals. Financial-year entitlements
        represent authorized limits, not expenses already incurred.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        These benefits are not automatically added to General Ledger
        expenditure or recognized revenue. Their accounting treatment
        requires verification against the underlying billing adjustments
        and journal entries.
    </p>
</div>
</div>
    {{-- Financial reporting limitations --}}

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
        <h4 class="text-sm font-bold text-amber-900">
            Financial Reporting — Important Limitations
        </h4>

        <p class="mt-2 text-xs leading-6 text-amber-800">
            This Finance MIS uses posted ERP General Ledger journal
            entries within the selected reporting period. It is
            a provisional accrual accounting report, not the
            hospital's complete audited financial statement.
        </p>

        <p class="mt-2 text-xs leading-6 text-amber-800">
            Expense-account coverage and historical posting
            are incomplete. Recorded expenditure of zero does not
            mean the hospital incurred no operating expenses.
            Department attribution depends on department_id
            being correctly assigned to income journal lines.
        </p>

        <p class="mt-2 text-xs leading-6 text-amber-800">
            Cash collections, patient advances, MHIS receivables
            and Tally reconciliation will be reported separately.
            They must not be added directly to recognized revenue.
        </p>
    </div>

</section>
{{-- =========================================== --}}
{{-- PHARMACY MANAGEMENT INFORMATION SYSTEM --}}
{{-- =========================================== --}}

<section
    x-show="activeTab === 'pharmacy'"
    x-cloak
    class="space-y-5"
>
    <div>
        <h3 class="text-xl font-bold text-slate-900">
            Pharmacy Management Information System
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Pharmacy sales, dispensing, procurement, inventory,
            expiry monitoring, stock movement and supplier liabilities.
        </p>
    </div>


{{-- =============================================== --}}
{{-- PHARMACY MIS — SALES & REVENUE --}}
{{-- =============================================== --}}

<div class="border-b border-slate-200 pb-4">
    <h4 class="text-lg font-bold text-slate-900">
        Pharmacy Sales &amp; Revenue
    </h4>
    <p class="mt-1 text-sm text-slate-500">
        Completed and credit sales, returns and recorded collections
        during the selected reporting period.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
    @foreach([
        [
            'label' => 'Pharmacy Sales',
            'value' => number_format($pharmacySaleCount),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'Gross Sales (Subtotal)',
            'value' => '₹' . number_format($pharmacyGrossSales, 2),
            'color' => 'text-blue-700',
        ],
        [
            'label' => 'Discounts',
            'value' => '₹' . number_format($pharmacyDiscounts, 2),
            'color' => 'text-amber-700',
        ],
        [
            'label' => 'Billed Sales (Incl. Tax)',
            'value' => '₹' . number_format($pharmacyTotalSales, 2),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'Sales Return Adjustments',
            'value' => '₹' . number_format($pharmacyReturnAdjustments, 2),
            'color' => 'text-rose-700',
        ],
        [
            'label' => 'Net Operational Sales',
            'value' => '₹' . number_format($pharmacyNetSales, 2),
            'color' => 'text-emerald-700',
        ],
        [
            'label' => 'Counter Collections Recorded',
            'value' => '₹' . number_format($pharmacyCounterCollections, 2),
            'color' => 'text-green-700',
        ],
        [
            'label' => 'IPD Pharmacy Charges',
            'value' => '₹' . number_format($pharmacyIpdCharges, 2),
            'color' => 'text-violet-700',
        ],
        [
            'label' => 'Initial Credit / MHIS Balance',
            'value' => '₹' . number_format($pharmacyInitialCreditBalance, 2),
            'color' => 'text-orange-700',
        ],
        [
            'label' => 'Completed Returns',
            'value' => number_format($pharmacyReturnCount),
            'color' => 'text-rose-700',
        ],
        [
            'label' => 'Returned Medicine Units',
            'value' => number_format($pharmacyReturnedUnits),
            'color' => 'text-slate-800',
        ],
        [
            'label' => 'IPD Return Adjustments',
            'value' => '₹' . number_format($pharmacyIpdReturnAdjustments, 2),
            'color' => 'text-purple-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['label'] }}
            </p>

            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- Sales by patient source and payment mode --}}

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Pharmacy Sales by Patient Source
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                            Source
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Sales
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Amount
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($pharmacySalesBySource as $source)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ $source->source_name }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($source->sale_count) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-semibold">
                                ₹{{ number_format($source->sale_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">
                                No pharmacy sales recorded for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Sales by Payment Mode
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                            Payment Mode
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Sales
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Amount
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($pharmacySalesByMode as $mode)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ ucwords(str_replace('_', ' ', $mode->mode)) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($mode->sale_count) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-semibold">
                                ₹{{ number_format($mode->sale_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">
                                No payment-mode transactions recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Return and refund reporting --}}

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h4 class="font-bold text-slate-900">
            Patient Returns &amp; Refunds
        </h4>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <div>
                <p class="text-xs text-slate-500">Returned Merchandise Value</p>
                <p class="mt-2 text-xl font-bold text-slate-900">
                    ₹{{ number_format($pharmacyGrossReturns, 2) }}
                </p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Return Adjustments</p>
                <p class="mt-2 text-xl font-bold text-rose-700">
                    ₹{{ number_format($pharmacyReturnAdjustments, 2) }}
                </p>
            </div>

            <div>
                <p class="text-xs text-slate-500">Cash Refunds Recorded</p>
                <p class="mt-2 text-xl font-bold text-slate-900">
                    ₹{{ number_format($pharmacyCashRefunds, 2) }}
                </p>
            </div>

            <div>
                <p class="text-xs text-slate-500">UPI / Card Refunds Recorded</p>
                <p class="mt-2 text-xl font-bold text-slate-900">
                    ₹{{ number_format($pharmacyDigitalRefunds, 2) }}
                </p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Returns by Refund Mode
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                            Mode
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Returns
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Amount
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($pharmacyReturnsByMode as $mode)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ ucfirst($mode->refund_mode) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($mode->return_count) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-semibold">
                                ₹{{ number_format($mode->return_amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">
                                No completed medicine returns recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
    <p class="text-xs leading-6 text-amber-900">
        <strong>Reporting note:</strong>
        Net operational sales are billed sales during the selected period
        minus returns processed during that period. IPD pharmacy charges
        are transferred to central inpatient billing and are not direct
        pharmacy counter collections. Refund amounts are based on recorded
        return transactions and do not independently confirm bank or cash
        settlement. These figures are not yet reconciled to General Ledger
        revenue.
    </p>
</div>

{{-- =============================================== --}}
{{-- PHARMACY MIS — PROCUREMENT --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        Pharmacy Procurement &amp; Purchases
    </h4>

    <p class="mt-1 text-sm text-slate-500">
        Completed goods receipt notes, supplier purchase returns
        and medicine quantities received during the reporting period.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
    @foreach([
        [
            'label' => 'Completed GRNs',
            'value' => number_format($pharmacyGrnCount),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'GRN Purchase Value',
            'value' => '₹' . number_format($pharmacyPurchaseTotal, 2),
            'color' => 'text-blue-700',
        ],
        [
            'label' => 'Purchase Discounts',
            'value' => '₹' . number_format($pharmacyPurchaseDiscounts, 2),
            'color' => 'text-amber-700',
        ],
        [
            'label' => 'Supplier Purchase Returns',
            'value' => number_format($pharmacyPurchaseReturnCount),
            'color' => 'text-rose-700',
        ],
        [
            'label' => 'Purchase Return Value',
            'value' => '₹' . number_format($pharmacyPurchaseReturnTotal, 2),
            'color' => 'text-red-700',
        ],
        [
            'label' => 'Net Procurement',
            'value' => '₹' . number_format($pharmacyNetProcurement, 2),
            'color' => 'text-emerald-700',
        ],
        [
            'label' => 'Medicine Units Received',
            'value' => number_format($pharmacyReceivedUnits),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'Bonus Medicine Units',
            'value' => number_format($pharmacyBonusUnits),
            'color' => 'text-violet-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['label'] }}
            </p>

            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- Supplier-wise purchases --}}

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Top Suppliers by Procurement Value
        </h4>

        <p class="mt-1 text-xs text-slate-500">
            Completed GRNs during the selected period.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Supplier
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        GRNs
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Purchase Value
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($pharmacySupplierPurchases as $supplier)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                            {{ $supplier->supplier_name }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-slate-700">
                            {{ number_format($supplier->grn_count) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm font-semibold text-slate-900">
                            ₹{{ number_format($supplier->purchase_total, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">
                            No completed GRNs recorded during this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <p class="text-xs leading-6 text-slate-600">
        <strong>Procurement reporting note:</strong>
        GRNs represent goods received, not necessarily expenses recognized
        in the General Ledger. Net procurement deducts completed supplier
        purchase returns processed during the reporting period.
        Received units include purchased and bonus stock where recorded.
        Supplier payments and outstanding liabilities are reported separately.
    </p>
</div>

{{-- =============================================== --}}
{{-- PHARMACY MIS — INVENTORY & EXPIRY --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        Inventory Valuation &amp; Stock Availability
    </h4>
    <p class="mt-1 text-sm text-slate-500">
        Current stock position across all pharmacy and hospital stock locations.
        Inventory quantities and valuations are live snapshots, independent
        of the selected transaction reporting dates.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
    @foreach([
        [
            'label' => 'Medicines in Stock',
            'value' => number_format($pharmacyStockMedicines),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'Stock Batches',
            'value' => number_format($pharmacyStockBatches),
            'color' => 'text-blue-700',
        ],
        [
            'label' => 'Available Stock Units',
            'value' => number_format($pharmacyStockUnits),
            'color' => 'text-emerald-700',
        ],
        [
            'label' => 'Inventory Value at Cost',
            'value' => '₹' . number_format($pharmacyInventoryCost, 2),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'Potential Retail Value',
            'value' => '₹' . number_format($pharmacyInventoryRetailValue, 2),
            'color' => 'text-violet-700',
        ],
        [
            'label' => 'Low-Stock Location / Batches',
            'value' => number_format($pharmacyLowStockCount),
            'color' => 'text-amber-700',
        ],
        [
            'label' => 'Zero-Stock Location / Batches',
            'value' => number_format($pharmacyZeroStockCount),
            'color' => 'text-rose-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['label'] }}
            </p>
            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- Stock distribution across hospital locations --}}

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Stock Distribution by Hospital Location
        </h4>
        <p class="mt-1 text-xs text-slate-500">
            Quantities and inventory values at batch purchase cost.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Stock Location
                    </th>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Type
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Available Units
                    </th>
                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Inventory Value
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($pharmacyStockByLocation as $location)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                            {{ $location->name }}
                        </td>
                        <td class="px-5 py-3 text-sm text-slate-600">
                            {{ ucwords(str_replace('_', ' ', $location->location_type)) }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm tabular-nums">
                            {{ number_format($location->stock_units) }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm font-semibold tabular-nums">
                            ₹{{ number_format($location->inventory_cost, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-8 text-center text-sm text-slate-500">
                            No stock locations configured.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50">
                <tr>
                    <td colspan="2" class="px-5 py-3 text-sm font-bold text-slate-900">
                        Total Inventory
                    </td>
                    <td class="px-5 py-3 text-right text-sm font-bold">
                        {{ number_format($pharmacyStockUnits) }}
                    </td>
                    <td class="px-5 py-3 text-right text-sm font-bold">
                        ₹{{ number_format($pharmacyInventoryCost, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- =============================================== --}}
{{-- PHARMACY MIS — EXPIRY MONITORING --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        Medicine Expiry Monitoring
    </h4>
    <p class="mt-1 text-sm text-slate-500">
        Current stock categorized by batch expiry date.
        Near-expiry intervals are mutually exclusive.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
    @foreach([
        [
            'label' => 'Expired Units',
            'value' => number_format($pharmacyExpiredUnits),
            'color' => 'text-rose-700',
        ],
        [
            'label' => 'Expired Stock Value',
            'value' => '₹' . number_format($pharmacyExpiredStockValue, 2),
            'color' => 'text-red-700',
        ],
        [
            'label' => 'Expiring in 0–30 Days',
            'value' => number_format($pharmacyExpiring30Units),
            'color' => 'text-orange-700',
        ],
        [
            'label' => 'Expiring in 31–60 Days',
            'value' => number_format($pharmacyExpiring60Units),
            'color' => 'text-amber-700',
        ],
        [
            'label' => 'Expiring in 61–90 Days',
            'value' => number_format($pharmacyExpiring90Units),
            'color' => 'text-yellow-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['label'] }}
            </p>
            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <p class="text-xs leading-6 text-slate-600">
        <strong>Inventory reporting notes:</strong>
        Inventory value is calculated using available location-level stock
        multiplied by the recorded batch purchase price. Potential retail
        value is not recognized revenue or realized profit. Low-stock and
        zero-stock counts represent existing location/batch balance records;
        missing location/batch combinations are not counted.
        Expiry indicators use the current database date rather than
        the MIS transaction reporting period.
    </p>
</div>

{{-- =============================================== --}}
{{-- PHARMACY MIS — STOCK OPERATIONS --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        Stock Transfers &amp; Medicine Disposals
    </h4>

    <p class="mt-1 text-sm text-slate-500">
        Completed internal stock transfers and recorded medicine
        disposals during the selected reporting period.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach([
        [
            'label' => 'Completed Transfers',
            'value' => number_format($pharmacyTransferCount),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'Units Transferred',
            'value' => number_format($pharmacyTransferredUnits),
            'color' => 'text-blue-700',
        ],
        [
            'label' => 'Completed Disposals',
            'value' => number_format($pharmacyDisposalCount),
            'color' => 'text-rose-700',
        ],
        [
            'label' => 'Disposed Medicine Units',
            'value' => number_format($pharmacyDisposedUnits),
            'color' => 'text-orange-700',
        ],
        [
            'label' => 'Medicine Disposal Value',
            'value' => '₹' . number_format($pharmacyDisposalValue, 2),
            'color' => 'text-red-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['label'] }}
            </p>

            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- Transfer destinations and disposal reasons --}}

<div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Stock Transfers by Destination
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                            Destination
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Transfers
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Units Received
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($pharmacyTransfersByDestination as $destination)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ $destination->location_name }}
                            </td>

                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($destination->transfer_count) }}
                            </td>

                            <td class="px-5 py-3 text-right text-sm font-semibold">
                                {{ number_format($destination->received_units) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">
                                No completed transfers during this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h4 class="font-bold text-slate-900">
                Medicine Disposals by Reason
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                            Reason
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Units
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Value
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($pharmacyDisposalsByReason as $reason)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ $reason->disposal_reason }}
                            </td>

                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($reason->disposed_units) }}
                            </td>

                            <td class="px-5 py-3 text-right text-sm font-semibold">
                                ₹{{ number_format($reason->disposal_value, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">
                                No completed medicine disposals during this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- =============================================== --}}
{{-- STOCK RECONCILIATION --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        Pharmacy Stock Reconciliation
    </h4>

    <p class="mt-1 text-sm text-slate-500">
        Current batch stock compared with combined stock
        across all hospital locations.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold text-slate-500">
            Batches Checked
        </p>

        <p class="mt-3 text-2xl font-bold text-indigo-700">
            {{ number_format($pharmacyReconciliationBatchCount) }}
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold text-slate-500">
            Mismatched Batches
        </p>

        <p class="mt-3 text-2xl font-bold text-rose-700">
            {{ number_format($pharmacyReconciliationMismatchCount) }}
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold text-slate-500">
            Absolute Difference Units
        </p>

        <p class="mt-3 text-2xl font-bold text-amber-700">
            {{ number_format($pharmacyReconciliationDifferenceUnits) }}
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-xs font-semibold text-slate-500">
            Reconciliation Status
        </p>

        <p class="mt-3 text-lg font-bold {{ $pharmacyStockReconciled ? 'text-emerald-700' : 'text-rose-700' }}">
            {{ $pharmacyStockReconciled ? 'Reconciled' : 'Mismatch Detected' }}
        </p>
    </div>
</div>

{{-- Stock discrepancies --}}

@if($pharmacyReconciliationMismatchCount > 0)
    <div class="overflow-hidden rounded-2xl border border-rose-200 bg-white shadow-sm">
        <div class="border-b border-rose-100 px-5 py-4">
            <h4 class="font-bold text-rose-800">
                Stock Reconciliation Exceptions
            </h4>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                            Batch
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Batch Quantity
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Location Quantity
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                            Difference
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach($pharmacyStockMismatchDetails as $mismatch)
                        <tr>
                            <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                                {{ $mismatch['batch_number'] }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($mismatch['batch_quantity']) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm">
                                {{ number_format($mismatch['location_quantity']) }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-bold text-rose-700">
                                {{ number_format($mismatch['difference']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
        <p class="text-sm font-semibold text-emerald-800">
            No batch-to-location stock discrepancies detected.
        </p>
        <p class="mt-1 text-xs text-emerald-700">
            All checked batch quantities agree with their combined
            location balances.
        </p>
    </div>
@endif

<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <p class="text-xs leading-6 text-slate-600">
        <strong>Stock operations reporting note:</strong>
        Internal transfers redistribute existing hospital inventory
        and must not be counted as procurement or pharmacy revenue.
        Completed disposals represent operational inventory reductions.
        Their values are not automatically recognized as General Ledger
        expenses. Reconciliation compares current stored quantities;
        it does not substitute for a physical stock audit.
    </p>
</div>


{{-- =============================================== --}}
{{-- PHARMACY MIS — SUPPLIER FINANCE --}}
{{-- =============================================== --}}

<div class="border-t border-slate-200 pt-6">
    <h4 class="text-lg font-bold text-slate-900">
        Supplier Payables, Payments &amp; Credits
    </h4>

    <p class="mt-1 text-sm text-slate-500">
        Current supplier liabilities, overdue balances, payments
        during the selected reporting period and available credits.
    </p>
</div>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
    @foreach([
        [
            'label' => 'Supplier Payable Invoices',
            'value' => number_format($pharmacyPayableCount),
            'color' => 'text-indigo-700',
        ],
        [
            'label' => 'Current Supplier Outstanding',
            'value' => '₹' . number_format($pharmacySupplierOutstanding, 2),
            'color' => 'text-rose-700',
        ],
        [
            'label' => 'Unpaid Invoices',
            'value' => number_format($pharmacyUnpaidPayables),
            'color' => 'text-amber-700',
        ],
        [
            'label' => 'Partially Paid Invoices',
            'value' => number_format($pharmacyPartiallyPaidPayables),
            'color' => 'text-orange-700',
        ],
        [
            'label' => 'Overdue Invoices',
            'value' => number_format($pharmacyOverduePayables),
            'color' => 'text-red-700',
        ],
        [
            'label' => 'Overdue Amount',
            'value' => '₹' . number_format($pharmacyOverdueAmount, 2),
            'color' => 'text-red-700',
        ],
        [
            'label' => 'Supplier Payments Recorded',
            'value' => '₹' . number_format($pharmacySupplierPaymentTotal, 2),
            'color' => 'text-emerald-700',
        ],
        [
            'label' => 'Payment Transactions',
            'value' => number_format($pharmacySupplierPaymentCount),
            'color' => 'text-blue-700',
        ],
        [
            'label' => 'Available Supplier Credits',
            'value' => '₹' . number_format($pharmacyAvailableSupplierCredit, 2),
            'color' => 'text-violet-700',
        ],
        [
            'label' => 'Available / Partial Credit Records',
            'value' => number_format($pharmacySupplierCreditCount),
            'color' => 'text-indigo-700',
        ],
    ] as $metric)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold text-slate-500">
                {{ $metric['label'] }}
            </p>

            <p class="mt-3 text-xl font-bold tabular-nums {{ $metric['color'] }}">
                {{ $metric['value'] }}
            </p>
        </div>
    @endforeach
</div>

{{-- Supplier outstanding balances --}}

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-5 py-4">
        <h4 class="font-bold text-slate-900">
            Supplier-Wise Outstanding Balances
        </h4>

        <p class="mt-1 text-xs text-slate-500">
            Top 10 suppliers by current outstanding payable balance.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500">
                        Supplier
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Invoices
                    </th>

                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                        Outstanding Amount
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($pharmacyOutstandingBySupplier as $supplier)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-sm font-semibold text-slate-800">
                            {{ $supplier->supplier_name }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm text-slate-700">
                            {{ number_format($supplier->invoice_count) }}
                        </td>

                        <td class="px-5 py-3 text-right text-sm font-semibold text-rose-700">
                            ₹{{ number_format($supplier->outstanding_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3"
                            class="px-5 py-8 text-center text-sm text-slate-500">
                            No outstanding supplier liabilities recorded.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pharmacy supplier accounting limitations --}}

<div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
    <h4 class="text-sm font-bold text-amber-900">
        Supplier Finance — Reporting Notes
    </h4>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        Supplier outstanding balances, overdue invoices and available
        credits are current accounting snapshots. They do not represent
        historical balances at the selected reporting end date.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        Supplier payments reflect transactions recorded during the
        selected reporting period. Current outstanding balances already
        incorporate payments and applicable adjustments; supplier credits
        must not be deducted a second time.
    </p>

    <p class="mt-2 text-xs leading-6 text-amber-800">
        GRN procurement, supplier payments and medicine disposals are
        operational records. Their accounting recognition must be
        reconciled separately with posted General Ledger journals and
        TallyPrime before relying on reported pharmacy profitability.
    </p>
</div>

</section>

                {{-- =========================================== --}}
                {{-- FUTURE REPORTING TABS --}}
                {{-- =========================================== --}}


{{-- =========================================== --}}
{{-- HOSPITAL EXPENSES MIS --}}
{{-- =========================================== --}}

<section
    x-show="activeTab === 'expenses'"
    x-cloak
    class="space-y-5"
>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900">
                Hospital Expenses MIS
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                Clinical, non-clinical and shared expenditure,
                departmental allocations and accounting status.
            </p>
        </div>

        @if(auth()->user()->hasPermission('hospital_expenses.view'))
            <a href="{{ route('finance.hospital-expenses.index') }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Open Expense Register
            </a>
        @endif
    </div>

    {{-- Expense status cards --}}

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm font-semibold text-amber-800">
                Draft Expenses
            </p>
            <p class="mt-3 text-3xl font-bold text-amber-900">
                ₹{{ number_format($expenseTotalDraft, 2) }}
            </p>
            <p class="mt-2 text-xs text-amber-700">
                Not approved or recognized in the General Ledger.
            </p>
        </div>

        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
            <p class="text-sm font-semibold text-indigo-800">
                Posted Expense Register
            </p>
            <p class="mt-3 text-3xl font-bold text-indigo-900">
                ₹{{ number_format($expenseTotalPosted, 2) }}
            </p>
            <p class="mt-2 text-xs text-indigo-700">
                Marked posted in the expense register;
                GL reconciliation is still required.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-700">
                Total Registered Expenses
            </p>
            <p class="mt-3 text-3xl font-bold text-slate-900">
                ₹{{ number_format(
                    $expenseStatusSummary->sum('total'),
                    2
                ) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">
                Includes all workflow statuses; not a measure
                of recognized expenditure.
            </p>
        </div>

    </div>

    {{-- Workflow status --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h4 class="mb-4 text-base font-bold text-slate-900">
            Expense Workflow Status
        </h4>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Records</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($expenseStatusSummary as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                {{ ucfirst($row->status) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ number_format($row->expense_count) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                ₹{{ number_format($row->total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"
                                class="px-4 py-8 text-center text-slate-500">
                                No expense records for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Classification --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h4 class="mb-4 text-base font-bold text-slate-900">
            Clinical, Non-Clinical and Shared Expenses
        </h4>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Classification</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Records</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($expenseClassificationSummary as $row)
                        <tr>
                            <td class="px-4 py-3">
                                {{ ucwords(str_replace(
                                    '_',
                                    ' ',
                                    $row->expense_classification
                                )) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ ucfirst($row->status) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ $row->expense_count }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                ₹{{ number_format($row->total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                class="px-4 py-8 text-center text-slate-500">
                                No classification data available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Department allocation --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h4 class="mb-4 text-base font-bold text-slate-900">
            Department-wise Expense Allocation
        </h4>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Allocated Amount</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($expenseDepartmentSummary as $row)
                        <tr>
                            <td class="px-4 py-3">
                                {{ $row->name }}
                            </td>
                            <td class="px-4 py-3">
                                {{ ucfirst($row->status) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                ₹{{ number_format($row->total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"
                                class="px-4 py-8 text-center text-slate-500">
                                No department allocations available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Finance Head --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h4 class="mb-4 text-base font-bold text-slate-900">
            Expenses by Finance Head
        </h4>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Finance Head</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($expenseHeadSummary as $row)
                        <tr>
                            <td class="px-4 py-3">
                                {{ $row->name }}
                            </td>
                            <td class="px-4 py-3">
                                {{ ucfirst($row->status) }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                ₹{{ number_format($row->total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"
                                class="px-4 py-8 text-center text-slate-500">
                                No expense categories available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
        <h4 class="text-sm font-bold text-amber-900">
            Accounting and Reporting Limitations
        </h4>

        <p class="mt-2 text-xs leading-6 text-amber-800">
            This MIS section reports Hospital Expense Register records.
            Draft, submitted and approved expenses are not recognized
            General Ledger expenditure. Posted register records require
            reconciliation against their accounting journals.
        </p>

        <p class="mt-2 text-xs leading-6 text-amber-800">
            Pharmacy consumption, payroll, depreciation and other
            ERP-generated costs are not yet automatically synchronized
            with this register. Figures may therefore be incomplete
            and must not be interpreted as total hospital expenditure.
        </p>
    </div>
</section>

            </div>

            {{-- =============================================== --}}
            {{-- FOOTER --}}
            {{-- =============================================== --}}

            <div class="rounded-xl border border-slate-200 bg-white px-5 py-4">
                <p class="text-xs leading-6 text-slate-500">
                    <strong class="text-slate-700">TCH MIS Reporting:</strong>
                    All figures are derived from existing ERP records.
                    Clinical reports use the selected reporting period.
                    Current bed status is a live snapshot, while historical
                    bed utilization remains provisional pending validation
                    of historical records and operational capacity.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
