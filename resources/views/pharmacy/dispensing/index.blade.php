<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Pharmacy Dispensing
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Dispense medicines, collect payment and print pharmacy receipts
                </p>
            </div>


            <a
                href="{{ route('pharmacy.dispensing.create') }}"
                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                New Dispensing
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            <div class="mb-6 grid gap-4 md:grid-cols-3">

                <a
                    href="{{ route('pharmacy.dispensing.create') }}"
                    class="rounded-2xl border border-blue-200 bg-blue-50 p-5 transition hover:bg-blue-100"
                >
                    <div class="text-sm font-semibold text-blue-700">
                        New Dispensing
                    </div>

                    <div class="mt-2 text-xs text-blue-600">
                        Enter medicines from the patient's paper prescription
                    </div>
                </a>


                <a
                    href="{{ route('pharmacy.stock-batches.index') }}"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:bg-slate-50"
                >
                    <div class="text-sm font-semibold text-slate-900">
                        Pharmacy Stock
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        Review batches, available quantities and expiry dates
                    </div>
                </a>


                <a
                    href="{{ route('pharmacy.medicines.index') }}"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:bg-slate-50"
                >
                    <div class="text-sm font-semibold text-slate-900">
                        Medicine Master
                    </div>

                    <div class="mt-2 text-xs text-slate-500">
                        Maintain medicine names, strengths and default prices
                    </div>
                </a>

            </div>


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <form
                        method="GET"
                        action="{{ route('pharmacy.dispensing.index') }}"
                        class="flex flex-col gap-3 sm:flex-row sm:items-end"
                    >

                        <div class="flex-1">

                            <label
                                for="search"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Search Pharmacy Sales
                            </label>

                            <input
                                id="search"
                                name="search"
                                type="text"
                                value="{{ $search }}"
                                placeholder="Sale number, UHID, MRD, patient name or phone"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div class="flex gap-2">

                            <button
                                type="submit"
                                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                Search
                            </button>


                            @if ($search !== '')

                                <a
                                    href="{{ route('pharmacy.dispensing.index') }}"
                                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Clear
                                </a>

                            @endif

                        </div>

                    </form>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Sale No.
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Date / Time
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Patient
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Encounter
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Total
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Payment
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($sales as $sale)

                                <tr class="transition hover:bg-slate-50">


                                    <td class="whitespace-nowrap px-6 py-4">

                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $sale->sale_no }}
                                        </div>

                                    </td>


                                    <td class="whitespace-nowrap px-6 py-4">

                                        <div class="text-sm text-slate-800">
                                            {{ $sale->sale_at?->format('d M Y') }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $sale->sale_at?->format('h:i A') }}
                                        </div>

                                    </td>


                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $sale->patient->full_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            UHID: {{ $sale->patient->uhid }}

                                            @if ($sale->patient->mrd_number)
                                                · MRD: {{ $sale->patient->mrd_number }}
                                            @endif
                                        </div>

                                    </td>


                                    <td class="px-6 py-4">

                                        @if ($sale->encounter)

                                            <div class="text-sm font-semibold text-slate-700">
                                                {{ $sale->encounter->encounter_no }}
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $sale->encounter->department?->name ?? '—' }}
                                            </div>

                                        @else

                                            <span class="text-sm text-slate-400">
                                                Walk-in / No encounter
                                            </span>

                                        @endif

                                    </td>


                                    <td class="whitespace-nowrap px-6 py-4 text-right">

                                        <div class="font-bold text-slate-900">
                                            ₹{{ number_format((float) $sale->total_amount, 2) }}
                                        </div>

                                        @if ((float) $sale->discount > 0)

                                            <div class="mt-1 text-xs text-slate-400">
                                                Discount ₹{{ number_format((float) $sale->discount, 2) }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-6 py-4">

                                        <div class="text-sm font-semibold text-slate-700">
                                            {{ strtoupper($sale->payment_mode ?? '—') }}
                                        </div>

                                        @if ((float) $sale->balance_amount > 0)

                                            <div class="mt-1 text-xs font-semibold text-amber-600">
                                                Balance ₹{{ number_format((float) $sale->balance_amount, 2) }}
                                            </div>

                                        @else

                                            <div class="mt-1 text-xs text-emerald-600">
                                                Paid
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-6 py-4">

                                        @if ($sale->status === 'completed')

                                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                Completed
                                            </span>

                                        @elseif ($sale->status === 'credit')

                                            <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                                Credit
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                {{ ucwords(str_replace('_', ' ', $sale->status)) }}
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-6 py-4">

                                        <div class="flex items-center justify-end gap-2">

                                            <a
                                                href="{{ route('pharmacy.dispensing.show', $sale) }}"
                                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                View
                                            </a>


                                            <a
                                                href="{{ route('pharmacy.dispensing.receipt', $sale) }}"
                                                class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                            >
                                                Receipt
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="8"
                                        class="px-6 py-14 text-center"
                                    >

                                        <div class="text-sm font-semibold text-slate-700">
                                            No pharmacy sales found.
                                        </div>

                                        <div class="mt-2 text-xs text-slate-400">
                                            Start by creating a new dispensing transaction.
                                        </div>

                                        <div class="mt-5">

                                            <a
                                                href="{{ route('pharmacy.dispensing.create') }}"
                                                class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                            >
                                                New Dispensing
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($sales->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $sales->links() }}
                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>