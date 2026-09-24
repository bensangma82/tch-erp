<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    Edit Employee Salary Structure
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $salaryStructure->employee?->employee_code }}
                    —
                    {{ $salaryStructure->employee?->full_name }}
                </p>
            </div>

            <div>
                @if ($salaryStructure->status === 'draft')
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        Draft
                    </span>
                @elseif ($salaryStructure->status === 'active')
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                        Active
                    </span>
                @elseif ($salaryStructure->status === 'superseded')
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        Superseded
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                        {{ ucfirst($salaryStructure->status) }}
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">

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

            {{-- Draft warning --}}
            @if ($salaryStructure->isDraft())
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
                    <div class="font-semibold text-amber-900">
                        Draft Salary Structure
                    </div>

                    <p class="mt-1 text-sm text-amber-800">
                        You can modify this structure until it is activated.
                        Review the earnings, deductions and effective date before activation.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.hr.payroll.salary-structures.update', $salaryStructure) }}"
                >
                    @csrf
                    @method('PUT')

                    @include(
                        'admin.hr.payroll.salary-structures._form',
                        [
                            'salaryStructure' => $salaryStructure,
                            'employees' => $employees,
                            'components' => $components,
                        ]
                    )
                </form>

                {{-- Activate separately so saving and activation are distinct actions --}}
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-emerald-900">
                                Activate Salary Structure
                            </h3>

                            <p class="mt-1 max-w-3xl text-sm text-emerald-800">
                                Activate only after checking the salary components.
                                Activation makes this the employee's current salary structure
                                from the effective date.
                            </p>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('admin.hr.payroll.salary-structures.activate', $salaryStructure) }}"
                            onsubmit="return confirm('Activate this salary structure? Please ensure all earnings, deductions and the effective date are correct.');"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="inline-flex whitespace-nowrap items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm"
                                style="background-color: #047857 !important; color: #ffffff !important;"
                            >
                                Activate Salary Structure
                            </button>
                        </form>
                    </div>
                </div>
            @else
                {{-- Historical structures are deliberately read-only --}}
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">
                                Salary Structure Details
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                This salary structure is no longer editable because its status is
                                {{ ucfirst($salaryStructure->status) }}.
                            </p>
                        </div>

                        <a
                            href="{{ route('admin.hr.payroll.salary-structures.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Back to Register
                        </a>
                    </div>

                    <div class="mt-6 grid gap-5 border-t border-slate-200 pt-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Employee
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $salaryStructure->employee?->full_name ?? '—' }}
                            </div>

                            <div class="mt-0.5 text-xs text-slate-500">
                                {{ $salaryStructure->employee?->employee_code ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Effective From
                            </div>

                            <div class="mt-1 font-medium text-slate-900">
                                {{ $salaryStructure->effective_from?->format('d M Y') ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Effective To
                            </div>

                            <div class="mt-1 font-medium text-slate-900">
                                {{ $salaryStructure->effective_to?->format('d M Y') ?? 'Open-ended' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Monthly Gross
                            </div>

                            <div class="mt-1 text-lg font-bold text-slate-900">
                                ₹{{ number_format((float) $salaryStructure->monthly_salary, 2) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Component
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Type
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Calculation
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Value
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($salaryStructure->items->where('is_active', true) as $salaryItem)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-slate-900">
                                                {{ $salaryItem->salaryComponent?->name ?? 'Unknown Component' }}
                                            </div>

                                            <div class="mt-0.5 text-xs text-slate-500">
                                                {{ $salaryItem->salaryComponent?->code ?? '—' }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-sm">
                                            @if ($salaryItem->salaryComponent?->type === 'earning')
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                    Earning
                                                </span>
                                            @else
                                                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                    Deduction
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            @if ($salaryItem->calculation_type === 'percentage')
                                                Percentage

                                                @if ($salaryItem->percentageOfComponent)
                                                    <div class="mt-0.5 text-xs text-slate-500">
                                                        Based on {{ $salaryItem->percentageOfComponent->code }}
                                                    </div>
                                                @endif
                                            @else
                                                Fixed Amount
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            @if ($salaryItem->calculation_type === 'percentage')
                                                {{ number_format((float) $salaryItem->percentage, 2) }}%
                                            @else
                                                ₹{{ number_format((float) $salaryItem->amount, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="4"
                                            class="px-6 py-8 text-center text-sm text-slate-500"
                                        >
                                            No salary components recorded.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($salaryStructure->remarks)
                        <div class="mt-6 rounded-lg bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Remarks
                            </div>

                            <div class="mt-1 text-sm text-slate-700">
                                {{ $salaryStructure->remarks }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>