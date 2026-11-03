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

                    
<div class="divide-y divide-slate-200">

    @forelse ($sales as $sale)

        <div class="p-5 transition hover:bg-slate-50 sm:p-6">

            <div class="flex flex-col gap-4">

                {{-- Sale number, date and status --}}
                <div class="flex flex-wrap items-start justify-between gap-3">

                    <div class="min-w-0">
                        <div class="break-all text-sm font-bold text-slate-900">
                            {{ $sale->sale_no }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            {{ $sale->sale_at?->format('d M Y · h:i A') }}
                        </div>
                    </div>

                    <div>
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
                    </div>

                </div>

                {{-- Patient and encounter --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Patient
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $sale->patient->full_name }}
                        </div>

                        <div class="mt-1 break-words text-xs text-slate-500">
                            UHID: {{ $sale->patient->uhid }}
                            @if ($sale->patient->mrd_number)
                                · MRD: {{ $sale->patient->mrd_number }}
                            @endif
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Encounter
                        </div>

                        @if ($sale->encounter)
                            <div class="mt-1 break-words text-sm font-semibold text-slate-700">
                                {{ $sale->encounter->encounter_no }}
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $sale->encounter->department?->name ?? '—' }}
                            </div>
                        @else
                            <div class="mt-1 text-sm text-slate-500">
                                Walk-in / No encounter
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Payment and actions --}}
                <div class="flex flex-col gap-4 border-t border-slate-100 pt-4 lg:flex-row lg:items-end lg:justify-between">

                    <div class="flex flex-wrap items-center gap-x-8 gap-y-3">

                        <div>
                            <div class="text-xs text-slate-400">Total</div>
                            <div class="text-lg font-bold text-slate-900">
                                ₹{{ number_format((float) $sale->total_amount, 2) }}
                            </div>

                            @if ((float) $sale->discount > 0)
                                <div class="text-xs text-slate-500">
                                    Discount ₹{{ number_format((float) $sale->discount, 2) }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <div class="text-xs text-slate-400">Payment</div>
                            <div class="mt-1 break-words text-sm font-semibold text-slate-700">
                                {{ ucwords(str_replace('_', ' ', $sale->payment_mode ?? '—')) }}
                            </div>

                            @if ((float) $sale->balance_amount > 0)
                                <div class="text-xs font-semibold text-amber-600">
                                    Balance ₹{{ number_format((float) $sale->balance_amount, 2) }}
                                </div>
                            @else
                                <div class="text-xs text-emerald-600">Paid</div>
                            @endif
                        </div>

                    </div>

                    <div class="flex flex-wrap items-center gap-2">

                        <a href="{{ route('pharmacy.dispensing.show', $sale) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            View
                        </a>

                        <a href="{{ route('pharmacy.dispensing.receipt', $sale) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
                            Receipt
                        </a>

                        @if (in_array($sale->status, ['completed', 'credit'], true))
                            <a href="{{ route('pharmacy.returns.create', $sale) }}"
                               class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                                Return Medicines
                            </a>
                        @endif

                    </div>

                </div>

            </div>

        </div>

    @empty

        <div class="px-6 py-14 text-center">
            <div class="font-semibold text-slate-700">
                No pharmacy sales found.
            </div>

            <div class="mt-2 text-sm text-slate-500">
                Start by creating a new dispensing transaction.
            </div>

            <a href="{{ route('pharmacy.dispensing.create') }}"
               class="mt-5 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                New Dispensing
            </a>
        </div>

    @endforelse

</div>


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