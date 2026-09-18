<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Patient Registry
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Search and manage registered patients.
                </p>
            </div>

            <a href="{{ route('patients.create') }}"
               class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Register Patient
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif
<div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

    <form method="GET"
          action="{{ route('patients.index') }}"
          class="flex flex-col gap-3 sm:flex-row">

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by UHID, name, phone, MHIS or ABHA"
            class="flex-1 rounded-lg border-gray-300"
        >

        <button
            type="submit"
            class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
        >
            Search
        </button>

        @if (request('search'))
            <a
                href="{{ route('patients.index') }}"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Clear
            </a>
        @endif

    </form>

</div>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="font-semibold text-gray-800">
                        Registered Patients
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    UHID
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Patient
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Sex
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Age / DOB
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Phone
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">

                            @forelse ($patients as $patient)

                                <tr class="hover:bg-gray-50">

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900">
                                        {{ $patient->uhid }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $patient->full_name }}
                                        </div>

                                        @if ($patient->locality || $patient->district)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ collect([$patient->locality, $patient->district])
                                                    ->filter()
                                                    ->implode(', ') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $patient->sex }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">

                                        @if ($patient->date_of_birth)
                                            {{ $patient->date_of_birth->format('d-m-Y') }}
                                        @elseif ($patient->age !== null)
                                            {{ $patient->age }} years
                                        @else
                                            —
                                        @endif

                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $patient->phone ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right">

                                        <a href="{{ route('patients.show', $patient) }}"
                                           class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            View
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="6"
                                        class="px-6 py-12 text-center text-sm text-gray-500">

                                        No patients have been registered yet.

                                    </td>
                                </tr>

                            @endforelse

                        </tbody>
                    </table>
                </div>

                @if ($patients->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $patients->links() }}
                    </div>
                @endif

            </div>

        </div>
    </div>

</x-app-layout>