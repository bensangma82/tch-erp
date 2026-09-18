<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Pharmacy Dashboard
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Pharmacy sales, returns, stock and expiry overview
                </p>

            </div>


            <a
                href="{{ route('pharmacy.dispensing.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                New Dispensing
            </a>

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


                    {{-- GROSS SALES --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Gross Sales
                        </div>

                        <div class="mt-3 text-3xl font-bold text-slate-900">
                            ₹{{ number_format((float) $todaySalesAmount, 2) }}
                        </div>

                        <div class="mt-2 text-sm text-slate-500">
                            {{ number_format($todayDispensingCount) }}
                            dispensing transactions
                        </div>

                    </div>



                    {{-- RETURNS --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Returns / Refunds
                        </div>

                        <div class="mt-3 text-3xl font-bold text-red-700">
                            ₹{{ number_format((float) $todayRefundAmount, 2) }}
                        </div>

                        <div class="mt-2 text-sm text-slate-500">
                            {{ number_format($todayReturnCount) }}
                            return transactions
                        </div>

                    </div>



                    {{-- NET SALES --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Net Pharmacy Sales
                        </div>

                        <div class="mt-3 text-3xl font-bold text-emerald-700">
                            ₹{{ number_format((float) $todayNetSales, 2) }}
                        </div>

                        <div class="mt-2 text-sm text-slate-500">
                            Gross sales less completed refunds
                        </div>

                    </div>


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


                    {{-- LOW STOCK --}}
                    <a
                        href="{{ route('pharmacy.stock-batches.index', ['filter' => 'low_stock']) }}"
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-amber-300 hover:shadow-md"
                    >

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Low Stock
                        </div>

                        <div class="mt-3 text-3xl font-bold text-amber-700">
                            {{ number_format($lowStockCount) }}
                        </div>

                        <div class="mt-2 text-sm text-slate-500">
                            At or below reorder level
                        </div>

                        <div class="mt-4 text-xs font-semibold text-amber-700">
                            View low stock →
                        </div>

                    </a>



                    {{-- OUT OF STOCK --}}
                    <a
                        href="{{ route('pharmacy.stock-batches.index', ['filter' => 'out_of_stock']) }}"
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-red-300 hover:shadow-md"
                    >

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Out of Stock
                        </div>

                        <div class="mt-3 text-3xl font-bold text-red-700">
                            {{ number_format($outOfStockCount) }}
                        </div>

                        <div class="mt-2 text-sm text-slate-500">
                            Active batches with zero stock
                        </div>

                        <div class="mt-4 text-xs font-semibold text-red-700">
                            View out of stock →
                        </div>

                    </a>



                    {{-- NEAR EXPIRY --}}
                    <a
                        href="{{ route('pharmacy.stock-batches.index', ['filter' => 'near_expiry']) }}"
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-orange-300 hover:shadow-md"
                    >

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Near Expiry
                        </div>

                        <div class="mt-3 text-3xl font-bold text-orange-700">
                            {{ number_format($nearExpiryCount) }}
                        </div>

                        <div class="mt-2 text-sm text-slate-500">
                            Expiring within 90 days
                        </div>

                        <div class="mt-4 text-xs font-semibold text-orange-700">
                            View near expiry →
                        </div>

                    </a>



                    {{-- EXPIRED STOCK --}}
                    <a
                        href="{{ route('pharmacy.stock-batches.index', ['filter' => 'expired']) }}"
                        class="rounded-2xl border border-red-200 bg-red-50/40 p-5 shadow-sm transition hover:border-red-400 hover:shadow-md"
                    >

                        <div class="text-xs font-semibold uppercase tracking-wide text-red-500">
                            Expired Stock
                        </div>

                        <div class="mt-3 text-3xl font-bold text-red-800">
                            {{ number_format($expiredCount) }}
                        </div>

                        <div class="mt-2 text-sm text-red-700">
                            Expired batches with stock remaining
                        </div>

                        <div class="mt-4 text-xs font-semibold text-red-800">
                            Review expired stock →
                        </div>

                    </a>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- QUICK ACTIONS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h3 class="font-bold text-slate-900">
                    Quick Actions
                </h3>


                <div class="mt-4 flex flex-wrap gap-3">

                    <a
                        href="{{ route('pharmacy.dispensing.create') }}"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                    >
                        New Dispensing
                    </a>

                    <a
                        href="{{ route('pharmacy.dispensing.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Pharmacy Sales
                    </a>

                    <a
                        href="{{ route('pharmacy.medicines.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Medicine Master
                    </a>

                    <a
                        href="{{ route('pharmacy.stock-batches.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Pharmacy Stock
                    </a>

                    <a
    href="{{ route('pharmacy.disposals.index') }}"
    class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100"
>
    Disposal Register
</a>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- RECENT SALES --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">

                    <div>

                        <h3 class="font-bold text-slate-900">
                            Recent Pharmacy Sales
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Latest dispensing transactions
                        </p>

                    </div>


                    <a
                        href="{{ route('pharmacy.dispensing.index') }}"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                    >
                        View All
                    </a>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

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


                        <tbody class="divide-y divide-slate-100">

                            @forelse ($recentSales as $sale)

                                <tr>

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
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">

                        <div>

                            <h3 class="font-bold text-slate-900">
                                Low Stock Alerts
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Stock has reached the configured reorder level
                            </p>

                        </div>

                        <a
                            href="{{ route('pharmacy.stock-batches.index', ['filter' => 'low_stock']) }}"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-800"
                        >
                            View All
                        </a>

                    </div>


                    <div class="divide-y divide-slate-100">

                        @forelse ($lowStockBatches as $batch)

                            <div class="flex items-center justify-between gap-4 px-6 py-4">

                                <div>

                                    <div class="font-semibold text-slate-900">
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
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">

                        <div>

                            <h3 class="font-bold text-slate-900">
                                Near Expiry
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Active stock expiring within 90 days
                            </p>

                        </div>

                        <a
                            href="{{ route('pharmacy.stock-batches.index', ['filter' => 'near_expiry']) }}"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-800"
                        >
                            View All
                        </a>

                    </div>


                    <div class="divide-y divide-slate-100">

                        @forelse ($nearExpiryBatches as $batch)

                            <div class="flex items-center justify-between gap-4 px-6 py-4">

                                <div>

                                    <div class="font-semibold text-slate-900">
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

                            <h3 class="font-bold text-red-900">
                                Expired Stock Requiring Attention
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

                            <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

                                <div>

                                    <div class="font-semibold text-slate-900">
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

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-bold text-slate-900">
                        Recent Returns
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Latest pharmacy return and refund transactions
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

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


                        <tbody class="divide-y divide-slate-100">

                            @forelse ($recentReturns as $return)

                                <tr>

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
                                            class="text-sm font-semibold text-blue-600 hover:text-blue-800"
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