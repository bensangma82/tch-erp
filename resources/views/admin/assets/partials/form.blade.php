@php
    $currentAsset = $asset ?? null;
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
        <label class="block text-sm font-medium text-gray-700">
            Asset Code
        </label>
        <input
            type="text"
            name="asset_code"
            value="{{ old('asset_code', $currentAsset?->asset_code) }}"
            required
            maxlength="50"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Asset Name
        </label>
        <input
            type="text"
            name="asset_name"
            value="{{ old('asset_name', $currentAsset?->asset_name) }}"
            required
            maxlength="200"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Category
        </label>
        <select
            name="asset_category_id"
            required
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
            <option value="">Select category</option>

            @foreach ($categories as $category)
                <option
                    value="{{ $category->id }}"
                    @selected(
                        old(
                            'asset_category_id',
                            $currentAsset?->asset_category_id
                        ) == $category->id
                    )
                >
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Department
        </label>
        <select
            name="department_id"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
            <option value="">Not assigned</option>

            @foreach ($departments as $department)
                <option
                    value="{{ $department->id }}"
                    @selected(
                        old(
                            'department_id',
                            $currentAsset?->department_id
                        ) == $department->id
                    )
                >
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Custodian / Employee
        </label>
        <select
            name="custodian_employee_id"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
            <option value="">No individual custodian</option>

            @foreach ($employees as $employee)
                <option
                    value="{{ $employee->id }}"
                    @selected(
                        old(
                            'custodian_employee_id',
                            $currentAsset?->custodian_employee_id
                        ) == $employee->id
                    )
                >
                    {{ $employee->employee_code }} -
                    {{ $employee->first_name }}
                    {{ $employee->middle_name }}
                    {{ $employee->last_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Vendor
        </label>
        <select
            name="asset_vendor_id"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
            <option value="">No vendor selected</option>

            @foreach ($vendors as $vendor)
                <option
                    value="{{ $vendor->id }}"
                    @selected(
                        old(
                            'asset_vendor_id',
                            $currentAsset?->asset_vendor_id
                        ) == $vendor->id
                    )
                >
                    {{ $vendor->name }}
                </option>
            @endforeach
        </select>
    </div>

</div>

<div class="border-t border-gray-100 pt-5">
    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
        Equipment Details
    </h3>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Manufacturer
            </label>
            <input
                type="text"
                name="manufacturer"
                value="{{ old('manufacturer', $currentAsset?->manufacturer) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Model
            </label>
            <input
                type="text"
                name="model"
                value="{{ old('model', $currentAsset?->model) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Serial Number
            </label>
            <input
                type="text"
                name="serial_number"
                value="{{ old('serial_number', $currentAsset?->serial_number) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

    </div>
</div>

<div class="border-t border-gray-100 pt-5">
    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
        Purchase Details
    </h3>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Purchase Date
            </label>
            <input
                type="date"
                name="purchase_date"
                value="{{ old('purchase_date', $currentAsset?->purchase_date?->format('Y-m-d')) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Purchase Cost
            </label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="purchase_cost"
                value="{{ old('purchase_cost', $currentAsset?->purchase_cost) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Invoice Number
            </label>
            <input
                type="text"
                name="invoice_number"
                value="{{ old('invoice_number', $currentAsset?->invoice_number) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Purchase Order Number
            </label>
            <input
                type="text"
                name="purchase_order_number"
                value="{{ old('purchase_order_number', $currentAsset?->purchase_order_number) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

    </div>
</div>

<div class="border-t border-gray-100 pt-5">
    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
        Installation & Warranty
    </h3>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Installation Date
            </label>
            <input
                type="date"
                name="installation_date"
                value="{{ old('installation_date', $currentAsset?->installation_date?->format('Y-m-d')) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Commissioning Date
            </label>
            <input
                type="date"
                name="commissioning_date"
                value="{{ old('commissioning_date', $currentAsset?->commissioning_date?->format('Y-m-d')) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Warranty Start
            </label>
            <input
                type="date"
                name="warranty_start_date"
                value="{{ old('warranty_start_date', $currentAsset?->warranty_start_date?->format('Y-m-d')) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Warranty End
            </label>
            <input
                type="date"
                name="warranty_end_date"
                value="{{ old('warranty_end_date', $currentAsset?->warranty_end_date?->format('Y-m-d')) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

    </div>
</div>

<div class="border-t border-gray-100 pt-5">
    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
        Operational Details
    </h3>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Current Location
            </label>
            <input
                type="text"
                name="location"
                value="{{ old('location', $currentAsset?->location) }}"
                placeholder="e.g. ICU, Dialysis Room 1"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Status
            </label>
            <select
                name="status"
                required
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
                @foreach ([
                    'active' => 'Active',
                    'under_maintenance' => 'Under Maintenance',
                    'out_of_service' => 'Out of Service',
                    'condemned' => 'Condemned',
                    'disposed' => 'Disposed',
                    'lost' => 'Lost',
                ] as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected(
                            old(
                                'status',
                                $currentAsset?->status ?? 'active'
                            ) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Criticality
            </label>
            <select
                name="criticality"
                required
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
                @foreach ([
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                    'critical' => 'Critical',
                ] as $value => $label)
                    <option
                        value="{{ $value }}"
                        @selected(
                            old(
                                'criticality',
                                $currentAsset?->criticality ?? 'medium'
                            ) === $value
                        )
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Useful Life (Years)
            </label>
            <input
                type="number"
                name="useful_life_years"
                min="1"
                max="100"
                value="{{ old('useful_life_years', $currentAsset?->useful_life_years) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
            >
        </div>

    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="requires_preventive_maintenance"
            value="1"
            @checked(
                old(
                    'requires_preventive_maintenance',
                    $currentAsset?->requires_preventive_maintenance
                )
            )
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
            @checked(
                old(
                    'requires_calibration',
                    $currentAsset?->requires_calibration
                )
            )
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
            @checked(
                old(
                    'is_active',
                    $currentAsset?->is_active ?? true
                )
            )
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">
            Active Record
        </span>
    </label>

</div>

<div>
    <label class="block text-sm font-medium text-gray-700">
        Remarks
    </label>

    <textarea
        name="remarks"
        rows="4"
        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
    >{{ old('remarks', $currentAsset?->remarks) }}</textarea>
</div>