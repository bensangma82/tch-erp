<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Asset Register
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Central register of hospital equipment and fixed assets.
                </p>
            </div>

            <a
                href="{{ route('admin.assets.create') }}"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
            >
                Register Asset
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <form
                method="GET"
                action="{{ route('admin.assets.index') }}"
                class="grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-5"
            >

                <div class="md:col-span-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search code, name, serial no., manufacturer..."
                        class="block w-full rounded-lg border-gray-300 shadow-sm"
                    >
                </div>

                <div>
                    <select
                        name="asset_category_id"
                        class="block w-full rounded-lg border-gray-300 shadow-sm"
                    >
                        <option value="">All Categories</option>

                        @foreach ($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(request('asset_category_id') == $category->id)
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select
                        name="department_id"
                        class="block w-full rounded-lg border-gray-300 shadow-sm"
                    >
                        <option value="">All Departments</option>

                        @foreach ($departments as $department)
                            <option
                                value="{{ $department->id }}"
                                @selected(request('department_id') == $department->id)
                            >
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('admin.assets.index') }}"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                    >
                        Reset
                    </a>
                </div>

                <div>
                    <select
                        name="status"
                        class="block w-full rounded-lg border-gray-300 shadow-sm"
                    >
                        <option value="">All Statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="under_maintenance" @selected(request('status') === 'under_maintenance')>Under Maintenance</option>
                        <option value="out_of_service" @selected(request('status') === 'out_of_service')>Out of Service</option>
                        <option value="condemned" @selected(request('status') === 'condemned')>Condemned</option>
                        <option value="disposed" @selected(request('status') === 'disposed')>Disposed</option>
                        <option value="lost" @selected(request('status') === 'lost')>Lost</option>
                    </select>
                </div>

                <div>
                    <select
                        name="criticality"
                        class="block w-full rounded-lg border-gray-300 shadow-sm"
                    >
                        <option value="">All Criticality</option>
                        <option value="low" @selected(request('criticality') === 'low')>Low</option>
                        <option value="medium" @selected(request('criticality') === 'medium')>Medium</option>
                        <option value="high" @selected(request('criticality') === 'high')>High</option>
                        <option value="critical" @selected(request('criticality') === 'critical')>Critical</option>
                    </select>
                </div>

            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Asset
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Category
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Department
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Location
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                    Criticality
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($assets as $asset)

                                <tr>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="font-semibold text-gray-900">
                                            {{ $asset->asset_name }}
                                        </div>

                                        <div class="text-xs text-gray-500">
                                            {{ $asset->asset_code }}
                                        </div>

                                        @if ($asset->serial_number)
                                            <div class="text-xs text-gray-400">
                                                S/N: {{ $asset->serial_number }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $asset->category?->name ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $asset->department?->name ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $asset->location ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        {{ ucwords(str_replace('_', ' ', $asset->status)) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        {{ ucfirst($asset->criticality) }}
                                    </td>

                                    <td class="px-4 py-3 text-right text-sm">
                                        <a
                                            href="{{ route('admin.assets.show', $asset) }}"
                                            class="mr-3 font-semibold text-indigo-600 hover:text-indigo-900"
                                        >
                                            View
                                        </a>

                                        <a
                                            href="{{ route('admin.assets.edit', $asset) }}"
                                            class="font-semibold text-gray-600 hover:text-gray-900"
                                        >
                                            Edit
                                        </a>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                        No assets have been registered yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>

                @if ($assets->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3">
                        {{ $assets->links() }}
                    </div>
                @endif

            </div>

        </div>
    </div>
</x-app-layout>