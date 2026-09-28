<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                Edit Asset Vendor
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Update vendor details and service capabilities.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <form
                method="POST"
                action="{{ route('admin.asset-vendors.update', $assetVendor) }}"
                class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >
                @csrf
                @method('PUT')

                @include('admin.asset-vendors.partials.form', [
                    'assetVendor' => $assetVendor
                ])

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                    <a
                        href="{{ route('admin.asset-vendors.index') }}"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                    >
                        Update Vendor
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>