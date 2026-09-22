<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Emergency Department
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Live emergency patient queue and disposition tracking
                </p>

            </div>


            @if (
                in_array(
                    auth()->user()?->role,
                    [
                        'reception',
                        'admin', 'nursing', 'emergency'
                    ],
                    true
                )
            )

                <a
                    href="{{ route('emergency.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
                >
                    Register Emergency Patient
                </a>

            @endif

        </div>

    </x-slot>



    @php

        $statusLabels = [
            'registered' =>
                'Registered',

            'triaged' =>
                'Triaged',

            'under_treatment' =>
                'Under Treatment',

            'awaiting_admission' =>
                'Awaiting Admission',

            'admitted' =>
                'Admitted',

            'discharged' =>
                'Discharged',

            'referred' =>
                'Referred',

            'death' =>
                'Death',

            'cancelled' =>
                'Cancelled',
        ];


        $statusClasses = [
            'registered' =>
                'bg-slate-100 text-slate-700',

            'triaged' =>
                'bg-blue-50 text-blue-700',

            'under_treatment' =>
                'bg-amber-50 text-amber-700',

            'awaiting_admission' =>
                'bg-purple-50 text-purple-700',

            'admitted' =>
                'bg-emerald-50 text-emerald-700',

            'discharged' =>
                'bg-cyan-50 text-cyan-700',

            'referred' =>
                'bg-indigo-50 text-indigo-700',

            'death' =>
                'bg-red-100 text-red-800',

            'cancelled' =>
                'bg-slate-100 text-slate-500',
        ];


        $triageClasses = [
            'red' =>
                'bg-red-100 text-red-800',

            'orange' =>
                'bg-orange-100 text-orange-800',

            'yellow' =>
                'bg-yellow-100 text-yellow-800',

            'green' =>
                'bg-green-100 text-green-800',

            'blue' =>
                'bg-blue-100 text-blue-800',
        ];

    @endphp



    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto w-full max-w-[1800px] space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- SUCCESS MESSAGE --}}
            {{-- ========================================================= --}}

            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- ERROR MESSAGE --}}
            {{-- ========================================================= --}}

            @if (session('error'))

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- FILTERS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('emergency.index') }}"
                    class="grid gap-4 md:grid-cols-3"
                >


                    {{-- ARRIVAL DATE --}}

                    <div>

                        <label
                            for="date"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Arrival Date
                        </label>

                        <input
                            id="date"
                            name="date"
                            type="date"
                            value="{{ request('date') }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>



                    {{-- STATUS --}}

                    <div>

                        <label
                            for="status"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                All Statuses
                            </option>


                            @foreach (
                                $statusLabels
                                as $value => $label
                            )

                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        request('status') === $value
                                    )
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                    </div>



                    {{-- FILTER ACTIONS --}}

                    <div class="flex items-end gap-2">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Filter
                        </button>


                        <a
                            href="{{ route('emergency.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>



            {{-- ========================================================= --}}
            {{-- EMERGENCY QUEUE --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Emergency Patient Queue
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Latest emergency visits are shown first
                            </p>

                        </div>


                        <div class="text-sm font-semibold text-slate-600">

                            {{ number_format(
                                $emergencyVisits->total()
                            ) }}

                            visit{{ $emergencyVisits->total() === 1 ? '' : 's' }}

                        </div>

                    </div>

                </div>



                <div class="overflow-x-auto">

                    <table class="min-w-[1250px] w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="min-w-[240px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Emergency No
                                </th>

                                <th class="min-w-[260px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Patient
                                </th>

                                <th class="min-w-[150px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Arrival
                                </th>

                                <th class="min-w-[140px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Triage
                                </th>

                                <th class="min-w-[180px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Vitals
                                </th>

                                <th class="min-w-[190px] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                                <th class="min-w-[160px] px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>



                        <tbody class="divide-y divide-slate-100">

                            @forelse ($emergencyVisits as $visit)

                                @php

                                    $patient =
                                        $visit->patient;

                                    $triage =
                                        $visit->latestTriage;


                                    $statusLabel =
                                        $statusLabels[$visit->status]
                                        ??
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $visit->status
                                            )
                                        );


                                    $statusClass =
                                        $statusClasses[$visit->status]
                                        ??
                                        'bg-slate-100 text-slate-700';


                                    $triageClass =
                                        $triage
                                            ? (
                                                $triageClasses[
                                                    $triage->triage_category
                                                ]
                                                ??
                                                'bg-slate-100 text-slate-700'
                                            )
                                            : null;

                                @endphp



                                <tr class="hover:bg-slate-50">


                                    {{-- EMERGENCY NUMBER --}}

                                    <td class="min-w-[240px] px-4 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $visit->emergency_no }}
                                        </div>


                                        <div class="mt-1 text-xs text-slate-500">

                                            @if ($visit->arrival_mode)

                                                {{
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $visit->arrival_mode
                                                        )
                                                    )
                                                }}

                                            @else

                                                Arrival mode not recorded

                                            @endif

                                        </div>

                                    </td>



                                    {{-- PATIENT --}}

                                    <td class="min-w-[260px] px-4 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $patient?->full_name ?? 'Unknown Patient' }}
                                        </div>


                                        <div class="mt-1 space-y-0.5 text-xs text-slate-500">

                                            @if ($patient?->uhid)

                                                <div>
                                                    {{ $patient->uhid }}
                                                </div>

                                            @endif


                                            @if ($patient?->mrd_number)

                                                <div>
                                                    {{ $patient->mrd_number }}
                                                </div>

                                            @endif


                                            @if ($patient?->phone)

                                                <div>
                                                    {{ $patient->phone }}
                                                </div>

                                            @endif


                                            @if (! $patient)

                                                <div>
                                                    Patient ID:
                                                    {{ $visit->patient_id }}
                                                </div>

                                            @endif

                                        </div>

                                    </td>



                                    {{-- ARRIVAL --}}

                                    <td class="min-w-[150px] px-4 py-4 align-top text-sm text-slate-700">

                                        <div class="font-medium">
                                            {{ $visit->arrival_at?->format('d M Y') ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $visit->arrival_at?->format('h:i A') ?? '—' }}
                                        </div>

                                    </td>



                                    {{-- TRIAGE --}}

                                    <td class="min-w-[140px] px-4 py-4 align-top">

                                        @if ($triage)

                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $triageClass }}"
                                            >
                                                {{
                                                    strtoupper(
                                                        $triage->triage_category
                                                        ?: 'Uncategorised'
                                                    )
                                                }}
                                            </span>

                                        @else

                                            <span class="text-sm text-slate-400">
                                                Not triaged
                                            </span>

                                        @endif

                                    </td>



                                    {{-- VITALS --}}

                                    <td class="min-w-[180px] px-4 py-4 align-top text-xs text-slate-600">

                                        @if ($triage)

                                            <div>

                                                BP:

                                                <span class="font-semibold">

                                                    {{ $triage->blood_pressure_systolic ?? '—' }}

                                                    /

                                                    {{ $triage->blood_pressure_diastolic ?? '—' }}

                                                </span>

                                            </div>


                                            <div class="mt-1">

                                                Pulse:

                                                <span class="font-semibold">
                                                    {{ $triage->pulse ?? '—' }}
                                                </span>

                                            </div>


                                            <div class="mt-1">

                                                SpO₂:

                                                <span class="font-semibold">

                                                    {{ $triage->spo2 ?? '—' }}

                                                    @if (
                                                        $triage->spo2 !== null
                                                    )
                                                        %
                                                    @endif

                                                </span>

                                            </div>

                                        @else

                                            —

                                        @endif

                                    </td>



                                    {{-- STATUS --}}

                                    <td class="min-w-[190px] px-4 py-4 align-top">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}"
                                        >
                                            {{ $statusLabel }}
                                        </span>


                                        @if ($visit->admission)

                                            <div class="mt-2 text-xs font-semibold text-emerald-700">
                                                {{ $visit->admission->admission_no }}
                                            </div>

                                        @endif

                                    </td>



                                    {{-- ACTION --}}

                                    <td class="min-w-[160px] whitespace-nowrap px-4 py-4 text-right align-top">

                                        <a
                                            href="{{ route('emergency.show', $visit) }}"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Open
                                        </a>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center"
                                    >

                                        <div class="text-sm font-semibold text-slate-700">
                                            No emergency visits found
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            Emergency registrations will appear here.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>



                @if ($emergencyVisits->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $emergencyVisits->links() }}
                    </div>

                @endif

            </div>


        </div>

    </div>

</x-app-layout>