

<x-app-layout>
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Service Categories</h1>
            <p class="mt-1 text-sm text-slate-600">
                Manage categories used by the Hospital Service Master.
            </p>
        </div>

        <a href="{{ route('service-categories.create') }}"
           class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            + Add Category
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Code</th>
                        <th class="px-5 py-3 text-center">Services</th>
                        <th class="px-5 py-3">Protection</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">
                                    {{ $category->name }}
                                </div>
                                @if ($category->description)
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $category->description }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-5 py-4 font-mono text-xs text-slate-700">
                                {{ $category->code }}
                            </td>

                            <td class="px-5 py-4 text-center tabular-nums">
                                {{ $category->services_count }}
                            </td>

                            <td class="px-5 py-4">
                                @if ($category->is_system)
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">
                                        System
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">Custom</span>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @if ($category->is_active)
                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-3">
                                    <a href="{{ route('service-categories.edit', $category) }}"
                                       class="font-medium text-blue-700 hover:text-blue-900">
                                        Edit
                                    </a>

                                    @if (! $category->is_system)
                                        <form method="POST"
                                              action="{{ route('service-categories.toggle-active', $category) }}"
                                              onsubmit="return confirm('Change the status of this category?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="font-medium text-slate-700 hover:text-slate-950">
                                                {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-medium text-slate-800">No service categories found</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Create the first category to begin configuring the Service Master.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

</div>
</x-app-layout>
