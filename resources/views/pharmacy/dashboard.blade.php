<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor"
                         stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m10.5 3.5 10 10a5 5 0 0 1-7 7l-10-10a5 5 0 0 1 7-7ZM8 8l8 8"/>
                    </svg>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">
                        Pharmacy Operations
                    </p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                        Pharmacy Dashboard
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Sales, inventory, medicine movement and stock monitoring
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if (auth()->user()?->isAdmin() || auth()->user()?->hasAnyRole(['management']))
                    <a href="{{ route('pharmacy.movement-report') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                        <span>Medicine Movement Analytics</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @endif

                <a href="{{ route('pharmacy.dispensing.create') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <span aria-hidden="true">+</span>
                    New Dispensing
                </a>
            </div>
        </div>
    </x-slot>



    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- TODAY'S PHARMACY --}}
            {{-- ========================================================= --}}

            <div>

                <div class="mb-4">

                    <h3 class="text-lg font-bold text-slate-900">
                        Today's Pharmacy
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ now()->format('d M Y') }}
                    </p>

                </div>



                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">


                    @php
                        $salesCards = [
                            [
                                'label' => 'Gross Sales',
                                'amount' => $todaySalesAmount,
                                'description' => number_format($todayDispensingCount) . ' dispensing transactions',
                                'color' => 'blue',
                                'icon' => 'sales',
                            ],
                            [
                                'label' => 'Returns / Refunds',
                                'amount' => $todayRefundAmount,
                                'description' => number_format($todayReturnCount) . ' return transactions',
                                'color' => 'red',
                                'icon' => 'returns',
                            ],
                            [
                                'label' => 'Net Pharmacy Sales',
                                'amount' => $todayNetSales,
                                'description' => 'Gross sales less completed refunds',
                                'color' => 'emerald',
                                'icon' => 'net',
                            ],
                        ];

                        $cardStyles = [
                            'blue' => ['bg-blue-50', 'text-blue-700', 'ring-blue-100'],
                            'red' => ['bg-red-50', 'text-red-700', 'ring-red-100'],
                            'emerald' => ['bg-emerald-50', 'text-emerald-700', 'ring-emerald-100'],
                        ];
                    @endphp

                    @foreach ($salesCards as $card)
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl ring-1 {{ implode(' ', [$cardStyles[$card['color']][0], $cardStyles[$card['color']][1], $cardStyles[$card['color']][2]]) }}">
                                    @if ($card['icon'] === 'sales')
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                             stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3 3v18h18M7 15l4-4 3 2 6-7"/>
                                        </svg>
                                    @elseif ($card['icon'] === 'returns')
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                             stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3 10h12a5 5 0 0 1 0 10h-2M3 10l5-5M3 10l5 5"/>
                                        </svg>
                                    @else
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                             stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3 17l6-6 4 4 8-9M15 6h6v6"/>
                                        </svg>
                                    @endif
                                </div>

                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">
                                    Today
                                </span>
                            </div>

                            <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ $card['label'] }}
                            </p>

                            <p class="mt-2 text-3xl font-bold tracking-tight tabular-nums {{ $cardStyles[$card['color']][1] }}">
                                ₹{{ number_format((float) $card['amount'], 2) }}
                            </p>

                            <p class="mt-3 text-sm text-slate-500">
                                {{ $card['description'] }}
                            </p>
                        </div>
                    @endforeach

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- STOCK ALERTS --}}
            {{-- ========================================================= --}}

            <div>

                <div class="mb-4">

                    <h3 class="text-lg font-bold text-slate-900">
                        Stock Alerts
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Click an alert to review the affected batches
                    </p>

                </div>


                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">


                    @php
                        $stockAlerts = [
                            [
                                'title' => 'Low Stock',
                                'count' => $lowStockCount,
                                'description' => 'At or below reorder level',
                                'filter' => 'low_stock',
                                'action' => 'View low stock',
                                'color' => 'amber',
                                'icon' => 'low',
                            ],
                            [
                                'title' => 'Out of Stock',
                                'count' => $outOfStockCount,
                                'description' => 'Active batches with zero stock',
                                'filter' => 'out_of_stock',
                                'action' => 'View out of stock',
                                'color' => 'red',
                                'icon' => 'empty',
                            ],
                            [
                                'title' => 'Near Expiry',
                                'count' => $nearExpiryCount,
                                'description' => 'Expiring within 90 days',
                                'filter' => 'near_expiry',
                                'action' => 'View near expiry',
                                'color' => 'orange',
                                'icon' => 'calendar',
                            ],
                            [
                                'title' => 'Expired Stock',
                                'count' => $expiredCount,
                                'description' => 'Expired batches with stock remaining',
                                'filter' => 'expired',
                                'action' => 'Review expired stock',
                                'color' => 'rose',
                                'icon' => 'warning',
                            ],
                        ];

                        $alertStyles = [
                            'amber' => [
                                'bg' => 'bg-amber-50',
                                'text' => 'text-amber-700',
                                'ring' => 'ring-amber-100',
                                'hover' => 'hover:border-amber-300',
                            ],
                            'red' => [
                                'bg' => 'bg-red-50',
                                'text' => 'text-red-700',
                                'ring' => 'ring-red-100',
                                'hover' => 'hover:border-red-300',
                            ],
                            'orange' => [
                                'bg' => 'bg-orange-50',
                                'text' => 'text-orange-700',
                                'ring' => 'ring-orange-100',
                                'hover' => 'hover:border-orange-300',
                            ],
                            'rose' => [
                                'bg' => 'bg-rose-50',
                                'text' => 'text-rose-700',
                                'ring' => 'ring-rose-100',
                                'hover' => 'hover:border-rose-300',
                            ],
                        ];
                    @endphp

                    @foreach ($stockAlerts as $alert)
                        @php $style = $alertStyles[$alert['color']]; @endphp

                        <a href="{{ route('pharmacy.stock-batches.index', ['filter' => $alert['filter']]) }}"
                           class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md {{ $style['hover'] }}">

                            <div class="flex items-start justify-between">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl ring-1 {{ $style['bg'] }} {{ $style['text'] }} {{ $style['ring'] }}">
                                    @if ($alert['icon'] === 'low')
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                             stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M4 5h16v5H4zM4 14h16v5H4zM9 8h.01M9 17h.01"/>
                                        </svg>
                                    @elseif ($alert['icon'] === 'empty')
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                             stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M4 7 12 3l8 4v10l-8 4-8-4V7Zm0 0 8 4 8-4M12 11v10M7 8l10 8"/>
                                        </svg>
                                    @elseif ($alert['icon'] === 'calendar')
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                             stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M7 3v4m10-4v4M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Zm7 7v4m0 0 2-2"/>
                                        </svg>
                                    @else
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                             stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M12 3 2 21h20L12 3Zm0 7v5m0 3h.01"/>
                                        </svg>
                                    @endif
                                </div>

                                <span class="text-slate-300 transition group-hover:text-slate-600">
                                    &nearr;
                                </span>
                            </div>

                            <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ $alert['title'] }}
                            </p>

                            <p class="mt-2 text-3xl font-bold tabular-nums {{ $style['text'] }}">
                                {{ number_format($alert['count']) }}
                            </p>

                            <p class="mt-2 min-h-[40px] text-sm text-slate-500">
                                {{ $alert['description'] }}
                            </p>

                            <div class="mt-auto border-t border-slate-100 pt-4">
                                <span class="text-sm font-semibold {{ $style['text'] }}">
                                    {{ $alert['action'] }} &rarr;
                                </span>
                            </div>
                        </a>
                    @endforeach

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- QUICK ACTIONS --}}
            {{-- ========================================================= --}}

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">
                            Pharmacy Workspace
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Quick access to dispensing, medicines, inventory and disposals
                        </p>
                    </div>

                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        Quick Actions
                    </span>
                </div>

                @php
                    $pharmacyActions = [
                        [
                            'title' => 'New Dispensing',
                            'description' => 'Create a pharmacy sale',
                            'route' => 'pharmacy.dispensing.create',
                            'icon' => 'plus',
                            'style' => 'bg-blue-50 text-blue-700',
                        ],
                        [
                            'title' => 'Pharmacy Sales',
                            'description' => 'Review dispensing records',
                            'route' => 'pharmacy.dispensing.index',
                            'icon' => 'receipt',
                            'style' => 'bg-indigo-50 text-indigo-700',
                        ],
                        [
                            'title' => 'Medicine Master',
                            'description' => 'Manage medicine catalogue',
                            'route' => 'pharmacy.medicines.index',
                            'icon' => 'medicine',
                            'style' => 'bg-violet-50 text-violet-700',
                        ],
                        [
                            'title' => 'Pharmacy Stock',
                            'description' => 'Review inventory batches',
                            'route' => 'pharmacy.stock-batches.index',
                            'icon' => 'stock',
                            'style' => 'bg-emerald-50 text-emerald-700',
                        ],
                        [
                            'title' => 'Disposal Register',
                            'description' => 'Review medicine disposals',
                            'route' => 'pharmacy.disposals.index',
                            'icon' => 'disposal',
                            'style' => 'bg-rose-50 text-rose-700',
                        ],
                    ];
                @endphp

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    @foreach ($pharmacyActions as $action)
                        <a href="{{ route($action['route']) }}"
                           class="group flex min-h-[150px] flex-col rounded-xl border border-slate-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-slate-50 hover:shadow-md">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $action['style'] }}">
                                @if ($action['icon'] === 'plus')
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                         stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                                    </svg>
                                @elseif ($action['icon'] === 'receipt')
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                         stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M5 3h14v18l-3-2-4 2-4-2-3 2V3ZM8 8h8M8 12h8"/>
                                    </svg>
                                @elseif ($action['icon'] === 'medicine')
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                         stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M10 4a5 5 0 0 1 7 7l-6 6a5 5 0 0 1-7-7l6-6ZM7 7l7 7"/>
                                    </svg>
                                @elseif ($action['icon'] === 'stock')
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                         stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="m3 7 9-4 9 4v10l-9 4-9-4V7Zm0 0 9 4 9-4M12 11v10"/>
                                    </svg>
                                @else
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                                         stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M4 7h16M9 7V4h6v3m-9 0 1 13h10l1-13M10 11v5m4-5v5"/>
                                    </svg>
                                @endif
                            </div>

                            <h4 class="mt-4 text-sm font-bold text-slate-900 group-hover:text-indigo-700">
                                {{ $action['title'] }}
                            </h4>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ $action['description'] }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- ========================================================= --}}
            {{-- RECENT SALES --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">

                    <div>

                        <h3 class="text-lg font-bold text-slate-900">
                            Recent Pharmacy Sales
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Latest dispensing transactions
                        </p>

                    </div>


                    <a
                        href="{{ route('pharmacy.dispensing.index') }}"
                        class="inline-flex rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                    >
                        View All
                    </a>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[760px] w-full divide-y divide-slate-200 text-sm">

                        <thead class="bg-slate-100/80">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Sale
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Patient
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Time
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Payment
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($recentSales as $sale)

                                <tr class="transition hover:bg-indigo-50/40">

                                    <td class="px-6 py-4 font-semibold text-slate-900">
                                        {{ $sale->sale_no }}
                                    </td>

                                    <td class="px-4 py-4">

                                        <div class="font-medium text-slate-900">
                                            {{ $sale->patient?->full_name ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $sale->patient?->uhid ?? '—' }}
                                        </div>

                                    </td>

                                    <td class="px-4 py-4 text-sm text-slate-600">

                                        {{ $sale->sale_at?->format('d M Y') }}

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $sale->sale_at?->format('h:i A') }}
                                        </div>

                                    </td>

                                    <td class="px-4 py-4 text-sm font-medium text-slate-700">
                                        {{ strtoupper($sale->payment_mode ?? '—') }}
                                    </td>

                                    <td class="px-4 py-4 text-right font-bold text-slate-900">
                                        ₹{{ number_format((float) $sale->total_amount, 2) }}
                                    </td>

                                    <td class="px-4 py-4 text-right">

                                        <a
                                            href="{{ route('pharmacy.dispensing.show', $sale) }}"
                                            class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="6"
                                        class="px-6 py-10 text-center text-sm text-slate-500"
                                    >
                                        No pharmacy sales recorded.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- LOW STOCK + NEAR EXPIRY --}}
            {{-- ========================================================= --}}

            <div class="grid gap-6 xl:grid-cols-2">


                {{-- LOW STOCK --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">

                        <div>

                            <h3 class="text-lg font-bold text-slate-900">
                                Low Stock Alerts
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Stock has reached the configured reorder level
                            </p>

                        </div>

                        <a
                            href="{{ route('pharmacy.stock-batches.index', ['filter' => 'low_stock']) }}"
                            class="inline-flex shrink-0 items-center rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
                        >
                            View All
                        </a>

                    </div>


                    <div class="divide-y divide-slate-100">

                        @forelse ($lowStockBatches as $batch)

                            <div class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50">

                                <div>

                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $batch->medicine?->generic_name ?? 'Medicine' }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        Batch {{ $batch->batch_number }}
                                        · Reorder {{ $batch->reorder_level }}
                                    </div>

                                </div>


                                <div class="text-right">

                                    <div class="text-lg font-bold text-amber-700">
                                        {{ $batch->quantity_available }}
                                    </div>

                                    <div class="text-xs text-slate-400">
                                        remaining
                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="px-6 py-10 text-center text-sm text-slate-500">
                                No low-stock alerts.
                            </div>

                        @endforelse

                    </div>

                </div>



                {{-- NEAR EXPIRY --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">

                        <div>

                            <h3 class="text-lg font-bold text-slate-900">
                                Near Expiry
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Active stock expiring within 90 days
                            </p>

                        </div>

                        <a
                            href="{{ route('pharmacy.stock-batches.index', ['filter' => 'near_expiry']) }}"
                            class="inline-flex shrink-0 items-center rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
                        >
                            View All
                        </a>

                    </div>


                    <div class="divide-y divide-slate-100">

                        @forelse ($nearExpiryBatches as $batch)

                            <div class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50">

                                <div>

                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $batch->medicine?->generic_name ?? 'Medicine' }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        Batch {{ $batch->batch_number }}
                                        · Stock {{ $batch->quantity_available }}
                                    </div>

                                </div>


                                <div class="text-right">

                                    <div class="font-bold text-orange-700">
                                        {{ $batch->expiry_date?->format('d M Y') ?? '—' }}
                                    </div>

                                    @if ($batch->expiry_date)

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{
                                                today()->diffInDays(
                                                    $batch->expiry_date,
                                                    false
                                                )
                                            }}
                                            days
                                        </div>

                                    @endif

                                </div>

                            </div>

                        @empty

                            <div class="px-6 py-10 text-center text-sm text-slate-500">
                                No stock expiring within 90 days.
                            </div>

                        @endforelse

                    </div>

                </div>


            </div>



            {{-- ========================================================= --}}
            {{-- EXPIRED STOCK --}}
            {{-- ========================================================= --}}

            @if ($expiredCount > 0)

                <div class="overflow-hidden rounded-2xl border border-red-200 bg-white shadow-sm">

                    <div class="flex flex-col gap-3 border-b border-red-100 bg-red-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="flex items-center gap-2 text-lg font-bold text-red-900">
                                <span aria-hidden="true">⚠</span> Expired Stock Requiring Attention
                            </h3>

                            <p class="mt-1 text-xs text-red-700">
                                These batches are expired but still have stock remaining.
                                They must not be dispensed.
                            </p>

                        </div>

                        <a
                            href="{{ route('pharmacy.stock-batches.index', ['filter' => 'expired']) }}"
                            class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                        >
                            Review All
                        </a>

                    </div>


                    <div class="divide-y divide-red-100">

                        @foreach ($expiredBatches as $batch)

                            <div class="flex flex-col gap-3 px-6 py-4 transition hover:bg-red-50/50 sm:flex-row sm:items-center sm:justify-between">

                                <div>

                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $batch->medicine?->generic_name ?? 'Medicine' }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        Batch {{ $batch->batch_number }}
                                        · Stock {{ $batch->quantity_available }}
                                    </div>

                                </div>


                                <div class="text-left sm:text-right">

                                    <div class="font-bold text-red-700">
                                        {{ $batch->expiry_date?->format('d M Y') ?? '—' }}
                                    </div>

                                    @if ($batch->expiry_date)

                                        <div class="mt-1 text-xs text-red-600">
                                            Expired
                                            {{
                                                abs(
                                                    today()->diffInDays(
                                                        $batch->expiry_date,
                                                        false
                                                    )
                                                )
                                            }}
                                            days ago
                                        </div>

                                    @endif

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- RECENT RETURNS --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="text-lg font-bold text-slate-900">
                        Recent Returns
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Latest pharmacy return and refund transactions
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[860px] w-full divide-y divide-slate-200 text-sm">

                        <thead class="bg-slate-100/80">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Return
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Original Sale
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Patient
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Reason
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Refund
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($recentReturns as $return)

                                <tr class="transition hover:bg-indigo-50/40">

                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $return->return_no }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $return->returned_at?->format('d M Y, h:i A') }}
                                        </div>

                                    </td>

                                    <td class="px-4 py-4 text-sm font-medium text-slate-700">
                                        {{ $return->sale?->sale_no ?? '—' }}
                                    </td>

                                    <td class="px-4 py-4">

                                        <div class="font-medium text-slate-900">
                                            {{ $return->patient?->full_name ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $return->patient?->uhid ?? '—' }}
                                        </div>

                                    </td>

                                    <td class="px-4 py-4 text-sm text-slate-600">
                                        {{ $return->reason }}
                                    </td>

                                    <td class="px-4 py-4 text-right font-bold text-red-700">
                                        ₹{{ number_format((float) $return->refund_amount, 2) }}
                                    </td>

                                    <td class="px-4 py-4 text-right">

                                        <a
                                            href="{{ route('pharmacy.returns.receipt', $return) }}"
                                            class="inline-flex rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                                        >
                                            Receipt
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="6"
                                        class="px-6 py-10 text-center text-sm text-slate-500"
                                    >
                                        No pharmacy returns recorded.
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