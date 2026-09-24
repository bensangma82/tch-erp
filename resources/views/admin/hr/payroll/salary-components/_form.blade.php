@php
    /*
    |--------------------------------------------------------------------------
    | Salary Component Form State
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Do not use the variable name $component here.
    | Laravel Blade components reserve $component internally.
    |
    */

    $editing = isset($salaryComponent)
        && $salaryComponent instanceof \App\Models\SalaryComponent
        && $salaryComponent->exists;

    $selectedType = old(
        'type',
        $editing ? $salaryComponent->type : 'earning'
    );

    $selectedCalculationType = old(
        'calculation_type',
        $editing ? $salaryComponent->calculation_type : 'fixed'
    );

    $selectedBaseComponent = old(
        'percentage_of_component_id',
        $editing ? $salaryComponent->percentage_of_component_id : ''
    );

    $code = old(
        'code',
        $editing ? $salaryComponent->code : ''
    );

    $name = old(
        'name',
        $editing ? $salaryComponent->name : ''
    );

    $sortOrder = old(
        'sort_order',
        $editing ? $salaryComponent->sort_order : 0
    );

    $defaultAmount = old(
        'default_amount',
        $editing ? $salaryComponent->default_amount : ''
    );

    $defaultPercentage = old(
        'default_percentage',
        $editing ? $salaryComponent->default_percentage : ''
    );

    $description = old(
        'description',
        $editing ? $salaryComponent->description : ''
    );

    $isTaxable = old(
        'is_taxable',
        $editing ? $salaryComponent->is_taxable : false
    );

    $affectsGross = old(
        'affects_gross',
        $editing ? $salaryComponent->affects_gross : true
    );

    $isStatutory = old(
        'is_statutory',
        $editing ? $salaryComponent->is_statutory : false
    );

    $isRecurring = old(
        'is_recurring',
        $editing ? $salaryComponent->is_recurring : true
    );

    $isActive = old(
        'is_active',
        $editing ? $salaryComponent->is_active : true
    );
@endphp


<div class="space-y-6">

    {{-- ========================================================= --}}
    {{-- BASIC INFORMATION --}}
    {{-- ========================================================= --}}

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-100 px-6 py-5">

            <h3 class="font-semibold text-slate-900">
                Component Details
            </h3>

            <p class="mt-1 text-xs text-slate-500">
                Define the salary component and how it behaves in payroll.
            </p>

        </div>


        <div class="p-6">

            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">


                {{-- COMPONENT CODE --}}

                <div>

                    <label
                        for="code"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Component Code
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="code"
                        type="text"
                        name="code"
                        value="{{ $code }}"
                        required
                        maxlength="50"
                        autocomplete="off"
                        placeholder="e.g. BASIC"
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm uppercase shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    >

                    <p class="mt-1 text-xs text-slate-400">
                        Unique payroll code
                    </p>

                </div>



                {{-- COMPONENT NAME --}}

                <div class="xl:col-span-2">

                    <label
                        for="name"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Component Name
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ $name }}"
                        required
                        maxlength="150"
                        placeholder="e.g. Basic Pay"
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    >

                </div>



                {{-- DISPLAY ORDER --}}

                <div>

                    <label
                        for="sort_order"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Display Order
                    </label>

                    <input
                        id="sort_order"
                        type="number"
                        name="sort_order"
                        value="{{ $sortOrder }}"
                        min="0"
                        max="9999"
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    >

                </div>



                {{-- COMPONENT TYPE --}}

                <div>

                    <label
                        for="type"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Component Type
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="type"
                        name="type"
                        required
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    >

                        <option
                            value="earning"
                            @selected($selectedType === 'earning')
                        >
                            Earning
                        </option>

                        <option
                            value="deduction"
                            @selected($selectedType === 'deduction')
                        >
                            Deduction
                        </option>

                    </select>

                </div>



                {{-- CALCULATION METHOD --}}

                <div>

                    <label
                        for="calculation_type"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Calculation Method
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="calculation_type"
                        name="calculation_type"
                        required
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    >

                        <option
                            value="fixed"
                            @selected($selectedCalculationType === 'fixed')
                        >
                            Fixed Amount
                        </option>

                        <option
                            value="percentage"
                            @selected($selectedCalculationType === 'percentage')
                        >
                            Percentage
                        </option>

                    </select>

                </div>



                {{-- DEFAULT FIXED AMOUNT --}}

                <div id="fixed-amount-section">

                    <label
                        for="default_amount"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Default Amount
                    </label>

                    <div class="relative mt-2">

                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">
                            ₹
                        </div>

                        <input
                            id="default_amount"
                            type="number"
                            name="default_amount"
                            value="{{ $defaultAmount }}"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            class="block w-full rounded-lg border-slate-300 pl-8 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >

                    </div>

                    <p class="mt-1 text-xs text-slate-400">
                        Can be overridden in an employee salary structure.
                    </p>

                </div>



                {{-- DESCRIPTION --}}

                <div class="md:col-span-2 xl:col-span-1">

                    <label
                        for="description"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        maxlength="2000"
                        placeholder="Optional payroll or policy note"
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    >{{ $description }}</textarea>

                </div>

            </div>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- PERCENTAGE CALCULATION --}}
    {{-- ========================================================= --}}

    <section
        id="percentage-section"
        class="overflow-hidden rounded-2xl border border-cyan-200 bg-cyan-50/40 shadow-sm"
    >

        <div class="border-b border-cyan-100 px-6 py-5">

            <h3 class="font-semibold text-slate-900">
                Percentage Calculation
            </h3>

            <p class="mt-1 text-xs text-slate-500">
                Used only when the calculation method is Percentage.
            </p>

        </div>


        <div class="p-6">

            <div class="grid gap-5 md:grid-cols-2">


                {{-- PERCENTAGE --}}

                <div>

                    <label
                        for="default_percentage"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Percentage
                    </label>

                    <div class="relative mt-2">

                        <input
                            id="default_percentage"
                            type="number"
                            name="default_percentage"
                            value="{{ $defaultPercentage }}"
                            min="0"
                            max="1000"
                            step="0.0001"
                            placeholder="e.g. 12"
                            class="block w-full rounded-lg border-slate-300 pr-10 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >

                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-sm text-slate-500">
                            %
                        </div>

                    </div>

                </div>



                {{-- PERCENTAGE OF --}}

                <div>

                    <label
                        for="percentage_of_component_id"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Percentage Of
                    </label>

                    <select
                        id="percentage_of_component_id"
                        name="percentage_of_component_id"
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    >

                        <option value="">
                            Select base component
                        </option>

                        @foreach ($baseComponents as $baseComponent)

                            <option
                                value="{{ $baseComponent->id }}"
                                @selected(
                                    (string) $selectedBaseComponent
                                    === (string) $baseComponent->id
                                )
                            >
                                {{ $baseComponent->code }} — {{ $baseComponent->name }}
                            </option>

                        @endforeach

                    </select>

                    <p class="mt-1 text-xs text-slate-400">
                        Example: PF = 12% of Basic Pay.
                    </p>

                </div>

            </div>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- PAYROLL BEHAVIOUR --}}
    {{-- ========================================================= --}}

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-100 px-6 py-5">

            <h3 class="font-semibold text-slate-900">
                Payroll Behaviour
            </h3>

            <p class="mt-1 text-xs text-slate-500">
                Configure how this component participates in salary and payroll calculations.
            </p>

        </div>


        <div class="p-6">

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">


                {{-- TAXABLE --}}

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">

                    <input
                        type="hidden"
                        name="is_taxable"
                        value="0"
                    >

                    <input
                        type="checkbox"
                        name="is_taxable"
                        value="1"
                        @checked((bool) $isTaxable)
                        class="mt-0.5 rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                    >

                    <span>

                        <span class="block text-sm font-semibold text-slate-800">
                            Taxable
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                            Include this component when determining taxable earnings.
                        </span>

                    </span>

                </label>



                {{-- AFFECTS GROSS --}}

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">

                    <input
                        type="hidden"
                        name="affects_gross"
                        value="0"
                    >

                    <input
                        type="checkbox"
                        name="affects_gross"
                        value="1"
                        @checked((bool) $affectsGross)
                        class="mt-0.5 rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                    >

                    <span>

                        <span class="block text-sm font-semibold text-slate-800">
                            Affects Gross Salary
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                            Include this component in gross salary calculations.
                        </span>

                    </span>

                </label>



                {{-- STATUTORY --}}

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">

                    <input
                        type="hidden"
                        name="is_statutory"
                        value="0"
                    >

                    <input
                        type="checkbox"
                        name="is_statutory"
                        value="1"
                        @checked((bool) $isStatutory)
                        class="mt-0.5 rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                    >

                    <span>

                        <span class="block text-sm font-semibold text-slate-800">
                            Statutory
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                            Mark PF, tax or another statutory payroll component.
                        </span>

                    </span>

                </label>



                {{-- RECURRING --}}

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">

                    <input
                        type="hidden"
                        name="is_recurring"
                        value="0"
                    >

                    <input
                        type="checkbox"
                        name="is_recurring"
                        value="1"
                        @checked((bool) $isRecurring)
                        class="mt-0.5 rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                    >

                    <span>

                        <span class="block text-sm font-semibold text-slate-800">
                            Recurring
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                            Normally included in every monthly payroll.
                        </span>

                    </span>

                </label>

            </div>



            {{-- ACTIVE --}}

            <div class="mt-3">

                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4">

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked((bool) $isActive)
                        class="mt-0.5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500"
                    >

                    <span>

                        <span class="block text-sm font-semibold text-emerald-900">
                            Active Component
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-emerald-700">
                            Active components are available when creating employee salary structures.
                        </span>

                    </span>

                </label>

            </div>

        </div>

    </section>



    {{-- ========================================================= --}}
    {{-- ACTIONS --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">

        <a
            href="{{ route('admin.hr.payroll.salary-components.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
        >
            Cancel
        </a>


        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm transition"
            style="background-color: #0e7490 !important; color: #ffffff !important;"
        >
            {{ $editing ? 'Update Salary Component' : 'Create Salary Component' }}
        </button>

    </div>

</div>



{{-- ============================================================= --}}
{{-- CALCULATION TYPE UI --}}
{{-- ============================================================= --}}

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const calculationType =
            document.getElementById('calculation_type');

        const fixedSection =
            document.getElementById('fixed-amount-section');

        const percentageSection =
            document.getElementById('percentage-section');

        const defaultAmount =
            document.getElementById('default_amount');

        const defaultPercentage =
            document.getElementById('default_percentage');

        const percentageOfComponent =
            document.getElementById('percentage_of_component_id');


        if (
            !calculationType ||
            !fixedSection ||
            !percentageSection
        ) {
            return;
        }


        function updateCalculationFields() {

            const isPercentage =
                calculationType.value === 'percentage';


            if (isPercentage) {

                fixedSection.style.display = 'none';
                percentageSection.style.display = 'block';

                if (defaultAmount) {
                    defaultAmount.disabled = true;
                }

                if (defaultPercentage) {
                    defaultPercentage.disabled = false;
                }

                if (percentageOfComponent) {
                    percentageOfComponent.disabled = false;
                }

            } else {

                fixedSection.style.display = 'block';
                percentageSection.style.display = 'none';

                if (defaultAmount) {
                    defaultAmount.disabled = false;
                }

                if (defaultPercentage) {
                    defaultPercentage.disabled = true;
                }

                if (percentageOfComponent) {
                    percentageOfComponent.disabled = true;
                }

            }

        }


        calculationType.addEventListener(
            'change',
            updateCalculationFields
        );

        updateCalculationFields();

    });
</script>