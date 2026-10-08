<x-app-layout>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Revenue Detail
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $financeHead->name }}
                    ·
                    {{ $financeHead->code }}
                    ·
                    {{ $period }}
                </p>
            </div>

            <a
                href="{{ route('finance.dashboard') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                ← Back to Dashboard
            </a>

        </div>

        {{-- Summary --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Finance Head
                </div>

                <div class="mt-2 text-lg font-bold text-slate-900">
                    {{ $financeHead->name }}
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    {{ $financeHead->code }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Transactions
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $transactions->count() }}
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Entries contributing to this month
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Total Revenue
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-600">
                    ₹{{ number_format((float) $totalRevenue, 2) }}
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    {{ $period }}
                </div>
            </div>

        </div>

        {{-- Transactions --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">
                    Revenue Transactions
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Underlying source transactions used in the dashboard total
                </p>
            </div>

            @if($transactions->isEmpty())

                <div class="px-5 py-12 text-center">

                    <div class="text-sm font-semibold text-slate-700">
                        No revenue transactions found
                    </div>

                    <div class="mt-1 text-sm text-slate-500">
                        There are currently no matching transactions for this Finance Head in {{ $period }}.
                    </div>

                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Date
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Source
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Reference
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Description
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Payment Mode
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($transactions as $transaction)

                                @php
                                    $amount = (float) $transaction['amount'];
                                    $date = $transaction['date'];
                                @endphp

                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                        @if($date instanceof \Carbon\CarbonInterface)
                                            {{ $date->format('d M Y') }}
                                        @elseif($date)
                                            {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ $transaction['source'] }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">
                                        {{ $transaction['reference'] ?: '—' }}
                                    </td>

                                    <td class="max-w-md px-4 py-3 text-sm text-slate-700">
                                        {{ $transaction['description'] ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                        {{ $transaction['payment_mode']
                                            ? ucfirst(str_replace('_', ' ', $transaction['payment_mode']))
                                            : '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold
                                        {{ $amount < 0 ? 'text-red-600' : 'text-slate-900' }}">
                                        {{ $amount < 0 ? '-' : '' }}₹{{ number_format(abs($amount), 2) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot class="border-t border-slate-200 bg-slate-50">
                            <tr>

                                <td
                                    colspan="5"
                                    class="px-4 py-3 text-right text-sm font-semibold text-slate-700"
                                >
                                    Total Revenue
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-base font-bold text-emerald-700">
                                    ₹{{ number_format((float) $totalRevenue, 2) }}
                                </td>

                            </tr>
                        </tfoot>

                    </table>

                </div>

            @endif

        </div>

    </div>

</x-app-layout>