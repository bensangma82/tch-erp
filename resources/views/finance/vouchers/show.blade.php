<x-app-layout>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Finance Voucher
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $financeVoucher->voucher_no }}
                </p>
            </div>

            <div class="flex gap-2">

                <a
                    href="{{ route('finance.vouchers.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    ← Back to Vouchers
                </a>

                <a
                    href="{{ route('finance.dashboard') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Finance Dashboard
                </a>

            </div>

        </div>


        {{-- Success --}}
        @if(session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif


        {{-- Errors --}}
        @if($errors->any())
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
                <ul class="list-disc space-y-1 pl-5 text-sm text-rose-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Voucher Card --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            {{-- Voucher heading --}}
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <div class="flex flex-wrap items-center gap-2">

                            <h2 class="text-lg font-bold text-slate-900">
                                {{ $financeVoucher->voucher_no }}
                            </h2>

                            @if($financeVoucher->voucher_type === 'receipt')

                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    Receipt
                                </span>

                            @elseif($financeVoucher->voucher_type === 'payment')

                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                    Payment
                                </span>

                            @else

                                <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                    Transfer
                                </span>

                            @endif

                        </div>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $financeVoucher->voucher_date->format('d M Y') }}
                        </p>

                    </div>


                    <div>

                        @if($financeVoucher->status === 'posted')

                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">
                                Posted
                            </span>

                        @elseif($financeVoucher->status === 'cancelled')

                            <span class="inline-flex rounded-full bg-rose-100 px-3 py-1 text-sm font-semibold text-rose-700">
                                Cancelled
                            </span>

                        @else

                            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-700">
                                Draft
                            </span>

                        @endif

                    </div>

                </div>

            </div>


            {{-- Voucher details --}}
            <div class="p-6">

                <dl class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">

                    <div>
                        <dt class="text-sm font-medium text-slate-500">
                            Amount
                        </dt>

                        <dd class="mt-1 text-2xl font-bold text-slate-900">
                            ₹{{ number_format((float) $financeVoucher->amount, 2) }}
                        </dd>
                    </div>


                    @if($financeVoucher->voucher_type !== 'transfer')

                        <div>
                            <dt class="text-sm font-medium text-slate-500">
                                {{ $financeVoucher->voucher_type === 'receipt'
                                    ? 'Income Head'
                                    : 'Expense Head' }}
                            </dt>

                            <dd class="mt-1 font-semibold text-slate-900">
                                {{ $financeVoucher->financeHead?->name ?? '—' }}
                            </dd>

                            @if($financeVoucher->financeHead?->category)
                                <dd class="mt-1 text-xs text-slate-500">
                                    {{ $financeVoucher->financeHead->category }}
                                </dd>
                            @endif
                        </div>

                    @endif


                    <div>
                        <dt class="text-sm font-medium text-slate-500">

                            @if($financeVoucher->voucher_type === 'receipt')
                                Deposited To
                            @elseif($financeVoucher->voucher_type === 'payment')
                                Paid From
                            @else
                                Transfer From
                            @endif

                        </dt>

                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ $financeVoucher->financeAccount?->name ?? '—' }}
                        </dd>
                    </div>


                    @if($financeVoucher->voucher_type === 'transfer')

                        <div>
                            <dt class="text-sm font-medium text-slate-500">
                                Transfer To
                            </dt>

                            <dd class="mt-1 font-semibold text-slate-900">
                                {{ $financeVoucher->destinationAccount?->name ?? '—' }}
                            </dd>
                        </div>

                    @endif


                    <div>
                        <dt class="text-sm font-medium text-slate-500">
                            Payment Mode
                        </dt>

                        <dd class="mt-1 text-slate-900">
                            {{ $financeVoucher->payment_mode ?: '—' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-sm font-medium text-slate-500">
                            Reference No.
                        </dt>

                        <dd class="mt-1 text-slate-900">
                            {{ $financeVoucher->reference_no ?: '—' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-sm font-medium text-slate-500">

                            @if($financeVoucher->voucher_type === 'receipt')
                                Received From
                            @elseif($financeVoucher->voucher_type === 'payment')
                                Paid To
                            @else
                                Party / Description
                            @endif

                        </dt>

                        <dd class="mt-1 text-slate-900">
                            {{ $financeVoucher->party_name ?: '—' }}
                        </dd>
                    </div>


                    <div>
                        <dt class="text-sm font-medium text-slate-500">
                            Created By
                        </dt>

                        <dd class="mt-1 text-slate-900">
                            {{ $financeVoucher->createdBy?->name ?? '—' }}
                        </dd>
                    </div>


                    @if($financeVoucher->posted_at)

                        <div>
                            <dt class="text-sm font-medium text-slate-500">
                                Posted
                            </dt>

                            <dd class="mt-1 text-slate-900">
                                {{ $financeVoucher->posted_at->format('d M Y, h:i A') }}
                            </dd>

                            <dd class="mt-1 text-xs text-slate-500">
                                By {{ $financeVoucher->postedBy?->name ?? '—' }}
                            </dd>
                        </div>

                    @endif


                    @if($financeVoucher->cancelled_at)

                        <div>
                            <dt class="text-sm font-medium text-slate-500">
                                Cancelled
                            </dt>

                            <dd class="mt-1 text-slate-900">
                                {{ $financeVoucher->cancelled_at->format('d M Y, h:i A') }}
                            </dd>

                            <dd class="mt-1 text-xs text-slate-500">
                                By {{ $financeVoucher->cancelledBy?->name ?? '—' }}
                            </dd>
                        </div>

                    @endif

                </dl>


                @if($financeVoucher->narration)

                    <div class="mt-6 border-t border-slate-200 pt-6">

                        <div class="text-sm font-medium text-slate-500">
                            Narration / Remarks
                        </div>

                        <div class="mt-2 whitespace-pre-line text-sm text-slate-800">
                            {{ $financeVoucher->narration }}
                        </div>

                    </div>

                @endif


                @if($financeVoucher->status === 'cancelled')

                    <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 p-4">

                        <div class="text-sm font-semibold text-rose-800">
                            Cancellation Reason
                        </div>

                        <div class="mt-1 text-sm text-rose-700">
                            {{ $financeVoucher->cancellation_reason }}
                        </div>

                    </div>

                @endif

            </div>


            {{-- Actions --}}
            @if($financeVoucher->status === 'draft')

                <div class="border-t border-slate-200 bg-amber-50 px-6 py-5">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <div class="font-semibold text-amber-900">
                                Draft Voucher
                            </div>

                            <div class="mt-1 text-sm text-amber-700">
                                This transaction is not yet included in Finance totals.
                            </div>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('finance.vouchers.post', $financeVoucher) }}"
                            onsubmit="return confirm('Post this voucher? Once posted, it will be included in Finance totals.');"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                Post Voucher
                            </button>
                        </form>

                    </div>

                </div>


            @elseif($financeVoucher->status === 'posted')

                <div class="border-t border-slate-200 bg-slate-50 px-6 py-5">

                    <div class="mb-4">

                        <div class="font-semibold text-slate-900">
                            Cancel Posted Voucher
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            The voucher will remain in the audit history but will no longer count toward Finance totals.
                        </div>

                    </div>

                    <form
                        method="POST"
                        action="{{ route('finance.vouchers.cancel', $financeVoucher) }}"
                        onsubmit="return confirm('Cancel this posted voucher?');"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="flex flex-col gap-3 sm:flex-row">

                            <input
                                type="text"
                                name="cancellation_reason"
                                value="{{ old('cancellation_reason') }}"
                                required
                                maxlength="1000"
                                placeholder="Reason for cancellation"
                                class="flex-1 rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"
                            >

                            <button
    type="submit"
    class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
>
    Cancel Voucher
</button>

                        </div>

                    </form>

                </div>

            @endif

        </div>

    </div>

</x-app-layout>