<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                Transfer Asset
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                {{ $asset->asset_code }} — {{ $asset->asset_name }}
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <div class="mb-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
                    Current Assignment
                </h3>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <div class="text-xs font-medium uppercase text-gray-500">
                            Department
                        </div>

                        <div class="mt-1 text-sm font-semibold text-gray-900">
                            {{ $asset->department?->name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase text-gray-500">
                            Location
                        </div>

                        <div class="mt-1 text-sm font-semibold text-gray-900">
                            {{ $asset->location ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-medium uppercase text-gray-500">
                            Custodian
                        </div>

                        <div class="mt-1 text-sm font-semibold text-gray-900">
                            @if ($asset->custodian)
                                {{ trim(
                                    $asset->custodian->first_name . ' ' .
                                    $asset->custodian->middle_name . ' ' .
                                    $asset->custodian->last_name
                                ) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('admin.assets.movements.store', $asset) }}"
                class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                @csrf

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Movement Date
                        </label>

                        <input
                            type="date"
                            name="movement_date"
                            value="{{ old('movement_date', now()->format('Y-m-d')) }}"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Movement Type
                        </label>

                        <select
                            name="movement_type"
                            required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                        >
                            <option value="">Select movement type</option>

                            @foreach ([
                                'department_transfer' => 'Department Transfer',
                                'location_transfer' => 'Location Transfer',
                                'custodian_change' => 'Custodian Change',
                                'temporary_transfer' => 'Temporary Transfer',
                                'return' => 'Return',
                                'other' => 'Other',
                            ] as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    @selected(old('movement_type') === $value)
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            New Department
                        </label>

                        <select
                            name="to_department_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                        >
                            <option value="">No department</option>

                            @foreach ($departments as $department)
                                <option
                                    value="{{ $department->id }}"
                                    @selected(
                                        old(
                                            'to_department_id',
                                            $asset->department_id
                                        ) == $department->id
                                    )
                                >
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            New Location
                        </label>

                        <input
                            type="text"
                            name="to_location"
                            value="{{ old('to_location', $asset->location) }}"
                            maxlength="200"
                            placeholder="e.g. ICU, Dialysis Room 2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">
                            New Custodian
                        </label>

                        <select
                            name="to_custodian_employee_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                        >
                            <option value="">No individual custodian</option>

                            @foreach ($employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    @selected(
                                        old(
                                            'to_custodian_employee_id',
                                            $asset->custodian_employee_id
                                        ) == $employee->id
                                    )
                                >
                                    {{ $employee->employee_code }} -
                                    {{ $employee->first_name }}
                                    {{ $employee->middle_name }}
                                    {{ $employee->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Reason for Movement
                    </label>

                    <textarea
                        name="reason"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                    >{{ old('reason') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Remarks
                    </label>

                    <textarea
                        name="remarks"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
                    >{{ old('remarks') }}</textarea>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">

                    <a
                        href="{{ route('admin.assets.show', $asset) }}"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                    >
                        Record Transfer
                    </button>

                </div>

            </form>

        </div>
    </div>
</x-app-layout>