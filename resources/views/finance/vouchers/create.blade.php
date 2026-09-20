<x-app-layout>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">

                    @if($type === 'receipt')
                        New Receipt Voucher
                    @elseif($type === 'payment')
                        New Payment Voucher
                    @else
                        New Transfer Voucher
                    @endif

                </h1>

                <p class="mt-1 text-sm text-slate-500">

                    @if($type === 'receipt')
                        Record manual income received by the hospital.
                    @elseif($type === 'payment')
                        Record a manual hospital expense or payment.
                    @else
                        Transfer funds between hospital cash or bank accounts.
                    @endif

                </p>
            </div>

            <a
                href="{{ route('finance.vouchers.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
            >
                ← Back to Vouchers
            </a>

        </div>


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


        {{-- Voucher Type Switch --}}
        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">

            <a
                href="{{ route('finance.vouchers.create', ['type' => 'receipt']) }}"
                class="rounded-xl border px-4 py-4 text-center transition
                    {{ $type === 'receipt'
                        ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
            >
                <div class="font-semibold">
                    Receipt
                </div>

                <div class="mt-1 text-xs">
                    Money received
                </div>
            </a>


            <a
                href="{{ route('finance.vouchers.create', ['type' => 'payment']) }}"
                class="rounded-xl border px-4 py-4 text-center transition
                    {{ $type === 'payment'
                        ? 'border-rose-500 bg-rose-50 text-rose-700'
                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
            >
                <div class="font-semibold">
                    Payment
                </div>

                <div class="mt-1 text-xs">
                    Money paid
                </div>
            </a>


            <a
                href="{{ route('finance.vouchers.create', ['type' => 'transfer']) }}"
                class="rounded-xl border px-4 py-4 text-center transition
                    {{ $type === 'transfer'
                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}"
            >
                <div class="font-semibold">
                    Transfer
                </div>

                <div class="mt-1 text-xs">
                    Move between accounts
                </div>
            </a>

        </div>


        {{-- Voucher Form --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <form
                method="POST"
                action="{{ route('finance.vouchers.store') }}"
            >
                @csrf

                <input
                    type="hidden"
                    name="voucher_type"
                    value="{{ $type }}"
                >


                <div class="space-y-6 p-6">

                    {{-- Date + Amount --}}
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <label
                                for="voucher_date"
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Voucher Date
                                <span class="text-rose-600">*</span>
                            </label>

                            <input
                                id="voucher_date"
                                type="date"
                                name="voucher_date"
                                value="{{ old('voucher_date', now()->toDateString()) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div>
                            <label
                                for="amount"
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Amount
                                <span class="text-rose-600">*</span>
                            </label>

                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                    ₹
                                </div>

                                <input
                                    id="amount"
                                    type="number"
                                    name="amount"
                                    value="{{ old('amount') }}"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    placeholder="0.00"
                                    class="w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>
                        </div>

                    </div>


                    {{-- Receipt / Payment Head --}}
                    @if($type !== 'transfer')

                        <div>
                            <label
                                for="finance_head_id"
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                {{ $type === 'receipt' ? 'Income Head' : 'Expense Head' }}
                                <span class="text-rose-600">*</span>
                            </label>

                            <select
                                id="finance_head_id"
                                name="finance_head_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    Select {{ $type === 'receipt' ? 'income' : 'expense' }} head
                                </option>

                                @foreach($heads as $head)
                                    <option
                                        value="{{ $head->id }}"
                                        @selected(
                                            (string) old('finance_head_id')
                                            === (string) $head->id
                                        )
                                    >
                                        {{ $head->name }}
                                        @if($head->category)
                                            — {{ $head->category }}
                                        @endif
                                    </option>
                                @endforeach

                            </select>
                        </div>

                    @endif


                    {{-- Main Account --}}
                    <div>
                        <label
                            for="finance_account_id"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            @if($type === 'receipt')
                                Deposit To
                            @elseif($type === 'payment')
                                Pay From
                            @else
                                Transfer From
                            @endif

                            <span class="text-rose-600">*</span>
                        </label>

                        <select
                            id="finance_account_id"
                            name="finance_account_id"
                            required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">
                                Select account
                            </option>

                            @foreach($accounts as $account)
                                <option
                                    value="{{ $account->id }}"
                                    @selected(
                                        (string) old('finance_account_id')
                                        === (string) $account->id
                                    )
                                >
                                    {{ $account->name }}
                                    ({{ ucfirst($account->account_type) }})
                                </option>
                            @endforeach

                        </select>
                    </div>


                    {{-- Transfer Destination --}}
                    @if($type === 'transfer')

                        <div>
                            <label
                                for="destination_account_id"
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Transfer To
                                <span class="text-rose-600">*</span>
                            </label>

                            <select
                                id="destination_account_id"
                                name="destination_account_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    Select destination account
                                </option>

                                @foreach($accounts as $account)
                                    <option
                                        value="{{ $account->id }}"
                                        @selected(
                                            (string) old('destination_account_id')
                                            === (string) $account->id
                                        )
                                    >
                                        {{ $account->name }}
                                        ({{ ucfirst($account->account_type) }})
                                    </option>
                                @endforeach

                            </select>
                        </div>

                    @endif


                    {{-- Payment Mode + Reference --}}
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                        <div>
                            <label
                                for="payment_mode"
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Payment Mode
                            </label>

                            <select
                                id="payment_mode"
                                name="payment_mode"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    Select payment mode
                                </option>

                                @foreach([
                                    'Cash',
                                    'UPI',
                                    'Card',
                                    'Bank Transfer',
                                    'Cheque',
                                    'Other'
                                ] as $mode)

                                    <option
                                        value="{{ $mode }}"
                                        @selected(old('payment_mode') === $mode)
                                    >
                                        {{ $mode }}
                                    </option>

                                @endforeach
                            </select>
                        </div>


                        <div>
                            <label
                                for="reference_no"
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Reference No.
                            </label>

                            <input
                                id="reference_no"
                                type="text"
                                name="reference_no"
                                value="{{ old('reference_no') }}"
                                maxlength="100"
                                placeholder="Cheque / UPI / bank reference"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                    </div>


                    {{-- Party --}}
                    <div>
                        <label
                            for="party_name"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            @if($type === 'receipt')
                                Received From
                            @elseif($type === 'payment')
                                Paid To
                            @else
                                Party / Description
                            @endif
                        </label>

                        <input
                            id="party_name"
                            type="text"
                            name="party_name"
                            value="{{ old('party_name') }}"
                            maxlength="200"
                            placeholder="
                                @if($type === 'receipt')
                                    Name of person / organisation
                                @elseif($type === 'payment')
                                    Supplier / vendor / payee
                                @else
                                    Optional
                                @endif
                            "
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>


                    {{-- Narration --}}
                    <div>
                        <label
                            for="narration"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Narration / Remarks
                        </label>

                        <textarea
                            id="narration"
                            name="narration"
                            rows="3"
                            placeholder="Enter details of this transaction"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >{{ old('narration') }}</textarea>
                    </div>


                    {{-- Draft Information --}}
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">

                        <p class="text-sm text-amber-800">
                            <strong>This voucher will initially be saved as Draft.</strong>
                            It will affect Finance totals only after it is posted.
                        </p>

                    </div>

                </div>


                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">

                    <a
                        href="{{ route('finance.vouchers.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                    >
                        Save as Draft
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app-layout>