<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    OPD Registration
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Register the outpatient visit and generate the OPD invoice.
                </p>
            </div>

            <a
                href="{{ route('opd.index') }}"
                class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
            >
                Today's OPD
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- VALIDATION ERRORS --}}
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


            <form
                id="opdRegistrationForm"
                method="POST"
                action="{{ route('opd.store') }}"
                class="space-y-6"
            >

                @csrf


                {{-- ========================================================= --}}
                {{-- PATIENT --}}
                {{-- ========================================================= --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            Patient
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Confirm the patient before registering the OPD visit.
                        </p>

                    </div>


                    <div class="p-6">

                        @if ($patient)

                            <input
                                type="hidden"
                                name="patient_id"
                                value="{{ $patient->id }}"
                            >


                            <div class="grid grid-cols-1 gap-5 md:grid-cols-5">

                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        UHID
                                    </div>

                                    <div class="mt-1 font-semibold text-gray-900">
                                        {{ $patient->uhid }}
                                    </div>
                                </div>


                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        MRD No.
                                    </div>

                                    <div class="mt-1 font-semibold text-gray-900">
                                        {{ $patient->mrd_number ?: '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Patient
                                    </div>

                                    <div class="mt-1 font-medium text-gray-900">
                                        {{ $patient->full_name }}
                                    </div>
                                </div>


                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Age / Sex
                                    </div>

                                    <div class="mt-1 text-gray-800">
                                        {{ $patient->age !== null ? $patient->age . ' yrs' : '—' }}
                                        /
                                        {{ $patient->sex ?: '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Phone
                                    </div>

                                    <div class="mt-1 text-gray-800">
                                        {{ $patient->phone ?: '—' }}
                                    </div>
                                </div>

                            </div>


                            <div class="mt-5 border-t border-gray-100 pt-4">

                                <a
                                    href="{{ route('patients.show', $patient) }}"
                                    target="_blank"
                                    class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                >
                                    View Patient Profile
                                </a>

                            </div>

                        @else

                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">

                                <div class="font-medium text-amber-900">
                                    No patient selected
                                </div>

                                <div class="mt-1 text-sm text-amber-700">
                                    Open the patient registry and select a patient before creating an OPD encounter.
                                </div>

                                <a
                                    href="{{ route('patients.index') }}"
                                    class="mt-3 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                >
                                    Select Patient
                                </a>

                            </div>

                        @endif

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- FOLLOW-UP ELIGIBILITY --}}
                {{-- ========================================================= --}}

                @if ($patient)

                    <div
                        id="followupEligibilityBox"
                        class="hidden rounded-xl border px-5 py-4"
                    >
                        <div
                            id="followupEligibilityTitle"
                            class="font-semibold"
                        ></div>

                        <div
                            id="followupEligibilityText"
                            class="mt-1 text-sm"
                        ></div>
                    </div>

                @endif


                {{-- ========================================================= --}}
                {{-- OPD DETAILS --}}
                {{-- ========================================================= --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            OPD Details
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Select the department, doctor and type of visit.
                        </p>

                    </div>


                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">


                        {{-- DEPARTMENT --}}
                        <div>

                            <label
                                for="department_id"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Department
                                <span class="text-red-500">*</span>
                            </label>


                            <select
                                id="department_id"
                                name="department_id"
                                required
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option value="">
                                    Select Department
                                </option>

                                @foreach ($departments as $department)

                                    <option
                                        value="{{ $department->id }}"
                                        @selected(
                                            old('department_id') == $department->id
                                        )
                                    >
                                        {{ $department->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- DOCTOR --}}
                        <div>

                            <label
                                for="doctor_id"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Doctor
                            </label>


                            <select
                                id="doctor_id"
                                name="doctor_id"
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option value="">
                                    Select Doctor
                                </option>

                                @foreach ($doctors as $doctor)

                                    <option
                                        value="{{ $doctor->id }}"
                                        data-department="{{ $doctor->department_id }}"
                                        @selected(
                                            old('doctor_id') == $doctor->id
                                        )
                                    >
                                        {{ $doctor->full_name }}

                                        @if ($doctor->speciality)
                                            — {{ $doctor->speciality }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- VISIT TYPE --}}
                        <div>

                            <label
                                for="visit_type"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Visit Type
                                <span class="text-red-500">*</span>
                            </label>


                            <select
                                id="visit_type"
                                name="visit_type"
                                required
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option
                                    value="new"
                                    @selected(old('visit_type', 'new') === 'new')
                                >
                                    New Visit
                                </option>

                                <option
                                    value="follow_up"
                                    @selected(old('visit_type') === 'follow_up')
                                >
                                    Follow-up
                                </option>

                                <option
                                    value="review"
                                    @selected(old('visit_type') === 'review')
                                >
                                    Review
                                </option>

                                <option
                                    value="referral"
                                    @selected(old('visit_type') === 'referral')
                                >
                                    Referral
                                </option>

                            </select>

                        </div>


                        {{-- REFERRAL TYPE --}}
                        <div
                            id="referralTypeContainer"
                            class="hidden"
                        >

                            <label
                                for="referral_type"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Referral Type
                            </label>

                            <select
                                id="referral_type"
                                name="referral_type"
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option value="">
                                    Select Referral Type
                                </option>

                                <option
                                    value="internal"
                                    @selected(old('referral_type') === 'internal')
                                >
                                    Internal Referral
                                </option>

                                <option
                                    value="external"
                                    @selected(old('referral_type') === 'external')
                                >
                                    External Referral
                                </option>

                            </select>

                        </div>


                        {{-- INTERNAL REFERRAL SOURCE DEPARTMENT --}}
                        <div
                            id="internalReferralDepartmentContainer"
                            class="hidden"
                        >

                            <label
                                for="referred_from_department_id"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Referred From Department
                            </label>


                            <select
                                id="referred_from_department_id"
                                name="referred_from_department_id"
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option value="">
                                    Select Source Department
                                </option>

                                @foreach ($departments as $department)

                                    <option
                                        value="{{ $department->id }}"
                                        @selected(
                                            old('referred_from_department_id')
                                            == $department->id
                                        )
                                    >
                                        {{ $department->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- INTERNAL REFERRING DOCTOR --}}
                        <div
                            id="internalReferringDoctorContainer"
                            class="hidden"
                        >

                            <label
                                for="referring_doctor_id"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Referring Doctor
                            </label>


                            <select
                                id="referring_doctor_id"
                                name="referring_doctor_id"
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option value="">
                                    Select Referring Doctor
                                </option>

                                @foreach ($doctors as $doctor)

                                    <option
                                        value="{{ $doctor->id }}"
                                        data-department="{{ $doctor->department_id }}"
                                        @selected(
                                            old('referring_doctor_id')
                                            == $doctor->id
                                        )
                                    >
                                        {{ $doctor->full_name }}

                                        @if ($doctor->speciality)
                                            — {{ $doctor->speciality }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- EXTERNAL REFERRED BY --}}
                        <div
                            id="externalReferralContainer"
                            class="hidden"
                        >

                            <label
                                for="referred_by"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Referred By
                            </label>

                            <input
                                id="referred_by"
                                type="text"
                                name="referred_by"
                                value="{{ old('referred_by') }}"
                                placeholder="External doctor / hospital"
                                class="w-full rounded-lg border-gray-300"
                            >

                        </div>


                        {{-- REASON --}}
                        <div class="md:col-span-2">

                            <label
                                for="reason_for_visit"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Reason for Visit
                            </label>

                            <textarea
                                id="reason_for_visit"
                                name="reason_for_visit"
                                rows="2"
                                class="w-full rounded-lg border-gray-300"
                                placeholder="Brief reason for consultation"
                            >{{ old('reason_for_visit') }}</textarea>

                        </div>

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- OPD CHARGES --}}
                {{-- ========================================================= --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 bg-slate-50 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            OPD Charges
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Consultation fees are loaded automatically from Service Master where configured.
                        </p>

                    </div>


                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-3">


                        {{-- CONSULTATION FEE --}}
                        <div>

                            <label
                                for="consultation_fee"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Consultation Fee
                            </label>


                            <div class="relative">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                    ₹
                                </div>

                                <input
                                    id="consultation_fee"
                                    type="number"
                                    name="consultation_fee"
                                    value="{{ old('consultation_fee', 0) }}"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="w-full rounded-lg border-gray-300 pl-8"
                                >

                            </div>

                        </div>


                        {{-- REGISTRATION FEE --}}
                        <div>

                            <label
                                for="registration_fee"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Registration Fee
                            </label>


                            <div class="relative">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                    ₹
                                </div>

                                <input
                                    id="registration_fee"
                                    type="number"
                                    name="registration_fee"
                                    value="{{ old('registration_fee', 0) }}"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="w-full rounded-lg border-gray-300 pl-8"
                                >

                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Keep ₹0 if no registration fee applies.
                            </p>

                        </div>


                        {{-- TOTAL --}}
                        <div>

                            <label
                                for="total_amount"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Total Amount
                            </label>


                            <div class="relative">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 font-semibold text-gray-700">
                                    ₹
                                </div>

                                <input
                                    id="total_amount"
                                    type="number"
                                    name="total_amount"
                                    value="{{ old('total_amount', 0) }}"
                                    readonly
                                    class="w-full rounded-lg border-gray-300 bg-gray-100 pl-8 font-bold text-gray-900"
                                >

                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Payment will be collected on the next screen.
                            </p>

                        </div>

                    </div>

                </div>


                {{-- ========================================================= --}}
                {{-- ACTIONS --}}
                {{-- ========================================================= --}}

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('patients.index') }}"
                        class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        @disabled(!$patient)
                        class="rounded-lg bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Register OPD
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
                /*
                |--------------------------------------------------------------------------
                | Recent OPD visits
                |--------------------------------------------------------------------------
                */

                @php
                    $recentVisitsForJs = $recentVisits->map(function ($visit) {
                        return [
                            'department_id' => (string) $visit->department_id,
                            'department_name' => $visit->department?->name,
                            'doctor_name' => $visit->doctor?->full_name,
                            'encounter_date' => $visit->encounter_date
                                ? $visit->encounter_date->format('Y-m-d')
                                : null,
                            'display_date' => $visit->encounter_date
                                ? $visit->encounter_date->format('d M Y')
                                : null,
                            'free_until' => $visit->encounter_date
                                ? $visit->encounter_date
                                    ->copy()
                                    ->addDays(7)
                                    ->format('d M Y')
                                : null,
                        ];
                    })->values();
                @endphp


                const recentVisits =
                    @json($recentVisitsForJs);

                const internalReferralFee =
                    @json(
                        $internalReferralFee !== null
                            ? (float) $internalReferralFee
                            : null
                    );

                const departmentConsultationFees =
                    @json($departmentConsultationFees);


                /*
                |--------------------------------------------------------------------------
                | Form elements
                |--------------------------------------------------------------------------
                */

                const visitType =
                    document.getElementById(
                        'visit_type'
                    );

                const referralType =
                    document.getElementById(
                        'referral_type'
                    );

                const referralTypeContainer =
                    document.getElementById(
                        'referralTypeContainer'
                    );

                const internalReferralDepartmentContainer =
                    document.getElementById(
                        'internalReferralDepartmentContainer'
                    );

                const internalReferringDoctorContainer =
                    document.getElementById(
                        'internalReferringDoctorContainer'
                    );

                const externalReferralContainer =
                    document.getElementById(
                        'externalReferralContainer'
                    );

                const referredFromDepartment =
                    document.getElementById(
                        'referred_from_department_id'
                    );

                const referringDoctor =
                    document.getElementById(
                        'referring_doctor_id'
                    );

                const followupEligibilityBox =
                    document.getElementById(
                        'followupEligibilityBox'
                    );

                const followupEligibilityTitle =
                    document.getElementById(
                        'followupEligibilityTitle'
                    );

                const followupEligibilityText =
                    document.getElementById(
                        'followupEligibilityText'
                    );

                const departmentSelect =
                    document.getElementById(
                        'department_id'
                    );

                const doctorSelect =
                    document.getElementById(
                        'doctor_id'
                    );

                const consultationFee =
                    document.getElementById(
                        'consultation_fee'
                    );

                const registrationFee =
                    document.getElementById(
                        'registration_fee'
                    );

                const totalAmount =
                    document.getElementById(
                        'total_amount'
                    );


                /*
                |--------------------------------------------------------------------------
                | Helpers
                |--------------------------------------------------------------------------
                */

                function numberValue(element)
                {
                    if (! element)
                    {
                        return 0;
                    }

                    const value =
                        parseFloat(
                            element.value
                        );

                    return isNaN(value)
                        ? 0
                        : value;
                }


                function currency(value)
                {
                    return '₹' +
                        Number(value)
                            .toFixed(2);
                }


                function calculatePayment()
                {
                    const consultation =
                        numberValue(
                            consultationFee
                        );

                    const registration =
                        numberValue(
                            registrationFee
                        );

                    const total =
                        consultation +
                        registration;

                    if (totalAmount)
                    {
                        totalAmount.value =
                            total.toFixed(2);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Doctor filtering
                |--------------------------------------------------------------------------
                */

                function filterDoctors()
                {
                    if (
                        ! departmentSelect ||
                        ! doctorSelect
                    )
                    {
                        return;
                    }

                    const departmentId =
                        departmentSelect.value;

                    const options =
                        doctorSelect.querySelectorAll(
                            'option'
                        );

                    options.forEach(
                        function (option)
                        {
                            if (! option.value)
                            {
                                option.hidden =
                                    false;

                                return;
                            }

                            const doctorDepartment =
                                option.dataset.department;

                            option.hidden =
                                departmentId &&
                                doctorDepartment !==
                                departmentId;
                        }
                    );

                    const selected =
                        doctorSelect.options[
                            doctorSelect.selectedIndex
                        ];

                    if (
                        selected &&
                        selected.value &&
                        selected.hidden
                    )
                    {
                        doctorSelect.value =
                            '';
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Internal referral
                |--------------------------------------------------------------------------
                */

                function isInternalReferral()
                {
                    return (
                        visitType &&
                        visitType.value === 'referral' &&
                        referralType &&
                        referralType.value === 'internal'
                    );
                }


                function updateReferralFields()
                {
                    const isReferral =
                        visitType &&
                        visitType.value ===
                        'referral';

                    const type =
                        referralType
                            ? referralType.value
                            : '';

                    if (referralTypeContainer)
                    {
                        referralTypeContainer
                            .classList
                            .toggle(
                                'hidden',
                                ! isReferral
                            );
                    }

                    const showInternal =
                        isReferral &&
                        type === 'internal';

                    const showExternal =
                        isReferral &&
                        type === 'external';

                    if (
                        internalReferralDepartmentContainer
                    )
                    {
                        internalReferralDepartmentContainer
                            .classList
                            .toggle(
                                'hidden',
                                ! showInternal
                            );
                    }

                    if (
                        internalReferringDoctorContainer
                    )
                    {
                        internalReferringDoctorContainer
                            .classList
                            .toggle(
                                'hidden',
                                ! showInternal
                            );
                    }

                    if (externalReferralContainer)
                    {
                        externalReferralContainer
                            .classList
                            .toggle(
                                'hidden',
                                ! showExternal
                            );
                    }

                    if (referralType)
                    {
                        referralType.required =
                            isReferral;
                    }

                    if (referredFromDepartment)
                    {
                        referredFromDepartment.required =
                            showInternal;
                    }


                    /*
                     * Internal referral fee overrides
                     * normal consultation/follow-up fee.
                     */
                    if (
                        showInternal &&
                        consultationFee
                    )
                    {
                        if (
                            internalReferralFee === null
                        )
                        {
                            consultationFee.value =
                                '0.00';

                            consultationFee.readOnly =
                                true;

                            consultationFee.className =
                                'w-full rounded-lg border-red-300 bg-red-50 pl-8 font-semibold text-red-800';

                            if (
                                followupEligibilityBox
                            )
                            {
                                followupEligibilityBox.className =
                                    'rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800';
                            }

                            if (
                                followupEligibilityTitle
                            )
                            {
                                followupEligibilityTitle.textContent =
                                    'INTERNAL REFERRAL FEE NOT CONFIGURED';
                            }

                            if (
                                followupEligibilityText
                            )
                            {
                                followupEligibilityText.textContent =
                                    'Service code INT-REF is missing or inactive in Service Master.';
                            }

                            calculatePayment();

                            return;
                        }


                        consultationFee.value =
                            Number(
                                internalReferralFee
                            ).toFixed(2);

                        consultationFee.readOnly =
                            true;

                        consultationFee.className =
                            'w-full rounded-lg border-amber-300 bg-amber-50 pl-8 font-semibold text-amber-800';

                        if (
                            followupEligibilityBox
                        )
                        {
                            followupEligibilityBox.className =
                                'rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800';
                        }

                        if (
                            followupEligibilityTitle
                        )
                        {
                            followupEligibilityTitle.textContent =
                                'INTERNAL REFERRAL — ' +
                                currency(
                                    internalReferralFee
                                );
                        }

                        if (
                            followupEligibilityText
                        )
                        {
                            followupEligibilityText.textContent =
                                'Internal departmental referral charge from Service Master applies. This overrides the 7-day free follow-up rule.';
                        }

                        calculatePayment();

                        return;
                    }


                    updateFollowupEligibility();
                }


                /*
                |--------------------------------------------------------------------------
                | Free follow-up / Service Master fee
                |--------------------------------------------------------------------------
                */

                function updateFollowupEligibility()
                {
                    if (
                        ! departmentSelect ||
                        ! consultationFee
                    )
                    {
                        return;
                    }

                    if (isInternalReferral())
                    {
                        return;
                    }


                    const departmentId =
                        String(
                            departmentSelect.value ||
                            ''
                        );


                    if (! departmentId)
                    {
                        if (
                            followupEligibilityBox
                        )
                        {
                            followupEligibilityBox.className =
                                'hidden rounded-xl border px-5 py-4';
                        }

                        consultationFee.readOnly =
                            false;

                        consultationFee.className =
                            'w-full rounded-lg border-gray-300 pl-8';

                        calculatePayment();

                        return;
                    }


                    /*
                     * Check 7-day free follow-up.
                     */
                    const eligibleVisit =
                        recentVisits.find(
                            function (visit)
                            {
                                return String(
                                    visit.department_id
                                ) === departmentId;
                            }
                        );


                    if (eligibleVisit)
                    {
                        consultationFee.value =
                            '0.00';

                        consultationFee.readOnly =
                            true;

                        consultationFee.className =
                            'w-full rounded-lg border-green-300 bg-green-50 pl-8 font-semibold text-green-800';

                        if (
                            visitType &&
                            visitType.value !==
                            'referral'
                        )
                        {
                            visitType.value =
                                'follow_up';
                        }

                        if (
                            followupEligibilityBox
                        )
                        {
                            followupEligibilityBox.className =
                                'rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-green-800';
                        }

                        if (
                            followupEligibilityTitle
                        )
                        {
                            followupEligibilityTitle.textContent =
                                'FREE FOLLOW-UP ELIGIBLE';
                        }

                        if (
                            followupEligibilityText
                        )
                        {
                            let message =
                                'Previous ' +
                                (
                                    eligibleVisit.department_name ||
                                    'department'
                                ) +
                                ' visit: ' +
                                (
                                    eligibleVisit.display_date ||
                                    'recent visit'
                                ) +
                                '. Consultation fee: ₹0.00.';

                            if (
                                eligibleVisit.doctor_name
                            )
                            {
                                message +=
                                    ' Previous doctor: ' +
                                    eligibleVisit.doctor_name +
                                    '.';
                            }

                            if (
                                eligibleVisit.free_until
                            )
                            {
                                message +=
                                    ' Free follow-up valid through ' +
                                    eligibleVisit.free_until +
                                    '.';
                            }

                            followupEligibilityText.textContent =
                                message;
                        }

                        calculatePayment();

                        return;
                    }


                    /*
                     * Use department consultation service
                     * if configured.
                     */
                    const configuredService =
                        departmentConsultationFees
                            ? departmentConsultationFees[
                                departmentId
                            ]
                            : null;


                    if (configuredService)
                    {
                        const configuredFee =
                            Number(
                                configuredService.price ||
                                0
                            );

                        consultationFee.value =
                            configuredFee.toFixed(2);

                        consultationFee.readOnly =
                            true;

                        consultationFee.className =
                            'w-full rounded-lg border-blue-300 bg-blue-50 pl-8 font-semibold text-blue-800';

                        if (
                            followupEligibilityBox
                        )
                        {
                            followupEligibilityBox.className =
                                'rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-blue-800';
                        }

                        if (
                            followupEligibilityTitle
                        )
                        {
                            followupEligibilityTitle.textContent =
                                'SERVICE MASTER CONSULTATION FEE — ' +
                                currency(
                                    configuredFee
                                );
                        }

                        if (
                            followupEligibilityText
                        )
                        {
                            followupEligibilityText.textContent =
                                'No free follow-up applies. The consultation fee has been loaded automatically from Service Master (' +
                                (
                                    configuredService.code ||
                                    'OPD consultation service'
                                ) +
                                ').';
                        }

                        calculatePayment();

                        return;
                    }


                    /*
                     * Temporary manual fallback.
                     */
                    consultationFee.readOnly =
                        false;

                    consultationFee.className =
                        'w-full rounded-lg border-gray-300 pl-8';

                    if (
                        followupEligibilityBox
                    )
                    {
                        followupEligibilityBox.className =
                            'rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 text-slate-700';
                    }

                    if (
                        followupEligibilityTitle
                    )
                    {
                        followupEligibilityTitle.textContent =
                            'FULL CONSULTATION FEE APPLIES';
                    }

                    if (
                        followupEligibilityText
                    )
                    {
                        followupEligibilityText.textContent =
                            'No free follow-up applies and no OPD consultation service is configured for this department yet. Enter the consultation fee manually.';
                    }

                    calculatePayment();
                }


                /*
                |--------------------------------------------------------------------------
                | Department change
                |--------------------------------------------------------------------------
                */

                if (
                    departmentSelect &&
                    doctorSelect
                )
                {
                    departmentSelect.addEventListener(
                        'change',
                        function ()
                        {
                            filterDoctors();
                            updateReferralFields();
                        }
                    );

                    filterDoctors();
                }


                /*
                |--------------------------------------------------------------------------
                | Fee listeners
                |--------------------------------------------------------------------------
                */

                if (consultationFee)
                {
                    consultationFee.addEventListener(
                        'input',
                        calculatePayment
                    );
                }


                if (registrationFee)
                {
                    registrationFee.addEventListener(
                        'input',
                        calculatePayment
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Referral listeners
                |--------------------------------------------------------------------------
                */

                if (visitType)
                {
                    visitType.addEventListener(
                        'change',
                        updateReferralFields
                    );
                }


                if (referralType)
                {
                    referralType.addEventListener(
                        'change',
                        updateReferralFields
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Filter referring doctor by source department
                |--------------------------------------------------------------------------
                */

                if (
                    referredFromDepartment &&
                    referringDoctor
                )
                {
                    referredFromDepartment.addEventListener(
                        'change',
                        function ()
                        {
                            const sourceDepartmentId =
                                referredFromDepartment.value;

                            const options =
                                referringDoctor
                                    .querySelectorAll(
                                        'option'
                                    );

                            options.forEach(
                                function (option)
                                {
                                    if (! option.value)
                                    {
                                        option.hidden =
                                            false;

                                        return;
                                    }

                                    option.hidden =
                                        sourceDepartmentId &&
                                        option.dataset.department !==
                                        sourceDepartmentId;
                                }
                            );


                            const selected =
                                referringDoctor.options[
                                    referringDoctor.selectedIndex
                                ];

                            if (
                                selected &&
                                selected.value &&
                                selected.hidden
                            )
                            {
                                referringDoctor.value =
                                    '';
                            }
                        }
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Initial state
                |--------------------------------------------------------------------------
                */

                updateReferralFields();
                calculatePayment();
            }
        );

    </script>

</x-app-layout>