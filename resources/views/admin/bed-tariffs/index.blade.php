<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Bed Tariff Master
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Configure inpatient daily bed charges by ward and bed type.
                </p>
            </div>

            <a
                href="{{ route('ip-billing.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                IP Billing
            </a>

        </div>
    </x-slot>


    @php
        $today = now()->toDateString();
    @endphp


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <div class="font-semibold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-inside list-disc text-sm text-red-700">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- ADD TARIFF --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">

                    <h3 class="text-base font-semibold text-slate-900">
                        Add Bed Tariff
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Create the current daily tariff for a ward and bed type.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.bed-tariffs.store') }}"
                    class="p-6"
                >

                    @csrf


                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">

                        <div>

                            <label
                                for="ward_id"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Ward
                            </label>

                            <select
                                id="ward_id"
                                name="ward_id"
                                required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >

                                <option value="">
                                    Select Ward
                                </option>

                                @foreach ($wards as $ward)

                                    <option
                                        value="{{ $ward->id }}"
                                        @selected((string) old('ward_id') === (string) $ward->id)
                                    >
                                        {{ $ward->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div>

                            <label
                                for="bed_type"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Bed Type
                            </label>

                            <select
                                id="bed_type"
                                name="bed_type"
                                required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >

                                <option value="">
                                    Select Bed Type
                                </option>

                                @foreach ($wards as $ward)

                                    @foreach (
                                        $ward->beds
                                            ->pluck('bed_type')
                                            ->filter()
                                            ->unique()
                                            ->sort()
                                        as $bedType
                                    )

                                        <option
                                            value="{{ $bedType }}"
                                            data-ward-id="{{ $ward->id }}"
                                            @selected(old('bed_type') === $bedType)
                                        >
                                            {{ ucfirst(str_replace('_', ' ', $bedType)) }}
                                        </option>

                                    @endforeach

                                @endforeach

                            </select>

                            <p class="mt-1 text-xs text-slate-500">
                                Options are filtered by the selected ward.
                            </p>

                        </div>


                        <div>

                            <label
                                for="rate_per_day"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Rate Per Day
                            </label>

                            <div class="relative mt-1">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                    ₹
                                </div>

                                <input
                                    id="rate_per_day"
                                    type="number"
                                    name="rate_per_day"
                                    value="{{ old('rate_per_day') }}"
                                    min="0"
                                    max="9999999.99"
                                    step="0.01"
                                    required
                                    class="block w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                    placeholder="0.00"
                                >

                            </div>

                        </div>


                        <div>

                            <label
                                for="effective_from"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Effective From
                            </label>

                            <input
                                id="effective_from"
                                type="date"
                                name="effective_from"
                                value="{{ old('effective_from', $today) }}"
                                required
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >

                        </div>


                        <div>

                            <label
                                for="effective_to"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Effective To
                            </label>

                            <input
                                id="effective_to"
                                type="date"
                                name="effective_to"
                                value="{{ old('effective_to') }}"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >

                            <p class="mt-1 text-xs text-slate-500">
                                Usually leave blank for the current tariff.
                            </p>

                        </div>


                        <div class="md:col-span-1 xl:col-span-3">

                            <label
                                for="remarks"
                                class="block text-sm font-semibold text-slate-700"
                            >
                                Remarks
                            </label>

                            <input
                                id="remarks"
                                type="text"
                                name="remarks"
                                value="{{ old('remarks') }}"
                                maxlength="2000"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                placeholder="Optional remarks"
                            >

                        </div>

                    </div>


                    <div class="mt-6 flex justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Save Tariff
                        </button>

                    </div>

                </form>

            </div>


            {{-- TARIFF TABLE --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="text-base font-semibold text-slate-900">
                                Current & Historical Tariffs
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Tariffs are retained so historical inpatient charges remain traceable.
                            </p>

                        </div>

                    </div>

                </div>


                @php
                    $tariffCount =
                        $wards->sum(
                            fn ($ward) =>
                                $ward->bedTariffs->count()
                        );
                @endphp


                @if ($tariffCount > 0)

                    <div class="overflow-x-auto">

                        <table class="min-w-[1100px] w-full">

                            <thead class="border-b border-slate-200 bg-white">

                                <tr>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Ward
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Bed Type
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Rate / Day
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Effective From
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Effective To
                                    </th>

                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Remarks
                                    </th>

                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @foreach ($wards as $ward)

                                    @foreach ($ward->bedTariffs as $tariff)

                                        <tr>

                                            <td class="px-5 py-4">

                                                <div class="font-semibold text-slate-900">
                                                    {{ $ward->name }}
                                                </div>

                                                <div class="mt-1 text-xs text-slate-500">
                                                    {{ $ward->code ?: '—' }}
                                                </div>

                                            </td>


                                            <td class="px-5 py-4 text-sm font-semibold text-slate-800">
                                                {{ ucfirst(str_replace('_', ' ', $tariff->bed_type)) }}
                                            </td>


                                            <td class="px-5 py-4 text-right font-bold text-slate-900">
                                                ₹{{ number_format((float) $tariff->rate_per_day, 2) }}
                                            </td>


                                            <td class="px-5 py-4 text-sm text-slate-700">
                                                {{ $tariff->effective_from?->format('d M Y') ?? '—' }}
                                            </td>


                                            <td class="px-5 py-4 text-sm text-slate-700">
                                                {{ $tariff->effective_to?->format('d M Y') ?? 'Current' }}
                                            </td>


                                            <td class="px-5 py-4 text-center">

                                                @if ($tariff->is_active)

                                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                        Active
                                                    </span>

                                                @else

                                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                        Inactive
                                                    </span>

                                                @endif

                                            </td>


                                            <td class="px-5 py-4 text-sm text-slate-600">
                                                {{ $tariff->remarks ?: '—' }}
                                            </td>


                                            <td class="px-5 py-4 text-center">

                                                @if ($tariff->is_active)

                                                    <form
                                                        method="POST"
                                                        action="{{ route('admin.bed-tariffs.deactivate', $tariff) }}"
                                                    >

                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="submit"
                                                            onclick="return confirm('Deactivate this bed tariff?')"
                                                            class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                                                        >
                                                            Deactivate
                                                        </button>

                                                    </form>

                                                @else

                                                    <span class="text-xs text-slate-400">
                                                        —
                                                    </span>

                                                @endif

                                            </td>

                                        </tr>

                                    @endforeach

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @else

                    <div class="px-6 py-14 text-center">

                        <div class="text-base font-semibold text-slate-700">
                            No bed tariffs configured yet
                        </div>

                        <p class="mt-2 text-sm text-slate-500">
                            Add the current daily rates for each ward and bed type.
                        </p>

                    </div>

                @endif

            </div>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const wardSelect =
                document.getElementById('ward_id');

            const bedTypeSelect =
                document.getElementById('bed_type');

            if (!wardSelect || !bedTypeSelect) {
                return;
            }


            const bedTypeOptions =
                Array.from(
                    bedTypeSelect.querySelectorAll(
                        'option[data-ward-id]'
                    )
                );


            function filterBedTypes() {

                const wardId =
                    wardSelect.value;

                const currentValue =
                    bedTypeSelect.value;


                bedTypeOptions.forEach(function (option) {

                    const shouldShow =
                        wardId !== ''
                        && option.dataset.wardId === wardId;

                    option.hidden =
                        !shouldShow;

                    option.disabled =
                        !shouldShow;

                });


                const currentOption =
                    bedTypeOptions.find(function (option) {
                        return (
                            option.value === currentValue
                            && option.dataset.wardId === wardId
                        );
                    });


                if (!currentOption) {
                    bedTypeSelect.value = '';
                }
            }


            wardSelect.addEventListener(
                'change',
                filterBedTypes
            );


            filterBedTypes();

        });
    </script>

</x-app-layout>