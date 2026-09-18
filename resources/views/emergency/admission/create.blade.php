<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Admit Emergency Patient
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

        $patient =
            $emergencyVisit->patient;

        $latestTriage =
            $emergencyVisit->latestTriage;


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

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">


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
            {{-- PATIENT SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Patient Summary
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Emergency registration details before inpatient admission
                    </p>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    {{-- PATIENT --}}

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



                    {{-- EMERGENCY NUMBER --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Emergency No
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $emergencyVisit->emergency_no }}
                        </div>

                    </div>



                    {{-- ARRIVAL --}}

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Arrival
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $emergencyVisit->arrival_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>

                    </div>



                    {{-- CHIEF COMPLAINT --}}

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
            {{-- TRIAGE SUMMARY --}}
            {{-- ========================================================= --}}

            @if ($latestTriage)

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Latest Emergency Triage
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Latest nursing assessment before admission
                        </p>

                    </div>


                    <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-6">


                        {{-- CATEGORY --}}

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



                        {{-- BP --}}

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



                        {{-- PULSE --}}

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Pulse
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->pulse ?? '—' }}
                            </div>

                        </div>



                        {{-- RR --}}

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                RR
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->respiratory_rate ?? '—' }}
                            </div>

                        </div>



                        {{-- SPO2 --}}

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



                        {{-- GCS --}}

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                GCS
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $latestTriage->gcs ?? '—' }}
                            </div>

                        </div>


                    </div>

                </div>

            @else

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                    <div class="font-semibold text-amber-800">
                        Triage not recorded
                    </div>

                    <div class="mt-1 text-sm text-amber-700">
                        This patient has not yet had Emergency triage recorded.
                        Admission can still proceed if clinically required.
                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- ADMISSION FORM --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('emergency.admission.store', $emergencyVisit) }}"
                class="space-y-6"
            >

                @csrf


                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Admission Details
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Select the admitting department, consultant, ward and bed.
                        </p>

                    </div>


                    <div class="grid gap-6 p-6 md:grid-cols-2">


                        {{-- ================================================= --}}
                        {{-- ADMISSION DATE --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="admitted_at"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Admission Date & Time *
                            </label>

                            <input
                                id="admitted_at"
                                name="admitted_at"
                                type="datetime-local"
                                required
                                value="{{ old(
                                    'admitted_at',
                                    now()->format('Y-m-d\TH:i')
                                ) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        {{-- ================================================= --}}
                        {{-- DEPARTMENT --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="department_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Department *
                            </label>

                            <select
                                id="department_id"
                                name="department_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select department
                                </option>


                                @foreach ($departments as $department)

                                    <option
                                        value="{{ $department->id }}"
                                        @selected(
                                            old('department_id')
                                            ==
                                            $department->id
                                        )
                                    >
                                        {{ $department->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>



                        {{-- ================================================= --}}
                        {{-- CONSULTANT --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="consultant_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Consultant
                            </label>

                            <select
                                id="consultant_id"
                                name="consultant_id"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select consultant
                                </option>


                                @foreach ($consultants as $consultant)

                                    @php

                                        $consultantName =
                                            trim(
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
                                            );

                                    @endphp


                                    <option
                                        value="{{ $consultant->id }}"
                                        @selected(
                                            old('consultant_id')
                                            ==
                                            $consultant->id
                                        )
                                    >

                                        {{ $consultantName }}

                                        @if ($consultant->speciality)

                                            — {{ $consultant->speciality }}

                                        @endif

                                    </option>

                                @endforeach

                            </select>


                            <p class="mt-2 text-xs text-slate-500">
                                Only active employees marked as doctors are listed.
                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- WARD --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="ward_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Ward *
                            </label>

                            <select
                                id="ward_id"
                                name="ward_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select ward
                                </option>


                                @foreach ($wards as $ward)

                                    <option
                                        value="{{ $ward->id }}"
                                        @selected(
                                            old('ward_id')
                                            ==
                                            $ward->id
                                        )
                                    >
                                        {{ $ward->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>



                        {{-- ================================================= --}}
                        {{-- AVAILABLE BED --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label
                                for="bed_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Available Bed *
                            </label>

                            <select
                                id="bed_id"
                                name="bed_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select ward first
                                </option>


                                @foreach ($availableBeds as $bed)

                                    <option
                                        value="{{ $bed->id }}"
                                        data-ward-id="{{ $bed->ward_id }}"
                                        @selected(
                                            old('bed_id')
                                            ==
                                            $bed->id
                                        )
                                    >
                                        {{ $bed->ward?->name ?? 'Unknown Ward' }}
                                        — {{ $bed->bed_number }}
                                    </option>

                                @endforeach

                            </select>


                            <p
                                id="bed-help"
                                class="mt-2 text-xs text-slate-500"
                            >
                                Only active beds currently marked available are listed.
                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- REASON FOR ADMISSION --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label
                                for="admission_reason"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Reason for Admission
                            </label>

                            <textarea
                                id="admission_reason"
                                name="admission_reason"
                                rows="4"
                                maxlength="5000"
                                placeholder="Reason requiring inpatient admission..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('admission_reason') }}</textarea>

                        </div>



                        {{-- ================================================= --}}
                        {{-- PROVISIONAL DIAGNOSIS --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label
                                for="provisional_diagnosis"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Provisional Diagnosis
                            </label>

                            <textarea
                                id="provisional_diagnosis"
                                name="provisional_diagnosis"
                                rows="4"
                                maxlength="5000"
                                placeholder="Working diagnosis at the time of admission..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('provisional_diagnosis') }}</textarea>

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- BED SAFETY NOTE --}}
                {{-- ===================================================== --}}

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                    <div class="font-semibold text-amber-800">
                        Bed allocation safety
                    </div>

                    <div class="mt-2 text-sm leading-6 text-amber-700">

                        The selected bed will be checked again at the moment
                        of admission.

                        If another user has already occupied that bed, the
                        admission will be stopped and you will be asked to
                        select another available bed.

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- ACTIONS --}}
                {{-- ===================================================== --}}

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('emergency.show', $emergencyVisit) }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        onclick="return confirm('Admit this Emergency patient and allocate the selected bed?');"
                        class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                    >
                        Admit Patient
                    </button>

                </div>

            </form>


        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- WARD -> BED FILTER --}}
    {{-- ============================================================= --}}

    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const wardSelect =
                    document.getElementById(
                        'ward_id'
                    );


                const bedSelect =
                    document.getElementById(
                        'bed_id'
                    );


                if (
                    !wardSelect
                    ||
                    !bedSelect
                ) {

                    return;
                }


                /*
                 * Preserve old bed selection after validation failure.
                 */

                const oldBedId =
                    @json(
                        (string) old(
                            'bed_id',
                            ''
                        )
                    );


                /*
                 * Read all available bed options before rebuilding
                 * the dropdown.
                 */

                const allBeds =
                    Array.from(
                        bedSelect.querySelectorAll(
                            'option[data-ward-id]'
                        )
                    )
                    .map(
                        function (option) {

                            return {

                                value:
                                    String(
                                        option.value
                                    ),

                                text:
                                    option
                                        .textContent
                                        .trim(),

                                wardId:
                                    String(
                                        option.dataset.wardId
                                    ),

                            };

                        }
                    );


                function rebuildBedOptions()
                {
                    const selectedWardId =
                        String(
                            wardSelect.value
                            ||
                            ''
                        );


                    const currentBedId =
                        String(
                            bedSelect.value
                            ||
                            oldBedId
                            ||
                            ''
                        );


                    bedSelect.innerHTML =
                        '';


                    const placeholder =
                        document.createElement(
                            'option'
                        );


                    placeholder.value =
                        '';


                    /*
                     * No ward selected.
                     */

                    if (!selectedWardId)
                    {
                        placeholder.textContent =
                            'Select ward first';


                        bedSelect.appendChild(
                            placeholder
                        );


                        bedSelect.disabled =
                            true;


                        return;
                    }


                    /*
                     * Beds belonging to selected ward.
                     */

                    const matchingBeds =
                        allBeds.filter(
                            function (bed) {

                                return (
                                    bed.wardId
                                    ===
                                    selectedWardId
                                );

                            }
                        );


                    placeholder.textContent =
                        matchingBeds.length > 0
                            ? 'Select available bed'
                            : 'No available beds in this ward';


                    bedSelect.appendChild(
                        placeholder
                    );


                    matchingBeds.forEach(
                        function (bed) {

                            const option =
                                document.createElement(
                                    'option'
                                );


                            option.value =
                                bed.value;


                            option.textContent =
                                bed.text;


                            if (
                                currentBedId !== ''
                                &&
                                bed.value === currentBedId
                            )
                            {
                                option.selected =
                                    true;
                            }


                            bedSelect.appendChild(
                                option
                            );

                        }
                    );


                    /*
                     * Disable when ward has no available beds.
                     */

                    bedSelect.disabled =
                        matchingBeds.length === 0;
                }


                wardSelect.addEventListener(
                    'change',
                    function () {

                        /*
                         * Clear old bed selection when user changes ward.
                         */

                        bedSelect.value =
                            '';


                        rebuildBedOptions();

                    }
                );


                /*
                 * Initial load.
                 */

                rebuildBedOptions();

            }
        );

    </script>

</x-app-layout>