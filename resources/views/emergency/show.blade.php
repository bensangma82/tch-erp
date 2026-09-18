<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Emergency Visit
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $emergencyVisit->emergency_no }}
                </p>

            </div>


            <a
                href="{{ route('emergency.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to Emergency Queue
            </a>

        </div>

    </x-slot>


    @php

        $patient =
            $emergencyVisit->patient;

        $latestTriage =
            $emergencyVisit->latestTriage;

        $admission =
            $emergencyVisit->admission;


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


        $statusClasses = [
            'registered' =>
                'bg-blue-50 text-blue-700',

            'triaged' =>
                'bg-amber-50 text-amber-700',

            'under_treatment' =>
                'bg-purple-50 text-purple-700',

            'admitted' =>
                'bg-emerald-50 text-emerald-700',

            'discharged' =>
                'bg-slate-100 text-slate-700',

            'referred' =>
                'bg-indigo-50 text-indigo-700',

            'death' =>
                'bg-red-100 text-red-800',

            'cancelled' =>
                'bg-slate-100 text-slate-500',
        ];


        $statusClass =
            $statusClasses[$emergencyVisit->status]
            ?? 'bg-slate-100 text-slate-700';


        $statusLabel =
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    $emergencyVisit->status
                )
            );


        $isClosed =
            in_array(
                $emergencyVisit->status,
                [
                    'admitted',
                    'discharged',
                    'referred',
                    'death',
                    'cancelled',
                ],
                true
            );


        $userRole =
            auth()->user()?->role;

    @endphp


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- SUCCESS MESSAGE --}}
            {{-- ========================================================= --}}

            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- EMERGENCY HEADER --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-center lg:justify-between">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Emergency Number
                        </div>

                        <div class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                            {{ $emergencyVisit->emergency_no }}
                        </div>

                        <div class="mt-3">

                            <span
                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}"
                            >
                                {{ $statusLabel }}
                            </span>

                        </div>

                    </div>


                    <div class="flex flex-wrap gap-2">


                        @if (
                            ! $isClosed
                            &&
                            in_array(
                                $userRole,
                                [
                                    'nursing',
                                    'admin',
                                ],
                                true
                            )
                        )

                            <a
                                href="{{ route('emergency.triage.create', $emergencyVisit) }}"
                                class="inline-flex items-center rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-100"
                            >
                                {{ $latestTriage ? 'Update Triage' : 'Record Triage' }}
                            </a>

                        @endif



                        @if (
                            ! $admission
                            &&
                            $emergencyVisit->status !== 'admitted'
                            &&
                            ! in_array(
                                $emergencyVisit->status,
                                [
                                    'discharged',
                                    'referred',
                                    'death',
                                    'cancelled',
                                ],
                                true
                            )
                            &&
                            in_array(
                                $userRole,
                                [
                                    'reception',
                                    'doctor',
                                    'admin',
                                ],
                                true
                            )
                        )

                            <a
                                href="{{ route('emergency.admission.create', $emergencyVisit) }}"
                                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                Admit Patient
                            </a>

                        @endif



                        @if ($admission)

                            <a
                                href="{{ route('ipd.show', $admission) }}"
                                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                Open IPD Admission
                            </a>

                        @endif


                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- PATIENT / VISIT DETAILS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Emergency Patient Details
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Registration and arrival information
                    </p>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Patient
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $patient?->full_name ?? 'Unknown Patient' }}
                        </div>

                        <div class="mt-2 space-y-1 text-xs text-slate-500">

                            @if ($patient?->uhid)

                                <div>
                                    UHID:
                                    <span class="font-semibold text-slate-700">
                                        {{ $patient->uhid }}
                                    </span>
                                </div>

                            @endif


                            @if ($patient?->mrd_number)

                                <div>
                                    MRD:
                                    <span class="font-semibold text-slate-700">
                                        {{ $patient->mrd_number }}
                                    </span>
                                </div>

                            @endif


                            @if ($patient?->phone)

                                <div>
                                    Mobile:
                                    <span class="font-semibold text-slate-700">
                                        {{ $patient->phone }}
                                    </span>
                                </div>

                            @endif


                            @if (! $patient)

                                <div>
                                    Patient ID:
                                    {{ $emergencyVisit->patient_id }}
                                </div>

                            @endif

                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Arrival
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $emergencyVisit->arrival_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Arrival Mode
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">

                            @if ($emergencyVisit->arrival_mode)

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $emergencyVisit->arrival_mode
                                        )
                                    )
                                }}

                            @else

                                —

                            @endif

                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Brought By
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $emergencyVisit->brought_by ?: '—' }}
                        </div>

                    </div>



                    <div class="sm:col-span-2 lg:col-span-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Chief Complaint
                        </div>

                        <div class="mt-2 text-sm font-medium leading-6 text-slate-900">
                            {{ $emergencyVisit->chief_complaint ?: '—' }}
                        </div>

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- TRIAGE --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h3 class="font-semibold text-slate-900">
                            Emergency Triage
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Latest nursing assessment and vital signs
                        </p>

                    </div>


                    @if (
                        ! $isClosed
                        &&
                        in_array(
                            $userRole,
                            [
                                'nursing',
                                'admin',
                            ],
                            true
                        )
                    )

                        <a
                            href="{{ route('emergency.triage.create', $emergencyVisit) }}"
                            class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            {{ $latestTriage ? 'Update Triage' : 'Record Triage' }}
                        </a>

                    @endif

                </div>


                @if ($latestTriage)

                    <div class="grid gap-x-8 gap-y-6 p-6 sm:grid-cols-2 lg:grid-cols-5">


                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Category
                            </div>

                            <div class="mt-2">

                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                    {{
                                        $triageClasses[$latestTriage->triage_category]
                                        ?? 'bg-slate-100 text-slate-700'
                                    }}"
                                >
                                    {{ strtoupper($latestTriage->triage_category ?: 'Uncategorised') }}
                                </span>

                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Blood Pressure
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->blood_pressure_systolic ?? '—' }}
                                /
                                {{ $latestTriage->blood_pressure_diastolic ?? '—' }}
                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Pulse
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->pulse ?? '—' }}
                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Respiratory Rate
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->respiratory_rate ?? '—' }}
                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                SpO₂
                            </div>

                            <div class="mt-2 font-bold text-slate-900">

                                {{ $latestTriage->spo2 ?? '—' }}

                                @if ($latestTriage->spo2 !== null)
                                    %
                                @endif

                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Temperature
                            </div>

                            <div class="mt-2 font-bold text-slate-900">

                                {{ $latestTriage->temperature ?? '—' }}

                                @if ($latestTriage->temperature !== null)
                                    °C
                                @endif

                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                GCS
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->gcs ?? '—' }}
                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Pain Score
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->pain_score ?? '—' }}
                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Oxygen Support
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->oxygen_support ?: '—' }}
                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Recorded At
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->recorded_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>

                        </div>


                        @if ($latestTriage->notes)

                            <div class="sm:col-span-2 lg:col-span-5">

                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Triage Notes
                                </div>

                                <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                    {{ $latestTriage->notes }}
                                </div>

                            </div>

                        @endif


                    </div>


                @else

                    <div class="p-8">

                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">

                            <div class="font-semibold text-slate-700">
                                Triage has not yet been recorded
                            </div>

                            <div class="mt-1 text-sm text-slate-500">
                                Nursing staff can record the patient's initial emergency assessment.
                            </div>

                        </div>

                    </div>

                @endif

            </div>



            {{-- ========================================================= --}}
            {{-- INPATIENT ADMISSION --}}
            {{-- ========================================================= --}}

            @if ($admission)

                @php

                    $consultant =
                        $admission->consultant;


                    $consultantName =
                        $consultant
                            ? trim(
                                ($consultant->title
                                    ? $consultant->title . ' '
                                    : '')
                                .
                                $consultant->first_name
                                .
                                ($consultant->middle_name
                                    ? ' ' . $consultant->middle_name
                                    : '')
                                .
                                ($consultant->last_name
                                    ? ' ' . $consultant->last_name
                                    : '')
                            )
                            : '—';

                @endphp


                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm">

                    <div class="flex flex-col gap-5 p-6">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                            <div>

                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Inpatient Admission
                                </div>

                                <div class="mt-2 text-2xl font-bold text-slate-900">
                                    {{ $admission->admission_no }}
                                </div>

                            </div>


                            <a
                                href="{{ route('ipd.show', $admission) }}"
                                class="inline-flex rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800"
                            >
                                Open IPD Admission
                            </a>

                        </div>


                        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">


                            <div>

                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Department
                                </div>

                                <div class="mt-2 font-semibold text-slate-900">
                                    {{ $admission->department?->name ?? '—' }}
                                </div>

                            </div>



                            <div>

                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Consultant
                                </div>

                                <div class="mt-2 font-semibold text-slate-900">
                                    {{ $consultantName }}
                                </div>

                            </div>



                            <div>

                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Ward
                                </div>

                                <div class="mt-2 font-semibold text-slate-900">
                                    {{ $admission->bed?->ward?->name ?? '—' }}
                                </div>

                            </div>



                            <div>

                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                    Bed
                                </div>

                                <div class="mt-2 font-semibold text-slate-900">
                                    {{ $admission->bed?->bed_number ?? '—' }}
                                </div>

                            </div>


                        </div>

                    </div>

                </div>


            @elseif (
                ! in_array(
                    $emergencyVisit->status,
                    [
                        'discharged',
                        'referred',
                        'death',
                        'cancelled',
                    ],
                    true
                )
            )

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Inpatient Admission
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Admit this patient to an inpatient ward or ICU when admission has been decided.
                            </p>

                        </div>


                        @if (
                            in_array(
                                $userRole,
                                [
                                    'reception',
                                    'doctor',
                                    'admin',
                                ],
                                true
                            )
                        )

                            <a
                                href="{{ route('emergency.admission.create', $emergencyVisit) }}"
                                class="inline-flex rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                Admit Patient
                            </a>

                        @endif

                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- WORKFLOW STATUS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="p-6">

                    <h3 class="font-semibold text-slate-900">
                        Emergency Workflow
                    </h3>


                    @if ($admission)

                        <p class="mt-3 text-sm text-slate-600">

                            This Emergency visit has been completed through inpatient admission.

                            The patient is currently linked to

                            <span class="font-semibold text-slate-900">
                                {{ $admission->admission_no }}
                            </span>.

                        </p>


                    @elseif ($emergencyVisit->status === 'discharged')

                        <p class="mt-3 text-sm text-slate-600">
                            This patient has been discharged from Emergency.
                        </p>


                    @elseif ($emergencyVisit->status === 'referred')

                        <p class="mt-3 text-sm text-slate-600">
                            This patient has been referred from Emergency.
                        </p>


                    @elseif ($emergencyVisit->status === 'death')

                        <p class="mt-3 text-sm text-slate-600">
                            This Emergency visit has been closed following death.
                        </p>


                    @elseif ($emergencyVisit->status === 'cancelled')

                        <p class="mt-3 text-sm text-slate-600">
                            This Emergency visit has been cancelled.
                        </p>


                    @else

                        <p class="mt-3 text-sm text-slate-600">

                            Current status:

                            <span class="font-semibold text-slate-900">
                                {{ $statusLabel }}
                            </span>.

                        </p>


                        <div class="mt-5 flex flex-wrap gap-3">


                            @if (
                                in_array(
                                    $userRole,
                                    [
                                        'nursing',
                                        'admin',
                                    ],
                                    true
                                )
                            )

                                <a
                                    href="{{ route('emergency.triage.create', $emergencyVisit) }}"
                                    class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-100"
                                >
                                    {{ $latestTriage ? 'Update Triage' : 'Record Triage' }}
                                </a>

                            @endif


                            @if (
                                in_array(
                                    $userRole,
                                    [
                                        'reception',
                                        'doctor',
                                        'admin',
                                    ],
                                    true
                                )
                            )

                                <a
                                    href="{{ route('emergency.admission.create', $emergencyVisit) }}"
                                    class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
                                >
                                    Admit Patient
                                </a>

                            @endif


                        </div>

                    @endif

                </div>

            </div>


        </div>

    </div>

</x-app-layout>