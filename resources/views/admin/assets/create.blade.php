<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                Register Asset
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Add equipment or fixed assets to the hospital asset register.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">

            <form
                method="POST"
                action="{{ route('admin.assets.store') }}"
                class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                @csrf

                @include('admin.assets.partials.form')

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <a
                        href="{{ route('admin.assets.index') }}"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                    >
                        Register Asset
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>