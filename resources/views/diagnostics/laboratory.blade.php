<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between w-full">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Laboratory Worklist
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Paid and authorized laboratory investigations.
                </p>
            </div>

            <a
                href="{{ route('imaging.index') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 shadow-sm transition"
            >
                Imaging Worklist
            </a>
        </div>
    </x-slot>

    <div class="py-6 w-full">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ERROR MESSAGE --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                    <ul class="list-inside list-disc text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm w-full">
                {{-- HEADER --}}
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-800">
                                Laboratory Orders
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Only investigations released after payment or authorization are shown.
                            </p>
                        </div>

                        <div class="text-sm text-gray-600">
                            Total:
                            <span class="font-semibold text-gray-900">
                                {{ $items->count() }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- FULL-WIDTH RESPONSIVE TABLE --}}
                <div class="overflow-x-auto w-full">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Order / Date
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Patient (UHID)
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Investigation
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Department
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Doctor
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Order Status
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Lab Status
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($items as $item)
                                @php
                                    $order = $item->serviceOrder;
                                    $patient = $order?->patient;
                                    $encounter = $order?->encounter;
                                    $admission = $order?->admission;
                                    $result = $item->diagnosticResult;
                                    $sample = $item->diagnosticSample;

                                    $requiresSample = (bool) $item->requires_sample;
                                    $sampleCollected = $sample && $sample->status === 'collected';
                                    $sampleRejected = $sample && $sample->status === 'rejected';

                                    // Support both IP and OPD source models
                                    $departmentName = $admission?->department?->name ?? $encounter?->department?->name ?? '—';
                                    $doctorName = $admission?->consultant?->full_name 
                                        ?? $admission?->consultant?->name 
                                        ?? $encounter?->doctor?->full_name 
                                        ?? $encounter?->doctor?->name 
                                        ?? 'Unassigned';
                                    $speciality = $admission?->consultant?->speciality ?? $encounter?->doctor?->speciality;
                                @endphp

                                <tr class="hover:bg-gray-50 transition-colors">
                                    {{-- ORDER / DATE --}}
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900 font-mono">
                                            {{ $order?->order_no ?? '—' }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ $order?->ordered_at?->format('d M Y, h:i A') ?? '—' }}
                                        </div>
                                    </td>

                                    {{-- PATIENT (UHID) --}}
                                    <td class="px-4 py-3.5">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $patient?->full_name ?? '—' }}
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center gap-1.5 whitespace-nowrap">
                                            <span>{{ $patient?->age !== null ? $patient->age . ' yrs' : 'Age —' }} / {{ $patient?->sex ? ucfirst($patient->sex) : '—' }}</span>
                                            <span class="text-gray-300">•</span>
                                            <span class="font-mono text-gray-600">{{ $patient?->uhid ?? '—' }}</span>
                                        </div>
                                    </td>

                                    {{-- INVESTIGATION & SPECIMEN STATUS --}}
                                    <td class="px-4 py-3.5">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $item->service_name }}
                                        </div>
                                        <div class="text-xs font-mono text-gray-500">
                                            {{ $item->service_code }}
                                        </div>

                                        @if ($requiresSample)
                                            <div class="mt-1">
                                                @if ($sampleCollected)
                                                    <span class="inline-flex items-center text-[11px] font-semibold text-emerald-700">
                                                        Sample: {{ $sample->sample_no }} ({{ $sample->specimen_type ?? 'Collected' }})
                                                    </span>
                                                @elseif ($sampleRejected)
                                                    <span class="inline-flex items-center text-[11px] font-semibold text-red-600">
                                                        Sample Rejected {{ $sample->sample_no ? '(' . $sample->sample_no . ')' : '' }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center text-[11px] font-medium text-amber-600">
                                                        Sample Required
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="mt-1 text-[11px] text-gray-400">
                                                No sample required
                                            </div>
                                        @endif
                                    </td>

                                    {{-- DEPARTMENT --}}
                                    <td class="px-4 py-3.5 text-sm text-gray-700 whitespace-nowrap">
                                        {{ $departmentName }}
                                    </td>

                                    {{-- DOCTOR --}}
                                    <td class="px-4 py-3.5">
                                        <div class="text-sm text-gray-700 whitespace-nowrap">
                                            {{ $doctorName }}
                                        </div>
                                        @if ($speciality)
                                            <div class="text-xs text-gray-400 whitespace-nowrap">
                                                {{ $speciality }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- ORDER STATUS --}}
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if ($order?->status === 'paid')
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                                                Paid
                                            </span>
                                        @elseif ($order?->status === 'authorized')
                                            <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                                Authorized
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 capitalize">
                                                {{ str_replace('_', ' ', $order?->status ?? 'unknown') }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- LAB STATUS --}}
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        @if ($item->status === 'ordered')
                                            @if ($requiresSample)
                                                @if ($sampleCollected)
                                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                                        Sample Collected
                                                    </span>
                                                @elseif ($sampleRejected)
                                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">
                                                        Sample Rejected
                                                    </span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                                        Awaiting Sample
                                                    </span>
                                                @endif
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                                    Pending
                                                </span>
                                            @endif
                                        @elseif ($item->status === 'in_process')
                                            <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                                In Process
                                            </span>
                                        @elseif ($item->status === 'completed')
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                                                Completed
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 capitalize">
                                                {{ str_replace('_', ' ', $item->status) }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- ACTION --}}
                                    <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                        <div class="flex flex-col items-end gap-1">
                                            {{-- ORDERED --}}
                                            @if ($item->status === 'ordered')
                                                @if ($requiresSample)
                                                    @if (! $sample || $sampleRejected)
                                                        <a
                                                            href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                            class="inline-flex rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition"
                                                        >
                                                            {{ $sampleRejected ? 'Recollect Sample' : 'Collect Sample' }}
                                                        </a>
                                                    @elseif ($sampleCollected)
                                                        <form
                                                            method="POST"
                                                            action="{{ route('diagnostics.items.status', $item) }}"
                                                        >
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="status" value="in_process">
                                                            <button
                                                                type="submit"
                                                                class="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 transition"
                                                            >
                                                                Start Processing
                                                            </button>
                                                        </form>
                                                        <a
                                                            href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                            class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-900"
                                                        >
                                                            View Sample
                                                        </a>
                                                    @else
                                                        <a
                                                            href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                            class="inline-flex rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition"
                                                        >
                                                            Manage Sample
                                                        </a>
                                                    @endif
                                                @else
                                                    <form
                                                        method="POST"
                                                        action="{{ route('diagnostics.items.status', $item) }}"
                                                    >
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="in_process">
                                                        <button
                                                            type="submit"
                                                            class="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 transition"
                                                        >
                                                            Start Processing
                                                        </button>
                                                    </form>
                                                @endif

                                            {{-- IN PROCESS --}}
                                            @elseif ($item->status === 'in_process')
                                                <a
                                                    href="{{ route('diagnostics.items.result.edit', $item) }}"
                                                    class="inline-flex rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition"
                                                >
                                                    Enter Result
                                                </a>
                                                @if ($requiresSample && $sampleCollected)
                                                    <div class="text-[11px] text-gray-400 font-mono">
                                                        {{ $sample->sample_no }}
                                                    </div>
                                                @endif

                                            {{-- COMPLETED --}}
                                            @elseif ($item->status === 'completed')
                                                @if ($result)
                                                    <a
                                                        href="{{ route('diagnostics.items.result.show', $item) }}"
                                                        class="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 transition"
                                                    >
                                                        View Result
                                                    </a>
                                                @else
                                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                                                        Completed
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-14 text-center">
                                        <div class="text-sm font-medium text-gray-700">
                                            No laboratory investigations are waiting.
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Paid or authorized laboratory orders will appear here automatically.
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