<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Staff Medical Benefit
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $benefitAccount->employee->full_name }}
                    · {{ $benefitAccount->employee->employee_code }}
                </p>
            </div>

            <a
                href="{{ route('admin.hr.medical-benefits.index') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back to Benefits
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Messages --}}
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold">
                        Please check the following:
                    </div>

                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Employee / policy summary --}}
            <div class="grid gap-4 lg:grid-cols-3">

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-gray-500">
                        Employee
                    </div>

                    <div class="mt-2 text-lg font-semibold text-gray-900">
                        {{ $benefitAccount->employee->full_name }}
                    </div>

                    <div class="mt-1 text-sm text-gray-600">
                        {{ $benefitAccount->employee->employee_code }}
                    </div>

                    @if ($benefitAccount->employee->department)
                        <div class="mt-1 text-sm text-gray-600">
                            {{ $benefitAccount->employee->department->name }}
                        </div>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-gray-500">
                        Financial Year
                    </div>

                    <div class="mt-2 text-lg font-semibold text-gray-900">
                        {{ $benefitAccount->policy->financial_year_label }}
                    </div>

                    <div class="mt-1 text-sm text-gray-600">
                        {{ $benefitAccount->financial_year_start->format('d M Y') }}
                        –
                        {{ $benefitAccount->financial_year_end->format('d M Y') }}
                    </div>

                    <div class="mt-2 text-xs text-gray-500">
                        Eligible from
                        {{ $benefitAccount->eligible_from->format('d M Y') }}

                        @if ($benefitAccount->eligible_until)
                            to
                            {{ $benefitAccount->eligible_until->format('d M Y') }}
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-gray-500">
                        Account Status
                    </div>

                    <div class="mt-3">
                        @if ($benefitAccount->status === 'active')
                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                Active
                            </span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                                {{ ucfirst($benefitAccount->status) }}
                            </span>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Benefit balances --}}
            <div class="grid gap-4 md:grid-cols-2">

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                Employee Benefit
                            </div>

                            <div class="mt-2 text-2xl font-bold text-gray-900">
                                ₹{{ number_format(
                                    (float) $benefitAccount->employee_balance,
                                    2
                                ) }}
                            </div>

                            <div class="mt-1 text-sm text-gray-500">
                                Remaining
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs text-gray-500">
                                Annual entitlement
                            </div>

                            <div class="mt-1 font-semibold text-gray-900">
                                ₹{{ number_format(
                                    (float) $benefitAccount->employee_entitlement,
                                    2
                                ) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                Utilized
                            </span>

                            <span class="font-medium text-gray-900">
                                ₹{{ number_format(
                                    (float) $benefitAccount->employee_utilized,
                                    2
                                ) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                Dependent Family Benefit
                            </div>

                            <div class="mt-2 text-2xl font-bold text-gray-900">
                                ₹{{ number_format(
                                    (float) $benefitAccount->dependent_family_balance,
                                    2
                                ) }}
                            </div>

                            <div class="mt-1 text-sm text-gray-500">
                                Combined balance for all eligible dependents
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs text-gray-500">
                                Annual entitlement
                            </div>

                            <div class="mt-1 font-semibold text-gray-900">
                                ₹{{ number_format(
                                    (float) $benefitAccount->dependent_family_entitlement,
                                    2
                                ) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                Utilized
                            </span>

                            <span class="font-medium text-gray-900">
                                ₹{{ number_format(
                                    (float) $benefitAccount->dependent_family_utilized,
                                    2
                                ) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Employee Patient / UHID linkage --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">
                        Employee Patient / UHID Link
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Link the HR employee record to the correct Patient Registry record.
                    </p>
                </div>

                <div class="p-5">

                    @if ($benefitAccount->employee->patient)

                        @php
                            $linkedPatient = $benefitAccount->employee->patient;
                        @endphp

                        <div class="flex flex-col gap-4 rounded-lg border border-green-200 bg-green-50 p-4 md:flex-row md:items-center md:justify-between">

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-green-700">
                                    Linked Patient
                                </div>

                                <div class="mt-1 font-semibold text-gray-900">
                                    {{ $linkedPatient->full_name }}
                                </div>

                                <div class="mt-1 text-sm text-gray-700">
                                    UHID:
                                    <strong>{{ $linkedPatient->uhid }}</strong>
                                </div>

                                @if ($linkedPatient->phone)
                                    <div class="mt-1 text-sm text-gray-600">
                                        Phone: {{ $linkedPatient->phone }}
                                    </div>
                                @endif

                                @if ($linkedPatient->date_of_birth)
                                    <div class="mt-1 text-sm text-gray-600">
                                        DOB:
                                        {{ $linkedPatient->date_of_birth->format('d M Y') }}
                                    </div>
                                @endif
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'admin.hr.medical-benefits.employee-patient.unlink',
                                    $benefitAccount->employee
                                ) }}"
                                onsubmit="return confirm(
                                    'Remove this Patient/UHID link?'
                                );"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50"
                                >
                                    Remove Link
                                </button>
                            </form>

                        </div>

                    @else

                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            This employee is not yet linked to a Patient/UHID.
                            Search the Patient Registry and select the correct record.
                            Do not link based on name alone.
                        </div>

                        <div>
                            <label
                                for="patient-search"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Search Patient Registry
                            </label>

                            <div class="flex gap-2">
                                <input
                                    id="patient-search"
                                    type="text"
                                    placeholder="UHID, MRD, name or phone"
                                    autocomplete="off"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                >

                                <button
                                    id="patient-search-button"
                                    type="button"
                                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm"
                                    style="background-color: #0891b2;"
                                >
                                    Search
                                </button>
                            </div>

                            <div
                                id="patient-search-message"
                                class="mt-3 hidden text-sm"
                            ></div>

                            <div
                                id="patient-search-results"
                                class="mt-4 space-y-3"
                            ></div>
                        </div>

                    @endif

                </div>
            </div>

                            {{-- Dependents --}}
<div class="rounded-xl border border-gray-200 bg-white shadow-sm">

    <div class="border-b border-gray-200 px-5 py-4">
        <h3 class="font-semibold text-gray-900">
            Registered Dependents
        </h3>

        <p class="mt-1 text-sm text-gray-500">
            Eligible dependents are spouse and children only.
            All registered dependents share one annual family benefit pool.
        </p>
    </div>

    <div class="p-5">

        {{-- Add dependent --}}
        <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4">

            <div class="font-semibold text-gray-900">
                Add Dependent
            </div>

            <p class="mt-1 text-sm text-gray-500">
                Search the Patient Registry and select the correct UHID.
            </p>

            <div class="mt-4">
                <label
                    for="dependent-patient-search"
                    class="mb-1 block text-sm font-medium text-gray-700"
                >
                    Search Patient Registry
                </label>

                <div class="flex gap-2">
                    <input
                        id="dependent-patient-search"
                        type="text"
                        placeholder="UHID, MRD, name or phone"
                        autocomplete="off"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    >

                    <button
                        id="dependent-patient-search-button"
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm"
                        style="background-color: #0891b2;"
                    >
                        Search
                    </button>
                </div>

                <div
                    id="dependent-patient-search-message"
                    class="mt-3 hidden text-sm"
                ></div>

                <div
                    id="dependent-patient-search-results"
                    class="mt-4 space-y-3"
                ></div>
            </div>

        </div>

        {{-- Existing dependents --}}
        <div>
            <h4 class="mb-3 text-sm font-semibold text-gray-900">
                Current Dependents
            </h4>

            @forelse (
                $benefitAccount->employee->medicalDependents
                    ->sortByDesc('is_active')
                as $dependent
            )

                <div class="mb-3 rounded-lg border border-gray-200 p-4 last:mb-0">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                        <div>
                            <div class="font-medium text-gray-900">
                                @if ($dependent->patient)
                                    {{ $dependent->patient->full_name }}
                                @else
                                    Patient record not linked
                                @endif
                            </div>

                            <div class="mt-1 text-sm text-gray-600">
                                {{ ucfirst($dependent->relationship) }}

                                @if ($dependent->patient)
                                    · {{ $dependent->patient->uhid }}
                                @endif
                            </div>

                            <div class="mt-1 text-xs text-gray-500">
                                @if ($dependent->eligible_from)
                                    Eligible from
                                    {{ $dependent->eligible_from->format('d M Y') }}
                                @endif

                                @if ($dependent->eligible_until)
                                    to
                                    {{ $dependent->eligible_until->format('d M Y') }}
                                @endif
                            </div>
                        </div>

                                    <div class="flex items-center gap-2">

    @if ($dependent->is_active)

        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">
            Active
        </span>

        <form
            method="POST"
            action="{{ route(
                'admin.hr.medical-benefits.dependents.deactivate',
                [
                    'benefitAccount' => $benefitAccount,
                    'dependent' => $dependent,
                ]
            ) }}"
            onsubmit="return confirm(
                'Deactivate this dependent? Previous benefit history will be preserved.'
            );"
        >
            @csrf
            @method('PATCH')

            <button
                type="submit"
                class="rounded-lg border border-red-300 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-50"
            >
                Deactivate
            </button>
        </form>

    @else

        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
            Inactive
        </span>

    @endif

</div>

                    </div>

                </div>

            @empty

                <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center">
                    <div class="text-sm font-medium text-gray-700">
                        No dependents registered
                    </div>

                    <div class="mt-1 text-sm text-gray-500">
                        Search the Patient Registry above to register
                        a spouse or child.
                    </div>
                </div>

            @endforelse
        </div>

    </div>

</div>
            {{-- Benefit ledger --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="font-semibold text-gray-900">
                        Benefit Ledger
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Utilizations and reversals for this financial-year account.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Date
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Beneficiary
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Type
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Source
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Amount
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">

                            @forelse (
                                $benefitAccount->transactions
                                    ->sortByDesc('transaction_date')
                                as $transaction
                            )
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $transaction->transaction_date->format('d M Y') }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $transaction->patient->full_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ ucfirst($transaction->beneficiary_type) }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ ucfirst($transaction->transaction_type) }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $transaction->source_reference ?? $transaction->source_type }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold">
                                        @if ($transaction->transaction_type === 'reversal')
                                            <span class="text-green-700">
                                                +₹{{ number_format((float) $transaction->amount, 2) }}
                                            </span>
                                        @else
                                            <span class="text-gray-900">
                                                -₹{{ number_format((float) $transaction->amount, 2) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td
                                        colspan="5"
                                        class="px-5 py-10 text-center text-sm text-gray-500"
                                    >
                                        No benefit transactions have been recorded.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>

    @if (! $benefitAccount->employee->patient)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const input = document.getElementById('patient-search');
                const button = document.getElementById('patient-search-button');
                const results = document.getElementById('patient-search-results');
                const message = document.getElementById('patient-search-message');

                const searchUrl = @json(
                    route('admin.hr.medical-benefits.patient-search')
                );

                const linkUrl = @json(
                    route(
                        'admin.hr.medical-benefits.employee-patient.link',
                        $benefitAccount->employee
                    )
                );

                const csrfToken = @json(csrf_token());

                function escapeHtml(value) {
                    const div = document.createElement('div');
                    div.textContent = value ?? '';
                    return div.innerHTML;
                }

                function showMessage(text, isError = false) {
                    message.textContent = text;
                    message.classList.remove(
                        'hidden',
                        'text-red-700',
                        'text-gray-600'
                    );

                    message.classList.add(
                        isError ? 'text-red-700' : 'text-gray-600'
                    );
                }

                function renderPatients(patients) {
                    results.innerHTML = '';

                    if (!patients.length) {
                        showMessage(
                            'No matching patient records were found.'
                        );
                        return;
                    }

                    showMessage(
                        patients.length +
                        ' matching patient record(s) found.'
                    );

                    patients.forEach(function (patient) {
                        const wrapper = document.createElement('div');

                        wrapper.className =
                            'rounded-lg border border-gray-200 p-4';

                        wrapper.innerHTML = `
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        ${escapeHtml(patient.name)}
                                    </div>

                                    <div class="mt-1 text-sm text-gray-700">
                                        UHID:
                                        <strong>${escapeHtml(patient.uhid)}</strong>
                                    </div>

                                    <div class="mt-1 text-xs text-gray-500">
                                        ${patient.mrd_number
                                            ? 'MRD: ' + escapeHtml(patient.mrd_number) + ' · '
                                            : ''}
                                        ${patient.sex
                                            ? escapeHtml(patient.sex) + ' · '
                                            : ''}
                                        ${patient.date_of_birth
                                            ? 'DOB: ' + escapeHtml(patient.date_of_birth) + ' · '
                                            : ''}
                                        ${patient.phone
                                            ? 'Phone: ' + escapeHtml(patient.phone)
                                            : ''}
                                    </div>
                                </div>

                                <form
                                    method="POST"
                                    action="${escapeHtml(linkUrl)}"
                                    onsubmit="return confirm(
                                        'Link this Patient/UHID to the employee?'
                                    );"
                                >
                                    <input
                                        type="hidden"
                                        name="_token"
                                        value="${escapeHtml(csrfToken)}"
                                    >

                                    <input
                                        type="hidden"
                                        name="_method"
                                        value="PUT"
                                    >

                                    <input
                                        type="hidden"
                                        name="patient_id"
                                        value="${escapeHtml(patient.id)}"
                                    >

                                    <button
                                        type="submit"
                                        class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm"
                                        style="background-color: #0891b2;"
                                    >
                                        Link Patient
                                    </button>
                                </form>
                            </div>
                        `;

                        results.appendChild(wrapper);
                    });
                }

                async function searchPatients() {
                    const query = input.value.trim();

                    results.innerHTML = '';

                    if (query.length < 2) {
                        showMessage(
                            'Enter at least 2 characters to search.',
                            true
                        );
                        return;
                    }

                    button.disabled = true;
                    button.textContent = 'Searching...';

                    showMessage('Searching Patient Registry...');

                    try {
                        const response = await fetch(
                            searchUrl +
                            '?q=' +
                            encodeURIComponent(query),
                            {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            }
                        );

                        if (!response.ok) {
                            throw new Error(
                                'Patient search failed.'
                            );
                        }

                        const data = await response.json();

                        renderPatients(data.patients ?? []);
                    } catch (error) {
                        showMessage(
                            'Unable to search the Patient Registry.',
                            true
                        );
                    } finally {
                        button.disabled = false;
                        button.textContent = 'Search';
                    }
                }

                button.addEventListener(
                    'click',
                    searchPatients
                );

                input.addEventListener(
                    'keydown',
                    function (event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            searchPatients();
                        }
                    }
                );
            });
        </script>
    @endif

                   <script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById(
            'dependent-patient-search'
        );

        const button = document.getElementById(
            'dependent-patient-search-button'
        );

        const results = document.getElementById(
            'dependent-patient-search-results'
        );

        const message = document.getElementById(
            'dependent-patient-search-message'
        );

        if (!input || !button || !results || !message) {
            return;
        }

        const searchUrl = @json(
            route('admin.hr.medical-benefits.patient-search')
        );

        const storeUrl = @json(
            route(
                'admin.hr.medical-benefits.dependents.store',
                $benefitAccount
            )
        );

        const csrfToken = @json(csrf_token());

        const defaultEligibleFrom = @json(
            $benefitAccount->eligible_from->toDateString()
        );

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function showMessage(text, isError = false) {
            message.textContent = text;

            message.classList.remove(
                'hidden',
                'text-red-700',
                'text-gray-600'
            );

            message.classList.add(
                isError
                    ? 'text-red-700'
                    : 'text-gray-600'
            );
        }

        function renderPatients(patients) {
            results.innerHTML = '';

            if (!patients.length) {
                showMessage(
                    'No matching patient records were found.'
                );

                return;
            }

            showMessage(
                patients.length +
                ' matching patient record(s) found.'
            );

            patients.forEach(function (patient) {
                const wrapper = document.createElement('div');

                wrapper.className =
                    'rounded-lg border border-gray-200 bg-white p-4';

                wrapper.innerHTML = `
                    <form
                        method="POST"
                        action="${escapeHtml(storeUrl)}"
                        onsubmit="return confirm(
                            'Register this patient as an eligible dependent?'
                        );"
                    >
                        <input
                            type="hidden"
                            name="_token"
                            value="${escapeHtml(csrfToken)}"
                        >

                        <input
                            type="hidden"
                            name="patient_id"
                            value="${escapeHtml(patient.id)}"
                        >

                        <div class="grid gap-4 lg:grid-cols-4 lg:items-end">

                            <div class="lg:col-span-2">
                                <div class="font-semibold text-gray-900">
                                    ${escapeHtml(patient.name)}
                                </div>

                                <div class="mt-1 text-sm text-gray-700">
                                    UHID:
                                    <strong>
                                        ${escapeHtml(patient.uhid)}
                                    </strong>
                                </div>

                                <div class="mt-1 text-xs text-gray-500">
                                    ${patient.mrd_number
                                        ? 'MRD: ' +
                                          escapeHtml(patient.mrd_number) +
                                          ' · '
                                        : ''}

                                    ${patient.sex
                                        ? escapeHtml(patient.sex) +
                                          ' · '
                                        : ''}

                                    ${patient.date_of_birth
                                        ? 'DOB: ' +
                                          escapeHtml(patient.date_of_birth) +
                                          ' · '
                                        : ''}

                                    ${patient.phone
                                        ? 'Phone: ' +
                                          escapeHtml(patient.phone)
                                        : ''}
                                </div>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-xs font-medium text-gray-700"
                                >
                                    Relationship
                                </label>

                                <select
                                    name="relationship"
                                    required
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                >
                                    <option value="">
                                        Select
                                    </option>

                                    <option value="spouse">
                                        Spouse
                                    </option>

                                    <option value="child">
                                        Child
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-xs font-medium text-gray-700"
                                >
                                    Eligible From
                                </label>

                                <input
                                    type="date"
                                    name="eligible_from"
                                    required
                                    value="${escapeHtml(defaultEligibleFrom)}"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                >
                            </div>

                        </div>

                        <div class="mt-4 flex justify-end">
                            <button
                                type="submit"
                                class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm"
                                style="background-color: #0891b2;"
                            >
                                Register Dependent
                            </button>
                        </div>
                    </form>
                `;

                results.appendChild(wrapper);
            });
        }

        async function searchPatients() {
            const query = input.value.trim();

            results.innerHTML = '';

            if (query.length < 2) {
                showMessage(
                    'Enter at least 2 characters to search.',
                    true
                );

                return;
            }

            button.disabled = true;
            button.textContent = 'Searching...';

            showMessage(
                'Searching Patient Registry...'
            );

            try {
                const response = await fetch(
                    searchUrl +
                        '?q=' +
                        encodeURIComponent(query),
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        'Patient search failed.'
                    );
                }

                const data = await response.json();

                renderPatients(
                    data.patients ?? []
                );
            } catch (error) {
                showMessage(
                    'Unable to search the Patient Registry.',
                    true
                );
            } finally {
                button.disabled = false;
                button.textContent = 'Search';
            }
        }

        button.addEventListener(
            'click',
            searchPatients
        );

        input.addEventListener(
            'keydown',
            function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchPatients();
                }
            }
        );
    });
</script>
</x-app-layout>