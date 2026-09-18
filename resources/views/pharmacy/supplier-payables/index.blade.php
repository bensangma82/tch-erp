<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Supplier Payables
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Outstanding supplier liabilities and payment status
                </p>

            </div>

            <a
                href="{{ route('pharmacy.grns.index') }}"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                GRN Register
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @php

                $totalOutstanding =
                    $payables->getCollection()
                        ->sum(
                            fn ($item) =>
                                (float) $item->outstanding_amount
                        );


                $overdueCount =
                    $payables->getCollection()
                        ->filter(
                            fn ($item) =>
                                $item->status !== 'paid'
                                &&
                                $item->due_date
                                &&
                                $item->due_date->isPast()
                        )
                        ->count();

            @endphp



            {{-- ========================================================= --}}
            {{-- SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 md:grid-cols-3">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Current Page Records
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $payables->count() }}
                    </div>

                </div>


                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-500">
                        Outstanding on Current Page
                    </div>

                    <div class="mt-2 text-2xl font-bold text-blue-800">
                        ₹{{ number_format($totalOutstanding, 2) }}
                    </div>

                </div>


                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-red-500">
                        Overdue on Current Page
                    </div>

                    <div class="mt-2 text-2xl font-bold text-red-700">
                        {{ $overdueCount }}
                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- FILTERS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('pharmacy.supplier-payables.index') }}"
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
                            placeholder="Payable no, GRN, invoice, supplier..."
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

                            <option
                                value="unpaid"
                                @selected(request('status') === 'unpaid')
                            >
                                Unpaid
                            </option>

                            <option
                                value="partially_paid"
                                @selected(request('status') === 'partially_paid')
                            >
                                Partially Paid
                            </option>

                            <option
                                value="paid"
                                @selected(request('status') === 'paid')
                            >
                                Paid
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
                            href="{{ route('pharmacy.supplier-payables.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>



            {{-- ========================================================= --}}
            {{-- REGISTER --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Supplier Payables Register
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Latest payables are shown first
                            </p>

                        </div>

                        <div class="text-sm text-slate-500">
                            {{ $payables->total() }}
                            {{ Str::plural('record', $payables->total()) }}
                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[1400px] w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Payable
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Supplier
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    GRN / Invoice
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Dates
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Original
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Returns
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Paid
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Outstanding
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($payables as $payable)

                                @php

                                    $isOverdue =
                                        $payable->status !== 'paid'
                                        &&
                                        $payable->due_date
                                        &&
                                        $payable->due_date->isPast();


                                    $statusClass =
                                        match ($payable->status) {

                                            'unpaid' =>
                                                'bg-red-50 text-red-700',

                                            'partially_paid' =>
                                                'bg-amber-50 text-amber-700',

                                            'paid' =>
                                                'bg-emerald-50 text-emerald-700',

                                            default =>
                                                'bg-slate-100 text-slate-700',
                                        };


                                    $statusLabel =
                                        match ($payable->status) {

                                            'unpaid' =>
                                                'Unpaid',

                                            'partially_paid' =>
                                                'Partially Paid',

                                            'paid' =>
                                                'Paid',

                                            default =>
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $payable->status
                                                    )
                                                ),
                                        };

                                @endphp


                                <tr class="hover:bg-slate-50">


                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $payable->payable_no }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $payable->payable_date?->format('d M Y') ?? '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $payable->supplier?->name ?? '—' }}
                                        </div>

                                        @if ($payable->supplier?->code)

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $payable->supplier->code }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-medium text-slate-800">
                                            {{ $payable->grn?->grn_no ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            Invoice:
                                            {{ $payable->supplier_invoice_no ?: '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="text-sm text-slate-700">
                                            Payable:
                                            {{ $payable->payable_date?->format('d M Y') ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-sm {{ $isOverdue ? 'font-semibold text-red-700' : 'text-slate-500' }}">
                                            Due:
                                            {{ $payable->due_date?->format('d M Y') ?? '—' }}
                                        </div>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-900">
                                        ₹{{ number_format((float) $payable->original_amount, 2) }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-amber-700">
                                        -₹{{ number_format((float) $payable->return_adjustment, 2) }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-emerald-700">
                                        ₹{{ number_format((float) $payable->paid_amount, 2) }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="font-bold text-blue-800">
                                            ₹{{ number_format((float) $payable->outstanding_amount, 2) }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="flex flex-wrap gap-2">

                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>

                                            @if ($isOverdue)

                                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">
                                                    Overdue
                                                </span>

                                            @endif

                                        </div>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <a
                                            href="{{ route('pharmacy.supplier-payables.show', $payable) }}"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            View
                                        </a>

                                    </td>


                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="10"
                                        class="px-6 py-16 text-center"
                                    >

                                        <div class="text-base font-semibold text-slate-700">
                                            No supplier payables found
                                        </div>

                                        <div class="mt-2 text-sm text-slate-500">
                                            Payables will appear here after they are created from completed GRNs.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($payables->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $payables->links() }}
                    </div>

                @endif

            </div>


        </div>

    </div>

</x-app-layout>
