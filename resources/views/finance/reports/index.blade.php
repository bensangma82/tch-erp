<x-app-layout>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Finance Reports
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Income, expenses, account movements and financial transactions
                </p>
            </div>

            <a
                href="{{ route('finance.dashboard') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
            >
                ← Finance Dashboard
            </a>

        </div>


        {{-- Date Filter --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <form method="GET" class="flex flex-col gap-4 sm:flex-row sm:items-end">

                <div>
                    <label
                        for="date_from"
                        class="mb-1 block text-sm font-medium text-slate-700"
                    >
                        From
                    </label>

                    <input
                        id="date_from"
                        type="date"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <div>
                    <label
                        for="date_to"
                        class="mb-1 block text-sm font-medium text-slate-700"
                    >
                        To
                    </label>

                    <input
                        id="date_to"
                        type="date"
                        name="date_to"
                        value="{{ $dateTo }}"
                        class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    Generate Report
                </button>

            </form>

        </div>


        {{-- Financial Summary --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Total Income
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-600">
                    ₹{{ number_format((float) $totalIncome, 2) }}
                </div>

            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Total Expenses
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    ₹{{ number_format((float) $totalExpenses, 2) }}
                </div>

            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Net Surplus / Deficit
                </div>

                <div class="mt-2 text-2xl font-bold
                    {{ $netSurplus >= 0
                        ? 'text-emerald-600'
                        : 'text-red-700' }}">
                    ₹{{ number_format((float) $netSurplus, 2) }}
                </div>

            </div>

        </div>


        {{-- Income and Expense Analysis --}}
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Income by Head --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-5 py-4">

                    <h2 class="font-semibold text-slate-900">
                        Income by Head
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Billing collections and posted Finance receipts during the selected period
                    </p>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Income Head
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Category
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($incomeHeads as $head)

                                <tr>

                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                        {{ $head->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $head->category ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                        ₹{{ number_format((float) ($head->report_total ?? 0), 2) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot class="border-t border-slate-200 bg-slate-50">
                            <tr>

                                <td
                                    colspan="2"
                                    class="px-4 py-3 text-right text-sm font-semibold text-slate-700"
                                >
                                    Total Income
                                </td>

                                <td class="px-4 py-3 text-right font-bold text-slate-900">
                                    ₹{{ number_format((float) $totalIncome, 2) }}
                                </td>

                            </tr>
                        </tfoot>

                    </table>

                </div>

            </div>


            {{-- Expenses by Head --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-5 py-4">

                    <h2 class="font-semibold text-slate-900">
                        Expenses by Head
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Posted payment vouchers during the selected period
                    </p>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Expense Head
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Category
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($expenseHeads as $head)

                                <tr>

                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                        {{ $head->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $head->category ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                        ₹{{ number_format((float) ($head->report_total ?? 0), 2) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot class="border-t border-slate-200 bg-slate-50">
                            <tr>

                                <td
                                    colspan="2"
                                    class="px-4 py-3 text-right text-sm font-semibold text-slate-700"
                                >
                                    Total Expenses
                                </td>

                                <td class="px-4 py-3 text-right font-bold text-slate-900">
                                    ₹{{ number_format((float) $totalExpenses, 2) }}
                                </td>

                            </tr>
                        </tfoot>

                    </table>

                </div>

            </div>

        </div>


        {{-- Account Movements --}}
        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-semibold text-slate-900">
                    Account Movements
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Movement during the selected period; opening balances are excluded
                </p>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Account
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Receipts
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Payments
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Transfer In
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Transfer Out
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Net Movement
                            </th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @foreach($accountMovements as $account)

                            <tr>

                                <td class="px-4 py-3">

                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $account->name }}
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        {{ $account->code }}
                                    </div>

                                </td>

                                <td class="px-4 py-3 text-right text-sm text-slate-700">
                                    ₹{{ number_format((float) $account->report_receipts, 2) }}
                                </td>

                                <td class="px-4 py-3 text-right text-sm text-slate-700">
                                    ₹{{ number_format((float) $account->report_payments, 2) }}
                                </td>

                                <td class="px-4 py-3 text-right text-sm text-slate-700">
                                    ₹{{ number_format((float) $account->report_transfers_in, 2) }}
                                </td>

                                <td class="px-4 py-3 text-right text-sm text-slate-700">
                                    ₹{{ number_format((float) $account->report_transfers_out, 2) }}
                                </td>

                                <td class="px-4 py-3 text-right text-sm font-bold
                                    {{ $account->report_net_movement >= 0
                                        ? 'text-slate-900'
                                        : 'text-red-700' }}">
                                    ₹{{ number_format((float) $account->report_net_movement, 2) }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>


        {{-- Posted Transactions --}}
        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">

                <h2 class="font-semibold text-slate-900">
                    Posted Transactions
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Transactions included in this report
                </p>

            </div>

            @if($transactions->isEmpty())

                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    No posted transactions found for this period.
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
                                    Voucher
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Head
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Account
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($transactions as $voucher)

                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                        {{ $voucher->voucher_date?->format('d M Y') }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium">

                                        <a
                                            href="{{ route('finance.vouchers.show', $voucher) }}"
                                            class="text-indigo-600 hover:text-indigo-800"
                                        >
                                            {{ $voucher->voucher_no }}
                                        </a>

                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ ucfirst($voucher->voucher_type) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $voucher->voucher_type === 'transfer'
                                            ? 'Transfer'
                                            : ($voucher->financeHead?->name ?? '—') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">

                                        {{ $voucher->financeAccount?->name ?? '—' }}

                                        @if(
                                            $voucher->voucher_type === 'transfer'
                                            && $voucher->destinationAccount
                                        )
                                            <span class="mx-1 text-slate-400">→</span>
                                            {{ $voucher->destinationAccount->name }}
                                        @endif

                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $transactions->links() }}
                </div>

            @endif

        </div>

    </div>

</x-app-layout>