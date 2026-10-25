<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

        {{-- Page heading --}}
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Investigation Refund Verification
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                Review investigation refund settlements before
                posting accounting journals. Independent verification
                and documentary evidence are mandatory.
                This page does not initiate cash or bank transfers.
            </p>
        </div>

        {{-- Success message --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200
                        bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="rounded-lg border border-red-200
                        bg-red-50 px-4 py-3 text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Pending refund settlements --}}
        @forelse ($pending as $check)

            @php
                $cancellation = \App\Models\InvestigationCancellationRequest::query()
                    ->find($check->cancellation_id);

                $invoice = $cancellation
                    ? \App\Models\Invoice::query()
                        ->find($cancellation->invoice_id)
                    : null;

                $payment = \App\Models\Payment::query()
                    ->find($check->payment_id);

                $cashier = \App\Models\User::query()
                    ->find($check->submitted_by);

                $currentUser = auth()->user();

                $canVerify = $currentUser
                    && $currentUser->permissions()
                        ->where(
                            'permissions.name',
                            'billing.investigation-refund.verify'
                        )
                        ->exists()
                    && (int) $currentUser->id !== (int) $check->submitted_by
                    && (
                        ! $cancellation
                        || (int) $currentUser->id !== (int) $cancellation->requested_by
                    );

                $formattedAmount = number_format(
                    (float) $check->amount,
                    2
                );
            @endphp

            <div class="rounded-xl border border-slate-200
                        bg-white shadow-sm overflow-hidden">

                {{-- Settlement heading --}}
                <div class="flex flex-wrap items-center
                            justify-between gap-3
                            bg-slate-50 border-b px-5 py-4">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Refund Settlement #{{ $check->id }}
                        </h2>

                        <p class="text-xs text-slate-500 mt-1">
                            Cancellation Request
                            #{{ $check->cancellation_id }}
                        </p>
                    </div>

                    <span class="inline-flex rounded-full
                                 bg-amber-100 text-amber-800
                                 px-3 py-1 text-xs font-semibold">
                        Pending Verification
                    </span>
                </div>

                <div class="p-5 space-y-5">

                    {{-- Settlement summary --}}
                    <div class="grid grid-cols-1 md:grid-cols-2
                                gap-4 text-sm">

                        <div>
                            <p class="text-slate-500">
                                Refund Amount
                            </p>
                            <p class="font-bold text-lg text-slate-900">
                                ₹{{ $formattedAmount }}
                            </p>
                        </div>

                        <div>
                            <p class="text-slate-500">
                                Settlement Method
                            </p>
                            <p class="font-semibold uppercase">
                                {{ $check->settlement_method }}
                            </p>
                        </div>

                        <div>
                            <p class="text-slate-500">
                                Settlement Reference
                            </p>
                            <p class="font-medium break-all">
                                {{ $check->settlement_reference }}
                            </p>
                        </div>

                        <div>
                            <p class="text-slate-500">
                                Submitted Date & Time
                            </p>
                            <p class="font-medium">
                                {{ $check->created_at
                                    ? $check->created_at->format('d M Y, h:i A')
                                    : 'Not available' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-slate-500">
                                Submitted By / Cashier
                            </p>
                            <p class="font-medium">
                                {{ $cashier?->name ?? 'Unknown User' }}
                                <span class="text-slate-500">
                                    (ID: {{ $check->submitted_by }})
                                </span>
                            </p>
                        </div>

                        <div>
                            <p class="text-slate-500">
                                Original Receipt
                            </p>
                            <p class="font-medium">
                                {{ $payment?->receipt_no ?? 'Unavailable' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-slate-500">
                                Original Receipt Amount
                            </p>
                            <p class="font-medium">
                                @if ($payment)
                                    ₹{{ number_format((float) $payment->amount, 2) }}
                                @else
                                    Unavailable
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-slate-500">
                                Invoice Number
                            </p>
                            <p class="font-medium">
                                {{ $invoice?->invoice_no ?? 'Unavailable' }}
                            </p>
                        </div>

                    </div>

                    {{-- Documentary evidence --}}
                    <div class="rounded-lg border border-slate-200
                                bg-slate-50 p-4">

                        <h3 class="font-semibold text-sm text-slate-900">
                            Documentary Settlement Evidence
                        </h3>

                        <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap break-words">{{ $check->evidence_description }}</p>
                    </div>

                    {{-- Invoice reconciliation --}}
                    @if ($invoice)
                        <a
                            href="{{ route(
                                'billing.investigation-cancellations.reconciliation',
                                $invoice
                            ) }}"
                            class="inline-flex items-center
                                   text-sm font-semibold text-blue-700
                                   hover:underline"
                        >
                            View Invoice Reconciliation
                        </a>
                    @endif

                    {{-- Independent verification --}}
                    @if ($canVerify)

                        <div class="border-t border-slate-200 pt-5">

                            <h3 class="font-semibold text-slate-900 mb-2">
                                Independent Verification
                            </h3>

                            <p class="text-sm text-slate-600 mb-4">
                                Verify the original payment evidence,
                                settlement reference and actual
                                disbursement before approving.
                                Approval will post a refund accounting journal.
                            </p>

                            <form
                                method="POST"
                                action="{{ route(
                                    'billing.investigation-refunds.verify',
                                    $check
                                ) }}"
                                class="space-y-4"
                            >
                                @csrf

                                <div>
                                    <label class="block text-sm font-medium
                                                  text-slate-700 mb-2">
                                        Verification Notes
                                    </label>

                                    <textarea
                                        name="verification_notes"
                                        rows="4"
                                        required
                                        minlength="10"
                                        maxlength="2000"
                                        class="w-full rounded-lg border-slate-300
                                               focus:border-blue-500
                                               focus:ring-blue-500"
                                        placeholder="Record the verified cash voucher or bank reference, transaction date, and independent evidence reviewed."
                                    >{{ old('verification_notes') }}</textarea>
                                </div>

                                <label class="flex items-start gap-3
                                              text-sm text-slate-700">

                                    <input
                                        type="checkbox"
                                        name="evidence_checked"
                                        value="1"
                                        required
                                        class="mt-1 rounded border-slate-300"
                                    >

                                    <span>
                                        I confirm that I independently
                                        examined documentary evidence
                                        showing that the refund was
                                        actually disbursed.
                                    </span>
                                </label>

                                <button
                                    type="submit"
                                    class="inline-flex rounded-lg bg-blue-700
                                           px-5 py-2.5 text-sm font-semibold
                                           text-white hover:bg-blue-800"
                                >
                                    Verify and Post Refund Journal
                                </button>
                            </form>
                        </div>

                    @else

                        <div class="rounded-lg border border-amber-200
                                    bg-amber-50 p-4 text-sm text-amber-800">
                            Verification is unavailable to this user.
                            A different authorised verifier is required.
                        </div>

                    @endif

                </div>
            </div>

        @empty

            <div class="rounded-xl border border-slate-200
                        bg-white p-8 text-center">
                <p class="font-medium text-slate-800">
                    No pending refund settlements
                </p>

                <p class="text-sm text-slate-500 mt-2">
                    Submitted refunds awaiting independent
                    verification will appear here.
                </p>
            </div>

        @endforelse

        {{-- Pagination --}}
        <div>
            {{ $pending->links() }}
        </div>

    </div>
</x-app-layout>
