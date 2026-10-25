{{-- 
    Investigation Cancellation — Invoice-Level Request Screen

    This page only submits cancellation requests.
    It does not modify invoice totals, clinical records,
    payment records, or accounting journals.
--}}

<x-app-layout>
    @php
        $money = fn ($amount) => '₹' . number_format(
            (float) $amount,
            2
        );

        $statusStyles = [
            'pending' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'rejected' => 'bg-red-100 text-red-800',
            'executed' => 'bg-green-100 text-green-800',
        ];

        $items = $invoice->items;

        $totalItems = $items->count();

        $activeRequests = 0;
        $executedRequests = 0;

        foreach ($items as $summaryItem) {
            $itemRequests = $requestsByItem->get(
                $summaryItem->id,
                collect()
            );

            $activeRequests += $itemRequests
                ->whereIn('status', ['pending', 'approved'])
                ->count();

            $executedRequests += $itemRequests
                ->where('status', 'executed')
                ->count();
        }
    @endphp

    <div class="min-h-screen bg-slate-50">

        <div class="mx-auto max-w-7xl px-4 py-6
                    sm:px-6 lg:px-8 space-y-6">

            {{-- ================================================= --}}
            {{-- PAGE HEADER --}}
            {{-- ================================================= --}}

            <header class="flex flex-col gap-4
                           lg:flex-row lg:items-start
                           lg:justify-between">

                <div class="space-y-2">

                    <span class="inline-flex items-center
                                 rounded-full bg-blue-100
                                 px-3 py-1 text-xs font-semibold
                                 text-blue-800">
                        INVESTIGATION BILLING
                    </span>

                    <h1 class="text-2xl font-bold tracking-tight
                               text-slate-900 lg:text-3xl">
                        Investigation Cancellation
                    </h1>

                    <p class="text-sm text-slate-600">
                        Invoice
                        <span class="font-semibold text-slate-900">
                            {{ $invoice->invoice_no }}
                        </span>
                    </p>

                    <p class="max-w-3xl text-sm leading-relaxed
                              text-slate-500">
                        Review investigations, clinical status,
                        and previous cancellation requests before
                        submitting a new cancellation request.
                    </p>

                </div>

                <div class="flex flex-wrap gap-2">

                    <a
                        href="{{ route(
                            'billing.investigation-cancellations.queue'
                        ) }}"
                        class="inline-flex items-center justify-center
                               rounded-lg border border-slate-300
                               bg-white px-4 py-2.5
                               text-sm font-semibold text-slate-700
                               hover:bg-slate-50"
                    >
                        Approval Queue
                    </a>

                    <a
                        href="{{ route(
                            'billing.investigation-cancellations.reconciliation',
                            $invoice->id
                        ) }}"
                        class="inline-flex items-center justify-center
                               rounded-lg bg-slate-900
                               px-4 py-2.5 text-sm font-semibold
                               text-white hover:bg-slate-800"
                    >
                        Invoice Reconciliation
                    </a>

                </div>

            </header>

            {{-- ================================================= --}}
            {{-- NOTIFICATIONS --}}
            {{-- ================================================= --}}

            @if(session('success'))

                <div role="status"
                     class="rounded-xl border border-green-200
                            bg-green-50 px-5 py-4 text-sm
                            text-green-800">

                    <p class="font-semibold">
                        Request Submitted
                    </p>

                    <p class="mt-1">
                        {{ session('success') }}
                    </p>

                </div>

            @endif

            @if($errors->any())

                <div role="alert"
                     class="rounded-xl border border-red-200
                            bg-red-50 px-5 py-4 text-sm
                            text-red-800">

                    <p class="font-semibold mb-2">
                        Please Review
                    </p>

                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>

            @endif

            {{-- ================================================= --}}
            {{-- IMPORTANT NOTICE --}}
            {{-- ================================================= --}}

            <section class="rounded-xl border border-amber-200
                            bg-amber-50 px-5 py-4">

                <div class="flex items-start gap-3">

                    <div class="flex h-8 w-8 flex-shrink-0
                                items-center justify-center
                                rounded-lg bg-amber-100
                                font-bold text-amber-800">
                        !
                    </div>

                    <div>

                        <h2 class="text-sm font-bold text-amber-900">
                            Cancellation Request Only
                        </h2>

                        <p class="mt-1 text-sm leading-relaxed
                                  text-amber-800">
                            Submitting a cancellation request does not
                            cancel the investigation automatically,
                            reverse revenue, change an invoice,
                            or issue a patient refund.

                            Approval, financial execution, and refund
                            processing are separate authorised actions.
                        </p>

                    </div>

                </div>

            </section>

            {{-- ================================================= --}}
            {{-- SUMMARY CARDS --}}
            {{-- ================================================= --}}

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="rounded-xl border border-slate-200
                            border-t-4 border-t-blue-500
                            bg-white p-5 shadow-sm">

                    <p class="text-sm font-medium text-slate-600">
                        Invoice Investigations
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $totalItems }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Total invoice items listed
                    </p>

                </div>

                <div class="rounded-xl border border-slate-200
                            border-t-4 border-t-amber-500
                            bg-white p-5 shadow-sm">

                    <p class="text-sm font-medium text-slate-600">
                        Active Cancellation Requests
                    </p>

                    <p class="mt-2 text-3xl font-bold text-amber-700">
                        {{ $activeRequests }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Pending or approved requests
                    </p>

                </div>

                <div class="rounded-xl border border-slate-200
                            border-t-4 border-t-green-500
                            bg-white p-5 shadow-sm">

                    <p class="text-sm font-medium text-slate-600">
                        Executed Cancellations
                    </p>

                    <p class="mt-2 text-3xl font-bold text-green-700">
                        {{ $executedRequests }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Cancellation requests already executed
                    </p>

                </div>

            </section>

            {{-- ================================================= --}}
            {{-- INVESTIGATION LIST --}}
            {{-- ================================================= --}}

            <section class="space-y-4">

                <div class="flex flex-wrap items-center
                            justify-between gap-3">

                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Investigations on Invoice
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Select an eligible investigation
                            to request cancellation.
                        </p>
                    </div>

                    <span class="rounded-full bg-slate-200
                                 px-3 py-1 text-xs font-semibold
                                 text-slate-700">
                        {{ $totalItems }} Items
                    </span>

                </div>

                @forelse($items as $item)

                    @php
                        $orderItem = $item->serviceOrderItem;

                        $previous = $requestsByItem->get(
                            $item->id,
                            collect()
                        );

                        $blocked = $previous->contains(
                            fn ($request) => in_array(
                                $request->status,
                                ['pending', 'approved', 'executed'],
                                true
                            )
                        );

                        $clinicalStatus = strtolower(
                            (string) ($orderItem?->status ?? 'not linked')
                        );

                        $sampleStatus = strtolower(
                            (string) (
                                $orderItem?->diagnosticSample?->status
                                ?? 'none recorded'
                            )
                        );

                        $hasResult = (bool) $orderItem?->diagnosticResult;

                        $clinicalClass = match ($clinicalStatus) {
                            'ordered' => 'bg-blue-100 text-blue-800',
                            'pending' => 'bg-amber-100 text-amber-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            default => 'bg-slate-100 text-slate-700',
                        };

                        $canSubmit = $canRequest
                            && $orderItem
                            && !$blocked;
                    @endphp

                    <article class="overflow-hidden rounded-xl
                                    border border-slate-200
                                    bg-white shadow-sm">

                        {{-- INVESTIGATION HEADER --}}

                        <div class="flex flex-col gap-3
                                    border-b border-slate-200
                                    bg-slate-50 px-5 py-4
                                    sm:flex-row sm:items-center
                                    sm:justify-between">

                            <div>

                                <p class="text-xs font-medium
                                          uppercase tracking-wide
                                          text-slate-500">
                                    Investigation Item #{{ $item->id }}
                                </p>

                                <h3 class="mt-1 text-lg font-bold
                                           text-slate-900">
                                    {{ $item->description
                                        ?? ('Item #' . $item->id) }}
                                </h3>

                            </div>

                            <div class="sm:text-right">

                                <p class="text-xs font-medium
                                          text-slate-500">
                                    Investigation Charge
                                </p>

                                <p class="mt-1 text-xl font-bold
                                          text-slate-900">
                                    {{ $money($item->amount) }}
                                </p>

                            </div>

                        </div>

                        <div class="space-y-5 p-5">

                            {{-- CLINICAL STATUS --}}

                            <div class="grid grid-cols-1 gap-3
                                        md:grid-cols-3">

                                <div class="rounded-lg border
                                            border-slate-200
                                            bg-slate-50 p-4">

                                    <p class="text-xs font-semibold
                                              uppercase text-slate-500">
                                        Clinical Status
                                    </p>

                                    <span class="mt-2 inline-flex
                                                 rounded-full px-3 py-1
                                                 text-xs font-semibold
                                                 {{ $clinicalClass }}">
                                        {{ ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $clinicalStatus
                                            )
                                        ) }}
                                    </span>

                                </div>

                                <div class="rounded-lg border
                                            border-slate-200
                                            bg-slate-50 p-4">

                                    <p class="text-xs font-semibold
                                              uppercase text-slate-500">
                                        Specimen Status
                                    </p>

                                    <p class="mt-2 text-sm font-semibold
                                              text-slate-800">
                                        {{ ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $sampleStatus
                                            )
                                        ) }}
                                    </p>

                                </div>

                                <div class="rounded-lg border
                                            border-slate-200
                                            bg-slate-50 p-4">

                                    <p class="text-xs font-semibold
                                              uppercase text-slate-500">
                                        Result Status
                                    </p>

                                    @if($hasResult)

                                        <span class="mt-2 inline-flex
                                                     rounded-full
                                                     bg-green-100 px-3 py-1
                                                     text-xs font-semibold
                                                     text-green-800">
                                            Result Present
                                        </span>

                                    @else

                                        <span class="mt-2 inline-flex
                                                     rounded-full
                                                     bg-slate-100 px-3 py-1
                                                     text-xs font-semibold
                                                     text-slate-600">
                                            Not Recorded
                                        </span>

                                    @endif

                                </div>

                            </div>

                            {{-- CANCELLATION HISTORY --}}

                            @if($previous->isNotEmpty())

                                <div class="rounded-lg border
                                            border-slate-200
                                            bg-white p-4">

                                    <h4 class="text-sm font-bold
                                               text-slate-900">
                                        Cancellation History
                                    </h4>

                                    <div class="mt-3 space-y-3">

                                        @foreach($previous as $c)

                                            @php
                                                $requestStatus = strtolower(
                                                    (string) $c->status
                                                );

                                                $badgeClass = $statusStyles[
                                                    $requestStatus
                                                ] ?? 'bg-slate-100 text-slate-700';
                                            @endphp

                                            <div class="flex flex-col
                                                        gap-2 rounded-lg
                                                        border border-slate-100
                                                        bg-slate-50 p-3
                                                        sm:flex-row
                                                        sm:items-start
                                                        sm:justify-between">

                                                <div class="min-w-0">

                                                    <p class="text-sm
                                                              font-semibold
                                                              text-slate-800">
                                                        Request #{{ $c->id }}
                                                    </p>

                                                    <p class="mt-1 text-sm
                                                              text-slate-600
                                                              break-words">
                                                        {{ $c->reason }}
                                                    </p>

                                                    @if($c->reviewed_at)

                                                        <p class="mt-2 text-xs
                                                                  text-slate-500">
                                                            Reviewed:
                                                            {{ $c->reviewed_at
                                                                ->format(
                                                                    'd M Y, h:i A'
                                                                ) }}
                                                        </p>

                                                    @endif

                                                </div>

                                                <span class="inline-flex
                                                             self-start
                                                             rounded-full
                                                             px-3 py-1
                                                             text-xs
                                                             font-semibold
                                                             {{ $badgeClass }}">
                                                    {{ ucfirst($requestStatus) }}
                                                </span>

                                            </div>

                                        @endforeach

                                    </div>

                                </div>

                            @endif

                            {{-- REQUEST FORM OR BLOCKED MESSAGE --}}

                            @if($canSubmit)

                                <div class="border-t border-slate-200 pt-5">

                                    <div class="mb-4">

                                        <h4 class="text-base font-bold
                                                   text-slate-900">
                                            Request Investigation Cancellation
                                        </h4>

                                        <p class="mt-1 text-sm
                                                  text-slate-500">
                                            Provide a clear justification
                                            for this cancellation request.
                                            Submission does not perform
                                            a financial reversal.
                                        </p>

                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'billing.investigation-cancellations.request',
                                            $item
                                        ) }}"
                                        class="space-y-4"
                                    >
                                        @csrf

                                        <div>

                                            <label
                                                for="reason-{{ $item->id }}"
                                                class="mb-2 block
                                                       text-sm font-semibold
                                                       text-slate-700"
                                            >
                                                Reason for Cancellation
                                            </label>

                                            <textarea
                                                id="reason-{{ $item->id }}"
                                                name="reason"
                                                rows="3"
                                                required
                                                minlength="10"
                                                maxlength="2000"
                                                class="w-full rounded-lg
                                                       border-slate-300
                                                       focus:border-blue-500
                                                       focus:ring-blue-500"
                                                placeholder="Enter the clinical or administrative reason for cancelling this investigation (minimum 10 characters)."
                                            >{{ old('reason') }}</textarea>

                                            <p class="mt-2 text-xs
                                                      text-slate-500">
                                                Minimum 10 characters.
                                                The request will be
                                                reviewed by an authorised
                                                approver.
                                            </p>

                                        </div>

                                        <div class="flex flex-wrap
                                                    items-center
                                                    justify-between gap-3">

                                            <p class="max-w-lg text-xs
                                                      text-slate-500">
                                                Eligibility and clinical
                                                safeguards will be
                                                rechecked by the server.
                                            </p>

                                            <button
                                                type="submit"
                                                class="inline-flex
                                                       items-center
                                                       justify-center
                                                       rounded-lg bg-blue-700
                                                       px-5 py-2.5
                                                       text-sm font-semibold
                                                       text-white
                                                       hover:bg-blue-800"
                                            >
                                                Submit Cancellation Request
                                            </button>

                                        </div>

                                    </form>

                                </div>

                            @elseif($blocked)

                                <div class="rounded-lg border
                                            border-amber-200
                                            bg-amber-50 p-4">

                                    <h4 class="text-sm font-bold
                                               text-amber-900">
                                        New Request Unavailable
                                    </h4>

                                    <p class="mt-1 text-sm
                                              text-amber-800">
                                        An active, approved, or executed
                                        cancellation already exists
                                        for this investigation.
                                        A duplicate request cannot
                                        be submitted.
                                    </p>

                                </div>

                            @elseif(!$orderItem)

                                <div class="rounded-lg border
                                            border-slate-200
                                            bg-slate-50 p-4">

                                    <p class="text-sm text-slate-600">
                                        Cancellation request is unavailable
                                        because this invoice item is not
                                        linked to a diagnostic service order.
                                    </p>

                                </div>

                            @else

                                <div class="rounded-lg border
                                            border-slate-200
                                            bg-slate-50 p-4">

                                    <p class="text-sm text-slate-600">
                                        You do not have permission to
                                        request cancellation of this
                                        investigation.
                                    </p>

                                </div>

                            @endif

                        </div>

                    </article>

                @empty

                    <div class="rounded-xl border border-slate-200
                                bg-white p-10 text-center">

                        <h3 class="font-semibold text-slate-900">
                            No Investigations Found
                        </h3>

                        <p class="mt-2 text-sm text-slate-500">
                            This invoice does not currently have
                            any investigation items available
                            for cancellation review.
                        </p>

                    </div>

                @endforelse

            </section>

            {{-- ================================================= --}}
            {{-- FOOTER --}}
            {{-- ================================================= --}}

            <footer class="flex flex-wrap items-center
                           justify-between gap-3
                           border-t border-slate-200
                           pt-5 pb-6">

                <p class="text-xs text-slate-500">
                    Tura Christian Hospital —
                    Investigation Cancellation Management
                </p>

                <a
                    href="{{ route(
                        'billing.investigation-cancellations.queue'
                    ) }}"
                    class="text-sm font-semibold
                           text-blue-700 hover:underline"
                >
                    Return to Approval Queue
                </a>

            </footer>

        </div>

    </div>
</x-app-layout>
