<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Current Trial Balance — Tally
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Live read-only Trial Balance retrieved from the configured Tally company.
                </p>
            </div>

            <a
                href="{{ route('finance.tally.index') }}"
                class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
            >
                Back to Tally Integration
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if ($error)
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $error }}
                </div>
            @endif

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Total Debit
                    </div>

                    <div class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format($totalDebit, 2) }}
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Total Credit
                    </div>

                    <div class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format($totalCredit, 2) }}
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Difference
                    </div>

                    <div class="mt-2 text-2xl font-bold {{ abs($difference) < 0.01 ? 'text-green-700' : 'text-red-700' }}">
                        ₹{{ number_format(abs($difference), 2) }}
                    </div>

                    <div class="mt-1 text-xs {{ abs($difference) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                        {{ abs($difference) < 0.01 ? 'Trial Balance is balanced' : 'Trial Balance is not balanced' }}
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                Trial Balance
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Current balances reported by Tally.
                            </p>
                        </div>

                        <div class="text-sm text-gray-500">
                            {{ count($trialBalance) }} ledgers
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Particulars
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Group
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Debit
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Credit
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($trialBalance as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                        {{ $row['name'] }}
                                    </td>

                                    <td class="px-6 py-3 text-sm text-gray-600">
                                        {{ $row['parent'] ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm tabular-nums text-gray-800">
                                        @if ($row['debit'] > 0)
                                            ₹{{ number_format($row['debit'], 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm tabular-nums text-gray-800">
                                        @if ($row['credit'] > 0)
                                            ₹{{ number_format($row['credit'], 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-6 py-10 text-center text-sm text-gray-500"
                                    >
                                        @if ($error)
                                            Trial Balance data could not be retrieved.
                                        @else
                                            No Trial Balance data was returned by Tally.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        @if (count($trialBalance) > 0)
                            <tfoot class="border-t-2 border-gray-300 bg-gray-50">
                                <tr>
                                    <td
                                        colspan="2"
                                        class="px-6 py-4 text-right text-sm font-bold text-gray-900"
                                    >
                                        Total
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold tabular-nums text-gray-900">
                                        ₹{{ number_format($totalDebit, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold tabular-nums text-gray-900">
                                        ₹{{ number_format($totalCredit, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <div class="mt-4 text-xs text-gray-500">
                This report currently displays the latest balances available from Tally.
                Historical “as at” reporting will be enabled after validation with the licensed Tally environment.
            </div>

        </div>
    </div>
</x-app-layout>