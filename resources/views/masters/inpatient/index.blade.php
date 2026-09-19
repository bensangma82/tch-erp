<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Inpatient Setup
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Manage wards, rooms / cabins and hospital beds.
                </p>
            </div>
        </div>
    </x-slot>


    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif


            {{-- VALIDATION ERRORS --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                    <div class="font-semibold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            {{-- SUMMARY --}}
            <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-gray-500">
                        Wards
                    </div>

                    <div class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $wards->count() }}
                    </div>
                </div>


                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-gray-500">
                        Rooms / Cabins
                    </div>

                    <div class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $rooms->count() }}
                    </div>
                </div>


                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-gray-500">
                        Total Beds
                    </div>

                    <div class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $beds->count() }}
                    </div>
                </div>


                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-sm font-medium text-gray-500">
                        Occupied Beds
                    </div>

                    <div class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $beds->filter(fn ($bed) => $bed->activeAllocation)->count() }}
                    </div>
                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- WARD MASTER --}}
            {{-- ========================================================= --}}

            <div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Ward Master
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Create and manage inpatient wards.
                    </p>
                </div>


                {{-- ADD WARD --}}
                <form
                    method="POST"
                    action="{{ route('inpatient-master.wards.store') }}"
                    class="border-b border-gray-200 p-6"
                >
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Ward Code
                            </label>

                            <input
                                type="text"
                                name="code"
                                required
                                placeholder="e.g. MW"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Ward Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                required
                                placeholder="e.g. Male Ward"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Ward Type
                            </label>

                            <select
                                name="ward_type"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select</option>
                                <option value="general">General</option>
                                <option value="icu">ICU</option>
                                <option value="special">Special</option>
                                <option value="private">Private</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Remarks
                            </label>

                            <input
                                type="text"
                                name="remarks"
                                placeholder="Optional"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div class="flex items-end">
                            <button
                                type="submit"
                                class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                + Add Ward
                            </button>
                        </div>

                    </div>
                </form>


                {{-- WARD LIST --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Code
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Ward
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Type
                                </th>

                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Rooms
                                </th>

                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Beds
                                </th>

                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">

                            @forelse ($wards as $ward)

                                <tr class="hover:bg-gray-50">

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-700">
                                        {{ $ward->code }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $ward->name }}
                                        </div>

                                        @if ($ward->remarks)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $ward->remarks }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ ucfirst($ward->ward_type) }}
                                    </td>

                                    <td class="px-6 py-4 text-center text-sm font-semibold text-gray-700">
                                        {{ $ward->rooms->count() }}
                                    </td>

                                    <td class="px-6 py-4 text-center text-sm font-semibold text-gray-700">
                                        {{ $ward->beds->count() }}
                                    </td>

                                    <td class="px-6 py-4 text-center">

                                        @if ($ward->is_active)
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                                Inactive
                                            </span>
                                        @endif

                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button type="button"
                                            onclick="document.getElementById('ward-edit-{{ $ward->id }}').classList.toggle('hidden')"
                                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                            Edit
                                        </button>
                                    </td>

                                </tr>

                                <tr id="ward-edit-{{ $ward->id }}" class="hidden bg-slate-50">
                                    <td colspan="7" class="px-6 py-5">
                                        <form method="POST" action="{{ route('inpatient-master.wards.update', $ward) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Ward Code</label>
                                                    <input type="text" name="code" value="{{ $ward->code }}" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Ward Name</label>
                                                    <input type="text" name="name" value="{{ $ward->name }}" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Ward Type</label>
                                                    <select name="ward_type" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @foreach (['general' => 'General', 'icu' => 'ICU', 'special' => 'Special', 'private' => 'Private', 'emergency' => 'Emergency'] as $value => $label)
                                                            <option value="{{ $value }}" @selected($ward->ward_type === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
                                                    <select name="is_active" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="1" @selected($ward->is_active)>Active</option>
                                                        <option value="0" @selected(!$ward->is_active)>Inactive</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Remarks</label>
                                                    <input type="text" name="remarks" value="{{ $ward->remarks }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                </div>
                                            </div>
                                            <div class="mt-4 flex justify-end gap-2">
                                                <button type="button" onclick="document.getElementById('ward-edit-{{ $ward->id }}').classList.add('hidden')" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                                                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">Save Changes</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-8 text-center text-sm text-gray-500"
                                    >
                                        No wards found.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>
                    </table>
                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- ROOM / CABIN MASTER --}}
            {{-- ========================================================= --}}

            <div class="mb-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Room / Cabin Master
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Create cabins and rooms within a ward.
                    </p>
                </div>


                {{-- ADD ROOM --}}
                <form
                    method="POST"
                    action="{{ route('inpatient-master.rooms.store') }}"
                    class="border-b border-gray-200 p-6"
                >
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Ward
                            </label>

                            <select
                                name="ward_id"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select Ward</option>

                                @foreach ($wards->where('is_active', true) as $ward)
                                    <option value="{{ $ward->id }}">
                                        {{ $ward->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Code
                            </label>

                            <input
                                type="text"
                                name="code"
                                required
                                placeholder="e.g. C01"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                required
                                placeholder="e.g. Cabin 1"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Type
                            </label>

                            <select
                                name="room_type"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="cabin">Cabin</option>
                                <option value="private_room">Private Room</option>
                                <option value="semi_private">Semi Private</option>
                                <option value="isolation">Isolation</option>
                                <option value="other">Other</option>
                            </select>
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Floor
                            </label>

                            <input
                                type="text"
                                name="floor"
                                placeholder="e.g. 1st Floor"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div class="flex items-end">
                            <button
                                type="submit"
                                class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                + Add Room
                            </button>
                        </div>

                    </div>
                </form>


                {{-- ROOM LIST --}}
                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Code
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Room / Cabin
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Ward
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Type
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Floor
                                </th>

                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Beds
                                </th>

                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">

                            @forelse ($rooms as $room)

                                <tr class="hover:bg-gray-50">

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-700">
                                        {{ $room->code }}
                                    </td>

                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        {{ $room->name }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $room->ward?->name ?? '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ ucwords(str_replace('_', ' ', $room->room_type)) }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $room->floor ?: '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-center text-sm font-semibold text-gray-700">
                                        {{ $room->beds->count() }}
                                    </td>

                                    <td class="px-6 py-4 text-center">

                                        @if ($room->is_active)
                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                                Inactive
                                            </span>
                                        @endif

                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            type="button"
                                            onclick="document.getElementById('room-edit-{{ $room->id }}').classList.toggle('hidden')"
                                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                        >
                                            Edit
                                        </button>
                                    </td>

                                </tr>

                                <tr id="room-edit-{{ $room->id }}" class="hidden bg-slate-50">
                                    <td colspan="8" class="px-6 py-5">
                                        <form method="POST" action="{{ route('inpatient-master.rooms.update', $room) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Ward</label>
                                                    <select name="ward_id" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @foreach ($wards as $wardOption)
                                                            <option value="{{ $wardOption->id }}" @selected($room->ward_id === $wardOption->id)>
                                                                {{ $wardOption->name }}{{ $wardOption->is_active ? '' : ' (Inactive)' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Code</label>
                                                    <input type="text" name="code" value="{{ $room->code }}" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Room / Cabin Name</label>
                                                    <input type="text" name="name" value="{{ $room->name }}" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Type</label>
                                                    <select name="room_type" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @foreach (['cabin' => 'Cabin', 'private_room' => 'Private Room', 'semi_private' => 'Semi Private', 'isolation' => 'Isolation', 'other' => 'Other'] as $value => $label)
                                                            <option value="{{ $value }}" @selected($room->room_type === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Floor</label>
                                                    <input type="text" name="floor" value="{{ $room->floor }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
                                                    <select name="is_active" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="1" @selected($room->is_active)>Active</option>
                                                        <option value="0" @selected(!$room->is_active)>Inactive</option>
                                                    </select>
                                                </div>

                                                <div class="md:col-span-2">
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Remarks</label>
                                                    <input type="text" name="remarks" value="{{ $room->remarks }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                </div>
                                            </div>

                                            <div class="mt-4 flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onclick="document.getElementById('room-edit-{{ $room->id }}').classList.add('hidden')"
                                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                                >
                                                    Cancel
                                                </button>

                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                                >
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="8"
                                        class="px-6 py-8 text-center text-sm text-gray-500"
                                    >
                                        No rooms or cabins have been added.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- BED MASTER --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                    <h3 class="text-lg font-semibold text-gray-900">
                        Bed Master
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Add beds directly to a ward or assign them to a room / cabin.
                    </p>

                </div>


                {{-- ADD BED --}}
                <form
                    method="POST"
                    action="{{ route('inpatient-master.beds.store') }}"
                    class="border-b border-gray-200 p-6"
                >
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-6">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Ward
                            </label>

                            <select
                                id="bed_ward_id"
                                name="ward_id"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select Ward</option>

                                @foreach ($wards->where('is_active', true) as $ward)
                                    <option value="{{ $ward->id }}">
                                        {{ $ward->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Room / Cabin
                            </label>

                            <select
                                id="bed_room_id"
                                name="room_id"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">General Ward / No Room</option>

                                @foreach ($rooms->where('is_active', true) as $room)
                                    <option
                                        value="{{ $room->id }}"
                                        data-ward="{{ $room->ward_id }}"
                                    >
                                        {{ $room->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Bed Number
                            </label>

                            <input
                                type="text"
                                name="bed_number"
                                required
                                placeholder="e.g. M-01"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Bed Type
                            </label>

                            <select
                                name="bed_type"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="general">General</option>
                                <option value="semi_private">Semi Private</option>
                                <option value="private">Private</option>
                                <option value="icu">ICU</option>
                                <option value="isolation">Isolation</option>
                            </select>
                        </div>


                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Remarks
                            </label>

                            <input
                                type="text"
                                name="remarks"
                                placeholder="Optional"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>


                        <div class="flex items-end">
                            <button
                                type="submit"
                                class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                + Add Bed
                            </button>
                        </div>

                    </div>
                </form>


                {{-- BED LIST --}}
                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Bed
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Ward
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Room / Cabin
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Type
                                </th>

                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Occupancy
                                </th>

                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Master Status
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>


                        <tbody class="divide-y divide-gray-100">

                            @forelse ($beds as $bed)

                                <tr class="hover:bg-gray-50">

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-900">
                                        {{ $bed->bed_number }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $bed->ward?->name ?? '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ $bed->room?->name ?? 'General Ward' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ ucwords(str_replace('_', ' ', $bed->bed_type)) }}
                                    </td>

                                    <td class="px-6 py-4 text-center">

                                        @if ($bed->activeAllocation)

                                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                                Occupied
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                                Available
                                            </span>

                                        @endif

                                    </td>

                                    <td class="px-6 py-4 text-center">

                                        @if ($bed->is_active)

                                            <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                Active
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            type="button"
                                            onclick="document.getElementById('bed-edit-{{ $bed->id }}').classList.toggle('hidden')"
                                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                        >
                                            Edit
                                        </button>
                                    </td>

                                </tr>

                                <tr id="bed-edit-{{ $bed->id }}" class="hidden bg-slate-50">
                                    <td colspan="7" class="px-6 py-5">
                                        <form method="POST" action="{{ route('inpatient-master.beds.update', $bed) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Ward</label>
                                                    <select
                                                        name="ward_id"
                                                        required
                                                        class="bed-edit-ward w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        data-bed-id="{{ $bed->id }}"
                                                    >
                                                        @foreach ($wards as $wardOption)
                                                            <option value="{{ $wardOption->id }}" @selected($bed->ward_id === $wardOption->id)>
                                                                {{ $wardOption->name }}{{ $wardOption->is_active ? '' : ' (Inactive)' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Room / Cabin</label>
                                                    <select
                                                        name="room_id"
                                                        class="bed-edit-room w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        data-bed-id="{{ $bed->id }}"
                                                        data-selected-room="{{ $bed->room_id ?? '' }}"
                                                    >
                                                        <option value="">General Ward / No Room</option>

                                                        @foreach ($rooms as $roomOption)
                                                            <option
                                                                value="{{ $roomOption->id }}"
                                                                data-ward="{{ $roomOption->ward_id }}"
                                                                @selected($bed->room_id === $roomOption->id)
                                                            >
                                                                {{ $roomOption->name }}{{ $roomOption->is_active ? '' : ' (Inactive)' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Bed Number</label>
                                                    <input
                                                        type="text"
                                                        name="bed_number"
                                                        value="{{ $bed->bed_number }}"
                                                        required
                                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Bed Type</label>
                                                    <select name="bed_type" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        @foreach (['general' => 'General', 'semi_private' => 'Semi Private', 'private' => 'Private', 'icu' => 'ICU', 'isolation' => 'Isolation'] as $value => $label)
                                                            <option value="{{ $value }}" @selected($bed->bed_type === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
                                                    <select name="is_active" required class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        <option value="1" @selected($bed->is_active)>Active</option>
                                                        <option value="0" @selected(!$bed->is_active)>Inactive</option>
                                                    </select>

                                                    @if ($bed->activeAllocation)
                                                        <p class="mt-1 text-xs font-medium text-red-600">
                                                            Occupied bed: location and status are protected.
                                                        </p>
                                                    @endif
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Remarks</label>
                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        value="{{ $bed->remarks }}"
                                                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>
                                            </div>

                                            <div class="mt-4 flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onclick="document.getElementById('bed-edit-{{ $bed->id }}').classList.add('hidden')"
                                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                                >
                                                    Cancel
                                                </button>

                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                                >
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-8 text-center text-sm text-gray-500"
                                    >
                                        No beds found.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>
    </div>


    {{-- FILTER ROOMS BY SELECTED WARD --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const wardSelect = document.getElementById('bed_ward_id');
            const roomSelect = document.getElementById('bed_room_id');

            if (!wardSelect || !roomSelect) {
                return;
            }

            const roomOptions = Array.from(roomSelect.options);

            function filterRooms() {

                const wardId = wardSelect.value;

                roomSelect.value = '';

                roomOptions.forEach(function (option) {

                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden =
                        !wardId ||
                        option.dataset.ward !== wardId;
                });
            }

            wardSelect.addEventListener('change', filterRooms);

            filterRooms();

            /*
             * Filter Room / Cabin options inside each Bed Edit form.
             */
            document.querySelectorAll('.bed-edit-ward').forEach(function (editWardSelect) {

                const bedId = editWardSelect.dataset.bedId;
                const editRoomSelect = document.querySelector(
                    '.bed-edit-room[data-bed-id="' + bedId + '"]'
                );

                if (!editRoomSelect) {
                    return;
                }

                const editRoomOptions = Array.from(editRoomSelect.options);

                function filterEditRooms(keepCurrentSelection = false) {

                    const wardId = editWardSelect.value;
                    const currentRoomId = keepCurrentSelection
                        ? editRoomSelect.dataset.selectedRoom
                        : '';

                    editRoomOptions.forEach(function (option) {

                        if (!option.value) {
                            option.hidden = false;
                            return;
                        }

                        option.hidden =
                            !wardId ||
                            option.dataset.ward !== wardId;
                    });

                    if (
                        currentRoomId &&
                        editRoomOptions.some(function (option) {
                            return option.value === currentRoomId &&
                                !option.hidden;
                        })
                    ) {
                        editRoomSelect.value = currentRoomId;
                    } else {
                        editRoomSelect.value = '';
                    }
                }

                editWardSelect.addEventListener('change', function () {
                    filterEditRooms(false);
                });

                filterEditRooms(true);
            });
        });
    </script>

</x-app-layout>