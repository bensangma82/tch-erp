@php
    $vendor = $assetVendor ?? null;
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
            Vendor Code
        </label>

        <input
            type="text"
            name="code"
            value="{{ old('code', $vendor?->code) }}"
            required
            maxlength="30"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Vendor Name
        </label>

        <input
            type="text"
            name="name"
            value="{{ old('name', $vendor?->name) }}"
            required
            maxlength="150"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Contact Person
        </label>

        <input
            type="text"
            name="contact_person"
            value="{{ old('contact_person', $vendor?->contact_person) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Phone
        </label>

        <input
            type="text"
            name="phone"
            value="{{ old('phone', $vendor?->phone) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Alternate Phone
        </label>

        <input
            type="text"
            name="alternate_phone"
            value="{{ old('alternate_phone', $vendor?->alternate_phone) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            Email
        </label>

        <input
            type="email"
            name="email"
            value="{{ old('email', $vendor?->email) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            City
        </label>

        <input
            type="text"
            name="city"
            value="{{ old('city', $vendor?->city) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            District
        </label>

        <input
            type="text"
            name="district"
            value="{{ old('district', $vendor?->district) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            State
        </label>

        <input
            type="text"
            name="state"
            value="{{ old('state', $vendor?->state) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            PIN Code
        </label>

        <input
            type="text"
            name="pin_code"
            value="{{ old('pin_code', $vendor?->pin_code) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            GSTIN
        </label>

        <input
            type="text"
            name="gstin"
            value="{{ old('gstin', $vendor?->gstin) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">
            PAN No.
        </label>

        <input
            type="text"
            name="pan_no"
            value="{{ old('pan_no', $vendor?->pan_no) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
        >
    </div>

</div>

<div>
    <label class="block text-sm font-medium text-gray-700">
        Address
    </label>

    <textarea
        name="address"
        rows="3"
        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
    >{{ old('address', $vendor?->address) }}</textarea>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700">
        Remarks
    </label>

    <textarea
        name="remarks"
        rows="3"
        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm"
    >{{ old('remarks', $vendor?->remarks) }}</textarea>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="provides_sales"
            value="1"
            @checked(old('provides_sales', $vendor?->provides_sales ?? true))
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">Sales</span>
    </label>

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="provides_service"
            value="1"
            @checked(old('provides_service', $vendor?->provides_service))
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">Service</span>
    </label>

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="provides_amc_cmc"
            value="1"
            @checked(old('provides_amc_cmc', $vendor?->provides_amc_cmc))
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">AMC / CMC</span>
    </label>

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="provides_calibration"
            value="1"
            @checked(old('provides_calibration', $vendor?->provides_calibration))
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">Calibration</span>
    </label>

    <label class="flex items-center gap-3">
        <input
            type="checkbox"
            name="is_active"
            value="1"
            @checked(old('is_active', $vendor?->is_active ?? true))
            class="rounded border-gray-300"
        >
        <span class="text-sm text-gray-700">Active</span>
    </label>

</div>