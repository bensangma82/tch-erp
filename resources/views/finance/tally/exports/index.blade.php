<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Tally Voucher Export
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Review posted Finance vouchers before exporting them to Tally.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('finance.tally.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Ledger Mappings
                </a>

                <a
                    href="{{ route('finance.vouchers.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Finance Vouchers
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Information --}}
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h3 class="text-sm font-semibold text-blue-900">
                    Tally Export Readiness
                </h3>

                <p class="mt-1 text-sm text-blue-800">
                    Only posted Finance vouchers with all required active Tally
                    ledger mappings are eligible for export. Blocked vouchers
                    show the configuration that must be completed first.
                </p>
            </div>

            {{-- Filters --}}
            <div class="mb-6 rounded-lg bg-white p-5 shadow">
                <form
                    method="GET"
                    action="{{ route('finance.tally.exports.index') }}"
                    class="grid grid-cols-1 gap-4 md:grid-cols-4"
                >
                    <div>
                        <label
                            for="voucher_type"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Voucher Type
                        </label>

                        <select
                            id="voucher_type"
                            name="voucher_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Types</option>

                            <option
                                value="receipt"
                                @selected(request('voucher_type') === 'receipt')
                            >
                                Receipt
                            </option>

                            <option
                                value="payment"
                                @selected(request('voucher_type') === 'payment')
                            >
                                Payment
                            </option>

                            <option
                                value="transfer"
                                @selected(request('voucher_type') === 'transfer')
                            >
                                Transfer / Contra
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="date_from"
                            class="block text-sm font-medium text-gray-700"
                        >
                            From Date
                        </label>

                        <input
                            id="date_from"
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label
                            for="date_to"
                            class="block text-sm font-medium text-gray-700"
                        >
                            To Date
                        </label>

                        <input
                            id="date_to"
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('finance.tally.exports.index') }}"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            {{-- Voucher table --}}
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Date
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Voucher
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Finance Head
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Account
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Amount
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Tally Status
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($vouchers as $voucher)
                                @php
                                    $check = $eligibility[$voucher->id] ?? [
                                        'eligible' => false,
                                        'errors' => ['Eligibility could not be determined.'],
                                    ];

                                    $export = $voucher->tallyExport;
                                @endphp

                                <tr class="align-top hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        {{ $voucher->voucher_date?->format('d-m-Y') }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4">
                                        <a
                                            href="{{ route('finance.vouchers.show', $voucher) }}"
                                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-900"
                                        >
                                            {{ $voucher->voucher_no }}
                                        </a>
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4">
                                        @if ($voucher->voucher_type === 'receipt')
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                                Receipt
                                            </span>
                                        @elseif ($voucher->voucher_type === 'payment')
                                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">
                                                Payment
                                            </span>
                                        @elseif ($voucher->voucher_type === 'transfer')
                                            <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                                                Contra
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                                {{ ucfirst($voucher->voucher_type) }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        @if ($voucher->financeHead)
                                            <div class="font-medium text-gray-900">
                                                {{ $voucher->financeHead->name }}
                                            </div>

                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $voucher->financeHead->code }}
                                            </div>

                                            @if ($voucher->financeHead->tallyMapping?->is_active)
                                                <div class="mt-1 text-xs text-indigo-600">
                                                    Tally:
                                                    {{ $voucher->financeHead->tallyMapping->tally_ledger_name }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">
                                                —
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        @if ($voucher->financeAccount)
                                            <div class="font-medium text-gray-900">
                                                {{ $voucher->financeAccount->name }}
                                            </div>

                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $voucher->financeAccount->code }}
                                            </div>

                                            @if ($voucher->financeAccount->tallyMapping?->is_active)
                                                <div class="mt-1 text-xs text-indigo-600">
                                                    Tally:
                                                    {{ $voucher->financeAccount->tallyMapping->tally_ledger_name }}
                                                </div>
                                            @endif
                                        @endif

                                        @if (
                                            $voucher->voucher_type === 'transfer'
                                            && $voucher->destinationAccount
                                        )
                                            <div class="mt-2 border-t border-gray-100 pt-2">
                                                <div class="text-xs font-medium text-gray-500">
                                                    Destination
                                                </div>

                                                <div class="font-medium text-gray-900">
                                                    {{ $voucher->destinationAccount->name }}
                                                </div>

                                                <div class="text-xs text-gray-500">
                                                    {{ $voucher->destinationAccount->code }}
                                                </div>

                                                @if ($voucher->destinationAccount->tallyMapping?->is_active)
                                                    <div class="mt-1 text-xs text-indigo-600">
                                                        Tally:
                                                        {{ $voucher->destinationAccount->tallyMapping->tally_ledger_name }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-semibold text-gray-900">
                                        ₹{{ number_format((float) $voucher->amount, 2) }}
                                    </td>

                                    <td class="min-w-64 px-4 py-4">
                                        @if ($export)
                                            <span class="inline-flex rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-800">
                                                {{ ucfirst($export->status) }}
                                            </span>

                                            @if ($export->export_reference)
                                                <div class="mt-2 text-xs text-gray-500">
                                                    Ref:
                                                    {{ $export->export_reference }}
                                                </div>
                                            @endif

                                            @if ($export->exported_at)
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ $export->exported_at->format('d-m-Y H:i') }}
                                                </div>
                                            @endif
                                        @elseif ($check['eligible'])
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                                Ready
                                            </span>

                                            <div class="mt-2 text-xs text-gray-500">
                                                All required Tally ledger mappings are available.
                                            </div>
                                        @else
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                Blocked
                                            </span>

                                            <ul class="mt-2 space-y-1 text-xs text-red-700">
                                                @foreach ($check['errors'] as $error)
                                                    <li>
                                                        • {{ $error }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center text-sm text-gray-500"
                                    >
                                        No posted Finance vouchers were found for the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($vouchers->hasPages())
                    <div class="border-t border-gray-200 px-4 py-4">
                        {{ $vouchers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>