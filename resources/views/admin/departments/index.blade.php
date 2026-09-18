<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-slate-900">
                    Department Master
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage hospital departments used across OPD, IPD, diagnostics and administration.
                </p>

            </div>


            <a
                href="{{ route('admin.departments.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                Add Department
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-200 px-6 py-4">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="text-base font-semibold text-slate-900">
                                Hospital Departments
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Active department records are available to clinical and administrative modules.
                            </p>

                        </div>


                        <div class="text-sm font-medium text-slate-500">
                            Total:
                            <span class="font-bold text-slate-900">
                                {{ $departments->total() }}
                            </span>
                        </div>

                    </div>

                </div>


                @if ($departments->count() > 0)

                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Code
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Department
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Type
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Status
                                    </th>

                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100 bg-white">

                                @foreach ($departments as $department)

                                    @php

                                        $typeClasses = [
                                            'clinical' =>
                                                'bg-blue-50 text-blue-700',

                                            'diagnostic' =>
                                                'bg-violet-50 text-violet-700',

                                            'support' =>
                                                'bg-amber-50 text-amber-700',

                                            'administrative' =>
                                                'bg-slate-100 text-slate-700',
                                        ];

                                        $typeClass =
                                            $typeClasses[$department->type]
                                            ?? 'bg-slate-100 text-slate-700';

                                    @endphp


                                    <tr class="hover:bg-slate-50">

                                        <td class="whitespace-nowrap px-6 py-4">

                                            <div class="font-mono text-sm font-semibold text-slate-700">
                                                {{ $department->code }}
                                            </div>

                                        </td>


                                        <td class="px-6 py-4">

                                            <div class="font-semibold text-slate-900">
                                                {{ $department->name }}
                                            </div>

                                            @if ($department->remarks)

                                                <div class="mt-1 max-w-xl truncate text-xs text-slate-500">
                                                    {{ $department->remarks }}
                                                </div>

                                            @endif

                                        </td>


                                        <td class="whitespace-nowrap px-6 py-4">

                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $typeClass }}"
                                            >
                                                {{ ucfirst($department->type) }}
                                            </span>

                                        </td>


                                        <td class="whitespace-nowrap px-6 py-4">

                                            @if ($department->is_active)

                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">

                                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>

                                                    Active

                                                </span>

                                            @else

                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">

                                                    <span class="h-2 w-2 rounded-full bg-red-500"></span>

                                                    Inactive

                                                </span>

                                            @endif

                                        </td>


                                        <td class="whitespace-nowrap px-6 py-4 text-right">

                                            <a
                                                href="{{ route('admin.departments.edit', $department) }}"
                                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                            >
                                                Edit
                                            </a>

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    <div class="border-t border-slate-200 px-6 py-4">
                        {{ $departments->links() }}
                    </div>

                @else

                    <div class="px-6 py-16 text-center">

                        <div class="text-base font-semibold text-slate-700">
                            No departments found
                        </div>

                        <p class="mt-1 text-sm text-slate-500">
                            Create the first hospital department to begin configuring master data.
                        </p>

                        <a
                            href="{{ route('admin.departments.create') }}"
                            class="mt-5 inline-flex rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Add Department
                        </a>

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>