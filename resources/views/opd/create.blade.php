<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    OPD Registration & Payment
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Register the outpatient visit, collect payment and generate a receipt.
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

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="font-semibold text-gray-800">
                                    Patient
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Confirm the patient before registering the OPD visit.
                                </p>

                            </div>

                        </div>

                    </div>


                    <div class="p-6">

                        @if ($patient)

                            <input
                                type="hidden"
                                name="patient_id"
                                value="{{ $patient->id }}"
                            >


                            <div class="grid grid-cols-1 gap-5 md:grid-cols-5">


                                {{-- UHID --}}
                                <div>

                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        UHID
                                    </div>

                                    <div class="mt-1 font-semibold text-gray-900">
                                        {{ $patient->uhid }}
                                    </div>

                                </div>


                                {{-- MRD --}}
                                <div>

                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        MRD No.
                                    </div>

                                    <div class="mt-1 font-semibold text-gray-900">
                                        {{ $patient->mrd_number ?: '—' }}
                                    </div>

                                </div>


                                {{-- PATIENT --}}
                                <div>

                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Patient
                                    </div>

                                    <div class="mt-1 font-medium text-gray-900">
                                        {{ $patient->full_name }}
                                    </div>

                                </div>


                                {{-- AGE / SEX --}}
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


                                {{-- PHONE --}}
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

                                <span class="text-red-500">
                                    *
                                </span>
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

                                <span class="text-red-500">
                                    *
                                </span>
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



                        {{-- REFERRED BY --}}
                        <div>

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
                                placeholder="Doctor / hospital / self"
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
                {{-- BILLING --}}
                {{-- ========================================================= --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 bg-slate-50 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            OPD Charges
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Enter the charges applicable to this visit.
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

                        </div>

                    </div>

                </div>



                {{-- ========================================================= --}}
                {{-- PAYMENT --}}
                {{-- ========================================================= --}}

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 bg-green-50 px-6 py-4">

                        <div class="flex items-center justify-between">

                            <div>

                                <h3 class="font-semibold text-gray-800">
                                    Payment
                                </h3>

                                <p class="mt-1 text-xs text-gray-600">
                                    Record the amount collected by reception.
                                </p>

                            </div>

                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                Reception
                            </span>

                        </div>

                    </div>


                    <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-3">


                        {{-- PAYMENT MODE --}}
                        <div>

                            <label
                                for="payment_mode"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Payment Mode

                                <span class="text-red-500">
                                    *
                                </span>
                            </label>


                            <select
                                id="payment_mode"
                                name="payment_mode"
                                required
                                class="w-full rounded-lg border-gray-300"
                            >

                                <option
                                    value="cash"
                                    @selected(old('payment_mode', 'cash') === 'cash')
                                >
                                    Cash
                                </option>

                                <option
                                    value="upi"
                                    @selected(old('payment_mode') === 'upi')
                                >
                                    UPI
                                </option>

                                <option
                                    value="card"
                                    @selected(old('payment_mode') === 'card')
                                >
                                    Card
                                </option>

                                <option
                                    value="credit"
                                    @selected(old('payment_mode') === 'credit')
                                >
                                    Credit
                                </option>

                                <option
                                    value="mhis"
                                    @selected(old('payment_mode') === 'mhis')
                                >
                                    MHIS / Insurance
                                </option>

                            </select>

                        </div>



                        {{-- TRANSACTION REFERENCE --}}
                        <div>

                            <label
                                for="transaction_reference"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Transaction / Reference No.
                            </label>


                            <input
                                id="transaction_reference"
                                type="text"
                                name="transaction_reference"
                                value="{{ old('transaction_reference') }}"
                                placeholder="UPI / card / reference number"
                                class="w-full rounded-lg border-gray-300"
                            >


                            <p
                                id="transaction_reference_help"
                                class="mt-1 text-xs text-gray-500"
                            >
                                Optional for cash payment.
                            </p>

                        </div>



                        {{-- AMOUNT RECEIVED --}}
                        <div>

                            <label
                                for="amount_received"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Amount Received

                                <span class="text-red-500">
                                    *
                                </span>
                            </label>


                            <div class="relative">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                    ₹
                                </div>

                                <input
                                    id="amount_received"
                                    type="number"
                                    name="amount_received"
                                    value="{{ old('amount_received', 0) }}"
                                    min="0"
                                    step="0.01"
                                    required
                                    class="w-full rounded-lg border-gray-300 pl-8"
                                >

                            </div>

                        </div>

                    </div>



                    {{-- PAYMENT SUMMARY --}}
                    <div class="border-t border-gray-200 bg-gray-50 px-6 py-5">

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">


                            <div class="rounded-lg border border-gray-200 bg-white p-4">

                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Total Payable
                                </div>

                                <div
                                    id="summary_total"
                                    class="mt-1 text-xl font-bold text-gray-900"
                                >
                                    ₹0.00
                                </div>

                            </div>



                            <div class="rounded-lg border border-gray-200 bg-white p-4">

                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Amount Received
                                </div>

                                <div
                                    id="summary_received"
                                    class="mt-1 text-xl font-bold text-gray-900"
                                >
                                    ₹0.00
                                </div>

                            </div>



                            <div class="rounded-lg border border-gray-200 bg-white p-4">

                                <div
                                    id="balance_label"
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    Balance
                                </div>

                                <div
                                    id="summary_balance"
                                    class="mt-1 text-xl font-bold text-gray-900"
                                >
                                    ₹0.00
                                </div>

                            </div>

                        </div>


                        <div
                            id="payment_message"
                            class="mt-4 hidden rounded-lg px-4 py-3 text-sm"
                        >
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
                        Register OPD & Collect Payment
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
            | Doctor filtering
            |--------------------------------------------------------------------------
            */

            const departmentSelect =
                document.getElementById(
                    'department_id'
                );

            const doctorSelect =
                document.getElementById(
                    'doctor_id'
                );


            function filterDoctors()
            {
                if (
                    !departmentSelect ||
                    !doctorSelect
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
                        if (!option.value)
                        {
                            option.hidden = false;
                            return;
                        }

                        const doctorDepartment =
                            option.dataset.department;

                        option.hidden =
                            departmentId &&
                            doctorDepartment !== departmentId;
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
                    doctorSelect.value = '';
                }
            }


            if (
                departmentSelect &&
                doctorSelect
            )
            {
                departmentSelect.addEventListener(
                    'change',
                    filterDoctors
                );

                filterDoctors();
            }


            /*
            |--------------------------------------------------------------------------
            | Billing / payment elements
            |--------------------------------------------------------------------------
            */

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

            const amountReceived =
                document.getElementById(
                    'amount_received'
                );

            const paymentMode =
                document.getElementById(
                    'payment_mode'
                );

            const transactionReference =
                document.getElementById(
                    'transaction_reference'
                );

            const transactionReferenceHelp =
                document.getElementById(
                    'transaction_reference_help'
                );

            const summaryTotal =
                document.getElementById(
                    'summary_total'
                );

            const summaryReceived =
                document.getElementById(
                    'summary_received'
                );

            const summaryBalance =
                document.getElementById(
                    'summary_balance'
                );

            const balanceLabel =
                document.getElementById(
                    'balance_label'
                );

            const paymentMessage =
                document.getElementById(
                    'payment_message'
                );


            /*
            |--------------------------------------------------------------------------
            | Helper functions
            |--------------------------------------------------------------------------
            */

            function numberValue(element)
            {
                if (!element)
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


            /*
            |--------------------------------------------------------------------------
            | Auto-fill cash payment
            |--------------------------------------------------------------------------
            |
            | For cash:
            | - Default Amount Received to the total payable.
            | - Reception can overwrite it if the patient gives a larger note.
            |
            | Example:
            | Total = ₹350
            | Default received = ₹350
            |
            */

            function autoFillCashAmount()
            {
                if (
                    !paymentMode ||
                    !amountReceived ||
                    !totalAmount
                )
                {
                    return;
                }

                if (paymentMode.value !== 'cash')
                {
                    return;
                }

                const total =
                    numberValue(
                        totalAmount
                    );

                const currentReceived =
                    numberValue(
                        amountReceived
                    );

                /*
                 * Only auto-fill when amount is blank or zero.
                 * This prevents overwriting ₹500 manually entered
                 * against a ₹350 bill.
                 */
                if (
                    amountReceived.value === '' ||
                    currentReceived === 0
                )
                {
                    amountReceived.value =
                        total.toFixed(2);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate bill and payment
            |--------------------------------------------------------------------------
            */

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


                /*
                 * Automatically put the full payable amount into
                 * Amount Received when Cash is selected.
                 */
                autoFillCashAmount();


                const received =
                    numberValue(
                        amountReceived
                    );

                const difference =
                    received -
                    total;


                if (summaryTotal)
                {
                    summaryTotal.textContent =
                        currency(total);
                }


                if (summaryReceived)
                {
                    summaryReceived.textContent =
                        currency(received);
                }


                if (
                    !summaryBalance ||
                    !balanceLabel
                )
                {
                    return;
                }


                /*
                 * No charge.
                 */
                if (
                    total === 0 &&
                    received === 0
                )
                {
                    balanceLabel.textContent =
                        'Balance';

                    summaryBalance.textContent =
                        currency(0);

                    summaryBalance.className =
                        'mt-1 text-xl font-bold text-gray-900';

                    if (paymentMessage)
                    {
                        paymentMessage.className =
                            'mt-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600';

                        paymentMessage.textContent =
                            'No OPD charge entered.';
                    }

                    return;
                }


                /*
                 * Exact payment.
                 */
                if (difference === 0)
                {
                    balanceLabel.textContent =
                        'Balance';

                    summaryBalance.textContent =
                        currency(0);

                    summaryBalance.className =
                        'mt-1 text-xl font-bold text-green-700';

                    if (paymentMessage)
                    {
                        paymentMessage.className =
                            'mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700';

                        paymentMessage.textContent =
                            'Full payment received.';
                    }

                    return;
                }


                /*
                 * Amount received greater than total.
                 */
                if (difference > 0)
                {
                    balanceLabel.textContent =
                        'Change to Return';

                    summaryBalance.textContent =
                        currency(difference);

                    summaryBalance.className =
                        'mt-1 text-xl font-bold text-blue-700';

                    if (paymentMessage)
                    {
                        paymentMessage.className =
                            'mt-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700';

                        paymentMessage.textContent =
                            'Return ' +
                            currency(difference) +
                            ' to the patient.';
                    }

                    return;
                }


                /*
                 * Partial / unpaid.
                 */
                const balance =
                    Math.abs(
                        difference
                    );

                balanceLabel.textContent =
                    'Amount Due';

                summaryBalance.textContent =
                    currency(balance);

                summaryBalance.className =
                    'mt-1 text-xl font-bold text-red-700';

                if (paymentMessage)
                {
                    paymentMessage.className =
                        'mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';

                    paymentMessage.textContent =
                        currency(balance) +
                        ' remains unpaid.';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Payment mode / reference
            |--------------------------------------------------------------------------
            */

            function updatePaymentMode()
            {
                if (
                    !paymentMode ||
                    !transactionReference
                )
                {
                    return;
                }

                const mode =
                    paymentMode.value;


                /*
                 * Cash
                 */
                if (mode === 'cash')
                {
                    transactionReference.required =
                        false;

                    transactionReference.placeholder =
                        'Not required for cash';

                    if (transactionReferenceHelp)
                    {
                        transactionReferenceHelp.textContent =
                            'Optional for cash payment.';
                    }

                    /*
                     * Set cash received to full bill by default.
                     */
                    if (amountReceived)
                    {
                        const total =
                            numberValue(
                                totalAmount
                            );

                        amountReceived.value =
                            total.toFixed(2);
                    }

                    calculatePayment();

                    return;
                }


                /*
                 * UPI / Card
                 */
                if (
                    mode === 'upi' ||
                    mode === 'card'
                )
                {
                    transactionReference.required =
                        true;

                    transactionReference.placeholder =
                        mode === 'upi'
                            ? 'Enter UPI transaction reference'
                            : 'Enter card transaction reference';

                    if (transactionReferenceHelp)
                    {
                        transactionReferenceHelp.textContent =
                            'Reference number is required for electronic payment.';
                    }

                    /*
                     * Electronic payment normally equals bill amount.
                     */
                    if (amountReceived)
                    {
                        amountReceived.value =
                            numberValue(
                                totalAmount
                            ).toFixed(2);
                    }

                    calculatePayment();

                    return;
                }


                /*
                 * Credit / MHIS
                 */
                transactionReference.required =
                    false;

                transactionReference.placeholder =
                    'Reference / approval number if available';

                if (transactionReferenceHelp)
                {
                    transactionReferenceHelp.textContent =
                        'Enter a reference number if applicable.';
                }


                /*
                 * Credit / insurance means no cash collected
                 * at reception by default.
                 */
                if (
                    amountReceived &&
                    (
                        mode === 'credit' ||
                        mode === 'mhis'
                    )
                )
                {
                    amountReceived.value =
                        '0.00';
                }

                calculatePayment();
            }


            /*
            |--------------------------------------------------------------------------
            | Field listeners
            |--------------------------------------------------------------------------
            */

            if (consultationFee)
            {
                consultationFee.addEventListener(
                    'input',
                    function ()
                    {
                        /*
                         * If cash / UPI / card, update amount received
                         * when the bill changes.
                         */
                        if (
                            paymentMode &&
                            (
                                paymentMode.value === 'cash' ||
                                paymentMode.value === 'upi' ||
                                paymentMode.value === 'card'
                            )
                        )
                        {
                            amountReceived.value =
                                (
                                    numberValue(
                                        consultationFee
                                    ) +
                                    numberValue(
                                        registrationFee
                                    )
                                ).toFixed(2);
                        }

                        calculatePayment();
                    }
                );
            }


            if (registrationFee)
            {
                registrationFee.addEventListener(
                    'input',
                    function ()
                    {
                        if (
                            paymentMode &&
                            (
                                paymentMode.value === 'cash' ||
                                paymentMode.value === 'upi' ||
                                paymentMode.value === 'card'
                            )
                        )
                        {
                            amountReceived.value =
                                (
                                    numberValue(
                                        consultationFee
                                    ) +
                                    numberValue(
                                        registrationFee
                                    )
                                ).toFixed(2);
                        }

                        calculatePayment();
                    }
                );
            }


            if (amountReceived)
            {
                amountReceived.addEventListener(
                    'input',
                    calculatePayment
                );
            }


            if (paymentMode)
            {
                paymentMode.addEventListener(
                    'change',
                    updatePaymentMode
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Initial calculation
            |--------------------------------------------------------------------------
            */

            updatePaymentMode();

        }
    );

</script>


</x-app-layout>