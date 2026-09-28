<x-app-layout>
    <x-slot name="header">

        <div class="flex items-center justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ $asset->asset_name }}
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $asset->asset_code }}
                </p>
            </div>

            <div class="flex gap-2">
                <a
                    href="{{ route('admin.assets.index') }}"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                >
                    Asset Register
                </a>

                          <a
    href="{{ route('admin.assets.movements.create', $asset) }}"
    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
>
    Transfer Asset
</a>


                <a
                    href="{{ route('admin.assets.edit', $asset) }}"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                >
                    Edit Asset
                </a>
            </div>

        </div>

    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-6xl space-y-5 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase text-gray-500">
                        Category
                    </div>

                    <div class="mt-2 font-semibold text-gray-900">
                        {{ $asset->category?->name ?? '—' }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase text-gray-500">
                        Department
                    </div>

                    <div class="mt-2 font-semibold text-gray-900">
                        {{ $asset->department?->name ?? '—' }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase text-gray-500">
                        Status
                    </div>

                    <div class="mt-2 font-semibold text-gray-900">
                        {{ ucwords(str_replace('_', ' ', $asset->status)) }}
                    </div>
                </div>

            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <h3 class="mb-5 text-lg font-semibold text-gray-900">
                    Asset Details
                </h3>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-3">

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Manufacturer
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->manufacturer ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Model
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->model ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Serial Number
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->serial_number ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Location
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->location ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Custodian
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->custodian
                                ? trim(
                                    $asset->custodian->first_name . ' ' .
                                    $asset->custodian->middle_name . ' ' .
                                    $asset->custodian->last_name
                                )
                                : '—'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Vendor
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->vendor?->name ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Purchase Date
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->purchase_date?->format('d M Y') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Purchase Cost
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->purchase_cost !== null
                                ? '₹' . number_format((float) $asset->purchase_cost, 2)
                                : '—'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Criticality
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ ucfirst($asset->criticality) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Warranty End
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->warranty_end_date?->format('d M Y') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Preventive Maintenance
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->requires_preventive_maintenance ? 'Required' : 'Not Required' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase text-gray-500">
                            Calibration
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $asset->requires_calibration ? 'Required' : 'Not Required' }}
                        </dd>
                    </div>

                </dl>

                @if ($asset->remarks)
                    <div class="mt-6 border-t border-gray-100 pt-5">
                        <div class="text-xs font-medium uppercase text-gray-500">
                            Remarks
                        </div>

                        <div class="mt-2 text-sm text-gray-700">
                            {{ $asset->remarks }}
                        </div>
                    </div>
                @endif

            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                Movement History
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                Department, location and custodian transfer history.
            </p>
        </div>

        <a
            href="{{ route('admin.assets.movements.create', $asset) }}"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
        >
            Transfer Asset
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">

            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Date
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Type
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Department
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Location
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Custodian
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                        Reason
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">

                @forelse ($asset->movements as $movement)

                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $movement->movement_date?->format('d M Y') }}
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ ucwords(str_replace('_', ' ', $movement->movement_type)) }}
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div>
                                {{ $movement->fromDepartment?->name ?? '—' }}
                            </div>

                            <div class="text-xs text-gray-400">
                                ↓
                            </div>

                            <div class="font-medium">
                                {{ $movement->toDepartment?->name ?? '—' }}
                            </div>
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div>
                                {{ $movement->from_location ?? '—' }}
                            </div>

                            <div class="text-xs text-gray-400">
                                ↓
                            </div>

                            <div class="font-medium">
                                {{ $movement->to_location ?? '—' }}
                            </div>
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-700">

                            <div>
                                @if ($movement->fromCustodian)
                                    {{ trim(
                                        $movement->fromCustodian->first_name . ' ' .
                                        $movement->fromCustodian->last_name
                                    ) }}
                                @else
                                    —
                                @endif
                            </div>

                            <div class="text-xs text-gray-400">
                                ↓
                            </div>

                            <div class="font-medium">
                                @if ($movement->toCustodian)
                                    {{ trim(
                                        $movement->toCustodian->first_name . ' ' .
                                        $movement->toCustodian->last_name
                                    ) }}
                                @else
                                    —
                                @endif
                            </div>

                        </td>

                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $movement->reason ?? '—' }}

                            @if ($movement->movedBy)
                                <div class="mt-1 text-xs text-gray-400">
                                    By {{ $movement->movedBy->name }}
                                </div>
                            @endif
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-sm text-gray-500"
                        >
                            No asset movement has been recorded yet.
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