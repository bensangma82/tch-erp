<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Register Emergency Patient
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Select an existing patient or register a new patient first
                </p>

            </div>


            <a
                href="{{ route('emergency.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Emergency Queue
            </a>

        </div>

    </x-slot>


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
            {{-- EMERGENCY REGISTRATION --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Emergency Registration
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Select an existing patient and record arrival details.
                            </p>

                        </div>


                        @if (
                            in_array(
                                auth()->user()?->role,
                                [
                                    'reception',
                                    'admin',
                                ],
                                true
                            )
                        )

                            <a
                                href="{{ route(
                                    'patients.create',
                                    [
                                        'return_to' =>
                                            route('emergency.create'),
                                    ]
                                ) }}"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                            >
                                + Register New Patient
                            </a>

                        @endif

                    </div>

                </div>



                <form
                    method="POST"
                    action="{{ route('emergency.store') }}"
                    class="space-y-6 p-6"
                >

                    @csrf


                    <div class="grid gap-6 md:grid-cols-2">


                        {{-- ================================================= --}}
                        {{-- PATIENT --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">

                                <label
                                    for="patient_id"
                                    class="block text-sm font-semibold text-slate-700"
                                >
                                    Patient *
                                </label>


                                @if (
                                    in_array(
                                        auth()->user()?->role,
                                        [
                                            'reception',
                                            'admin',
                                        ],
                                        true
                                    )
                                )

                                    <a
                                        href="{{ route(
                                            'patients.create',
                                            [
                                                'return_to' =>
                                                    route('emergency.create'),
                                            ]
                                        ) }}"
                                        class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                                    >
                                        Patient not registered? Register new patient
                                    </a>

                                @endif

                            </div>


                            <select
                                id="patient_id"
                                name="patient_id"
                                required
                                class="mt-2 w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                                <option value="">
                                    Select existing patient
                                </option>


                                @foreach ($patients as $patient)

                                    <option
                                        value="{{ $patient->id }}"
                                        @selected(
                                            old(
                                                'patient_id',
                                                request('patient_id')
                                            )
                                            ==
                                            $patient->id
                                        )
                                    >
                                        {{ $patient->full_name }}

                                        @if ($patient->uhid)
                                            — {{ $patient->uhid }}
                                        @endif

                                        @if ($patient->mrd_number)
                                            — {{ $patient->mrd_number }}
                                        @endif

                                        @if ($patient->phone)
                                            — {{ $patient->phone }}
                                        @endif

                                    </option>

                                @endforeach

                            </select>


                            <p class="mt-2 text-xs text-slate-500">
                                Select an existing patient by name, UHID, MRD or phone.
                                If the patient has never been registered, use
                                “Register New Patient” first.
                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- ARRIVAL DATE / TIME --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="arrival_at"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Arrival Date & Time *
                            </label>

                            <input
                                id="arrival_at"
                                name="arrival_at"
                                type="datetime-local"
                                required
                                value="{{ old(
                                    'arrival_at',
                                    now()->format('Y-m-d\TH:i')
                                ) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                        </div>



                        {{-- ================================================= --}}
                        {{-- ARRIVAL MODE --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="arrival_mode"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Arrival Mode
                            </label>

                            <select
                                id="arrival_mode"
                                name="arrival_mode"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                                <option value="">
                                    Select arrival mode
                                </option>


                                <option
                                    value="walk_in"
                                    @selected(
                                        old('arrival_mode') === 'walk_in'
                                    )
                                >
                                    Walk-in
                                </option>


                                <option
                                    value="ambulance"
                                    @selected(
                                        old('arrival_mode') === 'ambulance'
                                    )
                                >
                                    Ambulance
                                </option>


                                <option
                                    value="wheelchair"
                                    @selected(
                                        old('arrival_mode') === 'wheelchair'
                                    )
                                >
                                    Wheelchair
                                </option>


                                <option
                                    value="stretcher"
                                    @selected(
                                        old('arrival_mode') === 'stretcher'
                                    )
                                >
                                    Stretcher
                                </option>


                                <option
                                    value="police"
                                    @selected(
                                        old('arrival_mode') === 'police'
                                    )
                                >
                                    Police
                                </option>


                                <option
                                    value="referred"
                                    @selected(
                                        old('arrival_mode') === 'referred'
                                    )
                                >
                                    Referred
                                </option>


                                <option
                                    value="other"
                                    @selected(
                                        old('arrival_mode') === 'other'
                                    )
                                >
                                    Other
                                </option>

                            </select>

                        </div>



                        {{-- ================================================= --}}
                        {{-- BROUGHT BY --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                for="brought_by"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Brought By
                            </label>

                            <input
                                id="brought_by"
                                name="brought_by"
                                type="text"
                                maxlength="150"
                                value="{{ old('brought_by') }}"
                                placeholder="Relative, ambulance staff, police, self..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >

                        </div>



                        {{-- ================================================= --}}
                        {{-- INITIAL STATUS --}}
                        {{-- ================================================= --}}

                        <div>

                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Initial Status
                            </label>

                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                                Registered
                            </div>

                            <p class="mt-2 text-xs text-slate-500">
                                Triage status will be updated after nursing assessment.
                            </p>

                        </div>



                        {{-- ================================================= --}}
                        {{-- CHIEF COMPLAINT --}}
                        {{-- ================================================= --}}

                        <div class="md:col-span-2">

                            <label
                                for="chief_complaint"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Chief Complaint
                            </label>

                            <textarea
                                id="chief_complaint"
                                name="chief_complaint"
                                rows="5"
                                maxlength="5000"
                                placeholder="Presenting complaint, duration and brief emergency history..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            >{{ old('chief_complaint') }}</textarea>

                        </div>


                    </div>



                    {{-- ================================================= --}}
                    {{-- WORKFLOW INFORMATION --}}
                    {{-- ================================================= --}}

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                        <div class="font-semibold text-amber-800">
                            Emergency workflow
                        </div>

                        <div class="mt-2 text-sm leading-6 text-amber-700">

                            For a new patient, click
                            <strong>Register New Patient</strong>.

                            After the Patient Master record is created, you will
                            return here automatically with that patient selected.

                            Complete the arrival details and register the Emergency visit.

                            Nursing staff can then perform triage, followed by
                            treatment, admission, referral or discharge.

                        </div>

                    </div>



                    {{-- ================================================= --}}
                    {{-- ACTIONS --}}
                    {{-- ================================================= --}}

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('emergency.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
                        >
                            Register Emergency Patient
                        </button>

                    </div>

                </form>

            </div>


        </div>

    </div>

</x-app-layout>