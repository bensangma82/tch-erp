
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Trial Balance
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Account balances derived from posted ERP journals.
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

            <div class="rounded-lg bg-white p-5 shadow">
                <form method="GET"
                      action="{{ route('finance.accounting-reports.trial-balance') }}"
                      class="flex flex-wrap items-end gap-4">

                    <div>
                        <label for="as_of" class="block text-sm font-medium text-gray-700">
                            As of Date
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

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        Trial Balance as of
                        {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Account Code
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Account Name
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Classification
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                                    Debit (₹)
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                                    Credit (₹)
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($accounts as $account)
                                <tr class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-900">
                                        {{ $account->account_code }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $account->account_name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ ucfirst($account->account_type) }}
                                        @if ($account->account_group)
                                            <div class="text-xs text-gray-400">
                                                {{ $account->account_group }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-800">
                                        {{ (float) $account->closing_debit > 0
                                            ? number_format((float) $account->closing_debit, 2)
                                            : '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-800">
                                        {{ (float) $account->closing_credit > 0
                                            ? number_format((float) $account->closing_credit, 2)
                                            : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No posted journal balances found for the selected date.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot class="bg-slate-100">
                            <tr>
                                <th colspan="3" class="px-4 py-4 text-right text-sm font-bold text-gray-900">
                                    Total
                                </th>
                                <th class="px-4 py-4 text-right text-sm font-bold text-gray-900">
                                    {{ number_format($totalDebit, 2) }}
                                </th>
                                <th class="px-4 py-4 text-right text-sm font-bold text-gray-900">
                                    {{ number_format($totalCredit, 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if (abs((float) $totalDebit - (float) $totalCredit) < 0.005)
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    Trial Balance agrees. Total debit balances equal total credit balances.
                </div>
            @else
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">
                    Warning: Trial Balance does not agree.
                    Difference: ₹{{ number_format(abs($totalDebit - $totalCredit), 2) }}
                </div>
            @endif

            <p class="text-xs text-gray-500">
                This report includes posted journal entries up to the selected date.
                It reflects balances recorded within TCH ERP only and remains
                provisional until opening balances and transactions are reconciled
                with the hospital's official accounts.
            </p>
        </div>
    </div>
</x-app-layout>
