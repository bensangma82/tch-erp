<x-app-layout>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Finance Dashboard
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Hospital receipts, payments and financial overview
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('finance.vouchers.index') }}"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    Finance Vouchers
                </a>

                <a
                    href="{{ route('finance.master.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Finance Master
                </a>
            </div>

        </div>
        {{-- EXECUTIVE SNAPSHOT --}}
<div class="mb-6">

    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                Executive Snapshot
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Key cash, collection and working-capital indicators
            </p>
        </div>

        <div class="text-xs font-medium text-slate-500">
            {{ now()->format('d M Y') }}
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

        {{-- Cash & Bank --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Cash &amp; Bank
            </div>

            <div class="mt-2 text-2xl font-bold text-slate-900">
                ₹{{ number_format((float) $liquidity['cash_and_bank'], 2) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Current ERP balance
            </div>
        </div>

        {{-- Today Receipts --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Today Receipts
            </div>

            <div class="mt-2 text-2xl font-bold text-emerald-600">
                ₹{{ number_format((float) $todayReceipts, 2) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Total collections today
            </div>
        </div>

        {{-- Today Payments --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Today Payments
            </div>

            <div class="mt-2 text-2xl font-bold text-slate-900">
                ₹{{ number_format((float) $todayPayments, 2) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Cash &amp; bank outflow
            </div>
        </div>

        {{-- Month Net Cash Flow --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Month Net Cash Flow
            </div>

            <div class="mt-2 text-2xl font-bold
                {{ $monthNet >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                ₹{{ number_format((float) $monthNet, 2) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Receipts less payments
            </div>
        </div>

        {{-- MHIS Receivable --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                MHIS Receivable
            </div>

            <div class="mt-2 text-2xl font-bold text-sky-600">
                ₹{{ number_format((float) $liquidity['mhis_receivables'], 2) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Outstanding MHIS claims
            </div>
        </div>

        {{-- Supplier Payables --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Supplier Payables
            </div>

            <div class="mt-2 text-2xl font-bold text-amber-600">
                ₹{{ number_format((float) $liquidity['supplier_payables'], 2) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Outstanding supplier liability
            </div>
        </div>

    </div>
</div>
{{-- Tally Reconciliation Health --}}
<div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-slate-900">
                Tally Reconciliation Health
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Current ERP and Tally reconciliation status
            </p>
        </div>

        <a
            href="{{ route('finance.tally.reconciliation') }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
        >
            Open Reconciliation
        </a>
    </div>

    <div class="grid grid-cols-2 divide-x divide-y divide-slate-100 sm:grid-cols-3 xl:grid-cols-6">

        {{-- Matched --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Matched
            </div>

            <div class="mt-2 text-2xl font-bold text-emerald-600">
                {{ $tallyReconciliation['matched'] }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                ERP &amp; Tally linked
            </div>
        </div>

        {{-- Tally Only --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Tally Only
            </div>

            <div class="mt-2 text-2xl font-bold text-amber-600">
                {{ $tallyReconciliation['tally_only'] }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                No ERP link
            </div>
        </div>

        {{-- ERP Only --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                ERP Only
            </div>

            <div class="mt-2 text-2xl font-bold text-amber-600">
                {{ $tallyReconciliation['erp_only'] }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                No Tally match
            </div>
        </div>

        {{-- Ambiguous --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Ambiguous
            </div>

            <div class="mt-2 text-2xl font-bold text-orange-600">
                {{ $tallyReconciliation['ambiguous'] }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Review required
            </div>
        </div>

        {{-- Unmapped --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Unmapped
            </div>

            <div class="mt-2 text-2xl font-bold text-purple-600">
                {{ $tallyReconciliation['unmapped'] }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Mapping required
            </div>
        </div>

        {{-- Differences --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Differences
            </div>

            <div class="mt-2 text-2xl font-bold
                {{ $tallyReconciliation['difference'] > 0
                    ? 'text-red-600'
                    : 'text-emerald-600' }}">
                {{ $tallyReconciliation['difference'] }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Accounting exceptions
            </div>
        </div>

    </div>
</div>
{{-- FINANCIAL HEALTH SUMMARY --}}
@php
    $healthStatus = $financialRisk['status'] ?? 'unknown';

    $healthPanelClass = match ($healthStatus) {
        'critical' => 'border-red-200 bg-red-50',
        'warning' => 'border-amber-200 bg-amber-50',
        'stable' => 'border-emerald-200 bg-emerald-50',
        default => 'border-slate-200 bg-slate-50',
    };

    $healthTextClass = match ($healthStatus) {
        'critical' => 'text-red-700',
        'warning' => 'text-amber-700',
        'stable' => 'text-emerald-700',
        default => 'text-slate-700',
    };

    $healthBadgeClass = match ($healthStatus) {
        'critical' => 'bg-red-100 text-red-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'stable' => 'bg-emerald-100 text-emerald-700',
        default => 'bg-slate-200 text-slate-700',
    };
@endphp

<div class="mb-6 rounded-xl border p-5 shadow-sm {{ $healthPanelClass }}">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-lg font-semibold text-slate-900">
                    Financial Health Summary
                </h2>

                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $healthBadgeClass }}">
                    {{ strtoupper(str_replace('_', ' ', $healthStatus)) }}
                </span>
            </div>

            <div class="mt-2 text-sm {{ $healthTextClass }}">
                {{ $financialRisk['label'] ?? 'Financial status unavailable' }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                {{ $financialRisk['period'] ?? '' }}
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">

            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Current Ratio
                </div>

                <div class="mt-1 text-lg font-bold text-slate-900">
                    @if($liquidity['current_ratio'] !== null)
                        {{ number_format((float) $liquidity['current_ratio'], 2) }}
                    @else
                        —
                    @endif
                </div>
            </div>

            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Quick Ratio
                </div>

                <div class="mt-1 text-lg font-bold text-slate-900">
                    @if($liquidity['quick_ratio'] !== null)
                        {{ number_format((float) $liquidity['quick_ratio'], 2) }}
                    @else
                        —
                    @endif
                </div>
            </div>

            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Break-even
                </div>

                <div class="mt-1 text-lg font-bold
                    {{ $breakEven['ready'] ? 'text-slate-900' : 'text-amber-700' }}">
                    @if($breakEven['ready'])
                        ₹{{ number_format((float) $breakEven['break_even_revenue'], 2) }}
                    @else
                        Pending
                    @endif
                </div>
            </div>

            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    MTD Net Cash Flow
                </div>

                <div class="mt-1 text-lg font-bold
                    {{ $monthNet >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    ₹{{ number_format((float) $monthNet, 2) }}
                </div>
            </div>

        </div>

    </div>
</div>
        {{-- Financial Health --}}
        <div class="mb-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                        Financial Health
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Liquidity, break-even position and management risk indicators
                    </p>
                </div>

                <div class="text-xs font-medium text-slate-500">
                    {{ $financialRisk['period'] }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Current Ratio --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-slate-500">
                            Current Ratio
                        </div>

                        <div class="mt-2 text-3xl font-bold text-slate-900">
                            @if($liquidity['current_ratio'] !== null)
                                {{ number_format((float) $liquidity['current_ratio'], 2) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-lg font-bold text-slate-600">
                        CR
                    </div>
                </div>

                <div class="mt-4 text-xs text-slate-500">
                    Current assets
                    <span class="font-semibold text-slate-700">
                        ₹{{ number_format((float) $liquidity['current_assets'], 2) }}
                    </span>
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Current liabilities
                    <span class="font-semibold text-slate-700">
                        ₹{{ number_format((float) $liquidity['current_liabilities'], 2) }}
                    </span>
                </div>

                <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    Management indicator — interpret after complete live financial data is available.
                </div>
            </div>


            {{-- Quick Ratio --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-slate-500">
                            Quick Ratio
                        </div>

                        <div class="mt-2 text-3xl font-bold text-slate-900">
                            @if($liquidity['quick_ratio'] !== null)
                                {{ number_format((float) $liquidity['quick_ratio'], 2) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-lg font-bold text-slate-600">
                        QR
                    </div>
                </div>

                <div class="mt-4 text-xs text-slate-500">
                    Quick assets
                    <span class="font-semibold text-slate-700">
                        ₹{{ number_format((float) $liquidity['quick_assets'], 2) }}
                    </span>
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Cash &amp; bank
                    <span class="font-semibold text-slate-700">
                        ₹{{ number_format((float) $liquidity['cash_and_bank'], 2) }}
                    </span>
                </div>

                <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    Pharmacy inventory is excluded from quick assets.
                </div>
            </div>


            {{-- Break-even --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-slate-500">
                            Break-even
                        </div>

                        @if($breakEven['ready'])
                            <div class="mt-2 text-2xl font-bold text-slate-900">
                                ₹{{ number_format((float) $breakEven['break_even_revenue'], 2) }}
                            </div>
                        @else
                            <div class="mt-2 text-xl font-bold text-amber-700">
                                Data Required
                            </div>
                        @endif
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-lg font-bold text-slate-600">
                        BE
                    </div>
                </div>

                <div class="mt-4 text-xs text-slate-500">
                    MTD revenue
                    <span class="font-semibold text-slate-700">
                        ₹{{ number_format((float) $breakEven['revenue'], 2) }}
                    </span>
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Operating expenses
                    <span class="font-semibold text-slate-700">
                        ₹{{ number_format((float) $breakEven['total_expenses'], 2) }}
                    </span>
                </div>

                @if($breakEven['ready'])
                    <div class="mt-3 text-xs text-slate-500">
                        Margin of safety:
                        <span class="font-semibold text-slate-700">
                            {{ number_format((float) $breakEven['margin_of_safety_percentage'], 2) }}%
                        </span>
                    </div>
                @else
                    <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        @if($breakEven['status'] === 'insufficient_expense_data')
                            No operating expenses have been posted this month.
                        @elseif($breakEven['status'] === 'configuration_required')
                            Expense classification requires completion.
                        @elseif($breakEven['status'] === 'insufficient_revenue_data')
                            Revenue data is not yet sufficient.
                        @else
                            Break-even cannot currently be calculated.
                        @endif
                    </div>
                @endif
            </div>


            {{-- Financial Risk --}}
            @php
                $riskCardClass = match($financialRisk['status']) {
                    'critical' => 'border-red-300 bg-red-50',
                    'warning' => 'border-amber-300 bg-amber-50',
                    'stable' => 'border-emerald-300 bg-emerald-50',
                    default => 'border-slate-300 bg-slate-50',
                };

                $riskTextClass = match($financialRisk['status']) {
                    'critical' => 'text-red-700',
                    'warning' => 'text-amber-700',
                    'stable' => 'text-emerald-700',
                    default => 'text-slate-700',
                };

                $riskBadgeClass = match($financialRisk['status']) {
                    'critical' => 'bg-red-100 text-red-700',
                    'warning' => 'bg-amber-100 text-amber-700',
                    'stable' => 'bg-emerald-100 text-emerald-700',
                    default => 'bg-slate-200 text-slate-700',
                };
            @endphp

            <div class="rounded-xl border p-5 shadow-sm {{ $riskCardClass }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-slate-500">
                            Financial Risk
                        </div>

                        <div class="mt-2 text-xl font-bold {{ $riskTextClass }}">
                            {{ $financialRisk['label'] }}
                        </div>
                    </div>

                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $riskBadgeClass }}">
                        {{ strtoupper(str_replace('_', ' ', $financialRisk['status'])) }}
                    </span>
                </div>

                @if(!empty($financialRisk['critical_issues']))
                    <div class="mt-4 space-y-2">
                        @foreach($financialRisk['critical_issues'] as $issue)
                            <div class="rounded-lg bg-red-100 px-3 py-2 text-xs font-medium text-red-800">
                                {{ $issue }}
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($financialRisk['warnings']))
                    <div class="mt-4 space-y-2">
                        @foreach($financialRisk['warnings'] as $warning)
                            <div class="rounded-lg bg-white/70 px-3 py-2 text-xs text-slate-700">
                                {{ $warning }}
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(
                    empty($financialRisk['warnings'])
                    && empty($financialRisk['critical_issues'])
                )
                    <div class="mt-4 text-xs text-slate-600">
                        No financial warning threshold is currently triggered.
                    </div>
                @endif
            </div>

        </div>

        {{-- Financial Performance Charts --}}
        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- Revenue vs Expenses --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div>
                    <h3 class="font-semibold text-slate-900">
                        Revenue vs Operating Expenses
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $dashboardCharts['period'] }} financial performance
                    </p>
                </div>

                <div class="mt-5 h-72">
                    <canvas id="revenueExpenseChart"></canvas>
                </div>

            </div>


            {{-- Break-even Position --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div>
                    <h3 class="font-semibold text-slate-900">
                        Break-even Position
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Actual revenue compared with calculated break-even revenue
                    </p>
                </div>

                @if($dashboardCharts['break_even']['ready'])

                    <div class="mt-5 h-72">
                        <canvas id="breakEvenChart"></canvas>
                    </div>

                @else

                    <div class="flex h-72 items-center justify-center">

                        <div class="max-w-sm text-center">

                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-lg font-bold text-amber-700">
                                BE
                            </div>

                            <div class="mt-4 font-semibold text-slate-900">
                                Break-even chart not available
                            </div>

                            <p class="mt-2 text-sm text-slate-500">
                                Operating expense data must be posted before a valid
                                break-even target can be calculated.
                            </p>

                        </div>

                    </div>

                @endif

            </div>

        </div>
        {{-- MONTHLY REVENUE BY SERVICE --}}
@php
    $monthlyRevenueTotal =
        (float) $billingMonthRevenueByHead->sum('amount');

    $revenueHeadsWithActivity =
        $billingMonthRevenueByHead
            ->filter(
                fn ($revenue) =>
                    abs((float) $revenue['amount']) > 0.0001
            )
            ->values();

    $zeroRevenueHeads =
        $billingMonthRevenueByHead
            ->filter(
                fn ($revenue) =>
                    abs((float) $revenue['amount']) <= 0.0001
            )
            ->values();
@endphp

<div
    class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    x-data="{ showZeroHeads: false }"
>

    {{-- Header --}}
    <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h3 class="font-semibold text-slate-900">
                Monthly Revenue by Service
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Revenue collected this month, grouped by Finance Head
            </p>
        </div>

        <div class="text-left sm:text-right">
            <div class="text-lg font-bold text-slate-900">
                ₹{{ number_format($monthlyRevenueTotal, 2) }}
            </div>

            <div class="mt-0.5 text-xs text-slate-500">
                {{ now()->format('F Y') }}
            </div>
        </div>

    </div>


    {{-- Revenue heads with activity --}}
    @if($revenueHeadsWithActivity->isEmpty())

        <div class="px-5 py-8 text-center text-sm text-slate-500">
            No revenue collections have been classified this month.
        </div>

    @else

        <div class="divide-y divide-slate-100">

            @foreach($revenueHeadsWithActivity as $revenue)

                @php
                    $amount = (float) $revenue['amount'];

                    $percentage =
                        $monthlyRevenueTotal > 0
                            ? ($amount / $monthlyRevenueTotal) * 100
                            : 0;
                @endphp

                <div class="px-5 py-4">

                    <div class="flex items-center gap-4">

                        {{-- Service --}}
                        <div class="min-w-0 flex-1">

                            <div class="flex flex-wrap items-center gap-2">

                                @if(!empty($revenue['id']))
                                    <a
                                        href="{{ route(
                                            'finance.revenue.detail',
                                            $revenue['id']
                                        ) }}"
                                        class="inline-flex items-center gap-1 font-medium text-indigo-700 hover:text-indigo-900 hover:underline"
                                    >
                                        {{ $revenue['name'] }}

                                        <span class="text-xs text-slate-400">
                                            ↗
                                        </span>
                                    </a>
                                @else
                                    <span class="font-medium text-slate-900">
                                        {{ $revenue['name'] }}
                                    </span>
                                @endif

                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                                    {{ $revenue['code'] }}
                                </span>

                            </div>

                            {{-- Revenue bar --}}
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">

                                <div
                                    class="h-full rounded-full bg-indigo-500"
                                    style="width: {{ min(
                                        100,
                                        max(0, $percentage)
                                    ) }}%"
                                ></div>

                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                {{ number_format($percentage, 1) }}%
                                of classified monthly revenue
                            </div>

                        </div>


                        {{-- Amount --}}
                        <div class="w-32 shrink-0 text-right">

                            <div class="text-lg font-bold text-slate-900">
                                ₹{{ number_format($amount, 2) }}
                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    @endif


    {{-- Zero Revenue Heads --}}
    @if($zeroRevenueHeads->isNotEmpty())

        <div class="border-t border-slate-200 bg-slate-50">

            {{-- Toggle --}}
            <button
                type="button"
                @click="showZeroHeads = !showZeroHeads"
                class="flex w-full items-center justify-between px-5 py-3 text-left transition hover:bg-slate-100"
            >

                <div>
                    <span class="text-sm font-semibold text-slate-700">
                        Zero revenue heads
                    </span>

                    <span class="ml-2 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">
                        {{ $zeroRevenueHeads->count() }}
                    </span>
                </div>

                <div class="flex items-center gap-2 text-xs font-medium text-slate-500">

                    <span x-text="showZeroHeads ? 'Hide' : 'Show'">
                        Show
                    </span>

                    <svg
                        class="h-4 w-4 transition-transform duration-200"
                        :class="{ 'rotate-180': showZeroHeads }"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd"
                        />
                    </svg>

                </div>

            </button>


            {{-- Compact zero-head grid --}}
            <div
                x-show="showZeroHeads"
                x-collapse
                class="border-t border-slate-200 bg-white"
            >

                <div class="grid grid-cols-1 gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                    @foreach($zeroRevenueHeads as $revenue)

                        @if(!empty($revenue['id']))

                            <a
                                href="{{ route(
                                    'finance.revenue.detail',
                                    $revenue['id']
                                ) }}"
                                class="group bg-white px-4 py-3 transition hover:bg-slate-50"
                            >

                                <div class="flex items-center justify-between gap-3">

                                    <div class="min-w-0">

                                        <div class="truncate text-sm font-medium text-slate-700 group-hover:text-indigo-700">
                                            {{ $revenue['name'] }}
                                        </div>

                                        <div class="mt-0.5 text-xs text-slate-400">
                                            {{ $revenue['code'] }}
                                        </div>

                                    </div>

                                    <div class="shrink-0 text-sm font-semibold text-slate-500">
                                        ₹0
                                    </div>

                                </div>

                            </a>

                        @else

                            <div class="bg-white px-4 py-3">

                                <div class="flex items-center justify-between gap-3">

                                    <div class="min-w-0">

                                        <div class="truncate text-sm font-medium text-slate-700">
                                            {{ $revenue['name'] }}
                                        </div>

                                        <div class="mt-0.5 text-xs text-slate-400">
                                            {{ $revenue['code'] }}
                                        </div>

                                    </div>

                                    <div class="shrink-0 text-sm font-semibold text-slate-500">
                                        ₹0
                                    </div>

                                </div>

                            </div>

                        @endif

                    @endforeach

                </div>

            </div>

        </div>

    @endif

</div>
        {{-- Liquidity Composition --}}
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900">
                        Liquidity Composition
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Current assets currently represented in the ERP
                    </p>
                </div>

                <div class="text-sm font-semibold text-slate-700">
                    ₹{{ number_format((float) $liquidity['current_assets'], 2) }}
                </div>
            </div>

            @php
                $currentAssets = max(
                    (float) $liquidity['current_assets'],
                    0
                );

                $cashPercent = $currentAssets > 0
                    ? ((float) $liquidity['cash_and_bank'] / $currentAssets) * 100
                    : 0;

                $inventoryPercent = $currentAssets > 0
                    ? ((float) $liquidity['pharmacy_inventory'] / $currentAssets) * 100
                    : 0;

                $mhisPercent = $currentAssets > 0
                    ? ((float) $liquidity['mhis_receivables'] / $currentAssets) * 100
                    : 0;
            @endphp

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">

                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">
                            Cash &amp; Bank
                        </span>

                        <span class="font-semibold text-slate-900">
                            {{ number_format($cashPercent, 1) }}%
                        </span>
                    </div>

                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-emerald-500"
                            style="width: {{ min(100, max(0, $cashPercent)) }}%"
                        ></div>
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        ₹{{ number_format((float) $liquidity['cash_and_bank'], 2) }}
                    </div>
                </div>


                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">
                            Pharmacy Inventory
                        </span>

                        <span class="font-semibold text-slate-900">
                            {{ number_format($inventoryPercent, 1) }}%
                        </span>
                    </div>

                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-indigo-500"
                            style="width: {{ min(100, max(0, $inventoryPercent)) }}%"
                        ></div>
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        ₹{{ number_format((float) $liquidity['pharmacy_inventory'], 2) }}
                    </div>
                </div>


                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">
                            MHIS Receivable
                        </span>

                        <span class="font-semibold text-slate-900">
                            {{ number_format($mhisPercent, 1) }}%
                        </span>
                    </div>

                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-sky-500"
                            style="width: {{ min(100, max(0, $mhisPercent)) }}%"
                        ></div>
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        ₹{{ number_format((float) $liquidity['mhis_receivables'], 2) }}
                    </div>
                </div>

            </div>

            <div class="mt-5 border-t border-slate-100 pt-4 text-xs text-slate-500">
                Current liabilities recorded:
                <span class="font-semibold text-slate-700">
                    ₹{{ number_format((float) $liquidity['current_liabilities'], 2) }}
                </span>

                <span class="mx-2 text-slate-300">|</span>

                Supplier payables:
                <span class="font-semibold text-slate-700">
                    ₹{{ number_format((float) $liquidity['supplier_payables'], 2) }}
                </span>
            </div>

        </div>


        <div class="mt-8"></div>

        {{-- WORKING CAPITAL & OBLIGATIONS --}}
<div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="font-semibold text-slate-900">
            Working Capital &amp; Obligations
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Receivables, payables and short-term financial position
        </p>
    </div>

    @php
        $workingCapital =
            (float) $liquidity['current_assets']
            - (float) $liquidity['current_liabilities'];

        $netReceivablePosition =
            (float) $liquidity['mhis_receivables']
            - (float) $liquidity['supplier_payables'];
    @endphp

    <div class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-2 lg:grid-cols-4 lg:divide-x lg:divide-y-0">

        {{-- MHIS Receivable --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                MHIS Receivable
            </div>

            <div class="mt-2 text-2xl font-bold text-sky-600">
                ₹{{ number_format(
                    (float) $liquidity['mhis_receivables'],
                    2
                ) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Amount awaiting realization
            </div>
        </div>

        {{-- Supplier Payables --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Supplier Payables
            </div>

            <div class="mt-2 text-2xl font-bold
                {{ (float) $liquidity['supplier_payables'] > 0
                    ? 'text-amber-600'
                    : 'text-slate-900' }}">
                ₹{{ number_format(
                    (float) $liquidity['supplier_payables'],
                    2
                ) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Outstanding supplier liability
            </div>
        </div>

        {{-- Net Receivable Position --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Net Receivable Position
            </div>

            <div class="mt-2 text-2xl font-bold
                {{ $netReceivablePosition >= 0
                    ? 'text-emerald-600'
                    : 'text-red-600' }}">
                ₹{{ number_format(
                    $netReceivablePosition,
                    2
                ) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                MHIS less supplier payables
            </div>
        </div>

        {{-- Working Capital --}}
        <div class="p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Working Capital
            </div>

            <div class="mt-2 text-2xl font-bold
                {{ $workingCapital >= 0
                    ? 'text-emerald-600'
                    : 'text-red-600' }}">
                ₹{{ number_format(
                    $workingCapital,
                    2
                ) }}
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Current assets less current liabilities
            </div>
        </div>

    </div>

    <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">
        <div class="flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">

            <div>
                Current assets:
                <span class="font-semibold text-slate-700">
                    ₹{{ number_format(
                        (float) $liquidity['current_assets'],
                        2
                    ) }}
                </span>
            </div>

            <div>
                Current liabilities:
                <span class="font-semibold text-slate-700">
                    ₹{{ number_format(
                        (float) $liquidity['current_liabilities'],
                        2
                    ) }}
                </span>
            </div>

        </div>
    </div>

</div>
       
        {{-- Today's Summary --}}
        <div class="mb-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                Today
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Receipts
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-600">
                    ₹{{ number_format((float) $todayReceipts, 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Payments
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    ₹{{ number_format((float) $todayPayments, 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Net Cash Flow
                </div>

                <div class="mt-2 text-2xl font-bold
                    {{ $todayNet >= 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                    ₹{{ number_format((float) $todayNet, 2) }}
                </div>
            </div>

        </div>


        {{-- Monthly Summary --}}
        <div class="mb-3 mt-8">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                This Month
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Receipts
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-600">
                    ₹{{ number_format((float) $monthReceipts, 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Payments
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    ₹{{ number_format((float) $monthPayments, 2) }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Net Cash Flow
                </div>

                <div class="mt-2 text-2xl font-bold
                    {{ $monthNet >= 0 ? 'text-emerald-600' : 'text-slate-900' }}">
                    ₹{{ number_format((float) $monthNet, 2) }}
                </div>
            </div>

        </div>


        {{-- Finance Master Summary --}}
        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Active Accounts
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $activeAccounts }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Income Heads
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $activeIncomeHeads }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Expense Heads
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    {{ $activeExpenseHeads }}
                </div>
            </div>

        </div>


        {{-- Account Balances --}}
        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="font-semibold text-slate-900">
                        Account Balances
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Calculated from opening balances, posted Finance vouchers, and integrated billing collections
                    </p>
                </div>

                <div class="text-left sm:text-right">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Total Balance
                    </div>

                    <div class="mt-1 text-xl font-bold text-slate-900">
                        ₹{{ number_format((float) $totalAccountBalance, 2) }}
                    </div>
                </div>

            </div>


            @if($accountBalances->isEmpty())

                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    No active Finance accounts found.
                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Account
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Opening
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Receipts
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Payments
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Transfer In
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Transfer Out
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Balance
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($accountBalances as $account)

                                <tr class="hover:bg-slate-50">

                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $account->name }}
                                        </div>

                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $account->code }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ ucfirst($account->account_type) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->opening_balance, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->receipt_total, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->payment_total, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->transfer_in_total, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $account->transfer_out_total, 2) }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <span class="text-sm font-bold
                                            {{ $account->calculated_balance >= 0
                                                ? 'text-slate-900'
                                                : 'text-red-700' }}">
                                            ₹{{ number_format((float) $account->calculated_balance, 2) }}
                                        </span>
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot class="border-t border-slate-200 bg-slate-50">
                            <tr>

                                <td
                                    colspan="7"
                                    class="px-4 py-3 text-right text-sm font-semibold text-slate-700"
                                >
                                    Total Account Balance
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-base font-bold text-slate-900">
                                    ₹{{ number_format((float) $totalAccountBalance, 2) }}
                                </td>

                            </tr>
                        </tfoot>

                    </table>

                </div>

            @endif

        </div>


        {{-- Recent Transactions --}}
        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">

                <h2 class="font-semibold text-slate-900">
                    Recent Finance Transactions
                </h2>

                <a
                    href="{{ route('finance.vouchers.index') }}"
                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                >
                    View All
                </a>

            </div>

            @if($recentVouchers->isEmpty())

                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    No finance vouchers have been entered yet.
                </div>

            @else

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Date
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Voucher
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Head
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Account
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($recentVouchers as $voucher)

                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                        {{ $voucher->voucher_date?->format('d M Y') }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium">
                                        <a
                                            href="{{ route('finance.vouchers.show', $voucher) }}"
                                            class="text-indigo-600 hover:text-indigo-800"
                                        >
                                            {{ $voucher->voucher_no }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ ucfirst($voucher->voucher_type) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">

                                        @if($voucher->voucher_type === 'transfer')
                                            Transfer
                                        @else
                                            {{ $voucher->financeHead?->name ?? '—' }}
                                        @endif

                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">

                                        {{ $voucher->financeAccount?->name ?? '—' }}

                                        @if(
                                            $voucher->voucher_type === 'transfer'
                                            && $voucher->destinationAccount
                                        )
                                            <span class="mx-1 text-slate-400">
                                                →
                                            </span>

                                            {{ $voucher->destinationAccount->name }}
                                        @endif

                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </td>

                                    <td class="px-4 py-3 text-center">

                                        @if($voucher->status === 'posted')

                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                Posted
                                            </span>

                                        @elseif($voucher->status === 'cancelled')

                                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                                                Cancelled
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">
                                                Draft
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

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