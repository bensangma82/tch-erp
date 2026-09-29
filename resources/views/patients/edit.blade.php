<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Edit Patient Details
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Correct demographic and contact information for this patient.
                </p>

            </div>


            <a
                href="{{ route('patients.show', $patient) }}"
                class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
            >
                Back to Patient
            </a>

        </div>

    </x-slot>



    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- PATIENT IDENTIFIERS --}}
            {{-- ========================================================= --}}

            <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-6 py-4">

                    <h3 class="text-base font-semibold text-gray-800">
                        Patient Identification
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        UHID and MRD Number are permanent identifiers and cannot be changed here.
                    </p>

                </div>


                <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-3">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            UHID
                        </div>

                        <div class="mt-1 text-base font-semibold text-gray-900">
                            {{ $patient->uhid }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            MRD Number
                        </div>

                        <div class="mt-1 text-base font-semibold text-gray-900">
                            {{ $patient->mrd_number ?: 'Not assigned' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Registered
                        </div>

                        <div class="mt-1 text-base text-gray-900">
                            {{ $patient->created_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- VALIDATION ERRORS --}}
            {{-- ========================================================= --}}

            @if ($errors->any())

                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- EDIT FORM --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('patients.update', $patient) }}"
                class="space-y-6"
            >

                @csrf
                @method('PUT')



                {{-- ===================================================== --}}
                {{-- BASIC INFORMATION --}}
                {{-- ===================================================== --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="text-base font-semibold text-gray-800">
                            Basic Information
                        </h3>

                    </div>


                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-4">


                        {{-- TITLE --}}

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Title
                            </label>

                            @php
                                $selectedTitle =
                                    old(
                                        'title',
                                        $patient->title
                                    );
                            @endphp


                            <select
                                name="title"
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option value="">
                                    Select
                                </option>

                                @foreach (
                                    [
                                        'Mr',
                                        'Mrs',
                                        'Ms',
                                        'Master',
                                        'Baby',
                                        'Dr',
                                    ]
                                    as $title
                                )

                                    <option
                                        value="{{ $title }}"
                                        @selected(
                                            $selectedTitle === $title
                                        )
                                    >
                                        {{ $title }}
                                    </option>

                                @endforeach

                            </select>

                        </div>



                        {{-- FIRST NAME --}}

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                First Name
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="text"
                                name="first_name"
                                value="{{ old('first_name', $patient->first_name) }}"
                                required
                                autocomplete="given-name"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        {{-- MIDDLE NAME --}}

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Middle Name
                            </label>

                            <input
                                type="text"
                                name="middle_name"
                                value="{{ old('middle_name', $patient->middle_name) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        {{-- LAST NAME --}}

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Last Name
                            </label>

                            <input
                                type="text"
                                name="last_name"
                                value="{{ old('last_name', $patient->last_name) }}"
                                autocomplete="family-name"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        {{-- DATE OF BIRTH --}}

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Date of Birth
                            </label>

                            <input
                                type="date"
                                name="date_of_birth"
                                value="{{ old(
                                    'date_of_birth',
                                    $patient->date_of_birth?->format('Y-m-d')
                                ) }}"
                                max="{{ now()->format('Y-m-d') }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        {{-- AGE --}}

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Age
                            </label>

                            <input
                                type="number"
                                name="age"
                                min="0"
                                max="130"
                                value="{{ old('age', $patient->age) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        {{-- SEX --}}

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Sex
                                <span class="text-red-500">*</span>
                            </label>

                            @php
                                $selectedSex =
                                    old(
                                        'sex',
                                        $patient->sex
                                    );
                            @endphp


                            <select
                                name="sex"
                                required
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option value="">
                                    Select
                                </option>

                                <option
                                    value="Male"
                                    @selected($selectedSex === 'Male')
                                >
                                    Male
                                </option>

                                <option
                                    value="Female"
                                    @selected($selectedSex === 'Female')
                                >
                                    Female
                                </option>

                                <option
                                    value="Other"
                                    @selected($selectedSex === 'Other')
                                >
                                    Other
                                </option>

                            </select>

                        </div>



                        {{-- BLOOD GROUP --}}

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Blood Group
                            </label>

                            @php
                                $selectedBloodGroup =
                                    old(
                                        'blood_group',
                                        $patient->blood_group
                                    );
                            @endphp


                            <select
                                name="blood_group"
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option value="">
                                    Unknown
                                </option>

                                @foreach (
                                    [
                                        'A+',
                                        'A-',
                                        'B+',
                                        'B-',
                                        'AB+',
                                        'AB-',
                                        'O+',
                                        'O-',
                                    ]
                                    as $group
                                )

                                    <option
                                        value="{{ $group }}"
                                        @selected(
                                            $selectedBloodGroup === $group
                                        )
                                    >
                                        {{ $group }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- CONTACT INFORMATION --}}
                {{-- ===================================================== --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="text-base font-semibold text-gray-800">
                            Contact Information
                        </h3>

                    </div>


                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-3">


                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Mobile Number
                            </label>

                            <input
                                type="text"
                                name="phone"
                                value="{{ old('phone', $patient->phone) }}"
                                inputmode="numeric"
                                autocomplete="tel"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Alternate Number
                            </label>

                            <input
                                type="text"
                                name="alternate_phone"
                                value="{{ old('alternate_phone', $patient->alternate_phone) }}"
                                inputmode="numeric"
                                autocomplete="tel"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="{{ old('email', $patient->email) }}"
                                autocomplete="email"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- ADDRESS --}}
                {{-- ===================================================== --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="text-base font-semibold text-gray-800">
                            Address
                        </h3>

                    </div>


                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-4">


                        <div class="md:col-span-4">

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Address
                            </label>

                            <textarea
                                name="address"
                                rows="2"
                                class="w-full rounded-lg border-gray-300"
                            >{{ old('address', $patient->address) }}</textarea>

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Locality / Village
                            </label>

                            <input
                                type="text"
                                name="locality"
                                value="{{ old('locality', $patient->locality) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                District
                            </label>

                            <input
                                type="text"
                                name="district"
                                value="{{ old('district', $patient->district) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                State
                            </label>

                            <input
                                type="text"
                                name="state"
                                value="{{ old('state', $patient->state) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                PIN Code
                            </label>

                            <input
                                type="text"
                                name="pin_code"
                                value="{{ old('pin_code', $patient->pin_code) }}"
                                inputmode="numeric"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- EMERGENCY CONTACT & IDENTIFICATION --}}
                {{-- ===================================================== --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="text-base font-semibold text-gray-800">
                            Emergency Contact & Identification
                        </h3>

                    </div>


                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-3">


                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Emergency Contact Name
                            </label>

                            <input
                                type="text"
                                name="emergency_contact_name"
                                value="{{ old(
                                    'emergency_contact_name',
                                    $patient->emergency_contact_name
                                ) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Emergency Contact Phone
                            </label>

                            <input
                                type="text"
                                name="emergency_contact_phone"
                                value="{{ old(
                                    'emergency_contact_phone',
                                    $patient->emergency_contact_phone
                                ) }}"
                                inputmode="numeric"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Relationship
                            </label>

                            <input
                                type="text"
                                name="emergency_contact_relation"
                                value="{{ old(
                                    'emergency_contact_relation',
                                    $patient->emergency_contact_relation
                                ) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                ABHA Number
                            </label>

                            <input
                                type="text"
                                name="abha_number"
                                value="{{ old('abha_number', $patient->abha_number) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                MHIS Number
                            </label>

                            <input
                                type="text"
                                name="mhis_number"
                                value="{{ old('mhis_number', $patient->mhis_number) }}"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Known Allergies
                            </label>

                            <input
                                type="text"
                                name="known_allergies"
                                value="{{ old(
                                    'known_allergies',
                                    $patient->known_allergies
                                ) }}"
                                placeholder="e.g. Penicillin"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- SAFETY NOTICE --}}
                {{-- ===================================================== --}}

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                    <div class="font-semibold text-amber-900">
                        Patient Record Correction
                    </div>

                    <div class="mt-1 text-sm leading-6 text-amber-800">
                        Please verify the patient before saving.
                        This page changes demographic and contact information only.
                        UHID and MRD Number will remain unchanged.
                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- ACTION BUTTONS --}}
                {{-- ===================================================== --}}

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('patients.show', $patient) }}"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Save Patient Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app-layout>