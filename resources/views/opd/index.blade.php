<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Today's OPD
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ now()->format('d M Y') }}
                </p>

            </div>


            <a
                href="{{ route('patients.index') }}"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Register OPD
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto w-full max-w-[1800px] px-4 sm:px-6 lg:px-8">


            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))

                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">


                {{-- TABLE HEADER --}}
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="font-semibold text-gray-800">
                                OPD Queue
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Today's registered outpatient encounters.
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


                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1500px] divide-y divide-gray-200">

                        <thead class="bg-white">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Queue
                                </th>

                                <th class="min-w-[190px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    UHID / MRD
                                </th>

                                <th class="min-w-[210px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Patient
                                </th>

                                <th class="min-w-[190px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Department
                                </th>

                                <th class="min-w-[230px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Doctor
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Visit
                                </th>

                                <th class="min-w-[170px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>

                                <th class="min-w-[130px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Payment
                                </th>

                                <th class="min-w-[280px] px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @forelse ($encounters as $encounter)

                                @php
                                    $payment = $encounter->payments
                                        ->sortByDesc('payment_date')
                                        ->first();

                                    $invoice = $encounter->invoices
                                        ->sortByDesc('id')
                                        ->first();

                                    $status = strtolower(
                                        (string) $encounter->status
                                    );
                                @endphp


                                <tr class="hover:bg-gray-50">


                                    {{-- QUEUE --}}
                                    <td class="px-5 py-5 align-top">

                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-base font-bold text-white">
                                            {{ $encounter->queue_number }}
                                        </div>

                                    </td>


                                    {{-- UHID / MRD --}}
                                    <td class="min-w-[190px] px-5 py-5 align-top">

                                        <div class="whitespace-nowrap text-sm font-semibold text-gray-900">
                                            {{ $encounter->patient->uhid }}
                                        </div>

                                        <div class="mt-1 whitespace-nowrap text-xs text-gray-500">
                                            MRD:
                                            {{ $encounter->patient->mrd_number ?: '—' }}
                                        </div>

                                    </td>


                                    {{-- PATIENT --}}
                                    <td class="min-w-[210px] px-5 py-5 align-top">

                                        <a
                                            href="{{ route('patients.show', $encounter->patient) }}"
                                            class="text-sm font-semibold text-gray-900 hover:text-blue-700"
                                        >
                                            {{ $encounter->patient->full_name }}
                                        </a>


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
                                    <td class="min-w-[190px] px-5 py-5 align-top text-sm text-gray-700">

                                        {{ $encounter->department?->name ?? '—' }}

                                    </td>


                                    {{-- DOCTOR --}}
                                    <td class="min-w-[230px] px-5 py-5 align-top">

                                        <div class="text-sm text-gray-700">
                                            {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                                        </div>

                                        @if ($encounter->doctor?->speciality)

                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $encounter->doctor->speciality }}
                                            </div>

                                        @endif

                                    </td>


                                    {{-- VISIT --}}
                                    <td class="px-5 py-5 align-top text-sm text-gray-700">

                                        {{ ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $encounter->visit_type
                                            )
                                        ) }}

                                    </td>


                                    {{-- STATUS --}}
                                    <td class="min-w-[280px] whitespace-nowrap px-5 py-5 align-top">

                                        @if ($status === 'waiting')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                                Awaiting Vitals
                                            </span>


                                        @elseif ($status === 'waiting_for_doctor')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                Waiting for Doctor
                                            </span>


                                        @elseif ($status === 'completed')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Completed
                                            </span>


                                        @elseif ($status === 'cancelled')

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                Cancelled
                                            </span>


                                        @else

                                            <span class="inline-flex whitespace-nowrap rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                                {{ ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $encounter->status
                                                    )
                                                ) }}
                                            </span>

                                        @endif

                                    </td>


                                    {{-- PAYMENT --}}
                                    <td class="min-w-[130px] px-5 py-5 align-top">

                                        @if ($invoice)

                                            @if ($invoice->status === 'paid')

                                                <div class="space-y-1">

                                                    <span class="inline-flex whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                        Paid
                                                    </span>

                                                    <div class="whitespace-nowrap text-xs text-gray-500">
                                                        ₹{{ number_format((float) $invoice->paid_amount, 2) }}
                                                    </div>

                                                </div>


                                            @elseif ($invoice->status === 'partial')

                                                <div class="space-y-1">

                                                    <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                                        Partial
                                                    </span>

                                                    <div class="whitespace-nowrap text-xs text-gray-500">
                                                        Paid:
                                                        ₹{{ number_format((float) $invoice->paid_amount, 2) }}
                                                    </div>

                                                    <div class="whitespace-nowrap text-xs font-medium text-red-600">
                                                        Due:
                                                        ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                                                    </div>

                                                </div>


                                            @else

                                                <div class="space-y-1">

                                                    <span class="inline-flex whitespace-nowrap rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                        Unpaid
                                                    </span>

                                                    <div class="whitespace-nowrap text-xs font-medium text-red-600">
                                                        Due:
                                                        ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                                                    </div>

                                                </div>

                                            @endif


                                        @else

                                            <span class="text-xs text-gray-400">
                                                No invoice
                                            </span>

                                        @endif

                                    </td>


                                    {{-- ACTIONS --}}
                                    <td class="min-w-[170px] px-5 py-5 align-top">

                                        <div class="flex min-w-[240px] flex-col items-start gap-2">


                                            {{-- PRINT OPD CARD --}}
                                            <a
                                                href="{{ route('opd.card', $encounter) }}"
                                                target="_blank"
                                                class="inline-flex whitespace-nowrap rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                            >
                                                Print OPD Card
                                            </a>


                                            {{-- PRINT RECEIPT --}}
                                            @if ($payment)

                                                <div class="space-y-1">

                                                    <div class="whitespace-nowrap text-xs font-medium text-gray-500">
                                                        {{ $payment->receipt_no }}
                                                    </div>

                                                    <a
                                                        href="{{ route('payments.receipt', $payment) }}"
                                                        target="_blank"
                                                        class="inline-flex whitespace-nowrap rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                                    >
                                                        Print Receipt
                                                    </a>

                                                </div>


                                            @else

                                                <div class="text-xs text-gray-400">
                                                    No receipt
                                                </div>

                                            @endif

                                        </div>

                                    </td>


                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="9"
                                        class="px-6 py-14 text-center"
                                    >

                                        <div class="text-sm font-medium text-gray-700">
                                            No OPD patients registered today.
                                        </div>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Select a patient from the patient registry to register an OPD visit.
                                        </p>


                                        <a
                                            href="{{ route('patients.index') }}"
                                            class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                        >
                                            Register OPD
                                        </a>

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