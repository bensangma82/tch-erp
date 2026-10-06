<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Current Cash Flow — Tally
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Live read-only Cash Flow derived from accounting voucher movements in the configured Tally company.
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
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Operating Activities
                    </div>

                    <div class="mt-2 text-2xl font-bold {{ $totalOperating < 0 ? 'text-red-700' : 'text-gray-900' }}">
                        @if ($totalOperating < 0)
                            (₹{{ number_format(abs($totalOperating), 2) }})
                        @else
                            ₹{{ number_format($totalOperating, 2) }}
                        @endif
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Investing Activities
                    </div>

                    <div class="mt-2 text-2xl font-bold {{ $totalInvesting < 0 ? 'text-red-700' : 'text-gray-900' }}">
                        @if ($totalInvesting < 0)
                            (₹{{ number_format(abs($totalInvesting), 2) }})
                        @else
                            ₹{{ number_format($totalInvesting, 2) }}
                        @endif
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Financing Activities
                    </div>

                    <div class="mt-2 text-2xl font-bold {{ $totalFinancing < 0 ? 'text-red-700' : 'text-gray-900' }}">
                        @if ($totalFinancing < 0)
                            (₹{{ number_format(abs($totalFinancing), 2) }})
                        @else
                            ₹{{ number_format($totalFinancing, 2) }}
                        @endif
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Net Cash Flow
                    </div>

                    <div class="mt-2 text-2xl font-bold {{ $netCashFlow < 0 ? 'text-red-700' : 'text-green-700' }}">
                        @if ($netCashFlow < 0)
                            (₹{{ number_format(abs($netCashFlow), 2) }})
                        @else
                            ₹{{ number_format($netCashFlow, 2) }}
                        @endif
                    </div>
                </div>
            </div>

            @php
                $sections = [
                    [
                        'title' => 'Operating Activities',
                        'description' => 'Cash movements arising from normal operating income, expenditure and working-capital activity.',
                        'rows' => $operating,
                        'total' => $totalOperating,
                    ],
                    [
                        'title' => 'Investing Activities',
                        'description' => 'Cash movements relating to fixed assets, investments, deposits and loans or advances classified as assets.',
                        'rows' => $investing,
                        'total' => $totalInvesting,
                    ],
                    [
                        'title' => 'Financing Activities',
                        'description' => 'Cash movements relating to capital and borrowings.',
                        'rows' => $financing,
                        'total' => $totalFinancing,
                    ],
                    [
                        'title' => 'Unclassified',
                        'description' => 'Cash movements that could not be classified safely from the current Tally group hierarchy.',
                        'rows' => $unclassified,
                        'total' => $totalUnclassified,
                    ],
                ];
            @endphp

            <div class="space-y-6">
                @foreach ($sections as $section)
                    <div class="overflow-hidden rounded-xl bg-white shadow">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        {{ $section['title'] }}
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $section['description'] }}
                                    </p>
                                </div>

                                <div class="text-sm text-gray-500">
                                    {{ count($section['rows']) }}
                                    {{ count($section['rows']) === 1 ? 'entry' : 'entries' }}
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Date
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Voucher
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Counterparty Ledger
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Group
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Narration
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Amount
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @forelse ($section['rows'] as $row)
                                        <tr class="hover:bg-gray-50">
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                @if (!empty($row['date']) && strlen($row['date']) === 8)
                                                    {{ substr($row['date'], 6, 2) }}/{{ substr($row['date'], 4, 2) }}/{{ substr($row['date'], 0, 4) }}
                                                @else
                                                    {{ $row['date'] ?: '—' }}
                                                @endif
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                <div class="font-medium text-gray-900">
                                                    {{ $row['voucher_type'] ?: '—' }}
                                                </div>

                                                @if (!empty($row['voucher_number']))
                                                    <div class="text-xs text-gray-500">
                                                        No. {{ $row['voucher_number'] }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                {{ $row['counterparty_ledger'] ?: '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                {{ $row['counterparty_group'] ?: '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                {{ $row['narration'] ?: '—' }}
                                            </td>

                                            <td
                                                class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium tabular-nums {{ $row['amount'] < 0 ? 'text-red-700' : 'text-gray-900' }}"
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
                                                colspan="6"
                                                class="px-6 py-10 text-center text-sm text-gray-500"
                                            >
                                                @if ($error)
                                                    Cash Flow data could not be retrieved.
                                                @else
                                                    No transactions in this section.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>

                                @if (count($section['rows']) > 0)
                                    <tfoot class="border-t-2 border-gray-300 bg-gray-50">
                                        <tr>
                                            <td
                                                colspan="5"
                                                class="px-4 py-4 text-right text-sm font-bold text-gray-900"
                                            >
                                                Net {{ $section['title'] }}
                                            </td>

                                            <td
                                                class="whitespace-nowrap px-4 py-4 text-right text-sm font-bold tabular-nums {{ $section['total'] < 0 ? 'text-red-700' : 'text-gray-900' }}"
                                            >
                                                @if ($section['total'] < 0)
                                                    (₹{{ number_format(abs($section['total']), 2) }})
                                                @else
                                                    ₹{{ number_format($section['total'], 2) }}
                                                @endif
                                            </td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- NET CASH FLOW --}}
            <div class="mt-6 rounded-xl bg-white p-6 shadow">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <div class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                            Net Increase / (Decrease) in Cash and Cash Equivalents
                        </div>

                        <p class="mt-1 text-sm text-gray-500">
                            Calculated from the actual cash and bank ledger entries returned by Tally.
                        </p>
                    </div>

                    <div class="text-3xl font-bold tabular-nums {{ $netCashFlow < 0 ? 'text-red-700' : 'text-green-700' }}">
                        @if ($netCashFlow < 0)
                            (₹{{ number_format(abs($netCashFlow), 2) }})
                        @else
                            ₹{{ number_format($netCashFlow, 2) }}
                        @endif
                    </div>
                </div>
            </div>

            @if (abs($totalUnclassified) >= 0.01 || count($unclassified) > 0)
                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Some cash movements remain unclassified. Review the corresponding Tally ledger groups before relying on the report for final financial reporting.
                </div>
            @endif

            <div class="mt-6 text-xs text-gray-500">
                This report is generated from the currently available Tally voucher data.
                Cash and bank accounts are identified from the Tally group hierarchy.
                Historical period selection and formal opening-to-closing cash reconciliation will be added after validation against the licensed Tally environment.
            </div>

        </div>
    </div>
</x-app-layout>