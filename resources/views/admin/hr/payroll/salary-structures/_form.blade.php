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

    $restoringInput = session()->hasOldInput('employee_id');
    $oldItems = collect(old('items', []))
        ->filter(fn ($item) => is_array($item))
        ->keyBy('salary_component_id');
    $employeeCategory = $employees->firstWhere('id', $selectedEmployee)?->employee_type;
    $staffGroups = \App\Models\BasicPayRate::staffGroups($basicPayRates);
    $selectedStaffGroup = old('staff_group', $editing ? $salaryStructure->staff_group : null);

    if (is_string($selectedStaffGroup) && $selectedStaffGroup !== '' && !$staffGroups->has($selectedStaffGroup)) {
        $staffGroups->put($selectedStaffGroup, \Illuminate\Support\Str::title($selectedStaffGroup));
    }
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
                            data-employee-category="{{ $employee->employee_type }}"
                            data-category-key="{{ \App\Models\BasicPayRate::key((string) $employee->employee_type) }}"
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


            <div class="lg:col-span-2">
                <span class="mb-1 block text-sm font-medium text-slate-700">Employment Type</span>
                <output id="employee-category" for="employee_id" class="block text-sm text-slate-700">
                    {{ $employeeCategory ?: '—' }}
                </output>
            </div>

            <div>
                <label for="staff_group" class="mb-1 block text-sm font-medium text-slate-700">
                    Staff Group
                </label>
                <select id="staff_group" name="staff_group"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600">
                    <option value="">Select staff group</option>
                    @foreach ($staffGroups as $key => $label)
                        <option value="{{ $key }}" @selected($selectedStaffGroup === (string) $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="pay_grade" class="mb-1 block text-sm font-medium text-slate-700">
                    Pay Grade
                </label>
                <input
                    type="text"
                    id="pay_grade"
                    name="pay_grade"
                    maxlength="60"
                    list="basic-pay-grades"
                    value="{{ old('pay_grade', $editing ? $salaryStructure->pay_grade : '') }}"
                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                >
                <datalist id="basic-pay-grades"></datalist>
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
                Select the earnings and deductions that apply, then enter the amount or percentage.
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

                            $oldItem = $oldItems->get($salaryComponent->id);

                            $calculationType =
                                $oldItem['calculation_type']
                                ?? $existingItem?->calculation_type
                                ?? $salaryComponent->calculation_type
                                ?? 'fixed';

                            $isFixedBasicPay = $salaryComponent->code === 'BASIC'
                                && $calculationType === 'fixed';

                            $amount = $isFixedBasicPay
                                ? ($restoringInput ? ($oldItem['amount'] ?? '') : ($existingItem?->amount ?? ''))
                                : ($oldItem['amount'] ?? $existingItem?->amount ?? $salaryComponent->default_amount ?? 0);

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

                            $included = $restoringInput
                                ? in_array($oldItem['is_active'] ?? null, [true, 1, '1'], true)
                                : ($existingItem?->is_active ?? false);

                            $sortOrder = $oldItem['sort_order']
                                ?? $existingItem?->sort_order
                                ?? $salaryComponent->sort_order
                                ?? $index;

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
                            data-component-type="{{ $salaryComponent->type }}"
                            data-sort-order="{{ $sortOrder }}"
                            data-minimum-amount="{{ $minimumAmount }}"
                            data-maximum-amount="{{ $maximumAmount }}"
                        >
                            <td class="px-4 py-4 align-top">
                                <input type="hidden" name="items[{{ $index }}][is_active]" value="0">

                                <input
                                    type="checkbox"
                                    name="items[{{ $index }}][is_active]"
                                    value="1"
                                    aria-label="Include {{ $salaryComponent->name }}"
                                    class="component-enabled rounded border-slate-300 text-cyan-700 focus:ring-cyan-600"
                                    @checked($included)
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
                                    value="{{ $sortOrder }}"
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

                                @unless ($salaryComponent->is_active)
                                    <div class="mt-1 text-xs text-slate-500">Inactive in Salary Components</div>
                                @endunless

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
                                            @if ($isFixedBasicPay)
                                                id="basic-pay-amount"
                                                aria-label="Basic Pay amount"
                                                aria-describedby="basic-pay-rate-status"
                                                data-preserve-amount="{{ $restoringInput || $existingItem?->amount !== null ? '1' : '0' }}"
                                            @endif
                                            class="component-amount w-36 rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                                        >
                                    </div>

                                    @if ($isFixedBasicPay)
                                        <p id="basic-pay-rate-status" aria-live="polite" class="mt-1 text-xs text-slate-600"></p>
                                        <button id="apply-basic-pay-rate" type="button" hidden
                                            class="mt-1 text-xs font-medium text-cyan-700 hover:underline">
                                            Use Grade Rate
                                        </button>
                                    @endif

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
    const employee = document.getElementById('employee_id');
    const category = document.getElementById('employee-category');
    employee.addEventListener('change', function () {
        category.textContent = employee.selectedOptions[0]?.dataset.employeeCategory || '—';
    });

    const basicPayAmount = document.getElementById('basic-pay-amount');
    const grade = document.getElementById('pay_grade');
    const staffGroup = document.getElementById('staff_group');
    const gradeOptions = document.getElementById('basic-pay-grades');
    const rateStatus = document.getElementById('basic-pay-rate-status');
    const applyRate = document.getElementById('apply-basic-pay-rate');
    const basicPayRates = {{ \Illuminate\Support\Js::from($basicPayRates->map(fn ($rate) => [
        'group' => $rate->staff_group,
        'category' => $rate->employee_category,
        'grade' => $rate->grade_key,
        'gradeLabel' => $rate->pay_grade,
        'amount' => $rate->amount,
    ])->all()) }};
    let preserveBasicPay = basicPayAmount?.dataset.preserveAmount === '1';
    let availableRates = [];

    function updateAvailableRates() {
        const categoryKey = employee.selectedOptions[0]?.dataset.categoryKey;
        const group = staffGroup.value;
        availableRates = basicPayRates.filter(rate => group
            ? rate.group === group
            : !rate.group && rate.category === categoryKey);

        gradeOptions.replaceChildren(...availableRates.map(rate => {
            const option = document.createElement('option');
            option.value = rate.gradeLabel;
            return option;
        }));

        updateBasicPayRate();
    }

    function updateBasicPayRate() {
        const gradeKey = grade.value.trim().toLowerCase();
        const rate = availableRates.find(item => item.grade === gradeKey);

        if (!preserveBasicPay) {
            basicPayAmount.value = rate?.amount ?? '';
        }

        applyRate.hidden = !rate || !preserveBasicPay;
        rateStatus.textContent = rate
            ? 'Grade rate ' + money(rate.amount)
            : 'Enter Basic Pay for this employee.';
        recalculate();
    }

    rows.sort((left, right) => Number(left.dataset.sortOrder) - Number(right.dataset.sortOrder));

    function roundAmount(value) {
        return Number(value.toFixed(2));
    }

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

        return roundAmount(numberValue(input.value));
    }

    function percentageAmount(row, calculatedById) {
        const percentageInput = row.querySelector('.component-percentage');
        const baseSelect = row.querySelector('.component-base');

        if (!percentageInput || !baseSelect) {
            return 0;
        }

        const percentage = numberValue(percentageInput.value);
        const baseAmount = calculatedById[baseSelect.value];
        if (baseAmount === undefined) {
            return null;
        }

        let amount = baseAmount * (percentage / 100);
        if (row.dataset.minimumAmount !== '') {
            amount = Math.max(amount, numberValue(row.dataset.minimumAmount));
        }
        if (row.dataset.maximumAmount !== '') {
            amount = Math.min(amount, numberValue(row.dataset.maximumAmount));
        }

        return roundAmount(amount);
    }

    function recalculate() {
        const calculatedById = {};

        /*
         * Pass 1:
         * Calculate fixed components first.
         */
        rows.forEach(function (row) {
            if (!rowEnabled(row)) {
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
                return;
            }

            const percentageInput = row.querySelector(
                '.component-percentage'
            );

            if (percentageInput) {
                const amount = percentageAmount(row, calculatedById);
                if (amount !== null) {
                    calculatedById[row.dataset.componentId] = amount;
                }
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
                calculatedDisplay.textContent = rowEnabled(row) && !(row.dataset.componentId in calculatedById)
                    ? 'Check base component'
                    : money(amount);
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
    }

    rows.forEach(function (row) {
        row.querySelectorAll(
            'input, select'
        ).forEach(function (input) {
            input.addEventListener('input', recalculate);
            input.addEventListener('change', recalculate);
        });
    });

    if (basicPayAmount) {
        employee.addEventListener('change', updateAvailableRates);
        staffGroup.addEventListener('change', updateAvailableRates);
        grade.addEventListener('input', updateBasicPayRate);
        basicPayAmount.addEventListener('input', function () {
            preserveBasicPay = true;
            updateBasicPayRate();
        });
        applyRate.addEventListener('click', function () {
            preserveBasicPay = false;
            updateBasicPayRate();
        });
        updateAvailableRates();
    } else {
        recalculate();
    }
});
</script>