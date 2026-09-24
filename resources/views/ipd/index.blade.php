<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between w-full">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    IPD Patient Census
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Current admitted patients and inpatient bed census
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $statusLabels = [
            'admitted' => 'Admitted',
            'transferred' => 'Transferred',
            'discharged' => 'Discharged',
            'referred' => 'Referred',
            'death' => 'Death',
            'cancelled' => 'Cancelled',
        ];

        $statusClasses = [
            'admitted' => 'bg-emerald-50 text-emerald-700',
            'transferred' => 'bg-blue-50 text-blue-700',
            'discharged' => 'bg-slate-100 text-slate-700',
            'referred' => 'bg-indigo-50 text-indigo-700',
            'death' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    <div class="min-h-screen bg-slate-50 py-6 w-full">
        <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ERROR MESSAGE --}}
            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            {{-- FILTERS --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('ipd.index') }}"
                    class="grid gap-4 md:grid-cols-2 lg:grid-cols-6"
                >
                    {{-- SEARCH --}}
                    <div class="lg:col-span-2">
                        <label for="search" class="mb-2 block text-sm font-semibold text-slate-700">
                            Search
                        </label>
                        <input
                            id="search"
                            name="search"
                            type="text"
                            value="{{ request('search') }}"
                            placeholder="Admission no, UHID, MRD, name or phone..."
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        >
                    </div>

                    {{-- WARD --}}
                    <div>
                        <label for="ward_id" class="mb-2 block text-sm font-semibold text-slate-700">
                            Ward
                        </label>
                        <select
                            id="ward_id"
                            name="ward_id"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        >
                            <option value="">All Wards</option>
                            @foreach ($wards as $ward)
                                <option value="{{ $ward->id }}" @selected(request('ward_id') == $ward->id)>
                                    {{ $ward->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- DEPARTMENT --}}
                    <div>
                        <label for="department_id" class="mb-2 block text-sm font-semibold text-slate-700">
                            Department
                        </label>
                        <select
                            id="department_id"
                            name="department_id"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        >
                            <option value="">All Departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CONSULTANT --}}
                    <div>
                        <label for="consultant_id" class="mb-2 block text-sm font-semibold text-slate-700">
                            Consultant
                        </label>
                        <select
                            id="consultant_id"
                            name="consultant_id"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        >
                            <option value="">All Consultants</option>
                            @foreach ($consultants as $consultant)
                                @php
                                    $consultantName = trim(
                                        ($consultant->title ? $consultant->title . ' ' : '') .
                                        $consultant->first_name .
                                        ($consultant->middle_name ? ' ' . $consultant->middle_name : '') .
                                        ($consultant->last_name ? ' ' . $consultant->last_name : '')
                                    );
                                @endphp
                                <option value="{{ $consultant->id }}" @selected(request('consultant_id') == $consultant->id)>
                                    {{ $consultantName }}
                                    @if ($consultant->speciality)
                                        — {{ $consultant->speciality }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- STATUS --}}
                    <div>
                        <label for="status" class="mb-2 block text-sm font-semibold text-slate-700">
                            Status
                        </label>
                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        >
                            <option value="">Current Admissions</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- FILTER ACTIONS --}}
                    <div class="flex items-end gap-2 lg:col-span-6">
                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition"
                        >
                            Apply Filters
                        </button>
                        <a
                            href="{{ route('ipd.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            {{-- CENSUS TABLE --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm w-full">
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-slate-900">
                                Inpatient Census
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Current inpatient admissions are shown by default
                            </p>
                        </div>

                        <div class="text-sm font-semibold text-slate-600">
                            {{ number_format($admissions->total()) }} admission{{ $admissions->total() === 1 ? '' : 's' }}
                        </div>
                    </div>
                </div>

                <div class="w-full">
                    <table class="w-full table-fixed divide-y divide-slate-200 text-sm">
                        <colgroup>
                            <col class="w-[24%]">
                            <col class="w-[22%]">
                            <col class="w-[17%]">
                            <col class="w-[14%]">
                            <col class="w-[9%]">
                            <col class="w-[14%]">
                        </colgroup>

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Patient / Admission
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Clinical
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Ward / Bed
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Admission
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>
                                <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($admissions as $admission)
                                @php
                                    $patient = $admission->patient;
                                    $statusLabel = $statusLabels[$admission->status] ?? ucwords(str_replace('_', ' ', $admission->status));
                                    $statusClass = $statusClasses[$admission->status] ?? 'bg-slate-100 text-slate-700';
                                    $consultant = $admission->consultant;

                                    $consultantName = $consultant
                                        ? trim(
                                            ($consultant->title ? $consultant->title . ' ' : '') .
                                            $consultant->first_name .
                                            ($consultant->middle_name ? ' ' . $consultant->middle_name : '') .
                                            ($consultant->last_name ? ' ' . $consultant->last_name : '')
                                        )
                                        : '—';

                                    $currentBed = $admission->currentBedAllocation?->bed ?? $admission->bed;
                                @endphp

                                <tr class="hover:bg-slate-50 transition-colors">
                                    {{-- PATIENT / ADMISSION --}}
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-semibold text-slate-900 leading-5">
                                            {{ $patient?->full_name ?? 'Unknown Patient' }}
                                        </div>

                                        <div class="mt-1 text-xs font-mono text-slate-600 break-words">
                                            {{ $admission->admission_no }}
                                        </div>

                                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] leading-4 text-slate-500">
                                            @if ($patient?->uhid)
                                                <span>UHID: {{ $patient->uhid }}</span>
                                            @endif
                                            @if ($patient?->mrd_number)
                                                <span>MRD: {{ $patient->mrd_number }}</span>
                                            @endif
                                            @if ($patient?->phone)
                                                <span>Mob: {{ $patient->phone }}</span>
                                            @endif
                                            @if (! $patient)
                                                <span>Patient ID: {{ $admission->patient_id }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- CLINICAL --}}
                                    <td class="px-4 py-3 align-top text-slate-700">
                                        <div class="font-semibold text-slate-900 break-words">
                                            {{ $admission->department?->name ?? '—' }}
                                        </div>
                                        <div class="mt-1 text-xs font-medium text-slate-700 break-words">
                                            {{ $consultantName }}
                                        </div>
                                        @if ($consultant?->speciality)
                                            <div class="mt-0.5 text-[11px] leading-4 text-slate-400 break-words">
                                                {{ $consultant->speciality }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- WARD / BED --}}
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-semibold text-slate-900 break-words">
                                            {{ $currentBed?->ward?->name ?? '—' }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            Bed: {{ $currentBed?->bed_number ?? '—' }}
                                        </div>
                                    </td>

                                    {{-- ADMISSION DETAILS --}}
                                    <td class="px-4 py-3 align-top text-slate-700">
                                        <div class="font-medium">
                                            {{ $admission->admitted_at?->format('d M Y') ?? '—' }}
                                        </div>
                                        <div class="mt-0.5 text-[11px] text-slate-400">
                                            {{ $admission->admitted_at?->format('h:i A') ?? '—' }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500 break-words">
                                            {{ $admission->source_type ? ucwords(str_replace('_', ' ', $admission->source_type)) : '—' }}
                                        </div>
                                    </td>

                                    {{-- STATUS --}}
                                    <td class="px-3 py-3 align-top text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    {{-- ACTIONS --}}
                                    <td class="px-3 py-3 align-top">
                                        <div class="flex flex-col items-stretch gap-1.5">
                                            <a
                                                href="{{ route('ipd.show', $admission) }}"
                                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                                            >
                                                Open
                                            </a>

                                            <a
                                                href="{{ route('ip-billing.show', $admission) }}"
                                                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-2 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-slate-800"
                                            >
                                                IP Billing
                                            </a>

                                            @if (in_array($admission->status, ['admitted', 'transferred'], true))
                                                <a
                                                    href="{{ route('pharmacy.dispensing.create', ['admission_id' => $admission->id]) }}"
                                                    class="inline-flex items-center justify-center rounded-lg bg-violet-600 px-2 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-violet-700"
                                                >
                                                    Dispense
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="text-sm font-semibold text-slate-700">
                                            No inpatient admissions found
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            Current admitted patients will appear here.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($admissions->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $admissions->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>