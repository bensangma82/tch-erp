<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Asset Categories
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage hospital asset classifications and maintenance defaults.
                </p>
            </div>

            <a
                href="{{ route('admin.asset-categories.create') }}"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
            >
                Add Asset Category
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Code
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Category
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Class
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    PM
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Calibration
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Useful Life
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($categories as $category)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $category->code }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <div class="font-medium">
                                            {{ $category->name }}
                                        </div>

                                        @if ($category->description)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $category->description }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ ucfirst($category->asset_class) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        {{ $category->requires_preventive_maintenance ? 'Yes' : 'No' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        {{ $category->requires_calibration ? 'Yes' : 'No' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $category->default_useful_life_years
                                            ? $category->default_useful_life_years . ' years'
                                            : '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @if ($category->is_active)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">
                                                Active
                                            </span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-right text-sm">
                                        <a
                                            href="{{ route('admin.asset-categories.edit', $category) }}"
                                            class="font-semibold text-indigo-600 hover:text-indigo-900"
                                        >
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                                        No asset categories have been created yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($categories->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3">
                        {{ $categories->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>