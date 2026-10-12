
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Income &amp; Expenditure Statement
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Accrual-based income and expenditure from posted ERP journals.
                </p>
            </div>

            <a href="{{ route('finance.accounting-reports.index') }}"
               class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Back to Accounting Reports
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Reporting period --}}
            <div class="rounded-lg bg-white p-5 shadow">
                <form method="GET"
                      action="{{ route('finance.accounting-reports.income-expenditure') }}"
                      class="flex flex-wrap items-end gap-4">

                    <div>
                        <label for="from" class="block text-sm font-medium text-gray-700">
                            From Date
                        </label>
                        <input id="from"
                               name="from"
                               type="date"
                               value="{{ $from }}"
                               required
                               class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="to" class="block text-sm font-medium text-gray-700">
                            To Date
                        </label>
                        <input id="to"
                               name="to"
                               type="date"
                               value="{{ $to }}"
                               required
                               class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Generate Report
                    </button>
                </form>
            </div>

            {{-- Statement summary --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Total Income
                    </p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format((float) $totalIncome, 2) }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Total Expenditure
                    </p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format((float) $totalExpense, 2) }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        {{ $surplus >= 0 ? 'Net Surplus' : 'Net Deficit' }}
                    </p>
                    <p class="mt-2 text-2xl font-bold {{ $surplus >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        ₹{{ number_format(abs((float) $surplus), 2) }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="border-b border-gray-100 pb-4 text-center">
                    <h3 class="text-lg font-bold text-gray-900">
                        Tura Christian Hospital
                    </h3>
                    <p class="mt-1 text-sm font-semibold text-gray-700">
                        Income &amp; Expenditure Statement
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        For the period
                        {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                        to
                        {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                    </p>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-2">

                    {{-- Income --}}
                    <section>
                        <h4 class="border-b-2 border-slate-300 pb-3 text-base font-bold text-gray-900">
                            Income
                        </h4>

                        <table class="mt-2 w-full">
                            <thead>
                                <tr class="text-xs uppercase text-gray-500">
                                    <th class="py-2 text-left">Account</th>
                                    <th class="py-2 text-right">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($income as $account)
                                    <tr>
                                        <td class="py-3 pr-4 text-sm text-gray-700">
                                            <div class="font-semibold text-gray-800">
                                                {{ $account->account_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $account->account_code }}
                                                @if ($account->account_group)
                                                    / {{ $account->account_group }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap py-3 text-right text-sm font-medium text-gray-800">
                                            {{ number_format((float) $account->amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-6 text-center text-sm text-gray-500">
                                            No income recorded for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-slate-300">
                                    <th class="py-4 text-left text-sm font-bold text-gray-900">
                                        Total Income
                                    </th>
                                    <th class="py-4 text-right text-sm font-bold text-gray-900">
                                        {{ number_format((float) $totalIncome, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </section>

                    {{-- Expenditure --}}
                    <section>
                        <h4 class="border-b-2 border-slate-300 pb-3 text-base font-bold text-gray-900">
                            Expenditure
                        </h4>

                        <table class="mt-2 w-full">
                            <thead>
                                <tr class="text-xs uppercase text-gray-500">
                                    <th class="py-2 text-left">Account</th>
                                    <th class="py-2 text-right">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($expenses as $account)
                                    <tr>
                                        <td class="py-3 pr-4 text-sm text-gray-700">
                                            <div class="font-semibold text-gray-800">
                                                {{ $account->account_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $account->account_code }}
                                                @if ($account->account_group)
                                                    / {{ $account->account_group }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap py-3 text-right text-sm font-medium text-gray-800">
                                            {{ number_format((float) $account->amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-6 text-center text-sm text-gray-500">
                                            No expenditure recorded for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-slate-300">
                                    <th class="py-4 text-left text-sm font-bold text-gray-900">
                                        Total Expenditure
                                    </th>
                                    <th class="py-4 text-right text-sm font-bold text-gray-900">
                                        {{ number_format((float) $totalExpense, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </section>
                </div>

                <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t-2 border-slate-300 pt-5">
                    <span class="text-base font-bold text-gray-900">
                        {{ $surplus >= 0 ? 'Surplus for the Period' : 'Deficit for the Period' }}
                    </span>
                    <span class="text-xl font-bold {{ $surplus >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        ₹{{ number_format(abs((float) $surplus), 2) }}
                    </span>
                </div>
            </div>

            <p class="text-xs text-gray-500">
                Figures are derived from posted ERP journals for the selected
                accounting period. Revenue and expenditure recognition may differ
                from cash receipts and payments. The statement remains provisional
                until opening balances, account mappings and transactions are
                reconciled with the hospital's official books.
            </p>

        </div>
    </div>
</x-app-layout>
