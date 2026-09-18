<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Administration
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Administrative requests, approvals and execution tracking
                </p>
            </div>

            <a
                href="{{ route('administration.requests.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
            >
                New Administrative Request
            </a>

        </div>

    </x-slot>


    @php

        $statusLabels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'verified' => 'Verified',
            'pending_ms_approval' => 'Pending MS Approval',
            'ms_approved' => 'MS Approved',
            'ms_rejected' => 'MS Rejected',
            'higher_approval_required' => 'Higher Approval Required',
            'higher_approved' => 'Higher Approved',
            'execution_in_progress' => 'Execution In Progress',
            'executed' => 'Executed',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
        ];

        $statusClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'submitted' => 'bg-blue-50 text-blue-700',
            'verified' => 'bg-cyan-50 text-cyan-700',
            'pending_ms_approval' => 'bg-amber-50 text-amber-700',
            'ms_approved' => 'bg-emerald-50 text-emerald-700',
            'ms_rejected' => 'bg-red-50 text-red-700',
            'higher_approval_required' => 'bg-orange-50 text-orange-700',
            'higher_approved' => 'bg-teal-50 text-teal-700',
            'execution_in_progress' => 'bg-violet-50 text-violet-700',
            'executed' => 'bg-indigo-50 text-indigo-700',
            'closed' => 'bg-slate-200 text-slate-700',
            'cancelled' => 'bg-slate-100 text-slate-500',
        ];

        $typeLabels = [
            'purchase' => 'Purchase',
            'recruitment' => 'Recruitment',
            'finance' => 'Finance',
            'contract' => 'Contract',
            'project' => 'Project',
            'maintenance' => 'Maintenance',
            'hr' => 'HR',
            'other' => 'Other',
        ];

        $priorityClasses = [
            'low' => 'bg-slate-100 text-slate-600',
            'normal' => 'bg-blue-50 text-blue-700',
            'high' => 'bg-amber-50 text-amber-700',
            'urgent' => 'bg-red-50 text-red-700',
        ];

    @endphp


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            @if (session('error'))

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>

            @endif


            {{-- ========================================================= --}}
            {{-- DASHBOARD SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Total Requests
                    </div>

                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($summary['total'] ?? 0) }}
                    </div>
                </div>


                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Pending MS Approval
                    </div>

                    <div class="mt-2 text-3xl font-bold text-amber-800">
                        {{ number_format($summary['pending_ms'] ?? 0) }}
                    </div>
                </div>


                <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-orange-700">
                        Higher Approval
                    </div>

                    <div class="mt-2 text-3xl font-bold text-orange-800">
                        {{ number_format($summary['higher_approval'] ?? 0) }}
                    </div>
                </div>


                <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-violet-700">
                        Pending Execution
                    </div>

                    <div class="mt-2 text-3xl font-bold text-violet-800">
                        {{ number_format($summary['pending_execution'] ?? 0) }}
                    </div>
                </div>


                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Closed
                    </div>

                    <div class="mt-2 text-3xl font-bold text-emerald-800">
                        {{ number_format($summary['closed'] ?? 0) }}
                    </div>
                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- GOVERNANCE NOTE --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="grid gap-4 lg:grid-cols-2">

                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <div class="text-sm font-bold text-indigo-900">
                            Medical Superintendent
                        </div>

                        <div class="mt-1 text-sm leading-6 text-indigo-700">
                            Final decision-making authority for hospital priorities, finance,
                            recruitment and purchase within delegated powers.
                        </div>
                    </div>


                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-sm font-bold text-slate-900">
                            Administrator
                        </div>

                        <div class="mt-1 text-sm leading-6 text-slate-600">
                            Verification, documentation, coordination, execution and follow-up
                            of approved administrative decisions.
                        </div>
                    </div>

                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- FILTERS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('administration.requests.index') }}"
                    class="grid gap-4 md:grid-cols-2 lg:grid-cols-5"
                >

                    <div class="lg:col-span-2">

                        <label
                            for="search"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Search
                        </label>

                        <input
                            id="search"
                            name="search"
                            type="text"
                            value="{{ request('search') }}"
                            placeholder="Request no, title or description..."
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label
                            for="request_type"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Type
                        </label>

                        <select
                            id="request_type"
                            name="request_type"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                All Types
                            </option>

                            @foreach ($typeLabels as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(request('request_type') === $value)
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label
                            for="status"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            @foreach ($statusLabels as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(request('status') === $value)
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label
                            for="priority"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Priority
                        </label>

                        <select
                            id="priority"
                            name="priority"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                All Priorities
                            </option>

                            <option value="low" @selected(request('priority') === 'low')>
                                Low
                            </option>

                            <option value="normal" @selected(request('priority') === 'normal')>
                                Normal
                            </option>

                            <option value="high" @selected(request('priority') === 'high')>
                                High
                            </option>

                            <option value="urgent" @selected(request('priority') === 'urgent')>
                                Urgent
                            </option>

                        </select>

                    </div>


                    <div class="flex items-end gap-2 lg:col-span-5">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Apply Filters
                        </button>

                        <a
                            href="{{ route('administration.requests.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>


            {{-- ========================================================= --}}
            {{-- REQUEST REGISTER --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h3 class="font-semibold text-slate-900">
                            Administrative Request Register
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Requests requiring verification, approval and execution
                        </p>

                    </div>

                    <div class="text-sm font-semibold text-slate-600">
                        {{ number_format($requests->total()) }}
                        request{{ $requests->total() === 1 ? '' : 's' }}
                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[1250px] w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Request No
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Request
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Department
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Priority
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Est. Amount
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Created
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @forelse ($requests as $administrativeRequest)

                                @php

                                    $status =
                                        $administrativeRequest->status ?? 'draft';

                                    $priority =
                                        $administrativeRequest->priority ?? 'normal';

                                @endphp


                                <tr class="hover:bg-slate-50">

                                    <td class="px-4 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $administrativeRequest->request_no }}
                                        </div>

                                    </td>


                                    <td class="px-4 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $administrativeRequest->title }}
                                        </div>

                                        @if ($administrativeRequest->description)

                                            <div class="mt-1 max-w-md truncate text-xs text-slate-500">
                                                {{ $administrativeRequest->description }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-4 py-4 align-top text-sm text-slate-700">
                                        {{ $administrativeRequest->department?->name ?? '—' }}
                                    </td>


                                    <td class="px-4 py-4 align-top text-sm font-medium text-slate-700">
                                        {{ $typeLabels[$administrativeRequest->request_type] ?? ucwords(str_replace('_', ' ', $administrativeRequest->request_type)) }}
                                    </td>


                                    <td class="px-4 py-4 align-top">

                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $priorityClasses[$priority] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ strtoupper($priority) }}
                                        </span>

                                    </td>


                                    <td class="px-4 py-4 text-right align-top">

                                        @if ($administrativeRequest->estimated_amount !== null)

                                            <div class="font-semibold text-slate-900">
                                                ₹{{ number_format((float) $administrativeRequest->estimated_amount, 2) }}
                                            </div>

                                        @else

                                            <span class="text-slate-400">
                                                —
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-4 py-4 align-top">

                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)) }}
                                        </span>

                                    </td>


                                    <td class="px-4 py-4 align-top">

                                        <div class="text-sm font-medium text-slate-700">
                                            {{ $administrativeRequest->created_at?->format('d M Y') ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $administrativeRequest->createdBy?->name ?? '—' }}
                                        </div>

                                    </td>


                                    <td class="px-4 py-4 text-right align-top">

                                        <a
                                            href="{{ route('administration.requests.show', $administrativeRequest) }}"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Open
                                        </a>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="9"
                                        class="px-6 py-12 text-center"
                                    >

                                        <div class="text-sm font-semibold text-slate-700">
                                            No administrative requests found
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            Create the first request to start the approval workflow.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($requests->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $requests->links() }}
                    </div>

                @endif

            </div>


        </div>

    </div>

</x-app-layout>
