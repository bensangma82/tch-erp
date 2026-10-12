
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                Accounting Reports
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Accrual-based financial reports generated from TCH ERP journal entries.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p class="text-sm text-blue-900">
                    <strong>ERP Accounting Reports:</strong>
                    These reports are based on posted journal entries in the ERP.
                    They are separate from Tally reports and remain provisional
                    until opening balances and journal postings are reconciled.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- General Ledger --}}
                <a href="{{ route('finance.accounting-reports.general-ledger') }}"
                   class="block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                    <div class="mb-3 text-sm font-semibold text-indigo-700">
                        GENERAL LEDGER
                    </div>

                    <h3 class="text-lg font-bold text-gray-900">
                        Account Ledger
                    </h3>

                    <p class="mt-2 text-sm text-gray-600">
                        View transactions for individual GL accounts,
                        including opening balances, debit and credit movements,
                        running balances, and closing balances.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-700">
                        View General Ledger →
                    </div>
                </a>

                {{-- Trial Balance --}}
                <a href="{{ route('finance.accounting-reports.trial-balance') }}"
                   class="block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                    <div class="mb-3 text-sm font-semibold text-indigo-700">
                        TRIAL BALANCE
                    </div>

                    <h3 class="text-lg font-bold text-gray-900">
                        Trial Balance
                    </h3>

                    <p class="mt-2 text-sm text-gray-600">
                        Review the closing debit and credit balances of
                        all GL accounts as of a selected date and verify
                        whether total debits equal total credits.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-700">
                        View Trial Balance →
                    </div>
                </a>

                {{-- Income and Expenditure --}}
                <a href="{{ route('finance.accounting-reports.income-expenditure') }}"
                   class="block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                    <div class="mb-3 text-sm font-semibold text-indigo-700">
                        INCOME &amp; EXPENDITURE
                    </div>

                    <h3 class="text-lg font-bold text-gray-900">
                        Income &amp; Expenditure Statement
                    </h3>

                    <p class="mt-2 text-sm text-gray-600">
                        Review recognized income and expenses for a selected
                        period and calculate the hospital's operating
                        surplus or deficit.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-700">
                        View Income &amp; Expenditure →
                    </div>
                </a>

                {{-- Balance Sheet --}}
                <a href="{{ route('finance.accounting-reports.balance-sheet') }}"
                   class="block rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">
                    <div class="mb-3 text-sm font-semibold text-indigo-700">
                        BALANCE SHEET
                    </div>

                    <h3 class="text-lg font-bold text-gray-900">
                        Balance Sheet
                    </h3>

                    <p class="mt-2 text-sm text-gray-600">
                        Review assets, liabilities, equity and accumulated
                        surplus or deficit, with a balance reconciliation
                        as of a selected date.
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-700">
                        View Balance Sheet →
                    </div>
                </a>

            </div>

        </div>
    </div>
</x-app-layout>
