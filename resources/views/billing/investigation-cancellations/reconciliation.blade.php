<x-app-layout>
    @php
        /*
        |--------------------------------------------------------------------------
        | Financial display helpers
        |--------------------------------------------------------------------------
        | Reconciliation summary amounts are stored in paise.
        */
        $money = fn ($value) => '₹' . number_format(
            ((int) $value) / 100,
            2
        );

        $refundEnabled = config(
            'investigation_cancellation.enable_verified_refunds',
            false
        ) === true;

        $user = auth()->user();

        $canSubmitRefund = $user
            && $user->permissions()
                ->where(
                    'permissions.name',
                    'billing.investigation-refund.process'
                )
                ->exists();

        $canVerifyRefund = $user
            && $user->permissions()
                ->where(
                    'permissions.name',
                    'billing.investigation-refund.verify'
                )
                ->exists();

        $canAccessWorkflow = $canSubmitRefund || $canVerifyRefund;

        $paymentsForRefund = $refundEnabled && $canSubmitRefund
            ? \App\Models\Payment::query()
                ->where('invoice_id', $invoice->id)
                ->orderBy('id')
                ->get()
            : collect();

        $availableRefund = max(
            0,
            (int) $summary['potential_refund']
        );

        /*
         | An individual cancellation cannot initiate a refund
         | exceeding its approved amount.
         */
        $approvedRefund = $eligible
            ? (int) round(
                ((float) $eligible->approved_amount) * 100
            )
            : 0;

        $suggestedRefund = min(
            $availableRefund,
            $approvedRefund
        );

        $canInitiate = $refundEnabled
            && $canSubmitRefund
            && $eligible
            && $availableRefund > 0
            && $approvedRefund > 0
            && !$summary['refund_hold']
            && $paymentsForRefund->isNotEmpty();

        $metrics = [
            [
                'label' => 'Original Invoice',
                'value' => $summary['invoice_total'],
                'description' => 'Original billed value',
                'color' => 'slate',
            ],
            [
                'label' => 'Payments Received',
                'value' => $summary['paid'],
                'description' => 'Original receipts collected',
                'color' => 'blue',
            ],
            [
                'label' => 'Executed Cancellations',
                'value' => $summary['executed_cancellations'],
                'description' => 'Approved and executed reversals',
                'color' => 'amber',
            ],
            [
                'label' => 'Revised Net Bill',
                'value' => $summary['revised_net_bill'],
                'description' => 'Invoice after cancellations',
                'color' => 'slate',
            ],
            [
                'label' => 'Completed Refunds',
                'value' => $summary['completed_refunds'],
                'description' => 'Refunds recorded in accounting',
                'color' => 'green',
            ],
            [
                'label' => 'Net Cash Retained',
                'value' => $summary['net_paid'],
                'description' => 'Payments less completed refunds',
                'color' => 'slate',
            ],
            [
                'label' => 'Balance Due',
                'value' => $summary['balance_due'],
                'description' => 'Outstanding invoice balance',
                'color' => 'slate',
            ],
            [
                'label' => 'Potential Refund',
                'value' => $summary['potential_refund'],
                'description' => 'Subject to eligibility and review',
                'color' => 'blue',
            ],
        ];
    @endphp

    <div class="min-h-screen bg-slate-50">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            {{-- =====================================================
                 PAGE HEADER
            ====================================================== --}}

            <div class="flex flex-col lg:flex-row lg:items-start
                        lg:justify-between gap-4">

                <div class="space-y-2">

                    <div class="flex items-center flex-wrap gap-2">

                        <span class="inline-flex items-center rounded-full
                                     bg-blue-100 px-3 py-1 text-xs
                                     font-semibold text-blue-800">
                            BILLING & RECONCILIATION
                        </span>

                        <span class="inline-flex items-center rounded-full
                                     bg-slate-200 px-3 py-1 text-xs
                                     font-medium text-slate-700">
                            Invoice #{{ $invoice->id }}
                        </span>

                    </div>

                    <h1 class="text-2xl lg:text-3xl font-bold
                               tracking-tight text-slate-900">
                        Investigation Cancellation Reconciliation
                    </h1>

                    <p class="text-sm text-slate-600">
                        Invoice:
                        <span class="font-semibold text-slate-900">
                            {{ $invoice->invoice_no }}
                        </span>
                    </p>

                    <p class="text-sm text-slate-500 max-w-3xl">
                        Review cancellation adjustments, revised billing,
                        receipts, completed refunds and outstanding
                        refundable amounts.
                    </p>

                </div>

                <div class="flex flex-wrap items-center gap-2">

                    @if($canAccessWorkflow && $refundEnabled)

                        <a
                            href="{{ route(
                                'billing.investigation-refunds.verification'
                            ) }}"
                            class="inline-flex items-center justify-center
                                   rounded-lg border border-blue-200
                                   bg-white px-4 py-2.5 text-sm
                                   font-semibold text-blue-700
                                   hover:bg-blue-50"
                        >
                            Refund Workflow Queue
                        </a>

                    @endif

                    <a
                        href="{{ route(
                            'billing.investigation-cancellations.queue',
                            ['status' => 'executed']
                        ) }}"
                        class="inline-flex items-center justify-center
                               rounded-lg bg-slate-800 px-4 py-2.5
                               text-sm font-semibold text-white
                               hover:bg-slate-900"
                    >
                        Back to Cancellations
                    </a>

                </div>

            </div>

            {{-- =====================================================
                 SYSTEM MESSAGES
            ====================================================== --}}

            @if(session('success'))

                <div role="status"
                     class="rounded-xl border border-green-200
                            bg-green-50 px-5 py-4 text-sm text-green-800">

                    <div class="font-semibold mb-1">
                        Operation Successful
                    </div>

                    {{ session('success') }}

                </div>

            @endif

            @if($errors->any())

                <div role="alert"
                     class="rounded-xl border border-red-200
                            bg-red-50 px-5 py-4 text-sm text-red-800">

                    <div class="font-semibold mb-2">
                        Please Review
                    </div>

                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>

            @endif

            {{-- Accounting information notice --}}

            <div class="rounded-xl border border-blue-200
                        bg-blue-50 px-5 py-4">

                <div class="flex items-start gap-3">

                    <div class="flex-shrink-0 rounded-lg bg-blue-100
                                px-2.5 py-1.5 text-blue-700 font-bold">
                        i
                    </div>

                    <div>

                        <h2 class="text-sm font-semibold text-blue-900">
                            Accounting Reconciliation Information
                        </h2>

                        <p class="mt-1 text-sm leading-relaxed text-blue-800">
                            Original invoice and receipt records remain
                            unchanged. Revised financial figures reflect
                            executed cancellation adjustments and completed
                            refund accounting entries. A pending refund
                            request is not treated as a completed payment.
                        </p>

                    </div>

                </div>

            </div>

            {{-- =====================================================
                 FINANCIAL SUMMARY
            ====================================================== --}}

            <section class="space-y-4">

                <div class="flex items-center justify-between gap-3">

                    <h2 class="text-lg font-bold text-slate-900">
                        Financial Overview
                    </h2>

                    <span class="text-xs text-slate-500">
                        Amounts in INR
                    </span>

                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2
                            xl:grid-cols-4 gap-4">

                    @foreach($metrics as $metric)

                        @php
                            $accent = match ($metric['color']) {
                                'blue' => 'border-t-blue-500',
                                'amber' => 'border-t-amber-500',
                                'green' => 'border-t-green-500',
                                default => 'border-t-slate-400',
                            };

                            $amountColor = match ($metric['color']) {
                                'blue' => 'text-blue-700',
                                'amber' => 'text-amber-700',
                                'green' => 'text-green-700',
                                default => 'text-slate-900',
                            };
                        @endphp

                        <div class="rounded-xl bg-white border
                                    border-slate-200 border-t-4
                                    {{ $accent }}
                                    shadow-sm p-5">

                            <p class="text-sm font-medium text-slate-600">
                                {{ $metric['label'] }}
                            </p>

                            <p class="mt-3 text-2xl font-bold
                                      tracking-tight {{ $amountColor }}">
                                {{ $money($metric['value']) }}
                            </p>

                            <p class="mt-2 text-xs text-slate-500">
                                {{ $metric['description'] }}
                            </p>

                        </div>

                    @endforeach

                </div>

            </section>

            {{-- =====================================================
                 REFUND HOLD WARNING
            ====================================================== --}}

            @if($summary['refund_hold'])

                <div class="rounded-xl border border-red-300
                            bg-red-50 px-5 py-4">

                    <h3 class="font-bold text-red-900">
                        Refund Reconciliation Hold
                    </h3>

                    <p class="mt-1 text-sm text-red-800">
                        An unresolved refund record has been detected.
                        Further refund initiation or processing must
                        remain blocked until the existing record has
                        been reconciled.
                    </p>

                </div>

            @endif

            {{-- =====================================================
                 CANCELLATION HISTORY
            ====================================================== --}}

            <section class="rounded-xl bg-white border
                            border-slate-200 shadow-sm overflow-hidden">

                <div class="px-5 py-4 border-b border-slate-200
                            bg-slate-50">

                    <h2 class="text-lg font-bold text-slate-900">
                        Cancellation History
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Investigation cancellation requests associated
                        with this invoice.
                    </p>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm text-left">

                        <thead class="bg-white text-xs uppercase
                                      tracking-wide text-slate-500">

                            <tr class="border-b border-slate-200">
                                <th scope="col" class="px-5 py-3">
                                    Request
                                </th>

                                <th scope="col" class="px-5 py-3">
                                    Status
                                </th>

                                <th scope="col" class="px-5 py-3 text-right">
                                    Amount
                                </th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($summary['requests'] as $c)

                                @php
                                    $status = strtolower(
                                        (string) $c->status
                                    );

                                    $statusColor = match($status) {
                                        'executed' =>
                                            'bg-green-100 text-green-800',
                                        'approved' =>
                                            'bg-blue-100 text-blue-800',
                                        'rejected', 'cancelled' =>
                                            'bg-red-100 text-red-800',
                                        default =>
                                            'bg-amber-100 text-amber-800',
                                    };

                                    $cancellationAmount = (int) round(
                                        ((float) (
                                            $c->approved_amount
                                            ?? $c->requested_amount
                                        )) * 100
                                    );
                                @endphp

                                <tr class="hover:bg-slate-50">

                                    <td class="px-5 py-4 font-semibold
                                               text-slate-800">
                                        #{{ $c->id }}
                                    </td>

                                    <td class="px-5 py-4">

                                        <span class="inline-flex rounded-full
                                                     px-3 py-1 text-xs
                                                     font-semibold
                                                     {{ $statusColor }}">
                                            {{ ucfirst($status) }}
                                        </span>

                                    </td>

                                    <td class="px-5 py-4 text-right
                                               font-semibold text-slate-900">
                                        {{ $money($cancellationAmount) }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="3"
                                        class="px-5 py-8 text-center
                                               text-slate-500">
                                        No cancellation requests found.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </section>

            {{-- =====================================================
                 RECORDED REFUNDS
            ====================================================== --}}

            <section class="rounded-xl bg-white border
                            border-slate-200 shadow-sm overflow-hidden">

                <div class="px-5 py-4 border-b border-slate-200
                            bg-slate-50">

                    <h2 class="text-lg font-bold text-slate-900">
                        Recorded Refunds
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Refund entries recorded in the accounting system.
                        Initiated requests are not shown as completed refunds.
                    </p>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm text-left">

                        <thead class="text-xs uppercase tracking-wide
                                      text-slate-500 bg-white">

                            <tr class="border-b border-slate-200">

                                <th scope="col" class="px-5 py-3">
                                    Refund ID
                                </th>

                                <th scope="col" class="px-5 py-3">
                                    Reference
                                </th>

                                <th scope="col" class="px-5 py-3">
                                    Status
                                </th>

                                <th scope="col" class="px-5 py-3 text-right">
                                    Amount
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($summary['refunds'] as $r)

                                <tr class="hover:bg-slate-50">

                                    <td class="px-5 py-4 font-semibold
                                               text-slate-800">
                                        #{{ $r->id }}
                                    </td>

                                    <td class="px-5 py-4 text-slate-700
                                               break-all">
                                        {{ $r->reference_no }}
                                    </td>

                                    <td class="px-5 py-4">

                                        <span class="inline-flex rounded-full
                                                     bg-green-100
                                                     px-3 py-1 text-xs
                                                     font-semibold
                                                     text-green-800">
                                            {{ ucfirst($r->status) }}
                                        </span>

                                    </td>

                                    <td class="px-5 py-4 text-right
                                               font-semibold text-slate-900">

                                        {{ $money(
                                            (int) round(
                                                ((float) $r->amount) * 100
                                            )
                                        ) }}

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="4"
                                        class="px-5 py-8 text-center
                                               text-slate-500">

                                        No completed refund records
                                        are available for this invoice.

                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </section>

            {{-- =====================================================
                 REFUND WORKFLOW
            ====================================================== --}}

            <section class="rounded-xl bg-white border
                            border-slate-200 shadow-sm overflow-hidden">

                <div class="px-5 py-5 bg-slate-50
                            border-b border-slate-200">

                    <div class="flex flex-wrap items-center
                                justify-between gap-3">

                        <div>

                            <h2 class="text-lg font-bold text-slate-900">
                                Investigation Refund Workflow
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Controlled refund processing with
                                segregation of duties.
                            </p>

                        </div>

                        <span class="inline-flex rounded-full
                                     bg-blue-100 px-3 py-1 text-xs
                                     font-semibold text-blue-800">
                            Three-Stage Workflow
                        </span>

                    </div>

                </div>

                {{-- Workflow stages --}}

                <div class="p-5 border-b border-slate-200">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                        <div class="rounded-lg border border-blue-200
                                    bg-blue-50 p-4">

                            <div class="flex items-center gap-2 mb-2">

                                <span class="flex h-7 w-7 items-center
                                             justify-center rounded-full
                                             bg-blue-700 text-white
                                             text-xs font-bold">
                                    1
                                </span>

                                <h3 class="font-semibold text-blue-900">
                                    Initiate
                                </h3>

                            </div>

                            <p class="text-xs leading-relaxed
                                      text-blue-800">
                                Billing staff initiates the eligible
                                refund. No disbursement occurs.
                            </p>

                        </div>

                        <div class="rounded-lg border border-amber-200
                                    bg-amber-50 p-4">

                            <div class="flex items-center gap-2 mb-2">

                                <span class="flex h-7 w-7 items-center
                                             justify-center rounded-full
                                             bg-amber-600 text-white
                                             text-xs font-bold">
                                    2
                                </span>

                                <h3 class="font-semibold text-amber-900">
                                    Disburse
                                </h3>

                            </div>

                            <p class="text-xs leading-relaxed
                                      text-amber-800">
                                Cashier records actual payment and
                                the supporting settlement evidence.
                            </p>

                        </div>

                        <div class="rounded-lg border border-green-200
                                    bg-green-50 p-4">

                            <div class="flex items-center gap-2 mb-2">

                                <span class="flex h-7 w-7 items-center
                                             justify-center rounded-full
                                             bg-green-700 text-white
                                             text-xs font-bold">
                                    3
                                </span>

                                <h3 class="font-semibold text-green-900">
                                    Verify
                                </h3>

                            </div>

                            <p class="text-xs leading-relaxed
                                      text-green-800">
                                Independent verifier confirms the
                                evidence and posts the accounting journal.
                            </p>

                        </div>

                    </div>

                </div>

                {{-- Refund initiation section --}}

                <div class="p-5 space-y-5">

                    <div class="flex flex-col sm:flex-row
                                sm:justify-between gap-3">

                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                Stage 1 — Initiate Refund
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Register a request for an eligible
                                investigation cancellation.
                            </p>
                        </div>

                        <div class="sm:text-right">

                            <p class="text-xs font-medium
                                      uppercase text-slate-500">
                                Potential Refund
                            </p>

                            <p class="text-2xl font-bold text-blue-700">
                                {{ $money($availableRefund) }}
                            </p>

                        </div>

                    </div>

                    @if($canInitiate)

                        <form
                            method="POST"
                            action="{{ route(
                                'billing.investigation-refunds.submit',
                                $eligible
                            ) }}"
                            class="space-y-5"
                        >
                            @csrf

                            <div class="grid grid-cols-1 lg:grid-cols-2
                                        gap-5">

                                {{-- Original receipt --}}

                                <div class="lg:col-span-2">

                                    <label for="refund_payment_id"
                                           class="block text-sm font-semibold
                                                  text-slate-700 mb-2">
                                        Original Payment Receipt
                                    </label>

                                    <select
                                        id="refund_payment_id"
                                        name="payment_id"
                                        required
                                        class="w-full rounded-lg
                                               border-slate-300
                                               shadow-sm focus:border-blue-500
                                               focus:ring-blue-500"
                                    >

                                        <option value="">
                                            Select original receipt
                                        </option>

                                        @foreach($paymentsForRefund as $payment)

                                            <option
                                                value="{{ $payment->id }}"
                                                @selected(
                                                    old('payment_id')
                                                    == $payment->id
                                                )
                                            >
                                                {{ $payment->receipt_no }}
                                                —
                                                ₹{{ number_format(
                                                    (float) $payment->amount,
                                                    2
                                                ) }}
                                                —
                                                {{ strtoupper(
                                                    $payment->payment_mode
                                                ) }}
                                            </option>

                                        @endforeach

                                    </select>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Select the receipt against which
                                        the refund will be allocated.
                                    </p>

                                </div>

                                {{-- Refund amount --}}

                                <div>

                                    <label for="refund_amount"
                                           class="block text-sm font-semibold
                                                  text-slate-700 mb-2">
                                        Refund Amount (₹)
                                    </label>

                                    <input
                                        type="text"
                                        id="refund_amount"
                                        name="amount"
                                        required
                                        inputmode="decimal"
                                        pattern="[0-9]{1,10}\.[0-9]{2}"
                                        value="{{ old(
                                            'amount',
                                            number_format(
                                                $suggestedRefund / 100,
                                                2,
                                                '.',
                                                ''
                                            )
                                        ) }}"
                                        class="w-full rounded-lg
                                               border-slate-300 shadow-sm
                                               focus:border-blue-500
                                               focus:ring-blue-500"
                                    >

                                    <p class="mt-1 text-xs text-slate-500">
                                        Suggested amount is capped by
                                        this cancellation's approved value.
                                        Final eligibility is checked by
                                        the server.
                                    </p>

                                </div>

                                {{-- Refund method --}}

                                <div>

                                    <label for="refund_method"
                                           class="block text-sm font-semibold
                                                  text-slate-700 mb-2">
                                        Proposed Refund Method
                                    </label>

                                    <select
                                        id="refund_method"
                                        name="settlement_method"
                                        required
                                        class="w-full rounded-lg
                                               border-slate-300 shadow-sm
                                               focus:border-blue-500
                                               focus:ring-blue-500"
                                    >

                                        <option value="">
                                            Select refund method
                                        </option>

                                        <option value="cash"
                                            @selected(
                                                old('settlement_method')
                                                === 'cash'
                                            )>
                                            Cash
                                        </option>

                                        <option value="upi"
                                            @selected(
                                                old('settlement_method')
                                                === 'upi'
                                            )>
                                            UPI
                                        </option>

                                        <option value="card"
                                            @selected(
                                                old('settlement_method')
                                                === 'card'
                                            )>
                                            Card
                                        </option>

                                    </select>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Must match the original receipt's
                                        payment mode.
                                    </p>

                                </div>

                            </div>

                            {{-- Important initiation notice --}}

                            <div class="rounded-lg border border-amber-200
                                        bg-amber-50 p-4">

                                <h4 class="text-sm font-semibold
                                           text-amber-900">
                                    Refund Initiation Only
                                </h4>

                                <p class="mt-1 text-sm leading-relaxed
                                          text-amber-800">
                                    Submitting this form creates a refund
                                    request only. It does not confirm
                                    cash handover or electronic transfer.
                                    The cashier must separately complete
                                    disbursement and provide documentary
                                    evidence. No accounting journal is
                                    posted at initiation.
                                </p>

                            </div>

                            <div class="flex flex-wrap items-center
                                        justify-between gap-3 pt-2">

                                <p class="text-xs text-slate-500
                                          max-w-xl">
                                    All requests are subject to duplicate
                                    checks, receipt matching and
                                    financial eligibility controls.
                                </p>

                                <button
                                    type="submit"
                                    class="inline-flex items-center
                                           justify-center rounded-lg
                                           bg-blue-700 px-6 py-3
                                           text-sm font-semibold text-white
                                           shadow-sm hover:bg-blue-800
                                           focus:outline-none
                                           focus:ring-2
                                           focus:ring-blue-500
                                           focus:ring-offset-2"
                                >
                                    Initiate Refund Request
                                </button>

                            </div>

                        </form>

                    @else

                        <div class="rounded-lg border border-amber-200
                                    bg-amber-50 px-4 py-4">

                            <h4 class="text-sm font-semibold text-amber-900">
                                Refund Initiation Unavailable
                            </h4>

                            <p class="mt-1 text-sm text-amber-800">
                                A new refund cannot currently be initiated.
                                Possible reasons include insufficient
                                permissions, an ineligible cancellation,
                                an existing settlement, a reconciliation
                                hold, or no eligible receipt.
                            </p>

                        </div>

                    @endif

                    {{-- Workflow navigation --}}

                    @if($canAccessWorkflow && $refundEnabled)

                        <div class="border-t border-slate-200 pt-4">

                            <div class="flex flex-col sm:flex-row
                                        sm:items-center sm:justify-between
                                        gap-3">

                                <div>

                                    <p class="text-sm font-semibold
                                              text-slate-800">
                                        Refund Workflow Queue
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Review initiated requests,
                                        cashier disbursements and refunds
                                        awaiting independent verification.
                                    </p>

                                </div>

                                <a
                                    href="{{ route(
                                        'billing.investigation-refunds.verification'
                                    ) }}"
                                    class="inline-flex items-center
                                           justify-center rounded-lg
                                           border border-slate-300
                                           bg-white px-4 py-2.5
                                           text-sm font-semibold
                                           text-slate-700 hover:bg-slate-50"
                                >
                                    Open Refund Queue
                                </a>

                            </div>

                        </div>

                    @endif

                </div>

            </section>

            {{-- =====================================================
                 FOOTER
            ====================================================== --}}

            <div class="flex flex-wrap items-center
                        justify-between gap-3 pb-6">

                <p class="text-xs text-slate-500">
                    Tura Christian Hospital
                    — Investigation Billing Reconciliation
                </p>

                <a
                    href="{{ route(
                        'billing.investigation-cancellations.queue',
                        ['status' => 'executed']
                    ) }}"
                    class="text-sm font-semibold text-blue-700
                           hover:underline"
                >
                    Back to Executed Cancellations
                </a>

            </div>

        </div>

    </div>
</x-app-layout>
