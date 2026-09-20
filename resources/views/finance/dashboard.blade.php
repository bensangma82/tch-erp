<x-app-layout>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Finance Dashboard
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Hospital receipts, payments and financial overview
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('finance.vouchers.index') }}"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    Finance Vouchers
                </a>

                <a
                    href="{{ route('finance.master.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Finance Master
                </a>
            </div>

        </div>


        {{-- Today's Summary --}}
        <div class="mb-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                Today
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Receipts
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-600">
                    ₹{{ number_format((float) $todayReceipts, 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Payments
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    ₹{{ number_format((float) $todayPayments, 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Net Cash Flow
                </div>

                <div class="mt-2 text-2xl font-bold
                    {{ $todayNet >= 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                    ₹{{ number_format((float) $todayNet, 2) }}
                </div>
            </div>

        </div>


        {{-- Monthly Summary --}}
        <div class="mb-3 mt-8">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                This Month
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Receipts
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-600">
                    ₹{{ number_format((float) $monthReceipts, 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Payments
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    ₹{{ number_format((float) $monthPayments, 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Net Cash Flow
                </div>

                <div class="mt-2 text-2xl font-bold
                    {{ $monthNet >= 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                    ₹{{ number_format((float) $monthNet, 2) }}
                </div>
            </div>

        </div>


        {{-- Finance Master Summary --}}
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Active Accounts
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $activeAccounts }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Income Heads
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $activeIncomeHeads }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Expense Heads
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $activeExpenseHeads }}
                </div>
            </div>

        </div>


        {{-- Account Balances --}}
        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="font-semibold text-slate-900">
                        Account Balances
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Calculated from opening balances, posted Finance vouchers, and integrated billing collections
                    </p>
                </div>

                <div class="text-left sm:text-right">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Total Balance
                    </div>

                    <div class="mt-1 text-xl font-bold text-slate-900">
                        ₹{{ number_format((float) $totalAccountBalance, 2) }}
                    </div>
                </div>

            </div>


            @if($accountBalances->isEmpty())

                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    No active Finance accounts found.
                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Account
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Opening
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
                                    Balance
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($accountBalances as $account)

                                <tr class="hover:bg-slate-50">

                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $account->name }}
                                        </div>

                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $account->code }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ ucfirst($account->account_type) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->opening_balance, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->receipt_total, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->payment_total, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->transfer_in_total, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->transfer_out_total, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <span class="text-sm font-bold
                                            {{ $account->calculated_balance >= 0
                                                ? 'text-slate-900'
                                                : 'text-red-700' }}">
                                            ₹{{ number_format((float) $account->calculated_balance, 2) }}
                                        </span>
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot class="border-t border-slate-200 bg-slate-50">
                            <tr>

                                <td
                                    colspan="7"
                                    class="px-4 py-3 text-right text-sm font-semibold text-slate-700"
                                >
                                    Total Account Balance
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-base font-bold text-slate-900">
                                    ₹{{ number_format((float) $totalAccountBalance, 2) }}
                                </td>

                            </tr>
                        </tfoot>

                    </table>

                </div>

            @endif

        </div>


        {{-- Recent Transactions --}}
        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">

                <h2 class="font-semibold text-slate-900">
                    Recent Finance Transactions
                </h2>

                <a
                    href="{{ route('finance.vouchers.index') }}"
                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                >
                    View All
                </a>

            </div>

            @if($recentVouchers->isEmpty())

                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    No finance vouchers have been entered yet.
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

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($recentVouchers as $voucher)

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

                                        @if($voucher->voucher_type === 'transfer')
                                            Transfer
                                        @else
                                            {{ $voucher->financeHead?->name ?? '—' }}
                                        @endif

                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">

                                        {{ $voucher->financeAccount?->name ?? '—' }}

                                        @if(
                                            $voucher->voucher_type === 'transfer'
                                            && $voucher->destinationAccount
                                        )
                                            <span class="mx-1 text-slate-400">
                                                →
                                            </span>

                                            {{ $voucher->destinationAccount->name }}
                                        @endif

                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </td>

                                    <td class="px-4 py-3 text-center">

                                        @if($voucher->status === 'posted')

                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                Posted
                                            </span>

                                        @elseif($voucher->status === 'cancelled')

                                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                                                Cancelled
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                                Draft
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    </div>

</x-app-layout>