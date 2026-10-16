<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">

        {{-- Page Header --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Hospital Expense Register
                </h1>

                <p class="mt-1 text-sm text-gray-600">
                    Clinical, non-clinical and shared hospital expenses
                </p>
            </div>

            @if (auth()->user()->hasPermission('hospital_expenses.create'))
                <a
                    href="{{ route('finance.hospital-expenses.create') }}"
                    class="inline-flex items-center justify-center rounded-lg
                           bg-indigo-600 px-4 py-2 text-sm font-semibold
                           text-white hover:bg-indigo-700"
                >
                    + New Expense
                </a>
            @endif

        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50
                        px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50
                        px-4 py-3 text-sm text-red-800">

                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif

        {{-- Expense Register --}}
        <div class="overflow-hidden rounded-xl border border-gray-200
                    bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-gray-200 text-sm">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-semibold
                                       uppercase tracking-wide text-gray-600">
                                Expense No.
                            </th>

                            <th class="px-3 py-3 text-left text-xs font-semibold
                                       uppercase tracking-wide text-gray-600">
                                Date
                            </th>

                            <th class="px-3 py-3 text-left text-xs font-semibold
                                       uppercase tracking-wide text-gray-600">
                                Description
                            </th>

                            <th class="px-3 py-3 text-left text-xs font-semibold
                                       uppercase tracking-wide text-gray-600">
                                Finance Head
                            </th>

                            <th class="px-3 py-3 text-left text-xs font-semibold
                                       uppercase tracking-wide text-gray-600">
                                Classification
                            </th>

                            <th class="px-3 py-3 text-left text-xs font-semibold
                                       uppercase tracking-wide text-gray-600">
                                Type
                            </th>

                            <th class="px-3 py-3 text-right text-xs font-semibold
                                       uppercase tracking-wide text-gray-600">
                                Amount
                            </th>

                            <th class="px-3 py-3 text-left text-xs font-semibold
                                       uppercase tracking-wide text-gray-600">
                                Status / Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        @forelse ($expenses as $expense)

                            <tr class="hover:bg-gray-50">

                                {{-- Expense Number --}}
                                <td class="whitespace-nowrap px-3 py-4">
                                    <span class="font-medium text-indigo-600">
                                        {{ $expense->expense_no }}
                                    </span>
                                </td>

                                {{-- Expense Date --}}
                                <td class="whitespace-nowrap px-3 py-4 text-gray-700">
                                    {{ $expense->expense_date
                                        ? \Illuminate\Support\Carbon::parse(
                                            $expense->expense_date
                                        )->format('d M Y')
                                        : '—' }}
                                </td>

                                {{-- Description / Supplier --}}
                                <td class="max-w-xs px-3 py-4">

                                    <div class="font-medium text-gray-900">
                                        {{ $expense->description ?: '—' }}
                                    </div>

                                    <div class="mt-1 text-xs text-gray-500">
                                        {{ $expense->supplier_name ?: '—' }}
                                    </div>

                                </td>

                                {{-- Finance Head --}}
                                <td class="px-3 py-4 text-gray-700">
                                    {{ $expense->financeHead?->name ?? '—' }}
                                </td>

                                {{-- Classification --}}
                                <td class="whitespace-nowrap px-3 py-4 text-gray-700">
                                    {{ ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $expense->expense_classification ?? ''
                                        )
                                    ) }}
                                </td>

                                {{-- Expense Type --}}
                                <td class="whitespace-nowrap px-3 py-4 text-gray-700">
                                    {{ ucfirst($expense->expense_type ?? '') }}
                                </td>

                                {{-- Total Amount --}}
                                <td class="whitespace-nowrap px-3 py-4 text-right
                                           font-semibold text-gray-900">
                                    ₹{{ number_format(
                                        (float) $expense->total_amount,
                                        2
                                    ) }}
                                </td>

                                {{-- Status and Workflow Actions --}}
                                <td class="px-3 py-4">

                                    <div class="flex min-w-32 flex-col items-start gap-2">

                                        {{-- Status --}}
                                        @php
                                            $statusStyle = match ($expense->status) {
                                                'draft' =>
                                                    'bg-gray-100 text-gray-700',

                                                'submitted' =>
                                                    'bg-amber-100 text-amber-800',

                                                'approved' =>
                                                    'bg-blue-100 text-blue-800',

                                                'posted' =>
                                                    'bg-green-100 text-green-800',

                                                'cancelled' =>
                                                    'bg-red-100 text-red-800',

                                                default =>
                                                    'bg-gray-100 text-gray-700',
                                            };
                                        @endphp

                                        <span class="inline-flex rounded-full px-2.5 py-1
                                                     text-xs font-semibold {{ $statusStyle }}">
                                            {{ ucfirst($expense->status) }}
                                        </span>

                                        {{-- Submit for Approval --}}
                                        @if (
                                            $expense->status === 'draft'
                                            && auth()->user()->hasPermission('hospital_expenses.submit')
                                        )
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'finance.hospital-expenses.submit',
                                                    $expense
                                                ) }}"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Submit this expense for approval?');"
                                                    class="rounded-md bg-indigo-600
                                                           px-3 py-1.5 text-xs
                                                           font-semibold text-white
                                                           hover:bg-indigo-700"
                                                >
                                                    Submit for Approval
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Approval / Return --}}
                                        @if (
                                            $expense->status === 'submitted'
                                            && auth()->user()->hasPermission('hospital_expenses.approve')
                                        )

                                            @if (
                                                $expense->created_by !== null
                                                && (int) $expense->created_by !== (int) auth()->id()
                                            )
                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'finance.hospital-expenses.approve',
                                                        $expense
                                                    ) }}"
                                                >
                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        onclick="return confirm('Approve this hospital expense?');"
                                                        class="rounded-md bg-green-600
                                                               px-3 py-1.5 text-xs
                                                               font-semibold text-white
                                                               hover:bg-green-700"
                                                    >
                                                        Approve
                                                    </button>
                                                </form>
                                            @endif

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'finance.hospital-expenses.return',
                                                    $expense
                                                ) }}"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Return this expense for correction?');"
                                                    class="rounded-md bg-amber-500
                                                           px-3 py-1.5 text-xs
                                                           font-semibold text-white
                                                           hover:bg-amber-600"
                                                >
                                                    Return for Correction
                                                </button>
                                            </form>

                                        @endif

                                        {{-- Post Approved Expense to GL --}}
                                        @if (
                                            $expense->status === 'approved'
                                            && $expense->expense_type === 'operating'
                                            && $expense->journal_entry_id === null
                                            && auth()->user()->hasPermission('hospital_expenses.post')
                                        )
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'finance.hospital-expenses.post',
                                                    $expense
                                                ) }}"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Post this expense to the General Ledger? This creates a permanent accounting journal.');"
                                                    class="rounded-md bg-emerald-600
                                                           px-3 py-1.5 text-xs
                                                           font-semibold text-white
                                                           hover:bg-emerald-700"
                                                >
                                                    Post to GL
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Supplier Payment --}}
                                        @if (
                                            $expense->status === 'posted'
                                            && $expense->expense_type === 'operating'
                                            && $expense->journal_entry_id !== null
                                            && auth()->user()->hasPermission('hospital_expenses.pay')
                                        )
                                            <a
                                                href="{{ route(
                                                    'finance.hospital-expenses.payments.create',
                                                    $expense
                                                ) }}"
                                                class="inline-block rounded-md
                                                       bg-blue-600 px-3 py-1.5
                                                       text-xs font-semibold text-white
                                                       hover:bg-blue-700"
                                            >
                                                Supplier Payment
                                            </a>
                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="8"
                                    class="px-4 py-10 text-center text-sm text-gray-500"
                                >
                                    No hospital expenses found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        {{-- Pagination --}}
        @if (method_exists($expenses, 'links'))
            <div>
                {{ $expenses->links() }}
            </div>
        @endif

    </div>
</x-app-layout>
