<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Internal Stock Transfers
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Transfer medicines between Central Store, Pharmacy and wards.
                </p>
            </div>

            <a
                href="{{ route('pharmacy.stock-transfers.create') }}"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                New Transfer
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


            <div class="mb-6 rounded-xl bg-white p-5 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('pharmacy.stock-transfers.index') }}"
                    class="grid gap-4 md:grid-cols-4"
                >
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Transfer no. or location..."
                            class="w-full rounded-lg border-gray-300"
                        >
                    </div>


                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border-gray-300"
                        >
                            <option value="">All Statuses</option>

                            @foreach (['draft', 'issued', 'received', 'cancelled'] as $status)
                                <option
                                    value="{{ $status }}"
                                    @selected(request('status') === $status)
                                >
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('pharmacy.stock-transfers.index') }}"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>


            <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Transfer
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Date
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    From
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    To
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500">
                                    Items
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($transfers as $transfer)

                                @php
                                    $statusClasses = match ($transfer->status) {
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

                                <tr class="hover:bg-gray-50">

                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">
                                            {{ $transfer->transfer_no }}
                                        </div>

                                        <div class="text-xs text-gray-500">
                                            {{ $transfer->createdBy?->name }}
                                        </div>
                                    </td>


                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $transfer->transfer_date?->format('d M Y') }}
                                    </td>


                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $transfer->fromLocation?->name }}
                                    </td>


                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $transfer->toLocation?->name }}
                                    </td>


                                    <td class="px-4 py-3 text-center text-sm text-gray-700">
                                        {{ $transfer->items_count }}
                                    </td>


                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ ucfirst($transfer->status) }}
                                        </span>
                                    </td>


                                    <td class="px-4 py-3 text-right">
                                        <a
                                            href="{{ route('pharmacy.stock-transfers.show', $transfer) }}"
                                            class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                                        >
                                            View
                                        </a>
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-10 text-center text-sm text-gray-500"
                                    >
                                        No stock transfers found.
                                    </td>
                                </tr>

                            @endforelse
                        </tbody>
                    </table>
                </div>


                @if ($transfers->hasPages())
                    <div class="border-t border-gray-200 p-4">
                        {{ $transfers->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>