<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                   {{ strtolower((string) $invoice->invoice_type) === 'opd'
    ? 'OPD Payment'
    : 'Outstanding Investigation Payment' }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ strtolower((string) $invoice->invoice_type) === 'opd'
    ? 'Collect payment for this OPD registration.'
    : 'Collect the remaining balance against an existing investigation invoice.' }}
            </div>

            <a
                href="{{ route('billing.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Back to Billing
            </a>
        </div>
    </x-slot>


    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <div class="font-semibold text-red-800">
                        Payment could not be processed.
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            {{-- Patient / Invoice --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">
                        Invoice Details
                    </h3>
                </div>

                <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Invoice
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $invoice->invoice_no }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Patient
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $invoice->patient?->full_name ?? $invoice->patient?->name ?? '—' }}
                        </div>

                        @if ($invoice->patient?->uhid)
                            <div class="mt-1 text-sm text-slate-500">
                                UHID: {{ $invoice->patient->uhid }}
                            </div>
                        @endif
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Department
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $invoice->encounter?->department?->name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Invoice Date
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $invoice->invoice_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>

                </div>
            </div>


            {{-- Financial Summary --}}
            <div class="grid gap-4 sm:grid-cols-3">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Invoice Total
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        ₹{{ number_format((float) $invoice->total_amount, 2) }}
                    </div>
                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Already Paid
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        ₹{{ number_format((float) $invoice->paid_amount, 2) }}
                    </div>
                </div>


                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Balance Due
                    </div>

                    <div class="mt-2 text-2xl font-bold text-amber-900">
                        ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                    </div>
                </div>

            </div>


            {{-- Previous Payments --}}
            @if ($invoice->payments->isNotEmpty())

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
                                        Receipt
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Date
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Mode
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Amount
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">

                                @foreach ($invoice->payments as $previousPayment)

                                    <tr>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-900">
                                            {{ $previousPayment->receipt_no }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                            {{ $previousPayment->payment_date?->format('d M Y h:i A') ?? '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">

                                            @if ($previousPayment->payment_mode === 'staff_medical_benefit')
                                                Staff Medical Benefit
                                            @else
                                                {{ strtoupper($previousPayment->payment_mode) }}
                                            @endif

                                        </td>

                                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-900">
                                            ₹{{ number_format((float) $previousPayment->amount, 2) }}
                                        </td>
                                    </tr>

                                @endforeach

                            </tbody>
                        </table>

                    </div>
                </div>

            @endif


            {{-- Collect Outstanding Balance --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Collect Outstanding Balance
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Record an additional payment against this invoice.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('billing.invoice.payment.store', $invoice) }}"
                    class="space-y-6 p-6"
                    id="outstandingPaymentForm"
                >

                    @csrf


                    <div class="grid gap-5 md:grid-cols-2">

                        {{-- Payment Mode --}}
                        <div>

                            <label
                                for="payment_mode"
                                class="block text-sm font-medium text-slate-700"
                            >
                                Payment Mode
                            </label>

                            <select
                                name="payment_mode"
                                id="payment_mode"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="">Select payment mode</option>

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

                                @if ($staffMedicalBenefit)
    <option
        value="staff_medical_benefit"
        @selected(
            old('payment_mode') === 'staff_medical_benefit'
        )
    >
        Staff Medical Benefit
    </option>
@endif

                            </select>

                        </div>


                        {{-- Amount Received --}}
                        <div>

                            <label
                                for="amount_received"
                                class="block text-sm font-medium text-slate-700"
                            >
                                Amount Received
                            </label>

                            <div class="relative mt-2">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                    ₹
                                </div>

                                <input
                                    type="number"
                                    name="amount_received"
                                    id="amount_received"
                                    value="{{ old('amount_received', number_format((float) $invoice->balance_amount, 2, '.', '')) }}"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    class="block w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                >

                            </div>

                            <p
                                id="amountHelp"
                                class="mt-2 text-xs text-slate-500"
                            >
                                Outstanding balance:
                                ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                            </p>

                        </div>


                        {{-- Transaction Reference --}}
                        <div>

                            <label
                                for="transaction_reference"
                                class="block text-sm font-medium text-slate-700"
                            >
                                Transaction Reference
                            </label>

                            <input
                                type="text"
                                name="transaction_reference"
                                id="transaction_reference"
                                value="{{ old('transaction_reference') }}"
                                maxlength="255"
                                class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                            <p class="mt-2 text-xs text-slate-500">
                                Optional for cash. Enter UPI/card reference where applicable.
                            </p>

                        </div>


                        {{-- Remarks --}}
                        <div>

                            <label
                                for="remarks"
                                class="block text-sm font-medium text-slate-700"
                            >
                                Remarks
                            </label>

                            <input
                                type="text"
                                name="remarks"
                                id="remarks"
                                value="{{ old('remarks') }}"
                                maxlength="2000"
                                class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>

                    </div>


                    {{-- Payment Preview --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <div class="grid gap-4 sm:grid-cols-3">

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Balance Before
                                </div>

                                <div class="mt-1 text-lg font-semibold text-slate-900">
                                    ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                                </div>
                            </div>


                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Payment Applied
                                </div>

                                <div
                                    id="appliedPayment"
                                    class="mt-1 text-lg font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </div>
                            </div>


                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Balance After
                                </div>

                                <div
                                    id="balanceAfter"
                                    class="mt-1 text-lg font-semibold text-slate-900"
                                >
                                    ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                                </div>
                            </div>

                        </div>


                        <div
                            id="changeRow"
                            class="mt-4 hidden border-t border-slate-200 pt-4"
                        >
                            <span class="text-sm font-medium text-slate-600">
                                Change to Return:
                            </span>

                            <span
                                id="changeAmount"
                                class="ml-2 font-semibold text-slate-900"
                            >
                                ₹0.00
                            </span>
                        </div>

                    </div>


                    <div class="flex items-center justify-end gap-3">

                        <a
                            href="{{ route('billing.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            id="submitPaymentButton"
                            class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-sm"
                            style="background-color: #0891b2;"
                        >
                            Collect Payment
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const balance =
                {{ json_encode(round((float) $invoice->balance_amount, 2)) }};


                          const staffMedicalBenefitBalance =
    {{ json_encode(
        $staffMedicalBenefit
            ? round(
                (float) $staffMedicalBenefit['balance'],
                2
            )
            : 0
    ) }};

            const paymentMode =
                document.getElementById('payment_mode');

            const amountReceived =
                document.getElementById('amount_received');

            const appliedPayment =
                document.getElementById('appliedPayment');

            const balanceAfter =
                document.getElementById('balanceAfter');

            const changeRow =
                document.getElementById('changeRow');

            const changeAmount =
                document.getElementById('changeAmount');


            function money(value) {
                return '₹' + Number(value).toFixed(2);
            }


            function updatePreview() {

                const mode = paymentMode.value;

                let received =
                    parseFloat(amountReceived.value);

                if (Number.isNaN(received)) {
                    received = 0;
                }

                received =
                    Math.max(received, 0);

                    if (mode === 'staff_medical_benefit') {

    received =
        Math.min(
            balance,
            staffMedicalBenefitBalance
        );

    amountReceived.value =
        received.toFixed(2);

    amountReceived.readOnly =
        true;

} else {

    amountReceived.readOnly =
        false;
}


                let applied =
                    Math.min(received, balance);

                let remaining =
                    Math.max(balance - applied, 0);

                let change = 0;


                if (
                    mode === 'cash'
                    && received > balance
                ) {
                    change =
                        received - balance;
                }


                appliedPayment.textContent =
                    money(applied);

                balanceAfter.textContent =
                    money(remaining);


                if (change > 0) {

                    changeAmount.textContent =
                        money(change);

                    changeRow.classList.remove('hidden');

                } else {

                    changeAmount.textContent =
                        money(0);

                    changeRow.classList.add('hidden');
                }
            }


            paymentMode.addEventListener(
                'change',
                updatePreview
            );

            amountReceived.addEventListener(
                'input',
                updatePreview
            );


            updatePreview();
        });
    </script>

</x-app-layout>