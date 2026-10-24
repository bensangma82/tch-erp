

<x-app-layout>
<div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- Page Header --}}
    <div>
        <a
            href="{{ route('service-categories.index') }}"
            class="text-sm font-medium text-slate-600 hover:text-slate-900"
        >
            &larr; Back to Service Categories
        </a>

        <h1 class="mt-3 text-2xl font-bold text-slate-900">
            Edit Service Category
        </h1>

        <p class="mt-1 text-sm text-slate-600">
            Update the category name, description, and display order.
        </p>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div
            role="alert"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
        >
            <p class="mb-2 font-semibold">
                Please correct the following errors:
            </p>

            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Edit Category Form --}}
    <form
        method="POST"
        action="{{ route('service-categories.update', $serviceCategory) }}"
        class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
    >
        @csrf
        @method('PUT')

        {{-- Category Code --}}
        <div>
            <label
                for="code"
                class="mb-1 block text-sm font-medium text-slate-700"
            >
                Category code
            </label>

            <input
                id="code"
                type="text"
                value="{{ $serviceCategory->code }}"
                disabled
                class="w-full rounded-lg border-slate-200 bg-slate-50 font-mono text-slate-500 shadow-sm"
            >

            <p class="mt-1 text-xs text-slate-500">
                Category codes cannot be changed because existing services
                and diagnostic workflows may depend on them.
            </p>
        </div>

        {{-- Category Name --}}
        <div>
            <label
                for="name"
                class="mb-1 block text-sm font-medium text-slate-700"
            >
                Category name *
            </label>

            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $serviceCategory->name) }}"
                required
                maxlength="100"
                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >
        </div>

        {{-- Description --}}
        <div>
            <label
                for="description"
                class="mb-1 block text-sm font-medium text-slate-700"
            >
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="3"
                maxlength="2000"
                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >{{ old('description', $serviceCategory->description) }}</textarea>
        </div>

        {{-- Display Order --}}
        <div>
            <label
                for="sort_order"
                class="mb-1 block text-sm font-medium text-slate-700"
            >
                Display order
            </label>

            <input
                id="sort_order"
                name="sort_order"
                type="number"
                min="0"
                max="9999"
                value="{{ old('sort_order', $serviceCategory->sort_order) }}"
                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
            >

            <p class="mt-1 text-xs text-slate-500">
                Lower numbers appear first in the category list.
            </p>
        </div>

        {{-- System Category Notice --}}
        @if ($serviceCategory->is_system)
            <div
                class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
            >
                <p class="font-semibold">
                    Protected system category
                </p>

                <p class="mt-1">
                    This category is used by system workflows.
                    Its code and activation status are protected.
                </p>
            </div>
        @endif

        {{-- Form Actions --}}
        <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">

            <a
                href="{{ route('service-categories.index') }}"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
            >
                Save Changes
            </button>

        </div>
    </form>

</div>
</x-app-layout>
