<x-app-layout>

    <x-slot name="header">

        <div>

            <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                Edit Medicine
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Update medicine master information
            </p>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                {{ $medicine->generic_name }}
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $medicine->code }}
                            </p>

                        </div>


                        @if ($medicine->is_active)

                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                Active
                            </span>

                        @else

                            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                Inactive
                            </span>

                        @endif

                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('pharmacy.medicines.update', $medicine) }}"
                    class="space-y-6 p-6"
                >

                    @csrf
                    @method('PUT')


                    <div class="grid gap-5 md:grid-cols-2">


                        <div>

                            <label
                                for="code"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Medicine Code
                            </label>

                            <input
                                id="code"
                                name="code"
                                type="text"
                                value="{{ old('code', $medicine->code) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('code')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>



                        <div>

                            <label
                                for="generic_name"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Generic Name
                            </label>

                            <input
                                id="generic_name"
                                name="generic_name"
                                type="text"
                                value="{{ old('generic_name', $medicine->generic_name) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('generic_name')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>



                        <div>

                            <label
                                for="brand_name"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Brand Name
                            </label>

                            <input
                                id="brand_name"
                                name="brand_name"
                                type="text"
                                value="{{ old('brand_name', $medicine->brand_name) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="strength"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Strength
                            </label>

                            <input
                                id="strength"
                                name="strength"
                                type="text"
                                value="{{ old('strength', $medicine->strength) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="dosage_form"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Dosage Form
                            </label>

                            <input
                                id="dosage_form"
                                name="dosage_form"
                                type="text"
                                value="{{ old('dosage_form', $medicine->dosage_form) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="unit"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Dispensing Unit
                            </label>

                            <input
                                id="unit"
                                name="unit"
                                type="text"
                                value="{{ old('unit', $medicine->unit) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="manufacturer"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Manufacturer
                            </label>

                            <input
                                id="manufacturer"
                                name="manufacturer"
                                type="text"
                                value="{{ old('manufacturer', $medicine->manufacturer) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="default_selling_price"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Default Selling Price
                            </label>

                            <input
                                id="default_selling_price"
                                name="default_selling_price"
                                type="number"
                                step="0.01"
                                min="0"
                                value="{{ old('default_selling_price', $medicine->default_selling_price) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>

                    </div>


<div>

    <label
        for="hsn_code"
        class="mb-2 block text-sm font-semibold text-slate-700"
    >
        HSN Code
    </label>

    <input
        id="hsn_code"
        name="hsn_code"
        type="text"
        value="{{ old('hsn_code', $medicine->hsn_code ?? '') }}"
        placeholder="e.g. 3004"
        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
    >

    @error('hsn_code')
        <p class="mt-2 text-sm text-red-600">
            {{ $message }}
        </p>
    @enderror

</div>


<div>

    <label
        for="gst_percent"
        class="mb-2 block text-sm font-semibold text-slate-700"
    >
        GST %
    </label>

    <select
        id="gst_percent"
        name="gst_percent"
        required
        class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
    >

        @foreach ([0, 5, 12, 18, 28] as $gst)

            <option
                value="{{ $gst }}"
                @selected(
                    old(
                        'gst_percent',
                        $medicine->gst_percent ?? 0
                    ) == $gst
                )
            >
                {{ $gst }}%
            </option>

        @endforeach

    </select>

    @error('gst_percent')
        <p class="mt-2 text-sm text-red-600">
            {{ $message }}
        </p>
    @enderror

</div>
                    <div>

                        <label
                            for="description"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Description / Notes
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('description', $medicine->description) }}</textarea>

                    </div>



                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">

                        <a
                            href="{{ route('pharmacy.medicines.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Back
                        </a>


                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>