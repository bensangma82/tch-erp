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

            <a
                href="{{ route('finance.tally.index') }}"
                class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
            >
                Back to Tally Integration
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- SUMMARY --}}
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Matched
                    </div>

                    <div class="mt-2 text-2xl font-bold text-green-700">
                        {{ $summary['matched'] }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        ERP and Tally linked
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Tally Only
                    </div>

                    <div class="mt-2 text-2xl font-bold text-amber-700">
                        {{ $summary['tally_only'] }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        No linked ERP voucher
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        ERP Only
                    </div>

                    <div class="mt-2 text-2xl font-bold text-amber-700">
                        {{ $summary['erp_only'] }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        No Tally candidate
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Ambiguous
                    </div>

                    <div class="mt-2 text-2xl font-bold text-orange-700">
                        {{ $summary['ambiguous'] }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        Multiple candidates
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Unmapped
                    </div>

                    <div class="mt-2 text-2xl font-bold text-purple-700">
                        {{ $summary['unmapped'] }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        Ledger mapping required
                    </div>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Differences
                    </div>

                    <div class="mt-2 text-2xl font-bold {{ $summary['difference'] === 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ $summary['difference'] }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        Accounting differences
                    </div>
                </div>
            </div>

            {{-- STATUS EXPLANATION --}}
            <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
                <div class="text-sm font-semibold text-blue-900">
                    Reconciliation status
                </div>

                <div class="mt-1 text-sm leading-6 text-blue-800">
                    Automatic matching is conservative. ERP and Tally vouchers are linked only when
                    the reconciliation rules identify a unique match. Ambiguous transactions remain
                    unresolved rather than being linked automatically.
                </div>
            </div>

            {{-- MATCHED --}}
            <div class="mb-6 overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between gap-4">
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
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Date
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    ERP Voucher
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Tally Voucher
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Narration
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Amount
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Match
                                </th>
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

                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ $voucher->guid ?: 'No GUID' }}
                                        </div>
                                    </td>

                                    <td class="max-w-xs px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->narration ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium tabular-nums text-gray-900">
                                        ₹{{ number_format($tallyAmount, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-sm">
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                            {{ $method }}
                                        </span>

                                        @if ($voucher->match_confidence !== null)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ number_format((float) $voucher->match_confidence, 0) }}% confidence
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No reconciled vouchers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- AMBIGUOUS --}}
            <div class="mb-6 overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-orange-200 bg-orange-50 px-6 py-4">
                    <div class="flex items-center justify-between gap-4">
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
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Date
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    ERP Voucher
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Narration
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Amount
                                </th>

                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Candidates
                                </th>
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
                                        <div class="font-medium text-gray-900">
                                            {{ $voucher->voucher_no }}
                                        </div>

                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ ucfirst($voucher->voucher_type) }}
                                        </div>
                                    </td>

                                    <td class="max-w-xs px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->narration ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium tabular-nums text-gray-900">
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

            {{-- ERP ONLY + UNMAPPED --}}
            <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">

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
                                            ·
                                            {{ ucfirst($voucher->voucher_type) }}
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
                                            ·
                                            {{ ucfirst($voucher->voucher_type) }}
                                        </div>

                                        <div class="mt-2 text-xs text-purple-700">
                                            Head:
                                            {{ $voucher->financeHead?->name ?: '—' }}
                                            ·
                                            Account:
                                            {{ $voucher->financeAccount?->name ?: '—' }}

                                            @if ($voucher->destinationAccount)
                                                · Destination:
                                                {{ $voucher->destinationAccount->name }}
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
            <div class="mb-6 overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-amber-200 bg-amber-50 px-6 py-4">
                    <div class="flex items-center justify-between gap-4">
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
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Date
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Tally Voucher
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Narration
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Ledgers
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">
                                    Amount
                                </th>
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

                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ $voucher->guid ?: 'No GUID' }}
                                        </div>
                                    </td>

                                    <td class="max-w-xs px-6 py-3 text-sm text-gray-700">
                                        {{ $voucher->narration ?: '—' }}
                                    </td>

                                    <td class="px-6 py-3 text-sm text-gray-700">
                                        @forelse ($voucher->entries as $entry)
                                            <div>
                                                {{ $entry->ledger_name }}
                                            </div>
                                        @empty
                                            —
                                        @endforelse
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm font-medium tabular-nums text-gray-900">
                                        ₹{{ number_format($tallyAmount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No Tally-only vouchers.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DIFFERENCES --}}
            <div class="overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-red-200 bg-red-50 px-6 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-red-900">
                                Accounting Differences
                            </h3>

                            <p class="mt-1 text-sm text-red-700">
                                Identified ERP/Tally voucher pairs whose accounting content differs.
                            </p>
                        </div>

                        <div class="text-sm font-semibold text-red-800">
                            {{ $differences->count() }}
                        </div>
                    </div>
                </div>

                @forelse ($differences as $voucher)
                    <div class="border-b border-gray-100 px-6 py-4 last:border-b-0">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-medium text-gray-900">
                                    {{ $voucher->financeVoucher?->voucher_no ?: 'ERP voucher unavailable' }}
                                </div>

                                <div class="mt-1 text-sm text-gray-600">
                                    Tally:
                                    {{ $voucher->voucher_type ?: '—' }}
                                    {{ $voucher->voucher_number ? '#'.$voucher->voucher_number : '' }}
                                </div>

                                @if ($voucher->reconciliation_notes)
                                    <div class="mt-2 text-sm text-red-700">
                                        {{ $voucher->reconciliation_notes }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center">
                        <div class="text-sm font-medium text-green-700">
                            No accounting differences currently recorded.
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            Detailed content-difference detection will be expanded in the next reconciliation stage.
                        </div>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>