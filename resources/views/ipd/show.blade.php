<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    IPD Admission
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $admission->admission_no }}
                </p>

            </div>


            <a
                href="{{ route('ipd.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to IPD Census
            </a>

        </div>

    </x-slot>


    @php

        $patient =
            $admission->patient;


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


        $currentBed =
            $admission->currentBedAllocation?->bed
            ??
            $admission->bed;


        $statusClasses = [
            'admitted' =>
                'bg-emerald-50 text-emerald-700',

            'transferred' =>
                'bg-blue-50 text-blue-700',

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
            $statusClasses[$admission->status]
            ??
            'bg-slate-100 text-slate-700';


        $statusLabel =
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    $admission->status
                )
            );


        $emergencyVisit =
            $admission->emergencyVisit;


        $latestTriage =
            $emergencyVisit?->latestTriage;



        $dischargeSummary =
            $admission->dischargeSummary;


        $canEditDischargeSummary =
            in_array(
                auth()->user()?->role,
                ['doctor', 'admin'],
                true
            );


        $dischargeSummaryPreparedBy =
            $dischargeSummary?->preparedBy?->name
            ?? '—';


        $dischargeSummaryUpdatedAt =
            $dischargeSummary?->updated_at
                ? $dischargeSummary->updated_at->format('d M Y, h:i A')
                : '—';


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


        /*
        |--------------------------------------------------------------------------
        | INPATIENT PHARMACY HISTORY
        |--------------------------------------------------------------------------
        |
        | Pharmacy sales are linked directly to this admission through
        | pharmacy_sales.admission_id. Pricing is intentionally not displayed
        | on the clinical IPD screen; financial detail remains in IP Billing.
        |
        */

        $pharmacySales =
            \App\Models\PharmacySale::query()
                ->with([
                    'items',
                    'returns.items',
                ])
                ->where('admission_id', $admission->id)
                ->latest('sale_at')
                ->get();


        $pharmacySaleCount =
            $pharmacySales->count();


        $pharmacyReturnCount =
            $pharmacySales
                ->sum(
                    fn ($sale) =>
                        $sale->returns?->count() ?? 0
                );

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
            {{-- ERROR MESSAGE --}}
            {{-- ========================================================= --}}

            @if (session('error'))

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- ADMISSION HEADER --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-center lg:justify-between">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Admission Number
                        </div>

                        <div class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                            {{ $admission->admission_no }}
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

                        {{-- ========================================================= --}}
                        {{-- BED TRANSFER --}}
                        {{-- ========================================================= --}}

                        @if ($admission->status === 'admitted')

                            <a
                                href="{{ route('ipd.transfer.create', $admission) }}"
                                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                            >
                                Transfer Bed
                            </a>

                        @endif


                        {{-- ========================================================= --}}
                        {{-- DISCHARGE SUMMARY --}}
                        {{-- ========================================================= --}}

                        @if ($dischargeSummary)

                            @if ($canEditDischargeSummary)

                                <a
                                    href="{{ route('ipd.discharge-summary.edit', $admission) }}"
                                    class="inline-flex items-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                                >
                                    Edit Discharge Summary
                                </a>

                            @endif


                            <a
                                href="{{ route('ipd.discharge-summary.show', $admission) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                View / Print Summary
                            </a>

                        @elseif ($canEditDischargeSummary)

                            <a
                                href="{{ route('ipd.discharge-summary.edit', $admission) }}"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                            >
                                Prepare Discharge Summary
                            </a>

                        @endif


                        {{-- ========================================================= --}}
                        {{-- CLOSE ADMISSION --}}
                        {{-- ========================================================= --}}

                        @if ($admission->status === 'admitted')

                            <a
                                href="{{ route('ipd.closure.create', $admission) }}"
                                class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
                            >
                                Close Admission
                            </a>

                        @endif


                        {{-- ========================================================= --}}
                        {{-- EMERGENCY VISIT --}}
                        {{-- ========================================================= --}}

                        @if ($emergencyVisit)

                            <a
                                href="{{ route('emergency.show', $emergencyVisit) }}"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Open Emergency Visit
                            </a>

                        @endif

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- DISCHARGE SUMMARY STATUS --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h3 class="font-semibold text-slate-900">
                            Discharge Summary
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Preparation status and document access for this admission
                        </p>

                    </div>


                    @if ($dischargeSummary)

                        <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                            Prepared
                        </span>

                    @else

                        <span class="inline-flex w-fit items-center rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                            Not Prepared
                        </span>

                    @endif

                </div>


                <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Status
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $dischargeSummary ? 'Prepared' : 'Not Prepared' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Prepared By
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $dischargeSummaryPreparedBy }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Last Updated
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $dischargeSummaryUpdatedAt }}
                        </div>

                    </div>



                    <div class="flex items-end">

                        <div class="flex flex-wrap gap-2">

                            @if ($dischargeSummary)

                                <a
                                    href="{{ route('ipd.discharge-summary.show', $admission) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                                >
                                    View / Print
                                </a>


                                @if ($canEditDischargeSummary)

                                    <a
                                        href="{{ route('ipd.discharge-summary.edit', $admission) }}"
                                        class="inline-flex items-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                                    >
                                        Edit
                                    </a>

                                @endif

                            @elseif ($canEditDischargeSummary)

                                <a
                                    href="{{ route('ipd.discharge-summary.edit', $admission) }}"
                                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                                >
                                    Prepare Summary
                                </a>

                            @else

                                <span class="text-sm text-slate-500">
                                    Awaiting preparation by doctor
                                </span>

                            @endif

                        </div>

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- PATIENT DETAILS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Patient Details
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Patient identification and contact details
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

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            UHID
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $patient?->uhid ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            MRD
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $patient?->mrd_number ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Mobile
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $patient?->phone ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Sex
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $patient?->sex ? ucfirst($patient->sex) : '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Age
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">

                            @if ($patient?->age !== null)

                                {{ $patient->age }} years

                            @elseif ($patient?->date_of_birth)

                                {{ $patient->date_of_birth->age }} years

                            @else

                                —

                            @endif

                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Blood Group
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $patient?->blood_group ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Known Allergies
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $patient?->known_allergies ?: '—' }}
                        </div>

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- ADMISSION DETAILS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Admission Details
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Current inpatient admission information
                    </p>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Admission Date
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $admission->admitted_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Admission Type
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">

                            @if ($admission->admission_type)

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $admission->admission_type
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
                            Department
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $admission->department?->name ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Consultant
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $consultantName }}
                        </div>

                        @if ($consultant?->speciality)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $consultant->speciality }}
                            </div>

                        @endif

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Current Ward
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $currentBed?->ward?->name ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Current Bed
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $currentBed?->bed_number ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Source
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">

                            @if ($admission->source_type)

                                {{
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $admission->source_type
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
                            Created By
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $admission->createdBy?->name ?? '—' }}
                        </div>

                    </div>



                    <div class="sm:col-span-2 lg:col-span-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Reason for Admission
                        </div>

                        <div class="mt-2 whitespace-pre-line text-sm font-medium leading-6 text-slate-900">
                            {{ $admission->admission_reason ?: '—' }}
                        </div>

                    </div>



                    <div class="sm:col-span-2 lg:col-span-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Provisional Diagnosis
                        </div>

                        <div class="mt-2 whitespace-pre-line text-sm font-medium leading-6 text-slate-900">
                            {{ $admission->provisional_diagnosis ?: '—' }}
                        </div>

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- EMERGENCY SOURCE --}}
            {{-- ========================================================= --}}

            @if ($emergencyVisit)

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Emergency Source
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Emergency visit from which this admission originated
                        </p>

                    </div>


                    <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Emergency No
                            </div>

                            <div class="mt-2 font-semibold text-slate-900">
                                {{ $emergencyVisit->emergency_no }}
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
                                Chief Complaint
                            </div>

                            <div class="mt-2 text-sm font-semibold text-slate-900">
                                {{ $emergencyVisit->chief_complaint ?: '—' }}
                            </div>

                        </div>


                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- LATEST EMERGENCY TRIAGE --}}
            {{-- ========================================================= --}}

            @if ($latestTriage)

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Latest Emergency Triage
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Last recorded Emergency nursing assessment before admission
                        </p>

                    </div>


                    <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-5">


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
                                    {{
                                        strtoupper(
                                            $latestTriage->triage_category
                                            ?: 'Uncategorised'
                                        )
                                    }}
                                </span>

                            </div>

                        </div>



                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                BP
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
                                RR
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

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- INPATIENT PHARMACY HISTORY --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h3 class="font-semibold text-slate-900">
                            Inpatient Pharmacy
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Medicines dispensed and returned during this admission
                        </p>

                    </div>


                    <div class="flex flex-wrap items-center gap-2">

                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                            {{ $pharmacySaleCount }}
                            sale{{ $pharmacySaleCount === 1 ? '' : 's' }}
                        </span>

                        @if ($pharmacyReturnCount > 0)

                            <span class="inline-flex rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                                {{ $pharmacyReturnCount }}
                                return{{ $pharmacyReturnCount === 1 ? '' : 's' }}
                            </span>

                        @endif


                        @if (in_array($admission->status, ['admitted', 'transferred'], true))

                            <a
                                href="{{ route('pharmacy.dispensing.create', ['admission_id' => $admission->id]) }}"
                                class="inline-flex items-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700"
                            >
                                Dispense Medicines
                            </a>

                        @endif

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[1050px] w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Sale No
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Date
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Medicines
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Qty
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Return Status
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @forelse ($pharmacySales as $sale)

                                @php

                                    $medicineNames =
                                        $sale->items
                                            ->pluck('medicine_name')
                                            ->filter()
                                            ->unique()
                                            ->values();


                                    $totalDispensedQty =
                                        $sale->items
                                            ->sum('quantity');


                                    $returnedQty =
                                        $sale->returns
                                            ?->flatMap(
                                                fn ($return) =>
                                                    $return->items ?? collect()
                                            )
                                            ->sum('quantity')
                                        ?? 0;


                                    $hasReturns =
                                        $returnedQty > 0;


                                    $fullyReturned =
                                        $totalDispensedQty > 0
                                        &&
                                        (float) $returnedQty >= (float) $totalDispensedQty;

                                @endphp


                                <tr class="hover:bg-slate-50">

                                    <td class="px-4 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $sale->sale_no }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ strtoupper($sale->status ?? '—') }}
                                        </div>

                                    </td>


                                    <td class="px-4 py-4 align-top text-sm text-slate-700">

                                        <div class="font-medium">
                                            {{ $sale->sale_at?->format('d M Y') ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $sale->sale_at?->format('h:i A') ?? '—' }}
                                        </div>

                                    </td>


                                    <td class="px-4 py-4 align-top">

                                        @if ($medicineNames->isNotEmpty())

                                            <div class="space-y-1 text-sm text-slate-700">

                                                @foreach ($medicineNames->take(3) as $medicineName)

                                                    <div>
                                                        {{ $medicineName }}
                                                    </div>

                                                @endforeach


                                                @if ($medicineNames->count() > 3)

                                                    <div class="text-xs font-medium text-slate-500">
                                                        +{{ $medicineNames->count() - 3 }} more
                                                    </div>

                                                @endif

                                            </div>

                                        @else

                                            <span class="text-sm text-slate-500">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-4 py-4 text-center align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $totalDispensedQty }}
                                        </div>

                                        @if ($returnedQty > 0)

                                            <div class="mt-1 text-xs text-amber-700">
                                                {{ $returnedQty }} returned
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-4 py-4 align-top">

                                        @if ($fullyReturned)

                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                Fully Returned
                                            </span>

                                        @elseif ($hasReturns)

                                            <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                                Partially Returned
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                No Return
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-4 py-4 align-top">

                                        <div class="flex flex-wrap justify-end gap-2">

                                            <a
                                                href="{{ route('pharmacy.dispensing.show', $sale) }}"
                                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                View
                                            </a>


                                            <a
                                                href="{{ route('pharmacy.dispensing.receipt', $sale) }}"
                                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                Receipt
                                            </a>


                                            @if (
                                                in_array($sale->status, ['completed', 'credit'], true)
                                                &&
                                                ! $fullyReturned
                                                &&
                                                in_array($admission->status, ['admitted', 'transferred'], true)
                                            )

                                                <a
                                                    href="{{ route('pharmacy.returns.create', $sale) }}"
                                                    class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700"
                                                >
                                                    Return
                                                </a>

                                            @endif

                                        </div>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="6"
                                        class="px-6 py-12 text-center"
                                    >

                                        <div class="text-sm font-semibold text-slate-700">
                                            No medicines dispensed for this admission
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            Inpatient pharmacy issues will appear here after dispensing.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- BED ALLOCATION HISTORY --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Bed Allocation History
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Ward and bed allocation history for this admission
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[900px] w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Ward
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Bed
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Allocated
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Released
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @forelse (
                                $admission->bedAllocations
                                    ->sortByDesc('allocated_at')
                                as $allocation
                            )

                                <tr class="hover:bg-slate-50">

                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">
                                        {{ $allocation->bed?->ward?->name ?? '—' }}
                                    </td>

                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">
                                        {{ $allocation->bed?->bed_number ?? '—' }}
                                    </td>

                                    <td class="px-4 py-4 text-sm text-slate-700">

                                        @if ($allocation->allocation_type)

                                            {{
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $allocation->allocation_type
                                                    )
                                                )
                                            }}

                                        @else

                                            —

                                        @endif

                                    </td>

                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        {{ $allocation->allocated_at?->format('d M Y, h:i A') ?? '—' }}
                                    </td>

                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        {{ $allocation->released_at?->format('d M Y, h:i A') ?? '—' }}
                                    </td>

                                    <td class="px-4 py-4">

                                        @if ($allocation->status === 'active')

                                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                Active
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                {{ ucfirst($allocation->status ?: 'Released') }}
                                            </span>

                                        @endif

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="6"
                                        class="px-6 py-10 text-center"
                                    >

                                        <div class="text-sm font-semibold text-slate-700">
                                            No bed allocation history found
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- IPD WORKFLOW --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="p-6">

                    <h3 class="font-semibold text-slate-900">
                        IPD Workflow
                    </h3>

                    @if ($admission->status === 'admitted')

                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            This patient is currently admitted.
                            Bed transfer, discharge, referral and inpatient clinical
                            documentation will be added to this admission workflow next.
                        </p>

                    @elseif ($admission->status === 'discharged')

                        <p class="mt-3 text-sm text-slate-600">
                            This admission has been discharged.
                        </p>

                    @elseif ($admission->status === 'referred')

                        <p class="mt-3 text-sm text-slate-600">
                            This patient has been referred from IPD.
                        </p>

                    @elseif ($admission->status === 'death')

                        <p class="mt-3 text-sm text-slate-600">
                            This admission has been closed following death.
                        </p>

                    @elseif ($admission->status === 'cancelled')

                        <p class="mt-3 text-sm text-slate-600">
                            This admission has been cancelled.
                        </p>

                    @else

                        <p class="mt-3 text-sm text-slate-600">
                            Current status: {{ $statusLabel }}.
                        </p>

                    @endif

                </div>

            </div>


        </div>

    </div>

</x-app-layout>