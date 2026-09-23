<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Leave Request Register
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Review, approve, reject and cancel employee leave requests.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('admin.hr.leave-balances.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Leave Balances
                </a>

                <a
                    href="{{ route('admin.hr.leave-requests.create') }}"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    New Leave Request
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

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Pending
                    </div>
                    <div class="mt-2 text-3xl font-bold text-amber-900">
                        {{ $pendingCount }}
                    </div>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Approved
                    </div>
                    <div class="mt-2 text-3xl font-bold text-emerald-900">
                        {{ $approvedCount }}
                    </div>
                </div>

                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-red-700">
                        Rejected
                    </div>
                    <div class="mt-2 text-3xl font-bold text-red-900">
                        {{ $rejectedCount }}
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Cancelled
                    </div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $cancelledCount }}
                    </div>
                </div>

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
                    action="{{ route('admin.hr.leave-requests.index') }}"
                    class="grid gap-4 p-6 md:grid-cols-4"
                >

                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Year
                        </label>

                        <input
                            type="number"
                            name="year"
                            value="{{ $year }}"
                            min="2000"
                            max="2100"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                    </div>


                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Status
                        </label>

                        <select
                            name="status"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
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
                                    {{ $employee->employee_code }}
                                    -
                                    {{ $employee->full_name }}
                                </option>
                            @endforeach
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
                            href="{{ route('admin.hr.leave-requests.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </section>



            {{-- REGISTER --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Leave Requests
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ $leaveRequests->total() }} request{{ $leaveRequests->total() === 1 ? '' : 's' }} found.
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Request
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Employee
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Leave
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Dates
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Days
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($leaveRequests as $leaveRequest)

                                <tr>

                                    <td class="px-5 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $leaveRequest->request_no }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $leaveRequest->applied_at?->format('d M Y, h:i A') ?? '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $leaveRequest->employee?->full_name ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $leaveRequest->employee?->employee_code ?? '—' }}

                                            @if ($leaveRequest->employee?->department)
                                                · {{ $leaveRequest->employee->department->name }}
                                            @endif
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="font-medium text-slate-800">
                                            {{ $leaveRequest->leaveType?->name ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $leaveRequest->leaveType?->code ?? '—' }}
                                        </div>

                                        @if ($leaveRequest->reason)
                                            <div class="mt-2 max-w-xs text-xs leading-5 text-slate-500">
                                                {{ $leaveRequest->reason }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 align-top text-sm text-slate-700">
                                        {{ $leaveRequest->date_range_label }}
                                    </td>


                                    <td class="px-5 py-4 text-right align-top font-semibold text-slate-900">
                                        {{ number_format((float) $leaveRequest->total_days, 1) }}
                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        @php
                                            $statusClass = match ($leaveRequest->status) {
                                                'approved' => 'bg-emerald-100 text-emerald-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                                'cancelled' => 'bg-slate-200 text-slate-600',
                                                default => 'bg-amber-100 text-amber-700',
                                            };
                                        @endphp

                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ ucfirst($leaveRequest->status) }}
                                        </span>

                                        @if ($leaveRequest->approvedBy)
                                            <div class="mt-2 text-xs text-slate-400">
                                                {{ $leaveRequest->approvedBy->name }}
                                            </div>
                                        @endif

                                        @if ($leaveRequest->remarks)
                                            <div class="mt-2 max-w-xs text-xs leading-5 text-slate-500">
                                                {{ $leaveRequest->remarks }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="flex min-w-[220px] flex-col gap-2">


                                            @if ($leaveRequest->status === 'pending')

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.hr.leave-requests.approve', $leaveRequest) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                                    >
                                                        Approve
                                                    </button>
                                                </form>


                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.hr.leave-requests.reject', $leaveRequest) }}"
                                                    class="space-y-2"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        required
                                                        maxlength="2000"
                                                        placeholder="Reason for rejection"
                                                        class="block w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-red-500 focus:ring-red-500"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="inline-flex w-full items-center justify-center rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                                                    >
                                                        Reject
                                                    </button>
                                                </form>

                                            @endif


                                            @if (in_array($leaveRequest->status, ['pending', 'approved'], true))

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.hr.leave-requests.cancel', $leaveRequest) }}"
                                                    class="space-y-2"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        required
                                                        maxlength="2000"
                                                        placeholder="Cancellation reason"
                                                        class="block w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                    >
                                                        Cancel Request
                                                    </button>
                                                </form>

                                            @endif


                                            @if (in_array($leaveRequest->status, ['rejected', 'cancelled'], true))

                                                <div class="text-xs text-slate-400">
                                                    No further action available.
                                                </div>

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
                                        No leave requests found for the selected filters.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($leaveRequests->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $leaveRequests->links() }}
                    </div>

                @endif

            </section>

        </div>

    </div>

</x-app-layout>
