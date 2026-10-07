<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Reconciliation Review
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Review the ERP Finance voucher against possible Tally voucher matches.
                </p>
            </div>

            <a
                href="{{ route('finance.tally.reconciliation') }}"
                class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
            >
                Back to Reconciliation
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- STATUS --}}
            @php
                $status = $diagnostic['status'] ?? 'erp_only';

                $statusLabel = match ($status) {
                    'ambiguous' => 'Ambiguous',
                    'unmapped' => 'Unmapped',
                    'erp_only' => 'ERP Only',
                    default => ucfirst(str_replace('_', ' ', $status)),
                };

                $statusClasses = match ($status) {
                    'ambiguous' => 'border-orange-200 bg-orange-50 text-orange-800',
                    'unmapped' => 'border-purple-200 bg-purple-50 text-purple-800',
                    default => 'border-amber-200 bg-amber-50 text-amber-800',
                };
            @endphp

            <div class="rounded-xl border px-5 py-4 {{ $statusClasses }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-sm font-semibold">
                            Reconciliation Status: {{ $statusLabel }}
                        </div>

                        <div class="mt-1 text-sm">
                            @if ($status === 'ambiguous')
                                More than one Tally voucher satisfies the automatic reconciliation rules.
                                No automatic match has been made.
                            @elseif ($status === 'unmapped')
                                Required Tally ledger mappings are incomplete.
                            @else
                                No confirmed Tally match is currently linked to this ERP voucher.
                            @endif
                        </div>
                    </div>

                    <div class="shrink-0 rounded-full bg-white/70 px-3 py-1 text-sm font-semibold">
                        {{ $diagnostic['candidate_count'] ?? 0 }}
                        {{ ($diagnostic['candidate_count'] ?? 0) === 1 ? 'candidate' : 'candidates' }}
                    </div>
                </div>
            </div>

            {{-- ERP VOUCHER --}}
            <div class="overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                ERP Finance Voucher
                            </div>

                            <h3 class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $financeVoucher->voucher_no }}
                            </h3>
                        </div>

                        <span class="inline-flex w-fit rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                            {{ ucfirst($financeVoucher->voucher_type) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Voucher Date
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $financeVoucher->voucher_date?->format('d M Y') ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Amount
                        </div>

                        <div class="mt-1 text-lg font-bold tabular-nums text-gray-900">
                            ₹{{ number_format((float) $financeVoucher->amount, 2) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Finance Head
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $financeVoucher->financeHead?->name ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Account
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $financeVoucher->financeAccount?->name ?: '—' }}
                        </div>

                        @if ($financeVoucher->destinationAccount)
                            <div class="mt-1 text-xs text-gray-500">
                                Destination:
                                {{ $financeVoucher->destinationAccount->name }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 border-t border-gray-100 px-6 py-5 md:grid-cols-2">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Narration
                        </div>

                        <div class="mt-2 text-sm text-gray-700">
                            {{ $financeVoucher->narration ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Reference / Party
                        </div>

                        <div class="mt-2 text-sm text-gray-700">
                            @if ($financeVoucher->reference_no)
                                <div>
                                    Reference:
                                    <span class="font-medium">
                                        {{ $financeVoucher->reference_no }}
                                    </span>
                                </div>
                            @endif

                            @if ($financeVoucher->party_name)
                                <div class="{{ $financeVoucher->reference_no ? 'mt-1' : '' }}">
                                    Party:
                                    <span class="font-medium">
                                        {{ $financeVoucher->party_name }}
                                    </span>
                                </div>
                            @endif

                            @if (! $financeVoucher->reference_no && ! $financeVoucher->party_name)
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- CANDIDATES --}}
            <div>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Candidate Tally Vouchers
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        These vouchers satisfy the current conservative matching criteria.
                        This page does not change either ERP or Tally.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    @forelse ($candidates as $candidate)
                        @php
                            $tallyAmount = round(
                                $candidate->entries->sum(
                                    fn ($entry) => abs((float) $entry->amount)
                                ) / 2,
                                2
                            );

                            $amountMatches =
                                abs(
                                    $tallyAmount
                                    - (float) $financeVoucher->amount
                                ) < 0.01;

                            $dateMatches =
                                $candidate->voucher_date
                                && $financeVoucher->voucher_date
                                && $candidate->voucher_date->format('Y-m-d')
                                    === $financeVoucher->voucher_date->format('Y-m-d');

                            $narrationMatches =
                                trim(strtolower((string) $candidate->narration))
                                === trim(strtolower((string) $financeVoucher->narration));
                        @endphp

                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Tally Candidate
                                        </div>

                                        <h4 class="mt-1 text-lg font-semibold text-gray-900">
                                            {{ $candidate->voucher_type ?: 'Voucher' }}
                                            {{ $candidate->voucher_number ? '#'.$candidate->voucher_number : '' }}
                                        </h4>
                                    </div>

                                    <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800">
                                        Candidate
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-5 px-6 py-5">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Date
                                    </div>

                                    <div class="mt-1 text-sm font-medium text-gray-900">
                                        {{ $candidate->voucher_date?->format('d M Y') ?: '—' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Amount
                                    </div>

                                    <div class="mt-1 text-lg font-bold tabular-nums text-gray-900">
                                        ₹{{ number_format($tallyAmount, 2) }}
                                    </div>
                                </div>

                                <div class="col-span-2">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Narration
                                    </div>

                                    <div class="mt-1 text-sm text-gray-700">
                                        {{ $candidate->narration ?: '—' }}
                                    </div>
                                </div>

                                <div class="col-span-2">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        GUID
                                    </div>

                                    <div class="mt-1 break-all font-mono text-xs text-gray-600">
                                        {{ $candidate->guid ?: '—' }}
                                    </div>
                                </div>
                            </div>

                            {{-- MATCH INDICATORS --}}
                            <div class="border-t border-gray-100 px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $dateMatches ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}"
                                    >
                                        Date {{ $dateMatches ? 'matches' : 'differs' }}
                                    </span>

                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $amountMatches ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}"
                                    >
                                        Amount {{ $amountMatches ? 'matches' : 'differs' }}
                                    </span>

                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $narrationMatches ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}"
                                    >
                                        Narration {{ $narrationMatches ? 'matches' : 'differs' }}
                                    </span>
                                </div>
                            </div>

                            {{-- LEDGER ENTRIES --}}
                            <div class="border-t border-gray-200">
                                <div class="bg-gray-50 px-6 py-3">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Tally Ledger Entries
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-white">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Ledger
                                                </th>

                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Debit
                                                </th>

                                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                    Credit
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-100">
                                            @forelse ($candidate->entries as $entry)
                                                @php
                                                    $amount = (float) $entry->amount;

                                                    $debit = $amount < 0
                                                        ? abs($amount)
                                                        : null;

                                                    $credit = $amount > 0
                                                        ? $amount
                                                        : null;
                                                @endphp

                                                <tr>
                                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                                        {{ $entry->ledger_name }}
                                                    </td>

                                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm tabular-nums text-gray-700">
                                                        {{ $debit !== null ? '₹'.number_format($debit, 2) : '—' }}
                                                    </td>

                                                    <td class="whitespace-nowrap px-6 py-3 text-right text-sm tabular-nums text-gray-700">
                                                        {{ $credit !== null ? '₹'.number_format($credit, 2) : '—' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="px-6 py-6 text-center text-sm text-gray-500">
                                                        No ledger entries imported.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                                                        {{-- MANUAL RECONCILIATION --}}
                            <div class="border-t border-gray-200 bg-indigo-50 px-6 py-5">
                                <div class="mb-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        Manual reconciliation
                                    </div>

                                    <p class="mt-1 text-sm leading-5 text-gray-600">
                                        Select this candidate only after confirming that it represents the same accounting transaction as the ERP voucher.
                                    </p>
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'finance.tally.reconciliation.manual-match',
                                        [
                                            'financeVoucher' => $financeVoucher,
                                            'tallyVoucher' => $candidate,
                                        ]
                                    ) }}"
                                    onsubmit="return confirm('Confirm manual reconciliation with this Tally voucher? This decision will be recorded in the audit trail.');"
                                >
                                    @csrf

                                    <div>
                                        <label
                                            for="notes-{{ $candidate->id }}"
                                            class="block text-xs font-semibold uppercase tracking-wide text-gray-600"
                                        >
                                            Reconciliation note
                                        </label>

                                        <textarea
                                            id="notes-{{ $candidate->id }}"
                                            name="notes"
                                            rows="2"
                                            maxlength="1000"
                                            placeholder="Optional reason or comment"
                                            class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >{{ old('notes') }}</textarea>
                                    </div>

                                    <div class="mt-4 flex items-center justify-between gap-4">
                                        <div class="text-xs text-gray-500">
                                            Your user account and reconciliation time will be recorded.
                                        </div>

                                        <button
                                            type="submit"
                                            class="inline-flex shrink-0 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        >
                                            Match this voucher
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="xl:col-span-2 rounded-xl border border-gray-200 bg-white px-6 py-10 text-center shadow">
                            <div class="text-sm font-semibold text-gray-700">
                                No Tally candidates found
                            </div>

                            <div class="mt-1 text-sm text-gray-500">
                                This ERP voucher currently has no candidate satisfying the reconciliation rules.
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- SAFETY NOTE --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4">
                <div class="text-sm font-semibold text-gray-800">
    Reconciliation control
</div>

<div class="mt-1 text-sm leading-6 text-gray-600">
    Manual matching should only be used after confirming that the ERP and Tally vouchers represent the same accounting transaction.
    The selected match, user account, timestamp and reconciliation note are retained for audit purposes.
</div>
            </div>

        </div>
    </div>
</x-app-layout>
