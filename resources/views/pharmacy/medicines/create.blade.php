<x-app-layout>

    <x-slot name="header">

        <div>

            <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                Add Medicine
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Create a new medicine in the pharmacy master
            </p>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Medicine Details
                    </h3>

                </div>


                <form
                    method="POST"
                    action="{{ route('pharmacy.medicines.store') }}"
                    class="space-y-6 p-6"
                >

                    @csrf


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
                                value="{{ old('code') }}"
                                required
                                placeholder="e.g. MED0001"
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
                                value="{{ old('generic_name') }}"
                                required
                                placeholder="e.g. Paracetamol"
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
                                value="{{ old('brand_name') }}"
                                placeholder="Optional"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('brand_name')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                                value="{{ old('strength') }}"
                                placeholder="e.g. 500 mg"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('strength')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>



                        <div>

                            <label
                                for="dosage_form"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Dosage Form
                            </label>

                            <select
                                id="dosage_form"
                                name="dosage_form"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select dosage form
                                </option>

                                @foreach ([
                                    'Tablet',
                                    'Capsule',
                                    'Syrup',
                                    'Suspension',
                                    'Injection',
                                    'Infusion',
                                    'Drops',
                                    'Cream',
                                    'Ointment',
                                    'Gel',
                                    'Inhaler',
                                    'Nebulisation',
                                    'Powder',
                                    'Suppository',
                                    'Other',
                                ] as $form)

                                    <option
                                        value="{{ $form }}"
                                        @selected(old('dosage_form') === $form)
                                    >
                                        {{ $form }}
                                    </option>

                                @endforeach

                            </select>

                            @error('dosage_form')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>



                        <div>

                            <label
                                for="unit"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Dispensing Unit
                            </label>

                            <select
                                id="unit"
                                name="unit"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                @foreach ([
                                    'tablet' => 'Tablet',
                                    'capsule' => 'Capsule',
                                    'bottle' => 'Bottle',
                                    'vial' => 'Vial',
                                    'ampoule' => 'Ampoule',
                                    'tube' => 'Tube',
                                    'sachet' => 'Sachet',
                                    'piece' => 'Piece',
                                ] as $value => $label)

                                    <option
                                        value="{{ $value }}"
                                        @selected(old('unit', 'tablet') === $value)
                                    >
                                        {{ $label }}
                                    </option>

                                @endforeach

                            </select>

                            @error('unit')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                                value="{{ old('manufacturer') }}"
                                placeholder="Optional"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('manufacturer')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>



                        <div>

                            <label
                                for="default_selling_price"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Default Selling Price
                            </label>

                            <div class="relative">

                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                    ₹
                                </span>

                                <input
                                    id="default_selling_price"
                                    name="default_selling_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value="{{ old('default_selling_price', 0) }}"
                                    required
                                    class="w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                            </div>

                            @error('default_selling_price')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                        >{{ old('description') }}</textarea>

                        @error('description')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>



                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">

                        <a
                            href="{{ route('pharmacy.medicines.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            Save Medicine
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>