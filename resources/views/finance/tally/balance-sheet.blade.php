<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Current Balance Sheet — Tally
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Live read-only Balance Sheet derived from the configured Tally company.
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

            {{-- SUMMARY --}}
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Total Assets
                    </div>

                    <div class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format($totalAssets, 2) }}
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Liabilities &amp; Equity
                    </div>

                    <div class="mt-2 text-2xl font-bold text-gray-900">
                        ₹{{ number_format($totalLiabilities, 2) }}
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Difference
                    </div>

                    <div
                        class="mt-2 text-2xl font-bold {{ abs($difference) < 0.01 ? 'text-green-700' : 'text-red-700' }}"
                    >
                        ₹{{ number_format(abs($difference), 2) }}
                    </div>

                    <div
                        class="mt-1 text-xs {{ abs($difference) < 0.01 ? 'text-green-600' : 'text-red-600' }}"
                    >
                        {{ abs($difference) < 0.01
                            ? 'Balance Sheet is balanced'
                            : 'Balance Sheet requires reconciliation' }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

                {{-- ASSETS --}}
                <div class="overflow-hidden rounded-xl bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Assets
                                </h3>

                                <p class="mt-1 text-sm text-gray-500">
                                    Asset ledgers classified from the Tally group hierarchy.
                                </p>
                            </div>

                            <div class="text-sm text-gray-500">
                                {{ count($assets) }} ledgers
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
                                        Amount
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($assets as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                            {{ $row['name'] }}
                                        </td>

                                        <td class="px-6 py-3 text-sm text-gray-600">
                                            {{ $row['group'] ?: '—' }}
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-6 py-3 text-right text-sm tabular-nums {{ $row['amount'] < 0 ? 'text-red-700' : 'text-gray-800' }}"
                                        >
                                            @if ($row['amount'] < 0)
                                                (₹{{ number_format(abs($row['amount']), 2) }})
                                            @else
                                                ₹{{ number_format($row['amount'], 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="3"
                                            class="px-6 py-10 text-center text-sm text-gray-500"
                                        >
                                            @if ($error)
                                                Asset data could not be retrieved.
                                            @else
                                                No asset ledgers were returned by Tally.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            @if (count($assets) > 0)
                                <tfoot class="border-t-2 border-gray-300 bg-gray-50">
                                    <tr>
                                        <td
                                            colspan="2"
                                            class="px-6 py-4 text-right text-sm font-bold text-gray-900"
                                        >
                                            Total Assets
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold tabular-nums text-gray-900">
                                            ₹{{ number_format($totalAssets, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                {{-- LIABILITIES AND EQUITY --}}
                <div class="overflow-hidden rounded-xl bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    Liabilities &amp; Equity
                                </h3>

                                <p class="mt-1 text-sm text-gray-500">
                                    Liability and capital ledgers classified from the Tally group hierarchy.
                                </p>
                            </div>

                            <div class="text-sm text-gray-500">
                                {{ count($liabilities) }} ledgers
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
                                        Amount
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($liabilities as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                            {{ $row['name'] }}
                                        </td>

                                        <td class="px-6 py-3 text-sm text-gray-600">
                                            {{ $row['group'] ?: '—' }}
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-6 py-3 text-right text-sm tabular-nums {{ $row['amount'] < 0 ? 'text-red-700' : 'text-gray-800' }}"
                                        >
                                            @if ($row['amount'] < 0)
                                                (₹{{ number_format(abs($row['amount']), 2) }})
                                            @else
                                                ₹{{ number_format($row['amount'], 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    @if (abs($currentProfitLoss) < 0.01)
                                        <tr>
                                            <td
                                                colspan="3"
                                                class="px-6 py-10 text-center text-sm text-gray-500"
                                            >
                                                @if ($error)
                                                    Liability and equity data could not be retrieved.
                                                @else
                                                    No liability or equity ledgers were returned by Tally.
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse

                                @if (abs($currentProfitLoss) >= 0.01)
                                    <tr class="bg-gray-50">
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">
                                            Current Profit &amp; Loss
                                        </td>

                                        <td class="px-6 py-3 text-sm text-gray-600">
                                            Current Period Result
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-6 py-3 text-right text-sm font-semibold tabular-nums {{ $currentProfitLoss < 0 ? 'text-red-700' : 'text-gray-900' }}"
                                        >
                                            @if ($currentProfitLoss < 0)
                                                (₹{{ number_format(abs($currentProfitLoss), 2) }})
                                            @else
                                                ₹{{ number_format($currentProfitLoss, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </tbody>

                            @if (count($liabilities) > 0 || abs($currentProfitLoss) >= 0.01)
                                <tfoot class="border-t-2 border-gray-300 bg-gray-50">
                                    <tr>
                                        <td
                                            colspan="2"
                                            class="px-6 py-4 text-right text-sm font-bold text-gray-900"
                                        >
                                            Total Liabilities &amp; Equity
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold tabular-nums text-gray-900">
                                            ₹{{ number_format($totalLiabilities, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

            </div>

            {{-- RECONCILIATION --}}
            @if (! $error)
                <div class="mt-6 rounded-xl bg-white p-6 shadow">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <div class="text-sm font-semibold text-gray-600">
                                Balance Sheet Reconciliation
                            </div>

                            <div
                                class="mt-1 text-lg font-bold {{ abs($difference) < 0.01 ? 'text-green-700' : 'text-red-700' }}"
                            >
                                {{ abs($difference) < 0.01
                                    ? 'Balanced'
                                    : 'Out of Balance' }}
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Difference
                            </div>

                            <div
                                class="mt-1 text-2xl font-bold tabular-nums {{ abs($difference) < 0.01 ? 'text-green-700' : 'text-red-700' }}"
                            >
                                ₹{{ number_format(abs($difference), 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-4 text-xs text-gray-500">
                This report is calculated from the latest Tally ledger balances and group hierarchy.
                Current Profit &amp; Loss is included in the equity side for Balance Sheet reconciliation.
                Historical period reporting will be enabled after validation with the licensed Tally environment.
            </div>

        </div>
    </div>
</x-app-layout>