
<x-app-layout>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12h6m-6 4h6M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>
                        </svg>
                    </span>

                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-slate-900">
                            Tally Reconciliation
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Reconcile ERP Finance Vouchers and GL Journals
                            with imported TallyPrime transactions.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('finance.tally.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">

                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m15 18-6-6 6-6"/>
                    </svg>

                    Back to Tally Integration
                </a>
            </div>
        </div>
    </x-slot>


    @php
        /*
        |--------------------------------------------------------------------------
        | Dashboard Configuration
        |--------------------------------------------------------------------------
        | Presentation only. All reconciliation results are supplied
        | by the existing controller and reconciliation services.
        |--------------------------------------------------------------------------
        */

        $financeStats = [
            [
                'label' => 'Matched',
                'key' => 'matched',
                'description' => 'ERP and Tally linked',
                'color' => 'emerald',
                'text' => 'text-emerald-700',
                'border' => 'border-emerald-200',
                'background' => 'bg-emerald-50',
                'target' => 'matched-vouchers',
            ],
            [
                'label' => 'Tally Only',
                'key' => 'tally_only',
                'description' => 'No linked ERP voucher',
                'color' => 'amber',
                'text' => 'text-amber-700',
                'border' => 'border-amber-200',
                'background' => 'bg-amber-50',
                'target' => 'tally-only-vouchers',
            ],
            [
                'label' => 'ERP Only',
                'key' => 'erp_only',
                'description' => 'No Tally candidate',
                'color' => 'blue',
                'text' => 'text-blue-700',
                'border' => 'border-blue-200',
                'background' => 'bg-blue-50',
                'target' => 'erp-only-vouchers',
            ],
            [
                'label' => 'Ambiguous',
                'key' => 'ambiguous',
                'description' => 'Multiple candidates',
                'color' => 'violet',
                'text' => 'text-violet-700',
                'border' => 'border-violet-200',
                'background' => 'bg-violet-50',
                'target' => 'ambiguous-vouchers',
            ],
            [
                'label' => 'Unmapped',
                'key' => 'unmapped',
                'description' => 'Mapping review needed',
                'color' => 'slate',
                'text' => 'text-slate-700',
                'border' => 'border-slate-200',
                'background' => 'bg-slate-50',
                'target' => 'unmapped-vouchers',
            ],
            [
                'label' => 'Differences',
                'key' => 'difference',
                'description' => 'Accounting differences',
                'color' => 'rose',
                'text' => 'text-rose-700',
                'border' => 'border-rose-200',
                'background' => 'bg-rose-50',
                'target' => 'accounting-differences',
            ],
        ];

        $journalStats = [
            [
                'label' => 'Matched',
                'key' => 'matched',
                'text' => 'text-emerald-700',
                'border' => 'border-emerald-200',
                'background' => 'bg-emerald-50',
            ],
            [
                'label' => 'Difference',
                'key' => 'difference',
                'text' => 'text-rose-700',
                'border' => 'border-rose-200',
                'background' => 'bg-rose-50',
            ],
            [
                'label' => 'Missing',
                'key' => 'missing',
                'text' => 'text-amber-700',
                'border' => 'border-amber-200',
                'background' => 'bg-amber-50',
            ],
            [
                'label' => 'Ambiguous',
                'key' => 'ambiguous',
                'text' => 'text-violet-700',
                'border' => 'border-violet-200',
                'background' => 'bg-violet-50',
            ],
        ];

        $financeIssues =
            ($summary['difference'] ?? 0)
            + ($summary['erp_only'] ?? 0)
            + ($summary['ambiguous'] ?? 0)
            + ($summary['unmapped'] ?? 0);

        $journalIssues =
            ($journalSummary['difference'] ?? 0)
            + ($journalSummary['missing'] ?? 0)
            + ($journalSummary['ambiguous'] ?? 0);

        $journalExportCount = $journalReconciliations->count();

        $badgeClasses = [
            'matched' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'difference' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'missing' => 'bg-amber-100 text-amber-800 ring-amber-200',
            'ambiguous' => 'bg-violet-100 text-violet-800 ring-violet-200',
        ];
    @endphp


    <div class="py-6">

        <div class="mx-auto max-w-screen-2xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- ================================================= --}}
            {{-- COMPANY AND RECONCILIATION INFORMATION --}}
            {{-- ================================================= --}}

            <div class="grid gap-4 rounded-xl border border-blue-200 bg-blue-50/70 p-4 md:grid-cols-2 xl:grid-cols-3">

                <div class="flex items-start gap-3">
                    <span class="rounded-lg bg-white p-2 text-blue-700 shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M4 7c0-2 16-2 16 0s-16 2-16 0Zm0 0v5c0 2 16 2 16 0V7M4 12v5c0 2 16 2 16 0v-5"/>
                        </svg>
                    </span>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                            Reconciliation Source
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            Imported TallyPrime Vouchers
                        </p>
                        <p class="mt-1 text-xs text-slate-600">
                            Refresh Tally imports before reviewing recent exports.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="rounded-lg bg-white p-2 text-blue-700 shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 21h18M5 21V5l7-3 7 3v16M9 9h.01M15 9h.01M9 13h.01M15 13h.01M10 21v-4h4v4"/>
                        </svg>
                    </span>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                            Configured Tally Company
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ config('tally.company') ?: 'Not configured' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-600">
                            Read-only reconciliation against synchronized data.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <span class="rounded-lg bg-white p-2 text-blue-700 shadow-sm">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" stroke-width="1.8">
                            <circle cx="12" cy="12" r="9"/>
                            <path stroke-linecap="round" d="M12 10v6m0-9h.01"/>
                        </svg>
                    </span>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                            Matching Policy
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            Conservative Matching
                        </p>
                        <p class="mt-1 text-xs leading-5 text-slate-600">
                            Only uniquely identified transactions are considered
                            matched. Ambiguous results require manual review.
                        </p>
                    </div>
                </div>
            </div>


            {{-- ================================================= --}}
            {{-- QUICK NAVIGATION --}}
            {{-- ================================================= --}}

            <div class="flex flex-wrap items-center gap-2 text-xs">

                <span class="mr-1 font-semibold text-slate-500">
                    Jump to:
                </span>

                <a href="#finance-reconciliation"
                   class="rounded-full border border-slate-200 bg-white px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50">
                    Finance Vouchers
                </a>

                <a href="#accounting-differences"
                   class="rounded-full border border-rose-200 bg-white px-3 py-1.5 font-semibold text-rose-700 hover:bg-rose-50">
                    Accounting Differences
                </a>

                <a href="#journal-reconciliation"
                   class="rounded-full border border-indigo-200 bg-white px-3 py-1.5 font-semibold text-indigo-700 hover:bg-indigo-50">
                    GL Journals
                </a>

                <a href="#unmatched-transactions"
                   class="rounded-full border border-amber-200 bg-white px-3 py-1.5 font-semibold text-amber-700 hover:bg-amber-50">
                    Unmatched Transactions
                </a>
            </div>


            {{-- ================================================= --}}
            {{-- FINANCE VOUCHER RECONCILIATION --}}
            {{-- ================================================= --}}

            <section id="finance-reconciliation" class="space-y-5 scroll-mt-6">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8 3h9l4 4v14H8a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Zm3 9h6m-6 4h6"/>
                            </svg>
                        </span>

                        <div>
                            <h3 class="text-lg font-bold text-slate-900">
                                Finance Voucher Reconciliation
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Posted ERP receipts, payments, and transfers
                                compared with Tally vouchers.
                            </p>
                        </div>
                    </div>

                    <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600">
                        Existing Finance Voucher Matching
                    </span>
                </div>


                {{-- SIX SUMMARY CARDS --}}

                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">

                    @foreach ($financeStats as $stat)

                        <a href="#{{ $stat['target'] }}"
                           class="group rounded-xl border {{ $stat['border'] }} bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

                            <div class="mb-3 flex items-center justify-between">

                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                    {{ $stat['label'] }}
                                </span>

                                <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $stat['background'] }} {{ $stat['text'] }}">
                                    <svg class="h-4 w-4" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24"
                                         stroke-width="2">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" d="M12 8v4m0 4h.01"/>
                                    </svg>
                                </span>

                            </div>

                            <div class="text-3xl font-bold tabular-nums {{ $stat['text'] }}">
                                {{ $summary[$stat['key']] ?? 0 }}
                            </div>

                            <p class="mt-1 text-[11px] leading-4 text-slate-500">
                                {{ $stat['description'] }}
                            </p>

                        </a>

                    @endforeach

                </div>


                {{-- ============================================= --}}
                {{-- MATCHED VOUCHERS + INSIGHTS --}}
                {{-- ============================================= --}}

                <div class="grid gap-5 xl:grid-cols-12">

                    <div id="matched-vouchers"
                         class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-9">

                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">

                            <div class="flex items-center gap-3">

                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24" stroke-width="2">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="m8 12 3 3 5-6"/>
                                    </svg>
                                </span>

                                <div>
                                    <h4 class="text-sm font-bold text-slate-900">
                                        Matched Finance Vouchers
                                    </h4>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        ERP and Tally records successfully linked.
                                    </p>
                                </div>
                            </div>

                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                {{ $matched->count() }} Matched
                            </span>

                        </div>


                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-slate-100 text-sm">

                                <thead class="bg-slate-50">
                                    <tr>
                                        @foreach ([
                                            'Date',
                                            'ERP Voucher',
                                            'Tally Voucher',
                                            'Narration',
                                            'Amount',
                                            'Match Method',
                                            'Action'
                                        ] as $heading)

                                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                                {{ $heading }}
                                            </th>

                                        @endforeach
                                    </tr>
                                </thead>


                                <tbody class="divide-y divide-slate-100">

                                    @forelse ($matched as $voucher)

                                        @php
                                            $tallyAmount = round(
                                                $voucher->entries->sum(
                                                    fn ($entry) => abs((float) $entry->amount)
                                                ) / 2,
                                                2
                                            );

                                            $method = match ($voucher->match_method) {
                                                'exact_remote_id' => 'Exact ID',
                                                'secondary_match' => 'Secondary',
                                                'manual' => 'Manual',
                                                default => $voucher->match_method ?: '—',
                                            };
                                        @endphp

                                        <tr class="transition hover:bg-slate-50">

                                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                                {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="max-w-40 break-words text-xs font-semibold text-slate-900">
                                                    {{ $voucher->financeVoucher?->voucher_no ?: '—' }}
                                                </div>

                                                <div class="mt-1 text-[11px] text-slate-500">
                                                    {{ ucfirst($voucher->financeVoucher?->voucher_type ?: '') }}
                                                </div>
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="whitespace-nowrap text-xs font-semibold text-slate-800">
                                                    {{ $voucher->voucher_type ?: '—' }}
                                                    {{ $voucher->voucher_number ? '#'.$voucher->voucher_number : '' }}
                                                </div>

                                                <div class="mt-1 max-w-36 truncate text-[10px] text-slate-400"
                                                     title="{{ $voucher->guid }}">
                                                    {{ $voucher->guid ?: 'No GUID' }}
                                                </div>
                                            </td>

                                            <td class="max-w-52 px-4 py-3 text-xs leading-5 text-slate-600">
                                                <div class="line-clamp-2">
                                                    {{ $voucher->narration ?: '—' }}
                                                </div>
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold tabular-nums text-slate-900">
                                                ₹{{ number_format($tallyAmount, 2) }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-semibold text-emerald-800">
                                                    {{ $method }}
                                                </span>

                                                @if ($voucher->match_confidence !== null)
                                                    <div class="mt-1 whitespace-nowrap text-[10px] text-slate-500">
                                                        {{ number_format((float) $voucher->match_confidence, 0) }}% confidence
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-right">
                                                @if ($voucher->financeVoucher)
                                                    <a href="{{ route('finance.tally.reconciliation.erp', $voucher->financeVoucher) }}"
                                                       class="inline-flex rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                                                        Review
                                                    </a>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>

                                        </tr>

                                    @empty

                                        <tr>
                                            <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">
                                                No matched Finance Vouchers found.
                                            </td>
                                        </tr>

                                    @endforelse

                                </tbody>
                            </table>
                        </div>
                    </div>


                    {{-- ========================================= --}}
                    {{-- RECONCILIATION INSIGHTS --}}
                    {{-- ========================================= --}}

                    <aside class="rounded-xl border border-blue-100 bg-gradient-to-b from-blue-50 to-white p-5 shadow-sm xl:col-span-3">

                        <div class="mb-5">
                            <h4 class="text-sm font-bold text-slate-900">
                                Reconciliation Insights
                            </h4>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Current matching results and items requiring review.
                            </p>
                        </div>

                        <div class="space-y-3 text-xs">

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-600">Matched</span>
                                <span class="font-bold text-emerald-700">
                                    {{ $summary['matched'] ?? 0 }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-600">Tally Only</span>
                                <span class="font-bold text-amber-700">
                                    {{ $summary['tally_only'] ?? 0 }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-600">ERP Only</span>
                                <span class="font-bold text-blue-700">
                                    {{ $summary['erp_only'] ?? 0 }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-600">Accounting Differences</span>
                                <span class="font-bold text-rose-700">
                                    {{ $summary['difference'] ?? 0 }}
                                </span>
                            </div>

                        </div>

                        <div class="my-5 border-t border-blue-100"></div>

                        <h5 class="text-xs font-bold uppercase tracking-wide text-slate-800">
                            Priority Review
                        </h5>

                        <div class="mt-3 space-y-3">

                            @if (($summary['difference'] ?? 0) > 0)
                                <a href="#accounting-differences"
                                   class="block rounded-lg border border-rose-100 bg-rose-50 p-3 text-xs text-rose-800 hover:bg-rose-100">
                                    <strong>{{ $summary['difference'] }}</strong>
                                    accounting difference(s) require investigation.
                                </a>
                            @endif

                            @if (($summary['erp_only'] ?? 0) > 0)
                                <a href="#erp-only-vouchers"
                                   class="block rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-800 hover:bg-blue-100">
                                    <strong>{{ $summary['erp_only'] }}</strong>
                                    ERP-only voucher(s) have no Tally candidate.
                                </a>
                            @endif

                            @if (($summary['unmapped'] ?? 0) > 0)
                                <a href="#unmapped-vouchers"
                                   class="block rounded-lg border border-violet-100 bg-violet-50 p-3 text-xs text-violet-800 hover:bg-violet-100">
                                    <strong>{{ $summary['unmapped'] }}</strong>
                                    voucher(s) need mapping review.
                                </a>
                            @endif

                            @if ($financeIssues === 0)
                                <div class="rounded-lg bg-emerald-50 p-3 text-xs text-emerald-800">
                                    No ERP-side reconciliation exceptions currently identified.
                                </div>
                            @endif

                        </div>

                        <div class="mt-5 rounded-lg border border-slate-200 bg-white p-3">
                            <p class="text-[11px] font-semibold text-slate-800">
                                Important
                            </p>
                            <p class="mt-1 text-[11px] leading-5 text-slate-500">
                                Tally-only records may include GL Journals or earlier
                                test transactions. They are not automatically missing
                                Finance Voucher records.
                            </p>
                        </div>

                    </aside>
                </div>


                {{-- ============================================= --}}
                {{-- ACCOUNTING DIFFERENCES --}}
                {{-- ============================================= --}}

                <div id="accounting-differences"
                     class="scroll-mt-6 overflow-hidden rounded-xl border border-rose-200 bg-white shadow-sm">

                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-rose-100 bg-rose-50/70 px-5 py-4">

                        <div>
                            <h4 class="text-sm font-bold text-rose-900">
                                Accounting Differences
                            </h4>

                            <p class="mt-1 text-xs text-rose-700">
                                Transactions linked between ERP and Tally but containing
                                differences in accounting details.
                            </p>
                        </div>

                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-200">
                            {{ $differences->count() }} Difference(s)
                        </span>
                    </div>


                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-slate-100 text-sm">

                            <thead class="bg-slate-50">
                                <tr>
                                    @foreach ([
                                        'Date',
                                        'ERP Voucher',
                                        'Tally Voucher',
                                        'ERP Amount',
                                        'Tally Amount',
                                        'Discrepancy',
                                        'Action'
                                    ] as $heading)

                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            {{ $heading }}
                                        </th>

                                    @endforeach
                                </tr>
                            </thead>


                            <tbody class="divide-y divide-slate-100">

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

                                    <tr class="hover:bg-rose-50/30">

                                        <td class="whitespace-nowrap px-4 py-4 text-xs text-slate-600">
                                            {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                        </td>

                                        <td class="px-4 py-4">
                                            @if ($erpVoucher)
                                                <a href="{{ route('finance.tally.reconciliation.erp', $erpVoucher) }}"
                                                   class="text-xs font-bold text-indigo-700 hover:underline">
                                                    {{ $erpVoucher->voucher_no }}
                                                </a>
                                                <div class="mt-1 text-[11px] text-slate-500">
                                                    {{ ucfirst($erpVoucher->voucher_type) }}
                                                </div>
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="text-xs font-semibold text-slate-800">
                                                {{ $voucher->voucher_type ?: '—' }}
                                                {{ $voucher->voucher_number ? '#'.$voucher->voucher_number : '' }}
                                            </div>

                                            <div class="mt-1 max-w-36 truncate text-[10px] text-slate-400"
                                                 title="{{ $voucher->guid }}">
                                                {{ $voucher->guid ?: 'No GUID' }}
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-4 text-right text-xs font-semibold tabular-nums">
                                            {{ $erpVoucher ? '₹'.number_format((float) $erpVoucher->amount, 2) : '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-4 text-right text-xs font-semibold tabular-nums">
                                            ₹{{ number_format($tallyAmount, 2) }}
                                        </td>

                                        <td class="max-w-xs px-4 py-4">

                                            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs leading-5 text-rose-800">
                                                {{ $voucher->reconciliation_notes ?: 'Accounting details differ.' }}
                                            </div>

                                            <div class="mt-1 text-[10px] text-slate-500">
                                                Match:
                                                {{ $voucher->match_method === 'exact_remote_id'
                                                    ? 'Exact REMOTEID'
                                                    : ($voucher->match_method ?: '—') }}
                                            </div>

                                        </td>

                                        <td class="px-4 py-4 text-right">
                                            @if ($erpVoucher)
                                                <a href="{{ route('finance.tally.reconciliation.erp', $erpVoucher) }}"
                                                   class="inline-flex rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-50">
                                                    Review
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-sm text-emerald-700">
                                            No accounting differences detected.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>

            </section>


            {{-- ================================================= --}}
            {{-- GL JOURNAL RECONCILIATION --}}
            {{-- ================================================= --}}

            <section id="journal-reconciliation"
                     class="scroll-mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">

                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M4 5a2 2 0 0 1 2-2h13v17H6a2 2 0 0 0-2 2V5Zm0 12a2 2 0 0 1 2-2h13"/>
                            </svg>
                        </span>

                        <div>
                            <h3 class="text-lg font-bold text-slate-900">
                                GL Journal Reconciliation
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Read-only comparison of exported ERP journals
                                against imported TallyPrime Journal vouchers.
                            </p>
                        </div>
                    </div>

                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                        {{ $journalExportCount }} Export Record(s)
                    </span>

                </div>


                {{-- JOURNAL SUMMARY --}}

                <div class="grid grid-cols-2 gap-3 p-5 lg:grid-cols-4">

                    @foreach ($journalStats as $stat)

                        <div class="rounded-xl border {{ $stat['border'] }} {{ $stat['background'] }} p-4">

                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                {{ $stat['label'] }}
                            </div>

                            <div class="mt-2 text-3xl font-bold tabular-nums {{ $stat['text'] }}">
                                {{ $journalSummary[$stat['key']] ?? 0 }}
                            </div>

                        </div>

                    @endforeach

                </div>


                @if ($journalIssues > 0)
                    <div class="mx-5 mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-900">
                        <span class="font-bold">Attention:</span>
                        {{ $journalIssues }} journal reconciliation exception(s)
                        require review. A missing imported voucher does not necessarily
                        mean the Tally transmission failed; verify synchronization
                        and the recorded export status.
                    </div>
                @endif


                {{-- JOURNAL TABLE --}}

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-100 text-sm">

                        <thead class="bg-slate-50">
                            <tr>
                                @foreach ([
                                    'ERP Journal',
                                    'ERP Date',
                                    'Export Status',
                                    'Tally Voucher',
                                    'Reconciliation',
                                    'Details',
                                    'Action'
                                ] as $heading)

                                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        {{ $heading }}
                                    </th>

                                @endforeach
                            </tr>
                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @forelse ($journalReconciliations as $item)

                                @php
                                    $journal = $item['journal'];
                                    $result = $item['reconciliation'];

                                    $status = $result['status'];
                                    $tallyVoucher = $result['tally_voucher'];

                                    $statusClass = $badgeClasses[$status]
                                        ?? 'bg-slate-100 text-slate-700 ring-slate-200';

                                    $exportStatus = strtolower(
                                        (string) ($journal->tallyExport?->status ?? 'not exported')
                                    );

                                    $exportClass = match ($exportStatus) {
                                        'confirmed' => 'text-emerald-700',
                                        'unknown', 'failed' => 'text-rose-700',
                                        'sending' => 'text-amber-700',
                                        default => 'text-slate-600',
                                    };
                                @endphp

                                <tr class="transition hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="text-xs font-bold text-slate-900">
                                            {{ $journal->journal_no }}
                                        </div>

                                        <div class="mt-1 text-[10px] text-slate-400">
                                            ID {{ $journal->id }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                        {{ $journal->journal_date?->format('d M Y') ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3">

                                        <div class="flex items-center gap-2 text-xs font-semibold {{ $exportClass }}">

                                            <span class="h-2 w-2 rounded-full
                                                {{
                                                    $exportStatus === 'confirmed'
                                                        ? 'bg-emerald-500'
                                                        : (
                                                            in_array($exportStatus, ['failed', 'unknown'])
                                                                ? 'bg-rose-500'
                                                                : 'bg-slate-400'
                                                        )
                                                }}">
                                            </span>

                                            {{ ucfirst($exportStatus) }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-xs font-semibold text-slate-800">
                                        {{ $tallyVoucher?->voucher_number ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3">

                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $statusClass }}">
                                            {{ ucfirst($status) }}
                                        </span>

                                    </td>

                                    <td class="max-w-sm px-4 py-3 text-xs leading-5 text-slate-600">

                                        {{ $result['reason'] }}

                                        @if ($status === 'missing' && $exportStatus === 'unknown')
                                            <div class="mt-1 font-semibold text-rose-700">
                                                Check Tally rejection or transmission details.
                                            </div>
                                        @endif

                                    </td>

                                    <td class="px-4 py-3 text-right">

                                        <a href="{{ route('finance.tally.journals.view', $journal->id) }}"
                                           class="inline-flex rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-50">
                                            View
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">
                                        No exported GL Journals available for reconciliation.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>
                    </table>

                </div>


                <div class="border-t border-slate-100 bg-slate-50/70 px-5 py-3">

                    <p class="text-[11px] leading-5 text-slate-500">
                        Journal matching compares the exported journal identity,
                        accounting date, and normalized ledger debit/credit entries.
                        Results are computed from the imported Tally data without
                        modifying ERP or Tally records.
                    </p>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- UNMATCHED FINANCE VOUCHERS --}}
            {{-- ================================================= --}}

            <section id="unmatched-transactions" class="space-y-4 scroll-mt-6">

                <div>
                    <h3 class="text-lg font-bold text-slate-900">
                        Unmatched Transactions &amp; Review Queue
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Investigate transactions that cannot currently be linked
                        by the automatic Finance Voucher matching rules.
                    </p>
                </div>


                {{-- ============================================= --}}
                {{-- ERP ONLY + UNMAPPED --}}
                {{-- ============================================= --}}

                <div class="grid gap-5 xl:grid-cols-2">

                    {{-- ERP ONLY --}}

                    <div id="erp-only-vouchers"
                         class="overflow-hidden rounded-xl border border-blue-200 bg-white shadow-sm">

                        <div class="flex items-center justify-between border-b border-blue-100 bg-blue-50 px-5 py-4">

                            <div>
                                <h4 class="text-sm font-bold text-blue-900">
                                    ERP Only
                                </h4>
                                <p class="mt-1 text-xs text-blue-700">
                                    Posted ERP Finance Vouchers with no Tally candidate.
                                </p>
                            </div>

                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-blue-700">
                                {{ $erpOnly->count() }}
                            </span>
                        </div>


                        <div class="divide-y divide-slate-100">

                            @forelse ($erpOnly as $row)

                                @php
                                    $voucher = $row['voucher'];
                                @endphp

                                <div class="flex items-start justify-between gap-4 px-5 py-4">

                                    <div class="min-w-0">

                                        <a href="{{ route('finance.tally.reconciliation.erp', $voucher) }}"
                                           class="text-xs font-bold text-indigo-700 hover:underline">
                                            {{ $voucher->voucher_no }}
                                        </a>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                            · {{ ucfirst($voucher->voucher_type) }}
                                        </p>

                                        @if ($voucher->narration)
                                            <p class="mt-2 text-xs leading-5 text-slate-600">
                                                {{ $voucher->narration }}
                                            </p>
                                        @endif

                                        <a href="{{ route('finance.tally.reconciliation.erp', $voucher) }}"
                                           class="mt-2 inline-flex text-[11px] font-semibold text-indigo-700 hover:underline">
                                            Review voucher →
                                        </a>

                                    </div>

                                    <span class="whitespace-nowrap text-xs font-bold tabular-nums text-slate-900">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </span>

                                </div>

                            @empty

                                <div class="px-5 py-8 text-center text-xs text-emerald-700">
                                    No ERP-only Finance Vouchers.
                                </div>

                            @endforelse

                        </div>
                    </div>


                    {{-- UNMAPPED --}}

                    <div id="unmapped-vouchers"
                         class="overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm">

                        <div class="flex items-center justify-between border-b border-violet-100 bg-violet-50 px-5 py-4">

                            <div>
                                <h4 class="text-sm font-bold text-violet-900">
                                    Unmapped ERP Vouchers
                                </h4>
                                <p class="mt-1 text-xs text-violet-700">
                                    Missing mapping prevents automatic reconciliation.
                                </p>
                            </div>

                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-violet-700">
                                {{ $unmapped->count() }}
                            </span>
                        </div>


                        <div class="divide-y divide-slate-100">

                            @forelse ($unmapped as $row)

                                @php
                                    $voucher = $row['voucher'];
                                @endphp

                                <div class="flex items-start justify-between gap-4 px-5 py-4">

                                    <div class="min-w-0">

                                        <a href="{{ route('finance.tally.reconciliation.erp', $voucher) }}"
                                           class="text-xs font-bold text-indigo-700 hover:underline">
                                            {{ $voucher->voucher_no }}
                                        </a>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                            · {{ ucfirst($voucher->voucher_type) }}
                                        </p>

                                        <p class="mt-2 text-xs leading-5 text-violet-700">
                                            Head: {{ $voucher->financeHead?->name ?: '—' }}
                                            · Account: {{ $voucher->financeAccount?->name ?: '—' }}

                                            @if ($voucher->destinationAccount)
                                                · Destination: {{ $voucher->destinationAccount->name }}
                                            @endif
                                        </p>

                                        <a href="{{ route('finance.tally.reconciliation.erp', $voucher) }}"
                                           class="mt-2 inline-flex text-[11px] font-semibold text-indigo-700 hover:underline">
                                            Review mapping →
                                        </a>

                                    </div>

                                    <span class="whitespace-nowrap text-xs font-bold tabular-nums text-slate-900">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </span>

                                </div>

                            @empty

                                <div class="px-5 py-8 text-center text-xs text-emerald-700">
                                    No unmapped ERP Finance Vouchers.
                                </div>

                            @endforelse

                        </div>
                    </div>

                </div>


                {{-- ============================================= --}}
                {{-- AMBIGUOUS VOUCHERS --}}
                {{-- ============================================= --}}

                <details id="ambiguous-vouchers"
                         class="group overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm"
                         @if ($ambiguous->count() > 0) open @endif>

                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-violet-50 px-5 py-4 hover:bg-violet-100">

                        <div>
                            <h4 class="text-sm font-bold text-violet-900">
                                Ambiguous ERP Vouchers
                            </h4>

                            <p class="mt-1 text-xs text-violet-700">
                                Multiple Tally vouchers may match the same ERP transaction.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-violet-700">
                                {{ $ambiguous->count() }}
                            </span>

                            <svg class="h-4 w-4 text-violet-700 transition group-open:rotate-180"
                                 fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="m6 9 6 6 6-6"/>
                            </svg>
                        </div>

                    </summary>


                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">

                            <thead class="bg-slate-50">
                                <tr>
                                    @foreach ([
                                        'Date',
                                        'ERP Voucher',
                                        'Narration',
                                        'Amount',
                                        'Candidates',
                                        'Action'
                                    ] as $heading)

                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            {{ $heading }}
                                        </th>

                                    @endforeach
                                </tr>
                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @forelse ($ambiguous as $row)

                                    @php
                                        $voucher = $row['voucher'];
                                    @endphp

                                    <tr class="hover:bg-slate-50">

                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                            {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-xs font-semibold text-slate-900">
                                            {{ $voucher->voucher_no }}
                                        </td>

                                        <td class="max-w-xs px-4 py-3 text-xs text-slate-600">
                                            {{ $voucher->narration ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold tabular-nums">
                                            ₹{{ number_format((float) $voucher->amount, 2) }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-bold text-violet-700">
                                                {{ $row['candidate_count'] }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('finance.tally.reconciliation.erp', $voucher) }}"
                                               class="inline-flex rounded-lg border border-violet-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-violet-700 hover:bg-violet-50">
                                                Review
                                            </a>
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-xs text-slate-500">
                                            No ambiguous ERP Finance Vouchers.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>
                        </table>
                    </div>

                </details>


                {{-- ============================================= --}}
                {{-- TALLY ONLY --}}
                {{-- ============================================= --}}

                <details id="tally-only-vouchers"
                         class="group overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">

                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-amber-50 px-5 py-4 hover:bg-amber-100">

                        <div>
                            <h4 class="text-sm font-bold text-amber-900">
                                Tally-Only Vouchers
                            </h4>

                            <p class="mt-1 text-xs text-amber-700">
                                Imported vouchers not linked to ERP Finance Vouchers.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">

                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-amber-700">
                                {{ $tallyOnly->count() }}
                            </span>

                            <span class="text-xs text-amber-700">
                                Expand
                            </span>

                            <svg class="h-4 w-4 text-amber-700 transition group-open:rotate-180"
                                 fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="m6 9 6 6 6-6"/>
                            </svg>

                        </div>
                    </summary>


                    <div class="border-b border-amber-100 bg-amber-50/40 px-5 py-3">
                        <p class="text-xs leading-5 text-slate-600">
                            Some records may represent independently reconciled GL Journals,
                            historical testing entries, or valid Tally-originated transactions.
                            Review their origin before creating a Finance Voucher draft.
                        </p>
                    </div>


                    <div class="max-h-[650px] overflow-auto">

                        <table class="min-w-full divide-y divide-slate-100 text-sm">

                            <thead class="sticky top-0 z-10 bg-slate-50">

                                <tr>
                                    @foreach ([
                                        'Date',
                                        'Tally Voucher',
                                        'Narration',
                                        'Ledgers',
                                        'Amount',
                                        'Action'
                                    ] as $heading)

                                        <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            {{ $heading }}
                                        </th>

                                    @endforeach
                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @forelse ($tallyOnly as $voucher)

                                    @php
                                        $tallyAmount = round(
                                            $voucher->entries->sum(
                                                fn ($entry) => abs((float) $entry->amount)
                                            ) / 2,
                                            2
                                        );

                                        $canCreateDraft =
                                            $voucher->finance_voucher_id === null
                                            && in_array(
                                                strtolower(trim((string) $voucher->voucher_type)),
                                                ['receipt', 'payment', 'contra'],
                                                true
                                            );
                                    @endphp

                                    <tr class="hover:bg-slate-50">

                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                            {{ $voucher->voucher_date?->format('d M Y') ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="whitespace-nowrap text-xs font-semibold text-slate-900">
                                                {{ $voucher->voucher_type ?: '—' }}
                                                {{ $voucher->voucher_number ? '#'.$voucher->voucher_number : '' }}
                                            </div>

                                            <div class="mt-1 max-w-40 truncate text-[10px] text-slate-400"
                                                 title="{{ $voucher->guid }}">
                                                {{ $voucher->guid ?: 'No GUID' }}
                                            </div>
                                        </td>

                                        <td class="max-w-xs px-4 py-3 text-xs leading-5 text-slate-600">
                                            {{ $voucher->narration ?: '—' }}
                                        </td>

                                        <td class="px-4 py-3">

                                            <div class="max-w-52 space-y-1">

                                                @forelse ($voucher->entries as $entry)

                                                    <div class="text-[11px] text-slate-600">
                                                        {{ $entry->ledger_name }}
                                                    </div>

                                                @empty
                                                    <span class="text-slate-400">—</span>
                                                @endforelse

                                            </div>

                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right text-xs font-bold tabular-nums text-slate-900">
                                            ₹{{ number_format($tallyAmount, 2) }}
                                        </td>

                                        <td class="px-4 py-3 text-right">

                                            @if ($canCreateDraft)

                                                <a href="{{ route('finance.tally.import.review', $voucher) }}"
                                                   class="inline-flex whitespace-nowrap rounded-lg bg-indigo-600 px-3 py-2 text-[11px] font-semibold text-white hover:bg-indigo-700">
                                                    Review &amp; Create Draft
                                                </a>

                                            @else

                                                <span class="text-[11px] text-slate-400">
                                                    Manual review
                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-xs text-slate-500">
                                            No Tally-only vouchers.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>
                    </div>

                </details>

            </section>


            {{-- ================================================= --}}
            {{-- FOOTER / STATUS LEGEND --}}
            {{-- ================================================= --}}

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>
                        <h4 class="text-sm font-bold text-slate-900">
                            Reconciliation Status Guide
                        </h4>

                        <p class="mt-1 text-xs text-slate-500">
                            Definitions used throughout the Tally reconciliation dashboard.
                        </p>
                    </div>


                    <div class="flex flex-wrap gap-2 text-[11px]">

                        <span class="rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-800">
                            Matched — Records agree
                        </span>

                        <span class="rounded-full bg-rose-100 px-3 py-1 font-semibold text-rose-800">
                            Difference — Accounting mismatch
                        </span>

                        <span class="rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-800">
                            Missing — No imported match
                        </span>

                        <span class="rounded-full bg-violet-100 px-3 py-1 font-semibold text-violet-800">
                            Ambiguous — Multiple candidates
                        </span>

                        <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">
                            Unmapped — Mapping incomplete
                        </span>

                    </div>
                </div>


                <div class="mt-4 border-t border-slate-100 pt-4 text-[11px] leading-5 text-slate-500">

                    <strong class="text-slate-700">Accounting control:</strong>
                    This page provides reconciliation diagnostics only.
                    It does not post new journal entries, transmit vouchers to TallyPrime,
                    alter export records, or automatically resolve accounting differences.

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
