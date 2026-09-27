<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Staff Medical Benefits
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Annual employee and dependent medical benefit accounts
                </p>
            </div>

        </div>
    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- Messages --}}
            {{-- ========================================================= --}}

            @if (session('success'))

                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">

                    {{ session('success') }}

                </div>

            @endif


            @if ($errors->any())

                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">

                    <div class="font-semibold">
                        Please check the following:
                    </div>

                    <ul class="mt-1 list-disc pl-5">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- ========================================================= --}}
            {{-- Selected Policy --}}
            {{-- ========================================================= --}}

            @php

                $selectedPolicy =
                    $policies->firstWhere(
                        'id',
                        (int) request('policy_id')
                    )
                    ?? $policies->first();

            @endphp


            @if ($selectedPolicy)

                <div class="mb-6 grid gap-4 md:grid-cols-3">


                    {{-- Financial Year --}}

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div class="text-sm font-medium text-gray-500">
                            Financial Year
                        </div>

                        <div class="mt-2 text-xl font-semibold text-gray-900">

                            {{ $selectedPolicy->financial_year_label }}

                        </div>

                        <div class="mt-1 text-xs text-gray-500">

                            {{ $selectedPolicy->financial_year_start->format('d M Y') }}

                            –

                            {{ $selectedPolicy->financial_year_end->format('d M Y') }}

                        </div>

                    </div>


                    {{-- Employee Limit --}}

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div class="text-sm font-medium text-gray-500">
                            Employee Annual Limit
                        </div>

                        <div class="mt-2 text-xl font-semibold text-gray-900">

                            ₹{{ number_format(
                                (float) $selectedPolicy->employee_annual_limit,
                                2
                            ) }}

                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            Employee's own medical expenses
                        </div>

                    </div>


                    {{-- Dependent Limit --}}

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                        <div class="text-sm font-medium text-gray-500">
                            Dependent Family Limit
                        </div>

                        <div class="mt-2 text-xl font-semibold text-gray-900">

                            ₹{{ number_format(
                                (float) $selectedPolicy->dependent_family_annual_limit,
                                2
                            ) }}

                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            One combined annual pool for all eligible dependents
                        </div>

                    </div>

                </div>

            @endif


            {{-- ========================================================= --}}
            {{-- Add Employee to Staff Medical Benefit --}}
            {{-- ========================================================= --}}

            <div class="mb-6 overflow-hidden rounded-xl border border-cyan-200 bg-white shadow-sm">


                <div class="border-b border-cyan-200 bg-cyan-50 px-5 py-4">

                    <h3 class="font-semibold text-cyan-900">
                        Add Employee to Staff Medical Benefit
                    </h3>

                    <p class="mt-1 text-sm text-cyan-700">
                        Select an existing employee from HR and enrol them in a medical benefit policy.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route(
                        'admin.hr.medical-benefits.employees.store'
                    ) }}"
                    class="p-5"
                >

                    @csrf


                    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">


                        {{-- Employee --}}

                        <div class="lg:col-span-2">

                            <label
                                for="benefit_employee_id"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Employee
                            </label>


                            <select
                                id="benefit_employee_id"
                                name="employee_id"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                                <option value="">
                                    Select employee
                                </option>


                                @foreach ($employees as $employee)

                                    <option
                                        value="{{ $employee->id }}"
                                        @selected(
                                            (string) old('employee_id')
                                            ===
                                            (string) $employee->id
                                        )
                                    >

                                        {{ $employee->full_name }}

                                        — {{ $employee->employee_code }}

                                        @if ($employee->department)

                                            — {{ $employee->department->name }}

                                        @endif


                                        @if ($employee->patient)

                                            — UHID {{ $employee->patient->uhid }}

                                        @endif

                                    </option>

                                @endforeach

                            </select>


                            <p class="mt-1 text-xs text-gray-500">
                                Only active employees from the HR employee master are shown.
                            </p>

                        </div>


                        {{-- Policy --}}

                        <div>

                            <label
                                for="benefit_policy_id"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Financial Year
                            </label>


                            <select
                                id="benefit_policy_id"
                                name="policy_id"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                                <option value="">
                                    Select policy
                                </option>


                                @foreach ($policies as $policy)

                                    @if ($policy->is_active)

                                        <option
                                            value="{{ $policy->id }}"
                                            data-start="{{ $policy->financial_year_start->toDateString() }}"
                                            data-end="{{ $policy->financial_year_end->toDateString() }}"
                                            @selected(
                                                (string) old(
                                                    'policy_id',
                                                    $selectedPolicy?->id
                                                )
                                                ===
                                                (string) $policy->id
                                            )
                                        >

                                            {{ $policy->financial_year_label }}

                                        </option>

                                    @endif

                                @endforeach

                            </select>

                        </div>


                        {{-- Eligible From --}}

                        <div>

                            <label
                                for="benefit_eligible_from"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Eligible From
                            </label>


                            <input
                                id="benefit_eligible_from"
                                name="eligible_from"
                                type="date"
                                value="{{ old(
                                    'eligible_from',
                                    $selectedPolicy?->financial_year_start?->toDateString()
                                ) }}"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        {{-- Eligible Until --}}

                        <div>

                            <label
                                for="benefit_eligible_until"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Eligible Until
                            </label>


                            <input
                                id="benefit_eligible_until"
                                name="eligible_until"
                                type="date"
                                value="{{ old('eligible_until') }}"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >


                            <p class="mt-1 text-xs text-gray-500">
                                Leave blank if eligible until the end of the financial year.
                            </p>

                        </div>


                        {{-- Remarks --}}

                        <div class="md:col-span-2">

                            <label
                                for="benefit_remarks"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Remarks
                            </label>


                            <input
                                id="benefit_remarks"
                                name="remarks"
                                type="text"
                                value="{{ old('remarks') }}"
                                placeholder="Optional remarks"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        {{-- Submit --}}

                        {{-- Submit --}}

<div class="flex items-end">

    <button
        type="submit"
        class="w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
        style="background-color: #0891b2;"
    >
        Add Employee
    </button>

</div>
                    </div>

                </form>

            </div>


            {{-- ========================================================= --}}
            {{-- Filters --}}
            {{-- ========================================================= --}}

            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">


                <form
                    method="GET"
                    action="{{ route(
                        'admin.hr.medical-benefits.index'
                    ) }}"
                    class="grid gap-4 md:grid-cols-3"
                >


                    {{-- Employee Search --}}

                    <div>

                        <label
                            for="search"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Search Employee
                        </label>


                        <input
                            id="search"
                            name="search"
                            type="text"
                            value="{{ request('search') }}"
                            placeholder="Name or employee code"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >

                    </div>


                    {{-- Financial Year Filter --}}

                    <div>

                        <label
                            for="policy_id"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Financial Year
                        </label>


                        <select
                            id="policy_id"
                            name="policy_id"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >

                            <option value="">
                                All financial years
                            </option>


                            @foreach ($policies as $policy)

                                <option
                                    value="{{ $policy->id }}"
                                    @selected(
                                        (string) request('policy_id')
                                        ===
                                        (string) $policy->id
                                    )
                                >

                                    {{ $policy->financial_year_label }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Filter Buttons --}}

                    <div class="flex items-end gap-2">

                        <button
                            type="submit"
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm"
                            style="background-color: #0891b2;"
                        >
                            Filter
                        </button>


                        <a
                            href="{{ route(
                                'admin.hr.medical-benefits.index'
                            ) }}"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>


            {{-- ========================================================= --}}
            {{-- Existing Benefit Accounts --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


                <div class="border-b border-gray-200 px-5 py-4">

                    <div class="flex items-center justify-between gap-4">

                        <div>

                            <h3 class="font-semibold text-gray-900">
                                Employee Benefit Accounts
                            </h3>


                            <p class="mt-1 text-sm text-gray-500">

                                {{ $accounts->total() }}

                                account{{ $accounts->total() === 1 ? '' : 's' }}

                            </p>

                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">


                        <thead class="bg-gray-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Employee
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Patient / UHID
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Employee Balance
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Dependent Balance
                                </th>

                                <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100 bg-white">


                            @forelse ($accounts as $account)

                                <tr class="hover:bg-gray-50">


                                    {{-- Employee --}}

                                    <td class="whitespace-nowrap px-5 py-4">

                                        <div class="font-medium text-gray-900">

                                            {{ $account->employee->full_name }}

                                        </div>


                                        <div class="mt-1 text-xs text-gray-500">

                                            {{ $account->employee->employee_code }}


                                            @if ($account->employee->department)

                                                · {{ $account->employee->department->name }}

                                            @endif

                                        </div>


                                        <div class="mt-1 text-xs text-gray-500">

                                            Eligible:

                                            {{ $account->eligible_from->format('d M Y') }}


                                            @if ($account->eligible_until)

                                                –

                                                {{ $account->eligible_until->format('d M Y') }}

                                            @endif

                                        </div>

                                    </td>


                                    {{-- UHID --}}

                                    <td class="whitespace-nowrap px-5 py-4">

                                        @if ($account->employee->patient)

                                            <div class="font-medium text-gray-900">

                                                {{ $account->employee->patient->uhid }}

                                            </div>


                                            <div class="mt-1 text-xs text-green-700">
                                                Linked
                                            </div>

                                        @else

                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">

                                                Not linked

                                            </span>

                                        @endif

                                    </td>


                                    {{-- Employee Balance --}}

                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="font-semibold text-gray-900">

                                            ₹{{ number_format(
                                                (float) $account->employee_balance,
                                                2
                                            ) }}

                                        </div>


                                        <div class="mt-1 text-xs text-gray-500">

                                            of ₹{{ number_format(
                                                (float) $account->employee_entitlement,
                                                2
                                            ) }}

                                        </div>

                                    </td>


                                    {{-- Dependent Balance --}}

                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="font-semibold text-gray-900">

                                            ₹{{ number_format(
                                                (float) $account->dependent_family_balance,
                                                2
                                            ) }}

                                        </div>


                                        <div class="mt-1 text-xs text-gray-500">

                                            of ₹{{ number_format(
                                                (float) $account->dependent_family_entitlement,
                                                2
                                            ) }}

                                        </div>

                                    </td>


                                    {{-- Status --}}

                                    <td class="whitespace-nowrap px-5 py-4 text-center">

                                        @if ($account->status === 'active')

                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">

                                                Active

                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">

                                                {{ ucfirst($account->status) }}

                                            </span>

                                        @endif

                                    </td>


                                    {{-- Action --}}

                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <a
                                            href="{{ route(
                                                'admin.hr.medical-benefits.show',
                                                $account
                                            ) }}"
                                            class="inline-flex rounded-lg px-3 py-2 text-sm font-semibold text-white shadow-sm"
                                            style="background-color: #0891b2;"
                                        >
                                            Manage
                                        </a>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="6"
                                        class="px-5 py-10 text-center text-sm text-gray-500"
                                    >
                                        No Staff Medical Benefit accounts found.
                                    </td>

                                </tr>

                            @endforelse


                        </tbody>

                    </table>

                </div>


                @if ($accounts->hasPages())

                    <div class="border-t border-gray-200 px-5 py-4">

                        {{ $accounts->links() }}

                    </div>

                @endif

            </div>


            {{-- ========================================================= --}}
            {{-- Policy Note --}}
            {{-- ========================================================= --}}

            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">

                <strong>
                    Benefit policy:
                </strong>

                Employee medical expenses use the employee's annual pool.
                Eligible dependents share one combined family pool.
                Unused entitlement expires at the end of the financial year
                and is not carried forward.

            </div>


        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- Automatically adjust eligibility dates when policy changes --}}
    {{-- ============================================================= --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const policySelect =
                document.getElementById(
                    'benefit_policy_id'
                );

            const eligibleFrom =
                document.getElementById(
                    'benefit_eligible_from'
                );

            const eligibleUntil =
                document.getElementById(
                    'benefit_eligible_until'
                );


            if (
                ! policySelect ||
                ! eligibleFrom ||
                ! eligibleUntil
            ) {
                return;
            }


            function updateEligibilityDates() {

                const selectedOption =
                    policySelect.options[
                        policySelect.selectedIndex
                    ];


                if (! selectedOption) {
                    return;
                }


                const start =
                    selectedOption.dataset.start;

                const end =
                    selectedOption.dataset.end;


                if (start) {

                    eligibleFrom.min =
                        start;

                    eligibleUntil.min =
                        start;


                    if (
                        ! eligibleFrom.value
                    ) {
                        eligibleFrom.value =
                            start;
                    }
                }


                if (end) {

                    eligibleFrom.max =
                        end;

                    eligibleUntil.max =
                        end;
                }
            }


            policySelect.addEventListener(
                'change',
                function () {

                    const selectedOption =
                        policySelect.options[
                            policySelect.selectedIndex
                        ];


                    if (
                        selectedOption &&
                        selectedOption.dataset.start
                    ) {
                        eligibleFrom.value =
                            selectedOption.dataset.start;
                    }


                    eligibleUntil.value =
                        '';


                    updateEligibilityDates();
                }
            );


            updateEligibilityDates();

        });
    </script>

</x-app-layout>