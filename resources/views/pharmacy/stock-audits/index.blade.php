<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Stock Audit Register
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Physical stock verification and variance history
                </p>
            </div>

            <a
                href="{{ route('pharmacy.stock-audits.create') }}"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                Start Stock Audit
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- FILTERS --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('pharmacy.stock-audits.index') }}"
                    class="grid gap-4 lg:grid-cols-12"
                >

                    <div class="lg:col-span-4">

                        <label
                            for="search"
                            class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            Search
                        </label>

                        <input
                            id="search"
                            name="search"
                            type="text"
                            value="{{ request('search') }}"
                            placeholder="Audit number..."
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div class="lg:col-span-2">

                        <label
                            for="status"
                            class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500"
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

                            <option value="counting" @selected(request('status') === 'counting')>
                                Counting
                            </option>

                            <option value="review" @selected(request('status') === 'review')>
                                Under Review
                            </option>

                            <option value="approved" @selected(request('status') === 'approved')>
                                Approved
                            </option>

                            <option value="posted" @selected(request('status') === 'posted')>
                                Posted
                            </option>

                            <option value="cancelled" @selected(request('status') === 'cancelled')>
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <div class="lg:col-span-2">

                        <label
                            for="from_date"
                            class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            From Date
                        </label>

                        <input
                            id="from_date"
                            name="from_date"
                            type="date"
                            value="{{ request('from_date') }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div class="lg:col-span-2">

                        <label
                            for="to_date"
                            class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            To Date
                        </label>

                        <input
                            id="to_date"
                            name="to_date"
                            type="date"
                            value="{{ request('to_date') }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div class="flex items-end gap-2 lg:col-span-12">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Apply Filters
                        </button>

                        <a
                            href="{{ route('pharmacy.stock-audits.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>



            {{-- REGISTER --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Stock Audits
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Latest audits are shown first
                            </p>

                        </div>

                        <div class="text-sm text-slate-500">
                            {{ $audits->total() }}
                            {{ Str::plural('record', $audits->total()) }}
                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[1200px] w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Audit Date
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Audit Number
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type
                                </th>

                                <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Batches
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Created By
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Approved By
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Posted By
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($audits as $audit)

                                @php

                                    $statusClass =
                                        match ($audit->status) {

                                            'counting' =>
                                                'bg-blue-50 text-blue-700',

                                            'review' =>
                                                'bg-amber-50 text-amber-700',

                                            'approved' =>
                                                'bg-purple-50 text-purple-700',

                                            'posted' =>
                                                'bg-emerald-50 text-emerald-700',

                                            'cancelled' =>
                                                'bg-red-50 text-red-700',

                                            default =>
                                                'bg-slate-100 text-slate-700',
                                        };


                                    $statusLabel =
                                        match ($audit->status) {

                                            'counting' =>
                                                'Counting',

                                            'review' =>
                                                'Under Review',

                                            'approved' =>
                                                'Approved',

                                            'posted' =>
                                                'Posted',

                                            'cancelled' =>
                                                'Cancelled',

                                            default =>
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $audit->status
                                                    )
                                                ),
                                        };

                                @endphp


                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">
                                        {{ $audit->audit_date?->format('d M Y') ?? '—' }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $audit->audit_no }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 text-slate-700">
                                        {{ ucwords(str_replace('_', ' ', $audit->audit_type)) }}
                                    </td>


                                    <td class="px-5 py-4 text-center">

                                        <span class="inline-flex min-w-8 justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ $audit->items_count }}
                                        </span>

                                    </td>


                                    <td class="px-5 py-4">

                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-medium text-slate-800">
                                            {{ $audit->createdBy?->name ?? 'System' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $audit->created_at?->format('d M Y, h:i A') }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-medium text-slate-800">
                                            {{ $audit->approvedBy?->name ?? '—' }}
                                        </div>

                                        @if ($audit->approved_at)

                                            <div class="mt-1 text-xs text-slate-400">
                                                {{ $audit->approved_at->format('d M Y, h:i A') }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-medium text-slate-800">
                                            {{ $audit->postedBy?->name ?? '—' }}
                                        </div>

                                        @if ($audit->posted_at)

                                            <div class="mt-1 text-xs text-slate-400">
                                                {{ $audit->posted_at->format('d M Y, h:i A') }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <a
                                            href="{{ route('pharmacy.stock-audits.show', $audit) }}"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="9"
                                        class="px-6 py-16 text-center"
                                    >

                                        <div class="text-base font-semibold text-slate-700">
                                            No stock audits found
                                        </div>

                                        <div class="mt-2 text-sm text-slate-500">
                                            Start a stock audit to begin physical stock verification.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($audits->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $audits->links() }}
                    </div>

                @endif

            </div>


        </div>

    </div>

</x-app-layout>