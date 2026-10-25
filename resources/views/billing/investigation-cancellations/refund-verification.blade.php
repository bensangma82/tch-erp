<x-app-layout>
    @php
        $currentUser = auth()->user();

        $canProcess = $currentUser
            && $currentUser->permissions()
                ->where('permissions.name', 'billing.investigation-refund.process')
                ->exists();

        $hasVerifyPermission = $currentUser
            && $currentUser->permissions()
                ->where('permissions.name', 'billing.investigation-refund.verify')
                ->exists();

        $records = $pending->getCollection();

        // Load related records in batches rather than querying per card.
        $cancellations = \App\Models\InvestigationCancellationRequest::query()
            ->whereIn('id', $records->pluck('cancellation_id'))
            ->get()
            ->keyBy('id');

        $payments = \App\Models\Payment::query()
            ->whereIn('id', $records->pluck('payment_id'))
            ->get()
            ->keyBy('id');

        $invoices = \App\Models\Invoice::query()
            ->whereIn('id', $cancellations->pluck('invoice_id')->filter())
            ->get()
            ->keyBy('id');

        $userIds = $records->pluck('submitted_by')
            ->merge($records->pluck('initiated_by'))
            ->merge($records->pluck('disbursed_by'))
            ->filter()
            ->unique();

        $users = \App\Models\User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $money = fn ($amount) => '₹' . number_format(
            (float) $amount,
            2
        );

        $counts = $records->countBy('status');

        $dateTime = fn ($value) => $value
            ? \Illuminate\Support\Carbon::parse($value)
                ->format('d M Y, h:i A')
            : 'Not recorded';
    @endphp

    <div class="min-h-screen bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            {{-- HEADER --}}

            <header class="flex flex-col md:flex-row md:items-start
                           md:justify-between gap-4">

                <div class="space-y-2">

                    <span class="inline-flex rounded-full
                                 bg-blue-100 px-3 py-1 text-xs
                                 font-semibold text-blue-800">
                        BILLING / REFUND CONTROL
                    </span>

                    <h1 class="text-2xl lg:text-3xl font-bold text-slate-900">
                        Investigation Refund Workflow
                    </h1>

                    <p class="text-sm text-slate-600 max-w-3xl">
                        Manage refund initiation, record actual disbursement,
                        and independently verify supporting evidence
                        before posting accounting journals.
                    </p>

                </div>

                <span class="self-start inline-flex rounded-lg
                             bg-white border border-slate-200
                             px-4 py-2 text-sm font-medium text-slate-700">
                    {{ $pending->total() }} Open Records
                </span>

            </header>

            {{-- FEEDBACK --}}

            @if(session('success'))
                <div role="status"
                     class="rounded-xl border border-green-200
                            bg-green-50 p-4 text-sm text-green-800">
                    <strong>Success:</strong> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div role="alert"
                     class="rounded-xl border border-red-200
                            bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold mb-2">
                        Please review the following:
                    </p>

                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- SAFETY NOTICE --}}

            <div class="rounded-xl border border-amber-200
                        bg-amber-50 p-5">

                <h2 class="font-semibold text-amber-900">
                    Financial Control Notice
                </h2>

                <p class="mt-2 text-sm leading-relaxed text-amber-800">
                    This ERP records refund requests and external
                    disbursement evidence. It does not physically hand
                    over cash or initiate electronic transfers.
                    Only genuinely completed disbursements should be
                    submitted for independent verification.
                    Verification creates the refund accounting journal.
                </p>

            </div>

            {{-- WORKFLOW OVERVIEW --}}

            <section class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div class="rounded-xl border border-blue-200
                            bg-white p-5 shadow-sm">

                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center
                                     justify-center rounded-full
                                     bg-blue-100 text-blue-800 font-bold">
                            1
                        </span>

                        <h2 class="font-semibold text-slate-900">
                            Refund Initiated
                        </h2>
                    </div>

                    <p class="mt-3 text-sm text-slate-600">
                        Awaiting cashier payment
                    </p>

                    <p class="mt-2 text-2xl font-bold text-blue-700">
                        {{ $counts->get('initiated', 0) }}
                    </p>

                </div>

                <div class="rounded-xl border border-amber-200
                            bg-white p-5 shadow-sm">

                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center
                                     justify-center rounded-full
                                     bg-amber-100 text-amber-800 font-bold">
                            2
                        </span>

                        <h2 class="font-semibold text-slate-900">
                            Disbursement Recorded
                        </h2>
                    </div>

                    <p class="mt-3 text-sm text-slate-600">
                        Awaiting independent verification
                    </p>

                    <p class="mt-2 text-2xl font-bold text-amber-700">
                        {{ $counts->get('disbursed', 0)
                            + $counts->get('submitted', 0) }}
                    </p>

                </div>

                <div class="rounded-xl border border-green-200
                            bg-white p-5 shadow-sm">

                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center
                                     justify-center rounded-full
                                     bg-green-100 text-green-800 font-bold">
                            3
                        </span>

                        <h2 class="font-semibold text-slate-900">
                            Independent Verification
                        </h2>
                    </div>

                    <p class="mt-3 text-sm text-slate-600">
                        Journal posted only after approval
                    </p>

                    <p class="mt-2 text-sm font-semibold text-green-700">
                        Final accounting stage
                    </p>

                </div>

            </section>

            <p class="text-xs text-slate-500">
                Stage counts above reflect only the current page of results.
            </p>

            {{-- OPEN REFUNDS --}}

            <section class="space-y-4">

                <h2 class="text-lg font-bold text-slate-900">
                    Open Refund Settlements
                </h2>

                @forelse($pending as $check)

                    @php
                        $cancellation = $cancellations->get(
                            $check->cancellation_id
                        );

                        $payment = $payments->get($check->payment_id);

                        $invoice = $cancellation
                            ? $invoices->get($cancellation->invoice_id)
                            : null;

                        $initiatorId = $check->initiated_by
                            ?? $check->submitted_by;

                        $cashierId = $check->disbursed_by
                            ?? (
                                $check->status === 'submitted'
                                    ? $check->submitted_by
                                    : null
                            );

                        $initiator = $users->get($initiatorId);
                        $cashier = $cashierId
                            ? $users->get($cashierId)
                            : null;

                        $status = strtolower((string) $check->status);

                        $statusLabel = match($status) {
                            'initiated' => 'Awaiting Disbursement',
                            'disbursed' => 'Awaiting Verification',
                            'submitted' => 'Legacy Verification',
                            default => ucfirst($status),
                        };

                        $statusClass = match($status) {
                            'initiated' => 'bg-blue-100 text-blue-800',
                            'disbursed' => 'bg-amber-100 text-amber-800',
                            'submitted' => 'bg-purple-100 text-purple-800',
                            default => 'bg-slate-100 text-slate-700',
                        };

                        $independentVerifier = $currentUser
                            && (int) $currentUser->id !== (int) $check->submitted_by
                            && (
                                !$check->initiated_by
                                || (int) $currentUser->id !== (int) $check->initiated_by
                            )
                            && (
                                !$check->disbursed_by
                                || (int) $currentUser->id !== (int) $check->disbursed_by
                            )
                            && $cancellation
                            && (int) $currentUser->id !== (int) $cancellation->requested_by;

                        $canDisburse = $canProcess
                            && $status === 'initiated';

                        $canVerify = $hasVerifyPermission
                            && $independentVerifier
                            && in_array(
                                $status,
                                ['disbursed', 'submitted'],
                                true
                            );
                    @endphp

                    <article class="rounded-xl border border-slate-200
                                    bg-white shadow-sm overflow-hidden">

                        {{-- RECORD HEADER --}}

                        <div class="flex flex-wrap items-center
                                    justify-between gap-3
                                    bg-slate-50 border-b border-slate-200
                                    px-5 py-4">

                            <div>
                                <h3 class="font-bold text-slate-900">
                                    Refund #{{ $check->id }}
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    Cancellation #{{ $check->cancellation_id }}
                                    ·
                                    {{ $invoice?->invoice_no ?? 'Invoice unavailable' }}
                                </p>
                            </div>

                            <span class="inline-flex rounded-full
                                         px-3 py-1 text-xs font-semibold
                                         {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>

                        </div>

                        <div class="p-5 space-y-5">

                            {{-- PRIMARY AMOUNT --}}

                            <div class="flex flex-wrap items-center
                                        justify-between gap-3">

                                <div>
                                    <p class="text-xs uppercase
                                              font-semibold text-slate-500">
                                        Refund Amount
                                    </p>

                                    <p class="mt-1 text-3xl font-bold
                                              text-slate-900">
                                        {{ $money($check->amount) }}
                                    </p>
                                </div>

                                <div class="text-sm sm:text-right">
                                    <p class="text-slate-500">
                                        Refund Method
                                    </p>

                                    <p class="font-semibold uppercase
                                              text-slate-800">
                                        {{ $check->settlement_method }}
                                    </p>
                                </div>

                            </div>

                            {{-- AUDIT SUMMARY --}}

                            <div class="grid grid-cols-1 md:grid-cols-2
                                        lg:grid-cols-3 gap-4
                                        rounded-lg bg-slate-50 p-4 text-sm">

                                <div>
                                    <p class="text-slate-500">
                                        Original Receipt
                                    </p>

                                    <p class="font-semibold text-slate-800">
                                        {{ $payment?->receipt_no ?? 'Unavailable' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-slate-500">
                                        Original Receipt Amount
                                    </p>

                                    <p class="font-semibold text-slate-800">
                                        {{ $payment
                                            ? $money($payment->amount)
                                            : 'Unavailable' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-slate-500">
                                        Request Created
                                    </p>

                                    <p class="font-medium text-slate-800">
                                        {{ $dateTime($check->created_at) }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-slate-500">
                                        Initiated By
                                    </p>

                                    <p class="font-medium text-slate-800">
                                        {{ $initiator?->name ?? 'Unknown user' }}
                                        (ID {{ $initiatorId }})
                                    </p>
                                </div>

                                <div>
                                    <p class="text-slate-500">
                                        Cashier / Disbursement By
                                    </p>

                                    <p class="font-medium text-slate-800">
                                        @if($cashier)
                                            {{ $cashier->name }}
                                            (ID {{ $cashierId }})
                                        @else
                                            Not yet disbursed
                                        @endif
                                    </p>
                                </div>

                                <div>
                                    <p class="text-slate-500">
                                        Disbursement Date
                                    </p>

                                    <p class="font-medium text-slate-800">
                                        {{ $dateTime($check->disbursed_at) }}
                                    </p>
                                </div>

                            </div>

                            {{-- SETTLEMENT REFERENCE & EVIDENCE --}}

                            @if(in_array($status, ['disbursed', 'submitted'], true))

                                <div class="rounded-lg border
                                            border-slate-200 p-4 space-y-3">

                                    <div>
                                        <p class="text-xs font-semibold
                                                  uppercase text-slate-500">
                                            External Settlement Reference
                                        </p>

                                        <p class="mt-1 font-medium text-sm
                                                  text-slate-900 break-all">
                                            {{ $check->settlement_reference }}
                                        </p>
                                    </div>

                                    <div class="border-t border-slate-100 pt-3">
                                        <p class="text-xs font-semibold
                                                  uppercase text-slate-500">
                                            Documentary Evidence Description
                                        </p>

                                        <p class="mt-2 text-sm text-slate-700
                                                  whitespace-pre-wrap break-words">{{ $check->evidence_description }}</p>
                                    </div>

                                </div>

                            @else

                                <div class="rounded-lg border border-blue-200
                                            bg-blue-50 p-4 text-sm text-blue-800">

                                    <strong>Payment not recorded.</strong>
                                    This is an initiated refund request.
                                    The internal tracking reference is
                                    {{ $check->settlement_reference }}.
                                    It is not proof of disbursement.

                                </div>

                            @endif

                            {{-- RECONCILIATION LINK --}}

                            @if($invoice)
                                <a
                                    href="{{ route(
                                        'billing.investigation-cancellations.reconciliation',
                                        $invoice
                                    ) }}"
                                    class="inline-flex items-center
                                           text-sm font-semibold
                                           text-blue-700 hover:underline"
                                >
                                    View Invoice Reconciliation
                                </a>
                            @endif

                            {{-- STAGE 2: CASHIER DISBURSEMENT --}}

                            @if($canDisburse)

                                <div class="border-t border-slate-200 pt-5">

                                    <h4 class="text-base font-bold
                                               text-slate-900">
                                        Stage 2 — Record Actual Disbursement
                                    </h4>

                                    <p class="mt-2 text-sm text-slate-600">
                                        Complete this form only after
                                        the patient has actually received
                                        the refund. Recording this form
                                        does not transfer funds or
                                        post a journal.
                                    </p>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'billing.investigation-refunds.disburse',
                                            $check
                                        ) }}"
                                        class="mt-5 space-y-4"
                                    >
                                        @csrf

                                        <div>
                                            <label
                                                for="reference_{{ $check->id }}"
                                                class="block text-sm
                                                       font-semibold
                                                       text-slate-700 mb-2"
                                            >
                                                Actual Disbursement Reference
                                            </label>

                                            <input
                                                id="reference_{{ $check->id }}"
                                                name="settlement_reference"
                                                type="text"
                                                required
                                                minlength="6"
                                                maxlength="100"
                                                class="w-full rounded-lg
                                                       border-slate-300"
                                                placeholder="Cash voucher / UPI / bank transaction reference"
                                            >
                                        </div>

                                        <div>
                                            <label
                                                for="evidence_{{ $check->id }}"
                                                class="block text-sm
                                                       font-semibold
                                                       text-slate-700 mb-2"
                                            >
                                                Documentary Evidence
                                            </label>

                                            <textarea
                                                id="evidence_{{ $check->id }}"
                                                name="evidence_description"
                                                rows="4"
                                                required
                                                minlength="15"
                                                maxlength="2000"
                                                class="w-full rounded-lg
                                                       border-slate-300"
                                                placeholder="Describe payment date, recipient, cash voucher or transaction confirmation and evidence retained."
                                            ></textarea>
                                        </div>

                                        <label class="flex items-start
                                                      gap-3 text-sm
                                                      text-slate-700">

                                            <input
                                                type="checkbox"
                                                name="actually_paid"
                                                value="1"
                                                required
                                                class="mt-1 rounded
                                                       border-slate-300"
                                            >

                                            <span>
                                                I confirm that this refund
                                                has actually been disbursed,
                                                and the documentary
                                                evidence is available for
                                                independent verification.
                                            </span>
                                        </label>

                                        <button
                                            type="submit"
                                            class="inline-flex rounded-lg
                                                   bg-amber-600 px-5 py-2.5
                                                   text-sm font-semibold
                                                   text-white hover:bg-amber-700"
                                        >
                                            Record Disbursement
                                        </button>

                                    </form>

                                </div>

                            @endif

                            {{-- STAGE 3: INDEPENDENT VERIFICATION --}}

                            @if($canVerify)

                                <div class="border-t border-slate-200 pt-5">

                                    <h4 class="text-base font-bold
                                               text-slate-900">
                                        Stage 3 — Independent Verification
                                    </h4>

                                    @if($status === 'submitted')
                                        <p class="mt-2 text-sm
                                                  text-purple-800">
                                            Legacy settlement: explicitly
                                            verify independent evidence
                                            of actual payment before
                                            approving this record.
                                        </p>
                                    @endif

                                    <p class="mt-2 text-sm text-slate-600">
                                        Confirm the original receipt,
                                        actual disbursement, supporting
                                        documentation and refund amount.
                                        Approval will post a refund journal.
                                    </p>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'billing.investigation-refunds.verify',
                                            $check
                                        ) }}"
                                        class="mt-5 space-y-4"
                                    >
                                        @csrf

                                        <div>
                                            <label
                                                for="notes_{{ $check->id }}"
                                                class="block text-sm
                                                       font-semibold
                                                       text-slate-700 mb-2"
                                            >
                                                Verification Notes
                                            </label>

                                            <textarea
                                                id="notes_{{ $check->id }}"
                                                name="verification_notes"
                                                rows="4"
                                                required
                                                minlength="10"
                                                maxlength="2000"
                                                class="w-full rounded-lg
                                                       border-slate-300"
                                                placeholder="Record the evidence examined, payment reference, disbursement date and independent verification findings."
                                            ></textarea>
                                        </div>

                                        <label class="flex items-start
                                                      gap-3 text-sm
                                                      text-slate-700">

                                            <input
                                                type="checkbox"
                                                name="evidence_checked"
                                                value="1"
                                                required
                                                class="mt-1 rounded
                                                       border-slate-300"
                                            >

                                            <span>
                                                I independently examined
                                                documentary evidence
                                                confirming actual refund
                                                disbursement and verified
                                                the associated receipt
                                                and cancellation.
                                            </span>

                                        </label>

                                        <button
                                            type="submit"
                                            class="inline-flex rounded-lg
                                                   bg-green-700 px-5 py-2.5
                                                   text-sm font-semibold
                                                   text-white hover:bg-green-800"
                                        >
                                            Verify and Post Refund Journal
                                        </button>

                                    </form>

                                </div>

                            @elseif(in_array($status, ['disbursed', 'submitted'], true))

                                <div class="rounded-lg border border-amber-200
                                            bg-amber-50 p-4 text-sm
                                            text-amber-800">
                                    This refund requires an authorised
                                    independent verifier who was not
                                    involved in initiating, requesting
                                    or disbursing the refund.
                                </div>

                            @endif

                        </div>

                    </article>

                @empty

                    <div class="rounded-xl border border-slate-200
                                bg-white p-10 text-center">

                        <h3 class="font-semibold text-slate-900">
                            No Open Refund Settlements
                        </h3>

                        <p class="mt-2 text-sm text-slate-500">
                            Initiated refunds and settlements awaiting
                            verification will appear here.
                        </p>

                    </div>

                @endforelse

            </section>

            {{-- PAGINATION --}}

            <div>
                {{ $pending->links() }}
            </div>

            <footer class="pb-5 text-xs text-slate-500">
                Tura Christian Hospital — Investigation Refund Control
            </footer>

        </div>
    </div>
</x-app-layout>
