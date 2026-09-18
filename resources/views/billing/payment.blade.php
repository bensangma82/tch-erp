<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Investigation Payment
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Review the order and collect payment.
                </p>

            </div>

            <a
                href="{{ route('billing.index') }}"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                Back to Billing Counter
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">


            {{-- VALIDATION ERRORS --}}
            @if ($errors->any())

                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <div class="text-sm font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-inside list-disc text-sm text-red-600">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- ORDER / PATIENT DETAILS --}}
            <div class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-gray-800">
                                {{ $serviceOrder->order_no }}
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                {{ $serviceOrder->ordered_at->format('d M Y, h:i A') }}
                            </p>

                        </div>

                        @if ($serviceOrder->status === 'paid')

                            <span class="inline-flex whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                Paid
                            </span>

                        @elseif ($serviceOrder->status === 'authorized')

                            <span class="inline-flex whitespace-nowrap rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                Authorized
                            </span>

                        @else

                            <span class="inline-flex whitespace-nowrap rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                                Awaiting Payment
                            </span>

                        @endif

                    </div>

                </div>


                <div class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Patient
                        </div>

                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $serviceOrder->patient->full_name }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">

                            @if ($serviceOrder->patient->age !== null)

                                {{ $serviceOrder->patient->age }} yrs

                            @else

                                Age —

                            @endif

                            /

                            {{ $serviceOrder->patient->sex ?: '—' }}

                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            UHID / MRD
                        </div>

                        <div class="mt-1 text-sm font-semibold text-gray-900">
                            {{ $serviceOrder->patient->uhid }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            MRD:
                            {{ $serviceOrder->patient->mrd_number ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Department
                        </div>

                        <div class="mt-1 text-sm text-gray-900">
                            {{ $serviceOrder->encounter->department?->name ?? '—' }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            Queue:
                            {{ $serviceOrder->encounter->queue_number }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Doctor
                        </div>

                        <div class="mt-1 text-sm text-gray-900">
                            {{ $serviceOrder->encounter->doctor?->full_name ?? 'Unassigned' }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            {{ $serviceOrder->encounter->encounter_no }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">


                {{-- INVESTIGATION ITEMS --}}
                <div class="lg:col-span-2">

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                            <h3 class="font-semibold text-gray-800">
                                Investigation Charges
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Charges are taken from the order snapshot and cannot be changed here.
                            </p>

                        </div>


                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-gray-200">

                                <thead class="bg-white">

                                    <tr>

                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Service
                                        </th>

                                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Qty
                                        </th>

                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Rate
                                        </th>

                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Amount
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-gray-100">

                                    @foreach ($serviceOrder->items as $item)

                                        <tr>

                                            <td class="px-5 py-4">

                                                <div class="text-sm font-semibold text-gray-900">
                                                    {{ $item->service_name }}
                                                </div>

                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ $item->service_code }}
                                                    ·
                                                    {{ ucfirst($item->category) }}
                                                </div>

                                            </td>


                                            <td class="px-5 py-4 text-center text-sm text-gray-700">
                                                {{ $item->quantity }}
                                            </td>


                                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-gray-700">
                                                ₹{{ number_format((float) $item->unit_price, 2) }}
                                            </td>


                                            <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-gray-900">
                                                ₹{{ number_format((float) $item->amount, 2) }}
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>


                        <div class="border-t border-gray-200 bg-gray-50 px-6 py-5">

                            <div class="flex items-center justify-end gap-8">

                                <span class="text-sm font-semibold text-gray-600">
                                    Total Payable
                                </span>

                                <span class="text-2xl font-bold text-gray-900">
                                    ₹{{ number_format((float) $total, 2) }}
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- PAYMENT PANEL --}}
                <div>

                    <form
                        method="POST"
                        action="{{ route('billing.payment.store', $serviceOrder) }}"
                        id="paymentForm"
                    >

                        @csrf


                        <div class="sticky top-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                            <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">

                                <h3 class="font-semibold text-gray-800">
                                    Collect Payment
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Select the payment method and confirm the amount.
                                </p>

                            </div>


                            <div class="space-y-5 p-5">


                                {{-- PAYMENT MODE --}}
                                <div>

                                    <label
                                        for="payment_mode"
                                        class="mb-1 block text-sm font-medium text-gray-700"
                                    >
                                        Payment Mode *
                                    </label>

                                    <select
                                        id="payment_mode"
                                        name="payment_mode"
                                        required
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    >

                                        <option value="">
                                            Select Payment Mode
                                        </option>

                                        <option
                                            value="cash"
                                            @selected(old('payment_mode') === 'cash')
                                        >
                                            Cash
                                        </option>

                                        <option
                                            value="upi"
                                            @selected(old('payment_mode') === 'upi')
                                        >
                                            UPI
                                        </option>

                                        <option
                                            value="card"
                                            @selected(old('payment_mode') === 'card')
                                        >
                                            Card
                                        </option>

                                        <option
                                            value="credit"
                                            @selected(old('payment_mode') === 'credit')
                                        >
                                            Credit
                                        </option>

                                        <option
                                            value="mhis"
                                            @selected(old('payment_mode') === 'mhis')
                                        >
                                            MHIS
                                        </option>

                                    </select>

                                </div>


                                {{-- AMOUNT RECEIVED --}}
                                <div>

                                    <label
                                        for="amount_received"
                                        class="mb-1 block text-sm font-medium text-gray-700"
                                    >
                                        Amount Received
                                    </label>

                                    <input
                                        id="amount_received"
                                        type="number"
                                        name="amount_received"
                                        value="{{ old('amount_received') }}"
                                        min="0"
                                        max="9999999.99"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="w-full rounded-lg border-gray-300 text-lg font-semibold shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    >

                                    <p
                                        id="amountHelp"
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        Select a payment mode.
                                    </p>

                                </div>


                                {{-- TRANSACTION REFERENCE --}}
                                <div
                                    id="referenceWrapper"
                                    class="hidden"
                                >

                                    <label
                                        for="transaction_reference"
                                        class="mb-1 block text-sm font-medium text-gray-700"
                                    >
                                        Transaction / Reference No.
                                    </label>

                                    <input
                                        id="transaction_reference"
                                        type="text"
                                        name="transaction_reference"
                                        value="{{ old('transaction_reference') }}"
                                        placeholder="UPI or card reference"
                                        autocomplete="off"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    >

                                </div>


                                {{-- PAYMENT SUMMARY --}}
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">

                                    <div class="space-y-3">

                                        <div class="flex items-center justify-between">

                                            <span class="text-sm text-gray-600">
                                                Bill Total
                                            </span>

                                            <span
                                                id="billTotal"
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                ₹{{ number_format((float) $total, 2) }}
                                            </span>

                                        </div>


                                        <div class="flex items-center justify-between">

                                            <span class="text-sm text-gray-600">
                                                Applied Payment
                                            </span>

                                            <span
                                                id="appliedPayment"
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                ₹0.00
                                            </span>

                                        </div>


                                        <div class="flex items-center justify-between">

                                            <span class="text-sm text-gray-600">
                                                Balance
                                            </span>

                                            <span
                                                id="balanceAmount"
                                                class="text-sm font-semibold text-red-600"
                                            >
                                                ₹{{ number_format((float) $total, 2) }}
                                            </span>

                                        </div>


                                        <div
                                            id="changeRow"
                                            class="hidden border-t border-gray-200 pt-3"
                                        >

                                            <div class="flex items-center justify-between">

                                                <span class="text-sm font-semibold text-gray-700">
                                                    Change to Return
                                                </span>

                                                <span
                                                    id="changeAmount"
                                                    class="text-lg font-bold text-green-700"
                                                >
                                                    ₹0.00
                                                </span>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                {{-- CREDIT/MHIS NOTE --}}
                                <div
                                    id="creditNote"
                                    class="hidden rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700"
                                >
                                    No cash receipt will be generated for a zero-value Credit/MHIS transaction. The investigation order will be marked as authorized.
                                </div>


                                {{-- REMARKS --}}
                                <div>

                                    <label
                                        for="remarks"
                                        class="mb-1 block text-sm font-medium text-gray-700"
                                    >
                                        Remarks
                                    </label>

                                    <textarea
                                        id="remarks"
                                        name="remarks"
                                        rows="2"
                                        placeholder="Optional"
                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    >{{ old('remarks') }}</textarea>

                                </div>


                                <button
                                    type="submit"
                                    id="collectButton"
                                    class="w-full rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                                >
                                    Collect Payment
                                </button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const total =
                {{ number_format((float) $total, 2, '.', '') }};

            const paymentMode =
                document.getElementById('payment_mode');

            const amountReceived =
                document.getElementById('amount_received');

            const amountHelp =
                document.getElementById('amountHelp');

            const referenceWrapper =
                document.getElementById('referenceWrapper');

            const transactionReference =
                document.getElementById('transaction_reference');

            const appliedPayment =
                document.getElementById('appliedPayment');

            const balanceAmount =
                document.getElementById('balanceAmount');

            const changeRow =
                document.getElementById('changeRow');

            const changeAmount =
                document.getElementById('changeAmount');

            const creditNote =
                document.getElementById('creditNote');

            const collectButton =
                document.getElementById('collectButton');


            function money(value) {

                return '₹' +
                    Number(value)
                        .toFixed(2);

            }


            function updatePaymentUI() {

                const mode =
                    paymentMode.value;

                let received =
                    parseFloat(
                        amountReceived.value
                    );

                if (Number.isNaN(received)) {
                    received = 0;
                }


                /*
                |--------------------------------------------------------------------------
                | Default amount / input state
                |--------------------------------------------------------------------------
                */

                if (mode === 'cash') {

                    amountReceived.disabled = false;

                    amountReceived.readOnly = false;

                    amountHelp.textContent =
                        'Enter the cash tendered by the patient. Amount may be greater than the bill total.';

                    referenceWrapper.classList.add(
                        'hidden'
                    );

                    creditNote.classList.add(
                        'hidden'
                    );


                } else if (
                    mode === 'upi' ||
                    mode === 'card'
                ) {

                    amountReceived.disabled = false;

                    amountReceived.readOnly = false;

                    amountHelp.textContent =
                        'Enter the amount received. It cannot exceed the bill total.';

                    referenceWrapper.classList.remove(
                        'hidden'
                    );

                    creditNote.classList.add(
                        'hidden'
                    );


                } else if (
                    mode === 'credit' ||
                    mode === 'mhis'
                ) {

                    amountReceived.value =
                        '0.00';

                    amountReceived.disabled = false;

                    amountReceived.readOnly = true;

                    received = 0;

                    amountHelp.textContent =
                        'No cash collection is required at this stage.';

                    referenceWrapper.classList.add(
                        'hidden'
                    );

                    creditNote.classList.remove(
                        'hidden'
                    );


                } else {

                    amountReceived.disabled = false;

                    amountReceived.readOnly = false;

                    amountHelp.textContent =
                        'Select a payment mode.';

                    referenceWrapper.classList.add(
                        'hidden'
                    );

                    creditNote.classList.add(
                        'hidden'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Calculation
                |--------------------------------------------------------------------------
                */

                let applied = 0;
                let balance = total;
                let change = 0;


                if (mode === 'cash') {

                    applied =
                        Math.min(
                            received,
                            total
                        );

                    balance =
                        Math.max(
                            total - applied,
                            0
                        );

                    change =
                        Math.max(
                            received - total,
                            0
                        );


                } else if (
                    mode === 'upi' ||
                    mode === 'card'
                ) {

                    applied =
                        Math.min(
                            received,
                            total
                        );

                    balance =
                        Math.max(
                            total - applied,
                            0
                        );


                } else if (
                    mode === 'credit' ||
                    mode === 'mhis'
                ) {

                    applied = 0;
                    balance = total;
                    change = 0;
                }


                appliedPayment.textContent =
                    money(applied);

                balanceAmount.textContent =
                    money(balance);

                changeAmount.textContent =
                    money(change);


                if (
                    mode === 'cash' &&
                    change > 0
                ) {

                    changeRow.classList.remove(
                        'hidden'
                    );

                } else {

                    changeRow.classList.add(
                        'hidden'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Button text
                |--------------------------------------------------------------------------
                */

                if (mode === 'credit') {

                    collectButton.textContent =
                        'Authorize Credit';

                } else if (mode === 'mhis') {

                    collectButton.textContent =
                        'Authorize MHIS';

                } else {

                    collectButton.textContent =
                        'Collect Payment';
                }

            }


            /*
            |--------------------------------------------------------------------------
            | Payment mode defaults
            |--------------------------------------------------------------------------
            */

            paymentMode.addEventListener(
                'change',
                function () {

                    const mode =
                        paymentMode.value;


                    if (
                        mode === 'cash' ||
                        mode === 'upi' ||
                        mode === 'card'
                    ) {

                        amountReceived.value =
                            total.toFixed(2);

                    } else if (
                        mode === 'credit' ||
                        mode === 'mhis'
                    ) {

                        amountReceived.value =
                            '0.00';
                    }


                    updatePaymentUI();

                }
            );


            amountReceived.addEventListener(
                'input',
                updatePaymentUI
            );


            /*
            |--------------------------------------------------------------------------
            | Initial state after validation error
            |--------------------------------------------------------------------------
            */

            updatePaymentUI();

        });
    </script>

</x-app-layout>