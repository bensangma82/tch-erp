<x-app-layout>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}

{{-- ========================================================= --}}
{{-- FINANCE DASHBOARD HEADER --}}
{{-- ========================================================= --}}

<div class="mb-6 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

    <div class="flex items-center gap-4">

        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-blue-100 bg-blue-50 text-blue-700 shadow-sm">
            <svg class="h-7 w-7" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 20V4m0 16h16"/>
                <path d="M8 16v-5m5 5V7m5 9v-8"/>
            </svg>
        </div>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Finance Dashboard
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Financial operations, liquidity, revenue performance,
                and accounting reconciliation.
            </p>

            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    ERP Financial Overview
                </span>

                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                    {{ now()->format('d F Y') }}
                </span>
            </div>
        </div>

    </div>

    <div class="flex flex-wrap gap-2">

        <a href="{{ route('finance.vouchers.index') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 3h8l4 4v14H8a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Z"/>
                <path d="M9 12h6m-6 4h6"/>
            </svg>
            Finance Vouchers
        </a>

        <a href="{{ route('finance.master.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            Finance Master
        </a>

        <a href="{{ route('finance.tally.reconciliation') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
            Tally Reconciliation
            <span>→</span>
        </a>

    </div>

</div>

        {{-- EXECUTIVE SNAPSHOT --}}

{{-- ========================================================= --}}
{{-- EXECUTIVE FINANCIAL OVERVIEW --}}
{{-- ========================================================= --}}

<section class="mb-7 space-y-4">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <h2 class="text-base font-bold text-slate-900">
                Executive Financial Overview
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Cash movement, collections, receivables and financial obligations.
            </p>
        </div>

        <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
            {{ now()->format('d M Y') }}
        </span>

    </div>


    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">


        {{-- CASH & BANK --}}

        <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm transition hover:shadow-md">

            <div class="flex items-start justify-between">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700">

                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 8h18v12H3zM3 8l3-4h12l3 4M7 14h4"/>
                        <circle cx="17" cy="14" r="2"/>
                    </svg>

                </div>

                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                    Liquidity
                </span>

            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Cash &amp; Bank
            </p>

            <p class="mt-2 break-words text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                ₹{{ number_format((float) $liquidity['cash_and_bank'], 2) }}
            </p>

            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                Current ERP cash and bank position
            </div>

        </div>


        {{-- TODAY'S RECEIPTS --}}

        <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm transition hover:shadow-md">

            <div class="flex items-start justify-between">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">

                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 3v12m-5-5 5 5 5-5M4 18v3h16v-3"/>
                    </svg>

                </div>

                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                    Inflow
                </span>

            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Today's Receipts
            </p>

            <p class="mt-2 break-words text-2xl font-bold tracking-tight text-emerald-700 tabular-nums">
                ₹{{ number_format((float) $todayReceipts, 2) }}
            </p>

            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                Total collections recorded today
            </div>

        </div>


        {{-- TODAY'S PAYMENTS --}}

        <div class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm transition hover:shadow-md">

            <div class="flex items-start justify-between">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-700">

                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 21V9m-5 5 5-5 5 5M4 6V3h16v3"/>
                    </svg>

                </div>

                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700">
                    Outflow
                </span>

            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Today's Payments
            </p>

            <p class="mt-2 break-words text-2xl font-bold tracking-tight text-slate-900 tabular-nums">
                ₹{{ number_format((float) $todayPayments, 2) }}
            </p>

            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                Cash and bank payments today
            </div>

        </div>


        {{-- MONTH NET CASH FLOW --}}

        <div class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm transition hover:shadow-md">

            <div class="flex items-start justify-between">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">

                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 17l6-6 4 4 8-9M16 6h5v5"/>
                    </svg>

                </div>

                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700">
                    This Month
                </span>

            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Month Net Cash Flow
            </p>

            <p class="mt-2 break-words text-2xl font-bold tracking-tight tabular-nums
                {{ $monthNet >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                {{ $monthNet < 0 ? '−' : '' }}₹{{ number_format(abs((float) $monthNet), 2) }}
            </p>

            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                Monthly receipts less payments
            </div>

        </div>


        {{-- MHIS RECEIVABLES --}}

        <div class="rounded-2xl border border-sky-200 bg-white p-5 shadow-sm transition hover:shadow-md">

            <div class="flex items-start justify-between">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-700">

                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <rect x="4" y="3" width="16" height="18" rx="2"/>
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 8h8M8 12h8M8 16h5"/>
                    </svg>

                </div>

                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">
                    Receivable
                </span>

            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                MHIS Receivables
            </p>

            <p class="mt-2 break-words text-2xl font-bold tracking-tight text-sky-700 tabular-nums">
                ₹{{ number_format((float) $liquidity['mhis_receivables'], 2) }}
            </p>

            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                Outstanding MHIS amounts recorded in ERP
            </div>

        </div>


        {{-- SUPPLIER PAYABLES --}}

        <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm transition hover:shadow-md">

            <div class="flex items-start justify-between">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-700">

                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 6h16v12H4zM8 3v6M16 3v6M8 13h8"/>
                    </svg>

                </div>

                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                    Payable
                </span>

            </div>

            <p class="mt-5 text-sm font-medium text-slate-500">
                Supplier Payables
            </p>

            <p class="mt-2 break-words text-2xl font-bold tracking-tight text-amber-700 tabular-nums">
                ₹{{ number_format((float) $liquidity['supplier_payables'], 2) }}
            </p>

            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                Outstanding supplier liabilities in ERP
            </div>

        </div>

    </div>

</section>

{{-- Tally Reconciliation Health --}}

{{-- ========================================================= --}}
{{-- TALLY RECONCILIATION HEALTH --}}
{{-- ========================================================= --}}

@php
    $reconciliationMatched = (int) ($tallyReconciliation['matched'] ?? 0);

    $reconciliationItems = [
        [
            'label' => 'Tally Only',
            'value' => (int) ($tallyReconciliation['tally_only'] ?? 0),
            'description' => 'No linked ERP voucher',
            'color' => 'amber',
        ],
        [
            'label' => 'ERP Only',
            'value' => (int) ($tallyReconciliation['erp_only'] ?? 0),
            'description' => 'No matching Tally voucher',
            'color' => 'amber',
        ],
        [
            'label' => 'Ambiguous',
            'value' => (int) ($tallyReconciliation['ambiguous'] ?? 0),
            'description' => 'Multiple possible matches',
            'color' => 'orange',
        ],
        [
            'label' => 'Unmapped',
            'value' => (int) ($tallyReconciliation['unmapped'] ?? 0),
            'description' => 'Account mapping required',
            'color' => 'violet',
        ],
        [
            'label' => 'Differences',
            'value' => (int) ($tallyReconciliation['difference'] ?? 0),
            'description' => 'Accounting differences',
            'color' => 'rose',
        ],
    ];

    $reconciliationExceptions = collect($reconciliationItems)
        ->sum('value');

    $reconciliationTotal = $reconciliationMatched
        + $reconciliationExceptions;

    $reconciliationPercentage = $reconciliationTotal > 0
        ? ($reconciliationMatched / $reconciliationTotal) * 100
        : null;
@endphp

<section class="mb-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

    {{-- HEADER --}}

    <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

        <div class="flex items-center gap-3">

            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">

                <svg class="h-6 w-6"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M4 7h14m-4-4 4 4-4 4M20 17H6m4-4-4 4 4 4"/>

                </svg>

            </div>

            <div>

                <h2 class="text-base font-bold text-slate-900">
                    Tally Reconciliation Health
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Finance Voucher reconciliation between TCH ERP and imported Tally records.
                </p>

            </div>

        </div>

        <a href="{{ route('finance.tally.reconciliation') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-700 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-indigo-800">

            Open Reconciliation
            <span>→</span>

        </a>

    </div>


    {{-- SUMMARY --}}

    <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-3">

        {{-- MATCHED --}}

        <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-5">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-emerald-800">
                    Matched
                </span>

                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
                    Reconciled
                </span>

            </div>

            <div class="mt-3 text-3xl font-bold tabular-nums text-emerald-700">
                {{ number_format($reconciliationMatched) }}
            </div>

            <p class="mt-2 text-xs text-emerald-800">
                Transactions linked between ERP and Tally
            </p>

        </div>


        {{-- EXCEPTIONS --}}

        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-5">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-amber-800">
                    Unresolved Categories
                </span>

                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                    Review
                </span>

            </div>

            <div class="mt-3 text-3xl font-bold tabular-nums text-amber-700">
                {{ number_format($reconciliationExceptions) }}
            </div>

            <p class="mt-2 text-xs text-amber-800">
                Sum of outstanding category counts
            </p>

        </div>


        {{-- COVERAGE --}}

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

            <span class="text-xs font-bold uppercase tracking-wide text-slate-600">
                Indicative Matching Coverage
            </span>

            <div class="mt-3 text-3xl font-bold tabular-nums text-slate-900">

                @if($reconciliationPercentage !== null)
                    {{ number_format($reconciliationPercentage, 1) }}%
                @else
                    —
                @endif

            </div>

            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">

                <div class="h-full rounded-full bg-emerald-500"
                     style="width: {{ min(100, max(0, $reconciliationPercentage ?? 0)) }}%">
                </div>

            </div>

            <p class="mt-2 text-xs text-slate-500">
                Matched count relative to displayed categories; not a formal reconciliation rate.
            </p>

        </div>

    </div>


    {{-- CATEGORY BREAKDOWN --}}

    <div class="border-t border-slate-100 px-5 pb-5 pt-4">

        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">

            <h3 class="text-sm font-bold text-slate-900">
                Reconciliation Exceptions
            </h3>

            <span class="text-xs text-slate-500">
                Investigate outstanding items in the reconciliation workspace.
            </span>

        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">

            @foreach($reconciliationItems as $item)

                <div class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:shadow-sm">

                    <div class="flex items-center justify-between gap-2">

                        <p class="text-xs font-semibold text-slate-600">
                            {{ $item['label'] }}
                        </p>

                        @if($item['value'] > 0)

                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>

                        @else

                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>

                        @endif

                    </div>

                    <p class="mt-3 text-2xl font-bold tabular-nums
                        {{ $item['value'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ number_format($item['value']) }}
                    </p>

                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        {{ $item['description'] }}
                    </p>

                </div>

            @endforeach

        </div>

    </div>


    {{-- FOOTNOTE --}}

    <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">

        <p class="text-xs leading-5 text-slate-500">
            This overview uses the existing Finance Dashboard reconciliation counts.
            GL Journal reconciliation is reviewed separately in the detailed
            Tally Reconciliation workspace. Category totals are indicative and
            should not be interpreted as unique transaction counts without validation.
        </p>

    </div>

</section>

{{-- FINANCIAL HEALTH SUMMARY --}}

{{-- ========================================================= --}}
{{-- FINANCIAL HEALTH & RISK ASSESSMENT --}}
{{-- ========================================================= --}}

@php
    $healthStatus = $financialRisk['status'] ?? 'unknown';

    $healthLabel = $financialRisk['label']
        ?? 'Financial status unavailable';

    $healthPeriod = $financialRisk['period'] ?? '';

    $healthStyles = match ($healthStatus) {
        'critical' => [
            'border' => 'border-red-200',
            'background' => 'bg-red-50',
            'text' => 'text-red-700',
            'badge' => 'bg-red-100 text-red-700',
            'dot' => 'bg-red-500',
        ],
        'warning' => [
            'border' => 'border-amber-200',
            'background' => 'bg-amber-50',
            'text' => 'text-amber-700',
            'badge' => 'bg-amber-100 text-amber-700',
            'dot' => 'bg-amber-500',
        ],
        'stable' => [
            'border' => 'border-emerald-200',
            'background' => 'bg-emerald-50',
            'text' => 'text-emerald-700',
            'badge' => 'bg-emerald-100 text-emerald-700',
            'dot' => 'bg-emerald-500',
        ],
        default => [
            'border' => 'border-slate-200',
            'background' => 'bg-slate-50',
            'text' => 'text-slate-700',
            'badge' => 'bg-slate-200 text-slate-700',
            'dot' => 'bg-slate-400',
        ],
    };

    $currentRatio = $liquidity['current_ratio'] ?? null;
    $quickRatio = $liquidity['quick_ratio'] ?? null;

    $breakEvenReady = (bool) ($breakEven['ready'] ?? false);

    $riskCriticalIssues = $financialRisk['critical_issues'] ?? [];
    $riskWarnings = $financialRisk['warnings'] ?? [];
@endphp

<section class="mb-7 space-y-5">

    {{-- SECTION HEADER --}}

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

        <div>

            <h2 class="text-lg font-bold text-slate-900">
                Financial Health &amp; Risk Assessment
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Liquidity, working-capital coverage, break-even
                performance and management risk indicators.
            </p>

        </div>

        @if($healthPeriod)
            <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
                {{ $healthPeriod }}
            </span>
        @endif

    </div>


    {{-- ===================================================== --}}
    {{-- FOUR FINANCIAL INDICATORS --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- CURRENT RATIO --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between gap-3">

                <div>

                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                        Current Ratio
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Short-term liability coverage
                    </p>

                </div>

                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xs font-bold text-blue-700">
                    CR
                </span>

            </div>

            <div class="mt-5 text-3xl font-bold tabular-nums text-slate-900">

                @if($currentRatio !== null)
                    {{ number_format((float) $currentRatio, 2) }}
                    <span class="text-base font-medium text-slate-400">x</span>
                @else
                    <span class="text-2xl text-slate-400">N/A</span>
                @endif

            </div>

            <div class="mt-5 space-y-2 border-t border-slate-100 pt-4">

                <div class="flex items-center justify-between gap-2 text-xs">

                    <span class="text-slate-500">
                        Current Assets
                    </span>

                    <span class="font-semibold tabular-nums text-slate-800">
                        ₹{{ number_format((float) ($liquidity['current_assets'] ?? 0), 2) }}
                    </span>

                </div>

                <div class="flex items-center justify-between gap-2 text-xs">

                    <span class="text-slate-500">
                        Current Liabilities
                    </span>

                    <span class="font-semibold tabular-nums text-slate-800">
                        ₹{{ number_format((float) ($liquidity['current_liabilities'] ?? 0), 2) }}
                    </span>

                </div>

            </div>

            <p class="mt-4 text-xs leading-5 text-slate-500">
                Interpret after complete asset and liability balances are available.
            </p>

        </div>


        {{-- QUICK RATIO --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between gap-3">

                <div>

                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                        Quick Ratio
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Liquid asset coverage
                    </p>

                </div>

                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xs font-bold text-indigo-700">
                    QR
                </span>

            </div>

            <div class="mt-5 text-3xl font-bold tabular-nums text-slate-900">

                @if($quickRatio !== null)
                    {{ number_format((float) $quickRatio, 2) }}
                    <span class="text-base font-medium text-slate-400">x</span>
                @else
                    <span class="text-2xl text-slate-400">N/A</span>
                @endif

            </div>

            <div class="mt-5 space-y-2 border-t border-slate-100 pt-4">

                <div class="flex items-center justify-between gap-2 text-xs">

                    <span class="text-slate-500">
                        Quick Assets
                    </span>

                    <span class="font-semibold tabular-nums text-slate-800">
                        ₹{{ number_format((float) ($liquidity['quick_assets'] ?? 0), 2) }}
                    </span>

                </div>

                <div class="flex items-center justify-between gap-2 text-xs">

                    <span class="text-slate-500">
                        Cash &amp; Bank
                    </span>

                    <span class="font-semibold tabular-nums text-slate-800">
                        ₹{{ number_format((float) ($liquidity['cash_and_bank'] ?? 0), 2) }}
                    </span>

                </div>

            </div>

            <p class="mt-4 text-xs leading-5 text-slate-500">
                Pharmacy inventory is excluded from quick assets.
            </p>

        </div>


        {{-- BREAK-EVEN --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between gap-3">

                <div>

                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                        Break-even Position
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Monthly revenue threshold
                    </p>

                </div>

                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-xs font-bold text-emerald-700">
                    BE
                </span>

            </div>

            <div class="mt-5">

                @if($breakEvenReady)

                    <p class="break-words text-2xl font-bold tabular-nums text-slate-900">
                        ₹{{ number_format((float) $breakEven['break_even_revenue'], 2) }}
                    </p>

                @else

                    <p class="text-xl font-bold text-amber-700">
                        Data Required
                    </p>

                @endif

            </div>

            <div class="mt-5 space-y-2 border-t border-slate-100 pt-4">

                <div class="flex items-center justify-between gap-2 text-xs">

                    <span class="text-slate-500">
                        MTD Revenue
                    </span>

                    <span class="font-semibold tabular-nums text-slate-800">
                        ₹{{ number_format((float) ($breakEven['revenue'] ?? 0), 2) }}
                    </span>

                </div>

                <div class="flex items-center justify-between gap-2 text-xs">

                    <span class="text-slate-500">
                        Operating Expenses
                    </span>

                    <span class="font-semibold tabular-nums text-slate-800">
                        ₹{{ number_format((float) ($breakEven['total_expenses'] ?? 0), 2) }}
                    </span>

                </div>

            </div>

            @if($breakEvenReady)

                <p class="mt-4 text-xs text-slate-600">
                    Margin of safety:
                    <span class="font-bold text-slate-800">
                        {{ number_format((float) ($breakEven['margin_of_safety_percentage'] ?? 0), 2) }}%
                    </span>
                </p>

            @else

                <p class="mt-4 text-xs leading-5 text-amber-700">

                    @if(($breakEven['status'] ?? '') === 'insufficient_expense_data')
                        No operating expenses have been posted this month.
                    @elseif(($breakEven['status'] ?? '') === 'configuration_required')
                        Expense classification requires completion.
                    @elseif(($breakEven['status'] ?? '') === 'insufficient_revenue_data')
                        Revenue data is not yet sufficient.
                    @else
                        Break-even cannot currently be calculated.
                    @endif

                </p>

            @endif

        </div>


        {{-- MTD NET CASH FLOW --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between gap-3">

                <div>

                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                        MTD Net Cash Flow
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Month-to-date cash movement
                    </p>

                </div>

                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700">

                    <svg class="h-5 w-5" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24"
                         stroke-width="1.8">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M3 17l6-6 4 4 8-9M16 6h5v5"/>

                    </svg>

                </span>

            </div>

            <p class="mt-5 break-words text-2xl font-bold tabular-nums
                {{ $monthNet >= 0 ? 'text-emerald-700' : 'text-red-700' }}">

                {{ $monthNet < 0 ? '−' : '' }}₹{{ number_format(abs((float) $monthNet), 2) }}

            </p>

            <div class="mt-5 space-y-2 border-t border-slate-100 pt-4">

                <div class="flex items-center justify-between gap-2 text-xs">

                    <span class="text-slate-500">
                        Monthly Receipts
                    </span>

                    <span class="font-semibold tabular-nums text-slate-800">
                        ₹{{ number_format((float) $monthReceipts, 2) }}
                    </span>

                </div>

                <div class="flex items-center justify-between gap-2 text-xs">

                    <span class="text-slate-500">
                        Monthly Payments
                    </span>

                    <span class="font-semibold tabular-nums text-slate-800">
                        ₹{{ number_format((float) $monthPayments, 2) }}
                    </span>

                </div>

            </div>

            <p class="mt-4 text-xs leading-5 text-slate-500">
                Cash flow is distinct from accrual-based operating surplus.
            </p>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- FINANCIAL RISK PANEL --}}
    {{-- ===================================================== --}}

    <div class="overflow-hidden rounded-2xl border {{ $healthStyles['border'] }} bg-white shadow-sm">

        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">

            <div class="flex items-start gap-3">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $healthStyles['background'] }} {{ $healthStyles['text'] }}">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         stroke-width="1.8"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <path d="M12 3 2.5 20h19L12 3Z"/>
                        <path d="M12 9v5m0 3h.01"/>

                    </svg>

                </div>

                <div>

                    <h3 class="text-base font-bold text-slate-900">
                        Financial Risk Assessment
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Management risk classification generated from available ERP financial data.
                    </p>

                </div>

            </div>

            <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold {{ $healthStyles['badge'] }}">

                <span class="h-2 w-2 rounded-full {{ $healthStyles['dot'] }}"></span>

                {{ strtoupper(str_replace('_', ' ', $healthStatus)) }}

            </span>

        </div>


        <div class="p-6">

            <div class="rounded-xl border {{ $healthStyles['border'] }} {{ $healthStyles['background'] }} p-4">

                <p class="text-xs font-bold uppercase tracking-wide {{ $healthStyles['text'] }}">
                    Current Financial Assessment
                </p>

                <p class="mt-2 text-lg font-bold {{ $healthStyles['text'] }}">
                    {{ $healthLabel }}
                </p>

                @if($healthPeriod)

                    <p class="mt-2 text-xs text-slate-500">
                        Reporting period: {{ $healthPeriod }}
                    </p>

                @endif

            </div>


            @if(!empty($riskCriticalIssues) || !empty($riskWarnings))

                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">

                    {{-- CRITICAL ISSUES --}}

                    <div class="rounded-xl border border-red-100 bg-red-50/60 p-4">

                        <div class="flex items-center justify-between gap-3">

                            <h4 class="text-sm font-bold text-red-800">
                                Critical Issues
                            </h4>

                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700">
                                {{ count($riskCriticalIssues) }}
                            </span>

                        </div>

                        @if(!empty($riskCriticalIssues))

                            <div class="mt-3 space-y-2">

                                @foreach($riskCriticalIssues as $issue)

                                    <div class="rounded-lg border border-red-100 bg-white px-3 py-3 text-xs leading-5 text-red-800">
                                        {{ $issue }}
                                    </div>

                                @endforeach

                            </div>

                        @else

                            <p class="mt-3 text-xs leading-5 text-red-700">
                                No critical issues have been reported.
                            </p>

                        @endif

                    </div>


                    {{-- WARNINGS --}}

                    <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">

                        <div class="flex items-center justify-between gap-3">

                            <h4 class="text-sm font-bold text-amber-800">
                                Management Warnings
                            </h4>

                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">
                                {{ count($riskWarnings) }}
                            </span>

                        </div>

                        @if(!empty($riskWarnings))

                            <div class="mt-3 space-y-2">

                                @foreach($riskWarnings as $warning)

                                    <div class="rounded-lg border border-amber-100 bg-white px-3 py-3 text-xs leading-5 text-amber-800">
                                        {{ $warning }}
                                    </div>

                                @endforeach

                            </div>

                        @else

                            <p class="mt-3 text-xs leading-5 text-amber-700">
                                No management warnings have been reported.
                            </p>

                        @endif

                    </div>

                </div>

            @else

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">

                    <p class="text-sm font-semibold text-slate-700">
                        No financial warning threshold is currently triggered.
                    </p>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        This assessment reflects only the data and rules currently
                        available in the ERP. It does not establish that all
                        financial statements are complete or reconciled.
                    </p>

                </div>

            @endif

        </div>


        <div class="border-t border-slate-100 bg-slate-50 px-6 py-3">

            <p class="text-xs leading-5 text-slate-500">
                Financial indicators are provisional management measures.
                Verify ledger completeness, opening balances, classification,
                and reconciliation before using them for formal financial decisions.
            </p>

        </div>

    </div>

</section>


        {{-- Financial Performance Charts --}}

{{-- ========================================================= --}}
{{-- FINANCIAL PERFORMANCE CHARTS --}}
{{-- ========================================================= --}}

<section class="mt-7 space-y-4">

    {{-- SECTION HEADER --}}

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <h2 class="text-lg font-bold text-slate-900">
                Financial Performance
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Revenue, operating expenditure and break-even performance
                for the current reporting period.
            </p>
        </div>

        <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
            {{ $dashboardCharts['period'] }}
        </span>

    </div>


    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

        {{-- ================================================= --}}
        {{-- REVENUE VS OPERATING EXPENSES --}}
        {{-- ================================================= --}}

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-start gap-3 border-b border-slate-100 px-5 py-5">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         stroke-width="1.8"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <path d="M4 20V4M4 20h16M8 16v-5M13 16V7M18 16v-8"/>

                    </svg>

                </div>

                <div>
                    <h3 class="text-base font-bold text-slate-900">
                        Revenue vs Operating Expenses
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Compare recorded revenue and operating expenses
                        for {{ $dashboardCharts['period'] }}.
                    </p>
                </div>

            </div>


            <div class="p-5">

                <div class="grid grid-cols-2 gap-3">

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">

                        <p class="text-xs font-semibold text-emerald-800">
                            Recorded Revenue
                        </p>

                        <p class="mt-2 break-words text-xl font-bold tabular-nums text-emerald-700">
                            ₹{{ number_format((float) ($breakEven['revenue'] ?? 0), 2) }}
                        </p>

                    </div>

                    <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">

                        <p class="text-xs font-semibold text-amber-800">
                            Recorded Operating Expenses
                        </p>

                        <p class="mt-2 break-words text-xl font-bold tabular-nums text-amber-700">
                            ₹{{ number_format((float) ($breakEven['total_expenses'] ?? 0), 2) }}
                        </p>

                    </div>

                </div>


                <div class="mt-5 h-72">
                    <canvas id="revenueExpenseChart"></canvas>
                </div>

            </div>


            <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">

                <p class="text-xs leading-5 text-slate-500">
                    Based on financial data recorded in ERP.
                    Incomplete revenue or expense classification may affect
                    the interpretation of these figures.
                </p>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- BREAK-EVEN POSITION --}}
        {{-- ================================================= --}}

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-start gap-3 border-b border-slate-100 px-5 py-5">

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         stroke-width="1.8"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <path d="M4 20V4M4 20h16M7 15l5-5 3 3 5-7"/>

                    </svg>

                </div>

                <div>
                    <h3 class="text-base font-bold text-slate-900">
                        Break-even Position
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Actual revenue compared with the calculated
                        break-even revenue threshold.
                    </p>
                </div>

            </div>


            <div class="p-5">

                @if($dashboardCharts['break_even']['ready'])

                    <div class="grid grid-cols-2 gap-3">

                        <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">

                            <p class="text-xs font-semibold text-blue-800">
                                Actual Revenue
                            </p>

                            <p class="mt-2 break-words text-xl font-bold tabular-nums text-blue-700">
                                ₹{{ number_format((float) $breakEven['revenue'], 2) }}
                            </p>

                        </div>

                        <div class="rounded-xl border border-violet-100 bg-violet-50/60 p-4">

                            <p class="text-xs font-semibold text-violet-800">
                                Break-even Revenue
                            </p>

                            <p class="mt-2 break-words text-xl font-bold tabular-nums text-violet-700">
                                ₹{{ number_format((float) $breakEven['break_even_revenue'], 2) }}
                            </p>

                        </div>

                    </div>


                    <div class="mt-5 h-72">
                        <canvas id="breakEvenChart"></canvas>
                    </div>

                @else

                    <div class="flex min-h-80 items-center justify-center rounded-xl border border-amber-100 bg-amber-50/40 px-6 py-8">

                        <div class="max-w-sm text-center">

                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">

                                <svg class="h-7 w-7"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.8"
                                     stroke-linecap="round"
                                     stroke-linejoin="round">

                                    <path d="M12 3 2.5 20h19L12 3Z"/>
                                    <path d="M12 9v5m0 3h.01"/>

                                </svg>

                            </div>

                            <h4 class="mt-4 text-base font-bold text-slate-900">
                                Break-even Analysis Pending
                            </h4>

                            <p class="mt-2 text-sm leading-6 text-slate-600">

                                @if(($breakEven['status'] ?? '') === 'configuration_required')

                                    Expense classification must be completed
                                    before the break-even revenue target
                                    can be calculated.

                                @elseif(($breakEven['status'] ?? '') === 'insufficient_expense_data')

                                    Operating expenses must be posted
                                    before a valid break-even target
                                    can be calculated.

                                @elseif(($breakEven['status'] ?? '') === 'insufficient_revenue_data')

                                    Additional revenue data is required
                                    before break-even analysis is available.

                                @else

                                    The ERP does not currently have sufficient
                                    classified financial data to calculate
                                    a reliable break-even position.

                                @endif

                            </p>

                            <span class="mt-4 inline-flex rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800">
                                Data Required
                            </span>

                        </div>

                    </div>

                @endif

            </div>


            <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">

                <p class="text-xs leading-5 text-slate-500">
                    Break-even analysis requires adequate revenue,
                    expense classification, and contribution-margin
                    information.
                </p>

            </div>

        </div>

    </div>

</section>

        {{-- MONTHLY REVENUE BY SERVICE --}}

{{-- ========================================================= --}}
{{-- MONTHLY REVENUE BY SERVICE --}}
{{-- ========================================================= --}}

@php
    $monthlyRevenueTotal = (float) $billingMonthRevenueByHead->sum('amount');

    $revenueHeadsWithActivity = $billingMonthRevenueByHead
        ->filter(fn ($revenue) => abs((float) $revenue['amount']) > 0.0001)
        ->sortByDesc(fn ($revenue) => (float) $revenue['amount'])
        ->values();

    $zeroRevenueHeads = $billingMonthRevenueByHead
        ->filter(fn ($revenue) => abs((float) $revenue['amount']) <= 0.0001)
        ->values();

    $activeRevenueHeadCount = $revenueHeadsWithActivity->count();
    $totalRevenueHeadCount = $billingMonthRevenueByHead->count();

    $largestRevenueHead = $revenueHeadsWithActivity->first();

    $largestRevenueAmount = $largestRevenueHead
        ? (float) $largestRevenueHead['amount']
        : 0;

    $positiveRevenueTotal = (float) $revenueHeadsWithActivity
        ->filter(fn ($revenue) => (float) $revenue['amount'] > 0)
        ->sum('amount');
@endphp

<section
    class="mt-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    x-data="{ showZeroHeads: false }"
>

    {{-- SECTION HEADER --}}

    <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

        <div class="flex items-start gap-3">

            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">

                <svg class="h-6 w-6"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M4 20V4M4 20h16M8 16v-5M13 16V8M18 16v-9"/>

                </svg>

            </div>

            <div>

                <h3 class="text-base font-bold text-slate-900">
                    Monthly Revenue by Service
                </h3>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Monthly revenue collections grouped by Finance Head,
                    with service-wise contribution and detailed drill-down.
                </p>

            </div>

        </div>

        <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">
            {{ now()->format('F Y') }}
        </span>

    </div>


    {{-- ===================================================== --}}
    {{-- REVENUE SUMMARY --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-3">

        {{-- TOTAL REVENUE --}}

        <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">

            <p class="text-xs font-semibold text-indigo-700">
                Total Classified Revenue
            </p>

            <p class="mt-2 break-words text-2xl font-bold tabular-nums text-slate-900">
                ₹{{ number_format($monthlyRevenueTotal, 2) }}
            </p>

            <p class="mt-2 text-xs text-slate-500">
                Net total from monthly Finance Head collections
            </p>

        </div>


        {{-- ACTIVE REVENUE HEADS --}}

        <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">

            <p class="text-xs font-semibold text-emerald-700">
                Revenue Heads with Activity
            </p>

            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900">
                {{ number_format($activeRevenueHeadCount) }}
            </p>

            <p class="mt-2 text-xs text-slate-500">
                Of {{ number_format($totalRevenueHeadCount) }} configured revenue heads
            </p>

        </div>


        {{-- LARGEST CONTRIBUTOR --}}

        <div class="rounded-xl border border-sky-100 bg-sky-50/50 p-4">

            <p class="text-xs font-semibold text-sky-700">
                Largest Revenue Contributor
            </p>

            @if($largestRevenueHead)

                <p class="mt-2 truncate text-base font-bold text-slate-900"
                   title="{{ $largestRevenueHead['name'] }}">
                    {{ $largestRevenueHead['name'] }}
                </p>

                <p class="mt-1 text-lg font-bold tabular-nums text-sky-700">
                    ₹{{ number_format($largestRevenueAmount, 2) }}
                </p>

            @else

                <p class="mt-2 text-lg font-bold text-slate-500">
                    No activity
                </p>

            @endif

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- REVENUE HEAD BREAKDOWN --}}
    {{-- ===================================================== --}}

    <div class="border-t border-slate-100">

        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">

            <div>

                <h4 class="text-sm font-bold text-slate-900">
                    Service Revenue Breakdown
                </h4>

                <p class="mt-1 text-xs text-slate-500">
                    Revenue heads ordered by recorded contribution.
                </p>

            </div>

            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                {{ $activeRevenueHeadCount }} Active
            </span>

        </div>


        @if($revenueHeadsWithActivity->isEmpty())

            <div class="px-6 py-12 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         stroke-width="1.8">

                        <path d="M4 20V4M4 20h16M8 16v-5M13 16V8M18 16v-9"/>

                    </svg>

                </div>

                <h4 class="mt-4 text-sm font-bold text-slate-800">
                    No classified revenue activity
                </h4>

                <p class="mt-2 text-xs text-slate-500">
                    No non-zero Finance Head revenue collections
                    have been recorded for the current month.
                </p>

            </div>

        @else

            <div class="divide-y divide-slate-100">

                @foreach($revenueHeadsWithActivity as $revenue)

                    @php
                        $amount = (float) $revenue['amount'];

                        $percentage = $monthlyRevenueTotal > 0
                            ? ($amount / $monthlyRevenueTotal) * 100
                            : 0;

                        $barPercentage = $positiveRevenueTotal > 0
                            ? max(0, min(100, ($amount / $positiveRevenueTotal) * 100))
                            : 0;
                    @endphp

                    <div class="px-6 py-4 transition hover:bg-slate-50/70">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            {{-- REVENUE HEAD --}}

                            <div class="min-w-0 flex-1">

                                <div class="flex flex-wrap items-center gap-2">

                                    @if(!empty($revenue['id']))

                                        <a href="{{ route('finance.revenue.detail', $revenue['id']) }}"
                                           class="inline-flex items-center gap-1 text-sm font-bold text-indigo-700 hover:text-indigo-900 hover:underline">

                                            {{ $revenue['name'] }}
                                            <span aria-hidden="true">↗</span>

                                        </a>

                                    @else

                                        <span class="text-sm font-bold text-slate-900">
                                            {{ $revenue['name'] }}
                                        </span>

                                    @endif

                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                        {{ $revenue['code'] }}
                                    </span>

                                </div>

                                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        class="h-full rounded-full {{ $amount >= 0 ? 'bg-indigo-500' : 'bg-rose-500' }}"
                                        style="width: {{ $barPercentage }}%"
                                    ></div>

                                </div>

                                <p class="mt-2 text-xs text-slate-500">

                                    @if($monthlyRevenueTotal > 0)
                                        {{ number_format($percentage, 1) }}%
                                        of net classified monthly revenue
                                    @else
                                        Percentage unavailable for non-positive net revenue
                                    @endif

                                </p>

                            </div>


                            {{-- AMOUNT --}}

                            <div class="shrink-0 text-left sm:w-44 sm:text-right">

                                <p class="text-lg font-bold tabular-nums
                                    {{ $amount >= 0 ? 'text-slate-900' : 'text-rose-700' }}">

                                    {{ $amount < 0 ? '−' : '' }}₹{{ number_format(abs($amount), 2) }}

                                </p>

                                <p class="mt-1 text-[11px] text-slate-400">
                                    Recorded amount
                                </p>

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        @endif

    </div>


    {{-- ===================================================== --}}
    {{-- ZERO-REVENUE HEADS --}}
    {{-- ===================================================== --}}

    @if($zeroRevenueHeads->isNotEmpty())

        <div class="border-t border-slate-200 bg-slate-50/60">

            <button
                type="button"
                @click="showZeroHeads = !showZeroHeads"
                :aria-expanded="showZeroHeads.toString()"
                class="flex w-full items-center justify-between gap-3 px-6 py-4 text-left transition hover:bg-slate-100"
            >

                <div class="flex flex-wrap items-center gap-2">

                    <span class="text-sm font-bold text-slate-800">
                        Revenue Heads Without Activity
                    </span>

                    <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600">
                        {{ $zeroRevenueHeads->count() }}
                    </span>

                </div>

                <div class="flex items-center gap-2 text-xs font-semibold text-indigo-700">

                    <span x-text="showZeroHeads ? 'Hide' : 'Show'">
                        Show
                    </span>

                    <svg
                        class="h-4 w-4 transition-transform duration-200"
                        :class="{ 'rotate-180': showZeroHeads }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="m6 9 6 6 6-6"/>
                    </svg>

                </div>

            </button>


            <div
                x-show="showZeroHeads"
                x-collapse
                class="border-t border-slate-200 bg-white"
            >

                <div class="grid grid-cols-1 gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-3">

                    @foreach($zeroRevenueHeads as $revenue)

                        @if(!empty($revenue['id']))

                            <a href="{{ route('finance.revenue.detail', $revenue['id']) }}"
                               class="group flex items-center justify-between gap-3 bg-white px-5 py-4 transition hover:bg-indigo-50">

                                <div class="min-w-0">

                                    <p class="truncate text-sm font-semibold text-slate-700 group-hover:text-indigo-700">
                                        {{ $revenue['name'] }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $revenue['code'] }}
                                    </p>

                                </div>

                                <span class="shrink-0 text-sm font-bold text-slate-500">
                                    ₹0.00
                                </span>

                            </a>

                        @else

                            <div class="flex items-center justify-between gap-3 bg-white px-5 py-4">

                                <div class="min-w-0">

                                    <p class="truncate text-sm font-semibold text-slate-700">
                                        {{ $revenue['name'] }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $revenue['code'] }}
                                    </p>

                                </div>

                                <span class="shrink-0 text-sm font-bold text-slate-500">
                                    ₹0.00
                                </span>

                            </div>

                        @endif

                    @endforeach

                </div>

            </div>

        </div>

    @endif


    {{-- FOOTNOTE --}}

    <div class="border-t border-slate-100 bg-slate-50 px-6 py-3">

        <p class="text-xs leading-5 text-slate-500">
            This section displays monthly collections classified by Finance Head.
            It should not be interpreted as total accrual revenue or as a complete
            departmental income statement. Negative adjustments may affect
            contribution percentages.
        </p>

    </div>

</section>

        {{-- Liquidity Composition --}}

{{-- ========================================================= --}}
{{-- LIQUIDITY & WORKING CAPITAL --}}
{{-- ========================================================= --}}

@php
    $currentAssets = (float) $liquidity['current_assets'];
    $currentLiabilities = (float) $liquidity['current_liabilities'];

    $cashAndBank = (float) $liquidity['cash_and_bank'];
    $pharmacyInventory = (float) $liquidity['pharmacy_inventory'];
    $mhisReceivables = (float) $liquidity['mhis_receivables'];
    $supplierPayables = (float) $liquidity['supplier_payables'];

    $workingCapital = $currentAssets - $currentLiabilities;

    $netReceivablePosition = $mhisReceivables - $supplierPayables;

    $liquidityComponents = [
        [
            'name' => 'Cash & Bank',
            'amount' => $cashAndBank,
            'color' => 'bg-emerald-500',
            'description' => 'Recorded cash and bank balances',
        ],
        [
            'name' => 'Pharmacy Inventory',
            'amount' => $pharmacyInventory,
            'color' => 'bg-indigo-500',
            'description' => 'Inventory included in current assets',
        ],
        [
            'name' => 'MHIS Receivables',
            'amount' => $mhisReceivables,
            'color' => 'bg-sky-500',
            'description' => 'Recorded outstanding MHIS receivables',
        ],
    ];

    $compositionBase = max($currentAssets, 0);

    $classifiedAssets = collect($liquidityComponents)->sum('amount');

    $unclassifiedAssets = $currentAssets - $classifiedAssets;
@endphp

<section class="mt-8 space-y-5">

    {{-- SECTION HEADER --}}

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <h2 class="text-lg font-bold text-slate-900">
                Liquidity &amp; Working Capital
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Current asset composition, short-term obligations,
                and working-capital position.
            </p>
        </div>

        <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
            Current ERP balances
        </span>

    </div>


    {{-- ===================================================== --}}
    {{-- FINANCIAL POSITION SUMMARY --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- CURRENT ASSETS --}}

        <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Current Assets
                </span>

                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path d="M4 20V4m0 16h16M8 16v-5m5 5V8m5 8V6"/>
                    </svg>
                </span>

            </div>

            <p class="mt-5 break-words text-2xl font-bold tabular-nums text-slate-900">
                ₹{{ number_format($currentAssets, 2) }}
            </p>

            <p class="mt-3 text-xs leading-5 text-slate-500">
                Total current assets represented in ERP
            </p>

        </div>


        {{-- CURRENT LIABILITIES --}}

        <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Current Liabilities
                </span>

                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <rect x="4" y="4" width="16" height="16" rx="2"/>
                        <path d="M8 9h8M8 13h8M8 17h5"/>
                    </svg>
                </span>

            </div>

            <p class="mt-5 break-words text-2xl font-bold tabular-nums text-slate-900">
                ₹{{ number_format($currentLiabilities, 2) }}
            </p>

            <p class="mt-3 text-xs leading-5 text-slate-500">
                Short-term obligations recorded in ERP
            </p>

        </div>


        {{-- WORKING CAPITAL --}}

        <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Working Capital
                </span>

                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path d="M4 20V4m0 16h16M7 14l4-4 3 3 6-7"/>
                    </svg>
                </span>

            </div>

            <p class="mt-5 break-words text-2xl font-bold tabular-nums
                {{ $workingCapital >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                {{ $workingCapital < 0 ? '−' : '' }}₹{{ number_format(abs($workingCapital), 2) }}
            </p>

            <p class="mt-3 text-xs leading-5 text-slate-500">
                Current assets minus current liabilities
            </p>

        </div>


        {{-- NET RECEIVABLE POSITION --}}

        <div class="rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Net Receivable Position
                </span>

                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path d="M4 7h14m-4-4 4 4-4 4M20 17H6m4-4-4 4 4 4"/>
                    </svg>
                </span>

            </div>

            <p class="mt-5 break-words text-2xl font-bold tabular-nums
                {{ $netReceivablePosition >= 0 ? 'text-indigo-700' : 'text-red-700' }}">
                {{ $netReceivablePosition < 0 ? '−' : '' }}₹{{ number_format(abs($netReceivablePosition), 2) }}
            </p>

            <p class="mt-3 text-xs leading-5 text-slate-500">
                MHIS receivables less supplier payables
            </p>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- LIQUIDITY COMPOSITION & OBLIGATIONS --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- LIQUIDITY COMPOSITION --}}

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">

            <div class="border-b border-slate-100 px-6 py-5">

                <h3 class="text-base font-bold text-slate-900">
                    Liquidity Composition
                </h3>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Distribution of current assets currently recognized
                    in the ERP financial overview.
                </p>

            </div>


            <div class="space-y-6 p-6">

                @foreach($liquidityComponents as $component)

                    @php
                        $amount = (float) $component['amount'];

                        $percentage = $compositionBase > 0
                            ? ($amount / $compositionBase) * 100
                            : null;

                        $barWidth = $percentage !== null
                            ? min(100, max(0, $percentage))
                            : 0;
                    @endphp

                    <div>

                        <div class="flex flex-wrap items-center justify-between gap-3">

                            <div>

                                <p class="text-sm font-bold text-slate-800">
                                    {{ $component['name'] }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $component['description'] }}
                                </p>

                            </div>

                            <div class="text-right">

                                <p class="text-sm font-bold tabular-nums text-slate-900">
                                    ₹{{ number_format($amount, 2) }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-slate-500">

                                    @if($percentage !== null)
                                        {{ number_format($percentage, 1) }}%
                                    @else
                                        N/A
                                    @endif

                                </p>

                            </div>

                        </div>


                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100">

                            <div
                                class="h-full rounded-full {{ $component['color'] }}"
                                style="width: {{ $barWidth }}%"
                            ></div>

                        </div>

                    </div>

                @endforeach


                {{-- UNCLASSIFIED ASSETS --}}

                @if(abs($unclassifiedAssets) > 0.01)

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <div class="flex flex-wrap items-center justify-between gap-3">

                            <div>

                                <p class="text-sm font-bold text-slate-700">
                                    Other / Unclassified Current Assets
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Difference between reported current assets
                                    and the three components shown above.
                                </p>

                            </div>

                            <p class="text-sm font-bold tabular-nums text-slate-900">
                                {{ $unclassifiedAssets < 0 ? '−' : '' }}₹{{ number_format(abs($unclassifiedAssets), 2) }}
                            </p>

                        </div>

                    </div>

                @endif

            </div>


            <div class="border-t border-slate-100 bg-slate-50 px-6 py-3">

                <p class="text-xs leading-5 text-slate-500">
                    Component percentages are calculated against recorded current assets.
                    Figures depend on the completeness and classification
                    of current-asset balances in ERP.
                </p>

            </div>

        </div>


        {{-- SHORT-TERM OBLIGATIONS --}}

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-100 px-5 py-5">

                <h3 class="text-base font-bold text-slate-900">
                    Receivables &amp; Obligations
                </h3>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Key short-term amounts tracked in ERP.
                </p>

            </div>


            <div class="space-y-4 p-5">

                {{-- MHIS RECEIVABLES --}}

                <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4">

                    <p class="text-xs font-bold uppercase tracking-wide text-sky-700">
                        MHIS Receivables
                    </p>

                    <p class="mt-2 break-words text-xl font-bold tabular-nums text-sky-800">
                        ₹{{ number_format($mhisReceivables, 2) }}
                    </p>

                    <p class="mt-2 text-xs text-sky-700">
                        Recorded outstanding MHIS amounts
                    </p>

                </div>


                {{-- SUPPLIER PAYABLES --}}

                <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">

                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">
                        Supplier Payables
                    </p>

                    <p class="mt-2 break-words text-xl font-bold tabular-nums text-amber-800">
                        ₹{{ number_format($supplierPayables, 2) }}
                    </p>

                    <p class="mt-2 text-xs text-amber-700">
                        Recorded supplier liabilities
                    </p>

                </div>


                {{-- WORKING CAPITAL STATUS --}}

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                    <p class="text-xs font-bold uppercase tracking-wide text-slate-600">
                        Working Capital Calculation
                    </p>

                    <div class="mt-3 space-y-2 text-xs">

                        <div class="flex items-center justify-between gap-2">

                            <span class="text-slate-500">
                                Current Assets
                            </span>

                            <span class="font-semibold tabular-nums text-slate-800">
                                ₹{{ number_format($currentAssets, 2) }}
                            </span>

                        </div>

                        <div class="flex items-center justify-between gap-2">

                            <span class="text-slate-500">
                                Less Current Liabilities
                            </span>

                            <span class="font-semibold tabular-nums text-slate-800">
                                ₹{{ number_format($currentLiabilities, 2) }}
                            </span>

                        </div>

                        <div class="flex items-center justify-between gap-2 border-t border-slate-200 pt-2">

                            <span class="font-bold text-slate-700">
                                Net Working Capital
                            </span>

                            <span class="font-bold tabular-nums
                                {{ $workingCapital >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $workingCapital < 0 ? '−' : '' }}₹{{ number_format(abs($workingCapital), 2) }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- IMPORTANT NOTE --}}

    <div class="rounded-xl border border-amber-200 bg-amber-50/60 px-5 py-4">

        <p class="text-xs leading-6 text-amber-900">
            <strong>Financial data completeness:</strong>
            Working capital and liquidity indicators reflect only balances
            currently recorded in TCH ERP. A positive working-capital value
            does not establish adequate liquidity when liabilities,
            receivables, or opening balances are incomplete.
        </p>

    </div>

</section>


        {{-- Today's Summary --}}

{{-- ========================================================= --}}
{{-- DAILY & MONTHLY CASH FLOW OVERVIEW --}}
{{-- ========================================================= --}}

@php
    $dailyNetCashFlow = (float) $todayNet;
    $monthlyNetCashFlow = (float) $monthNet;
@endphp

<section class="mt-8 space-y-5">

    {{-- SECTION HEADER --}}

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <h2 class="text-lg font-bold text-slate-900">
                Cash Flow Overview
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Daily and monthly receipts, payments and net cash movements
                recorded in the Finance module.
            </p>
        </div>

        <span class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
            {{ now()->format('F Y') }}
        </span>

    </div>


    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">

        {{-- ================================================= --}}
        {{-- TODAY'S CASH FLOW --}}
        {{-- ================================================= --}}

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">

                <div class="flex items-center gap-3">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">

                        <svg class="h-6 w-6"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24"
                             stroke-width="1.8"
                             stroke-linecap="round"
                             stroke-linejoin="round">

                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                            <path d="M7 3v4m10-4v4M3 10h18"/>

                        </svg>

                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Today's Cash Flow
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ now()->format('d F Y') }}
                        </p>
                    </div>

                </div>

                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    Daily
                </span>

            </div>


            <div class="p-5">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                    {{-- RECEIPTS --}}

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">

                        <div class="flex items-center gap-2">

                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">

                                <svg class="h-4 w-4" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24"
                                     stroke-width="2">
                                    <path d="M12 3v15m-6-6 6 6 6-6"/>
                                </svg>

                            </span>

                            <p class="text-xs font-bold text-emerald-800">
                                Receipts
                            </p>

                        </div>

                        <p class="mt-4 break-words text-2xl font-bold tabular-nums text-emerald-700">
                            ₹{{ number_format((float) $todayReceipts, 2) }}
                        </p>

                        <p class="mt-2 text-xs text-emerald-800">
                            Recorded cash inflows
                        </p>

                    </div>


                    {{-- PAYMENTS --}}

                    <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-4">

                        <div class="flex items-center gap-2">

                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 text-rose-700">

                                <svg class="h-4 w-4" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24"
                                     stroke-width="2">
                                    <path d="M12 21V6m-6 6 6-6 6 6"/>
                                </svg>

                            </span>

                            <p class="text-xs font-bold text-rose-800">
                                Payments
                            </p>

                        </div>

                        <p class="mt-4 break-words text-2xl font-bold tabular-nums text-slate-900">
                            ₹{{ number_format((float) $todayPayments, 2) }}
                        </p>

                        <p class="mt-2 text-xs text-rose-800">
                            Recorded cash outflows
                        </p>

                    </div>

                </div>


                {{-- NET CASH FLOW --}}

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Net Daily Cash Flow
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                Today's receipts less payments
                            </p>
                        </div>

                        <p class="break-words text-2xl font-bold tabular-nums
                            {{ $dailyNetCashFlow >= 0 ? 'text-emerald-700' : 'text-red-700' }}">

                            {{ $dailyNetCashFlow < 0 ? '−' : '' }}₹{{ number_format(abs($dailyNetCashFlow), 2) }}

                        </p>

                    </div>

                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-200">

                        <div
                            class="h-full rounded-full {{ $dailyNetCashFlow >= 0 ? 'bg-emerald-500' : 'bg-red-500' }}"
                            style="width: 100%"
                        ></div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ================================================= --}}
        {{-- MONTHLY CASH FLOW --}}
        {{-- ================================================= --}}

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">

                <div class="flex items-center gap-3">

                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">

                        <svg class="h-6 w-6"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24"
                             stroke-width="1.8"
                             stroke-linecap="round"
                             stroke-linejoin="round">

                            <path d="M4 20V4m0 16h16M8 16v-5m5 5V8m5 8V6"/>

                        </svg>

                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-900">
                            Monthly Cash Flow
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ now()->format('F Y') }}
                        </p>
                    </div>

                </div>

                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    Month to Date
                </span>

            </div>


            <div class="p-5">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                    {{-- MONTHLY RECEIPTS --}}

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">

                        <div class="flex items-center gap-2">

                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">

                                <svg class="h-4 w-4" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24"
                                     stroke-width="2">
                                    <path d="M12 3v15m-6-6 6 6 6-6"/>
                                </svg>

                            </span>

                            <p class="text-xs font-bold text-emerald-800">
                                Monthly Receipts
                            </p>

                        </div>

                        <p class="mt-4 break-words text-2xl font-bold tabular-nums text-emerald-700">
                            ₹{{ number_format((float) $monthReceipts, 2) }}
                        </p>

                        <p class="mt-2 text-xs text-emerald-800">
                            Month-to-date collections
                        </p>

                    </div>


                    {{-- MONTHLY PAYMENTS --}}

                    <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-4">

                        <div class="flex items-center gap-2">

                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 text-rose-700">

                                <svg class="h-4 w-4" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24"
                                     stroke-width="2">
                                    <path d="M12 21V6m-6 6 6-6 6 6"/>
                                </svg>

                            </span>

                            <p class="text-xs font-bold text-rose-800">
                                Monthly Payments
                            </p>

                        </div>

                        <p class="mt-4 break-words text-2xl font-bold tabular-nums text-slate-900">
                            ₹{{ number_format((float) $monthPayments, 2) }}
                        </p>

                        <p class="mt-2 text-xs text-rose-800">
                            Month-to-date payments
                        </p>

                    </div>

                </div>


                {{-- MONTHLY NET CASH FLOW --}}

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Net Monthly Cash Flow
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                Monthly receipts less payments
                            </p>
                        </div>

                        <p class="break-words text-2xl font-bold tabular-nums
                            {{ $monthlyNetCashFlow >= 0 ? 'text-emerald-700' : 'text-red-700' }}">

                            {{ $monthlyNetCashFlow < 0 ? '−' : '' }}₹{{ number_format(abs($monthlyNetCashFlow), 2) }}

                        </p>

                    </div>

                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-200">

                        <div
                            class="h-full rounded-full {{ $monthlyNetCashFlow >= 0 ? 'bg-emerald-500' : 'bg-red-500' }}"
                            style="width: 100%"
                        ></div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ACCOUNTING NOTE --}}

    <div class="rounded-xl border border-blue-100 bg-blue-50/60 px-5 py-4">

        <p class="text-xs leading-6 text-blue-900">
            <strong>Cash flow reporting:</strong>
            This overview reflects the existing Finance module's
            daily and monthly cash-flow calculations.
            It should not be interpreted as an accrual-based
            Income &amp; Expenditure Statement.
        </p>

    </div>

</section>



{{-- ========================================================= --}}
{{-- FINANCE MASTER & ACCOUNT BALANCES --}}
{{-- ========================================================= --}}

<section class="mt-8 space-y-5">

    {{-- SECTION HEADER --}}

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <h2 class="text-lg font-bold text-slate-900">
                Finance Master &amp; Account Balances
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Finance account configuration, income and expense
                classifications, and recorded account balances.
            </p>
        </div>

        <a href="{{ route('finance.master.index') }}"
           class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
            Manage Finance Master
            <span>→</span>
        </a>

    </div>


    {{-- ===================================================== --}}
    {{-- FINANCE MASTER SUMMARY --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- ACTIVE ACCOUNTS --}}

        <div class="rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Active Accounts
                </span>

                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <rect x="3" y="5" width="18" height="15" rx="2"/>
                        <path d="M3 10h18M7 15h4"/>
                    </svg>
                </span>

            </div>

            <p class="mt-4 text-3xl font-bold tabular-nums text-slate-900">
                {{ number_format((int) $activeAccounts) }}
            </p>

            <p class="mt-2 text-xs text-slate-500">
                Configured active Finance accounts
            </p>

        </div>


        {{-- INCOME HEADS --}}

        <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Income Heads
                </span>

                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path d="M4 20V4m0 16h16M7 15l5-5 3 3 5-7"/>
                    </svg>
                </span>

            </div>

            <p class="mt-4 text-3xl font-bold tabular-nums text-slate-900">
                {{ number_format((int) $activeIncomeHeads) }}
            </p>

            <p class="mt-2 text-xs text-slate-500">
                Active income classifications
            </p>

        </div>


        {{-- EXPENSE HEADS --}}

        <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-3">

                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Expense Heads
                </span>

                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <path d="M4 4h16v16H4zM8 9h8M8 13h8M8 17h5"/>
                    </svg>
                </span>

            </div>

            <p class="mt-4 text-3xl font-bold tabular-nums text-slate-900">
                {{ number_format((int) $activeExpenseHeads) }}
            </p>

            <p class="mt-2 text-xs text-slate-500">
                Active expense classifications
            </p>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- ACCOUNT BALANCES TABLE --}}
    {{-- ===================================================== --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- TABLE HEADER --}}

        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h3 class="text-base font-bold text-slate-900">
                    Account Balances
                </h3>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Calculated from opening balances, posted Finance vouchers,
                    and integrated billing collections.
                </p>

            </div>

            <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 sm:text-right">

                <p class="text-[11px] font-bold uppercase tracking-wide text-blue-700">
                    Total Account Balance
                </p>

                <p class="mt-1 break-words text-xl font-bold tabular-nums text-slate-900">
                    {{ (float) $totalAccountBalance < 0 ? '−' : '' }}₹{{ number_format(abs((float) $totalAccountBalance), 2) }}
                </p>

            </div>

        </div>


        @if($accountBalances->isEmpty())

            <div class="px-6 py-12 text-center">

                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24" stroke-width="1.8">
                        <rect x="3" y="5" width="18" height="15" rx="2"/>
                        <path d="M3 10h18M7 15h4"/>
                    </svg>
                </div>

                <h4 class="mt-4 text-sm font-bold text-slate-800">
                    No active Finance accounts
                </h4>

                <p class="mt-2 text-xs text-slate-500">
                    Configure Finance accounts to display account balances.
                </p>

            </div>

        @else

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>

                            <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Account
                            </th>

                            <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Type
                            </th>

                            <th class="px-4 py-4 text-right text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Opening
                            </th>

                            <th class="px-4 py-4 text-right text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Receipts
                            </th>

                            <th class="px-4 py-4 text-right text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Payments
                            </th>

                            <th class="px-4 py-4 text-right text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Transfer In
                            </th>

                            <th class="px-4 py-4 text-right text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                Transfer Out
                            </th>

                            <th class="bg-blue-50 px-5 py-4 text-right text-[11px] font-bold uppercase tracking-wide text-blue-700">
                                Closing Balance
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100 bg-white">

                        @foreach($accountBalances as $account)

                            <tr class="transition hover:bg-slate-50">

                                {{-- ACCOUNT --}}

                                <td class="px-5 py-4">

                                    <p class="whitespace-nowrap text-sm font-bold text-slate-900">
                                        {{ $account->name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $account->code }}
                                    </p>

                                </td>


                                {{-- ACCOUNT TYPE --}}

                                <td class="px-4 py-4">

                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ ucfirst($account->account_type) }}
                                    </span>

                                </td>


                                {{-- OPENING --}}

                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-slate-700">
                                    ₹{{ number_format((float) $account->opening_balance, 2) }}
                                </td>


                                {{-- RECEIPTS --}}

                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-emerald-700">
                                    ₹{{ number_format((float) $account->receipt_total, 2) }}
                                </td>


                                {{-- PAYMENTS --}}

                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-slate-700">
                                    ₹{{ number_format((float) $account->payment_total, 2) }}
                                </td>


                                {{-- TRANSFER IN --}}

                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-slate-700">
                                    ₹{{ number_format((float) $account->transfer_in_total, 2) }}
                                </td>


                                {{-- TRANSFER OUT --}}

                                <td class="whitespace-nowrap px-4 py-4 text-right text-sm tabular-nums text-slate-700">
                                    ₹{{ number_format((float) $account->transfer_out_total, 2) }}
                                </td>


                                {{-- CLOSING BALANCE --}}

                                <td class="whitespace-nowrap bg-blue-50/40 px-5 py-4 text-right">

                                    <span class="text-sm font-bold tabular-nums
                                        {{ (float) $account->calculated_balance >= 0
                                            ? 'text-slate-900'
                                            : 'text-red-700' }}">

                                        {{ (float) $account->calculated_balance < 0 ? '−' : '' }}₹{{ number_format(abs((float) $account->calculated_balance), 2) }}

                                    </span>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>


                    <tfoot class="border-t border-slate-200 bg-slate-50">

                        <tr>

                            <td colspan="7"
                                class="px-5 py-4 text-right text-sm font-bold text-slate-700">
                                Total Closing Balance
                            </td>

                            <td class="whitespace-nowrap bg-blue-50 px-5 py-4 text-right text-base font-bold tabular-nums text-slate-900">
                                {{ (float) $totalAccountBalance < 0 ? '−' : '' }}₹{{ number_format(abs((float) $totalAccountBalance), 2) }}
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        @endif


        {{-- ACCOUNTING NOTE --}}

        <div class="border-t border-slate-100 bg-slate-50 px-6 py-3">

            <p class="text-xs leading-5 text-slate-500">
                These balances use the Finance account calculation model.
                They should not be assumed to equal accrual General Ledger
                or TallyPrime balances without reconciliation.
            </p>

        </div>

    </div>

</section>


{{-- ========================================================= --}}
{{-- RECENT FINANCE TRANSACTIONS --}}
{{-- ========================================================= --}}

<section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

    {{-- HEADER --}}

    <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

        <div class="flex items-start gap-3">

            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">

                <svg class="h-6 w-6"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M4 7h14m-4-4 4 4-4 4M20 17H6m4-4-4 4 4 4"/>

                </svg>

            </div>

            <div>

                <h2 class="text-base font-bold text-slate-900">
                    Recent Finance Transactions
                </h2>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Latest Finance vouchers, including receipts,
                    payments, transfers, and posting status.
                </p>

            </div>

        </div>

        <a href="{{ route('finance.vouchers.index') }}"
           class="inline-flex w-fit items-center justify-center gap-2 rounded-lg bg-indigo-700 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-indigo-800">

            View All Vouchers
            <span aria-hidden="true">→</span>

        </a>

    </div>


    {{-- ===================================================== --}}
    {{-- TRANSACTION TABLE --}}
    {{-- ===================================================== --}}

    @if($recentVouchers->isEmpty())

        <div class="px-6 py-14 text-center">

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">

                <svg class="h-7 w-7"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M8 3h8l4 4v14H8a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Z"/>
                    <path d="M9 12h7M9 16h7"/>

                </svg>

            </div>

            <h3 class="mt-4 text-sm font-bold text-slate-800">
                No Finance Transactions Yet
            </h3>

            <p class="mt-2 text-xs leading-5 text-slate-500">
                Recent receipts, payments, and transfers will
                appear here once Finance vouchers are recorded.
            </p>

        </div>

    @else

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">

                    <tr>

                        <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Date
                        </th>

                        <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Voucher
                        </th>

                        <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Type
                        </th>

                        <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Finance Head
                        </th>

                        <th class="px-4 py-4 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Account
                        </th>

                        <th class="px-4 py-4 text-right text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Amount
                        </th>

                        <th class="px-4 py-4 text-center text-[11px] font-bold uppercase tracking-wide text-slate-500">
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100 bg-white">

                    @foreach($recentVouchers as $voucher)

                        @php
                            $voucherType = strtolower((string) $voucher->voucher_type);
                            $voucherStatus = strtolower((string) $voucher->status);

                            $typeClass = match ($voucherType) {
                                'receipt' => 'bg-emerald-50 text-emerald-700',
                                'payment' => 'bg-rose-50 text-rose-700',
                                'transfer' => 'bg-indigo-50 text-indigo-700',
                                default => 'bg-slate-100 text-slate-700',
                            };

                            $statusClass = match ($voucherStatus) {
                                'posted' => 'bg-emerald-100 text-emerald-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                'draft' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp

                        <tr class="transition hover:bg-slate-50">

                            {{-- DATE --}}

                            <td class="whitespace-nowrap px-5 py-4">

                                <p class="text-sm font-medium text-slate-800">
                                    {{ $voucher->voucher_date?->format('d M Y') ?? '—' }}
                                </p>

                            </td>


                            {{-- VOUCHER NUMBER --}}

                            <td class="whitespace-nowrap px-4 py-4">

                                <a href="{{ route('finance.vouchers.show', $voucher) }}"
                                   class="inline-flex items-center gap-1 text-sm font-bold text-indigo-700 hover:text-indigo-900 hover:underline">

                                    {{ $voucher->voucher_no }}

                                    <span class="text-xs" aria-hidden="true">↗</span>

                                </a>

                            </td>


                            {{-- VOUCHER TYPE --}}

                            <td class="px-4 py-4">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $typeClass }}">
                                    {{ ucfirst($voucherType) }}
                                </span>

                            </td>


                            {{-- FINANCE HEAD --}}

                            <td class="px-4 py-4 text-sm text-slate-700">

                                @if($voucherType === 'transfer')

                                    <span class="text-slate-500">
                                        Internal Transfer
                                    </span>

                                @else

                                    {{ $voucher->financeHead?->name ?? '—' }}

                                @endif

                            </td>


                            {{-- ACCOUNT --}}

                            <td class="px-4 py-4">

                                <div class="text-sm font-medium text-slate-700">
                                    {{ $voucher->financeAccount?->name ?? '—' }}
                                </div>

                                @if(
                                    $voucherType === 'transfer'
                                    && $voucher->destinationAccount
                                )

                                    <div class="mt-1 flex items-center gap-1 text-xs text-slate-500">

                                        <span aria-hidden="true">→</span>

                                        <span>
                                            {{ $voucher->destinationAccount->name }}
                                        </span>

                                    </div>

                                @endif

                            </td>


                            {{-- AMOUNT --}}

                            <td class="whitespace-nowrap px-4 py-4 text-right">

                                <span class="text-sm font-bold tabular-nums
                                    {{ $voucherType === 'receipt'
                                        ? 'text-emerald-700'
                                        : 'text-slate-900' }}">

                                    ₹{{ number_format((float) $voucher->amount, 2) }}

                                </span>

                            </td>


                            {{-- STATUS --}}

                            <td class="px-4 py-4 text-center">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">
                                    {{ ucfirst($voucherStatus ?: 'Unknown') }}
                                </span>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- FOOTER --}}
    {{-- ===================================================== --}}

    <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

        <p class="text-xs leading-5 text-slate-500">
            Showing the latest Finance vouchers returned by the dashboard.
            Draft and cancelled vouchers may be included.
        </p>

        <a href="{{ route('finance.vouchers.index') }}"
           class="inline-flex w-fit items-center gap-1 text-xs font-bold text-indigo-700 hover:text-indigo-900 hover:underline">

            Open Finance Voucher Register
            <span aria-hidden="true">→</span>

        </a>

    </div>

</section>


    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        /*
        |--------------------------------------------------------------------------
        | Revenue vs Operating Expenses
        |--------------------------------------------------------------------------
        */

        const revenueExpenseCanvas =
            document.getElementById('revenueExpenseChart');

        if (
            revenueExpenseCanvas
            && typeof window.Chart !== 'undefined'
        ) {
            new window.Chart(
                revenueExpenseCanvas,
                {
                    type: 'bar',

                    data: {
                        labels: @json(
                            $dashboardCharts[
                                'revenue_vs_expenses'
                            ]['labels']
                        ),

                        datasets: [
                            {
                                label: 'Amount (₹)',

                                data: @json(
                                    $dashboardCharts[
                                        'revenue_vs_expenses'
                                    ]['values']
                                ),

                                borderWidth: 1,
                                borderRadius: 8,
                                maxBarThickness: 90,
                            }
                        ]
                    },

                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        plugins: {
                            legend: {
                                display: false
                            },

                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const value =
                                            Number(context.raw ?? 0);

                                        return new Intl.NumberFormat(
                                            'en-IN',
                                            {
                                                style: 'currency',
                                                currency: 'INR',
                                                maximumFractionDigits: 2
                                            }
                                        ).format(value);
                                    }
                                }
                            }
                        },

                        scales: {
                            y: {
                                beginAtZero: true,

                                ticks: {
                                    callback: function (value) {
                                        return '₹'
                                            + new Intl.NumberFormat(
                                                'en-IN',
                                                {
                                                    notation: 'compact',
                                                    maximumFractionDigits: 1
                                                }
                                            ).format(value);
                                    }
                                },

                                grid: {
                                    color: 'rgba(148, 163, 184, 0.15)'
                                }
                            },

                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Break-even Position
        |--------------------------------------------------------------------------
        */

        @if($dashboardCharts['break_even']['ready'])

            const breakEvenCanvas =
                document.getElementById('breakEvenChart');

            if (
                breakEvenCanvas
                && typeof window.Chart !== 'undefined'
            ) {
                new window.Chart(
                    breakEvenCanvas,
                    {
                        type: 'bar',

                        data: {
                            labels: @json(
                                $dashboardCharts[
                                    'break_even'
                                ]['labels']
                            ),

                            datasets: [
                                {
                                    label: 'Amount (₹)',

                                    data: @json(
                                        $dashboardCharts[
                                            'break_even'
                                        ]['values']
                                    ),

                                    borderWidth: 1,
                                    borderRadius: 8,
                                    maxBarThickness: 90,
                                }
                            ]
                        },

                        options: {
                            responsive: true,
                            maintainAspectRatio: false,

                            plugins: {
                                legend: {
                                    display: false
                                },

                                tooltip: {
                                    callbacks: {
                                        label: function (context) {
                                            const value =
                                                Number(context.raw ?? 0);

                                            return new Intl.NumberFormat(
                                                'en-IN',
                                                {
                                                    style: 'currency',
                                                    currency: 'INR',
                                                    maximumFractionDigits: 2
                                                }
                                            ).format(value);
                                        }
                                    }
                                }
                            },

                            scales: {
                                y: {
                                    beginAtZero: true,

                                    ticks: {
                                        callback: function (value) {
                                            return '₹'
                                                + new Intl.NumberFormat(
                                                    'en-IN',
                                                    {
                                                        notation: 'compact',
                                                        maximumFractionDigits: 1
                                                    }
                                                ).format(value);
                                        }
                                    },

                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.15)'
                                    }
                                },

                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    }
                );
            }

        @endif

    });
</script>


</x-app-layout>