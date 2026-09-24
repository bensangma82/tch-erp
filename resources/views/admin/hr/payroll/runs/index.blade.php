<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    Payroll Runs
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Create, calculate, review and approve monthly employee payroll.
                </p>
            </div>

            <a
                href="{{ route('admin.hr.payroll.runs.create') }}"
                class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm"
                style="background-color: #0e7490 !important; color: #ffffff !important;"
            >
                Create Monthly Payroll
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

            {{-- Filters --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('admin.hr.payroll.runs.index') }}"
                    class="grid gap-4 md:grid-cols-4"
                >
                    <div>
                        <label
                            for="year"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Year
                        </label>

                        <input
                            type="number"
                            id="year"
                            name="year"
                            min="2000"
                            max="2100"
                            value="{{ request('year') }}"
                            placeholder="e.g. {{ now()->year }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                        >
                    </div>

                    <div>
                        <label
                            for="status"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                        >
                            <option value="">All statuses</option>
                            <option value="draft" @selected(request('status') === 'draft')>
                                Draft
                            </option>
                            <option value="calculated" @selected(request('status') === 'calculated')>
                                Calculated
                            </option>
                            <option value="approved" @selected(request('status') === 'approved')>
                                Approved
                            </option>
                            <option value="paid" @selected(request('status') === 'paid')>
                                Paid
                            </option>
                            <option value="locked" @selected(request('status') === 'locked')>
                                Locked
                            </option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>
                                Cancelled
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm"
                            style="background-color: #0e7490 !important; color: #ffffff !important;"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('admin.hr.payroll.runs.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Payroll Register --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-900">
                        Monthly Payroll Register
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Each payroll month is maintained as a separate controlled payroll run.
                    </p>
                </div>

                @if ($payrollRuns->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Payroll No.
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Payroll Month
                                    </th>

                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Employees
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Gross Earnings
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Deductions
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Net Payroll
                                    </th>

                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Status
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($payrollRuns as $payrollRun)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="font-semibold text-slate-900">
                                                {{ $payrollRun->payroll_no }}
                                            </div>

                                            @if ($payrollRun->description)
                                                <div class="mt-0.5 text-xs text-slate-500">
                                                    {{ $payrollRun->description }}
                                                </div>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                            {{ \Illuminate\Support\Carbon::create(
                                                $payrollRun->year,
                                                $payrollRun->month,
                                                1
                                            )->format('F Y') }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm font-semibold text-slate-700">
                                            {{ $payrollRun->employee_count }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">
                                            ₹{{ number_format((float) $payrollRun->total_earnings, 2) }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-red-700">
                                            ₹{{ number_format((float) $payrollRun->total_deductions, 2) }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-slate-900">
                                            ₹{{ number_format((float) $payrollRun->total_net_pay, 2) }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-center">
                                            @if ($payrollRun->status === 'draft')
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                    Draft
                                                </span>
                                            @elseif ($payrollRun->status === 'calculated')
                                                <span class="inline-flex rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-700">
                                                    Calculated
                                                </span>
                                            @elseif ($payrollRun->status === 'approved')
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                    Approved
                                                </span>
                                            @elseif ($payrollRun->status === 'paid')
                                                <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                    Paid
                                                </span>
                                            @elseif ($payrollRun->status === 'locked')
                                                <span class="inline-flex rounded-full bg-slate-800 px-2.5 py-1 text-xs font-semibold text-white">
                                                    Locked
                                                </span>
                                            @elseif ($payrollRun->status === 'cancelled')
                                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                    Cancelled
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                    {{ ucfirst($payrollRun->status) }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <a
                                                href="{{ route('admin.hr.payroll.runs.show', $payrollRun) }}"
                                                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                            >
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($payrollRuns->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4">
                            {{ $payrollRuns->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-12 text-center">
                        <h3 class="text-base font-semibold text-slate-900">
                            No payroll runs created
                        </h3>

                        <p class="mt-2 text-sm text-slate-500">
                            Create a monthly payroll run to begin payroll processing.
                        </p>

                        <div class="mt-5">
                            <a
                                href="{{ route('admin.hr.payroll.runs.create') }}"
                                class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm"
                                style="background-color: #0e7490 !important; color: #ffffff !important;"
                            >
                                Create First Payroll
                            </a>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>