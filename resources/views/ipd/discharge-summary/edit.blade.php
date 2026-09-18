<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Discharge Summary
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $admission->admission_no }}
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                @if ($summary)

                    <a
                        href="{{ route('ipd.discharge-summary.show', $admission) }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        View Summary
                    </a>

                @endif


                <a
                    href="{{ route('ipd.show', $admission) }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Back to IPD Admission
                </a>

            </div>

        </div>

    </x-slot>


    @php

        $patient =
            $admission->patient;


        $consultant =
            $admission->consultant;


        $emergencyVisit =
            $admission->emergencyVisit;


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


        $dischargeDate =
            $admission->discharged_at
            ??
            $admission->closed_at;


        $chiefComplaint =
            $emergencyVisit?->chief_complaint
            ??
            '—';

    @endphp



    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- SUCCESS MESSAGE --}}
            {{-- ========================================================= --}}

            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



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
            {{-- HOSPITAL HEADER --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col items-center px-6 py-6 text-center">


                    {{-- HOSPITAL LOGO --}}

                    <img
                        src="{{ asset('images/TCH_favicon.png') }}"
                        alt="Tura Christian Hospital Logo"
                        class="mb-3 h-20 w-auto object-contain"
                        onerror="this.style.display='none';"
                    >


                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                        Tura Christian Hospital
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Tura, West Garo Hills, Meghalaya
                    </p>

                    <div class="mt-4 text-sm font-bold uppercase tracking-[0.2em] text-slate-700">
                        Discharge Summary
                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- PATIENT DETAILS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-4">

                    <h3 class="font-semibold text-slate-900">
                        Patient Details
                    </h3>

                </div>


                <div class="grid gap-x-8 gap-y-5 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    {{-- PATIENT NAME --}}

                    <div class="lg:col-span-2">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Patient Name
                        </div>

                        <div class="mt-1 font-bold text-slate-900">
                            {{ $patient?->full_name ?? 'Unknown Patient' }}
                        </div>

                    </div>



                    {{-- UHID --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            UHID
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $patient?->uhid ?? '—' }}
                        </div>

                    </div>



                    {{-- MRD --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            MRD
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $patient?->mrd_number ?? '—' }}
                        </div>

                    </div>



                    {{-- AGE / SEX --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Age / Sex
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">

                            @if ($patient?->age !== null)

                                {{ $patient->age }} years

                            @elseif ($patient?->date_of_birth)

                                {{ $patient->date_of_birth->age }} years

                            @else

                                —

                            @endif

                            /

                            {{ $patient?->sex ? ucfirst($patient->sex) : '—' }}

                        </div>

                    </div>



                    {{-- MOBILE --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Mobile
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $patient?->phone ?? '—' }}
                        </div>

                    </div>



                    {{-- IPD NUMBER --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            IPD No
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $admission->admission_no }}
                        </div>

                    </div>



                    {{-- DEPARTMENT --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Department
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $admission->department?->name ?? '—' }}
                        </div>

                    </div>



                    {{-- ADMISSION DATE --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Admission Date
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $admission->admitted_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>

                    </div>



                    {{-- DISCHARGE DATE --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Discharge Date
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $dischargeDate?->format('d M Y, h:i A') ?? '—' }}
                        </div>

                    </div>



                    {{-- CONSULTANT --}}

                    <div class="lg:col-span-2">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Consultant
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $consultantName }}
                        </div>

                        @if ($consultant?->speciality)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $consultant->speciality }}
                            </div>

                        @endif

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- CHIEF COMPLAINT --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-4">

                    <h3 class="font-semibold text-slate-900">
                        Chief Complaint
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Taken from the Emergency registration.
                    </p>

                </div>


                <div class="p-6">

                    <div class="whitespace-pre-line text-sm leading-6 text-slate-800">
                        {{ $chiefComplaint }}
                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- DISCHARGE SUMMARY FORM --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('ipd.discharge-summary.update', $admission) }}"
                class="space-y-6"
            >

                @csrf
                @method('PUT')



                {{-- ===================================================== --}}
                {{-- SUMMARY IN BRIEF --}}
                {{-- ===================================================== --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-4">

                        <h3 class="font-semibold text-slate-900">
                            Summary in Brief
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Brief clinical summary of the admission, important findings,
                            treatment and progress.
                        </p>

                    </div>


                    <div class="p-6">

                        <textarea
                            id="hospital_course"
                            name="hospital_course"
                            rows="7"
                            maxlength="20000"
                            placeholder="Example: Patient was admitted with cough and fever. Evaluation showed... Patient was treated with... Clinical condition gradually improved..."
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('hospital_course', $summary?->hospital_course) }}</textarea>

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- CONDITION AT DISCHARGE --}}
                {{-- ===================================================== --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-4">

                        <h3 class="font-semibold text-slate-900">
                            Condition at Discharge
                        </h3>

                    </div>


                    <div class="p-6">

                        <textarea
                            id="condition_at_discharge"
                            name="condition_at_discharge"
                            rows="4"
                            maxlength="5000"
                            placeholder="Example: Clinically improved, afebrile, haemodynamically stable and ambulatory."
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('condition_at_discharge', $summary?->condition_at_discharge) }}</textarea>

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- MEDICATION RECOMMENDATION --}}
                {{-- ===================================================== --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-4">

                        <h3 class="font-semibold text-slate-900">
                            Medication Recommendation
                        </h3>

                    </div>


                    <div class="p-6">

                        <textarea
                            id="discharge_medications"
                            name="discharge_medications"
                            rows="6"
                            maxlength="20000"
                            placeholder="Example:
1. Tab. Amlodipine 5 mg — once daily
2. Tab. Pantoprazole 40 mg — once daily before breakfast
3. Tab. Paracetamol 500 mg — as required"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('discharge_medications', $summary?->discharge_medications) }}</textarea>

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- REVIEW DATE --}}
                {{-- ===================================================== --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="grid gap-6 p-6 md:grid-cols-2">


                        <div>

                            <label
                                for="review_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Review Date
                            </label>

                            <input
                                id="review_date"
                                name="review_date"
                                type="date"
                                value="{{ old(
                                    'review_date',
                                    $summary?->review_date?->format('Y-m-d')
                                ) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <div class="text-sm font-semibold text-slate-700">
                                Consultant
                            </div>

                            <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">

                                <div class="font-semibold text-slate-900">
                                    {{ $consultantName }}
                                </div>

                                @if ($consultant?->speciality)

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $consultant->speciality }}
                                    </div>

                                @endif

                            </div>

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- FINAL DIAGNOSIS - OPTIONAL --}}
                {{-- ===================================================== --}}
                {{--
                    The existing database field is retained.

                    It is not required for the simplified one-page format,
                    but keeping it here allows useful diagnosis information
                    to remain available without changing the database again.
                --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-4">

                        <h3 class="font-semibold text-slate-900">
                            Diagnosis
                            <span class="font-normal text-slate-400">
                                (optional)
                            </span>
                        </h3>

                    </div>


                    <div class="p-6">

                        <textarea
                            id="final_diagnosis"
                            name="final_diagnosis"
                            rows="3"
                            maxlength="10000"
                            placeholder="Final diagnosis..."
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('final_diagnosis', $summary?->final_diagnosis) }}</textarea>

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- ACTIONS --}}
                {{-- ===================================================== --}}

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('ipd.show', $admission) }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>


                    @if ($summary)

                        <a
                            href="{{ route('ipd.discharge-summary.show', $admission) }}"
                            class="rounded-lg border border-blue-300 bg-blue-50 px-5 py-2.5 text-center text-sm font-semibold text-blue-700 hover:bg-blue-100"
                        >
                            View Summary
                        </a>

                    @endif


                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        {{ $summary
                            ? 'Update Discharge Summary'
                            : 'Save Discharge Summary'
                        }}
                    </button>

                </div>


            </form>


        </div>

    </div>

</x-app-layout>