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

        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

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
                    class="space-y-8 p-6"
                >
                    @csrf
                    @method('PUT')

                    {{-- BASIC INFORMATION --}}
                    <div>

                        <h4 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">
                            Basic Information
                        </h4>

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
                                    value="{{ old('strength', $medicine->strength) }}"
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
                                            @selected(
                                                old(
                                                    'dosage_form',
                                                    $medicine->dosage_form
                                                ) === $form
                                            )
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
                                    for="hsn_code"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    HSN Code
                                </label>

                                <input
                                    id="hsn_code"
                                    name="hsn_code"
                                    type="text"
                                    value="{{ old('hsn_code', $medicine->hsn_code) }}"
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

                        </div>

                    </div>

                    {{-- PACKAGING --}}
                    <div class="border-t border-slate-100 pt-6">

                        <div class="mb-4">
                            <h4 class="text-sm font-bold uppercase tracking-wide text-slate-500">
                                Packaging & Inventory
                            </h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Define how this medicine is purchased and how it is dispensed.
                            </p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-3">

                            <div>
                                <label
                                    for="unit"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Base / Dispensing Unit
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
                                        'ml' => 'mL',
                                        'bottle' => 'Bottle',
                                        'vial' => 'Vial',
                                        'ampoule' => 'Ampoule',
                                        'tube' => 'Tube',
                                        'sachet' => 'Sachet',
                                        'piece' => 'Piece',
                                    ] as $value => $label)

                                        <option
                                            value="{{ $value }}"
                                            @selected(
                                                old(
                                                    'unit',
                                                    $medicine->unit
                                                ) === $value
                                            )
                                        >
                                            {{ $label }}
                                        </option>

                                    @endforeach
                                </select>

                                <p class="mt-1 text-xs text-slate-500">
                                    Smallest unit maintained in stock.
                                </p>

                                @error('unit')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="purchase_pack"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Purchase Pack
                                </label>

                                <select
                                    id="purchase_pack"
                                    name="purchase_pack"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">
                                        Select pack
                                    </option>

                                    @foreach ([
                                        'Strip',
                                        'Box',
                                        'Bottle',
                                        'Vial',
                                        'Ampoule',
                                        'Tube',
                                        'Sachet',
                                        'Pack',
                                        'Piece',
                                    ] as $pack)

                                        <option
                                            value="{{ $pack }}"
                                            @selected(
                                                old(
                                                    'purchase_pack',
                                                    $medicine->purchase_pack
                                                ) === $pack
                                            )
                                        >
                                            {{ $pack }}
                                        </option>

                                    @endforeach
                                </select>

                                <p class="mt-1 text-xs text-slate-500">
                                    Unit normally purchased from supplier.
                                </p>

                                @error('purchase_pack')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="units_per_pack"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Units per Pack
                                </label>

                                <input
                                    id="units_per_pack"
                                    name="units_per_pack"
                                    type="number"
                                    min="1"
                                    step="1"
                                    value="{{ old('units_per_pack', $medicine->units_per_pack ?? 1) }}"
                                    required
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                                <p class="mt-1 text-xs text-slate-500">
                                    Example: 15 tablets in one strip.
                                </p>

                                @error('units_per_pack')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                        </div>

                        <div
                            id="pack-preview"
                            class="mt-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800"
                        >
                        </div>

                    </div>

                    {{-- PRICING --}}
                    <div class="border-t border-slate-100 pt-6">

                        <div class="mb-4">
                            <h4 class="text-sm font-bold uppercase tracking-wide text-slate-500">
                                Pricing
                            </h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Pack-level purchase/MRP values and the current unit-level selling price.
                            </p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-3">

                            <div>
                                <label
                                    for="default_purchase_price"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Default Purchase Rate / Pack
                                </label>

                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                        ₹
                                    </span>

                                    <input
                                        id="default_purchase_price"
                                        name="default_purchase_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value="{{ old('default_purchase_price', $medicine->default_purchase_price ?? 0) }}"
                                        required
                                        class="w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                </div>

                                @error('default_purchase_price')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="mrp_per_pack"
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    MRP / Pack
                                </label>

                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                        ₹
                                    </span>

                                    <input
                                        id="mrp_per_pack"
                                        name="mrp_per_pack"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value="{{ old('mrp_per_pack', $medicine->mrp_per_pack ?? 0) }}"
                                        required
                                        class="w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                </div>

                                @error('mrp_per_pack')
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
                                    Default Selling Price / Unit
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
                                        value="{{ old('default_selling_price', $medicine->default_selling_price) }}"
                                        required
                                        class="w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                </div>

                                <p class="mt-1 text-xs text-slate-500">
                                    Current dispensing price for one base unit.
                                </p>

                                @error('default_selling_price')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                        </div>

                    </div>

                    {{-- DESCRIPTION --}}
                    <div class="border-t border-slate-100 pt-6">

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

                        @error('description')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    {{-- ACTIONS --}}
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pack = document.getElementById('purchase_pack');
            const unit = document.getElementById('unit');
            const unitsPerPack = document.getElementById('units_per_pack');
            const preview = document.getElementById('pack-preview');

            function updatePackPreview() {
                const packName = pack.value || 'pack';
                const unitName =
                    unit.options[unit.selectedIndex]?.text || 'unit';

                const quantity =
                    parseInt(unitsPerPack.value || '1', 10);

                preview.textContent =
                    '1 ' +
                    packName +
                    ' = ' +
                    quantity +
                    ' ' +
                    unitName +
                    (quantity === 1 ? '' : 's');
            }

            pack.addEventListener(
                'change',
                updatePackPreview
            );

            unit.addEventListener(
                'change',
                updatePackPreview
            );

            unitsPerPack.addEventListener(
                'input',
                updatePackPreview
            );

            updatePackPreview();
        });
    </script>

</x-app-layout>