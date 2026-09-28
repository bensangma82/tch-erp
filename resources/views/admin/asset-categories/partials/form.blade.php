@php
    $category = $assetCategory ?? null;
@endphp

@if ($errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">

    <div>
        <label for="code" class="block text-sm font-medium text-gray-700">
            Category Code
        </label>

        <input
            id="code"
            name="code"
            type="text"
            value="{{ old('code', $category?->code) }}"
            required
            maxlength="30"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
            Category Name
        </label>

        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $category?->name) }}"
            required
            maxlength="150"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label for="asset_class" class="block text-sm font-medium text-gray-700">
            Asset Class
        </label>

        <select
            id="asset_class"
            name="asset_class"
            required
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
            @foreach ([
                'biomedical' => 'Biomedical',
                'it' => 'IT',
                'electrical' => 'Electrical',
                'furniture' => 'Furniture',
                'vehicle' => 'Vehicle',
                'building' => 'Building / Infrastructure',
                'general' => 'General',
            ] as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected(old('asset_class', $category?->asset_class ?? 'general') === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="default_useful_life_years" class="block text-sm font-medium text-gray-700">
            Default Useful Life (Years)
        </label>

        <input
            id="default_useful_life_years"
            name="default_useful_life_years"
            type="number"
            min="1"
            max="100"
            value="{{ old('default_useful_life_years', $category?->default_useful_life_years) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

</div>

<div>
    <label for="description" class="block text-sm font-medium text-gray-700">
        Description
    </label>

    <textarea
        id="description"
        name="description"
        rows="3"
        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
    >{{ old('description', $category?->description) }}</textarea>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="requires_preventive_maintenance"
            value="1"
            @checked(old(
                'requires_preventive_maintenance',
                $category?->requires_preventive_maintenance
            ))
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">
            Preventive Maintenance Required
        </span>
    </label>

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="requires_calibration"
            value="1"
            @checked(old(
                'requires_calibration',
                $category?->requires_calibration
            ))
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">
            Calibration Required
        </span>
    </label>

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            @checked(old(
                'is_active',
                $category?->is_active ?? true
            ))
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">
            Active
        </span>
    </label>

</div>