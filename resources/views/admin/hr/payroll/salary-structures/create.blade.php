<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">
                Create Employee Salary Structure
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Define the employee's monthly earnings and deductions.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            <form
                method="POST"
                action="{{ route('admin.hr.payroll.salary-structures.store') }}"
            >
                @csrf

                @include(
                    'admin.hr.payroll.salary-structures._form',
                    [
                        'salaryStructure' => null,
                        'employees' => $employees,
                        'components' => $components,
                        'selectedEmployeeId' => $selectedEmployeeId ?? null,
                    ]
                )
            </form>

        </div>
    </div>
</x-app-layout>