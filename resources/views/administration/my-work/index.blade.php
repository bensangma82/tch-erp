<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    My Assigned Work
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Administrative requests currently assigned to you for execution.
                </p>
            </div>

            <a
                href="{{ route('administration.requests.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Administration
            </a>

        </div>

    </x-slot>


    @php

        $statusLabels = [
            'execution_in_progress' => 'Execution In Progress',
            'executed' => 'Executed',
            'closed' => 'Closed',
        ];

        $statusClasses = [
            'execution_in_progress' => 'bg-violet-50 text-violet-700',
            'executed' => 'bg-emerald-50 text-emerald-700',
            'closed' => 'bg-slate-200 text-slate-700',
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


            {{-- Summary cards --}}

            <div class="grid gap-4 sm:grid-cols-3">

                <div class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Pending Execution
                    </div>

                    <div class="mt-2 text-3xl font-bold text-violet-700">
                        {{ $summary['pending'] }}
                    </div>

                </div>


                <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Executed
                    </div>

                    <div class="mt-2 text-3xl font-bold text-emerald-700">
                        {{ $summary['executed'] }}
                    </div>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Closed
                    </div>

                    <div class="mt-2 text-3xl font-bold text-slate-700">
                        {{ $summary['closed'] }}
                    </div>

                </div>

            </div>


            {{-- Search / filter --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('administration.my-work.index') }}"
                    class="grid gap-4 md:grid-cols-4"
                >

                    <div class="md:col-span-2">

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
                            placeholder="Request number, title or description..."
                            class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

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
                            class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                            <option value="">
                                All
                            </option>

                            <option
                                value="execution_in_progress"
                                @selected(request('status') === 'execution_in_progress')
                            >
                                Execution In Progress
                            </option>

                            <option
                                value="executed"
                                @selected(request('status') === 'executed')
                            >
                                Executed
                            </option>

                            <option
                                value="closed"
                                @selected(request('status') === 'closed')
                            >
                                Closed
                            </option>

                        </select>

                    </div>


                    <div class="flex items-end gap-2">

                        <button
                            type="submit"
                            class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('administration.my-work.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>


            {{-- Assigned work --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Assigned Requests
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Open a request to review instructions, supporting documents and complete the assigned work.
                    </p>

                </div>


                <div class="divide-y divide-slate-100">

                    @forelse ($requests as $administrativeRequest)

                        @php
                            $status = $administrativeRequest->status;
                            $priority = $administrativeRequest->priority ?? 'normal';
                        @endphp


                        <div class="p-6">

                            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

                                <div class="min-w-0 flex-1">

                                    <div class="flex flex-wrap items-center gap-2">

                                        <span class="font-mono text-sm font-semibold text-slate-500">
                                            {{ $administrativeRequest->request_no }}
                                        </span>

                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityClasses[$priority] ?? 'bg-slate-100 text-slate-600' }}"
                                        >
                                            {{ strtoupper($priority) }}
                                        </span>

                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-slate-100 text-slate-700' }}"
                                        >
                                            {{ $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)) }}
                                        </span>

                                    </div>


                                    <div class="mt-3 text-lg font-bold text-slate-900">
                                        {{ $administrativeRequest->title }}
                                    </div>


                                    <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-500">

                                        <div>
                                            Department:
                                            <span class="font-medium text-slate-700">
                                                {{ $administrativeRequest->department?->name ?? 'Hospital-wide' }}
                                            </span>
                                        </div>

                                        <div>
                                            Responsible Role:
                                            <span class="font-medium text-slate-700">
                                                {{ $administrativeRequest->assigned_role ?? '—' }}
                                            </span>
                                        </div>

                                        @if ($administrativeRequest->execution_started_at)

                                            <div>
                                                Assigned:
                                                <span class="font-medium text-slate-700">
                                                    {{ $administrativeRequest->execution_started_at->format('d M Y, h:i A') }}
                                                </span>
                                            </div>

                                        @endif

                                    </div>


                                    @if ($administrativeRequest->execution_remarks)

                                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">

                                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                Execution Instructions
                                            </div>

                                            <div class="mt-2 line-clamp-3 whitespace-pre-line text-sm text-slate-700">
                                                {{ $administrativeRequest->execution_remarks }}
                                            </div>

                                        </div>

                                    @endif

                                </div>


                                <div class="flex shrink-0">

                                    <a
                                        href="{{ route(
                                            'administration.requests.show',
                                            $administrativeRequest
                                        ) }}"
                                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                                    >
                                        Open Work
                                    </a>

                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="px-6 py-14 text-center">

                            <div class="text-base font-semibold text-slate-700">
                                No assigned work found
                            </div>

                            <div class="mt-2 text-sm text-slate-500">
                                Administrative requests assigned to your account will appear here.
                            </div>

                        </div>

                    @endforelse

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