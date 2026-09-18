<x-app-layout>

    @php
        $isInpatient = ! empty($sale->admission_id);
    @endphp


    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Pharmacy Sale
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    @if ($isInpatient)
                        Inpatient pharmacy issue posted to the running IP bill
                    @else
                        Review sale details, receipt, payment and returns
                    @endif
                </p>
            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('pharmacy.dispensing.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Back
                </a>


                <a
                    href="{{ route('pharmacy.dispensing.receipt', $sale) }}"
                    class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900"
                >
                    Receipt
                </a>


                @if (in_array($sale->status, ['completed', 'credit'], true))

                    <a
                        href="{{ route('pharmacy.returns.create', $sale) }}"
                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
                    >
                        Return Medicines
                    </a>

                @endif

            </div>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- SALE HEADER --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Pharmacy Sale Number
                            </div>

                            <div class="mt-1 text-2xl font-bold text-slate-900">
                                {{ $sale->sale_no }}
                            </div>

                            <div class="mt-2 text-sm text-slate-500">
                                {{ $sale->sale_at?->format('d M Y, h:i A') }}
                            </div>

                        </div>


                        <div class="text-left md:text-right">

                            @php
                                $statusClasses = match ($sale->status) {
                                    'completed' => 'bg-emerald-50 text-emerald-700',
                                    'credit' => 'bg-amber-50 text-amber-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp

                            <span
                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}"
                            >
                                {{ strtoupper($sale->status) }}
                            </span>

                        </div>

                    </div>

                </div>



                <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-4">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Patient
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $sale->patient?->full_name ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            UHID
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $sale->patient?->uhid ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            MRD
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $sale->patient?->mrd_number ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Encounter
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $sale->encounter?->encounter_no ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Department
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $sale->encounter?->department?->name ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Doctor
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $sale->encounter?->doctor?->name ?? '—' }}
                        </div>

                    </div>


                    @if ($isInpatient)

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Billing
                            </div>

                            <div class="mt-1 font-semibold text-emerald-700">
                                Posted to IP Billing
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Admission No
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $sale->admission?->admission_no ?? '—' }}
                            </div>
                        </div>

                    @else

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Payment Mode
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ strtoupper($sale->payment_mode ?? '—') }}
                            </div>

                        </div>


                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Transaction Reference
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $sale->transaction_reference ?: '—' }}
                            </div>

                        </div>

                    @endif

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- MEDICINES --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Medicines Dispensed
                    </h3>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Medicine
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Batch
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Qty
                                </th>

                                @unless ($isInpatient)

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Rate
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        GST
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Taxable
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Amount
                                    </th>

                                @endunless

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($sale->items as $item)

                                <tr>

                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $item->medicine_name }}
                                        </div>

                                        @if ($item->brand_name)
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $item->brand_name }}
                                            </div>
                                        @endif

                                        @if ($item->strength)
                                            <div class="text-xs text-slate-500">
                                                {{ $item->strength }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-4 py-4 text-sm text-slate-700">
                                        {{ $item->batch_number }}
                                    </td>


                                    <td class="px-4 py-4 text-right font-semibold text-slate-700">
                                        {{ $item->quantity }}
                                    </td>


                                    @unless ($isInpatient)

                                        <td class="px-4 py-4 text-right text-slate-700">
                                            ₹{{ number_format((float) $item->unit_price, 2) }}
                                        </td>


                                        <td class="px-4 py-4 text-right text-slate-700">
                                            {{ number_format((float) $item->gst_percent, 2) }}%
                                        </td>


                                        <td class="px-4 py-4 text-right text-slate-700">
                                            ₹{{ number_format((float) $item->taxable_amount, 2) }}
                                        </td>


                                        <td class="px-4 py-4 text-right font-bold text-slate-900">
                                            ₹{{ number_format((float) $item->amount, 2) }}
                                        </td>

                                    @endunless

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="{{ $isInpatient ? 3 : 7 }}"
                                        class="px-6 py-10 text-center text-sm text-slate-500"
                                    >
                                        No pharmacy sale items found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- PAYMENT SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="grid gap-6 lg:grid-cols-2">


                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h3 class="font-semibold text-slate-900">
                        Sale Information
                    </h3>


                    <div class="mt-5 space-y-4">

                        <div class="flex items-center justify-between">

                            <span class="text-sm text-slate-500">
                                Dispensed By
                            </span>

                            <span class="font-semibold text-slate-900">
                                {{ $sale->createdBy?->name ?? '—' }}
                            </span>

                        </div>


                        <div class="flex items-start justify-between gap-5">

                            <span class="text-sm text-slate-500">
                                Remarks
                            </span>

                            <span class="max-w-md text-right text-sm font-medium text-slate-900">
                                {{ $sale->remarks ?: '—' }}
                            </span>

                        </div>

                    </div>

                </div>



                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    @if ($isInpatient)

                        <h3 class="font-semibold text-slate-900">
                            IP Billing
                        </h3>

                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                            <div class="text-sm font-bold text-emerald-800">
                                Posted to Running IP Bill
                            </div>

                            <div class="mt-2 text-sm leading-6 text-emerald-700">
                                No payment is collected at the pharmacy for this inpatient issue.
                                Charges are posted automatically to the patient's IP billing account.
                                Any medicine return is handled through an auditable billing reversal.
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">
                                    Admission No
                                </span>

                                <span class="font-semibold text-slate-900">
                                    {{ $sale->admission?->admission_no ?? '—' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">
                                    Pharmacy Sale
                                </span>

                                <span class="font-semibold text-slate-900">
                                    {{ $sale->sale_no }}
                                </span>
                            </div>

                        </div>

                    @else

                        <h3 class="font-semibold text-slate-900">
                            Payment Summary
                        </h3>


                        <div class="mt-5 space-y-3">

                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Gross Amount
                                </span>

                                <span class="font-semibold text-slate-900">
                                    ₹{{ number_format((float) $sale->subtotal, 2) }}
                                </span>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Discount
                                </span>

                                <span class="font-semibold text-slate-700">
                                    ₹{{ number_format((float) $sale->discount, 2) }}
                                </span>

                            </div>


                            <div class="border-t border-slate-100 pt-3">

                                <div class="flex items-center justify-between">

                                    <span class="text-sm text-slate-500">
                                        Taxable Value
                                    </span>

                                    <span class="font-semibold text-slate-700">
                                        ₹{{ number_format((float) $sale->taxable_amount, 2) }}
                                    </span>

                                </div>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    CGST
                                </span>

                                <span class="font-semibold text-slate-700">
                                    ₹{{ number_format((float) $sale->cgst_amount, 2) }}
                                </span>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    SGST
                                </span>

                                <span class="font-semibold text-slate-700">
                                    ₹{{ number_format((float) $sale->sgst_amount, 2) }}
                                </span>

                            </div>


                            @if ((float) $sale->igst_amount > 0)

                                <div class="flex items-center justify-between">

                                    <span class="text-sm text-slate-500">
                                        IGST
                                    </span>

                                    <span class="font-semibold text-slate-700">
                                        ₹{{ number_format((float) $sale->igst_amount, 2) }}
                                    </span>

                                </div>

                            @endif


                            <div class="border-y border-slate-200 py-4">

                                <div class="flex items-center justify-between">

                                    <span class="font-bold text-slate-900">
                                        Total Amount
                                    </span>

                                    <span class="text-xl font-bold text-slate-900">
                                        ₹{{ number_format((float) $sale->total_amount, 2) }}
                                    </span>

                                </div>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Paid
                                </span>

                                <span class="font-semibold text-emerald-700">
                                    ₹{{ number_format((float) $sale->paid_amount, 2) }}
                                </span>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Balance
                                </span>

                                <span class="font-semibold text-red-700">
                                    ₹{{ number_format((float) $sale->balance_amount, 2) }}
                                </span>

                            </div>

                        </div>

                    @endif

                </div>

            </div>


                        <div class="flex items-center justify-between">

                            <span class="text-sm text-slate-500">
                                CGST
                            </span>

                            <span class="font-semibold text-slate-700">
                                ₹{{ number_format((float) $sale->cgst_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex items-center justify-between">

                            <span class="text-sm text-slate-500">
                                SGST
                            </span>

                            <span class="font-semibold text-slate-700">
                                ₹{{ number_format((float) $sale->sgst_amount, 2) }}
                            </span>

                        </div>


                        @if ((float) $sale->igst_amount > 0)

                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    IGST
                                </span>

                                <span class="font-semibold text-slate-700">
                                    ₹{{ number_format((float) $sale->igst_amount, 2) }}
                                </span>

                            </div>

                        @endif


                        <div class="border-y border-slate-200 py-4">

                            <div class="flex items-center justify-between">

                                <span class="font-bold text-slate-900">
                                    Total Amount
                                </span>

                                <span class="text-xl font-bold text-slate-900">
                                    ₹{{ number_format((float) $sale->total_amount, 2) }}
                                </span>

                            </div>

                        </div>


                        <div class="flex items-center justify-between">

                            <span class="text-sm text-slate-500">
                                Paid
                            </span>

                            <span class="font-semibold text-emerald-700">
                                ₹{{ number_format((float) $sale->paid_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex items-center justify-between">

                            <span class="text-sm text-slate-500">
                                Balance
                            </span>

                            <span class="font-semibold text-red-700">
                                ₹{{ number_format((float) $sale->balance_amount, 2) }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- PREVIOUS RETURNS --}}
            {{-- ========================================================= --}}

            @if ($sale->returns && $sale->returns->count() > 0)

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Previous Returns
                        </h3>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Return No
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Date
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Reason
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        {{ $isInpatient ? 'Billing' : 'Refund' }}
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100 bg-white">

                                @foreach ($sale->returns as $return)

                                    <tr>

                                        <td class="px-6 py-4 font-semibold text-slate-900">
                                            {{ $return->return_no }}
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            {{ $return->returned_at?->format('d M Y, h:i A') }}
                                        </td>

                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            {{ $return->reason }}
                                        </td>

                                        <td class="px-4 py-4 text-right font-semibold {{ $isInpatient ? 'text-emerald-700' : 'text-slate-900' }}">
                                            @if ($isInpatient)
                                                Reversed in IP Bill
                                            @else
                                                ₹{{ number_format((float) $return->refund_amount, 2) }}
                                            @endif
                                        </td>

                                        <td class="px-4 py-4 text-right">

                                            <a
                                                href="{{ route('pharmacy.returns.receipt', $return) }}"
                                                class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                                            >
                                                Receipt
                                            </a>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif


        </div>

    </div>

</x-app-layout>