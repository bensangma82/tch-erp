<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Inpatient Running Bill
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    View the current inpatient billing account, charges and advances.
                </p>
            </div>

            <a
                href="{{ route('ip-billing.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back to IP Billing
            </a>

        </div>
    </x-slot>


    @php
        $patient = $admission->patient;

        $currentAllocation =
            $admission->currentBedAllocation;

        $currentBed =
            $currentAllocation?->bed
            ?? $admission->bed;

        $ward =
            $currentBed?->ward;

        $activeCharges =
            $account->charges
                ->where('status', 'active');

        $activeAdvances =
            $account->advances
                ->where('status', 'active');

        $mhisClaim =
            $account->mhisClaims
                ->first();

        $mhisApprovedAmount =
            $account->mhisClaims
                ->whereIn(
                    'status',
                    [
                        'approved',
                        'submitted',
                        'settled',
                    ]
                )
                ->sum(
                    fn ($claim) =>
                        (float) $claim->approved_amount
                );

        $mhisReceivedAmount =
            $account->mhisReceipts
                ->where('status', 'active')
                ->sum(
                    fn ($receipt) =>
                        (float) $receipt->amount
                );

        $mhisOutstandingAmount =
            max(
                (float) $mhisApprovedAmount
                - (float) $mhisReceivedAmount,
                0
            );

        $isFinalized =
            $account->status === 'finalized';
    @endphp


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <ul class="list-inside list-disc text-sm text-red-700">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- PATIENT / ADMISSION SUMMARY --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="grid gap-0 lg:grid-cols-4">

                    <div class="border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Patient
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ $patient?->full_name ?? '—' }}
                        </div>

                        <div class="mt-3 space-y-1 text-sm text-slate-600">

                            <div>
                                UHID:
                                <span class="font-semibold text-slate-800">
                                    {{ $patient?->uhid ?? '—' }}
                                </span>
                            </div>

                            <div>
                                MRD:
                                <span class="font-semibold text-slate-800">
                                    {{ $patient?->mrd_number ?: '—' }}
                                </span>
                            </div>

                        </div>

                    </div>


                    <div class="border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Admission
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ $admission->admission_no }}
                        </div>

                        <div class="mt-3 space-y-1 text-sm text-slate-600">

                            <div>
                                Admitted:
                                <span class="font-semibold text-slate-800">
                                    {{ $admission->admitted_at?->format('d M Y, h:i A') ?? '—' }}
                                </span>
                            </div>

                            <div>
                                Type:
                                <span class="font-semibold text-slate-800">
                                    {{ ucfirst($admission->admission_type ?? 'IPD') }}
                                </span>
                            </div>

                        </div>

                    </div>


                    <div class="border-b border-slate-200 p-5 lg:border-b-0 lg:border-r">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Ward / Bed
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ $ward?->name ?? '—' }}
                        </div>

                        <div class="mt-3 space-y-1 text-sm text-slate-600">

                            <div>
                                Bed:
                                <span class="font-semibold text-slate-800">
                                    {{ $currentBed?->bed_number ?? '—' }}
                                </span>
                            </div>

                            <div>
                                Type:
                                <span class="font-semibold text-slate-800">
                                    {{ $currentBed?->bed_type ?? '—' }}
                                </span>
                            </div>

                        </div>

                    </div>


                    <div class="p-5">

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Billing Account
                        </div>

                        <div class="mt-1 font-mono text-lg font-bold text-slate-900">
                            {{ $account->account_no }}
                        </div>

                        <div class="mt-3">

                            <span
                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                    @if ($account->status === 'open')
                                        bg-emerald-100 text-emerald-700
                                    @elseif ($account->status === 'finalized')
                                        bg-blue-100 text-blue-700
                                    @elseif ($account->status === 'finalizing')
                                        bg-amber-100 text-amber-700
                                    @elseif ($account->status === 'settled')
                                        bg-indigo-100 text-indigo-700
                                    @else
                                        bg-slate-100 text-slate-700
                                    @endif
                                "
                            >
                                {{ ucfirst($account->status) }}
                            </span>

                        </div>

                        @if ($isFinalized && $account->final_bill_no)
                            <div class="mt-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Final Bill No.
                                </div>

                                <div class="mt-1 font-mono text-sm font-bold text-blue-700">
                                    {{ $account->final_bill_no }}
                                </div>
                            </div>
                        @endif

                    </div>

                </div>

            </div>


            {{-- FINANCIAL SUMMARY --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Running Charges
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        ₹{{ number_format((float) $account->net_amount, 2) }}
                    </div>

                </div>


                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                        Advance / Deposit
                    </div>

                    <div class="mt-2 text-2xl font-bold text-blue-700">
                        ₹{{ number_format((float) $account->advance_amount, 2) }}
                    </div>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Other Payments
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        ₹{{ number_format((float) $account->paid_amount, 2) }}
                    </div>

                </div>


                <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-violet-600">
                        MHIS Approved
                    </div>

                    <div class="mt-2 text-2xl font-bold text-violet-700">
                        ₹{{ number_format((float) $mhisApprovedAmount, 2) }}
                    </div>

                    <div class="mt-3 space-y-1 text-xs font-medium text-violet-700">
                        <div class="flex justify-between gap-3">
                            <span>Received</span>
                            <span>₹{{ number_format((float) $mhisReceivedAmount, 2) }}</span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Outstanding</span>
                            <span>₹{{ number_format((float) $mhisOutstandingAmount, 2) }}</span>
                        </div>

                        <div class="pt-1 text-violet-600">
                            {{ $mhisClaim ? ucfirst($mhisClaim->status) : 'No claim' }}
                        </div>
                    </div>

                </div>


                <div
                    class="rounded-2xl border p-5 shadow-sm
                        @if ((float) $account->balance_amount > 0)
                            border-red-200 bg-red-50
                        @else
                            border-emerald-200 bg-emerald-50
                        @endif
                    "
                >

                    <div
                        class="text-xs font-semibold uppercase tracking-wide
                            @if ((float) $account->balance_amount > 0)
                                text-red-600
                            @else
                                text-emerald-600
                            @endif
                        "
                    >
                        Balance
                    </div>

                    <div
                        class="mt-2 text-2xl font-bold
                            @if ((float) $account->balance_amount > 0)
                                text-red-700
                            @else
                                text-emerald-700
                            @endif
                        "
                    >
                        ₹{{ number_format((float) $account->balance_amount, 2) }}
                    </div>

                </div>

            </div>


            {{-- CURRENT ACTIONS --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>

                        <h3 class="text-base font-semibold text-slate-900">
                            Billing Actions
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            @if ($isFinalized)
                                This inpatient bill has been finalized. Ordinary billing entries are locked, but MHIS follow-up and receipts may continue.
                            @else
                                Generate bed charges, add inpatient charges, receive advances, update MHIS, or finalize the bill.
                            @endif
                        </p>

                    </div>


                    <div class="flex flex-wrap gap-2">

                        @if (! $isFinalized)

                            <form
                                method="POST"
                                action="{{ route('ip-billing.generate-bed-charges', $admission) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    onclick="return confirm('Generate bed charges for this admission up to the current date? Existing bed-day charges will not be duplicated.')"
                                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                                >
                                    Generate Bed Charges
                                </button>
                            </form>


                            <a
                                href="{{ route('ip-billing.investigations.create', $admission) }}"
                                class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700"
                            >
                                Add Investigations
                            </a>


                            <button
                                type="button"
                                id="open-charge-modal"
                                class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700"
                            >
                                Add Charge
                            </button>

@if ($account->status === 'open')
    <a
        href="{{ route('pharmacy.dispensing.create', ['admission_id' => $admission->id]) }}"
        class="inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700"
    >
        Dispense Medicines
    </a>
@endif
                            <button
                                type="button"
                                id="open-advance-modal"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                            >
                                Receive Advance
                            </button>

                        @endif


                        <button
                            type="button"
                            id="open-mhis-modal"
                            class="inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700"
                        >
                            {{ $mhisClaim ? 'Update MHIS' : 'MHIS' }}
                        </button>


                        @if (
                            $mhisClaim
                            && in_array(
                                $mhisClaim->status,
                                ['approved', 'submitted', 'settled'],
                                true
                            )
                            && $mhisOutstandingAmount > 0
                        )
                            <button
                                type="button"
                                id="open-mhis-receipt-modal"
                                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                Record MHIS Payment
                            </button>
                        @endif


                        @if (! $isFinalized)

                            <form
                                method="POST"
                                action="{{ route('ip-billing.finalize', $admission) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    onclick="return confirm('Finalize this inpatient bill? After finalization, ordinary charges, bed charges and advances will be locked. MHIS processing can still continue.')"
                                    class="inline-flex items-center justify-center rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-800"
                                >
                                    Finalize Bill
                                </button>
                            </form>

                        @else

                            <a
                                href="{{ route('ip-billing.final-bill', $admission) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                            >
                                Print Final Bill
                            </a>

                        @endif

                    </div>

                </div>


                @if (! $isFinalized)

                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <span class="font-semibold">Before finalizing:</span>
                        make sure all bed days, services, procedures, investigations, pharmacy items, discounts, advances and MHIS approval details have been posted correctly.
                    </div>

                @else

                    <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        <div class="font-semibold">
                            Final Bill {{ $account->final_bill_no }}
                        </div>

                        <div class="mt-1">
                            Finalized on
                            {{ $account->finalized_at?->format('d M Y, h:i A') ?? '—' }}.
                            The ordinary IP billing ledger is now locked.
                        </div>
                    </div>

                @endif

            </div>


            {{-- CHARGES --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="text-base font-semibold text-slate-900">
                                Running Charges
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                All active inpatient charges for this admission.
                            </p>

                        </div>


                        <div class="text-sm font-semibold text-slate-600">
                            {{ $activeCharges->count() }}
                            item{{ $activeCharges->count() === 1 ? '' : 's' }}
                        </div>

                    </div>

                </div>


                @if ($activeCharges->count() > 0)

                    <div class="overflow-x-auto">

                        <table class="min-w-[1000px] w-full">

                            <thead class="border-b border-slate-200 bg-white">

                                <tr>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Date
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Type
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Description
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Qty
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Rate
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Discount
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Amount
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @foreach ($activeCharges as $charge)

                                    <tr>

                                        <td class="px-5 py-4 text-sm text-slate-700">
                                            {{ $charge->charge_date?->format('d M Y, h:i A') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4">

                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                {{ ucfirst(str_replace('_', ' ', $charge->charge_type)) }}
                                            </span>

                                        </td>

                                        <td class="px-5 py-4">

                                            <div class="font-semibold text-slate-900">
                                                {{ $charge->description }}
                                            </div>

                                            @if ($charge->code)

                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ $charge->code }}
                                                </div>

                                            @endif

                                        </td>

                                        <td class="px-5 py-4 text-right text-sm text-slate-700">
                                            {{ number_format((float) $charge->quantity, 2) }}
                                        </td>

                                        <td class="px-5 py-4 text-right text-sm text-slate-700">
                                            ₹{{ number_format((float) $charge->unit_price, 2) }}
                                        </td>

                                        <td class="px-5 py-4 text-right text-sm text-slate-700">
                                            ₹{{ number_format((float) $charge->discount, 2) }}
                                        </td>

                                        <td class="px-5 py-4 text-right font-semibold text-slate-900">
                                            ₹{{ number_format((float) $charge->amount, 2) }}
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @else

                    <div class="px-6 py-12 text-center">

                        <div class="text-base font-semibold text-slate-700">
                            No charges recorded yet
                        </div>

                        <p class="mt-1 text-sm text-slate-500">
                            Bed charges and other inpatient services will appear here.
                        </p>

                    </div>

                @endif

            </div>


            {{-- ADVANCES --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="text-base font-semibold text-slate-900">
                                Advance / Deposit History
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Advance payments received against this admission.
                            </p>

                        </div>


                        <div class="text-sm font-semibold text-slate-600">
                            {{ $activeAdvances->count() }}
                            receipt{{ $activeAdvances->count() === 1 ? '' : 's' }}
                        </div>

                    </div>

                </div>


                @if ($activeAdvances->count() > 0)

                    <div class="overflow-x-auto">

                        <table class="min-w-[900px] w-full">

                            <thead class="border-b border-slate-200 bg-white">

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

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Reference
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Amount
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Receipt
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @foreach ($activeAdvances as $advance)

                                    <tr>

                                        <td class="px-5 py-4 font-mono text-sm font-semibold text-slate-900">
                                            {{ $advance->receipt_no }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-slate-700">
                                            {{ $advance->payment_date?->format('d M Y, h:i A') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-semibold uppercase text-slate-700">
                                            {{ $advance->payment_mode }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-slate-700">
                                            {{ $advance->transaction_reference ?: '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-right font-bold text-blue-700">
                                            ₹{{ number_format((float) $advance->amount, 2) }}
                                        </td>

                                        <td class="px-5 py-4 text-right">
                                            <a
                                                href="{{ route('ip-billing.advance.receipt', $advance) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                            >
                                                Print Receipt
                                            </a>
                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @else

                    <div class="px-6 py-12 text-center">

                        <div class="text-base font-semibold text-slate-700">
                            No advance received
                        </div>

                        <p class="mt-1 text-sm text-slate-500">
                            Deposits received from the patient will appear here.
                        </p>

                    </div>

                @endif

            </div>



            {{-- MHIS --}}
            <div class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">

                <div class="border-b border-violet-200 bg-violet-50 px-6 py-4">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h3 class="text-base font-semibold text-slate-900">
                                MHIS Claim
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Meghalaya Health Insurance Scheme claim and approval details for this admission.
                            </p>
                        </div>

                        <button
                            type="button"
                            id="open-mhis-modal-secondary"
                            class="inline-flex items-center justify-center rounded-lg border border-violet-300 bg-white px-4 py-2 text-sm font-semibold text-violet-700 shadow-sm hover:bg-violet-50"
                        >
                            {{ $mhisClaim ? 'Update MHIS' : 'Add MHIS' }}
                        </button>

                    </div>

                </div>


                @if ($mhisClaim)

                    <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Claim No.
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $mhisClaim->claim_no ?: '—' }}
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Authorization No.
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $mhisClaim->authorization_no ?: '—' }}
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Package
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $mhisClaim->package_name ?: '—' }}
                            </div>

                            @if ($mhisClaim->package_code)
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $mhisClaim->package_code }}
                                </div>
                            @endif
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status
                            </div>

                            <div class="mt-1">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                    @if ($mhisClaim->status === 'settled')
                                        bg-emerald-100 text-emerald-700
                                    @elseif (in_array($mhisClaim->status, ['approved', 'submitted'], true))
                                        bg-blue-100 text-blue-700
                                    @elseif ($mhisClaim->status === 'rejected')
                                        bg-red-100 text-red-700
                                    @else
                                        bg-amber-100 text-amber-700
                                    @endif
                                ">
                                    {{ ucfirst($mhisClaim->status) }}
                                </span>
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Claim Amount
                            </div>

                            <div class="mt-1 text-lg font-bold text-slate-900">
                                ₹{{ number_format((float) $mhisClaim->claim_amount, 2) }}
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Approved Amount
                            </div>

                            <div class="mt-1 text-lg font-bold text-violet-700">
                                ₹{{ number_format((float) $mhisClaim->approved_amount, 2) }}
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                MHIS Received
                            </div>

                            <div class="mt-1 text-lg font-bold text-emerald-700">
                                ₹{{ number_format((float) $mhisReceivedAmount, 2) }}
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Outstanding Receivable
                            </div>

                            <div class="mt-1 text-lg font-bold text-amber-700">
                                ₹{{ number_format((float) $mhisOutstandingAmount, 2) }}
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Approval Date
                            </div>

                            <div class="mt-1 text-sm font-semibold text-slate-900">
                                {{ $mhisClaim->approval_date?->format('d M Y') ?? '—' }}
                            </div>
                        </div>


                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Settlement Date
                            </div>

                            <div class="mt-1 text-sm font-semibold text-slate-900">
                                {{ $mhisClaim->settlement_date?->format('d M Y') ?? '—' }}
                            </div>
                        </div>


                        @if ($mhisClaim->remarks)
                            <div class="sm:col-span-2 lg:col-span-4">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Remarks
                                </div>

                                <div class="mt-1 text-sm text-slate-700">
                                    {{ $mhisClaim->remarks }}
                                </div>
                            </div>
                        @endif

                    </div>

                @else

                    <div class="px-6 py-10 text-center">

                        <div class="text-base font-semibold text-slate-700">
                            No MHIS claim recorded
                        </div>

                        <p class="mt-1 text-sm text-slate-500">
                            Add MHIS claim or authorization details if this admission is covered.
                        </p>

                    </div>

                @endif

            </div>



            {{-- MHIS RECEIPT HISTORY --}}
            @if ($mhisClaim)
                <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">

                    <div class="border-b border-emerald-200 bg-emerald-50 px-6 py-4">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    MHIS Payment History
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Partial payments received from MHIS against this approved claim.
                                </p>
                            </div>

                            @if (
                                in_array(
                                    $mhisClaim->status,
                                    ['approved', 'submitted', 'settled'],
                                    true
                                )
                                && $mhisOutstandingAmount > 0
                            )
                                <button
                                    type="button"
                                    id="open-mhis-receipt-modal-secondary"
                                    class="inline-flex items-center justify-center rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm hover:bg-emerald-50"
                                >
                                    Record Payment
                                </button>
                            @endif

                        </div>

                    </div>


                    @php
                        $activeMhisReceipts =
                            $mhisClaim->receipts
                                ->where('status', 'active');
                    @endphp


                    @if ($activeMhisReceipts->isEmpty())

                        <div class="px-6 py-10 text-center">

                            <div class="text-base font-semibold text-slate-700">
                                No MHIS payment received yet
                            </div>

                            <p class="mt-1 text-sm text-slate-500">
                                Approved MHIS remains as an outstanding hospital receivable until payment is actually received.
                            </p>

                        </div>

                    @else

                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-slate-200">

                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Date
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Payment Ref.
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Transaction / Bank Ref.
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Amount
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Received By
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Remarks
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100 bg-white">

                                    @foreach ($activeMhisReceipts as $receipt)

                                        <tr>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                                {{ $receipt->receipt_date?->format('d M Y') ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                {{ $receipt->payment_reference ?: '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <div>
                                                    {{ $receipt->transaction_reference ?: '—' }}
                                                </div>

                                                @if ($receipt->bank_reference)
                                                    <div class="mt-1 text-xs text-slate-500">
                                                        Bank: {{ $receipt->bank_reference }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-emerald-700">
                                                ₹{{ number_format((float) $receipt->amount, 2) }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                {{ $receipt->receivedBy?->name ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-slate-600">
                                                {{ $receipt->remarks ?: '—' }}
                                            </td>
                                        </tr>

                                    @endforeach

                                </tbody>

                                <tfoot class="border-t border-slate-200 bg-slate-50">

                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-slate-700">
                                            Total MHIS Received
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-emerald-700">
                                            ₹{{ number_format((float) $mhisReceivedAmount, 2) }}
                                        </td>

                                        <td colspan="2"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-slate-700">
                                            Outstanding MHIS Receivable
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-amber-700">
                                            ₹{{ number_format((float) $mhisOutstandingAmount, 2) }}
                                        </td>

                                        <td colspan="2"></td>
                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    @endif

                </div>
            @endif


            {{-- BED HISTORY --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <h3 class="text-base font-semibold text-slate-900">
                        Bed Allocation History
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        This history will be used to generate bed charges.
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-[900px] w-full">

                        <thead class="border-b border-slate-200">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Ward
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Bed
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Type
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Allocated
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Released
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @forelse ($admission->bedAllocations as $allocation)

                                <tr>

                                    <td class="px-5 py-4 text-sm font-semibold text-slate-900">
                                        {{ $allocation->bed?->ward?->name ?? '—' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        {{ $allocation->bed?->bed_number ?? '—' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        {{ $allocation->bed?->bed_type ?? '—' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        {{ $allocation->allocated_at?->format('d M Y, h:i A') ?? '—' }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-slate-700">
                                        {{ $allocation->released_at?->format('d M Y, h:i A') ?? 'Current' }}
                                    </td>

                                    <td class="px-5 py-4">

                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                                @if ($allocation->status === 'active')
                                                    bg-emerald-100 text-emerald-700
                                                @else
                                                    bg-slate-100 text-slate-700
                                                @endif
                                            "
                                        >
                                            {{ ucfirst($allocation->status) }}
                                        </span>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="6"
                                        class="px-5 py-8 text-center text-sm text-slate-500"
                                    >
                                        No bed allocation history found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>




    {{-- MHIS MODAL --}}
    <div
        id="mhis-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/40 p-4"
        aria-hidden="true"
    >
        <div class="my-8 w-full max-w-3xl rounded-2xl bg-white shadow-2xl">

            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">

                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        MHIS Claim / Authorization
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Record or update MHIS coverage for this inpatient admission.
                    </p>
                </div>

                <button
                    type="button"
                    id="close-mhis-modal"
                    class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    ✕
                </button>

            </div>


            <form
                method="POST"
                action="{{ route('ip-billing.mhis.store', $admission) }}"
                class="space-y-5 p-6"
            >
                @csrf


                <div class="grid gap-4 sm:grid-cols-2">

                    <div>
                        <label for="mhis_claim_no" class="block text-sm font-semibold text-slate-700">
                            Claim No.
                        </label>

                        <input
                            id="mhis_claim_no"
                            type="text"
                            name="claim_no"
                            value="{{ old('claim_no', $mhisClaim?->claim_no) }}"
                            maxlength="255"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>


                    <div>
                        <label for="mhis_authorization_no" class="block text-sm font-semibold text-slate-700">
                            Authorization No.
                        </label>

                        <input
                            id="mhis_authorization_no"
                            type="text"
                            name="authorization_no"
                            value="{{ old('authorization_no', $mhisClaim?->authorization_no) }}"
                            maxlength="255"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>


                    <div>
                        <label for="mhis_package_code" class="block text-sm font-semibold text-slate-700">
                            Package Code
                        </label>

                        <input
                            id="mhis_package_code"
                            type="text"
                            name="package_code"
                            value="{{ old('package_code', $mhisClaim?->package_code) }}"
                            maxlength="255"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>


                    <div>
                        <label for="mhis_package_name" class="block text-sm font-semibold text-slate-700">
                            Package / Procedure
                        </label>

                        <input
                            id="mhis_package_name"
                            type="text"
                            name="package_name"
                            value="{{ old('package_name', $mhisClaim?->package_name) }}"
                            maxlength="500"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>

                </div>


                <div class="grid gap-4 sm:grid-cols-2">

                    <div>
                        <label for="mhis_claim_amount" class="block text-sm font-semibold text-slate-700">
                            Claim Amount
                        </label>

                        <input
                            id="mhis_claim_amount"
                            type="number"
                            name="claim_amount"
                            value="{{ old('claim_amount', $mhisClaim?->claim_amount ?? 0) }}"
                            min="0"
                            max="99999999.99"
                            step="0.01"
                            required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>


                    <div>
                        <label for="mhis_approved_amount" class="block text-sm font-semibold text-slate-700">
                            Approved Amount
                        </label>

                        <input
                            id="mhis_approved_amount"
                            type="number"
                            name="approved_amount"
                            value="{{ old('approved_amount', $mhisClaim?->approved_amount ?? 0) }}"
                            min="0"
                            max="99999999.99"
                            step="0.01"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>

                </div>


                <div class="grid gap-4 sm:grid-cols-2">

                    <div>
                        <label for="mhis_status" class="block text-sm font-semibold text-slate-700">
                            Status
                        </label>

                        <select
                            id="mhis_status"
                            name="status"
                            required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                            @php
                                $mhisStatus =
                                    old(
                                        'status',
                                        $mhisClaim?->status ?? 'pending'
                                    );
                            @endphp

                            <option value="pending" @selected($mhisStatus === 'pending')>
                                Pending
                            </option>

                            <option value="approved" @selected($mhisStatus === 'approved')>
                                Approved
                            </option>

                            <option value="submitted" @selected($mhisStatus === 'submitted')>
                                Submitted
                            </option>

                            <option value="settled" @selected($mhisStatus === 'settled')>
                                Settled
                            </option>

                            <option value="rejected" @selected($mhisStatus === 'rejected')>
                                Rejected
                            </option>
                        </select>
                    </div>


                    <div>
                        <label for="mhis_approval_date" class="block text-sm font-semibold text-slate-700">
                            Approval Date
                        </label>

                        <input
                            id="mhis_approval_date"
                            type="date"
                            name="approval_date"
                            value="{{ old('approval_date', $mhisClaim?->approval_date?->format('Y-m-d')) }}"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>

                </div>


                <div>
                    <label for="mhis_remarks" class="block text-sm font-semibold text-slate-700">
                        Remarks
                    </label>

                    <textarea
                        id="mhis_remarks"
                        name="remarks"
                        rows="3"
                        maxlength="3000"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                    >{{ old('remarks', $mhisClaim?->remarks) }}</textarea>
                </div>


                <div class="rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-800">
                    Approved MHIS reduces the patient balance. Actual MHIS payments are recorded separately and may be received in multiple partial payments over several months.
                </div>


                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">

                    <button
                        type="button"
                        id="cancel-mhis-modal"
                        class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700"
                    >
                        Save MHIS
                    </button>

                </div>

            </form>

        </div>
    </div>


    {{-- MHIS RECEIPT MODAL --}}
    <div
        id="mhis-receipt-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/40 p-4"
        aria-hidden="true"
    >
        <div class="my-8 w-full max-w-2xl rounded-2xl bg-white shadow-2xl">

            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">

                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        Record MHIS Payment
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Record an actual partial or full payment received from MHIS.
                    </p>
                </div>

                <button
                    type="button"
                    id="close-mhis-receipt-modal"
                    class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    ✕
                </button>

            </div>


            <form
                method="POST"
                action="{{ route('ip-billing.mhis.receipts.store', $admission) }}"
                class="space-y-5 p-6"
            >
                @csrf


                <div class="grid gap-4 sm:grid-cols-3">

                    <div class="rounded-xl border border-violet-200 bg-violet-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-violet-600">
                            Approved
                        </div>

                        <div class="mt-1 text-lg font-bold text-violet-700">
                            ₹{{ number_format((float) $mhisApprovedAmount, 2) }}
                        </div>
                    </div>


                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                            Received
                        </div>

                        <div class="mt-1 text-lg font-bold text-emerald-700">
                            ₹{{ number_format((float) $mhisReceivedAmount, 2) }}
                        </div>
                    </div>


                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-amber-600">
                            Outstanding
                        </div>

                        <div class="mt-1 text-lg font-bold text-amber-700">
                            ₹{{ number_format((float) $mhisOutstandingAmount, 2) }}
                        </div>
                    </div>

                </div>


                <div class="grid gap-4 sm:grid-cols-2">

                    <div>
                        <label for="mhis_receipt_date" class="block text-sm font-semibold text-slate-700">
                            Receipt Date
                        </label>

                        <input
                            id="mhis_receipt_date"
                            type="date"
                            name="receipt_date"
                            value="{{ old('receipt_date', now()->format('Y-m-d')) }}"
                            max="{{ now()->format('Y-m-d') }}"
                            required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </div>


                    <div>
                        <label for="mhis_receipt_amount" class="block text-sm font-semibold text-slate-700">
                            Amount Received
                        </label>

                        <input
                            id="mhis_receipt_amount"
                            type="number"
                            name="amount"
                            value="{{ old('amount') }}"
                            min="0.01"
                            max="{{ number_format((float) $mhisOutstandingAmount, 2, '.', '') }}"
                            step="0.01"
                            required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Cannot exceed the current outstanding MHIS receivable.
                        </p>
                    </div>

                </div>


                <div class="grid gap-4 sm:grid-cols-2">

                    <div>
                        <label for="mhis_payment_reference" class="block text-sm font-semibold text-slate-700">
                            Payment Reference
                        </label>

                        <input
                            id="mhis_payment_reference"
                            type="text"
                            name="payment_reference"
                            value="{{ old('payment_reference') }}"
                            maxlength="255"
                            placeholder="e.g. MHIS payment advice / batch no."
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </div>


                    <div>
                        <label for="mhis_transaction_reference" class="block text-sm font-semibold text-slate-700">
                            Transaction Reference
                        </label>

                        <input
                            id="mhis_transaction_reference"
                            type="text"
                            name="transaction_reference"
                            value="{{ old('transaction_reference') }}"
                            maxlength="255"
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        >
                    </div>

                </div>


                <div>
                    <label for="mhis_bank_reference" class="block text-sm font-semibold text-slate-700">
                        Bank Reference
                    </label>

                    <input
                        id="mhis_bank_reference"
                        type="text"
                        name="bank_reference"
                        value="{{ old('bank_reference') }}"
                        maxlength="255"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>


                <div>
                    <label for="mhis_receipt_remarks" class="block text-sm font-semibold text-slate-700">
                        Remarks
                    </label>

                    <textarea
                        id="mhis_receipt_remarks"
                        name="remarks"
                        rows="3"
                        maxlength="3000"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >{{ old('remarks') }}</textarea>
                </div>


                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    This records money actually received from MHIS. It does not change the original approved claim amount.
                </div>


                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">

                    <button
                        type="button"
                        id="cancel-mhis-receipt-modal"
                        class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                    >
                        Record Payment
                    </button>

                </div>

            </form>

        </div>
    </div>


    @if (! $isFinalized)
    {{-- MANUAL IP CHARGE MODAL --}}
    <div
        id="charge-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
        aria-hidden="true"
    >
        <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">

            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        Add Manual IP Charge
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Add a charge directly to this inpatient running bill.
                    </p>
                </div>

                <button
                    type="button"
                    id="close-charge-modal"
                    class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    ✕
                </button>
            </div>


            <form
                method="POST"
                action="{{ route('ip-billing.charges.store', $admission) }}"
                class="space-y-5 p-6"
            >
                @csrf

                <input type="hidden" name="service_id" value="">


                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                                Patient
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $patient?->full_name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                                IP Billing Account
                            </div>

                            <div class="mt-1 font-mono text-sm font-semibold text-slate-900">
                                {{ $account->account_no }}
                            </div>
                        </div>
                    </div>
                </div>


                <div>
                    <label for="charge_description" class="block text-sm font-semibold text-slate-700">
                        Charge Description
                    </label>

                    <input
                        id="charge_description"
                        type="text"
                        name="description"
                        value="{{ old('description') }}"
                        maxlength="500"
                        required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Example: Oxygen charge, procedure charge, consumable"
                    >
                </div>


                <div class="grid gap-4 sm:grid-cols-3">

                    <div>
                        <label for="charge_quantity" class="block text-sm font-semibold text-slate-700">
                            Quantity
                        </label>

                        <input
                            id="charge_quantity"
                            type="number"
                            name="quantity"
                            value="{{ old('quantity', 1) }}"
                            min="0.01"
                            max="99999.99"
                            step="0.01"
                            required
                            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        >
                    </div>


                    <div>
                        <label for="charge_unit_price" class="block text-sm font-semibold text-slate-700">
                            Rate
                        </label>

                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                ₹
                            </div>

                            <input
                                id="charge_unit_price"
                                type="number"
                                name="unit_price"
                                value="{{ old('unit_price') }}"
                                min="0"
                                max="9999999.99"
                                step="0.01"
                                required
                                class="block w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="0.00"
                            >
                        </div>
                    </div>


                    <div>
                        <label for="charge_discount" class="block text-sm font-semibold text-slate-700">
                            Discount
                        </label>

                        <div class="relative mt-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                ₹
                            </div>

                            <input
                                id="charge_discount"
                                type="number"
                                name="discount"
                                value="{{ old('discount', 0) }}"
                                min="0"
                                max="9999999.99"
                                step="0.01"
                                class="block w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            >
                        </div>
                    </div>

                </div>


                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-center justify-between gap-4">
                        <div class="text-sm font-semibold text-slate-600">
                            Final Charge Amount
                        </div>

                        <div id="charge-calculated-amount" class="text-xl font-bold text-slate-900">
                            ₹0.00
                        </div>
                    </div>
                </div>


                <div>
                    <label for="charge_remarks" class="block text-sm font-semibold text-slate-700">
                        Remarks
                    </label>

                    <textarea
                        id="charge_remarks"
                        name="remarks"
                        rows="3"
                        maxlength="2000"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Optional remarks"
                    >{{ old('remarks') }}</textarea>
                </div>


                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        id="cancel-charge-modal"
                        class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700"
                    >
                        Add Charge
                    </button>
                </div>
            </form>

        </div>
    </div>


    @endif


    @if (! $isFinalized)
    {{-- RECEIVE ADVANCE MODAL --}}
    <div
        id="advance-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
        aria-hidden="true"
    >
        <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl">

            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        Receive Advance / Deposit
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Record an advance payment against this inpatient billing account.
                    </p>
                </div>

                <button
                    type="button"
                    id="close-advance-modal"
                    class="rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close"
                >
                    ✕
                </button>
            </div>


            <form
                method="POST"
                action="{{ route('ip-billing.advance.store', $admission) }}"
                class="space-y-5 p-6"
            >
                @csrf

                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="grid gap-3 sm:grid-cols-2">

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                                Patient
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $patient?->full_name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                                Account
                            </div>

                            <div class="mt-1 font-mono text-sm font-semibold text-slate-900">
                                {{ $account->account_no }}
                            </div>
                        </div>

                    </div>
                </div>


                <div>
                    <label
                        for="advance_amount"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Amount
                    </label>

                    <div class="relative mt-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                            ₹
                        </div>

                        <input
                            id="advance_amount"
                            type="number"
                            name="amount"
                            value="{{ old('amount') }}"
                            min="1"
                            max="9999999.99"
                            step="0.01"
                            required
                            class="block w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="0.00"
                        >
                    </div>
                </div>


                <div>
                    <label
                        for="advance_payment_mode"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Payment Mode
                    </label>

                    <select
                        id="advance_payment_mode"
                        name="payment_mode"
                        required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">
                            Select payment mode
                        </option>

                        <option value="cash" @selected(old('payment_mode') === 'cash')>
                            Cash
                        </option>

                        <option value="upi" @selected(old('payment_mode') === 'upi')>
                            UPI
                        </option>

                        <option value="card" @selected(old('payment_mode') === 'card')>
                            Card
                        </option>
                    </select>
                </div>


                <div>
                    <label
                        for="advance_transaction_reference"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Transaction Reference
                    </label>

                    <input
                        id="advance_transaction_reference"
                        type="text"
                        name="transaction_reference"
                        value="{{ old('transaction_reference') }}"
                        maxlength="255"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Required for UPI / Card"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Optional for cash. Required for UPI or card payments.
                    </p>
                </div>


                <div>
                    <label
                        for="advance_remarks"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Remarks
                    </label>

                    <textarea
                        id="advance_remarks"
                        name="remarks"
                        rows="3"
                        maxlength="2000"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Optional remarks"
                    >{{ old('remarks') }}</textarea>
                </div>


                <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        id="cancel-advance-modal"
                        class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        Receive Advance
                    </button>
                </div>
            </form>

        </div>
    </div>


    @endif


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const mhisModal =
                document.getElementById('mhis-modal');

            const openMhisButton =
                document.getElementById('open-mhis-modal');

            const openMhisButtonSecondary =
                document.getElementById('open-mhis-modal-secondary');

            const closeMhisButton =
                document.getElementById('close-mhis-modal');

            const cancelMhisButton =
                document.getElementById('cancel-mhis-modal');


            const mhisReceiptModal =
                document.getElementById('mhis-receipt-modal');

            const openMhisReceiptButton =
                document.getElementById('open-mhis-receipt-modal');

            const openMhisReceiptButtonSecondary =
                document.getElementById('open-mhis-receipt-modal-secondary');

            const closeMhisReceiptButton =
                document.getElementById('close-mhis-receipt-modal');

            const cancelMhisReceiptButton =
                document.getElementById('cancel-mhis-receipt-modal');


            function openMhisModal() {
                mhisModal.classList.remove('hidden');
                mhisModal.classList.add('flex');
                mhisModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');

                setTimeout(function () {
                    document.getElementById('mhis_claim_no')?.focus();
                }, 50);
            }


            function closeMhisModal() {
                mhisModal.classList.add('hidden');
                mhisModal.classList.remove('flex');
                mhisModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            }


            function openMhisReceiptModal() {
                mhisReceiptModal?.classList.remove('hidden');
                mhisReceiptModal?.classList.add('flex');
                mhisReceiptModal?.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');

                setTimeout(function () {
                    document.getElementById('mhis_receipt_amount')?.focus();
                }, 50);
            }


            function closeMhisReceiptModal() {
                mhisReceiptModal?.classList.add('hidden');
                mhisReceiptModal?.classList.remove('flex');
                mhisReceiptModal?.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            }


            openMhisButton?.addEventListener(
                'click',
                openMhisModal
            );

            openMhisButtonSecondary?.addEventListener(
                'click',
                openMhisModal
            );

            closeMhisButton?.addEventListener(
                'click',
                closeMhisModal
            );

            cancelMhisButton?.addEventListener(
                'click',
                closeMhisModal
            );

            openMhisReceiptButton?.addEventListener(
                'click',
                openMhisReceiptModal
            );

            openMhisReceiptButtonSecondary?.addEventListener(
                'click',
                openMhisReceiptModal
            );

            closeMhisReceiptButton?.addEventListener(
                'click',
                closeMhisReceiptModal
            );

            cancelMhisReceiptButton?.addEventListener(
                'click',
                closeMhisReceiptModal
            );

            mhisReceiptModal?.addEventListener(
                'click',
                function (event) {
                    if (event.target === mhisReceiptModal) {
                        closeMhisReceiptModal();
                    }
                }
            );

            mhisModal?.addEventListener(
                'click',
                function (event) {
                    if (event.target === mhisModal) {
                        closeMhisModal();
                    }
                }
            );


            const chargeModal =
                document.getElementById('charge-modal');

            const openChargeButton =
                document.getElementById('open-charge-modal');

            const closeChargeButton =
                document.getElementById('close-charge-modal');

            const cancelChargeButton =
                document.getElementById('cancel-charge-modal');

            const chargeQuantity =
                document.getElementById('charge_quantity');

            const chargeUnitPrice =
                document.getElementById('charge_unit_price');

            const chargeDiscount =
                document.getElementById('charge_discount');

            const chargeCalculatedAmount =
                document.getElementById('charge-calculated-amount');


            function openChargeModal() {
                chargeModal.classList.remove('hidden');
                chargeModal.classList.add('flex');
                chargeModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');

                setTimeout(function () {
                    document.getElementById('charge_description')?.focus();
                }, 50);
            }


            function closeChargeModal() {
                chargeModal.classList.add('hidden');
                chargeModal.classList.remove('flex');
                chargeModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            }


            function recalculateManualCharge() {

                const quantity =
                    parseFloat(chargeQuantity?.value || '0');

                const unitPrice =
                    parseFloat(chargeUnitPrice?.value || '0');

                const discount =
                    parseFloat(chargeDiscount?.value || '0');

                const finalAmount =
                    Math.max(
                        (quantity * unitPrice) - discount,
                        0
                    );

                if (chargeCalculatedAmount) {
                    chargeCalculatedAmount.textContent =
                        '₹' +
                        finalAmount.toLocaleString(
                            'en-IN',
                            {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        );
                }
            }


            openChargeButton?.addEventListener(
                'click',
                openChargeModal
            );

            closeChargeButton?.addEventListener(
                'click',
                closeChargeModal
            );

            cancelChargeButton?.addEventListener(
                'click',
                closeChargeModal
            );

            chargeQuantity?.addEventListener(
                'input',
                recalculateManualCharge
            );

            chargeUnitPrice?.addEventListener(
                'input',
                recalculateManualCharge
            );

            chargeDiscount?.addEventListener(
                'input',
                recalculateManualCharge
            );

            chargeModal?.addEventListener(
                'click',
                function (event) {
                    if (event.target === chargeModal) {
                        closeChargeModal();
                    }
                }
            );


            const advanceModal =
                document.getElementById('advance-modal');

            const openAdvanceButton =
                document.getElementById('open-advance-modal');

            const closeAdvanceButton =
                document.getElementById('close-advance-modal');

            const cancelAdvanceButton =
                document.getElementById('cancel-advance-modal');

            const paymentMode =
                document.getElementById('advance_payment_mode');

            const transactionReference =
                document.getElementById('advance_transaction_reference');


            function openAdvanceModal() {
                advanceModal.classList.remove('hidden');
                advanceModal.classList.add('flex');
                advanceModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');

                setTimeout(function () {
                    document.getElementById('advance_amount')?.focus();
                }, 50);
            }


            function closeAdvanceModal() {
                advanceModal.classList.add('hidden');
                advanceModal.classList.remove('flex');
                advanceModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            }


            function updateTransactionReferenceRequirement() {

                const requiresReference =
                    paymentMode?.value === 'upi'
                    || paymentMode?.value === 'card';

                if (transactionReference) {
                    transactionReference.required =
                        requiresReference;
                }
            }


            openAdvanceButton?.addEventListener(
                'click',
                openAdvanceModal
            );

            closeAdvanceButton?.addEventListener(
                'click',
                closeAdvanceModal
            );

            cancelAdvanceButton?.addEventListener(
                'click',
                closeAdvanceModal
            );

            paymentMode?.addEventListener(
                'change',
                updateTransactionReferenceRequirement
            );

            advanceModal?.addEventListener(
                'click',
                function (event) {
                    if (event.target === advanceModal) {
                        closeAdvanceModal();
                    }
                }
            );


            document.addEventListener(
                'keydown',
                function (event) {

                    if (event.key !== 'Escape') {
                        return;
                    }

                    if (
                        mhisModal
                        && ! mhisModal.classList.contains('hidden')
                    ) {
                        closeMhisModal();
                    }


                    if (
                        mhisReceiptModal
                        && ! mhisReceiptModal.classList.contains('hidden')
                    ) {
                        closeMhisReceiptModal();
                    }

                    if (
                        chargeModal
                        && ! chargeModal.classList.contains('hidden')
                    ) {
                        closeChargeModal();
                    }

                    if (
                        advanceModal
                        && ! advanceModal.classList.contains('hidden')
                    ) {
                        closeAdvanceModal();
                    }
                }
            );


            recalculateManualCharge();
            updateTransactionReferenceRequirement();


            @if ($errors->any())

                @if (old('payment_mode') !== null && ! $isFinalized)
                    openAdvanceModal();
                @elseif (
                    old('receipt_date') !== null
                    || old('payment_reference') !== null
                    || old('bank_reference') !== null
                )
                    openMhisReceiptModal();
                @elseif (
                    old('claim_no') !== null
                    || old('authorization_no') !== null
                    || old('package_name') !== null
                    || old('claim_amount') !== null
                    || old('status') !== null
                )
                    openMhisModal();
                @elseif (
                    ! $isFinalized
                    && (
                        old('description') !== null
                        || old('unit_price') !== null
                        || old('quantity') !== null
                    )
                )
                    openChargeModal();
                @endif

            @endif

        });
    </script>

</x-app-layout>