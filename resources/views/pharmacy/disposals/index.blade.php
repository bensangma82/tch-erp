<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Pharmacy Disposal Register
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Complete register of pharmacy stock write-offs and disposals
                </p>
            </div>

            <a
                href="{{ route('pharmacy.stock-batches.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Pharmacy Stock
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('pharmacy.disposals.index') }}"
                    class="grid gap-4 md:grid-cols-2 lg:grid-cols-5"
                >

                    <div class="lg:col-span-2">

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Disposal no, medicine, brand or batch"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            From Date
                        </label>

                        <input
                            type="date"
                            name="from_date"
                            value="{{ request('from_date') }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            To Date
                        </label>

                        <input
                            type="date"
                            name="to_date"
                            value="{{ request('to_date') }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Reason
                        </label>

                        <select
                            name="reason"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        >

                            <option value="">
                                All reasons
                            </option>

                            <option value="expired" @selected(request('reason') === 'expired')>
                                Expired
                            </option>

                            <option value="damaged" @selected(request('reason') === 'damaged')>
                                Damaged
                            </option>

                            <option value="contaminated" @selected(request('reason') === 'contaminated')>
                                Contaminated
                            </option>

                            <option value="broken" @selected(request('reason') === 'broken')>
                                Broken / Spillage
                            </option>

                            <option value="recall" @selected(request('reason') === 'recall')>
                                Recall
                            </option>

                            <option value="other" @selected(request('reason') === 'other')>
                                Other
                            </option>

                        </select>

                    </div>


                    <div class="flex items-end gap-2 lg:col-span-5">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Apply Filters
                        </button>

                        <a
                            href="{{ route('pharmacy.disposals.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>



            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Date
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Disposal No.
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Medicine / Batch
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Reason
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Qty
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Write-off Value
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    User
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($disposals as $disposal)

                                @php
                                    $totalQty =
                                        $disposal->items->sum('quantity');

                                    $totalValue =
                                        $disposal->items->sum('stock_value');
                                @endphp


                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-5 py-4">

                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ $disposal->disposed_at?->format('d M Y') }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $disposal->disposed_at?->format('h:i A') }}
                                        </div>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-800">
                                        {{ $disposal->disposal_no }}
                                    </td>


                                    <td class="px-5 py-4">

                                        @foreach ($disposal->items as $item)

                                            <div class="{{ !$loop->first ? 'mt-3 border-t border-slate-100 pt-3' : '' }}">

                                                <div class="text-sm font-semibold text-slate-800">
                                                    {{ $item->medicine_name }}
                                                </div>

                                                @if ($item->brand_name)
                                                    <div class="text-xs text-slate-500">
                                                        {{ $item->brand_name }}
                                                    </div>
                                                @endif

                                                <div class="mt-1 text-xs text-slate-400">
                                                    Batch {{ $item->batch_number }}
                                                </div>

                                            </div>

                                        @endforeach

                                    </td>


                                    <td class="px-5 py-4">

                                        <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">

                                            {{
                                                match ($disposal->reason) {
                                                    'expired' => 'Expired',
                                                    'damaged' => 'Damaged',
                                                    'contaminated' => 'Contaminated',
                                                    'broken' => 'Broken / Spillage',
                                                    'recall' => 'Recall',
                                                    'other' => 'Other',
                                                    default => ucwords(str_replace('_', ' ', $disposal->reason)),
                                                }
                                            }}

                                        </span>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-slate-900">
                                        {{ number_format($totalQty) }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-red-700">
                                        ₹{{ number_format((float) $totalValue, 2) }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                        {{ $disposal->createdBy?->name ?? 'System' }}
                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <a
                                            href="{{ route('pharmacy.disposals.receipt', $disposal) }}"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            View / Print
                                        </a>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="8"
                                        class="px-6 py-12 text-center text-sm text-slate-500"
                                    >
                                        No pharmacy disposal records found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($disposals->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $disposals->links() }}
                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>