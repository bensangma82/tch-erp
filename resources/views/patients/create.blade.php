<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Patient Registration
                </h2>

                <p class="mt-1 text-sm text-gray-500">

                    @if (!empty($returnTo))

                        Register a new patient for Emergency.

                    @else

                        Register a new patient and generate a UHID.

                    @endif

                </p>

            </div>


            @if (!empty($returnTo))

                <a
                    href="{{ $returnTo }}"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                >
                    Back to Emergency
                </a>

            @else

                <a
                    href="{{ route('patients.index') }}"
                    class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                >
                    Patient List
                </a>

            @endif

        </div>

    </x-slot>



    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- EMERGENCY WORKFLOW NOTICE --}}
            {{-- ========================================================= --}}

            @if (!empty($returnTo))

                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5">

                    <div class="font-semibold text-red-800">
                        Emergency Patient Registration
                    </div>

                    <div class="mt-1 text-sm leading-6 text-red-700">
                        This patient registration was started from Emergency.
                        After saving the patient, you will automatically return
                        to Emergency Registration with the new patient selected.
                    </div>

                </div>

            @endif



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
            {{-- DUPLICATE WARNING --}}
            {{-- ========================================================= --}}

            @if (
                isset($duplicates)
                &&
                $duplicates->count()
            )

                <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-5">

                    <h3 class="font-semibold text-amber-900">
                        Possible Duplicate Patient
                    </h3>

                    <p class="mt-1 text-sm text-amber-800">
                        Please review the existing patient records before creating a new UHID.
                    </p>


                    <div class="mt-4 overflow-hidden rounded-lg border border-amber-200 bg-white">

                        <table class="min-w-full divide-y divide-gray-200">

                            <thead class="bg-amber-50">

                                <tr>

                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        UHID
                                    </th>

                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Patient
                                    </th>

                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        DOB
                                    </th>

                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Phone
                                    </th>

                                    <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-gray-100">

                                @foreach ($duplicates as $duplicate)

                                    <tr class="hover:bg-gray-50">

                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                            {{ $duplicate->uhid }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            {{ $duplicate->full_name }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $duplicate->date_of_birth?->format('d-m-Y') ?? '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $duplicate->phone ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-right">

                                            <a
                                                href="{{ route('patients.show', $duplicate) }}"
                                                target="_blank"
                                                class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                            >
                                                View Patient
                                            </a>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- REGISTRATION FORM --}}
            {{-- ========================================================= --}}

            <form
                id="patientRegistrationForm"
                method="POST"
                action="{{ route('patients.store') }}"
                class="space-y-6"
            >

                @csrf


                {{-- ===================================================== --}}
                {{-- PRESERVE WORKFLOW RETURN --}}
                {{-- ===================================================== --}}

                @if (!empty($returnTo))

                    <input
                        type="hidden"
                        name="return_to"
                        value="{{ $returnTo }}"
                    >

                @endif



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
                                        request('title')
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
                                value="{{ old('first_name', request('first_name')) }}"
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
                                value="{{ old('middle_name', request('middle_name')) }}"
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
                                value="{{ old('last_name', request('last_name')) }}"
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
                                value="{{ old('date_of_birth', request('date_of_birth')) }}"
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
                                value="{{ old('age', request('age')) }}"
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
                                        request('sex')
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
                                    @selected(
                                        $selectedSex === 'Male'
                                    )
                                >
                                    Male
                                </option>

                                <option
                                    value="Female"
                                    @selected(
                                        $selectedSex === 'Female'
                                    )
                                >
                                    Female
                                </option>

                                <option
                                    value="Other"
                                    @selected(
                                        $selectedSex === 'Other'
                                    )
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
                                        request('blood_group')
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



                    {{-- ================================================= --}}
                    {{-- DUPLICATE CHECK --}}
                    {{-- ================================================= --}}

                    <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>

                                <div class="text-sm font-medium text-gray-800">
                                    Duplicate Patient Check
                                </div>

                                <div class="mt-1 text-xs text-gray-500">
                                    Search existing patients using name, date of birth or mobile number.
                                </div>

                            </div>


                            <button
                                type="button"
                                onclick="checkDuplicatePatient()"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                            >
                                Check Existing Patient
                            </button>

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
                                value="{{ old('phone', request('phone')) }}"
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
                                value="{{ old('alternate_phone', request('alternate_phone')) }}"
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
                                value="{{ old('email', request('email')) }}"
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
                            >{{ old('address', request('address')) }}</textarea>

                        </div>



                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Locality / Village
                            </label>

                            <input
                                type="text"
                                name="locality"
                                value="{{ old('locality', request('locality')) }}"
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
                                value="{{ old('district', request('district')) }}"
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
                                value="{{ old('state', request('state', 'Meghalaya')) }}"
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
                                value="{{ old('pin_code', request('pin_code')) }}"
                                inputmode="numeric"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- EMERGENCY CONTACT --}}
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
                                value="{{ old('emergency_contact_name', request('emergency_contact_name')) }}"
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
                                value="{{ old('emergency_contact_phone', request('emergency_contact_phone')) }}"
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
                                value="{{ old('emergency_contact_relation', request('emergency_contact_relation')) }}"
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
                                value="{{ old('abha_number', request('abha_number')) }}"
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
                                value="{{ old('mhis_number', request('mhis_number')) }}"
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
                                value="{{ old('known_allergies', request('known_allergies')) }}"
                                placeholder="e.g. Penicillin"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- ACTION BUTTONS --}}
                {{-- ===================================================== --}}

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">


                    @if (!empty($returnTo))

                        <a
                            href="{{ $returnTo }}"
                            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel & Return to Emergency
                        </a>

                    @else

                        <a
                            href="{{ route('patients.index') }}"
                            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </a>

                    @endif


                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >

                        @if (!empty($returnTo))

                            Register & Continue Emergency

                        @else

                            Register Patient

                        @endif

                    </button>


                </div>

            </form>

        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- DUPLICATE CHECK JAVASCRIPT --}}
    {{-- ============================================================= --}}

    <script>

        function checkDuplicatePatient()
        {
            const form =
                document.getElementById(
                    'patientRegistrationForm'
                );


            if (!form)
            {
                alert(
                    'Patient registration form could not be found.'
                );

                return;
            }


            const firstName =
                form
                    .querySelector(
                        '[name="first_name"]'
                    )
                    .value
                    .trim();


            const lastName =
                form
                    .querySelector(
                        '[name="last_name"]'
                    )
                    .value
                    .trim();


            const dob =
                form
                    .querySelector(
                        '[name="date_of_birth"]'
                    )
                    .value;


            const phone =
                form
                    .querySelector(
                        '[name="phone"]'
                    )
                    .value
                    .trim();


            /*
             * Require at least one meaningful
             * identifying field.
             */

            if (
                !firstName
                &&
                !lastName
                &&
                !dob
                &&
                !phone
            )
            {
                alert(
                    'Enter at least a name, date of birth, or mobile number before checking for duplicates.'
                );

                return;
            }


            /*
             * Preserve all form values, including return_to.
             */

            const formData =
                new FormData(
                    form
                );


            const params =
                new URLSearchParams();


            for (
                const [key, value]
                of formData.entries()
            )
            {
                if (
                    key === '_token'
                )
                {
                    continue;
                }


                if (
                    typeof value === 'string'
                    &&
                    value.trim() !== ''
                )
                {
                    params.append(
                        key,
                        value.trim()
                    );
                }
            }


            window.location.href =
                "{{ route('patients.create') }}"
                +
                '?'
                +
                params.toString();
        }

    </script>

</x-app-layout>