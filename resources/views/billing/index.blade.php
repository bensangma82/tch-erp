<x-app-layout>

    <x-slot name="header">

        <div>

            <h2 class="text-xl font-semibold text-gray-800">
                Billing Counter
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Select an OPD encounter to enter investigations and collect payment.
            </p>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))

                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


                {{-- HEADER --}}
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="font-semibold text-gray-800">
                                Today's OPD Encounters
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Open the patient's encounter to enter investigation orders.
                            </p>

                        </div>


                        <div class="text-sm text-gray-600">

                            Total:

                            <span class="font-semibold text-gray-900">
                                {{ $encounters->count() }}
                            </span>

                        </div>

                    </div>

                </div>


                {{-- TABLE --}}
                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-white">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Queue
                                </th>

                                <th class="min-w-[190px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    UHID / MRD
                                </th>

                                <th class="min-w-[180px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Patient
                                </th>

                                <th class="min-w-[160px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Department
                                </th>

                                <th class="min-w-[190px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Doctor
                                </th>

                                <th class="min-w-[160px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Order Status
                                </th>

                                <th class="min-w-[190px] px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @forelse ($encounters as $encounter)

                                @php
                                    $latestOrder = $encounter->serviceOrders
                                        ->sortByDesc('id')
                                        ->first();

                                    $latestInvestigationInvoice = $encounter->invoices
                                        ->where('invoice_type', 'investigation')
                                        ->sortByDesc('id')
                                        ->first();

                                    $latestInvestigationPayment = $latestInvestigationInvoice?->payments
                                        ->sortByDesc('payment_date')
                                        ->first();
                                @endphp


                                <tr class="hover:bg-gray-50">


                                    {{-- QUEUE --}}
                                    <td class="px-5 py-4">

                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-base font-bold text-white">
                                            {{ $encounter->queue_number }}
                                        </div>

                                    </td>


                                    {{-- UHID / MRD --}}
                                    <td class="min-w-[190px] px-5 py-4">

                                        <div class="whitespace-nowrap text-sm font-semibold text-gray-900">
                                            {{ $encounter->patient->uhid }}
                                        </div>

                                        <div class="mt-1 whitespace-nowrap text-xs text-gray-500">
                                            MRD:
                                            {{ $encounter->patient->mrd_number ?: '—' }}
                                        </div>

                                    </td>


                                    {{-- PATIENT --}}
                                    <td class="min-w-[180px] px-5 py-4">

                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $encounter->patient->full_name }}
                                        </div>

                                        <div class="mt-1 whitespace-nowrap text-xs text-gray-500">

                                            @if ($encounter->patient->age !== null)

                                                {{ $encounter->patient->age }} yrs

                                            @else

                                                Age —

                                            @endif

                                            /

                                            {{ $encounter->patient->sex ?: '—' }}

                                        </div>

                                    </td>


                                    {{-- DEPARTMENT --}}
                                    <td class="min-w-[160px] px-5 py-4 text-sm text-gray-700">

                                        {{ $encounter->department?->name ?? '—' }}

                                    </td>


                                    {{-- DOCTOR --}}
                                    <td class="min-w-[190px] px-5 py-4">

                                        <div class="text-sm text-gray-700">
                                            {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                                        </div>

                                        @if ($encounter->doctor?->speciality)

                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $encounter->doctor->speciality }}
                                            </div>

                                        @endif

                                    </td>


                                    {{-- ORDER STATUS --}}
                                    <td class="min-w-[160px] px-5 py-4">

                                        @if (!$latestOrder)

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                                No Order
                                            </span>


                                        @elseif ($latestOrder->status === 'draft')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                                Draft
                                            </span>


                                        @elseif ($latestOrder->status === 'pending_payment')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                                                Awaiting Payment
                                            </span>


                                        @elseif ($latestOrder->status === 'paid')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Paid
                                            </span>


                                        @elseif ($latestOrder->status === 'authorized')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                Authorized
                                            </span>


                                        @elseif ($latestOrder->status === 'completed')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                Completed
                                            </span>


                                        @elseif ($latestOrder->status === 'cancelled')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                Cancelled
                                            </span>


                                        @else

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                                {{ ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $latestOrder->status
                                                    )
                                                ) }}
                                            </span>

                                        @endif

                                    </td>


                                    {{-- ACTION --}}
                                    <td class="min-w-[190px] px-5 py-4 text-right">

                                        <div class="flex flex-col items-end gap-2">


                                            {{-- NEW INVESTIGATION ORDER --}}
                                            <a
                                                href="{{ route('billing.create', $encounter) }}"
                                                class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                                            >
                                                <span class="mr-1.5 text-sm leading-none">+</span>
                                                Add Investigations
                                            </a>


                                            {{-- RECEIPT REPRINT --}}
                                            @if ($latestInvestigationPayment)

                                                <div class="mt-2 flex flex-col items-end">
                                                    <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-green-700">
                                                        <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                                                        Payment completed
                                                    </div>

                                                    <div class="mt-1 whitespace-nowrap text-xs font-medium text-gray-500">
                                                        {{ $latestInvestigationPayment->receipt_no }}
                                                    </div>
                                                </div>

                                                <a
                                                    href="{{ route('billing.receipt', $latestInvestigationPayment) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2"
                                                >
                                                    View / Print Receipt
                                                </a>

                                            @elseif (
                                                $latestOrder &&
                                                $latestOrder->status === 'pending_payment'
                                            )

                                                <a
                                                    href="{{ route('billing.payment', $latestOrder) }}"
                                                    class="inline-flex whitespace-nowrap rounded-lg border border-orange-300 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700 hover:bg-orange-100"
                                                >
                                                    Collect Payment
                                                </a>

                                            @elseif (
                                                $latestOrder &&
                                                $latestOrder->status === 'authorized'
                                            )

                                                <div class="text-xs text-blue-600">
                                                    Credit / MHIS authorized
                                                </div>

                                            @endif


                                        </div>

                                    </td>


                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="7"
                                        class="px-6 py-14 text-center"
                                    >

                                        <div class="text-sm font-medium text-gray-700">
                                            No OPD encounters found today.
                                        </div>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Patients must first be registered through OPD.
                                        </p>

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