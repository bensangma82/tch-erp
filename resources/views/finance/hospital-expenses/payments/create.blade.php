<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6">

        <div>
            <a href="{{ route('finance.hospital-expenses.index') }}"
               class="text-sm text-indigo-600 hover:underline">
                ← Back to Hospital Expenses
            </a>

            <h1 class="mt-3 text-2xl font-bold text-gray-900">
                Supplier Payment
            </h1>

            <p class="mt-1 text-sm text-gray-600">
                Expense: {{ $hospitalExpense->expense_no }}
            </p>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-4 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Total Invoice</p>
                <p class="mt-2 text-2xl font-bold">
                    ₹{{ number_format((float) $hospitalExpense->total_amount, 2) }}
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Amount Paid</p>
                <p class="mt-2 text-2xl font-bold text-green-700">
                    ₹{{ number_format(
                        max(0, (float) $hospitalExpense->total_amount - (float) $outstanding),
                        2
                    ) }}
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Outstanding Payable</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">
                    ₹{{ number_format((float) $outstanding, 2) }}
                </p>
            </div>

        </div>

        <div class="rounded-xl border bg-white p-6 shadow-sm">

            <h2 class="text-lg font-semibold text-gray-900">
                Record Supplier Payment
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Supplier:
                {{ $hospitalExpense->supplier_name ?: 'Not specified' }}
            </p>

            @if ((float) $outstanding > 0 && $accounts->isNotEmpty())

                {{--
                    Payment submission remains disabled until
                    payment-service and Tally duplicate-export
                    safeguards are verified.
                --}}
                <form method="POST"
                      action="{{ route(
                          'finance.hospital-expenses.payments.store',
                          $hospitalExpense
                      ) }}"
                      class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">

                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Payment Date
                        </label>

                        <input type="date"
                               name="payment_date"
                               value="{{ old('payment_date', now()->toDateString()) }}"
                               required
                               class="mt-1 w-full rounded-lg border-gray-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Payment Account
                        </label>

                        <select name="finance_account_id"
                                required
                                class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select Cash / Bank Account</option>

                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}"
                                    @selected(old('finance_account_id') == $account->id)>
                                    {{ $account->name }} ({{ $account->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Amount (₹)
                        </label>

                        <input type="number"
                               name="amount"
                               min="0.01"
                               max="{{ $outstanding }}"
                               step="0.01"
                               value="{{ old('amount', $outstanding) }}"
                               required
                               class="mt-1 w-full rounded-lg border-gray-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Payment Mode
                        </label>

                        <select name="payment_mode"
                                required
                                class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select Payment Mode</option>
                            <option value="cash" @selected(old('payment_mode') === 'cash')>
                                Cash
                            </option>
                            <option value="bank_transfer" @selected(old('payment_mode') === 'bank_transfer')>
                                Bank Transfer
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Reference Number
                        </label>

                        <input type="text"
                               name="reference_no"
                               value="{{ old('reference_no') }}"
                               maxlength="100"
                               class="mt-1 w-full rounded-lg border-gray-300">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Remarks
                        </label>

                        <input type="text"
                               name="remarks"
                               value="{{ old('remarks') }}"
                               maxlength="1000"
                               class="mt-1 w-full rounded-lg border-gray-300">
                    </div>

                    <div class="md:col-span-2">
                       <div class="md:col-span-2">
    <button
        type="submit"
        onclick="return confirm('Record this supplier payment and post the GL journal?');"
        class="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700"
    >
        Record Payment
    </button>
</div>

                    </div>

                </form>

            @elseif ((float) $outstanding <= 0)

                <p class="mt-4 font-semibold text-green-700">
                    This supplier invoice is fully paid.
                </p>

            @else

                <p class="mt-4 text-amber-700">
                    No eligible cash or bank accounts are available.
                </p>

            @endif

        </div>

        <div class="rounded-xl border bg-white p-6 shadow-sm">

            <h2 class="text-lg font-semibold text-gray-900">
                Payment History
            </h2>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Voucher</th>
                            <th class="px-4 py-3 text-left">Mode</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="px-4 py-3">
                                    {{ $payment->payment_date?->format('d M Y') }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $payment->financeVoucher?->voucher_no ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    ₹{{ number_format((float) $payment->amount, 2) }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ ucfirst($payment->status) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-4 py-8 text-center text-gray-500">
                                    No supplier payments recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</x-app-layout>
