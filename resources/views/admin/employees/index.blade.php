<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Employee / Staff Master
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Manage hospital staff records separately from ERP user accounts.
                </p>
            </div>

            <a
                href="{{ route('admin.employees.create') }}"
                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                Add Employee
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 rounded-xl bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('admin.employees.index') }}"
                    class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6"
                >
                    <div class="lg:col-span-2">
                        <label
                            for="search"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Name, code, designation, phone..."
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label
                            for="department_id"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Department
                        </label>

                        <select
                            name="department_id"
                            id="department_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Departments</option>

                            @foreach ($departments as $department)
                                <option
                                    value="{{ $department->id }}"
                                    @selected((string) request('department_id') === (string) $department->id)
                                >
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="employee_type"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Employee Type
                        </label>

                        <select
                            name="employee_type"
                            id="employee_type"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Types</option>

                            @foreach ($employeeTypes as $employeeType)
                                <option
                                    value="{{ $employeeType }}"
                                    @selected(request('employee_type') === $employeeType)
                                >
                                    {{ $employeeType }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="doctor"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Doctor
                        </label>

                        <select
                            name="doctor"
                            id="doctor"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All</option>
                            <option value="yes" @selected(request('doctor') === 'yes')>
                                Doctors
                            </option>
                            <option value="no" @selected(request('doctor') === 'no')>
                                Non-doctors
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            for="status"
                            class="mb-1 block text-sm font-medium text-gray-700"
                        >
                            Status
                        </label>

                        <select
                            name="status"
                            id="status"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All</option>
                            <option value="active" @selected(request('status') === 'active')>
                                Active
                            </option>
                            <option value="inactive" @selected(request('status') === 'inactive')>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 lg:col-span-6">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('admin.employees.index') }}"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>

            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm">

                <div class="border-b border-gray-200 px-5 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Employees
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                {{ $employees->total() }} record(s)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Employee
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Code
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Designation
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Department
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Doctor
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">

                            @forelse ($employees as $employee)
                                <tr class="hover:bg-gray-50">

                                    <td class="whitespace-nowrap px-4 py-4">
                                        <div class="font-medium text-gray-900">
                                            {{ $employee->full_name }}
                                        </div>

                                        @if ($employee->qualification || $employee->speciality)
                                            <div class="mt-1 text-xs text-gray-500">
                                                @if ($employee->qualification)
                                                    {{ $employee->qualification }}
                                                @endif

                                                @if ($employee->qualification && $employee->speciality)
                                                    ·
                                                @endif

                                                @if ($employee->speciality)
                                                    {{ $employee->speciality }}
                                                @endif
                                            </div>
                                        @endif

                                        @if ($employee->phone)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $employee->phone }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">
                                        {{ $employee->employee_code }}
                                    </td>

                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        {{ $employee->designation ?: '—' }}
                                    </td>

                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        {{ $employee->department?->name ?: '—' }}
                                    </td>

                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        {{ $employee->employee_type ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4">
                                        @if ($employee->is_doctor)
                                            <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                Yes
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                                No
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4">
                                        @if ($employee->is_active)
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        <a
                                            href="{{ route('admin.employees.edit', $employee) }}"
                                            class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                                        >
                                            Edit
                                        </a>
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="8"
                                        class="px-4 py-10 text-center text-sm text-gray-500"
                                    >
                                        No employees found.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>

                @if ($employees->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $employees->links() }}
                    </div>
                @endif

            </div>

        </div>
    </div>
</x-app-layout>