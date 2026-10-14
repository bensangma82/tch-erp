<x-app-layout>
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Tally Journal Voucher Preview
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Review the ERP accounting journal before Tally export.
                </p>
            </div>

            <a
                href="{{ route('finance.tally.journals.index') }}"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Journals
            </a>
        </div>

        {{-- SAFETY NOTICE --}}
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p class="text-sm font-semibold text-blue-900">
                Preview Only — Not Transmitted
            </p>

            <p class="mt-1 text-xs text-blue-700">
                This page is read-only. No accounting voucher
                will be sent to TallyPrime.
            </p>
        </div>

        {{-- JOURNAL DETAILS --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Journal Number
                    </p>
                    <p class="mt-1 font-semibold text-gray-900">
                        {{ $journal->journal_no }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Accounting Date
                    </p>
                    <p class="mt-1 font-semibold text-gray-900">
                        {{ $journal->journal_date?->format('d M Y') ?: '—' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Journal Status
                    </p>
                    <p class="mt-1 font-semibold text-gray-900">
                        {{ ucfirst($journal->status) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Configured Tally Company
                    </p>
                    <p class="mt-1 font-semibold text-gray-900">
                        {{ $tallyCompany ?: 'Not configured' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500">
                        Tally Export Status
                    </p>
                    <p class="mt-1 font-semibold text-gray-900">
                        {{ $export
                            ? ucfirst(str_replace('_', ' ', $export->status))
                            : 'Not Exported' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500">
                        ERP Reference
                    </p>
                    <p class="mt-1 font-semibold text-gray-900">
                        {{ $journal->reference_no ?: '—' }}
                    </p>
                </div>

            </div>

            @if (filled($journal->narration))
                <div class="mt-5 border-t border-gray-100 pt-4">
                    <p class="text-xs font-medium text-gray-500">
                        Narration
                    </p>
                    <p class="mt-1 text-sm text-gray-700">
                        {{ $journal->narration }}
                    </p>
                </div>
            @endif
        </div>

        {{-- EXPORT ELIGIBILITY --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">
                Export Validation
            </h2>

            @if ($eligibility['eligible'])
                <div class="mt-3 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                    Journal passed the current export eligibility checks.
                </div>
            @else
                <div class="mt-3 rounded-lg bg-red-50 p-4">
                    <p class="text-sm font-semibold text-red-800">
                        Journal is not eligible for a new export.
                    </p>

                    @if (! empty($eligibility['errors']))
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                            @foreach ($eligibility['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <p class="mt-3 text-xs text-gray-500">
                Eligibility does not authorize transmission.
                Automated Tally journal export remains disabled.
            </p>
        </div>

        {{-- DEBIT AND CREDIT ENTRIES --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">
                    Accounting Entries
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                #
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                GL Code
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                Ledger
                            </th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">
                                Debit (₹)
                            </th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">
                                Credit (₹)
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($journal->lines as $line)
                            <tr>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $line->line_no }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                   {{ $line->generalLedgerAccount?->account_code ?: '—' }}
                                </td>

                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $line->generalLedgerAccount?->account_name ?: 'Unmapped GL Account' }}
                                </td>

                                <td class="px-4 py-3 text-right tabular-nums text-gray-800">
                                    {{ number_format((float) $line->debit, 2) }}
                                </td>

                                <td class="px-4 py-3 text-right tabular-nums text-gray-800">
                                    {{ number_format((float) $line->credit, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    No accounting entries found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot class="bg-gray-50">
                        <tr class="font-bold text-gray-900">
                            <td colspan="3" class="px-4 py-4 text-right">
                                Total
                            </td>
                            <td class="px-4 py-4 text-right tabular-nums">
                                {{ number_format($totalDebit, 2) }}
                            </td>
                            <td class="px-4 py-4 text-right tabular-nums">
                                {{ number_format($totalCredit, 2) }}
                            </td>
                        </tr>
                    </tfoot>

                </table>
            </div>
        </div>

        {{-- BALANCE STATUS --}}
        @php
            $balanced = round($totalDebit, 2) === round($totalCredit, 2);
        @endphp

        <div class="mt-4 text-right">
            <span class="inline-flex rounded-lg px-3 py-2 text-sm font-semibold
                {{ $balanced
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800' }}">
                {{ $balanced ? 'Debit and Credit Totals Match' : 'Journal Is Not Balanced' }}
            </span>
        </div>

        {{-- FOOTER --}}
        <p class="mt-6 text-xs text-gray-500">
            TCH ERP accounting preview. The displayed values are
            from the ERP journal and do not constitute confirmation
            of posting in TallyPrime.
        </p>

    </div>
</x-app-layout>
