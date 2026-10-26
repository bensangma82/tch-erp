<x-app-layout>
    @php
        $currentUser = auth()->user();

        $canExecute = $currentUser
            && $currentUser->permissions()
                ->where(
                    'permissions.name',
                    'billing.investigation-cancellation.execute'
                )
                ->exists();

        $money = fn ($amount) => '₹' . number_format(
            (float) $amount,
            2
        );

        $statusOptions = [
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'executed' => 'Executed',
            'all' => 'All Requests',
        ];

        $statusStyles = [
            'pending' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'rejected' => 'bg-red-100 text-red-800',
            'executed' => 'bg-green-100 text-green-800',
        ];

        $pageRecords = $cancellations->getCollection();

        $pendingCount = $pageRecords
            ->where('status', 'pending')
            ->count();

        $approvedCount = $pageRecords
            ->where('status', 'approved')
            ->count();

        $rejectedCount = $pageRecords
            ->where('status', 'rejected')
            ->count();

        $executedCount = $pageRecords
            ->where('status', 'executed')
            ->count();
    @endphp

    <div class="min-h-screen bg-slate-50">

        <div class="mx-auto max-w-7xl space-y-6
                    px-4 py-6 sm:px-6 lg:px-8">

            {{-- ================================================= --}}
            {{-- HEADER --}}
            {{-- ================================================= --}}

            <div class="flex flex-col gap-4
                        lg:flex-row lg:items-start
                        lg:justify-between">

                <div class="space-y-2">

                    <span class="inline-flex rounded-full
                                 bg-blue-100 px-3 py-1
                                 text-xs font-semibold text-blue-800">
                        INVESTIGATION BILLING
                    </span>

                    <h1 class="text-2xl font-bold
                               tracking-tight text-slate-900
                               lg:text-3xl">
                        Investigation Cancellation Approvals
                    </h1>

                    <p class="max-w-3xl text-sm text-slate-600">
                        Review cancellation requests, assess
                        supporting information, record approval
                        decisions, and manage authorised
                        revenue reversal execution.
                    </p>

                </div>

                <div class="flex flex-wrap gap-2">

                    <a
                        href="{{ route(
                            'billing.investigation-cancellations.queue',
                            ['status' => 'pending']
                        ) }}"
                        class="rounded-lg border border-slate-300
                               bg-white px-4 py-2.5 text-sm
                               font-semibold text-slate-700
                               hover:bg-slate-100"
                    >
                        Pending Requests
                    </a>

                    <a
                        href="{{ route(
                            'billing.investigation-cancellations.queue',
                            ['status' => 'executed']
                        ) }}"
                        class="rounded-lg bg-slate-900
                               px-4 py-2.5 text-sm font-semibold
                               text-white hover:bg-slate-800"
                    >
                        Executed Cancellations
                    </a>

                </div>

            </div>

            {{-- ================================================= --}}
            {{-- NOTIFICATIONS --}}
            {{-- ================================================= --}}

            @if(session('success'))

                <div
                    role="status"
                    class="rounded-xl border border-green-200
                           bg-green-50 px-5 py-4
                           text-sm text-green-800"
                >
                    <strong>Success:</strong>
                    {{ session('success') }}
                </div>

            @endif

            @if($errors->any())

                <div
                    role="alert"
                    class="rounded-xl border border-red-200
                           bg-red-50 px-5 py-4
                           text-sm text-red-800"
                >
                    <p class="font-semibold mb-2">
                        Please review:
                    </p>

                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

            @endif

            {{-- ================================================= --}}
            {{-- IMPORTANT ACCOUNTING NOTICE --}}
            {{-- ================================================= --}}

            <div class="rounded-xl border border-amber-200
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
                            Approval and Financial Execution Are Separate
                        </h2>

                        <p class="mt-1 text-sm leading-relaxed
                                  text-amber-800">
                            Approving an investigation cancellation
                            does not reverse revenue or issue a refund.
                            Revenue reversal requires a separate,
                            explicitly authorised execution action.
                            Refund initiation, disbursement and
                            verification are handled through the
                            refund workflow.
                        </p>

                    </div>

                </div>

            </div>

            {{-- ================================================= --}}
            {{-- SUMMARY CARDS --}}
            {{-- ================================================= --}}

            <section class="space-y-3">

                <div class="flex flex-wrap items-center
                            justify-between gap-2">

                    <h2 class="text-lg font-bold text-slate-900">
                        Request Overview
                    </h2>

                    <span class="text-xs text-slate-500">
                        Showing {{ $cancellations->count() }}
                        of {{ $cancellations->total() }} requests
                    </span>

                </div>

                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">

                    <div class="rounded-xl border border-slate-200
                                border-t-4 border-t-amber-500
                                bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-600">
                            Pending Review
                        </p>

                        <p class="mt-3 text-3xl font-bold
                                  text-amber-700">
                            {{ $pendingCount }}
                        </p>

                    </div>

                    <div class="rounded-xl border border-slate-200
                                border-t-4 border-t-blue-500
                                bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-600">
                            Approved
                        </p>

                        <p class="mt-3 text-3xl font-bold
                                  text-blue-700">
                            {{ $approvedCount }}
                        </p>

                    </div>

                    <div class="rounded-xl border border-slate-200
                                border-t-4 border-t-red-500
                                bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-600">
                            Rejected
                        </p>

                        <p class="mt-3 text-3xl font-bold
                                  text-red-700">
                            {{ $rejectedCount }}
                        </p>

                    </div>

                    <div class="rounded-xl border border-slate-200
                                border-t-4 border-t-green-500
                                bg-white p-5 shadow-sm">

                        <p class="text-sm font-medium text-slate-600">
                            Executed
                        </p>

                        <p class="mt-3 text-3xl font-bold
                                  text-green-700">
                            {{ $executedCount }}
                        </p>

                    </div>

                </div>

                <p class="text-xs text-slate-500">
                    Status counts reflect the records on the
                    current page and selected filter, not
                    hospital-wide totals.
                </p>

            </section>

            {{-- ================================================= --}}
            {{-- FILTER PANEL --}}
            {{-- ================================================= --}}

            <section class="rounded-xl border border-slate-200
                            bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route(
                        'billing.investigation-cancellations.queue'
                    ) }}"
                    class="flex flex-col gap-4
                           sm:flex-row sm:items-end"
                >

                    <div class="w-full sm:max-w-xs">

                        <label
                            for="status"
                            class="mb-2 block text-sm
                                   font-semibold text-slate-700"
                        >
                            Filter by Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg
                                   border-slate-300
                                   focus:border-blue-500
                                   focus:ring-blue-500"
                        >
                            @foreach($statusOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected($status === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>

                    </div>

                    <button
                        type="submit"
                        class="rounded-lg bg-blue-700
                               px-5 py-2.5 text-sm
                               font-semibold text-white
                               hover:bg-blue-800"
                    >
                        Apply Filter
                    </button>

                    <a
                        href="{{ route(
                            'billing.investigation-cancellations.queue',
                            ['status' => 'all']
                        ) }}"
                        class="rounded-lg border border-slate-300
                               bg-white px-5 py-2.5 text-center
                               text-sm font-semibold text-slate-700
                               hover:bg-slate-50"
                    >
                        Show All
                    </a>

                </form>

            </section>

            {{-- ================================================= --}}
            {{-- CANCELLATION REQUEST TABLE --}}
            {{-- ================================================= --}}

            <section class="overflow-hidden rounded-xl
                            border border-slate-200
                            bg-white shadow-sm">

                <div class="flex flex-wrap items-center
                            justify-between gap-3
                            border-b border-slate-200
                            bg-slate-50 px-5 py-4">

                    <div>

                        <h2 class="text-lg font-bold text-slate-900">
                            Cancellation Requests
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Review the request and its financial
                            implications before taking action.
                        </p>

                    </div>

                    <span class="rounded-full bg-slate-200
                                 px-3 py-1 text-xs
                                 font-semibold text-slate-700">
                        {{ $statusOptions[$status] ?? ucfirst($status) }}
                    </span>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y
                                  divide-slate-200 text-left text-sm">

                        <thead class="bg-slate-50">

                            <tr class="text-xs uppercase
                                       tracking-wide text-slate-600">

                                <th scope="col" class="px-5 py-4">
                                    Request
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Invoice
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Investigation
                                </th>

                                <th scope="col" class="px-5 py-4 text-right">
                                    Amount
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Clinical / MS
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Status
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Financial Action
                                </th>

                                <th scope="col" class="px-5 py-4">
                                    Review
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($cancellations as $c)

                                @php
                                    $rowStatus = strtolower(
                                        (string) $c->status
                                    );

                                    $badgeClass = $statusStyles[$rowStatus]
                                        ?? 'bg-slate-100 text-slate-700';

                                    $canPreview = $rowStatus === 'approved'
                                        && $c->executed_at === null
                                        && ($canDecide || $canExecute);

                                    $canExecuteRecord =
                                        $rowStatus === 'approved'
                                        && $c->executed_at === null
                                        && $canExecute;
                                @endphp

                                <tr class="align-top transition
                                           odd:bg-white even:bg-slate-50/40
                                           hover:bg-blue-50/30">

                                    {{-- REQUEST --}}

                                    <td class="px-5 py-5">

                                        <p class="font-bold text-slate-900">
                                            #{{ $c->id }}
                                        </p>

                                        <p class="mt-1 text-xs
                                                  text-slate-600">
                                            {{ $c->requester?->name
                                                ?? 'Unknown requester' }}
                                        </p>

                                        @if($c->reason)
                                            <p class="mt-2 max-w-xs
                                                      text-xs leading-5
                                                      text-slate-500
                                                      break-words">
                                                {{ $c->reason }}
                                            </p>
                                        @endif

                                    </td>

                                    {{-- INVOICE --}}

                                    <td class="px-5 py-5">

                                        <a
                                            href="{{ route(
                                                'billing.investigation-cancellations.invoice',
                                                $c->invoice_id
                                            ) }}"
                                            class="font-semibold text-blue-700
                                                   hover:underline"
                                        >
                                            {{ $c->invoice?->invoice_no
                                                ?? $c->invoice_id }}
                                        </a>

                                        <a
                                            href="{{ route(
                                                'billing.investigation-cancellations.reconciliation',
                                                $c->invoice_id
                                            ) }}"
                                            class="mt-2 block text-xs
                                                   font-medium text-slate-600
                                                   hover:text-blue-700
                                                   hover:underline"
                                        >
                                            View Reconciliation
                                        </a>

                                    </td>

                                    {{-- INVESTIGATION --}}

                                    <td class="px-5 py-5">

                                        <p class="max-w-xs font-medium
                                                  text-slate-800">
                                            {{ $c->invoiceItem?->description
                                                ?? $c->invoice_item_id }}
                                        </p>

                                    </td>

                                    {{-- AMOUNT --}}

                                    <td class="whitespace-nowrap
                                               px-5 py-5 text-right
                                               font-bold text-slate-900">
                                        {{ $money($c->requested_amount) }}
                                    </td>

                                    {{-- CLINICAL / MS --}}

                                    <td class="px-5 py-5">

                                        <p class="text-xs font-medium
                                                  text-slate-700">
                                            {{ ucfirst(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $c->clinical_status_at_request
                                                    ?? 'Unknown'
                                                )
                                            ) }}
                                        </p>

                                        <p class="mt-2 text-xs
                                                  {{ $c->requires_ms_approval
                                                      ? 'text-amber-700'
                                                      : 'text-slate-500' }}">
                                            {{ $c->requires_ms_approval
                                                ? 'MS approval required'
                                                : 'MS not required at request' }}
                                        </p>

                                    </td>

                                    {{-- STATUS --}}

                                    <td class="px-5 py-5">

                                        <span class="inline-flex
                                                     rounded-full
                                                     px-3 py-1 text-xs
                                                     font-semibold
                                                     {{ $badgeClass }}">
                                            {{ ucfirst($rowStatus) }}
                                        </span>

                                    </td>

                                    {{-- FINANCIAL ACTION --}}

                                    <td class="px-5 py-5">

                                        <div class="flex min-w-36
                                                    flex-col items-start
                                                    gap-2">

                                            @if($canPreview)

                                                <a
                                                    href="{{ route(
                                                        'billing.investigation-cancellations.preview',
                                                        $c
                                                    ) }}"
                                                    class="inline-flex
                                                           items-center
                                                           justify-center
                                                           rounded-lg
                                                           border border-blue-200
                                                           bg-blue-50
                                                           px-3 py-2
                                                           text-xs font-semibold
                                                           text-blue-700
                                                           hover:bg-blue-100"
                                                >
                                                    Preview Reversal
                                                </a>

                                            @endif

                                            @if($canExecuteRecord)

                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'billing.investigation-cancellations.execute',
                                                        $c
                                                    ) }}"
                                                    onsubmit="return confirm('Execute revenue reversal for cancellation #{{ $c->id }}? This will post an accounting journal.');"
                                                >
                                                    @csrf

                                                    <input
                                                        type="hidden"
                                                        name="confirmation"
                                                        value="EXECUTE REVERSAL"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg
                                                               bg-red-700
                                                               px-3 py-2
                                                               text-xs
                                                               font-semibold
                                                               text-white
                                                               hover:bg-red-800"
                                                    >
                                                        Execute Reversal
                                                    </button>
                                                </form>

                                            @endif

                                            @if(
                                                !$canPreview
                                                && !$canExecuteRecord
                                            )

                                                <span class="text-xs
                                                             text-slate-400">
                                                    No action available
                                                </span>

                                            @endif

                                        </div>

                                    </td>

                                    {{-- APPROVAL REVIEW --}}

                                    <td class="px-5 py-5">

                                        @if(
                                            $rowStatus === 'pending'
                                            && $canDecide
                                        )

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'billing.investigation-cancellations.decide',
                                                    $c
                                                ) }}"
                                                class="min-w-56 space-y-3"
                                            >
                                                @csrf

                                                <div>

                                                    <label
                                                        for="decision-{{ $c->id }}"
                                                        class="mb-1 block
                                                               text-xs
                                                               font-semibold
                                                               text-slate-700"
                                                    >
                                                        Decision
                                                    </label>

                                                    <select
                                                        id="decision-{{ $c->id }}"
                                                        name="decision"
                                                        required
                                                        class="w-full
                                                               rounded-lg
                                                               border-slate-300
                                                               text-sm"
                                                    >
                                                        <option value="approve">
                                                            Approve
                                                        </option>

                                                        <option value="reject">
                                                            Reject
                                                        </option>
                                                    </select>

                                                </div>

                                                <div>

                                                    <label
                                                        for="reason-{{ $c->id }}"
                                                        class="mb-1 block
                                                               text-xs
                                                               font-semibold
                                                               text-slate-700"
                                                    >
                                                        Decision Reason
                                                    </label>

                                                    <textarea
                                                        id="reason-{{ $c->id }}"
                                                        name="decision_reason"
                                                        rows="3"
                                                        required
                                                        minlength="10"
                                                        maxlength="2000"
                                                        class="w-full
                                                               rounded-lg
                                                               border-slate-300
                                                               text-sm"
                                                        placeholder="Minimum 10 characters"
                                                    ></textarea>

                                                </div>

                                                <button
                                                    type="submit"
                                                    class="w-full rounded-lg
                                                           bg-blue-700
                                                           px-3 py-2
                                                           text-xs
                                                           font-semibold
                                                           text-white
                                                           hover:bg-blue-800"
                                                >
                                                    Record Decision
                                                </button>

                                            </form>

                                            <p class="mt-2 max-w-xs
                                                      text-xs leading-5
                                                      text-slate-500">
                                                MS designation, clinical
                                                status and separation of
                                                duties are rechecked
                                                by the server.
                                            </p>

                                        @else

                                            <p class="text-sm text-slate-700">
                                                {{ $c->reviewer?->name
                                                    ?? 'Not reviewed' }}
                                            </p>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="8"
                                        class="px-6 py-14 text-center"
                                    >
                                        <div class="space-y-2">

                                            <p class="text-base font-semibold
                                                      text-slate-800">
                                                No Cancellation Requests Found
                                            </p>

                                            <p class="text-sm text-slate-500">
                                                Try another status filter
                                                or view all requests.
                                            </p>

                                            <a
                                                href="{{ route(
                                                    'billing.investigation-cancellations.queue',
                                                    ['status' => 'all']
                                                ) }}"
                                                class="inline-flex mt-3
                                                       rounded-lg border
                                                       border-slate-300
                                                       px-4 py-2
                                                       text-sm font-semibold
                                                       text-blue-700
                                                       hover:bg-slate-50"
                                            >
                                                View All Requests
                                            </a>

                                        </div>
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </section>

            {{-- ================================================= --}}
            {{-- PAGINATION --}}
            {{-- ================================================= --}}

            <div class="rounded-xl border border-slate-200
                        bg-white px-5 py-4 shadow-sm">

                {{ $cancellations->links() }}

            </div>

            {{-- FOOTER --}}

            <p class="pb-6 text-xs text-slate-500">
                Tura Christian Hospital —
                Investigation Cancellation Governance
            </p>

        </div>

    </div>
</x-app-layout>
