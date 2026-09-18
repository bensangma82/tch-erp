<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Pharmacy Stock
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage medicine batches, expiry dates and available stock
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('pharmacy.dashboard') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Pharmacy Dashboard
                </a>

                <a
                    href="{{ route('pharmacy.medicines.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Medicine Master
                </a>

                <a
                    href="{{ route('pharmacy.stock-batches.create') }}"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                >
                    Add Stock Batch
                </a>

            </div>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-[1500px] space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- OPERATIONAL FILTER CARDS --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">


                {{-- LOW STOCK --}}
                <a
                    href="{{ route('pharmacy.stock-batches.index', ['filter' => 'low_stock']) }}"
                    class="rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md
                        {{ $filter === 'low_stock'
                            ? 'border-amber-400 ring-2 ring-amber-100'
                            : 'border-slate-200 hover:border-amber-300'
                        }}"
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

                </a>



                {{-- OUT OF STOCK --}}
                <a
                    href="{{ route('pharmacy.stock-batches.index', ['filter' => 'out_of_stock']) }}"
                    class="rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md
                        {{ $filter === 'out_of_stock'
                            ? 'border-red-400 ring-2 ring-red-100'
                            : 'border-slate-200 hover:border-red-300'
                        }}"
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

                </a>



                {{-- NEAR EXPIRY --}}
                <a
                    href="{{ route('pharmacy.stock-batches.index', ['filter' => 'near_expiry']) }}"
                    class="rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md
                        {{ $filter === 'near_expiry'
                            ? 'border-orange-400 ring-2 ring-orange-100'
                            : 'border-slate-200 hover:border-orange-300'
                        }}"
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

                </a>



                {{-- EXPIRED --}}
                <a
                    href="{{ route('pharmacy.stock-batches.index', ['filter' => 'expired']) }}"
                    class="rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md
                        {{ $filter === 'expired'
                            ? 'border-rose-500 ring-2 ring-rose-100'
                            : 'border-slate-200 hover:border-rose-300'
                        }}"
                >

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Expired Stock
                    </div>

                    <div class="mt-3 text-3xl font-bold text-rose-700">
                        {{ number_format($expiredCount) }}
                    </div>

                    <div class="mt-2 text-sm text-slate-500">
                        Expired batches with stock remaining
                    </div>

                </a>


            </div>



            {{-- ========================================================= --}}
            {{-- ACTIVE FILTER MESSAGE --}}
            {{-- ========================================================= --}}

            @if ($filter)

                @php
                    $filterLabel = match ($filter) {
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                        'near_expiry' => 'Near Expiry',
                        'expired' => 'Expired Stock',
                        default => 'Filtered Stock',
                    };
                @endphp

                <div class="flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <div class="text-sm font-semibold text-blue-900">
                            Showing: {{ $filterLabel }}
                        </div>

                        <div class="mt-1 text-xs text-blue-700">
                            Search and status filters can be used together with this operational filter.
                        </div>

                    </div>

                    <a
                        href="{{ route('pharmacy.stock-batches.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                    >
                        Show All Stock
                    </a>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- STOCK LIST --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                {{-- SEARCH --}}
                <div class="border-b border-slate-100 px-6 py-5">

                    <form
                        method="GET"
                        action="{{ route('pharmacy.stock-batches.index') }}"
                        class="grid gap-4 md:grid-cols-[1fr_220px_auto]"
                    >

                        @if ($filter)
                            <input
                                type="hidden"
                                name="filter"
                                value="{{ $filter }}"
                            >
                        @endif


                        <div>

                            <label
                                for="search"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Search Stock
                            </label>

                            <input
                                id="search"
                                name="search"
                                type="text"
                                value="{{ $search }}"
                                placeholder="Medicine, code or batch number"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div>

                            <label
                                for="status"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Status
                            </label>

                            <select
                                id="status"
                                name="status"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    All batches
                                </option>

                                <option
                                    value="active"
                                    @selected($status === 'active')
                                >
                                    Active
                                </option>

                                <option
                                    value="inactive"
                                    @selected($status === 'inactive')
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="flex items-end gap-2">

                            <button
                                type="submit"
                                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                Filter
                            </button>

                            @if (
                                $search !== ''
                                || $status
                                || $filter
                            )

                                <a
                                    href="{{ route('pharmacy.stock-batches.index') }}"
                                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Clear
                                </a>

                            @endif

                        </div>

                    </form>

                </div>



                {{-- TABLE --}}
                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Medicine
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Batch
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Expiry
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Received
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Available
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Purchase
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Selling
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Stock Status
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($batches as $batch)

                                @php

                                    $available =
                                        (int) $batch->quantity_available;

                                    $reorder =
                                        (int) $batch->reorder_level;


                                    /*
                                     * Low stock means some stock remains.
                                     * Zero stock is shown separately as Out of Stock.
                                     */
                                    $isLowStock =
                                        $available > 0
                                        && $available <= $reorder;


                                    $isOutOfStock =
                                        $available <= 0;


                                    $isExpired =
                                        $batch->expiry_date
                                        && $batch->expiry_date
                                            ->copy()
                                            ->startOfDay()
                                            ->lt(today());


                                    $isNearExpiry =
                                        $batch->expiry_date
                                        && ! $isExpired
                                        && $batch->expiry_date
                                            ->copy()
                                            ->startOfDay()
                                            ->lte(
                                                today()
                                                    ->copy()
                                                    ->addDays(90)
                                            );


                                    $rowClass = '';

                                    if (
                                        $batch->is_active
                                        && $isExpired
                                        && $available > 0
                                    ) {
                                        $rowClass =
                                            'bg-red-50/40';
                                    } elseif (
                                        $batch->is_active
                                        && $isNearExpiry
                                        && $available > 0
                                    ) {
                                        $rowClass =
                                            'bg-orange-50/30';
                                    } elseif (
                                        $batch->is_active
                                        && $isOutOfStock
                                    ) {
                                        $rowClass =
                                            'bg-red-50/20';
                                    } elseif (
                                        $batch->is_active
                                        && $isLowStock
                                    ) {
                                        $rowClass =
                                            'bg-amber-50/30';
                                    }

                                @endphp


                                <tr class="transition hover:bg-slate-50 {{ $rowClass }}">


                                    {{-- MEDICINE --}}
                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $batch->medicine->generic_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">

                                            @if ($batch->medicine->brand_name)

                                                {{ $batch->medicine->brand_name }}

                                            @else

                                                {{ $batch->medicine->code }}

                                            @endif

                                        </div>

                                    </td>



                                    {{-- BATCH --}}
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-700">
                                        {{ $batch->batch_number }}
                                    </td>



                                    {{-- EXPIRY --}}
                                    <td class="px-6 py-4">

                                        @if ($batch->expiry_date)

                                            <div class="text-sm text-slate-700">
                                                {{ $batch->expiry_date->format('d M Y') }}
                                            </div>


                                            @if ($isExpired)

                                                <div class="mt-1 text-xs font-bold text-red-700">
                                                    Expired
                                                </div>


                                                @if ($available > 0)

                                                    <div class="mt-1 text-[11px] font-semibold text-red-600">
                                                        {{ number_format($available) }}
                                                        remaining
                                                    </div>

                                                @endif


                                            @elseif ($isNearExpiry)

                                                @php
                                                    $daysToExpiry =
                                                        today()
                                                            ->diffInDays(
                                                                $batch->expiry_date,
                                                                false
                                                            );
                                                @endphp

                                                <div class="mt-1 text-xs font-semibold text-orange-600">
                                                    Near expiry
                                                </div>

                                                <div class="mt-1 text-[11px] text-slate-400">
                                                    {{ $daysToExpiry }}
                                                    days remaining
                                                </div>

                                            @endif

                                        @else

                                            <span class="text-sm text-slate-400">
                                                —
                                            </span>

                                        @endif

                                    </td>



                                    {{-- RECEIVED --}}
                                    <td class="px-6 py-4 text-right text-sm text-slate-700">
                                        {{ number_format($batch->quantity_received) }}
                                    </td>



                                    {{-- AVAILABLE --}}
                                    <td class="px-6 py-4 text-right">

                                        <div
                                            class="text-sm font-bold
                                            {{
                                                $isOutOfStock
                                                    ? 'text-red-700'
                                                    : (
                                                        $isLowStock
                                                            ? 'text-amber-700'
                                                            : 'text-slate-900'
                                                    )
                                            }}"
                                        >
                                            {{ number_format($available) }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            Reorder {{ number_format($reorder) }}
                                        </div>

                                    </td>



                                    {{-- PURCHASE --}}
                                    <td class="px-6 py-4 text-right text-sm text-slate-700">
                                        ₹{{ number_format((float) $batch->purchase_price, 2) }}
                                    </td>



                                    {{-- SELLING --}}
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-slate-900">
                                        ₹{{ number_format((float) $batch->selling_price, 2) }}
                                    </td>



                                    {{-- STATUS --}}
                                    <td class="px-6 py-4">

                                        <div class="flex flex-col items-start gap-1">


                                            @if (! $batch->is_active)

                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                    Inactive
                                                </span>


                                            @elseif ($isExpired)

                                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">
                                                    Expired
                                                </span>


                                            @elseif ($isOutOfStock)

                                                <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                                    Out of Stock
                                                </span>


                                            @elseif ($isLowStock)

                                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                                    Low Stock
                                                </span>


                                            @else

                                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    Available
                                                </span>

                                            @endif


                                            @if (
                                                $batch->is_active
                                                && $isNearExpiry
                                                && ! $isExpired
                                            )

                                                <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700">
                                                    Near Expiry
                                                </span>

                                            @endif

                                        </div>

                                    </td>



                                    {{-- ACTIONS --}}
                                    <td class="px-6 py-4">

                                        <div class="flex items-center justify-end gap-2">

                                            <a
                                                href="{{ route('pharmacy.stock-batches.movements', $batch) }}"
                                                class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                            >
                                                History
                                            </a>

<a
    href="{{ route('pharmacy.stock-batches.adjust', $batch) }}"
    class="rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700 transition hover:bg-purple-100"
>
    Adjust
</a>

<a
    href="{{ route('pharmacy.disposals.create', $batch) }}"
    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
>
    Dispose
</a>
                                            <a
                                                href="{{ route('pharmacy.stock-batches.edit', $batch) }}"
                                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Edit
                                            </a>


                                            <form
                                                method="POST"
                                                action="{{ route('pharmacy.stock-batches.status', $batch) }}"
                                                onsubmit="return confirm('{{ $batch->is_active ? 'Deactivate this stock batch?' : 'Activate this stock batch?' }}');"
                                            >

                                                @csrf
                                                @method('PATCH')


                                                @if ($batch->is_active)

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                                    >
                                                        Deactivate
                                                    </button>

                                                @else

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                    >
                                                        Activate
                                                    </button>

                                                @endif

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="9"
                                        class="px-6 py-12 text-center"
                                    >

                                        <div class="text-sm font-semibold text-slate-700">

                                            @if ($filter === 'low_stock')
                                                No low-stock batches found.
                                            @elseif ($filter === 'out_of_stock')
                                                No out-of-stock batches found.
                                            @elseif ($filter === 'near_expiry')
                                                No batches are expiring within 90 days.
                                            @elseif ($filter === 'expired')
                                                No expired batches with stock remaining.
                                            @else
                                                No stock batches found.
                                            @endif

                                        </div>

                                        @if ($filter)

                                            <a
                                                href="{{ route('pharmacy.stock-batches.index') }}"
                                                class="mt-3 inline-block text-sm font-semibold text-blue-600 hover:text-blue-800"
                                            >
                                                View all stock
                                            </a>

                                        @endif

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>



                {{-- PAGINATION --}}
                @if ($batches->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $batches->links() }}
                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>