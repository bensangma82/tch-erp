<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Contract Register
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Track employee contracts, renewals, expiry and termination status.
                </p>

            </div>

            <div class="flex flex-wrap gap-2">

                <form
                    method="POST"
                    action="{{ route('admin.hr.contracts.refresh-statuses') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                    >
                        Refresh Expiry Status
                    </button>
                </form>

                <a
                    href="{{ route('admin.hr.contracts.create') }}"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    New Contract
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
                        Unable to complete the action:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>

            @endif



            {{-- SUMMARY --}}

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Active Contracts
                    </div>
                    <div class="mt-2 text-3xl font-bold text-emerald-900">
                        {{ $activeCount }}
                    </div>
                </div>

                <a
                    href="{{ route('admin.hr.contracts.index', ['expiring' => '30']) }}"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition hover:border-amber-300"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Expiring ≤30 Days
                    </div>
                    <div class="mt-2 text-3xl font-bold text-amber-900">
                        {{ $expiringSoonCount }}
                    </div>
                </a>

                <a
                    href="{{ route('admin.hr.contracts.index', ['expiring' => 'expired']) }}"
                    class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm transition hover:border-red-300"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-red-700">
                        Expired
                    </div>
                    <div class="mt-2 text-3xl font-bold text-red-900">
                        {{ $expiredCount }}
                    </div>
                </a>

                <a
                    href="{{ route('admin.hr.contracts.index', ['status' => 'terminated']) }}"
                    class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition hover:border-slate-300"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Terminated
                    </div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $terminatedCount }}
                    </div>
                </a>

            </div>



            {{-- FILTERS --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">
                        Filters
                    </h3>
                </div>


                <form
                    method="GET"
                    action="{{ route('admin.hr.contracts.index') }}"
                    class="grid gap-4 p-6 md:grid-cols-5"
                >

                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Status
                        </label>

                        <select
                            name="status"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                            <option value="renewed" @selected(request('status') === 'renewed')>Renewed</option>
                            <option value="terminated" @selected(request('status') === 'terminated')>Terminated</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Employee
                        </label>

                        <select
                            name="employee_id"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">All Employees</option>

                            @foreach ($employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    @selected((string) request('employee_id') === (string) $employee->id)
                                >
                                    {{ $employee->employee_code }} - {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Department
                        </label>

                        <select
                            name="department_id"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">All Departments</option>

                            @foreach ($departments as $department)
                                <option
                                    value="{{ $department->id }}"
                                    @selected((string) request('department_id') === (string) $department->id)
                                >
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Expiry
                        </label>

                        <select
                            name="expiring"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">Any</option>
                            <option value="30" @selected(request('expiring') === '30')>Within 30 Days</option>
                            <option value="expired" @selected(request('expiring') === 'expired')>Past End Date</option>
                        </select>
                    </div>


                    <div class="flex items-end gap-2">

                        <button
                            type="submit"
                            class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Apply
                        </button>

                        <a
                            href="{{ route('admin.hr.contracts.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </section>



            {{-- CONTRACT TABLE --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Employee Contracts
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ $contracts->total() }} contract{{ $contracts->total() === 1 ? '' : 's' }} found.
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contract</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type / Designation</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Expiry</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($contracts as $contract)

                                @php
                                    $daysRemaining = $contract->days_remaining;

                                    $statusClass = match ($contract->status) {
                                        'active' => 'bg-emerald-100 text-emerald-700',
                                        'expired' => 'bg-red-100 text-red-700',
                                        'renewed' => 'bg-blue-100 text-blue-700',
                                        'terminated' => 'bg-slate-200 text-slate-700',
                                        'cancelled' => 'bg-slate-200 text-slate-600',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp

                                <tr>

                                    <td class="px-5 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $contract->contract_no }}
                                        </div>

                                        @if ($contract->reference_no)
                                            <div class="mt-1 text-xs text-slate-500">
                                                Ref: {{ $contract->reference_no }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $contract->employee?->full_name ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $contract->employee?->employee_code ?? '—' }}

                                            @if ($contract->department)
                                                · {{ $contract->department->name }}
                                            @endif
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="font-medium text-slate-800">
                                            {{ ucfirst(str_replace('_', ' ', $contract->contract_type)) }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $contract->designation ?: 'No designation recorded' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 align-top text-sm text-slate-700">

                                        <div>
                                            {{ $contract->start_date?->format('d M Y') ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-slate-400">
                                            to
                                        </div>

                                        <div class="mt-1">
                                            {{ $contract->end_date?->format('d M Y') ?? '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        @if ($contract->status === 'active' && $daysRemaining !== null)

                                            @if ($daysRemaining < 0)

                                                <span class="font-semibold text-red-700">
                                                    {{ abs($daysRemaining) }} day(s) overdue
                                                </span>

                                            @elseif ($daysRemaining <= 30)

                                                <span class="font-semibold text-amber-700">
                                                    {{ $daysRemaining }} day(s) remaining
                                                </span>

                                            @else

                                                <span class="font-semibold text-slate-700">
                                                    {{ $daysRemaining }} day(s)
                                                </span>

                                            @endif

                                        @else

                                            <span class="text-slate-400">
                                                —
                                            </span>

                                        @endif


                                        @if ($contract->renewal_due_date)
                                            <div class="mt-2 text-xs text-slate-500">
                                                Renewal due:
                                                {{ $contract->renewal_due_date->format('d M Y') }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ ucfirst($contract->status) }}
                                        </span>

                                        @if ($contract->previousContract)
                                            <div class="mt-2 text-xs text-slate-400">
                                                Renewal of {{ $contract->previousContract->contract_no }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="flex min-w-[140px] flex-col gap-2">

                                            @if (in_array($contract->status, ['active', 'expired'], true))

                                                <a
                                                    href="{{ route('admin.hr.contracts.edit', $contract) }}"
                                                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                >
                                                    Manage
                                                </a>

                                            @else

                                                <span class="text-xs text-slate-400">
                                                    Closed
                                                </span>

                                            @endif

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center text-sm text-slate-500"
                                    >
                                        No contracts found for the selected filters.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($contracts->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $contracts->links() }}
                    </div>

                @endif

            </section>


        </div>

    </div>

</x-app-layout>
