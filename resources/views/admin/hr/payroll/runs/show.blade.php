<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold text-slate-900">
                        {{ $payrollRun->payroll_no }}
                    </h2>

                    @if ($payrollRun->status === 'draft')
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                            Draft
                        </span>
                    @elseif ($payrollRun->status === 'calculated')
                        <span class="rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-700">
                            Calculated
                        </span>
                    @elseif ($payrollRun->status === 'approved')
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                            Approved
                        </span>
                    @elseif ($payrollRun->status === 'paid')
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                            Paid
                        </span>
                    @elseif ($payrollRun->status === 'locked')
                        <span class="rounded-full bg-slate-800 px-2.5 py-1 text-xs font-semibold text-white">
                            Locked
                        </span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                            {{ ucfirst($payrollRun->status) }}
                        </span>
                    @endif
                </div>

                <p class="mt-1 text-sm text-slate-500">
                    {{ \Illuminate\Support\Carbon::create(
                        $payrollRun->year,
                        $payrollRun->month,
                        1
                    )->format('F Y') }}
                    Payroll
                </p>
            </div>

            <a
                href="{{ route('admin.hr.payroll.runs.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Back to Payroll Runs
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold">Please correct the following:</div>
                    <ul class="mt-1 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Payroll Information --}}
            <div class="grid gap-4 md:grid-cols-4">

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Payroll Period
                    </div>

                    <div class="mt-2 font-semibold text-slate-900">
                        {{ $payrollRun->period_start?->format('d M Y') }}
                        –
                        {{ $payrollRun->period_end?->format('d M Y') }}
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Employees
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $payrollRun->employee_count }}
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Gross Payroll
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        ₹{{ number_format((float) $payrollRun->total_earnings, 2) }}
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Net Payroll
                    </div>

                    <div class="mt-2 text-2xl font-bold text-emerald-700">
                        ₹{{ number_format((float) $payrollRun->total_net_pay, 2) }}
                    </div>
                </div>

            </div>

            {{-- Payroll Controls --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>
                        <h3 class="font-semibold text-slate-900">
                            Payroll Processing
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Calculate salaries from the active employee salary structures before approval.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">

                        @if ($payrollRun->canBeModified())
                            <form
                                method="POST"
                                action="{{ route('admin.hr.payroll.runs.calculate', $payrollRun) }}"
                                onsubmit="return confirm('Calculate payroll for this month? Existing draft calculations will be replaced.');"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm"
                                    style="background-color: #0e7490 !important; color: #ffffff !important;"
                                >
                                    {{ $payrollRun->status === 'calculated'
                                        ? 'Recalculate Payroll'
                                        : 'Calculate Payroll' }}
                                </button>
                            </form>
                        @endif

                        @if ($payrollRun->canBeApproved())
                            <form
                                method="POST"
                                action="{{ route('admin.hr.payroll.runs.approve', $payrollRun) }}"
                                onsubmit="return confirm('Approve this payroll? Once approved it can no longer be recalculated.');"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800"
                                >
                                    Approve Payroll
                                </button>
                            </form>
                        @endif

                        @if ($payrollRun->canBeLocked())
                            <form
                                method="POST"
                                action="{{ route('admin.hr.payroll.runs.lock', $payrollRun) }}"
                                onsubmit="return confirm('Lock this payroll permanently? Once locked, it will be treated as a final historical payroll record.');"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900"
                                >
                                    Lock Payroll
                                </button>
                            </form>
                        @endif

                    </div>
                </div>
            </div>
                           {{-- Payroll to Finance --}}
@if ($payrollRun->isLocked())
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>
                    <h3 class="font-semibold text-slate-900">
                        Finance Posting
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Transfer the final payroll payment to Finance as Salary &amp; Wages payment voucher(s).
                    </p>
                </div>

                @if ($payrollRun->financeVouchers->isEmpty())
                    <form
                        method="POST"
                        action="{{ route('admin.hr.payroll.runs.finance-vouchers', $payrollRun) }}"
                        onsubmit="return confirm('Create Finance voucher(s) for this locked payroll? The vouchers will be created in Draft status for Finance review.');"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm"
                            style="background-color: #0e7490 !important; color: #ffffff !important;"
                        >
                            Create Finance Voucher(s)
                        </button>
                    </form>
                @endif

            </div>
        </div>

        @if ($payrollRun->financeVouchers->isEmpty())

            <div class="px-5 py-5">
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                    <div class="text-sm font-semibold text-amber-800">
                        Finance voucher not yet created
                    </div>

                    <p class="mt-1 text-sm text-amber-700">
                        The payroll is locked, but it has not yet been transferred to the Finance voucher register.
                    </p>

                    <p class="mt-1 text-xs text-amber-700">
                        Voucher(s) will be grouped by the Finance Account used for employee salary payments.
                    </p>
                </div>
            </div>

        @else

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Voucher No.
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Finance Head
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Payment Account
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Amount
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">

                        @foreach ($payrollRun->financeVouchers as $voucher)
                            <tr>
                                                <td class="whitespace-nowrap px-4 py-3 text-sm">
    <div class="font-semibold text-slate-900">
        {{ $voucher->voucher_no }}
    </div>

    <a
        href="{{ route('finance.vouchers.show', $voucher) }}"
        class="mt-1 inline-block text-xs font-semibold text-cyan-700 hover:text-cyan-900 hover:underline"
    >
        View Finance Voucher
    </a>
</td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ \Illuminate\Support\Carbon::parse($voucher->voucher_date)->format('d M Y') }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if ($voucher->financeHead)
                                        <div class="font-medium text-slate-900">
                                            {{ $voucher->financeHead->name }}
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            {{ $voucher->financeHead->code }}
                                        </div>
                                    @else
                                        <span class="text-slate-400">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    @if ($voucher->financeAccount)
                                        <div class="font-medium text-slate-900">
                                            {{ $voucher->financeAccount->name }}
                                        </div>

                                        <div class="text-xs text-slate-500">
                                            {{ $voucher->financeAccount->code }}
                                        </div>
                                    @else
                                        <span class="text-red-600">
                                            Account unavailable
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                    ₹{{ number_format((float) $voucher->amount, 2) }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @php
                                        $voucherStatusClass = match ($voucher->status) {
                                            'posted' =>
                                                'bg-emerald-100 text-emerald-800',
                                            'cancelled' =>
                                                'bg-red-100 text-red-800',
                                            default =>
                                                'bg-amber-100 text-amber-800',
                                        };
                                    @endphp

                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $voucherStatusClass }}">
                                        {{ ucfirst($voucher->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach

                    </tbody>

                    <tfoot class="bg-slate-50">
                        <tr>
                            <td
                                colspan="4"
                                class="px-4 py-3 text-right text-sm font-semibold text-slate-700"
                            >
                                Finance Voucher Total
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-slate-900">
                                ₹{{ number_format(
                                    (float) $payrollRun->financeVouchers->sum('amount'),
                                    2
                                ) }}
                            </td>

                            <td></td>
                        </tr>
                    </tfoot>

                </table>
            </div>

            <div class="border-t border-slate-200 bg-slate-50 px-5 py-3">
                              @if ($payrollRun->financeVouchers->contains('status', 'cancelled'))

    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
        <p class="text-sm font-semibold text-red-800">
            Payroll Finance voucher cancelled
        </p>

        <p class="mt-1 text-sm text-red-700">
            One or more Finance vouchers linked to this payroll have been cancelled.
            The cancelled voucher remains in the audit history and is no longer included
            in Finance totals.
        </p>

        <p class="mt-1 text-xs text-red-700">
            A replacement payroll voucher will not be created automatically.
            Any correction must be reviewed through the Finance module.
        </p>
    </div>

@elseif ($payrollRun->financeVouchers->contains('status', 'draft'))

    <p class="text-sm text-amber-700">
        Draft Finance voucher(s) are awaiting review and posting in the Finance module.
    </p>

@elseif ($payrollRun->financeVouchers->every(
    fn ($voucher) => $voucher->status === 'posted'
))

    <p class="text-sm font-medium text-emerald-700">
        All payroll Finance vouchers have been posted.
    </p>

@else

    <p class="text-sm text-slate-600">
        Review the voucher status in the Finance module.
    </p>

@endif
            </div>

        @endif

    </div>
@endif
            {{-- Payroll Summary --}}
            <div class="grid gap-4 md:grid-cols-3">

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-slate-500">
                        Gross Earnings
                    </div>

                    <div class="mt-2 text-xl font-bold text-slate-900">
                        ₹{{ number_format((float) $payrollRun->total_earnings, 2) }}
                    </div>
                </div>

                <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
                    <div class="text-sm font-medium text-red-600">
                        Total Deductions
                    </div>

                    <div class="mt-2 text-xl font-bold text-red-700">
                        ₹{{ number_format((float) $payrollRun->total_deductions, 2) }}
                    </div>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="text-sm font-medium text-emerald-700">
                        Net Payable
                    </div>

                    <div class="mt-2 text-xl font-bold text-emerald-800">
                        ₹{{ number_format((float) $payrollRun->total_net_pay, 2) }}
                    </div>
                </div>

            </div>

            {{-- Employee Payroll Entries --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-900">
                        Employee Payroll
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Salary values shown here are payroll snapshots for this month.
                    </p>
                </div>

                @if ($payrollRun->entries->count())

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Employee
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Department
                                    </th>

                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Payable Days
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Gross
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Deductions
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Net Pay
                                    </th>

                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Status
                                    </th>

                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">

                                @foreach ($payrollRun->entries as $entry)
                                    <tr class="hover:bg-slate-50">

                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-slate-900">
                                                {{ $entry->employee_name }}
                                            </div>

                                            <div class="mt-0.5 text-xs text-slate-500">
                                                {{ $entry->employee_code }}

                                                @if ($entry->designation)
                                                    · {{ $entry->designation }}
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ $entry->department_name ?: '—' }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-slate-700">
                                            {{ number_format((float) $entry->payable_days, 2) }}
                                            /
                                            {{ number_format((float) $entry->calendar_days, 0) }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">
                                            ₹{{ number_format((float) $entry->gross_earnings, 2) }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-red-700">
                                            ₹{{ number_format((float) $entry->total_deductions, 2) }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-emerald-700">
                                            ₹{{ number_format((float) $entry->net_pay, 2) }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-center">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                {{ ucfirst($entry->status) }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                @if (in_array($payrollRun->status, ['approved', 'paid', 'locked'], true))
                                                    <a
                                                        href="{{ route(
                                                            'admin.hr.payroll.runs.payslip',
                                                            [$payrollRun, $entry]
                                                        ) }}"
                                                        class="inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-semibold shadow-sm"
                                                        style="background-color: #0e7490 !important; color: #ffffff !important;"
                                                    >
                                                        View Payslip
                                                    </a>
                                                @endif

                                                @if ($payrollRun->status === 'approved' && $entry->status === 'approved')
                                                    <details class="w-full min-w-[250px] rounded-lg border border-emerald-200 bg-emerald-50 text-left">
                                                        <summary class="cursor-pointer px-3 py-2 text-xs font-semibold text-emerald-800">
                                                            Record Payment
                                                        </summary>

                                                        <form
                                                            method="POST"
                                                            action="{{ route(
                                                                'admin.hr.payroll.runs.payment',
                                                                [$payrollRun, $entry]
                                                            ) }}"
                                                            class="space-y-3 border-t border-emerald-200 p-3"
                                                            onsubmit="return confirm('Record salary payment for {{ addslashes($entry->employee_name) }}? This payment cannot be recorded twice.');"
                                                        >
                                                            @csrf

                                                            <div>
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    Payment Date
                                                                </label>
                                                                <input
                                                                    type="date"
                                                                    name="payment_date"
                                                                    value="{{ old('payment_date', now()->toDateString()) }}"
                                                                    required
                                                                    class="mt-1 w-full rounded-lg border-slate-300 text-xs"
                                                                >
                                                            </div>

                                                            <div>
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    Payment Mode
                                                                </label>
                                                                <select
                                                                    name="payment_mode"
                                                                    required
                                                                    class="mt-1 w-full rounded-lg border-slate-300 text-xs"
                                                                >
                                                                    <option value="">Select mode</option>
                                                                    <option value="bank_transfer">Bank Transfer</option>
                                                                    <option value="cash">Cash</option>
                                                                    <option value="cheque">Cheque</option>
                                                                    <option value="other">Other</option>
                                                                </select>
                                                            </div>
                                                                       <div>
    <label class="block text-xs font-semibold text-slate-600">
        Payment Account
    </label>

    <select
        name="finance_account_id"
        required
        class="mt-1 w-full rounded-lg border-slate-300 text-xs"
    >
        <option value="">Select account</option>

        @foreach ($financeAccounts as $financeAccount)
            <option
                value="{{ $financeAccount->id }}"
                @selected(
                    (string) old('finance_account_id') ===
                    (string) $financeAccount->id
                )
            >
                {{ $financeAccount->name }}
                ({{ strtoupper($financeAccount->account_type) }})
            </option>
        @endforeach
    </select>

    <p class="mt-1 text-[11px] text-slate-500">
        Account from which this salary payment is made.
    </p>
</div>
                                                            <div>
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    Reference No.
                                                                </label>
                                                                <input
                                                                    type="text"
                                                                    name="payment_reference"
                                                                    maxlength="150"
                                                                    placeholder="Transaction / cheque reference"
                                                                    class="mt-1 w-full rounded-lg border-slate-300 text-xs"
                                                                >
                                                                <p class="mt-1 text-[11px] text-slate-500">
                                                                    Required for bank transfer or cheque.
                                                                </p>
                                                            </div>

                                                            <div>
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    Remarks
                                                                </label>
                                                                <input
                                                                    type="text"
                                                                    name="remarks"
                                                                    maxlength="1000"
                                                                    placeholder="Optional"
                                                                    class="mt-1 w-full rounded-lg border-slate-300 text-xs"
                                                                >
                                                            </div>

                                                            <button
                                                                type="submit"
                                                                class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800"
                                                            >
                                                                Confirm Payment
                                                            </button>
                                                        </form>
                                                    </details>
                                                @elseif ($entry->status === 'paid')
                                                    <div class="min-w-[220px] rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-left text-xs">
                                                        <div class="font-semibold text-blue-800">
                                                            Payment Recorded
                                                        </div>
                                                        <div class="mt-1 text-slate-600">
                                                            {{ $entry->payment_date?->format('d M Y') ?: '—' }}
                                                            ·
                                                            {{ match ($entry->payment_mode) {
                                                                'bank_transfer' => 'Bank Transfer',
                                                                'cash' => 'Cash',
                                                                'cheque' => 'Cheque',
                                                                'other' => 'Other',
                                                                default => ucfirst(str_replace('_', ' ', (string) $entry->payment_mode)),
                                                            } }}
                                                        </div>

                                                        @if ($entry->payment_reference)
                                                            <div class="mt-1 text-slate-500">
                                                                Ref: {{ $entry->payment_reference }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @elseif (!in_array($payrollRun->status, ['approved', 'paid', 'locked'], true))
                                                    <span class="text-xs text-slate-400">—</span>
                                                @endif
                                            </div>
                                        </td>

                                    </tr>

                                    {{-- Component Breakdown + Payroll Adjustments --}}
                                    @php
                                        $employeeAdjustments = $payrollRun->adjustments
                                            ->where('employee_id', $entry->employee_id)
                                            ->where('is_active', true);
                                    @endphp

                                    <tr class="bg-slate-50/70">
                                        <td colspan="8" class="px-4 py-4">

                                            <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs">
                                                @foreach ($entry->items->sortBy('sort_order') as $item)
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="font-medium text-slate-500">
                                                            {{ $item->component_code }}:
                                                        </span>

                                                        <span
                                                            class="font-semibold {{ $item->type === 'deduction'
                                                                ? 'text-red-700'
                                                                : 'text-slate-800' }}"
                                                        >
                                                            ₹{{ number_format((float) $item->amount, 2) }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if ($employeeAdjustments->count())
                                                <div class="mt-4 border-t border-slate-200 pt-3">
                                                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        Payroll Adjustments
                                                    </div>

                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach ($employeeAdjustments as $adjustment)
                                                            <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-sm">
                                                                <span class="font-semibold
                                                                    {{ $adjustment->type === 'earning'
                                                                        ? 'text-emerald-700'
                                                                        : 'text-red-700' }}">
                                                                    {{ $adjustment->code }}
                                                                </span>

                                                                <span class="text-slate-700">
                                                                    {{ $adjustment->name }}
                                                                </span>

                                                                @if ($adjustment->type === 'lop')
                                                                    <span class="font-semibold text-red-700">
                                                                        {{ number_format((float) $adjustment->lop_days, 2) }} day(s)
                                                                        · ₹{{ number_format((float) $adjustment->amount, 2) }}
                                                                    </span>
                                                                @else
                                                                    <span class="font-semibold
                                                                        {{ $adjustment->type === 'earning'
                                                                            ? 'text-emerald-700'
                                                                            : 'text-red-700' }}">
                                                                        {{ $adjustment->type === 'earning' ? '+' : '-' }}
                                                                        ₹{{ number_format((float) $adjustment->amount, 2) }}
                                                                    </span>
                                                                @endif

                                                                @if ($payrollRun->canBeModified())
                                                                    <form
                                                                        method="POST"
                                                                        action="{{ route(
                                                                            'admin.hr.payroll.runs.adjustments.destroy',
                                                                            [$payrollRun, $adjustment]
                                                                        ) }}"
                                                                        onsubmit="return confirm('Delete this payroll adjustment? Recalculate payroll afterwards.');"
                                                                    >
                                                                        @csrf
                                                                        @method('DELETE')

                                                                        <button
                                                                            type="submit"
                                                                            class="font-semibold text-red-600 hover:text-red-800"
                                                                        >
                                                                            Delete
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($payrollRun->canBeModified())
                                                <details class="mt-4 rounded-lg border border-cyan-200 bg-cyan-50/50">
                                                    <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-cyan-800">
                                                        + Add Payroll Adjustment
                                                    </summary>

                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'admin.hr.payroll.runs.adjustments.store',
                                                            [$payrollRun, $entry]
                                                        ) }}"
                                                        class="border-t border-cyan-200 p-4"
                                                    >
                                                        @csrf

                                                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                                                            <div>
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    Type
                                                                </label>
                                                                <select
                                                                    name="type"
                                                                    required
                                                                    class="payroll-adjustment-type mt-1 w-full rounded-lg border-slate-300 text-sm"
                                                                >
                                                                    <option value="earning">Additional Earning</option>
                                                                    <option value="deduction">Additional Deduction</option>
                                                                    <option value="lop">Loss of Pay (LOP)</option>
                                                                </select>
                                                            </div>

                                                            <div>
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    Code
                                                                </label>
                                                                <input
                                                                    type="text"
                                                                    name="code"
                                                                    required
                                                                    maxlength="50"
                                                                    placeholder="BONUS"
                                                                    class="mt-1 w-full rounded-lg border-slate-300 text-sm uppercase"
                                                                >
                                                            </div>

                                                            <div>
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    Description
                                                                </label>
                                                                <input
                                                                    type="text"
                                                                    name="name"
                                                                    required
                                                                    maxlength="150"
                                                                    placeholder="Festival Bonus"
                                                                    class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                                                                >
                                                            </div>

                                                            <div class="payroll-adjustment-amount-wrap">
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    Amount (₹)
                                                                </label>
                                                                <input
                                                                    type="number"
                                                                    name="amount"
                                                                    min="0"
                                                                    step="0.01"
                                                                    placeholder="0.00"
                                                                    class="payroll-adjustment-amount mt-1 w-full rounded-lg border-slate-300 text-sm"
                                                                >
                                                                <p class="payroll-lop-auto-note mt-1 hidden text-[11px] leading-4 text-cyan-700">
                                                                    Automatic for LOP: Monthly Salary ÷ 30 × LOP Days
                                                                </p>
                                                            </div>

                                                            <div>
                                                                <label class="block text-xs font-semibold text-slate-600">
                                                                    LOP Days
                                                                </label>
                                                                <input
                                                                    type="number"
                                                                    name="lop_days"
                                                                    min="0"
                                                                    max="{{ (float) $entry->calendar_days }}"
                                                                    step="0.01"
                                                                    placeholder="0"
                                                                    class="payroll-adjustment-lop-days mt-1 w-full rounded-lg border-slate-300 text-sm"
                                                                >
                                                            </div>

                                                            <div class="flex items-end">
                                                                <button
                                                                    type="submit"
                                                                    class="inline-flex w-full items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm"
                                                                    style="background-color: #0e7490 !important; color: #ffffff !important;"
                                                                >
                                                                    Save Adjustment
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div class="mt-4">
                                                            <label class="block text-xs font-semibold text-slate-600">
                                                                Remarks
                                                            </label>
                                                            <input
                                                                type="text"
                                                                name="remarks"
                                                                maxlength="1000"
                                                                placeholder="Optional note"
                                                                class="mt-1 w-full rounded-lg border-slate-300 text-sm"
                                                            >
                                                        </div>

                                                        <p class="mt-3 text-xs text-slate-500">
                                                            For LOP, enter the number of LOP days only. The deduction is calculated automatically using Monthly Salary ÷ 30 × LOP Days.
                                                            After adding or deleting an adjustment, click Recalculate Payroll.
                                                        </p>
                                                    </form>
                                                </details>
                                            @endif

                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>

                @else

                    <div class="px-6 py-12 text-center">

                        <h3 class="text-base font-semibold text-slate-900">
                            Payroll has not been calculated
                        </h3>

                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                            No employee payroll entries exist for this period yet.
                            Calculate the payroll to generate salary entries from active salary structures.
                        </p>

                        @if ($payrollRun->canBeModified())
                            <form
                                method="POST"
                                action="{{ route('admin.hr.payroll.runs.calculate', $payrollRun) }}"
                                class="mt-5"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-semibold shadow-sm"
                                    style="background-color: #0e7490 !important; color: #ffffff !important;"
                                >
                                    Calculate Payroll
                                </button>
                            </form>
                        @endif

                    </div>

                @endif
            </div>

            {{-- Remarks --}}
            @if ($payrollRun->remarks)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-semibold text-slate-900">
                        Remarks
                    </div>

                    <div class="mt-2 whitespace-pre-line text-sm text-slate-600">
                        {{ $payrollRun->remarks }}
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.payroll-adjustment-type').forEach(function (typeSelect) {
                const form = typeSelect.closest('form');

                if (!form) {
                    return;
                }

                const amountInput = form.querySelector('.payroll-adjustment-amount');
                const lopDaysInput = form.querySelector('.payroll-adjustment-lop-days');
                const lopAutoNote = form.querySelector('.payroll-lop-auto-note');

                const syncAdjustmentFields = function () {
                    const isLop = typeSelect.value === 'lop';

                    if (amountInput) {
                        amountInput.disabled = isLop;
                        amountInput.required = !isLop;
                        amountInput.value = isLop ? '' : amountInput.value;
                        amountInput.placeholder = isLop
                            ? 'Calculated automatically'
                            : '0.00';

                        amountInput.classList.toggle('bg-slate-100', isLop);
                        amountInput.classList.toggle('text-slate-500', isLop);
                    }

                    if (lopDaysInput) {
                        lopDaysInput.required = isLop;
                        lopDaysInput.disabled = !isLop;

                        if (!isLop) {
                            lopDaysInput.value = '';
                        }

                        lopDaysInput.classList.toggle('bg-slate-100', !isLop);
                        lopDaysInput.classList.toggle('text-slate-500', !isLop);
                    }

                    if (lopAutoNote) {
                        lopAutoNote.classList.toggle('hidden', !isLop);
                    }
                };

                typeSelect.addEventListener('change', syncAdjustmentFields);
                syncAdjustmentFields();
            });
        });
    </script>

</x-app-layout>