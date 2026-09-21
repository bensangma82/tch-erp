<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Supplier Payable
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pharmacySupplierPayable->payable_no }}
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('pharmacy.supplier-payables.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Supplier Payables
                </a>

                @if ($pharmacySupplierPayable->grn)

                    <a
                        href="{{ route('pharmacy.grns.show', $pharmacySupplierPayable->grn) }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        View GRN
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


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif



            @php

                $statusLabel =
                    match ($pharmacySupplierPayable->status) {

                        'unpaid' =>
                            'Unpaid',

                        'partially_paid' =>
                            'Partially Paid',

                        'paid' =>
                            'Paid',

                        default =>
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $pharmacySupplierPayable->status
                                )
                            ),
                    };


                $statusClass =
                    match ($pharmacySupplierPayable->status) {

                        'unpaid' =>
                            'bg-red-50 text-red-700',

                        'partially_paid' =>
                            'bg-amber-50 text-amber-700',

                        'paid' =>
                            'bg-emerald-50 text-emerald-700',

                        default =>
                            'bg-slate-100 text-slate-700',
                    };


                $isOverdue =
                    $pharmacySupplierPayable->status !== 'paid'
                    &&
                    $pharmacySupplierPayable->due_date
                    &&
                    $pharmacySupplierPayable->due_date->isPast();

            @endphp



            {{-- ========================================================= --}}
            {{-- PAYABLE SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Supplier Payable
                            </div>

                            <div class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $pharmacySupplierPayable->payable_no }}
                            </div>

                        </div>


                        <div class="flex flex-wrap gap-2">

                            <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>

                            @if ($isOverdue)

                                <span class="inline-flex rounded-full bg-red-100 px-4 py-2 text-sm font-semibold text-red-800">
                                    Overdue
                                </span>

                            @endif

                        </div>

                    </div>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Supplier
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacySupplierPayable->supplier?->name ?? '—' }}
                        </div>

                        @if ($pharmacySupplierPayable->supplier?->code)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $pharmacySupplierPayable->supplier->code }}
                            </div>

                        @endif

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            GRN
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacySupplierPayable->grn?->grn_no ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Supplier Invoice
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacySupplierPayable->supplier_invoice_no ?: '—' }}
                        </div>

                        @if ($pharmacySupplierPayable->supplier_invoice_date)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $pharmacySupplierPayable->supplier_invoice_date->format('d M Y') }}
                            </div>

                        @endif

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Payable Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacySupplierPayable->payable_date?->format('d M Y') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Due Date
                        </div>

                        <div class="mt-2 font-bold {{ $isOverdue ? 'text-red-700' : 'text-slate-900' }}">
                            {{ $pharmacySupplierPayable->due_date?->format('d M Y') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Created By
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacySupplierPayable->createdBy?->name ?? 'System' }}
                        </div>

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- FINANCIAL SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Original Amount
                    </div>

                    <div class="mt-2 text-xl font-bold text-slate-900">
                        ₹{{ number_format((float) $pharmacySupplierPayable->original_amount, 2) }}
                    </div>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Return Adjustment
                    </div>

                    <div class="mt-2 text-xl font-bold text-amber-700">
                        -₹{{ number_format((float) $pharmacySupplierPayable->return_adjustment, 2) }}
                    </div>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Other Adjustment
                    </div>

                    <div class="mt-2 text-xl font-bold text-slate-700">
                        -₹{{ number_format((float) $pharmacySupplierPayable->other_adjustment, 2) }}
                    </div>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Paid
                    </div>

                    <div class="mt-2 text-xl font-bold text-emerald-700">
                        ₹{{ number_format((float) $pharmacySupplierPayable->paid_amount, 2) }}
                    </div>

                </div>


                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-500">
                        Outstanding
                    </div>

                    <div class="mt-2 text-2xl font-bold text-blue-800">
                        ₹{{ number_format((float) $pharmacySupplierPayable->outstanding_amount, 2) }}
                    </div>

                </div>


            </div>



            {{-- ========================================================= --}}
            {{-- PAYMENT ENTRY --}}
            {{-- ========================================================= --}}

            @if ($pharmacySupplierPayable->status !== 'paid')

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Record Supplier Payment
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Payment cannot exceed the current outstanding amount.
                        </p>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('pharmacy.supplier-payables.payments.store', $pharmacySupplierPayable) }}"
                        class="p-6"
                    >

                        @csrf


                        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">


                            <div>

                                <label
                                    for="payment_date"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Payment Date *
                                </label>

                                <input
                                    id="payment_date"
                                    name="payment_date"
                                    type="date"
                                    required
                                    value="{{ old('payment_date', today()->format('Y-m-d')) }}"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                            </div>


                            <div>

                                <label
                                    for="amount"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Amount *
                                </label>

                                <input
                                    id="amount"
                                    name="amount"
                                    type="number"
                                    min="0.01"
                                    max="{{ $pharmacySupplierPayable->outstanding_amount }}"
                                    step="0.01"
                                    required
                                    value="{{ old('amount') }}"
                                    placeholder="0.00"
                                    class="w-full rounded-lg border-slate-300 text-right shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                                <div class="mt-1 text-xs text-slate-500">
                                    Maximum ₹{{ number_format((float) $pharmacySupplierPayable->outstanding_amount, 2) }}
                                </div>

                            </div>

<div>

    <label
        for="finance_account_id"
        class="mb-2 block text-sm font-semibold text-slate-700"
    >
        Paid From Finance Account *
    </label>

    <select
        id="finance_account_id"
        name="finance_account_id"
        required
        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
    >

        <option value="">
            Select finance account
        </option>

        @foreach ($financeAccounts as $account)
            <option
                value="{{ $account->id }}"
                @selected((string) old('finance_account_id') === (string) $account->id)
            >
                {{ $account->name }}
                @if ($account->code)
                    ({{ $account->code }})
                @endif
            </option>
        @endforeach

    </select>

    @error('finance_account_id')
        <div class="mt-1 text-sm text-red-600">
            {{ $message }}
        </div>
    @enderror

</div>
                            <div>

                                <label
                                    for="payment_method"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Payment Method *
                                </label>

                                <select
                                    id="payment_method"
                                    name="payment_method"
                                    required
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                                    <option value="">
                                        Select payment method
                                    </option>

                                    <option value="cash" @selected(old('payment_method') === 'cash')>
                                        Cash
                                    </option>

                                    <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>
                                        Bank Transfer
                                    </option>

                                    <option value="cheque" @selected(old('payment_method') === 'cheque')>
                                        Cheque
                                    </option>

                                    <option value="upi" @selected(old('payment_method') === 'upi')>
                                        UPI
                                    </option>

                                    <option value="neft" @selected(old('payment_method') === 'neft')>
                                        NEFT
                                    </option>

                                    <option value="rtgs" @selected(old('payment_method') === 'rtgs')>
                                        RTGS
                                    </option>

                                    <option value="imps" @selected(old('payment_method') === 'imps')>
                                        IMPS
                                    </option>

                                    <option value="other" @selected(old('payment_method') === 'other')>
                                        Other
                                    </option>

                                </select>

                            </div>


                            <div>

                                <label
                                    for="reference_no"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Reference No.
                                </label>

                                <input
                                    id="reference_no"
                                    name="reference_no"
                                    type="text"
                                    value="{{ old('reference_no') }}"
                                    placeholder="UTR / cheque / transaction no."
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                            </div>


                            <div>

                                <label
                                    for="bank_name"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Bank
                                </label>

                                <input
                                    id="bank_name"
                                    name="bank_name"
                                    type="text"
                                    value="{{ old('bank_name') }}"
                                    placeholder="Bank name"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                            </div>


                            <div class="md:col-span-2 lg:col-span-3">

                                <label
                                    for="remarks"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Remarks
                                </label>

                                <input
                                    id="remarks"
                                    name="remarks"
                                    type="text"
                                    maxlength="3000"
                                    value="{{ old('remarks') }}"
                                    placeholder="Optional payment remarks"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                            </div>


                        </div>


                        <div class="mt-6 flex justify-end">

                            <button
                                type="submit"
                                onclick="return confirm('Record this supplier payment?');"
                                class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                Record Payment
                            </button>

                        </div>

                    </form>

                </div>

            @else

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-5">

                    <div class="font-semibold text-emerald-800">
                        This payable is fully settled.
                    </div>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- PAYMENT HISTORY --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Payment History
                    </h3>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Date
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Payment No.
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Method
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Finance Account
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Reference
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Amount
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Recorded By
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse ($pharmacySupplierPayable->payments->sortByDesc('payment_date') as $payment)

                                <tr>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">
                                        {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">
                                        {{ $payment->payment_no }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">

                                        @if ($payment->financeAccount)

                                            <div class="text-sm font-semibold text-slate-800">
                                                {{ $payment->financeAccount->name }}
                                            </div>

                                            @if ($payment->financeAccount->code)
                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ $payment->financeAccount->code }}
                                                </div>
                                            @endif

                                        @else

                                            <span class="text-sm text-slate-400">
                                                Not mapped
                                            </span>

                                        @endif

                                    </td>

                                    <td class="px-5 py-4">

                                        <div class="text-sm font-medium text-slate-800">
                                            {{ $payment->reference_no ?: '—' }}
                                        </div>

                                        @if ($payment->bank_name)
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $payment->bank_name }}
                                            </div>
                                        @endif

                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-right font-bold text-emerald-700">
                                        ₹{{ number_format((float) $payment->amount, 2) }}
                                    </td>

                                    <td class="px-5 py-4">

                                        <div class="text-sm font-medium text-slate-700">
                                            {{ $payment->createdBy?->name ?? 'System' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $payment->created_at?->format('d M Y, h:i A') }}
                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center text-sm text-slate-500"
                                    >
                                        No supplier payments recorded yet.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- REMARKS --}}

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h3 class="font-semibold text-slate-900">
                    Remarks
                </h3>

                <div class="mt-3 text-sm leading-6 text-slate-600">
                    {{ $pharmacySupplierPayable->remarks ?: 'No remarks.' }}
                </div>

            </div>


        </div>

    </div>

</x-app-layout>