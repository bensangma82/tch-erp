<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    Employee Salary Structures
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage employee-specific salary components, effective dates and salary revisions.
                </p>
            </div>

            <a
                href="{{ route('admin.hr.payroll.salary-structures.create') }}"
                class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition"
                style="background-color: #0e7490 !important; color: #ffffff !important;"
            >
                Create Salary Structure
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

            {{-- Summary --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Listed Structures
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $salaryStructures->total() }}
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Drafts on Page
                    </div>

                    <div class="mt-2 text-2xl font-bold text-amber-600">
                        {{ $salaryStructures->getCollection()->where('status', 'draft')->count() }}
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Active on Page
                    </div>

                    <div class="mt-2 text-2xl font-bold text-emerald-600">
                        {{ $salaryStructures->getCollection()->where('status', 'active')->count() }}
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Superseded on Page
                    </div>

                    <div class="mt-2 text-2xl font-bold text-slate-600">
                        {{ $salaryStructures->getCollection()->where('status', 'superseded')->count() }}
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('admin.hr.payroll.salary-structures.index') }}"
                    class="grid gap-4 md:grid-cols-4"
                >
                    <div class="md:col-span-2">
                        <label
                            for="employee_id"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Employee
                        </label>

                        <select
                            id="employee_id"
                            name="employee_id"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                        >
                            <option value="">All employees</option>

                            @foreach ($employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    @selected((string) request('employee_id') === (string) $employee->id)
                                >
                                    {{ $employee->employee_code }}
                                    —
                                    {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </select>
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

                            <option value="active" @selected(request('status') === 'active')>
                                Active
                            </option>

                            <option value="superseded" @selected(request('status') === 'superseded')>
                                Superseded
                            </option>

                            <option value="cancelled" @selected(request('status') === 'cancelled')>
                                Cancelled
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm"
                            style="background-color: #0e7490 !important; color: #ffffff !important;"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('admin.hr.payroll.salary-structures.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- Salary Structure Table --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-semibold text-slate-900">
                        Salary Structure Register
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Salary revisions are retained as separate effective-dated records.
                    </p>
                </div>

                @if ($salaryStructures->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Employee
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Designation
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Effective Period
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Monthly Salary
                                    </th>

                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Components
                                    </th>

                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Status
                                    </th>

                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($salaryStructures as $salaryStructure)
                                    <tr class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <div class="font-semibold text-slate-900">
                                                {{ $salaryStructure->employee?->full_name ?? '—' }}
                                            </div>

                                            <div class="mt-0.5 text-xs text-slate-500">
                                                {{ $salaryStructure->employee?->employee_code ?? '—' }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <div>
                                                {{ $salaryStructure->employee?->designation ?: '—' }}
                                            </div>

                                            @if ($salaryStructure->employee?->department)
                                                <div class="mt-0.5 text-xs text-slate-500">
                                                    {{ $salaryStructure->employee->department->name }}
                                                </div>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                            <div>
                                                {{ $salaryStructure->effective_from?->format('d M Y') ?? '—' }}
                                            </div>

                                            <div class="mt-0.5 text-xs text-slate-500">
                                                to
                                                {{ $salaryStructure->effective_to?->format('d M Y') ?? 'Open-ended' }}
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <div class="font-semibold text-slate-900">
                                                ₹{{ number_format((float) ($salaryStructure->monthly_salary ?? 0), 2) }}
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-slate-700">
                                            {{ $salaryStructure->items->where('is_active', true)->count() }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-center">
                                            @if ($salaryStructure->status === 'active')
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                    Active
                                                </span>
                                            @elseif ($salaryStructure->status === 'draft')
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                    Draft
                                                </span>
                                            @elseif ($salaryStructure->status === 'superseded')
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                    Superseded
                                                </span>
                                            @elseif ($salaryStructure->status === 'cancelled')
                                                <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                    Cancelled
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                    {{ ucfirst($salaryStructure->status) }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($salaryStructure->isDraft())
                                                    <a
                                                        href="{{ route('admin.hr.payroll.salary-structures.edit', $salaryStructure) }}"
                                                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                                    >
                                                        Edit
                                                    </a>

                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.hr.payroll.salary-structures.activate', $salaryStructure) }}"
                                                        onsubmit="return confirm('Activate this salary structure? Once activated, it becomes the employee current salary structure.');"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center rounded-md px-3 py-1.5 text-xs font-semibold shadow-sm"
                                                            style="background-color: #047857 !important; color: #ffffff !important;"
                                                        >
                                                            Activate
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs text-slate-400">
                                                        Historical
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($salaryStructures->hasPages())
                        <div class="border-t border-slate-200 px-5 py-4">
                            {{ $salaryStructures->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-12 text-center">
                        <h3 class="text-base font-semibold text-slate-900">
                            No salary structures found
                        </h3>

                        <p class="mt-2 text-sm text-slate-500">
                            Create the first employee salary structure to begin payroll configuration.
                        </p>

                        <div class="mt-5">
                            <a
                                href="{{ route('admin.hr.payroll.salary-structures.create') }}"
                                class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm"
                                style="background-color: #0e7490 !important; color: #ffffff !important;"
                            >
                                Create First Salary Structure
                            </a>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>