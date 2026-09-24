@php
    $editing = isset($salaryStructure)
        && $salaryStructure instanceof \App\Models\EmployeeSalaryStructure
        && $salaryStructure->exists;

    $selectedEmployee = old(
        'employee_id',
        $editing ? $salaryStructure->employee_id : ($selectedEmployeeId ?? '')
    );

    $effectiveFrom = old(
        'effective_from',
        $editing && $salaryStructure->effective_from
            ? $salaryStructure->effective_from->format('Y-m-d')
            : now()->format('Y-m-d')
    );

    $effectiveTo = old(
        'effective_to',
        $editing && $salaryStructure->effective_to
            ? $salaryStructure->effective_to->format('Y-m-d')
            : ''
    );

    $remarks = old(
        'remarks',
        $editing ? $salaryStructure->remarks : ''
    );

    $existingItems = $editing
        ? $salaryStructure->items->keyBy('salary_component_id')
        : collect();

    $oldItems = old('items');
@endphp

{{-- Validation Errors --}}
@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-4">
        <div class="font-semibold text-red-800">
            Please correct the following:
        </div>

        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-6">

    {{-- Employee / Effective Period --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5">
            <h3 class="font-semibold text-slate-900">
                Employee & Effective Period
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Select the employee and the date from which this salary structure becomes applicable.
            </p>
        </div>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-4">

            <div class="lg:col-span-2">
                <label
                    for="employee_id"
                    class="mb-1 block text-sm font-medium text-slate-700"
                >
                    Employee
                    <span class="text-red-600">*</span>
                </label>

                <select
                    id="employee_id"
                    name="employee_id"
                    required
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                >
                    <option value="">Select employee</option>

                    @foreach ($employees as $employee)
                        <option
                            value="{{ $employee->id }}"
                            @selected((string) $selectedEmployee === (string) $employee->id)
                        >
                            {{ $employee->employee_code }}
                            —
                            {{ $employee->full_name }}

                            @if ($employee->designation)
                                — {{ $employee->designation }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="effective_from"
                    class="mb-1 block text-sm font-medium text-slate-700"
                >
                    Effective From
                    <span class="text-red-600">*</span>
                </label>

                <input
                    type="date"
                    id="effective_from"
                    name="effective_from"
                    value="{{ $effectiveFrom }}"
                    required
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                >
            </div>

            <div>
                <label
                    for="effective_to"
                    class="mb-1 block text-sm font-medium text-slate-700"
                >
                    Effective To
                </label>

                <input
                    type="date"
                    id="effective_to"
                    name="effective_to"
                    value="{{ $effectiveTo }}"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Leave blank for an open-ended structure.
                </p>
            </div>

        </div>
    </div>

    {{-- Salary Components --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-semibold text-slate-900">
                Salary Components
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Enter the employee-specific amount or percentage for each component.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Include
                        </th>

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
                            Amount / %
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Based On
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Calculated
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse ($components as $index => $salaryComponent)
                        @php
                            $existingItem = $existingItems->get($salaryComponent->id);

                            $oldItem = is_array($oldItems)
                                ? collect($oldItems)->first(
                                    fn ($item) =>
                                        (string) ($item['salary_component_id'] ?? '')
                                        === (string) $salaryComponent->id
                                )
                                : null;

                            $calculationType =
                                $oldItem['calculation_type']
                                ?? $existingItem?->calculation_type
                                ?? $salaryComponent->calculation_type
                                ?? 'fixed';

                            $amount =
                                $oldItem['amount']
                                ?? $existingItem?->amount
                                ?? $salaryComponent->default_amount
                                ?? 0;

                            $percentage =
                                $oldItem['percentage']
                                ?? $existingItem?->percentage
                                ?? $salaryComponent->default_percentage
                                ?? 0;

                            $percentageOfComponentId =
                                $oldItem['percentage_of_component_id']
                                ?? $existingItem?->percentage_of_component_id
                                ?? $salaryComponent->percentage_of_component_id
                                ?? '';

                            $included = is_array($oldItems)
                                ? $oldItem !== null
                                : ($editing ? $existingItem !== null : true);

                            $minimumAmount =
                                $oldItem['minimum_amount']
                                ?? $existingItem?->minimum_amount
                                ?? '';

                            $maximumAmount =
                                $oldItem['maximum_amount']
                                ?? $existingItem?->maximum_amount
                                ?? '';

                            $itemRemarks =
                                $oldItem['remarks']
                                ?? $existingItem?->remarks
                                ?? '';
                        @endphp

                        <tr
                            class="salary-component-row"
                            data-component-id="{{ $salaryComponent->id }}"
                            data-component-code="{{ $salaryComponent->code }}"
                            data-component-type="{{ $salaryComponent->type }}"
                        >
                            <td class="px-4 py-4 align-top">
                                <input
                                    type="checkbox"
                                    class="component-enabled rounded border-slate-300 text-cyan-700 focus:ring-cyan-600"
                                    @checked($included)
                                >

                                <input
                                    type="hidden"
                                    class="component-active-input"
                                    name="items[{{ $index }}][is_active]"
                                    value="{{ $included ? '1' : '0' }}"
                                >

                                <input
                                    type="hidden"
                                    name="items[{{ $index }}][salary_component_id]"
                                    value="{{ $salaryComponent->id }}"
                                >

                                <input
                                    type="hidden"
                                    name="items[{{ $index }}][calculation_type]"
                                    value="{{ $calculationType }}"
                                >

                                <input
                                    type="hidden"
                                    name="items[{{ $index }}][sort_order]"
                                    value="{{ $salaryComponent->sort_order ?? $index }}"
                                >

                                <input
                                    type="hidden"
                                    name="items[{{ $index }}][minimum_amount]"
                                    value="{{ $minimumAmount }}"
                                >

                                <input
                                    type="hidden"
                                    name="items[{{ $index }}][maximum_amount]"
                                    value="{{ $maximumAmount }}"
                                >

                                <input
                                    type="hidden"
                                    name="items[{{ $index }}][remarks]"
                                    value="{{ $itemRemarks }}"
                                >
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="font-semibold text-slate-900">
                                    {{ $salaryComponent->name }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    {{ $salaryComponent->code }}
                                </div>

                                @if ($salaryComponent->is_statutory)
                                    <div class="mt-1">
                                        <span class="rounded bg-purple-100 px-2 py-0.5 text-[11px] font-semibold text-purple-700">
                                            Statutory
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4 align-top">
                                @if ($salaryComponent->type === 'earning')
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Earning
                                    </span>
                                @else
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        Deduction
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-4 align-top text-sm text-slate-700">
                                @if ($calculationType === 'percentage')
                                    Percentage
                                @else
                                    Fixed Amount
                                @endif
                            </td>

                            <td class="px-4 py-4 align-top text-right">
                                @if ($calculationType === 'percentage')
                                    <div class="flex items-center justify-end gap-2">
                                        <input
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            name="items[{{ $index }}][percentage]"
                                            value="{{ $percentage }}"
                                            class="component-percentage w-28 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                                        >

                                        <span class="text-sm font-medium text-slate-500">
                                            %
                                        </span>
                                    </div>

                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][amount]"
                                        value=""
                                    >
                                @else
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="text-sm text-slate-500">
                                            ₹
                                        </span>

                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            name="items[{{ $index }}][amount]"
                                            value="{{ $amount }}"
                                            class="component-amount w-36 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                                        >
                                    </div>

                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][percentage]"
                                        value=""
                                    >
                                @endif
                            </td>

                            <td class="px-4 py-4 align-top text-sm text-slate-700">
                                @if ($calculationType === 'percentage')
                                    <select
                                        name="items[{{ $index }}][percentage_of_component_id]"
                                        class="component-base w-44 rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                                    >
                                        <option value="">
                                            Select component
                                        </option>

                                        @foreach ($components->where('type', 'earning') as $baseSalaryComponent)
                                            @if ($baseSalaryComponent->id !== $salaryComponent->id)
                                                <option
                                                    value="{{ $baseSalaryComponent->id }}"
                                                    @selected(
                                                        (string) $percentageOfComponentId
                                                        === (string) $baseSalaryComponent->id
                                                    )
                                                >
                                                    {{ $baseSalaryComponent->code }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-slate-400">
                                        —
                                    </span>

                                    <input
                                        type="hidden"
                                        name="items[{{ $index }}][percentage_of_component_id]"
                                        value=""
                                    >
                                @endif
                            </td>

                            <td class="px-4 py-4 text-right align-top">
                                <span class="component-calculated font-semibold text-slate-900">
                                    ₹0.00
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="7"
                                class="px-6 py-10 text-center text-sm text-slate-500"
                            >
                                No active salary components are available.
                                Add salary components before creating an employee salary structure.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>

    {{-- Salary Summary --}}
    <div class="grid gap-4 md:grid-cols-3">

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                Gross Earnings
            </div>

            <div
                id="grossEarningsDisplay"
                class="mt-2 text-2xl font-bold text-emerald-800"
            >
                ₹0.00
            </div>
        </div>

        <div class="rounded-xl border border-red-200 bg-red-50 p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-red-700">
                Total Deductions
            </div>

            <div
                id="totalDeductionsDisplay"
                class="mt-2 text-2xl font-bold text-red-800"
            >
                ₹0.00
            </div>
        </div>

        <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-cyan-700">
                Estimated Net Salary
            </div>

            <div
                id="netSalaryDisplay"
                class="mt-2 text-2xl font-bold text-cyan-800"
            >
                ₹0.00
            </div>
        </div>

    </div>

    <input
        type="hidden"
        id="monthly_salary"
        name="monthly_salary"
        value="{{ old('monthly_salary', $editing ? $salaryStructure->monthly_salary : 0) }}"
    >

    {{-- Remarks --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <label
            for="remarks"
            class="mb-1 block text-sm font-medium text-slate-700"
        >
            Remarks
        </label>

        <textarea
            id="remarks"
            name="remarks"
            rows="3"
            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
            placeholder="Optional notes about this salary structure or revision."
        >{{ $remarks }}</textarea>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">

        <a
            href="{{ route('admin.hr.payroll.salary-structures.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
        >
            Cancel
        </a>

        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm"
            style="background-color: #0e7490 !important; color: #ffffff !important;"
        >
            {{ $editing ? 'Save Changes' : 'Save Draft Salary Structure' }}
        </button>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = Array.from(
        document.querySelectorAll('.salary-component-row')
    );

    const grossDisplay = document.getElementById('grossEarningsDisplay');
    const deductionsDisplay = document.getElementById('totalDeductionsDisplay');
    const netDisplay = document.getElementById('netSalaryDisplay');
    const monthlySalaryInput = document.getElementById('monthly_salary');

    function numberValue(value) {
        const parsed = parseFloat(value);

        return Number.isFinite(parsed) ? parsed : 0;
    }

    function money(value) {
        return '₹' + numberValue(value).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function rowEnabled(row) {
        const checkbox = row.querySelector('.component-enabled');

        return checkbox ? checkbox.checked : true;
    }

    function fixedAmount(row) {
        const input = row.querySelector('.component-amount');

        return input ? numberValue(input.value) : 0;
    }

    function percentageAmount(row, calculatedById) {
        const percentageInput = row.querySelector('.component-percentage');
        const baseSelect = row.querySelector('.component-base');

        if (!percentageInput || !baseSelect) {
            return 0;
        }

        const percentage = numberValue(percentageInput.value);
        const baseComponentId = baseSelect.value;

        const baseAmount = calculatedById[baseComponentId] ?? 0;

        return baseAmount * (percentage / 100);
    }

    function recalculate() {
        const calculatedById = {};

        /*
         * Pass 1:
         * Calculate fixed components first.
         */
        rows.forEach(function (row) {
            const activeInput = row.querySelector('.component-active-input');
            const enabled = rowEnabled(row);

            if (activeInput) {
                activeInput.value = enabled ? '1' : '0';
            }

            if (!enabled) {
                calculatedById[row.dataset.componentId] = 0;
                return;
            }

            const fixedInput = row.querySelector('.component-amount');

            if (fixedInput) {
                calculatedById[row.dataset.componentId] =
                    fixedAmount(row);
            }
        });

        /*
         * Pass 2:
         * Calculate percentage components using the fixed/base values.
         */
        rows.forEach(function (row) {
            if (!rowEnabled(row)) {
                calculatedById[row.dataset.componentId] = 0;
                return;
            }

            const percentageInput = row.querySelector(
                '.component-percentage'
            );

            if (percentageInput) {
                calculatedById[row.dataset.componentId] =
                    percentageAmount(row, calculatedById);
            }
        });

        let gross = 0;
        let deductions = 0;

        rows.forEach(function (row) {
            const amount =
                calculatedById[row.dataset.componentId] ?? 0;

            const calculatedDisplay = row.querySelector(
                '.component-calculated'
            );

            if (calculatedDisplay) {
                calculatedDisplay.textContent = money(amount);
            }

            if (!rowEnabled(row)) {
                row.classList.add('opacity-50');
                return;
            }

            row.classList.remove('opacity-50');

            if (row.dataset.componentType === 'earning') {
                gross += amount;
            }

            if (row.dataset.componentType === 'deduction') {
                deductions += amount;
            }
        });

        const net = gross - deductions;

        grossDisplay.textContent = money(gross);
        deductionsDisplay.textContent = money(deductions);
        netDisplay.textContent = money(net);

        /*
         * monthly_salary stores gross monthly earnings.
         */
        monthlySalaryInput.value = gross.toFixed(2);
    }

    rows.forEach(function (row) {
        row.querySelectorAll(
            'input, select'
        ).forEach(function (input) {
            input.addEventListener('input', recalculate);
            input.addEventListener('change', recalculate);
        });
    });

    recalculate();
});
</script>