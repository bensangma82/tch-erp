<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources · Payroll
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Salary Components
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configure earnings and deductions used for employee salary structures and monthly payroll.
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('admin.hr.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Back to HR Dashboard
                </a>

                <a
                    href="{{ route('admin.hr.payroll.salary-components.create') }}"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition"
                    style="background-color: #0e7490 !important; color: #ffffff !important;"
                >
                    Add Salary Component
                </a>

            </div>

        </div>

    </x-slot>



    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- FLASH / VALIDATION --}}
            {{-- ========================================================= --}}

            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4">

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



            {{-- ========================================================= --}}
            {{-- INTRODUCTION --}}
            {{-- ========================================================= --}}

            <section class="overflow-hidden rounded-2xl border border-cyan-100 bg-gradient-to-br from-cyan-50 via-white to-slate-50 shadow-sm">

                <div class="px-6 py-6">

                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-700">
                        Payroll Configuration
                    </div>

                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                        Salary Component Master
                    </h1>

                    <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
                        Define recurring earnings and deductions used when creating employee salary structures.
                        Components should normally be deactivated rather than removed so historical payroll
                        records remain traceable.
                    </p>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- SUMMARY --}}
            {{-- ========================================================= --}}

            @php
                $totalComponents = $components->count();
                $activeComponents = $components->where('is_active', true)->count();
                $earningComponents = $components->where('type', 'earning')->count();
                $deductionComponents = $components->where('type', 'deduction')->count();
            @endphp


            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">


                {{-- TOTAL --}}

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Total Components
                    </div>

                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $totalComponents }}
                    </div>

                </div>



                {{-- ACTIVE --}}

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Active
                    </div>

                    <div class="mt-2 text-3xl font-bold text-emerald-900">
                        {{ $activeComponents }}
                    </div>

                </div>



                {{-- EARNINGS --}}

                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-cyan-700">
                        Earnings
                    </div>

                    <div class="mt-2 text-3xl font-bold text-cyan-900">
                        {{ $earningComponents }}
                    </div>

                </div>



                {{-- DEDUCTIONS --}}

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">

                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Deductions
                    </div>

                    <div class="mt-2 text-3xl font-bold text-amber-900">
                        {{ $deductionComponents }}
                    </div>

                </div>


            </div>



            {{-- ========================================================= --}}
            {{-- COMPONENT TABLE --}}
            {{-- ========================================================= --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                {{-- TABLE HEADER --}}

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Configured Salary Components
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Earnings and deductions available for employee salary structures.
                            </p>

                        </div>


                        <a
                            href="{{ route('admin.hr.payroll.salary-components.create') }}"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition"
                            style="background-color: #0e7490 !important; color: #ffffff !important;"
                        >
                            Add Component
                        </a>

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- EMPTY STATE --}}
                {{-- ===================================================== --}}

                @if ($components->isEmpty())

                    <div class="px-6 py-14 text-center">

                        <div class="text-lg font-semibold text-slate-700">
                            No salary components configured
                        </div>

                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                            Start by creating components such as Basic Pay, HRA,
                            Special Allowance, Provident Fund or other payroll deductions.
                        </p>


                        <a
                            href="{{ route('admin.hr.payroll.salary-components.create') }}"
                            class="mt-5 inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm transition"
                            style="background-color: #0e7490 !important; color: #ffffff !important;"
                        >
                            Create First Component
                        </a>

                    </div>


                @else


                    {{-- ================================================= --}}
                    {{-- COMPONENT LIST --}}
                    {{-- ================================================= --}}

                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-slate-200">


                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Order
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Component
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Type
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Calculation
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Default
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Attributes
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Actions
                                    </th>

                                </tr>

                            </thead>



                            <tbody class="divide-y divide-slate-100 bg-white">


                                @foreach ($components as $component)

                                    <tr class="hover:bg-slate-50/70">


                                        {{-- ORDER --}}

                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                            {{ $component->sort_order }}
                                        </td>



                                        {{-- COMPONENT --}}

                                        <td class="px-5 py-4">

                                            <div class="font-semibold text-slate-900">
                                                {{ $component->name }}
                                            </div>

                                            <div class="mt-1 font-mono text-xs font-semibold text-slate-500">
                                                {{ $component->code }}
                                            </div>

                                            @if ($component->description)

                                                <div class="mt-1 max-w-sm text-xs text-slate-500">
                                                    {{ $component->description }}
                                                </div>

                                            @endif

                                        </td>



                                        {{-- TYPE --}}

                                        <td class="whitespace-nowrap px-5 py-4">

                                            @if ($component->type === 'earning')

                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                                    Earning
                                                </span>

                                            @else

                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                    Deduction
                                                </span>

                                            @endif

                                        </td>



                                        {{-- CALCULATION --}}

                                        <td class="px-5 py-4 text-sm text-slate-700">

                                            @if ($component->calculation_type === 'percentage')

                                                <div class="font-semibold">
                                                    Percentage
                                                </div>

                                                @if ($component->percentageOfComponent)

                                                    <div class="mt-1 text-xs text-slate-500">
                                                        of {{ $component->percentageOfComponent->name }}
                                                    </div>

                                                @endif

                                            @else

                                                <span class="font-semibold">
                                                    Fixed Amount
                                                </span>

                                            @endif

                                        </td>



                                        {{-- DEFAULT VALUE --}}

                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">

                                            @if ($component->calculation_type === 'percentage')

                                                <span class="font-semibold">
                                                    {{ number_format((float) $component->default_percentage, 2) }}%
                                                </span>

                                            @elseif (!is_null($component->default_amount))

                                                <span class="font-semibold">
                                                    ₹{{ number_format((float) $component->default_amount, 2) }}
                                                </span>

                                            @else

                                                <span class="text-slate-400">
                                                    —
                                                </span>

                                            @endif

                                        </td>



                                        {{-- ATTRIBUTES --}}

                                        <td class="px-5 py-4">

                                            <div class="flex max-w-xs flex-wrap gap-1.5">


                                                @if ($component->is_statutory)

                                                    <span class="rounded-full bg-violet-100 px-2 py-1 text-[11px] font-semibold text-violet-700">
                                                        Statutory
                                                    </span>

                                                @endif


                                                @if ($component->is_taxable)

                                                    <span class="rounded-full bg-blue-100 px-2 py-1 text-[11px] font-semibold text-blue-700">
                                                        Taxable
                                                    </span>

                                                @endif


                                                @if ($component->is_recurring)

                                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-700">
                                                        Recurring
                                                    </span>

                                                @endif


                                                @if ($component->affects_gross)

                                                    <span class="rounded-full bg-cyan-100 px-2 py-1 text-[11px] font-semibold text-cyan-700">
                                                        Gross
                                                    </span>

                                                @endif


                                                @if (
                                                    !$component->is_statutory
                                                    && !$component->is_taxable
                                                    && !$component->is_recurring
                                                    && !$component->affects_gross
                                                )

                                                    <span class="text-xs text-slate-400">
                                                        —
                                                    </span>

                                                @endif

                                            </div>

                                        </td>



                                        {{-- STATUS --}}

                                        <td class="whitespace-nowrap px-5 py-4">

                                            @if ($component->is_active)

                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                                    Active
                                                </span>

                                            @else

                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                    Inactive
                                                </span>

                                            @endif

                                        </td>



                                        {{-- ACTIONS --}}

                                        <td class="whitespace-nowrap px-5 py-4 text-right">

                                            <div class="flex justify-end gap-2">


                                                {{-- EDIT --}}

                                                <a
                                                    href="{{ route('admin.hr.payroll.salary-components.edit', $component) }}"
                                                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                                >
                                                    Edit
                                                </a>



                                                {{-- ACTIVATE / DEACTIVATE --}}

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.hr.payroll.salary-components.toggle-status', $component) }}"
                                                >

                                                    @csrf
                                                    @method('PATCH')


                                                    @if ($component->is_active)

                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center justify-center rounded-lg border px-3 py-2 text-xs font-semibold shadow-sm"
                                                            style="border-color: #f59e0b !important; background-color: #fffbeb !important; color: #92400e !important;"
                                                        >
                                                            Deactivate
                                                        </button>

                                                    @else

                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center justify-center rounded-lg border px-3 py-2 text-xs font-semibold shadow-sm"
                                                            style="border-color: #10b981 !important; background-color: #ecfdf5 !important; color: #065f46 !important;"
                                                        >
                                                            Activate
                                                        </button>

                                                    @endif

                                                </form>


                                            </div>

                                        </td>


                                    </tr>

                                @endforeach


                            </tbody>

                        </table>

                    </div>

                @endif

            </section>



            {{-- ========================================================= --}}
            {{-- INFORMATION --}}
            {{-- ========================================================= --}}

            <section class="rounded-2xl border border-slate-200 bg-slate-50 px-6 py-5">

                <h3 class="font-semibold text-slate-900">
                    Payroll Configuration Note
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Salary components define the building blocks of payroll. The actual amount applicable
                    to an employee will be stored in that employee's effective salary structure. Monthly
                    payroll will then snapshot those values so later changes to the master do not alter
                    historical payroll records.
                </p>

            </section>


        </div>

    </div>

</x-app-layout>