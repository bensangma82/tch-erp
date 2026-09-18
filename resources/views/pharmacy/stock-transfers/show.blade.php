<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ $pharmacyStockTransfer->transfer_no }}
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Internal stock transfer
                </p>
            </div>


            <a
                href="{{ route('pharmacy.stock-transfers.index') }}"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
            >
                Back to Transfers
            </a>

        </div>
    </x-slot>


    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif


            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif


            @php
                $statusClasses = match ($pharmacyStockTransfer->status) {
                    'draft' =>
                        'bg-gray-100 text-gray-700',

                    'issued' =>
                        'bg-amber-100 text-amber-800',

                    'received' =>
                        'bg-green-100 text-green-800',

                    'cancelled' =>
                        'bg-red-100 text-red-800',

                    default =>
                        'bg-gray-100 text-gray-700',
                };
            @endphp


            <div class="mb-6 grid gap-5 lg:grid-cols-4">

                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase text-gray-500">
                        Status
                    </div>

                    <div class="mt-3">
                        <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $statusClasses }}">
                            {{ ucfirst($pharmacyStockTransfer->status) }}
                        </span>
                    </div>
                </div>


                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase text-gray-500">
                        From
                    </div>

                    <div class="mt-2 text-lg font-semibold text-gray-900">
                        {{ $pharmacyStockTransfer->fromLocation?->name }}
                    </div>
                </div>


                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase text-gray-500">
                        To
                    </div>

                    <div class="mt-2 text-lg font-semibold text-gray-900">
                        {{ $pharmacyStockTransfer->toLocation?->name }}
                    </div>
                </div>


                <div class="rounded-xl bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase text-gray-500">
                        Transfer Date
                    </div>

                    <div class="mt-2 text-lg font-semibold text-gray-900">
                        {{ $pharmacyStockTransfer->transfer_date?->format('d M Y') }}
                    </div>
                </div>

            </div>


            <div class="mb-6 overflow-hidden rounded-xl bg-white shadow-sm">

                <div class="border-b border-gray-200 p-5">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Transfer Items
                    </h3>
                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Medicine
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Batch
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Expiry
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                    Requested
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                    Issued
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                    Received
                                </th>
                            </tr>
                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @foreach ($pharmacyStockTransfer->items as $item)

                                <tr>

                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">
                                            {{ $item->medicine_name }}
                                        </div>

                                        @if ($item->brand_name)
                                            <div class="text-xs text-gray-500">
                                                {{ $item->brand_name }}

                                                @if ($item->strength)
                                                    — {{ $item->strength }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>


                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $item->batch_number }}
                                    </td>


                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $item->expiry_date?->format('d M Y') ?? '—' }}
                                    </td>


                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        {{ number_format($item->quantity_requested) }}
                                    </td>


                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        {{ number_format($item->quantity_issued) }}
                                    </td>


                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        {{ number_format($item->quantity_received) }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>


            <div class="mb-6 grid gap-5 lg:grid-cols-2">

                <div class="rounded-xl bg-white p-5 shadow-sm">

                    <h3 class="mb-4 font-semibold text-gray-900">
                        Created / Issued
                    </h3>

                    <div class="space-y-3 text-sm">

                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">
                                Created by
                            </span>

                            <span class="font-medium text-gray-900">
                                {{ $pharmacyStockTransfer->createdBy?->name ?? '—' }}
                            </span>
                        </div>


                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">
                                Issued by
                            </span>

                            <span class="font-medium text-gray-900">
                                {{ $pharmacyStockTransfer->issuedBy?->name ?? '—' }}
                            </span>
                        </div>


                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">
                                Issued at
                            </span>

                            <span class="font-medium text-gray-900">
                                {{ $pharmacyStockTransfer->issued_at?->format('d M Y, h:i A') ?? '—' }}
                            </span>
                        </div>

                    </div>

                </div>


                <div class="rounded-xl bg-white p-5 shadow-sm">

                    <h3 class="mb-4 font-semibold text-gray-900">
                        Receipt
                    </h3>

                    <div class="space-y-3 text-sm">

                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">
                                Received by
                            </span>

                            <span class="font-medium text-gray-900">
                                {{ $pharmacyStockTransfer->receivedBy?->name ?? '—' }}
                            </span>
                        </div>


                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">
                                Received at
                            </span>

                            <span class="font-medium text-gray-900">
                                {{ $pharmacyStockTransfer->received_at?->format('d M Y, h:i A') ?? '—' }}
                            </span>
                        </div>

                    </div>

                </div>

            </div>


            @if ($pharmacyStockTransfer->remarks)
                <div class="mb-6 rounded-xl bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase text-gray-500">
                        Remarks
                    </div>

                    <div class="mt-2 text-sm text-gray-800">
                        {{ $pharmacyStockTransfer->remarks }}
                    </div>

                </div>
            @endif


            @if ($pharmacyStockTransfer->status === 'draft')

                <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm md:flex-row md:items-end md:justify-between">

                    <form
                        method="POST"
                        action="{{ route(
                            'pharmacy.stock-transfers.issue',
                            $pharmacyStockTransfer
                        ) }}"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            onclick="return confirm('Issue this stock from the source location?')"
                            class="rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-amber-700"
                        >
                            Issue Stock
                        </button>
                    </form>


                    <form
                        method="POST"
                        action="{{ route(
                            'pharmacy.stock-transfers.cancel',
                            $pharmacyStockTransfer
                        ) }}"
                        class="flex flex-1 flex-col gap-2 md:max-w-xl md:flex-row"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="text"
                            name="cancellation_reason"
                            required
                            placeholder="Reason for cancellation"
                            class="flex-1 rounded-lg border-gray-300 text-sm"
                        >

                        <button
                            type="submit"
                            class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
                        >
                            Cancel Transfer
                        </button>
                    </form>

                </div>

            @elseif ($pharmacyStockTransfer->status === 'issued')

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                        <div>
                            <h3 class="font-semibold text-amber-900">
                                Awaiting Receipt
                            </h3>

                            <p class="mt-1 text-sm text-amber-800">
                                Stock has left the source location and is currently in transit.
                            </p>
                        </div>


                        <form
                            method="POST"
                            action="{{ route(
                                'pharmacy.stock-transfers.receive',
                                $pharmacyStockTransfer
                            ) }}"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                onclick="return confirm('Confirm that the destination has physically received this stock?')"
                                class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700"
                            >
                                Receive Stock
                            </button>
                        </form>

                    </div>

                </div>

            @elseif ($pharmacyStockTransfer->status === 'received')

                <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-sm text-green-800">
                    Transfer completed. Stock has been received into
                    <strong>
                        {{ $pharmacyStockTransfer->toLocation?->name }}
                    </strong>.
                </div>

            @elseif ($pharmacyStockTransfer->status === 'cancelled')

                <div class="rounded-xl border border-red-200 bg-red-50 p-5">

                    <div class="font-semibold text-red-900">
                        Transfer Cancelled
                    </div>

                    <div class="mt-2 text-sm text-red-800">
                        {{ $pharmacyStockTransfer->cancellation_reason }}
                    </div>

                </div>

            @endif

        </div>
    </div>
</x-app-layout>