<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">
                Discharge Summary History
            </h2>
            <p class="text-sm text-slate-500 mt-1">
                Search patient records and previous hospital admissions.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <form method="GET"
                      action="{{ route('medical-records.discharge-history.index') }}"
                      class="flex flex-col sm:flex-row gap-3">

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        minlength="2"
                        placeholder="Search UHID, MRD number, name or phone"
                        class="flex-1 rounded-lg border-slate-300"
                    >

                    <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Search Patient
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-5 border-b">
                    <h3 class="font-semibold text-slate-800">
                        Patient Records
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="p-4 text-left">UHID</th>
                                <th class="p-4 text-left">Patient</th>
                                <th class="p-4 text-left">Sex / Age</th>
                                <th class="p-4 text-left">Phone</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($patients as $patient)
                                <tr>
                                    <td class="p-4">{{ $patient->uhid }}</td>
                                    <td class="p-4 font-medium">
                                        {{ $patient->full_name }}
                                    </td>
                                    <td class="p-4">
                                        {{ $patient->sex }} /
                                        {{ $patient->age ?? '-' }}
                                    </td>
                                    <td class="p-4">{{ $patient->phone }}</td>
                                    <td class="p-4 text-right">
                                        <a href="{{ route('medical-records.discharge-history.patient', $patient) }}"
                                           class="text-indigo-700 font-medium hover:underline">
                                            View History
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-500">
                                        No matching patient records.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4">
                    {{ $patients->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
