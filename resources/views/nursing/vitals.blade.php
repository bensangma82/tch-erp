<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Record Vitals
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Nursing assessment before the patient proceeds to the doctor.
                </p>

            </div>


            <a
                href="{{ route('nursing.index') }}"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Nursing Station
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- VALIDATION ERRORS --}}
            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 p-5">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- PATIENT SUMMARY --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="font-semibold text-gray-800">
                                Patient
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Encounter: {{ $encounter->encounter_no }}
                            </p>

                        </div>


                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-lg font-bold text-white">
                            {{ $encounter->queue_number }}
                        </div>

                    </div>

                </div>


                <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            UHID
                        </div>

                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $encounter->patient->uhid }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            MRD
                        </div>

                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $encounter->patient->mrd_number ?: '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Patient
                        </div>

                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $encounter->patient->full_name }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            {{ $encounter->patient->age !== null ? $encounter->patient->age . ' yrs' : 'Age —' }}
                            /
                            {{ $encounter->patient->sex ?: '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Department / Doctor
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $encounter->department?->name ?? '—' }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                        </div>
                    </div>

                </div>

            </div>


            {{-- LATEST VITALS --}}
            @php
                $previousVital = $encounter->vitals
                    ->sortByDesc('recorded_at')
                    ->first();
            @endphp

            @if ($previousVital)

                <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">

                    <div class="font-semibold text-blue-900">
                        Previous vitals recorded
                    </div>

                    <div class="mt-2 text-sm text-blue-800">

                        BP:
                        {{ $previousVital->systolic_bp ?? '—' }}
                        /
                        {{ $previousVital->diastolic_bp ?? '—' }}

                        &nbsp; | &nbsp;

                        Pulse:
                        {{ $previousVital->pulse_rate ?? '—' }}

                        &nbsp; | &nbsp;

                        SpO₂:
                        {{ $previousVital->spo2 ?? '—' }}%

                        &nbsp; | &nbsp;

                        Weight:
                        {{ $previousVital->weight_kg ?? '—' }} kg

                    </div>

                </div>

            @endif


            <form
                method="POST"
                action="{{ route('nursing.vitals.store', $encounter) }}"
                class="space-y-6"
            >

                @csrf


                {{-- VITALS --}}
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            Vital Signs
                        </h3>

                    </div>


                    <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">


                        {{-- BP --}}
                        <div class="sm:col-span-2">

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Blood Pressure
                            </label>

                            <div class="grid grid-cols-2 gap-3">

                                <div>
                                    <input
                                        type="number"
                                        name="systolic_bp"
                                        value="{{ old('systolic_bp') }}"
                                        min="30"
                                        max="300"
                                        placeholder="Systolic"
                                        class="w-full rounded-lg border-gray-300"
                                    >

                                    <div class="mt-1 text-xs text-gray-500">
                                        mmHg
                                    </div>
                                </div>

                                <div>
                                    <input
                                        type="number"
                                        name="diastolic_bp"
                                        value="{{ old('diastolic_bp') }}"
                                        min="20"
                                        max="200"
                                        placeholder="Diastolic"
                                        class="w-full rounded-lg border-gray-300"
                                    >

                                    <div class="mt-1 text-xs text-gray-500">
                                        mmHg
                                    </div>
                                </div>

                            </div>

                        </div>


                        {{-- PULSE --}}
                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Pulse Rate
                            </label>

                            <input
                                type="number"
                                name="pulse_rate"
                                value="{{ old('pulse_rate') }}"
                                min="20"
                                max="250"
                                placeholder="e.g. 78"
                                class="w-full rounded-lg border-gray-300"
                            >

                            <div class="mt-1 text-xs text-gray-500">
                                beats/min
                            </div>

                        </div>


                        {{-- RR --}}
                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Respiratory Rate
                            </label>

                            <input
                                type="number"
                                name="respiratory_rate"
                                value="{{ old('respiratory_rate') }}"
                                min="5"
                                max="80"
                                placeholder="e.g. 18"
                                class="w-full rounded-lg border-gray-300"
                            >

                            <div class="mt-1 text-xs text-gray-500">
                                breaths/min
                            </div>

                        </div>


                        {{-- SPO2 --}}
                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                SpO₂
                            </label>

                            <input
                                type="number"
                                name="spo2"
                                value="{{ old('spo2') }}"
                                min="20"
                                max="100"
                                placeholder="e.g. 98"
                                class="w-full rounded-lg border-gray-300"
                            >

                            <div class="mt-1 text-xs text-gray-500">
                                %
                            </div>

                        </div>


                        {{-- TEMP --}}
                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Temperature
                            </label>

                            <input
                                type="number"
                                name="temperature"
                                value="{{ old('temperature') }}"
                                min="30"
                                max="45"
                                step="0.1"
                                placeholder="e.g. 36.8"
                                class="w-full rounded-lg border-gray-300"
                            >

                            <div class="mt-1 text-xs text-gray-500">
                                °C
                            </div>

                        </div>


                        {{-- WEIGHT --}}
                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Weight
                            </label>

                            <input
                                id="weight_kg"
                                type="number"
                                name="weight_kg"
                                value="{{ old('weight_kg') }}"
                                min="0.5"
                                max="500"
                                step="0.01"
                                placeholder="e.g. 65"
                                class="w-full rounded-lg border-gray-300"
                            >

                            <div class="mt-1 text-xs text-gray-500">
                                kg
                            </div>

                        </div>


                        {{-- HEIGHT --}}
                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Height
                            </label>

                            <input
                                id="height_cm"
                                type="number"
                                name="height_cm"
                                value="{{ old('height_cm') }}"
                                min="20"
                                max="250"
                                step="0.1"
                                placeholder="e.g. 165"
                                class="w-full rounded-lg border-gray-300"
                            >

                            <div class="mt-1 text-xs text-gray-500">
                                cm
                            </div>

                        </div>


                        {{-- BMI --}}
                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                BMI
                            </label>

                            <input
                                id="bmi"
                                type="text"
                                readonly
                                value=""
                                placeholder="Calculated automatically"
                                class="w-full rounded-lg border-gray-300 bg-gray-100"
                            >

                            <div class="mt-1 text-xs text-gray-500">
                                kg/m²
                            </div>

                        </div>


                        {{-- BLOOD GLUCOSE --}}
                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Blood Glucose
                            </label>

                            <input
                                type="number"
                                name="blood_glucose"
                                value="{{ old('blood_glucose') }}"
                                min="10"
                                max="2000"
                                step="0.01"
                                placeholder="Optional"
                                class="w-full rounded-lg border-gray-300"
                            >

                            <div class="mt-1 text-xs text-gray-500">
                                mg/dL
                            </div>

                        </div>

                    </div>

                </div>


                {{-- NURSING NOTES --}}
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            Nursing Notes
                        </h3>

                    </div>


                    <div class="p-6">

                        <textarea
                            name="notes"
                            rows="3"
                            placeholder="Optional nursing observations"
                            class="w-full rounded-lg border-gray-300"
                        >{{ old('notes') }}</textarea>

                    </div>

                </div>


                {{-- ACTIONS --}}
                <div class="flex justify-end gap-3">

                    <a
                        href="{{ route('nursing.index') }}"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Save Vitals
                    </button>

                </div>


            </form>

        </div>

    </div>


    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function ()
            {
                const weight =
                    document.getElementById(
                        'weight_kg'
                    );

                const height =
                    document.getElementById(
                        'height_cm'
                    );

                const bmi =
                    document.getElementById(
                        'bmi'
                    );


                function calculateBmi()
                {
                    if (
                        !weight ||
                        !height ||
                        !bmi
                    )
                    {
                        return;
                    }

                    const weightValue =
                        parseFloat(
                            weight.value
                        );

                    const heightValue =
                        parseFloat(
                            height.value
                        );


                    if (
                        isNaN(weightValue) ||
                        isNaN(heightValue) ||
                        heightValue <= 0
                    )
                    {
                        bmi.value = '';
                        return;
                    }


                    const heightMeters =
                        heightValue / 100;

                    const bmiValue =
                        weightValue /
                        (
                            heightMeters *
                            heightMeters
                        );


                    bmi.value =
                        bmiValue.toFixed(1);
                }


                weight.addEventListener(
                    'input',
                    calculateBmi
                );

                height.addEventListener(
                    'input',
                    calculateBmi
                );


                calculateBmi();
            }
        );

    </script>

</x-app-layout>