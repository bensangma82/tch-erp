<x-app-layout>

    @php
    $isInpatient = ! empty($sale->admission_id);

    $pharmacyInterimPayments = collect();

    $pharmacySaleCollected = 0;

    $pharmacySaleRemaining =
        (float) $sale->total_amount;


    if (
        $isInpatient
        && $sale->admission
    ) {
        $pharmacyInterimPayments =
            \App\Models\IpBillingAdvance::query()
                ->with('receivedBy')
                ->where(
                    'source_type',
                    'pharmacy_sale'
                )
                ->where(
                    'source_id',
                    $sale->id
                )
                ->where(
                    'status',
                    'active'
                )
                ->orderBy('payment_date')
                ->orderBy('id')
                ->get();


        $pharmacySaleCollected =
            round(
                (float)
                $pharmacyInterimPayments->sum(
                    fn ($payment) =>
                        (float) $payment->amount
                ),
                2
            );


        $pharmacySaleRemaining =
            round(
                max(
                    (float) $sale->total_amount
                    - $pharmacySaleCollected,
                    0
                ),
                2
            );
    }
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
                    href="{{ route(
                        'pharmacy.dispensing.receipt',
                        $sale
                    ) }}"
                    class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900"
                >
                    Receipt
                </a>


                @if (
                    in_array(
                        $sale->status,
                        ['completed', 'credit'],
                        true
                    )
                )

                    <a
                        href="{{ route(
                            'pharmacy.returns.create',
                            $sale
                        ) }}"
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


            {{-- ========================================================= --}}
            {{-- MESSAGES --}}
            {{-- ========================================================= --}}

            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">

                    <div class="font-semibold">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

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
                                            ₹{{ number_format(
                                                (float) $item->unit_price,
                                                2
                                            ) }}
                                        </td>


                                        <td class="px-4 py-4 text-right text-slate-700">
                                            {{ number_format(
                                                (float) $item->gst_percent,
                                                2
                                            ) }}%
                                        </td>


                                        <td class="px-4 py-4 text-right text-slate-700">
                                            ₹{{ number_format(
                                                (float) $item->taxable_amount,
                                                2
                                            ) }}
                                        </td>


                                        <td class="px-4 py-4 text-right font-bold text-slate-900">
                                            ₹{{ number_format(
                                                (float) $item->amount,
                                                2
                                            ) }}
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
            {{-- SALE / PAYMENT INFORMATION --}}
            {{-- ========================================================= --}}

            <div class="grid gap-6 lg:grid-cols-3">


                {{-- Sale Information --}}

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">

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


                {{-- ===================================================== --}}
                {{-- INPATIENT IP BILLING --}}
                {{-- ===================================================== --}}

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">


                    @if ($isInpatient)

                        <h3 class="font-semibold text-slate-900">
                            IP Billing
                        </h3>


                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5">

                            <div class="text-sm font-bold text-emerald-800">
                                Posted to Running IP Bill
                            </div>

                            <div class="mt-2 text-sm leading-6 text-emerald-700">
                                Inpatient medicine charges are posted automatically
                                to the patient's IP billing account.
                                Interim payments collected at the pharmacy are
                                recorded as IP advances and reduce the final patient
                                balance. Medicine returns are handled through an
                                auditable billing reversal.
                            </div>

                        </div>


                        {{-- Pharmacy Sale Collection Summary --}}

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">


                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Pharmacy Sale Total
                                </div>

                                <div class="mt-1 text-lg font-bold text-slate-900">
                                    ₹{{ number_format(
                                        (float) $sale->total_amount,
                                        2
                                    ) }}
                                </div>

                            </div>


                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Already Collected
                                </div>

                                <div class="mt-1 text-lg font-bold text-emerald-700">
                                    ₹{{ number_format(
                                        (float) $pharmacySaleCollected,
                                        2
                                    ) }}
                                </div>

                            </div>


                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Remaining Against Sale
                                </div>

                                <div class="mt-1 text-lg font-bold text-amber-700">
                                    ₹{{ number_format(
                                        (float) $pharmacySaleRemaining,
                                        2
                                    ) }}
                                </div>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- Receive Interim Payment --}}
                        {{-- ================================================= --}}

                        @if (
                            $sale->admission
                            && $pharmacySaleRemaining > 0
                        )

                            <div class="mt-5 rounded-xl border border-cyan-200 bg-cyan-50 p-5">


                                <div class="font-semibold text-cyan-900">
                                    Receive Interim Payment
                                </div>


                                <p class="mt-1 text-sm leading-6 text-cyan-700">
                                    Collect a payment during admission. This amount
                                    is recorded against the patient's IP account as
                                    an advance and is deducted from the final
                                    patient balance.
                                </p>


                                <form
                                    method="POST"
                                    action="{{ route(
                                        'pharmacy.dispensing.interim-payment.store',
                                        $sale->admission
                                    ) }}"
                                    class="mt-5 space-y-4"
                                >

                                    @csrf


                                    <input
                                        type="hidden"
                                        name="source_type"
                                        value="pharmacy_sale"
                                    >


                                    <input
                                        type="hidden"
                                        name="source_id"
                                        value="{{ $sale->id }}"
                                    >


                                    {{-- Amount --}}

                                    <div>

                                        <label
                                            for="interim_amount"
                                            class="mb-1 block text-sm font-medium text-slate-700"
                                        >
                                            Amount
                                        </label>


                                        <input
                                            id="interim_amount"
                                            type="number"
                                            name="amount"
                                            min="1"
                                            max="{{ number_format(
                                                $pharmacySaleRemaining,
                                                2,
                                                '.',
                                                ''
                                            ) }}"
                                            step="0.01"
                                            required
                                            value="{{ old('amount') }}"
                                            placeholder="0.00"
                                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                        >


                                        <p class="mt-1 text-xs text-slate-500">
                                            Maximum collectable against this sale:
                                            ₹{{ number_format(
                                                (float) $pharmacySaleRemaining,
                                                2
                                            ) }}
                                        </p>

                                    </div>


                                    {{-- Payment Mode --}}

                                    <div>

                                        <label
                                            for="interim_payment_mode"
                                            class="mb-1 block text-sm font-medium text-slate-700"
                                        >
                                            Payment Mode
                                        </label>


                                        <select
                                            id="interim_payment_mode"
                                            name="payment_mode"
                                            required
                                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                        >

                                            <option value="">
                                                Select payment mode
                                            </option>

                                            <option
                                                value="cash"
                                                @selected(
                                                    old('payment_mode')
                                                    === 'cash'
                                                )
                                            >
                                                Cash
                                            </option>

                                            <option
                                                value="upi"
                                                @selected(
                                                    old('payment_mode')
                                                    === 'upi'
                                                )
                                            >
                                                UPI
                                            </option>

                                            <option
                                                value="card"
                                                @selected(
                                                    old('payment_mode')
                                                    === 'card'
                                                )
                                            >
                                                Card
                                            </option>

                                        </select>

                                    </div>


                                    {{-- Transaction Reference --}}

                                    <div>

                                        <label
                                            for="interim_transaction_reference"
                                            class="mb-1 block text-sm font-medium text-slate-700"
                                        >
                                            Transaction Reference
                                        </label>


                                        <input
                                            id="interim_transaction_reference"
                                            type="text"
                                            name="transaction_reference"
                                            value="{{ old(
                                                'transaction_reference'
                                            ) }}"
                                            placeholder="Required for UPI / Card"
                                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                        >

                                    </div>


                                    {{-- Remarks --}}

                                    <div>

                                        <label
                                            for="interim_remarks"
                                            class="mb-1 block text-sm font-medium text-slate-700"
                                        >
                                            Remarks
                                        </label>


                                        <input
                                            id="interim_remarks"
                                            type="text"
                                            name="remarks"
                                            value="{{ old('remarks') }}"
                                            placeholder="Optional remarks"
                                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                        >

                                    </div>


                                    <button
                                        type="submit"
                                        class="w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm"
                                        style="background-color: #0891b2;"
                                    >
                                        Receive Interim Payment
                                    </button>

                                </form>

                            </div>


                        @elseif (
                            $sale->admission
                            && $pharmacySaleRemaining <= 0
                        )

                            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5">

                                <div class="font-semibold text-emerald-800">
                                    Pharmacy Sale Fully Collected
                                </div>

                                <p class="mt-1 text-sm text-emerald-700">
                                    The full pharmacy sale amount has already been
                                    received as interim IP payments.
                                </p>

                            </div>


                        @elseif (! $sale->admission)

                            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-5">

                                <div class="font-semibold text-amber-800">
                                    Admission Link Missing
                                </div>

                                <p class="mt-1 text-sm text-amber-700">
                                    This sale is marked as an inpatient issue but is
                                    not linked to an admission. Interim payment
                                    collection is unavailable until the admission
                                    linkage is corrected.
                                </p>

                            </div>

                        @endif

@if (
    $pharmacyInterimPayments->isNotEmpty()
)

    <div class="mt-5 overflow-hidden rounded-xl border border-slate-200 bg-white">

        <div class="border-b border-slate-200 px-4 py-3">

            <div class="font-semibold text-slate-900">
                Interim Payment History
            </div>

            <div class="mt-1 text-xs text-slate-500">
                Payments collected at Pharmacy against this inpatient sale.
            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">

                    <tr>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Receipt
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Date
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Mode
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Amount
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Receipt
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @foreach (
                        $pharmacyInterimPayments
                        as $payment
                    )

                        <tr>

                            <td class="px-4 py-3 font-mono text-sm font-semibold text-slate-900">
                                {{ $payment->receipt_no }}
                            </td>


                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ $payment->payment_date?->format(
                                    'd M Y, h:i A'
                                ) ?? '—' }}
                            </td>


                            <td class="px-4 py-3 text-sm font-semibold uppercase text-slate-700">
                                {{ $payment->payment_mode }}
                            </td>


                            <td class="px-4 py-3 text-right font-bold text-emerald-700">
                                ₹{{ number_format(
                                    (float) $payment->amount,
                                    2
                                ) }}
                            </td>


                            <td class="px-4 py-3 text-right">

                                <a
                                    href="{{ route(
                                        'ip-billing.advance.receipt',
                                        $payment
                                    ) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                >
                                    Print Receipt
                                </a>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

@endif
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

                        {{-- ================================================= --}}
                        {{-- OUTPATIENT PAYMENT SUMMARY --}}
                        {{-- ================================================= --}}

                        <h3 class="font-semibold text-slate-900">
                            Payment Summary
                        </h3>


                        <div class="mt-5 space-y-3">


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Gross Amount
                                </span>

                                <span class="font-semibold text-slate-900">
                                    ₹{{ number_format(
                                        (float) $sale->subtotal,
                                        2
                                    ) }}
                                </span>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Discount
                                </span>

                                <span class="font-semibold text-slate-700">
                                    ₹{{ number_format(
                                        (float) $sale->discount,
                                        2
                                    ) }}
                                </span>

                            </div>


                            <div class="border-t border-slate-100 pt-3">

                                <div class="flex items-center justify-between">

                                    <span class="text-sm text-slate-500">
                                        Taxable Value
                                    </span>

                                    <span class="font-semibold text-slate-700">
                                        ₹{{ number_format(
                                            (float) $sale->taxable_amount,
                                            2
                                        ) }}
                                    </span>

                                </div>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    CGST
                                </span>

                                <span class="font-semibold text-slate-700">
                                    ₹{{ number_format(
                                        (float) $sale->cgst_amount,
                                        2
                                    ) }}
                                </span>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    SGST
                                </span>

                                <span class="font-semibold text-slate-700">
                                    ₹{{ number_format(
                                        (float) $sale->sgst_amount,
                                        2
                                    ) }}
                                </span>

                            </div>


                            @if (
                                (float) $sale->igst_amount > 0
                            )

                                <div class="flex items-center justify-between">

                                    <span class="text-sm text-slate-500">
                                        IGST
                                    </span>

                                    <span class="font-semibold text-slate-700">
                                        ₹{{ number_format(
                                            (float) $sale->igst_amount,
                                            2
                                        ) }}
                                    </span>

                                </div>

                            @endif


                            <div class="border-y border-slate-200 py-4">

                                <div class="flex items-center justify-between">

                                    <span class="font-bold text-slate-900">
                                        Total Amount
                                    </span>

                                    <span class="text-xl font-bold text-slate-900">
                                        ₹{{ number_format(
                                            (float) $sale->total_amount,
                                            2
                                        ) }}
                                    </span>

                                </div>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Paid
                                </span>

                                <span class="font-semibold text-emerald-700">
                                    ₹{{ number_format(
                                        (float) $sale->paid_amount,
                                        2
                                    ) }}
                                </span>

                            </div>


                            <div class="flex items-center justify-between">

                                <span class="text-sm text-slate-500">
                                    Balance
                                </span>

                                <span class="font-semibold text-red-700">
                                    ₹{{ number_format(
                                        (float) $sale->balance_amount,
                                        2
                                    ) }}
                                </span>

                            </div>

                        </div>

                    @endif

                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- PREVIOUS RETURNS --}}
            {{-- ========================================================= --}}

            @if (
                $sale->returns
                && $sale->returns->count() > 0
            )

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
                                            {{ $return->returned_at?->format(
                                                'd M Y, h:i A'
                                            ) }}
                                        </td>


                                        <td class="px-4 py-4 text-sm text-slate-600">
                                            {{ $return->reason }}
                                        </td>


                                        <td class="px-4 py-4 text-right font-semibold {{ $isInpatient ? 'text-emerald-700' : 'text-slate-900' }}">

                                            @if ($isInpatient)

                                                Reversed in IP Bill

                                            @else

                                                ₹{{ number_format(
                                                    (float) $return->refund_amount,
                                                    2
                                                ) }}

                                            @endif

                                        </td>


                                        <td class="px-4 py-4 text-right">

                                            <a
                                                href="{{ route(
                                                    'pharmacy.returns.receipt',
                                                    $return
                                                ) }}"
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