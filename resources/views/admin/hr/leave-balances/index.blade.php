<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Employee Leave Balances
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Maintain yearly leave entitlement, opening balance and manual adjustments for employees.
                </p>

            </div>

            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('admin.hr.leave-types.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Leave Type Master
                </a>

                <a
                    href="{{ route('admin.hr.index') }}"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    HR Dashboard
                </a>

            </div>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4">

                    <div class="font-semibold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- FILTERS --}}
            {{-- ========================================================= --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Balance Period
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Select a leave year and optionally focus on one employee.
                    </p>

                </div>


                <form
                    method="GET"
                    action="{{ route('admin.hr.leave-balances.index') }}"
                    class="grid gap-4 p-6 md:grid-cols-[180px_1fr_auto]"
                >

                    <div>

                        <label class="block text-sm font-semibold text-slate-700">
                            Leave Year
                        </label>

                        <input
                            type="number"
                            name="year"
                            value="{{ $year }}"
                            min="2000"
                            max="2100"
                            required
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >

                    </div>


                    <div>

                        <label class="block text-sm font-semibold text-slate-700">
                            Employee
                        </label>

                        <select
                            name="employee_id"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >

                            <option value="">
                                All Active Employees
                            </option>

                            @foreach ($employeeFilter as $employeeOption)

                                <option
                                    value="{{ $employeeOption->id }}"
                                    @selected((string) request('employee_id') === (string) $employeeOption->id)
                                >
                                    {{ $employeeOption->employee_code }}
                                    -
                                    {{ $employeeOption->full_name }}

                                    @if ($employeeOption->department)
                                        ({{ $employeeOption->department->name }})
                                    @endif
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="flex items-end">

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 md:w-auto"
                        >
                            Apply
                        </button>

                    </div>

                </form>

            </section>



            {{-- ========================================================= --}}
            {{-- BALANCE CARDS --}}
            {{-- ========================================================= --}}

            @forelse ($employees as $employee)

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                    <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-5">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>

                                <div class="flex flex-wrap items-center gap-2">

                                    <h3 class="text-lg font-bold text-slate-900">
                                        {{ $employee->full_name }}
                                    </h3>

                                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ $employee->employee_code }}
                                    </span>

                                    @if ($employee->is_active)

                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Active
                                        </span>

                                    @else

                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            Inactive
                                        </span>

                                    @endif

                                </div>


                                <div class="mt-2 text-sm text-slate-500">

                                    {{ $employee->designation ?: 'No designation' }}

                                    @if ($employee->department)
                                        · {{ $employee->department->name }}
                                    @endif

                                    · Leave Year {{ $year }}

                                </div>

                            </div>

                        </div>

                    </div>



                    <form
                        method="POST"
                        action="{{ route('admin.hr.leave-balances.update', $employee) }}"
                    >

                        @csrf
                        @method('PUT')

                        <input
                            type="hidden"
                            name="leave_year"
                            value="{{ $year }}"
                        >


                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-slate-200">

                                <thead class="bg-slate-50">

                                    <tr>

                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Leave Type
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Opening
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Entitlement
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Adjustment
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Available
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Used
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Remaining
                                        </th>

                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Remarks
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-slate-100 bg-white">

                                    @foreach ($leaveTypes as $leaveType)

                                        @php
                                            $row =
                                                $balanceMatrix[
                                                    $employee->id
                                                ][
                                                    $leaveType->id
                                                ];

                                            $remaining =
                                                (float) $row[
                                                    'remaining_days'
                                                ];
                                        @endphp

                                        <tr>


                                            <td class="px-5 py-4 align-top">

                                                <input
                                                    type="hidden"
                                                    name="balances[{{ $loop->index }}][leave_type_id]"
                                                    value="{{ $leaveType->id }}"
                                                >

                                                <div class="font-semibold text-slate-900">
                                                    {{ $leaveType->name }}
                                                </div>

                                                <div class="mt-1 flex flex-wrap gap-2 text-xs">

                                                    <span class="rounded bg-slate-100 px-2 py-0.5 font-semibold text-slate-600">
                                                        {{ $leaveType->code }}
                                                    </span>

                                                    @if ($leaveType->is_paid)

                                                        <span class="rounded bg-blue-50 px-2 py-0.5 font-medium text-blue-700">
                                                            Paid
                                                        </span>

                                                    @else

                                                        <span class="rounded bg-amber-50 px-2 py-0.5 font-medium text-amber-700">
                                                            Unpaid
                                                        </span>

                                                    @endif

                                                </div>

                                            </td>


                                            <td class="px-4 py-4 align-top">

                                                <input
                                                    type="number"
                                                    name="balances[{{ $loop->index }}][opening_balance]"
                                                    value="{{ number_format((float) $row['opening_balance'], 1, '.', '') }}"
                                                    min="0"
                                                    max="999.5"
                                                    step="0.5"
                                                    required
                                                    class="block w-24 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                                >

                                            </td>


                                            <td class="px-4 py-4 align-top">

                                                <input
                                                    type="number"
                                                    name="balances[{{ $loop->index }}][entitlement]"
                                                    value="{{ number_format((float) $row['entitlement'], 1, '.', '') }}"
                                                    min="0"
                                                    max="999.5"
                                                    step="0.5"
                                                    required
                                                    class="block w-24 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                                >

                                            </td>


                                            <td class="px-4 py-4 align-top">

                                                <input
                                                    type="number"
                                                    name="balances[{{ $loop->index }}][adjustment]"
                                                    value="{{ number_format((float) $row['adjustment'], 1, '.', '') }}"
                                                    min="-999.5"
                                                    max="999.5"
                                                    step="0.5"
                                                    required
                                                    class="block w-24 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                                >

                                            </td>


                                            <td class="px-4 py-4 text-right align-top font-semibold text-slate-700">
                                                {{ number_format((float) $row['available_days'], 1) }}
                                            </td>


                                            <td class="px-4 py-4 text-right align-top font-semibold text-blue-700">
                                                {{ number_format((float) $row['used_days'], 1) }}
                                            </td>


                                            <td class="px-4 py-4 text-right align-top">

                                                <span
                                                    class="inline-flex min-w-[72px] justify-end rounded-lg px-2.5 py-1.5 text-sm font-bold
                                                        {{ $remaining < 0
                                                            ? 'bg-red-50 text-red-700'
                                                            : ($remaining <= 2
                                                                ? 'bg-amber-50 text-amber-700'
                                                                : 'bg-emerald-50 text-emerald-700') }}"
                                                >
                                                    {{ number_format($remaining, 1) }}
                                                </span>

                                            </td>


                                            <td class="px-5 py-4 align-top">

                                                <input
                                                    type="text"
                                                    name="balances[{{ $loop->index }}][remarks]"
                                                    value="{{ $row['record']?->remarks }}"
                                                    maxlength="1000"
                                                    placeholder="Optional"
                                                    class="block min-w-[180px] w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                                >

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>



                        <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

                            <div class="text-xs leading-5 text-slate-500">
                                Used leave is calculated from approved leave requests. Saving this form does not alter historical leave requests.
                            </div>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                Save {{ $year }} Balances
                            </button>

                        </div>

                    </form>

                </section>

            @empty

                <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">

                    <div class="font-semibold text-slate-800">
                        No employees found
                    </div>

                    <p class="mt-2 text-sm text-slate-500">
                        No employee records match the selected filter.
                    </p>

                </section>

            @endforelse



            {{-- ========================================================= --}}
            {{-- EXPLANATION --}}
            {{-- ========================================================= --}}

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h3 class="font-semibold text-slate-900">
                    Balance Calculation
                </h3>

                <div class="mt-4 grid gap-4 md:grid-cols-3">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Available
                        </div>

                        <div class="mt-2 text-sm font-semibold text-slate-800">
                            Opening + Entitlement + Adjustment
                        </div>

                    </div>


                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Used
                        </div>

                        <div class="mt-2 text-sm font-semibold text-slate-800">
                            Approved leave requests
                        </div>

                    </div>


                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Remaining
                        </div>

                        <div class="mt-2 text-sm font-semibold text-slate-800">
                            Available - Used
                        </div>

                    </div>

                </div>

            </section>


        </div>

    </div>

</x-app-layout>
