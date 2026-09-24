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


            @if (auth()->user()?->hasAnyRole(['reception', 'medical_records']))
                <a
                    href="{{ route('patients.index') }}"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Register OPD
                </a>
            @endif

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


                <div class="w-full">

                    <table class="w-full table-fixed divide-y divide-gray-200 text-sm">

                        <colgroup>
                            <col class="w-[7%]">
                            <col class="w-[25%]">
                            <col class="w-[24%]">
                            <col class="w-[13%]">
                            <col class="w-[16%]">
                            <col class="w-[15%]">
                        </colgroup>

                        <thead class="bg-white">
                            <tr>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Queue
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Patient
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Clinical
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Visit
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status / Payment
                                </th>

                                <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
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
                                    <td class="px-3 py-4 align-top text-center">
                                        <div class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">
                                            {{ $encounter->queue_number }}
                                        </div>
                                    </td>

                                    {{-- PATIENT + IDENTIFIERS --}}
                                    <td class="px-3 py-4 align-top">
                                        <a
                                            href="{{ route('patients.show', $encounter->patient) }}"
                                            class="font-semibold text-gray-900 hover:text-blue-700"
                                        >
                                            {{ $encounter->patient->full_name }}
                                        </a>

                                        <div class="mt-1 text-xs text-gray-500">
                                            @if ($encounter->patient->age !== null)
                                                {{ $encounter->patient->age }} yrs
                                            @else
                                                Age —
                                            @endif
                                            / {{ $encounter->patient->sex ?: '—' }}
                                        </div>

                                        <div class="mt-1 space-y-0.5 break-words font-mono text-[11px] leading-4 text-gray-500">
                                            <div>UHID: {{ $encounter->patient->uhid }}</div>
                                            <div>MRD: {{ $encounter->patient->mrd_number ?: '—' }}</div>
                                        </div>
                                    </td>

                                    {{-- CLINICAL --}}
                                    <td class="px-3 py-4 align-top text-gray-700">
                                        <div class="font-semibold text-gray-900 break-words">
                                            {{ $encounter->department?->name ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs font-medium text-gray-700 break-words">
                                            {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                                        </div>

                                        @if ($encounter->doctor?->speciality)
                                            <div class="mt-0.5 text-[11px] leading-4 text-gray-500 break-words">
                                                {{ $encounter->doctor->speciality }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- VISIT --}}
                                    <td class="px-3 py-4 align-top text-gray-700">
                                        <div class="text-xs font-medium break-words">
                                            {{ ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $encounter->visit_type
                                                )
                                            ) }}
                                        </div>
                                    </td>

                                    {{-- STATUS + PAYMENT --}}
                                    <td class="px-3 py-4 align-top">

                                        <div>
                                            @if ($status === 'waiting')
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                                    Awaiting Vitals
                                                </span>
                                            @elseif ($status === 'waiting_for_doctor')
                                                <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                                                    Waiting for Doctor
                                                </span>
                                            @elseif ($status === 'completed')
                                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-semibold text-green-700">
                                                    Completed
                                                </span>
                                            @elseif ($status === 'cancelled')
                                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-semibold text-red-700">
                                                    Cancelled
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700">
                                                    {{ ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $encounter->status
                                                        )
                                                    ) }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-2">
                                            @if ($invoice)
                                                @if ($invoice->status === 'paid')
                                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-semibold text-green-700">
                                                        Paid
                                                    </span>
                                                    <div class="mt-1 text-[11px] text-gray-500">
                                                        ₹{{ number_format((float) $invoice->paid_amount, 2) }}
                                                    </div>
                                                @elseif ($invoice->status === 'partial')
                                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">
                                                        Partial
                                                    </span>
                                                    <div class="mt-1 text-[11px] text-gray-500">
                                                        Paid: ₹{{ number_format((float) $invoice->paid_amount, 2) }}
                                                    </div>
                                                    <div class="text-[11px] font-medium text-red-600">
                                                        Due: ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                                                    </div>
                                                @else
                                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-semibold text-red-700">
                                                        Unpaid
                                                    </span>
                                                    <div class="mt-1 text-[11px] font-medium text-red-600">
                                                        Due: ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-[11px] text-gray-400">
                                                    No invoice
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- ACTIONS --}}
                                    <td class="px-3 py-4 align-top">
                                        <div class="flex flex-col items-stretch gap-1.5">

                                            <a
                                                href="{{ route('opd.card', $encounter) }}"
                                                target="_blank"
                                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-[11px] font-semibold text-gray-700 hover:bg-gray-50"
                                            >
                                                Print OPD Card
                                            </a>

                                            @if ($payment)
                                                <div class="truncate text-center text-[10px] font-medium text-gray-500" title="{{ $payment->receipt_no }}">
                                                    {{ $payment->receipt_no }}
                                                </div>

                                                <a
                                                    href="{{ route('payments.receipt', $payment) }}"
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-2 py-1.5 text-[11px] font-semibold text-white hover:bg-slate-800"
                                                >
                                                    Print Receipt
                                                </a>
                                            @else
                                                <div class="text-center text-[11px] text-gray-400">
                                                    No receipt
                                                </div>
                                            @endif

                                        </div>
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-6 py-14 text-center"
                                    >
                                        <div class="text-sm font-medium text-gray-700">
                                            No OPD patients registered today.
                                        </div>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Select a patient from the patient registry to register an OPD visit.
                                        </p>

                                        @if (auth()->user()?->hasAnyRole(['reception', 'medical_records']))
                                            <a
                                                href="{{ route('patients.index') }}"
                                                class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                            >
                                                Register OPD
                                            </a>
                                        @endif
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