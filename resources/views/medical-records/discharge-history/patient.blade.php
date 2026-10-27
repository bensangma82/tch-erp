<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">
                Patient Discharge History
            </h2>
            <p class="text-sm text-slate-500 mt-1">
                {{ $patient->full_name }} · UHID: {{ $patient->uhid }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-5">
                <a href="{{ route('medical-records.discharge-history.index') }}"
                   class="text-indigo-700 hover:underline text-sm">
                    Back to Patient Search
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-5 border-b">
                    <h3 class="font-semibold text-slate-800">
                        Admission and Discharge Records
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="p-4 text-left">Admission No.</th>
                                <th class="p-4 text-left">Admitted</th>
                                <th class="p-4 text-left">Discharged</th>
                                <th class="p-4 text-left">Department</th>
                                <th class="p-4 text-left">Status</th>
                                <th class="p-4 text-right">Summary</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($admissions as $admission)
                                <tr>
                                    <td class="p-4 font-medium">
                                        {{ $admission->admission_no }}
                                    </td>
                                    <td class="p-4">
                                        {{ $admission->admitted_at?->format('d M Y') ?? '-' }}
                                    </td>
                                    <td class="p-4">
                                        {{ $admission->discharged_at?->format('d M Y') ?? '-' }}
                                    </td>
                                    <td class="p-4">
                                        {{ $admission->department?->name ?? '-' }}
                                    </td>
                                    <td class="p-4">
                                        {{ ucfirst($admission->status) }}
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="{{ route('medical-records.discharge-history.clinical-record', $admission) }}"
                                           class="text-emerald-700 font-medium hover:underline block mb-2">
                                            View Clinical Record
                                        </a>
                                        @if($admission->dischargeSummary)
                                            <a href="{{ route('ipd.discharge-summary.show', $admission) }}"
                                               class="text-indigo-700 font-medium hover:underline">
                                                View Summary
                                            </a>
                                        @else
                                            <span class="text-slate-400">
                                                No finalised summary
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-500">
                                        No admission history available.
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
