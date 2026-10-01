<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Emergency Visit Sheet
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $emergencyVisit->emergency_no }}
                </p>
            </div>

            <a
                href="{{ route('emergency.show', $emergencyVisit) }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to Emergency Visit
            </a>

        </div>

    </x-slot>


    @php

        $patient = $emergencyVisit->patient;

        $triage = $emergencyVisit->latestTriage;

        $note = $emergencyVisit->clinicalNote;

    @endphp


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- VALIDATION ERRORS --}}

            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 p-5">

                    <div class="font-semibold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- PATIENT DETAILS --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Patient Details
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Emergency attendance information
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
                            Arrival
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $emergencyVisit->arrival_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>
                    </div>

                </div>

            </div>


            <form
                method="POST"
                action="{{ route('emergency.clinical-note.store', $emergencyVisit) }}"
                class="space-y-6"
            >

                @csrf


                {{-- PRESENTING COMPLAINTS / HISTORY --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Presenting Complaints & History
                        </h3>

                    </div>


                    <div class="grid gap-6 p-6 lg:grid-cols-2">

                        <div>

                            <label class="text-sm font-semibold text-slate-700">
                                Presenting Complaints
                            </label>

                            <textarea
                                name="presenting_complaints"
                                rows="5"
                                class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old(
                                'presenting_complaints',
                                $note?->presenting_complaints
                                ?? $emergencyVisit->chief_complaint
                            ) }}</textarea>

                        </div>


                        <div>

                            <label class="text-sm font-semibold text-slate-700">
                                History
                            </label>

                            <textarea
                                name="history"
                                rows="5"
                                class="mt-2 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('history', $note?->history) }}</textarea>

                        </div>

                    </div>

                </div>


                {{-- VITAL SIGNS --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Clinical Vital Signs
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Initially populated from the latest Emergency triage record
                        </p>

                    </div>


                    <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-5">


                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                BP Systolic
                            </label>

                            <input
                                type="number"
                                name="blood_pressure_systolic"
                                value="{{ old(
                                    'blood_pressure_systolic',
                                    $note?->blood_pressure_systolic
                                    ?? $triage?->blood_pressure_systolic
                                ) }}"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >
                        </div>


                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                BP Diastolic
                            </label>

                            <input
                                type="number"
                                name="blood_pressure_diastolic"
                                value="{{ old(
                                    'blood_pressure_diastolic',
                                    $note?->blood_pressure_diastolic
                                    ?? $triage?->blood_pressure_diastolic
                                ) }}"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >
                        </div>


                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Pulse / min
                            </label>

                            <input
                                type="number"
                                name="pulse"
                                value="{{ old(
                                    'pulse',
                                    $note?->pulse
                                    ?? $triage?->pulse
                                ) }}"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >
                        </div>


                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Respiratory Rate
                            </label>

                            <input
                                type="number"
                                name="respiratory_rate"
                                value="{{ old(
                                    'respiratory_rate',
                                    $note?->respiratory_rate
                                    ?? $triage?->respiratory_rate
                                ) }}"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >
                        </div>


                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                SpO₂ %
                            </label>

                            <input
                                type="number"
                                name="spo2"
                                value="{{ old(
                                    'spo2',
                                    $note?->spo2
                                    ?? $triage?->spo2
                                ) }}"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >
                        </div>


                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Temperature °C
                            </label>

                            <input
                                type="number"
                                step="0.1"
                                name="temperature"
                                value="{{ old(
                                    'temperature',
                                    $note?->temperature
                                    ?? $triage?->temperature
                                ) }}"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >
                        </div>


                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                GCS
                            </label>

                            <input
                                type="number"
                                name="gcs"
                                value="{{ old(
                                    'gcs',
                                    $note?->gcs
                                    ?? $triage?->gcs
                                ) }}"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >
                        </div>


                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Pain Score
                            </label>

                            <input
                                type="number"
                                name="pain_score"
                                value="{{ old(
                                    'pain_score',
                                    $note?->pain_score
                                    ?? $triage?->pain_score
                                ) }}"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >
                        </div>


                        <div class="sm:col-span-2">

                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Oxygen Support
                            </label>

                            <input
                                type="text"
                                name="oxygen_support"
                                value="{{ old(
                                    'oxygen_support',
                                    $note?->oxygen_support
                                    ?? $triage?->oxygen_support
                                ) }}"
                                placeholder="Room Air / Nasal Prongs / Mask / NIV etc."
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >

                        </div>

                    </div>

                </div>


                {{-- EXAMINATION --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="font-semibold text-slate-900">
                            Examination
                        </h3>
                    </div>


                    <div class="grid gap-6 p-6 lg:grid-cols-2">

                        <div>

                            <label class="text-sm font-semibold text-slate-700">
                                General Examination
                            </label>

                            <textarea
                                name="general_examination"
                                rows="6"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >{{ old('general_examination', $note?->general_examination) }}</textarea>

                        </div>


                        <div>

                            <label class="text-sm font-semibold text-slate-700">
                                Systemic Examination
                            </label>

                            <textarea
                                name="systemic_examination"
                                rows="6"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >{{ old('systemic_examination', $note?->systemic_examination) }}</textarea>

                        </div>

                    </div>

                </div>


                {{-- ASSESSMENT / MANAGEMENT --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Assessment & Management
                        </h3>

                    </div>


                    <div class="space-y-6 p-6">


                        <div>

                            <label class="text-sm font-semibold text-slate-700">
                                Provisional / Working Diagnosis
                            </label>

                            <textarea
                                name="provisional_diagnosis"
                                rows="3"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >{{ old('provisional_diagnosis', $note?->provisional_diagnosis) }}</textarea>

                        </div>


                        <div class="grid gap-6 lg:grid-cols-2">

                            <div>

                                <label class="text-sm font-semibold text-slate-700">
                                    Investigations
                                </label>

                                <textarea
                                    name="investigations"
                                    rows="6"
                                    class="mt-2 w-full rounded-lg border-slate-300"
                                >{{ old('investigations', $note?->investigations) }}</textarea>

                            </div>


                            <div>

                                <label class="text-sm font-semibold text-slate-700">
                                    Treatment Given
                                </label>

                                <textarea
                                    name="treatment_given"
                                    rows="6"
                                    class="mt-2 w-full rounded-lg border-slate-300"
                                >{{ old('treatment_given', $note?->treatment_given) }}</textarea>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- DISPOSITION --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Disposition
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Record the outcome of this Emergency attendance
                        </p>

                    </div>


                    <div class="grid gap-6 p-6 lg:grid-cols-2">

                        <div>

                            <label class="text-sm font-semibold text-slate-700">
                                Outcome
                            </label>

                            <select
                                name="disposition"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >

                                <option value="">Select disposition</option>

                                @foreach ([
                                    'discharged' => 'Discharged from Emergency',
                                    'admitted' => 'Admitted to IPD',
                                    'observation' => 'Emergency Observation',
                                    'referred' => 'Referred',
                                    'lama' => 'LAMA / DAMA',
                                    'absconded' => 'Absconded',
                                    'death' => 'Death in Emergency',
                                ] as $value => $label)

                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            old(
                                                'disposition',
                                                $note?->disposition
                                            ) === $value
                                        )
                                    >
                                        {{ $label }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div>

                            <label class="text-sm font-semibold text-slate-700">
                                Discharge / Referral Advice
                            </label>

                            <textarea
                                name="discharge_advice"
                                rows="5"
                                class="mt-2 w-full rounded-lg border-slate-300"
                            >{{ old('discharge_advice', $note?->discharge_advice) }}</textarea>

                        </div>

                    </div>

                </div>


                {{-- SAVE --}}

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('emergency.show', $emergencyVisit) }}"
                        class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        {{ $note ? 'Update Emergency Visit Sheet' : 'Save Emergency Visit Sheet' }}
                    </button>

                </div>


            </form>

        </div>

    </div>

</x-app-layout>