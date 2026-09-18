<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Nursing Station
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Today's OPD patients awaiting nursing assessment.
                </p>

            </div>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="font-semibold text-gray-800">
                                OPD Nursing Queue
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Record vitals before the patient proceeds to the doctor.
                            </p>

                        </div>

                        <div class="text-sm text-gray-600">
                            Total:
                            <span class="font-semibold text-gray-900">
                                {{ $encounters->count() }}
                            </span>
                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-white">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Queue
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    UHID / MRD
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Patient
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Department
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Doctor
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Vitals
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @forelse ($encounters as $encounter)

                                @php
                                    $latestVital = $encounter->vitals
                                        ->sortByDesc('recorded_at')
                                        ->first();
                                @endphp

                                <tr class="hover:bg-gray-50">

                                    <td class="px-5 py-4">

                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-base font-bold text-white">
                                            {{ $encounter->queue_number }}
                                        </div>

                                    </td>


                                    <td class="min-w-[180px] px-5 py-4">

                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $encounter->patient->uhid }}
                                        </div>

                                        <div class="mt-1 text-xs text-gray-500">
                                            MRD:
                                            {{ $encounter->patient->mrd_number ?: '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $encounter->patient->full_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ $encounter->patient->age !== null ? $encounter->patient->age . ' yrs' : 'Age —' }}
                                            /
                                            {{ $encounter->patient->sex ?: '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $encounter->department?->name ?? '—' }}
                                    </td>


                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                                    </td>


                                    <td class="px-5 py-4">

                                        @if ($latestVital)

                                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                Recorded
                                            </span>

                                            <div class="mt-2 text-xs text-gray-500">
                                                {{ $latestVital->recorded_at?->format('h:i A') ?? '—' }}
                                            </div>

                                        @else

                                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                                Pending
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4">

                                        <a
    href="{{ route('nursing.vitals.create', $encounter) }}"
    class="inline-flex rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
>
    {{ $latestVital ? 'Update Vitals' : 'Record Vitals' }}
</a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="7"
                                        class="px-6 py-14 text-center text-sm text-gray-500"
                                    >
                                        No OPD patients registered today.
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