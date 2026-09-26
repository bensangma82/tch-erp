<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    Inpatient Billing
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    View active inpatient admissions and manage their running billing accounts.
                </p>
            </div>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- SEARCH --}}
            {{-- ========================================================= --}}

            <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <h3 class="text-base font-semibold text-slate-900">
                        Search Inpatients
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Search by admission number, UHID, MRD number, or patient name.
                    </p>

                </div>


                <form
                    method="GET"
                    action="{{ route('ip-billing.index') }}"
                    class="p-6"
                >

                    <div class="flex flex-col gap-3 sm:flex-row">

                        <div class="flex-1">

                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Admission no., UHID, MRD, patient name..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >

                        </div>


                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Search
                        </button>


                        @if (request()->filled('search'))

                            <a
                                href="{{ route('ip-billing.index') }}"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Clear
                            </a>

                        @endif

                    </div>

                </form>

            </div>


            {{-- ========================================================= --}}
            {{-- CURRENT INPATIENTS --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                {{-- CARD HEADER --}}
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="text-base font-semibold text-slate-900">
                                Current Inpatients
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Open an admission to view or create its running billing account.
                            </p>

                        </div>


                        <div class="text-sm font-semibold text-slate-600">

                            {{ $admissions->total() }}
                            patient{{ $admissions->total() === 1 ? '' : 's' }}

                        </div>

                    </div>

                </div>


                @if ($admissions->count() > 0)

                    <div class="w-full">

                        <table class="w-full table-fixed">

                            <thead class="border-b border-slate-200 bg-white">

                                <tr>

                                    <th class="w-[16%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Admission
                                    </th>

                                    <th class="w-[15%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Patient
                                    </th>

                                    <th class="w-[14%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Ward / Bed
                                    </th>

                                    <th class="w-[13%] px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Consultant
                                    </th>

                                    <th class="w-[7%] px-2 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Charges
                                    </th>

                                    <th class="w-[7%] px-2 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Advance
                                    </th>

                                    <th class="w-[7%] px-2 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Balance
                                    </th>

                                    <th class="w-[11%] px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Account
                                    </th>

                                    <th class="w-[10%] px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100 bg-white">

                                @foreach ($admissions as $admission)

                                    @php
                                        $account = $admission->billingAccount;

                                        $summary = $admission->billing_summary ?? [
                                            'subtotal' => 0,
                                            'discount' => 0,
                                            'net_amount' => 0,
                                            'advance_amount' => 0,
                                            'paid_amount' => 0,
                                            'balance_amount' => 0,
                                        ];

                                        $currentAllocation =
                                            $admission->currentBedAllocation;

                                        $currentBed =
                                            $currentAllocation?->bed
                                            ?? $admission->bed;

                                        $ward =
                                            $currentBed?->ward;
                                    @endphp


                                    <tr class="transition hover:bg-slate-50">


                                        {{-- ADMISSION --}}
                                        <td class="px-3 py-4 align-top">

                                            <div class="whitespace-nowrap text-sm font-semibold text-slate-900">
                                                {{ $admission->admission_no }}
                                            </div>

                                            <div class="mt-1 whitespace-nowrap text-xs text-slate-500">
                                                {{ $admission->admitted_at?->format('d M Y, h:i A') ?? '—' }}
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ ucfirst($admission->admission_type ?? 'IPD') }}
                                            </div>

                                        </td>


                                        {{-- PATIENT --}}
                                        <td class="px-3 py-4 align-top">

                                            <div class="text-sm font-semibold leading-5 text-slate-900">
                                                {{ $admission->patient?->full_name ?? '—' }}
                                            </div>

                                            <div class="mt-1 whitespace-nowrap text-xs text-slate-500">
                                                UHID:
                                                <span class="font-mono">
                                                    {{ $admission->patient?->uhid ?? '—' }}
                                                </span>
                                            </div>

                                            <div class="mt-1 whitespace-nowrap text-xs text-slate-500">
                                                MRD:
                                                <span class="font-mono">
                                                    {{ $admission->patient?->mrd_number ?: '—' }}
                                                </span>
                                            </div>

                                        </td>


                                        {{-- WARD / BED --}}
                                        <td class="px-3 py-4 align-top">

                                            <div class="text-sm font-semibold leading-5 text-slate-900">
                                                {{ $ward?->name ?? '—' }}
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">
                                                Bed:
                                                <span class="font-medium text-slate-700">
                                                    {{ $currentBed?->bed_number ?? '—' }}
                                                </span>
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">
                                                Type:
                                                {{ $currentBed?->bed_type ?? '—' }}
                                            </div>

                                        </td>


                                        {{-- CONSULTANT --}}
                                        <td class="px-3 py-4 align-top">

                                            <div class="text-sm font-medium leading-5 text-slate-900">
                                                {{ $admission->consultant?->full_name ?? $admission->consultant?->name ?? '—' }}
                                            </div>

                                            <div class="mt-1 text-xs leading-4 text-slate-500">
                                                {{ $admission->department?->name ?? '—' }}
                                            </div>

                                        </td>


                                        {{-- CHARGES --}}
                                        <td class="px-2 py-4 text-right align-top">

                                            <div class="whitespace-nowrap text-sm font-semibold tabular-nums text-slate-900">
                                                ₹{{ number_format((float) $summary['net_amount'], 2) }}
                                            </div>

                                        </td>


                                        {{-- ADVANCE --}}
                                        <td class="px-2 py-4 text-right align-top">

                                            <div class="whitespace-nowrap text-sm font-semibold tabular-nums text-blue-700">
                                                ₹{{ number_format((float) $summary['advance_amount'], 2) }}
                                            </div>

                                        </td>


                                        {{-- BALANCE --}}
                                        <td class="px-2 py-4 text-right align-top">

                                            @if ((float) $summary['balance_amount'] > 0)

                                                <div class="whitespace-nowrap text-sm font-bold tabular-nums text-red-700">
                                                    ₹{{ number_format((float) $summary['balance_amount'], 2) }}
                                                </div>

                                            @else

                                                <div class="whitespace-nowrap text-sm font-bold tabular-nums text-emerald-700">
                                                    ₹0.00
                                                </div>

                                            @endif

                                        </td>


                                        {{-- ACCOUNT --}}
                                        <td class="px-2 py-4 text-center align-top">

                                            @if ($account)

                                                <div class="whitespace-nowrap font-mono text-[11px] font-semibold text-slate-700">
                                                    {{ $account->account_no }}
                                                </div>


                                                <span
                                                    class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                                        @if ($account->status === 'open')
                                                            bg-emerald-100 text-emerald-700
                                                        @elseif ($account->status === 'finalizing')
                                                            bg-amber-100 text-amber-700
                                                        @elseif ($account->status === 'finalized')
                                                            bg-slate-100 text-slate-700
                                                        @elseif ($account->status === 'settled')
                                                            bg-blue-100 text-blue-700
                                                        @else
                                                            bg-slate-100 text-slate-700
                                                        @endif
                                                    "
                                                >
                                                    {{ ucfirst($account->status) }}
                                                </span>

                                            @else

                                                <span class="inline-flex whitespace-nowrap rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                    Not Opened
                                                </span>

                                            @endif

                                        </td>


                                        {{-- ACTION --}}
                                        <td class="px-2 py-4 text-center align-top">

                                            <a
                                                href="{{ route('ip-billing.show', $admission) }}"
                                                class="inline-flex w-full max-w-[110px] items-center justify-center whitespace-nowrap rounded-lg bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                                            >
                                                {{ $account ? 'Open Bill' : 'Start Billing' }}
                                            </a>

                                        </td>


                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    {{-- PAGINATION --}}
                    <div class="border-t border-slate-200 px-6 py-4">

                        {{ $admissions->links() }}

                    </div>


                @else

                    <div class="px-6 py-16 text-center">

                        <div class="text-lg font-semibold text-slate-700">
                            No active inpatient admissions found
                        </div>

                        <p class="mt-2 text-sm text-slate-500">
                            Active admitted patients will appear here for inpatient billing.
                        </p>

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>