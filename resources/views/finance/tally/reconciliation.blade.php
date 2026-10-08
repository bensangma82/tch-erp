
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Tally Reconciliation
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Compare posted ERP Finance vouchers with vouchers imported from Tally.
                </p>
            </div>

            <a href="{{ route('finance.tally.index') }}"
               class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                Back to Tally Integration
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- SUMMARY --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ([
                    ['Matched', 'matched', 'text-green-700', 'ERP and Tally linked'],
                    ['Tally Only', 'tally_only', 'text-amber-700', 'No linked ERP voucher'],
                    ['ERP Only', 'erp_only', 'text-amber-700', 'No Tally candidate'],
                    ['Ambiguous', 'ambiguous', 'text-orange-700', 'Multiple candidates'],
                    ['Unmapped', 'unmapped', 'text-purple-700', 'Ledger mapping required'],
                    ['Differences', 'difference', 'text-red-700', 'Accounting differences'],
                ] as [$label, $key, $color, $description])
                    <div class="rounded-xl bg-white p-5 shadow">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ $label }}
                        </div>
                        <div class="mt-2 text-2xl font-bold {{ $color }}">
                            {{ $summary[$key] }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            {{ $description }}
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- STATUS EXPLANATION --}}
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
                <div class="text-sm font-semibold text-blue-900">
                    Reconciliation status
                </div>
                <p class="mt-1 text-sm leading-6 text-blue-800">
                    Automatic matching is conservative. ERP and Tally vouchers
                    are linked only when the reconciliation rules identify a
                    unique match. Ambiguous transactions remain unresolved.
                </p>
            </div>

            {{-- MATCHED VOUCHERS --}}
            <div class="overflow-hidden rounded-xl bg-white shadow">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">
                            Matched Vouchers
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            ERP Finance vouchers successfully reconciled with imported Tally vouchers.
                        </p>
                    </div>
                    <div class="text-sm font-semibold text-green-700">
                        {{ $matched->count() }} matched
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach (['Date', 'ERP Voucher', 'Tally Voucher', 'Narration', 'Amount', 'Match', 'Action'] as $heading)
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($matched as $voucher)
                                @php
                                    $tallyAmount = round(
                                        $voucher->entries->sum(
                                            fn ($entry) => abs((float) $entry->amount)
                                        ) / 2,
                                        2
                                    );

                                    $method = match ($voucher->match_method) {
                                        'exact_remote_id' => 'Exact REMOTEID',
                                        'secondary_match' => 'Secondary',
                                        'manual' => 'Manual',
                                        default => $voucher->match_method ?: '—',
                                    };
                                @endphp

                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                    </td>

                                    <td class="px-6 py-3 text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ $voucher->financeVoucher?->voucher_no ?: '—' }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ ucfirst($voucher->financeVoucher?->voucher_type ?: '') }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-3 text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ $voucher->voucher_type ?: '—' }}
                                            {{ $voucher->voucher_number ? '#'.$voucher->voucher_number : '' }}
                                        </div>
                                        <div class="mt-1 break-all text-xs text-gray-500">
                                            {{ $voucher->guid ?: 'No GUID' }}
                                        </div>
                                    </td>

                                    <td class="max-w-xs px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->narration ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium tabular-nums text-gray-900">
                                        ₹{{ number_format($tallyAmount, 2) }}
                                    </td>

                                    <td class="px-6 py-3 text-sm">
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                            {{ $method }}
                                        </span>

                                        @if ($voucher->match_confidence !== null)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ number_format((float) $voucher->match_confidence, 0) }}% confidence
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm">
                                        @if ($voucher->financeVoucher)
                                            <a href="{{ route('finance.tally.reconciliation.erp', $voucher->financeVoucher) }}"
                                               class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                                Review
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No reconciled vouchers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ACCOUNTING DIFFERENCES --}}
            <div class="overflow-hidden rounded-xl border border-red-200 bg-white shadow">
                <div class="flex items-center justify-between gap-4 border-b border-red-200 bg-red-50 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-red-900">
                            Accounting Differences
                        </h3>
                        <p class="mt-1 text-sm text-red-700">
                            ERP and Tally vouchers identified as the same transaction
                            but with differing accounting details.
                        </p>
                    </div>
                    <div class="text-sm font-semibold text-red-800">
                        {{ $differences->count() }}
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach (['Date', 'ERP Voucher', 'Tally Voucher', 'ERP Amount', 'Tally Amount', 'Difference'] as $heading)
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($differences as $voucher)
                                @php
                                    $tallyAmount = round(
                                        $voucher->entries->sum(
                                            fn ($entry) => abs((float) $entry->amount)
                                        ) / 2,
                                        2
                                    );
                                    $erpVoucher = $voucher->financeVoucher;
                                @endphp

                                <tr class="hover:bg-red-50/40">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm">
                                        @if ($erpVoucher)
                                            <a href="{{ route('finance.tally.reconciliation.erp', $erpVoucher) }}"
                                               class="font-semibold text-indigo-700 hover:underline">
                                                {{ $erpVoucher->voucher_no }}
                                            </a>
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ ucfirst($erpVoucher->voucher_type) }}
                                            </div>
                                            <a href="{{ route('finance.tally.reconciliation.erp', $erpVoucher) }}"
                                               class="mt-2 inline-flex rounded-md border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-800 hover:bg-red-100">
                                                Review difference
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ $voucher->voucher_type ?: '—' }}
                                            {{ $voucher->voucher_number ? '#'.$voucher->voucher_number : '' }}
                                        </div>
                                        <div class="mt-1 break-all text-xs text-gray-500">
                                            {{ $voucher->guid ?: 'No GUID' }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold tabular-nums">
                                        @if ($erpVoucher)
                                            ₹{{ number_format((float) $erpVoucher->amount, 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold tabular-nums">
                                        ₹{{ number_format($tallyAmount, 2) }}
                                    </td>

                                    <td class="max-w-md px-6 py-4 text-sm">
                                        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-800">
                                            {{ $voucher->reconciliation_notes ?: 'Accounting details differ.' }}
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500">
                                            Match:
                                            {{ $voucher->match_method === 'exact_remote_id'
                                                ? 'Exact REMOTEID'
                                                : ($voucher->match_method ?: '—') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No accounting differences detected.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- AMBIGUOUS ERP VOUCHERS --}}
            <div class="overflow-hidden rounded-xl bg-white shadow">
                <div class="flex items-center justify-between gap-4 border-b border-orange-200 bg-orange-50 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-orange-900">
                            Ambiguous ERP Vouchers
                        </h3>
                        <p class="mt-1 text-sm text-orange-700">
                            More than one Tally voucher satisfies the automatic matching rules.
                            These transactions require review.
                        </p>
                    </div>
                    <div class="text-sm font-semibold text-orange-800">
                        {{ $ambiguous->count() }}
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach (['Date', 'ERP Voucher', 'Narration', 'Amount', 'Candidates'] as $heading)
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($ambiguous as $row)
                                @php
                                    $voucher = $row['voucher'];
                                @endphp

                                <tr class="hover:bg-orange-50/40">
                                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                    </td>

                                    <td class="px-6 py-3 text-sm">
                                        <a href="{{ route('finance.tally.reconciliation.erp', $voucher) }}"
                                           class="font-semibold text-indigo-700 hover:underline">
                                            {{ $voucher->voucher_no }}
                                        </a>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ ucfirst($voucher->voucher_type) }}
                                        </div>
                                        <a href="{{ route('finance.tally.reconciliation.erp', $voucher) }}"
                                           class="mt-2 inline-flex rounded-md border border-orange-200 bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-800 hover:bg-orange-100">
                                            Review candidates
                                        </a>
                                    </td>

                                    <td class="max-w-xs px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->narration ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium tabular-nums">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </td>

                                    <td class="px-6 py-3 text-center">
                                        <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800">
                                            {{ $row['candidate_count'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No ambiguous vouchers.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ERP ONLY AND UNMAPPED --}}
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

                {{-- ERP ONLY --}}
                <div class="overflow-hidden rounded-xl bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            ERP Only
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Posted ERP vouchers with no matching Tally candidate.
                        </p>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse ($erpOnly as $row)
                            @php
                                $voucher = $row['voucher'];
                            @endphp

                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ $voucher->voucher_no }}
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600">
                                            {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                            · {{ ucfirst($voucher->voucher_type) }}
                                        </div>

                                        @if ($voucher->narration)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $voucher->narration }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="whitespace-nowrap text-sm font-semibold tabular-nums text-gray-900">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-sm text-gray-500">
                                No ERP-only vouchers.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- UNMAPPED --}}
                <div class="overflow-hidden rounded-xl bg-white shadow">
                    <div class="border-b border-purple-200 bg-purple-50 px-6 py-4">
                        <h3 class="text-lg font-semibold text-purple-900">
                            Unmapped ERP Vouchers
                        </h3>
                        <p class="mt-1 text-sm text-purple-700">
                            Ledger mapping is incomplete, so automatic reconciliation cannot proceed.
                        </p>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @forelse ($unmapped as $row)
                            @php
                                $voucher = $row['voucher'];
                            @endphp

                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ $voucher->voucher_no }}
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600">
                                            {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                            · {{ ucfirst($voucher->voucher_type) }}
                                        </div>

                                        <div class="mt-2 text-xs text-purple-700">
                                            Head: {{ $voucher->financeHead?->name ?: '—' }}
                                            · Account: {{ $voucher->financeAccount?->name ?: '—' }}

                                            @if ($voucher->destinationAccount)
                                                · Destination: {{ $voucher->destinationAccount->name }}
                                            @endif
                                        </div>
                                    </div>

                                    <div class="whitespace-nowrap text-sm font-semibold tabular-nums text-gray-900">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-sm text-gray-500">
                                No unmapped ERP vouchers.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- TALLY ONLY --}}
            <div class="overflow-hidden rounded-xl bg-white shadow">
                <div class="flex items-center justify-between gap-4 border-b border-amber-200 bg-amber-50 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-amber-900">
                            Tally Only
                        </h3>
                        <p class="mt-1 text-sm text-amber-700">
                            Vouchers present in Tally but not currently linked to an ERP Finance voucher.
                        </p>
                    </div>

                    <div class="text-sm font-semibold text-amber-800">
                        {{ $tallyOnly->count() }}
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach (['Date', 'Tally Voucher', 'Narration', 'Ledgers', 'Amount', 'Action'] as $heading)
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($tallyOnly as $voucher)
                                @php
                                    $tallyAmount = round(
                                        $voucher->entries->sum(
                                            fn ($entry) => abs((float) $entry->amount)
                                        ) / 2,
                                        2
                                    );
                                @endphp

                                <tr class="hover:bg-amber-50/40">
                                    <td class="whitespace-nowrap px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                    </td>

                                    <td class="px-6 py-3 text-sm">
                                        <div class="font-medium text-gray-900">
                                            {{ $voucher->voucher_type ?: '—' }}
                                            {{ $voucher->voucher_number ? '#'.$voucher->voucher_number : '' }}
                                        </div>
                                        <div class="mt-1 break-all text-xs text-gray-500">
                                            {{ $voucher->guid ?: 'No GUID' }}
                                        </div>
                                    </td>

                                    <td class="max-w-xs px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->narration ?: '—' }}
                                    </td>

                                    <td class="px-6 py-3 text-sm text-gray-700">
                                        @forelse ($voucher->entries as $entry)
                                            <div>{{ $entry->ledger_name }}</div>
                                        @empty
                                            —
                                        @endforelse
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium tabular-nums text-gray-900">
                                        ₹{{ number_format($tallyAmount, 2) }}
                                    </td>

                                    {{-- REVIEW ACTION: TALLY ONLY --}}
                                    <td class="whitespace-nowrap px-6 py-3 text-center">
                                        @if (
                                            $voucher->finance_voucher_id === null
                                            && in_array(
                                                strtolower(trim((string) $voucher->voucher_type)),
                                                ['receipt', 'payment', 'contra'],
                                                true
                                            )
                                        )
                                            <a href="{{ route('finance.tally.import.review', $voucher) }}"
                                               class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Review &amp; Create Draft
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-500">
                                                Manual review required
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No Tally-only vouchers.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
