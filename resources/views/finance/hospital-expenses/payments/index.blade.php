<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Supplier Payments
            </h1>

            <p class="mt-1 text-sm text-gray-600">
                Manage outstanding supplier invoices, partial payments
                and payment history.
            </p>
        </div>

        {{-- Supplier Payments Table --}}
        <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                Expense No.
                            </th>

                            <th class="px-4 py-3 text-left">
                                Supplier
                            </th>

                            <th class="px-4 py-3 text-left">
                                Date
                            </th>

                            <th class="px-4 py-3 text-right">
                                Invoice
                            </th>

                            <th class="px-4 py-3 text-right">
                                Paid
                            </th>

                            <th class="px-4 py-3 text-right">
                                Outstanding
                            </th>

                            <th class="px-4 py-3 text-left">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        @forelse ($expenses as $expense)

                            @php
                                $outstanding = (float) $paymentService
                                    ->outstanding($expense);

                                $paid = max(
                                    0,
                                    (float) $expense->total_amount
                                        - $outstanding
                                );
                            @endphp

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $expense->expense_no }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $expense->supplier_name ?: '—' }}
                                </td>

                                <td class="px-4 py-3 text-gray-700">
                                    {{ $expense->expense_date?->format('d M Y') }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    ₹{{ number_format(
                                        (float) $expense->total_amount,
                                        2
                                    ) }}
                                </td>

                                <td class="px-4 py-3 text-right font-medium text-green-700">
                                    ₹{{ number_format($paid, 2) }}
                                </td>

                                <td class="px-4 py-3 text-right font-semibold">
                                    <span class="{{ $outstanding > 0
                                        ? 'text-amber-700'
                                        : 'text-green-700' }}">
                                        ₹{{ number_format($outstanding, 2) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <a
                                        href="{{ route(
                                            'finance.hospital-expenses.payments.create',
                                            $expense
                                        ) }}"
                                        class="inline-block rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
                                    >
                                        {{ $outstanding > 0
                                            ? 'Record Payment'
                                            : 'Payment History' }}
                                    </a>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="7"
                                    class="px-4 py-8 text-center text-gray-500"
                                >
                                    No posted supplier expense invoices found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div>
            {{ $expenses->links() }}
        </div>

    </div>
</x-app-layout>
