
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Balance Sheet
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Statement of assets, liabilities and equity.
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

            {{-- Reporting Date --}}
            <div class="rounded-lg bg-white p-5 shadow">
                <form method="GET"
                      action="{{ route('finance.accounting-reports.balance-sheet') }}"
                      class="flex flex-wrap items-end gap-4">

                    <div>
                        <label for="as_of"
                               class="block text-sm font-medium text-gray-700">
                            Balance Sheet As of
                        </label>
                        <input
                            id="as_of"
                            name="as_of"
                            type="date"
                            value="{{ $asOf }}"
                            required
                            class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Generate Report
                    </button>
                </form>
            </div>

            {{-- Summary --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Total Assets
                    </p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format((float) $totalAssets, 2) }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Total Liabilities
                    </p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format((float) $totalLiabilities, 2) }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Total Equity Including Surplus
                    </p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format((float) $totalEquity, 2) }}
                    </p>
                </div>
            </div>

            {{-- Balance Sheet Statement --}}
            <div class="rounded-lg bg-white p-6 shadow">

                <div class="border-b border-gray-100 pb-5 text-center">
                    <h3 class="text-lg font-bold text-gray-900">
                        Tura Christian Hospital
                    </h3>
                    <p class="mt-1 text-sm font-semibold text-gray-700">
                        Balance Sheet
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        As at {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}
                    </p>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-2">

                    {{-- Assets --}}
                    <section>
                        <h4 class="border-b-2 border-slate-300 pb-3 text-base font-bold text-gray-900">
                            Assets
                        </h4>

                        <table class="mt-2 w-full">
                            <thead>
                                <tr class="text-xs uppercase text-gray-500">
                                    <th class="py-2 text-left">Account</th>
                                    <th class="py-2 text-right">Amount (₹)</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                @forelse ($assets as $account)
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
                                        <td class="whitespace-nowrap py-3 text-right text-sm text-gray-800">
                                            {{ number_format((float) $account->amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-6 text-center text-sm text-gray-500">
                                            No asset balances recorded.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot>
                                <tr class="border-t-2 border-slate-300">
                                    <th class="py-4 text-left text-sm font-bold text-gray-900">
                                        Total Assets
                                    </th>
                                    <th class="py-4 text-right text-sm font-bold text-gray-900">
                                        {{ number_format((float) $totalAssets, 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </section>

                    {{-- Liabilities and Equity --}}
                    <section>
                        <h4 class="border-b-2 border-slate-300 pb-3 text-base font-bold text-gray-900">
                            Liabilities and Equity
                        </h4>

                        <table class="mt-2 w-full">
                            <thead>
                                <tr class="text-xs uppercase text-gray-500">
                                    <th class="py-2 text-left">Account</th>
                                    <th class="py-2 text-right">Amount (₹)</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td colspan="2" class="pt-4 pb-2 text-sm font-bold text-gray-900">
                                        Liabilities
                                    </td>
                                </tr>

                                @forelse ($liabilities as $account)
                                    <tr>
                                        <td class="py-3 pr-4 text-sm text-gray-700">
                                            {{ $account->account_name }}
                                            <div class="text-xs text-gray-500">
                                                {{ $account->account_code }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap py-3 text-right text-sm text-gray-800">
                                            {{ number_format((float) $account->amount, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-3 text-sm text-gray-500">
                                            No liability balances recorded.
                                        </td>
                                    </tr>
                                @endforelse

                                <tr class="border-t border-gray-200">
                                    <td class="py-3 text-sm font-semibold text-gray-900">
                                        Total Liabilities
                                    </td>
                                    <td class="py-3 text-right text-sm font-semibold text-gray-900">
                                        {{ number_format((float) $totalLiabilities, 2) }}
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2" class="pt-6 pb-2 text-sm font-bold text-gray-900">
                                        Equity and Accumulated Surplus
                                    </td>
                                </tr>

                                @foreach ($equity as $account)
                                    <tr>
                                        <td class="py-3 pr-4 text-sm text-gray-700">
                                            {{ $account->account_name }}
                                            <div class="text-xs text-gray-500">
                                                {{ $account->account_code }}
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap py-3 text-right text-sm text-gray-800">
                                            {{ number_format((float) $account->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach

                                <tr>
                                    <td class="py-3 text-sm text-gray-700">
                                        Accumulated Surplus / (Deficit)
                                    </td>
                                    <td class="py-3 text-right text-sm font-semibold text-gray-900">
                                        {{ number_format((float) $accumulatedSurplus, 2) }}
                                    </td>
                                </tr>

                                <tr class="border-t-2 border-slate-300">
                                    <th class="py-4 text-left text-sm font-bold text-gray-900">
                                        Total Liabilities and Equity
                                    </th>
                                    <th class="py-4 text-right text-sm font-bold text-gray-900">
                                        {{ number_format((float) ($totalLiabilities + $totalEquity), 2) }}
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>

            {{-- Reconciliation --}}
            @if (abs((float) $difference) < 0.005)
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    Balance Sheet agrees mathematically.
                    Total Assets equal Total Liabilities and Equity.
                </div>
            @else
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">
                    Warning: Balance Sheet does not agree.
                    Difference: ₹{{ number_format(abs((float) $difference), 2) }}
                </div>
            @endif

            <p class="text-xs text-gray-500">
                This provisional Balance Sheet is derived from posted ERP journals.
                Accumulated surplus includes unclosed income and expenditure balances.
                Opening balances, closing entries, classifications and transaction
                completeness must be reconciled with the hospital's official books
                before the statement is used for statutory or management reporting.
            </p>

        </div>
    </div>
</x-app-layout>
