<x-app-layout>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Finance Vouchers
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Manual receipts, payments and transfers
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">

                    <a
                        href="{{ route('finance.dashboard') }}"
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    >
                        ← Dashboard
                    </a>

                    <a
                        href="{{ route('finance.vouchers.create', ['type' => 'receipt']) }}"
                        class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                    >
                        + Receipt
                    </a>

                    <a
    href="{{ route('finance.vouchers.create', ['type' => 'payment']) }}"
    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
>
    + Payment
</a>

                    <a
                        href="{{ route('finance.vouchers.create', ['type' => 'transfer']) }}"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        + Transfer
                    </a>

                </div>
            </div>
        </div>


        {{-- Success --}}
        @if(session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif


        {{-- Errors --}}
        @if($errors->any())
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
                <ul class="list-disc space-y-1 pl-5 text-sm text-rose-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Filters --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <form
                method="GET"
                action="{{ route('finance.vouchers.index') }}"
            >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-5">

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Voucher Type
                        </label>

                        <select
                            name="voucher_type"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                Transfer
                            </option>
                        </select>
                    </div>


                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Statuses</option>

                            <option
                                value="draft"
                                @selected(request('status') === 'draft')
                            >
                                Draft
                            </option>

                            <option
                                value="posted"
                                @selected(request('status') === 'posted')
                            >
                                Posted
                            </option>

                            <option
                                value="cancelled"
                                @selected(request('status') === 'cancelled')
                            >
                                Cancelled
                            </option>
                        </select>
                    </div>


                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            From Date
                        </label>

                        <input
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>


                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            To Date
                        </label>

                        <input
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>


                    <div class="flex items-end gap-2">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('finance.vouchers.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Clear
                        </a>

                    </div>

                </div>
            </form>

        </div>


        {{-- Voucher Table --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Voucher No.
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Type
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Head / Transfer
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Account
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Party / Reference
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">
                                Amount
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                Status
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                Action
                            </th>

                        </tr>
                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse($vouchers as $voucher)

                            <tr class="hover:bg-slate-50">

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $voucher->voucher_date->format('d M Y') }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-900">
                                    {{ $voucher->voucher_no }}
                                </td>


                                <td class="px-4 py-3">

                                    @if($voucher->voucher_type === 'receipt')
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Receipt
                                        </span>

                                    @elseif($voucher->voucher_type === 'payment')
                                        <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                            Payment
                                        </span>

                                    @else
                                        <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                            Transfer
                                        </span>
                                    @endif

                                </td>


                                <td class="px-4 py-3 text-sm text-slate-700">

                                    @if($voucher->voucher_type === 'transfer')

                                        <div class="font-medium">
                                            {{ $voucher->financeAccount?->name ?? '—' }}
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            →
                                            {{ $voucher->destinationAccount?->name ?? '—' }}
                                        </div>

                                    @else

                                        {{ $voucher->financeHead?->name ?? '—' }}

                                    @endif

                                </td>


                                <td class="px-4 py-3 text-sm text-slate-700">

                                    @if($voucher->voucher_type === 'transfer')
                                        {{ $voucher->financeAccount?->name ?? '—' }}
                                    @else
                                        {{ $voucher->financeAccount?->name ?? '—' }}
                                    @endif

                                </td>


                                <td class="px-4 py-3 text-sm text-slate-600">

                                    <div>
                                        {{ $voucher->party_name ?: '—' }}
                                    </div>

                                    @if($voucher->reference_no)
                                        <div class="text-xs text-slate-500">
                                            Ref: {{ $voucher->reference_no }}
                                        </div>
                                    @endif

                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                    ₹{{ number_format((float) $voucher->amount, 2) }}
                                </td>


                                <td class="px-4 py-3 text-center">

                                    @if($voucher->status === 'posted')

                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Posted
                                        </span>

                                    @elseif($voucher->status === 'cancelled')

                                        <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                            Cancelled
                                        </span>

                                    @else

                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Draft
                                        </span>

                                    @endif

                                </td>


                                <td class="px-4 py-3 text-center">

                                    <a
                                        href="{{ route('finance.vouchers.show', $voucher) }}"
                                        class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="9"
                                    class="px-4 py-10 text-center text-sm text-slate-500"
                                >
                                    No finance vouchers found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if($vouchers->hasPages())
                <div class="border-t border-slate-200 px-4 py-4">
                    {{ $vouchers->links() }}
                </div>
            @endif

        </div>

    </div>

</x-app-layout>