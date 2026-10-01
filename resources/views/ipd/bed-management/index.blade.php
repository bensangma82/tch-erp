<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between w-full">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Bed Management
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Operational bed availability, occupancy and housekeeping status
                </p>
            </div>

            <a
                href="{{ route('ipd.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Back to IPD
            </a>

        </div>
    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6 w-full">

        <div class="w-full space-y-6 px-4 sm:px-6 lg:px-8">




    {{-- Success --}}
    @if (session('success'))
        <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif


    {{-- Errors --}}
    @if ($errors->any())
        <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- Summary Cards --}}
    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">

        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Total
            </div>
            <div class="mt-1 text-2xl font-semibold text-slate-900">
                {{ $summary['total'] }}
            </div>
        </div>

        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-emerald-700">
                Available
            </div>
            <div class="mt-1 text-2xl font-semibold text-emerald-800">
                {{ $summary['available'] }}
            </div>
        </div>

        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-red-700">
                Occupied
            </div>
            <div class="mt-1 text-2xl font-semibold text-red-800">
                {{ $summary['occupied'] }}
            </div>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-amber-700">
                Cleaning
            </div>
            <div class="mt-1 text-2xl font-semibold text-amber-800">
                {{ $summary['cleaning'] }}
            </div>
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-blue-700">
                Reserved
            </div>
            <div class="mt-1 text-2xl font-semibold text-blue-800">
                {{ $summary['reserved'] }}
            </div>
        </div>

        <div class="rounded-lg border border-slate-300 bg-slate-100 p-4">
            <div class="text-xs font-medium uppercase tracking-wide text-slate-600">
                Maintenance
            </div>
            <div class="mt-1 text-2xl font-semibold text-slate-800">
                {{ $summary['maintenance'] }}
            </div>
        </div>

    </div>


    {{-- Filters --}}
    <div class="mb-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">

        <form
            method="GET"
            action="{{ route('ipd.bed-management.index') }}"
            class="grid grid-cols-1 gap-3 md:grid-cols-4"
        >

            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Ward
                </label>

                <select
                    name="ward_id"
                    class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">All Wards</option>

                    @foreach ($wards as $ward)
                        <option
                            value="{{ $ward->id }}"
                            @selected((string) request('ward_id') === (string) $ward->id)
                        >
                            {{ $ward->name }}
                        </option>
                    @endforeach
                </select>
            </div>


            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Status
                </label>

                <select
                    name="status"
                    class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">All Statuses</option>

                    <option value="available" @selected(request('status') === 'available')>
                        Available
                    </option>

                    <option value="occupied" @selected(request('status') === 'occupied')>
                        Occupied
                    </option>

                    <option value="cleaning" @selected(request('status') === 'cleaning')>
                        Cleaning
                    </option>

                    <option value="reserved" @selected(request('status') === 'reserved')>
                        Reserved
                    </option>

                    <option value="maintenance" @selected(request('status') === 'maintenance')>
                        Maintenance
                    </option>
                </select>
            </div>


            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">
                    Search
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Bed, patient or UHID"
                    class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>


            <div class="flex items-end gap-2">

                <button
                    type="submit"
                    class="inline-flex flex-1 items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700"
                >
                    Filter
                </button>

                <a
                    href="{{ route('ipd.bed-management.index') }}"
                    class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    {{-- Bed Table --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">
                    <tr>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Bed
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Ward / Room
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Patient
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Admission
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Status
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Action
                        </th>

                    </tr>
                </thead>


                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse ($beds as $bed)

                        @php
                            $allocation = $bed->activeAllocation;
                            $admission = $allocation?->admission;
                            $patient = $admission?->patient;

                            $statusClasses = match ($bed->status) {
                                'available' =>
                                    'bg-emerald-100 text-emerald-800',

                                'occupied' =>
                                    'bg-red-100 text-red-800',

                                'cleaning' =>
                                    'bg-amber-100 text-amber-800',

                                'reserved' =>
                                    'bg-blue-100 text-blue-800',

                                'maintenance' =>
                                    'bg-slate-200 text-slate-700',

                                default =>
                                    'bg-slate-100 text-slate-700',
                            };
                        @endphp


                        <tr class="hover:bg-slate-50">

                            {{-- Bed --}}
                            <td class="whitespace-nowrap px-4 py-4">

                                <div class="font-semibold text-slate-900">
                                    {{ $bed->bed_number }}
                                </div>

                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ ucfirst($bed->bed_type) }}
                                </div>

                            </td>


                            {{-- Ward / Room --}}
                            <td class="px-4 py-4">

                                <div class="text-sm font-medium text-slate-800">
                                    {{ $bed->ward?->name ?? '—' }}
                                </div>

                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ $bed->room?->name ?? 'General Ward' }}
                                </div>

                            </td>


                            {{-- Patient --}}
                            <td class="px-4 py-4">

                                @if ($patient)

                                    <div class="font-medium text-slate-900">
                                        {{ $patient->name }}
                                    </div>

                                    <div class="mt-0.5 text-xs text-slate-500">
                                        UHID:
                                        {{ $patient->uhid ?? '—' }}
                                    </div>

                                @else

                                    <span class="text-sm text-slate-400">
                                        Vacant
                                    </span>

                                @endif

                            </td>


                            {{-- Admission --}}
                            <td class="px-4 py-4">

                                @if ($admission)

                                    <div class="text-sm font-medium text-slate-800">
                                        {{ $admission->admission_no }}
                                    </div>

                                    @if ($admission->admitted_at)
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $admission->admitted_at->format('d M Y, h:i A') }}
                                        </div>
                                    @endif

                                @else

                                    <span class="text-sm text-slate-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Status --}}
                            <td class="px-4 py-4">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ ucfirst($bed->status) }}
                                </span>

                            </td>


                            {{-- Actions --}}
<td class="px-4 py-4 text-right">

    @if ($allocation && $admission)

        <a
            href="{{ route('ipd.show', $admission) }}"
            class="inline-flex items-center rounded-md bg-slate-800 px-3 py-2 text-xs font-medium text-white hover:bg-slate-900"
        >
            Open Admission
        </a>

    @else

        <div class="flex flex-wrap justify-end gap-2">

            @if ($bed->status === 'cleaning')

                <form
                    method="POST"
                    action="{{ route('ipd.bed-management.status', $bed) }}"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="status" value="available">

                    <button
                        type="submit"
                        class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                    >
                        Mark Clean & Available
                    </button>
                </form>

            @elseif ($bed->status === 'available')

                <form
                    method="POST"
                    action="{{ route('ipd.bed-management.status', $bed) }}"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="status" value="cleaning">

                    <button
                        type="submit"
                        class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100"
                    >
                        Needs Cleaning
                    </button>
                </form>


                <form
                    method="POST"
                    action="{{ route('ipd.bed-management.status', $bed) }}"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="status" value="reserved">

                    <button
                        type="submit"
                        class="rounded-md border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                    >
                        Reserve
                    </button>
                </form>


                <form
                    method="POST"
                    action="{{ route('ipd.bed-management.status', $bed) }}"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="status" value="maintenance">

                    <button
                        type="submit"
                        class="rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Maintenance
                    </button>
                </form>

            @elseif ($bed->status === 'reserved')

                <form
                    method="POST"
                    action="{{ route('ipd.bed-management.status', $bed) }}"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="status" value="available">

                    <button
                        type="submit"
                        class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                    >
                        Return to Available
                    </button>
                </form>


                <form
                    method="POST"
                    action="{{ route('ipd.bed-management.status', $bed) }}"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="status" value="maintenance">

                    <button
                        type="submit"
                        class="rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                    >
                        Maintenance
                    </button>
                </form>

            @elseif ($bed->status === 'maintenance')

                <form
                    method="POST"
                    action="{{ route('ipd.bed-management.status', $bed) }}"
                >
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="status" value="available">

                    <button
                        type="submit"
                        class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                    >
                        Return to Available
                    </button>
                </form>

            @endif

        </div>

    @endif

</td>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-sm text-slate-500"
                            >
                                No beds found for the selected filters.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

</x-app-layout>