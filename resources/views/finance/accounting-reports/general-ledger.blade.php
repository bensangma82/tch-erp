
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    General Ledger Report
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Account-wise transactions and running balances.
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

            {{-- Filters --}}
            <div class="rounded-lg bg-white p-5 shadow">
                <form method="GET"
                      action="{{ route('finance.accounting-reports.general-ledger') }}"
                      class="grid grid-cols-1 gap-4 md:grid-cols-4">

                    <div class="md:col-span-2">
                        <label for="account_id" class="block text-sm font-medium text-gray-700">
                            General Ledger Account
                        </label>

                        <select id="account_id"
                                name="account_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Select a GL account</option>

                            @foreach ($accounts as $item)
                                <option value="{{ $item->id }}"
                                    @selected((string) request('account_id') === (string) $item->id)>
                                    {{ $item->account_code }} — {{ $item->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="from" class="block text-sm font-medium text-gray-700">
                            From Date
                        </label>
                        <input id="from"
                               name="from"
                               type="date"
                               value="{{ $from }}"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
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
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div class="flex flex-wrap gap-2 md:col-span-4">
                        <button type="submit"
                                class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Generate Report
                        </button>

                        <a href="{{ route('finance.accounting-reports.general-ledger') }}"
                           class="rounded-md border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            @if ($account)
                {{-- Account Summary --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $account->account_code }} — {{ $account->account_name }}
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ ucfirst($account->account_type) }}
                        @if ($account->account_group)
                            / {{ $account->account_group }}
                        @endif
                        | {{ \Carbon\Carbon::parse($from)->format('d M Y') }}
                        to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
                    </p>

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">
                                Opening Balance
                            </p>
                            <p class="mt-2 text-lg font-bold text-gray-900">
                                ₹{{ number_format(abs($openingBalance), 2) }}
                                <span class="text-xs font-medium text-gray-500">
                                    {{ $openingBalance >= 0 ? 'Dr' : 'Cr' }}
                                </span>
                            </p>
                        </div>

                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">
                                Total Debits
                            </p>
                            <p class="mt-2 text-lg font-bold text-gray-900">
                                ₹{{ number_format($totalDebit, 2) }}
                            </p>
                        </div>

                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">
                                Total Credits
                            </p>
                            <p class="mt-2 text-lg font-bold text-gray-900">
                                ₹{{ number_format($totalCredit, 2) }}
                            </p>
                        </div>

                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase text-gray-500">
                                Closing Balance
                            </p>
                            <p class="mt-2 text-lg font-bold text-gray-900">
                                ₹{{ number_format(abs($closingBalance), 2) }}
                                <span class="text-xs font-medium text-gray-500">
                                    {{ $closingBalance >= 0 ? 'Dr' : 'Cr' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Ledger Transactions --}}
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">
                            Ledger Transactions
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                        Date
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                        Journal / Reference
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                        Narration
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                                        Debit (₹)
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                                        Credit (₹)
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                                        Balance (₹)
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr class="bg-slate-50">
                                    <td colspan="5"
                                        class="px-4 py-3 text-sm font-semibold text-gray-700">
                                        Opening Balance
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800">
                                        {{ number_format(abs($openingBalance), 2) }}
                                        {{ $openingBalance >= 0 ? 'Dr' : 'Cr' }}
                                    </td>
                                </tr>

                                @forelse ($lines as $line)
                                    <tr class="align-top hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                            {{ $line->journalEntry?->journal_date?->format('d M Y') }}
                                        </td>

                                        <td class="px-4 py-4 text-sm">
                                            <a href="{{ route('finance.journals.show', $line->journal_entry_id) }}"
                                               class="font-semibold text-indigo-700 hover:underline">
                                                {{ $line->journalEntry?->journal_no }}
                                            </a>

                                            @if ($line->reference_no)
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ $line->reference_no }}
                                                </div>
                                            @endif
                                        </td>

                                        <td class="max-w-sm px-4 py-4 text-sm text-gray-600">
                                            {{ $line->description ?: $line->journalEntry?->narration ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-800">
                                            {{ (float) $line->debit > 0
                                                ? number_format((float) $line->debit, 2)
                                                : '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm text-gray-800">
                                            {{ (float) $line->credit > 0
                                                ? number_format((float) $line->credit, 2)
                                                : '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold text-gray-800">
                                            {{ number_format(abs($line->running_balance), 2) }}
                                            {{ $line->running_balance >= 0 ? 'Dr' : 'Cr' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            class="px-6 py-10 text-center text-sm text-gray-500">
                                            No posted transactions found for this period.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="bg-slate-50">
                                <tr>
                                    <th colspan="3" class="px-4 py-4 text-right text-sm font-semibold text-gray-800">
                                        Period Totals
                                    </th>
                                    <th class="px-4 py-4 text-right text-sm font-bold text-gray-900">
                                        {{ number_format($totalDebit, 2) }}
                                    </th>
                                    <th class="px-4 py-4 text-right text-sm font-bold text-gray-900">
                                        {{ number_format($totalCredit, 2) }}
                                    </th>
                                    <th class="px-4 py-4 text-right text-sm font-bold text-gray-900">
                                        {{ number_format(abs($closingBalance), 2) }}
                                        {{ $closingBalance >= 0 ? 'Dr' : 'Cr' }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @else
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm">
                    <p class="text-sm text-gray-600">
                        Select a General Ledger account and date range to generate the report.
                    </p>
                </div>
            @endif

            <p class="text-xs text-gray-500">
                Report includes posted ERP journals only. Opening balances reflect
                transactions recorded before the selected start date.
                Results remain provisional until reconciled with the hospital's books.
            </p>

        </div>
    </div>
</x-app-layout>
