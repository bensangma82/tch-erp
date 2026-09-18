<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Laboratory Parameter Master
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Configure report parameters, units, reference ranges and critical limits for laboratory investigations.
                </p>
            </div>

            <a
                href="{{ route('services.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Service Master
            </a>

        </div>
    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <ul class="list-inside list-disc text-sm text-red-700">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-slate-50 px-6 py-5">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="text-base font-semibold text-gray-900">
                                Laboratory Investigations
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Select a laboratory service to configure its result parameters.
                            </p>

                        </div>

                        <div class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ $services->count() }} tests
                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-white">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Code
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Investigation
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Sample
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Report
                                </th>

                                <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Parameters
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @forelse ($services as $service)

                                <tr class="hover:bg-slate-50">

                                    <td class="px-5 py-4">

                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $service->code ?: '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $service->name }}
                                        </div>

                                        @if ($service->description)

                                            <div class="mt-1 max-w-md text-xs text-gray-500">
                                                {{ $service->description }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        @if ($service->requires_sample)

                                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                Required
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                                No
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        @if ($service->requires_report)

                                            <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                                Required
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                                No
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4 text-center">

                                        @if ($service->active_parameter_count > 0)

                                            <span class="inline-flex min-w-[36px] justify-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                                {{ $service->active_parameter_count }}
                                            </span>

                                        @else

                                            <span class="inline-flex min-w-[36px] justify-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                                0
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        @if ($service->is_active)

                                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Active
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4 text-right">

                                        <a
                                            href="{{ route('admin.laboratory-parameters.edit', $service) }}"
                                            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800"
                                        >
                                            Configure Parameters
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="7"
                                        class="px-6 py-14 text-center"
                                    >

                                        <div class="text-sm font-semibold text-gray-700">
                                            No laboratory services found.
                                        </div>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Add laboratory investigations in Service Master first.
                                        </p>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>