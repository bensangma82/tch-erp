<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Emergency Triage
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



    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">


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
            {{-- PATIENT SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Emergency No
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $emergencyVisit->emergency_no }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Patient
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $emergencyVisit->patient?->name ?? 'Unknown Patient' }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            Patient ID:
                            {{ $emergencyVisit->patient_id }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Arrival
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $emergencyVisit->arrival_at?->format('d M Y') ?? '—' }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            {{ $emergencyVisit->arrival_at?->format('h:i A') ?? '—' }}
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



            {{-- ========================================================= --}}
            {{-- TRIAGE FORM --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('emergency.triage.store', $emergencyVisit) }}"
                class="space-y-6"
            >

                @csrf


                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Triage Category
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Assign the patient's emergency priority.
                        </p>

                    </div>


                    <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-5">


                        @foreach ([
                            'red' => [
                                'label' => 'Red',
                                'description' => 'Immediate / Resuscitation',
                                'classes' => 'border-red-300 bg-red-50 text-red-800',
                            ],

                            'orange' => [
                                'label' => 'Orange',
                                'description' => 'Very Urgent',
                                'classes' => 'border-orange-300 bg-orange-50 text-orange-800',
                            ],

                            'yellow' => [
                                'label' => 'Yellow',
                                'description' => 'Urgent',
                                'classes' => 'border-yellow-300 bg-yellow-50 text-yellow-800',
                            ],

                            'green' => [
                                'label' => 'Green',
                                'description' => 'Less Urgent',
                                'classes' => 'border-green-300 bg-green-50 text-green-800',
                            ],

                            'blue' => [
                                'label' => 'Blue',
                                'description' => 'Non-Urgent',
                                'classes' => 'border-blue-300 bg-blue-50 text-blue-800',
                            ],
                        ] as $value => $category)

                            <label
                                class="cursor-pointer rounded-xl border-2 p-4 transition hover:shadow-sm {{ $category['classes'] }}"
                            >

                                <div class="flex items-start gap-3">

                                    <input
                                        type="radio"
                                        name="triage_category"
                                        value="{{ $value }}"
                                        required
                                        @checked(old('triage_category') === $value)
                                        class="mt-1"
                                    >

                                    <div>

                                        <div class="font-bold uppercase">
                                            {{ $category['label'] }}
                                        </div>

                                        <div class="mt-1 text-xs">
                                            {{ $category['description'] }}
                                        </div>

                                    </div>

                                </div>

                            </label>

                        @endforeach


                    </div>

                </div>



                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Vital Signs
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Record the patient's initial emergency observations.
                        </p>

                    </div>


                    <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-4">


                        <div>

                            <label
                                for="blood_pressure_systolic"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                BP Systolic
                            </label>

                            <input
                                id="blood_pressure_systolic"
                                name="blood_pressure_systolic"
                                type="number"
                                min="40"
                                max="300"
                                value="{{ old('blood_pressure_systolic') }}"
                                placeholder="120"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                            <div class="mt-1 text-xs text-slate-400">
                                mmHg
                            </div>

                        </div>



                        <div>

                            <label
                                for="blood_pressure_diastolic"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                BP Diastolic
                            </label>

                            <input
                                id="blood_pressure_diastolic"
                                name="blood_pressure_diastolic"
                                type="number"
                                min="20"
                                max="200"
                                value="{{ old('blood_pressure_diastolic') }}"
                                placeholder="80"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                            <div class="mt-1 text-xs text-slate-400">
                                mmHg
                            </div>

                        </div>



                        <div>

                            <label
                                for="pulse"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Pulse
                            </label>

                            <input
                                id="pulse"
                                name="pulse"
                                type="number"
                                min="20"
                                max="300"
                                value="{{ old('pulse') }}"
                                placeholder="80"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                            <div class="mt-1 text-xs text-slate-400">
                                beats/min
                            </div>

                        </div>



                        <div>

                            <label
                                for="respiratory_rate"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Respiratory Rate
                            </label>

                            <input
                                id="respiratory_rate"
                                name="respiratory_rate"
                                type="number"
                                min="5"
                                max="100"
                                value="{{ old('respiratory_rate') }}"
                                placeholder="18"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                            <div class="mt-1 text-xs text-slate-400">
                                breaths/min
                            </div>

                        </div>



                        <div>

                            <label
                                for="spo2"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                SpO₂
                            </label>

                            <input
                                id="spo2"
                                name="spo2"
                                type="number"
                                min="0"
                                max="100"
                                value="{{ old('spo2') }}"
                                placeholder="98"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                            <div class="mt-1 text-xs text-slate-400">
                                %
                            </div>

                        </div>



                        <div>

                            <label
                                for="temperature"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Temperature
                            </label>

                            <input
                                id="temperature"
                                name="temperature"
                                type="number"
                                min="25"
                                max="45"
                                step="0.1"
                                value="{{ old('temperature') }}"
                                placeholder="37.0"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                            <div class="mt-1 text-xs text-slate-400">
                                °C
                            </div>

                        </div>



                        <div>

                            <label
                                for="gcs"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                GCS
                            </label>

                            <input
                                id="gcs"
                                name="gcs"
                                type="number"
                                min="3"
                                max="15"
                                value="{{ old('gcs') }}"
                                placeholder="15"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                            <div class="mt-1 text-xs text-slate-400">
                                3–15
                            </div>

                        </div>



                        <div>

                            <label
                                for="pain_score"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Pain Score
                            </label>

                            <input
                                id="pain_score"
                                name="pain_score"
                                type="number"
                                min="0"
                                max="10"
                                value="{{ old('pain_score') }}"
                                placeholder="0"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                            <div class="mt-1 text-xs text-slate-400">
                                0–10
                            </div>

                        </div>


                    </div>

                </div>



                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Additional Assessment
                        </h3>

                    </div>


                    <div class="grid gap-6 p-6 md:grid-cols-2">


                        <div>

                            <label
                                for="oxygen_support"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Oxygen Support
                            </label>

                            <select
                                id="oxygen_support"
                                name="oxygen_support"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                                <option value="">
                                    None / Not Recorded
                                </option>

                                <option
                                    value="Room Air"
                                    @selected(old('oxygen_support') === 'Room Air')
                                >
                                    Room Air
                                </option>

                                <option
                                    value="Nasal Cannula"
                                    @selected(old('oxygen_support') === 'Nasal Cannula')
                                >
                                    Nasal Cannula
                                </option>

                                <option
                                    value="Face Mask"
                                    @selected(old('oxygen_support') === 'Face Mask')
                                >
                                    Face Mask
                                </option>

                                <option
                                    value="Non-Rebreather Mask"
                                    @selected(old('oxygen_support') === 'Non-Rebreather Mask')
                                >
                                    Non-Rebreather Mask
                                </option>

                                <option
                                    value="HFNC"
                                    @selected(old('oxygen_support') === 'HFNC')
                                >
                                    HFNC
                                </option>

                                <option
                                    value="NIV / BiPAP"
                                    @selected(old('oxygen_support') === 'NIV / BiPAP')
                                >
                                    NIV / BiPAP
                                </option>

                                <option
                                    value="Mechanical Ventilation"
                                    @selected(old('oxygen_support') === 'Mechanical Ventilation')
                                >
                                    Mechanical Ventilation
                                </option>

                            </select>

                        </div>



                        <div>

                            <label
                                for="notes"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Triage Notes
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="5"
                                maxlength="5000"
                                placeholder="General appearance, airway, breathing, circulation, neurological status or other observations..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >{{ old('notes') }}</textarea>

                        </div>


                    </div>

                </div>



                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                    <div class="font-semibold text-amber-800">
                        Triage record
                    </div>

                    <div class="mt-2 text-sm leading-6 text-amber-700">
                        Saving this assessment will update the emergency visit status to
                        <strong>Triaged</strong>.
                        Additional triage assessments may be recorded later if the patient's
                        condition changes.
                    </div>

                </div>



                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('emergency.show', $emergencyVisit) }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
                    >
                        Save Triage Assessment
                    </button>

                </div>

            </form>


        </div>

    </div>

</x-app-layout>