<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Transfer Bed
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $admission->admission_no }}
                </p>

            </div>


            <a
                href="{{ route('ipd.show', $admission) }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Back to IPD Admission
            </a>

        </div>

    </x-slot>


    @php

        $patient =
            $admission->patient;


        $consultant =
            $admission->consultant;


        $consultantName =
            $consultant
                ? trim(
                    ($consultant->title
                        ? $consultant->title . ' '
                        : '')
                    .
                    $consultant->first_name
                    .
                    ($consultant->middle_name
                        ? ' ' . $consultant->middle_name
                        : '')
                    .
                    ($consultant->last_name
                        ? ' ' . $consultant->last_name
                        : '')
                )
                : '—';

    @endphp


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- VALIDATION ERRORS --}}
            {{-- ========================================================= --}}

            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- PATIENT SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Patient & Current Bed
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Review the current allocation before transferring the patient
                    </p>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Patient
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $patient?->full_name ?? 'Unknown Patient' }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            {{ $patient?->uhid ?? '—' }}
                        </div>

                        @if ($patient?->mrd_number)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $patient->mrd_number }}
                            </div>

                        @endif

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Consultant
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $consultantName }}
                        </div>

                        @if ($consultant?->speciality)

                            <div class="mt-1 text-xs text-slate-500">
                                {{ $consultant->speciality }}
                            </div>

                        @endif

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Current Ward
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $currentBed?->ward?->name ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Current Bed
                        </div>

                        <div class="mt-2 text-xl font-bold text-slate-900">
                            {{ $currentBed?->bed_number ?? '—' }}
                        </div>

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- TRANSFER FORM --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('ipd.transfer.store', $admission) }}"
                class="space-y-6"
            >

                @csrf


                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            New Bed Allocation
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Select the destination ward and an available bed
                        </p>

                    </div>


                    <div class="grid gap-6 p-6 md:grid-cols-2">


                        {{-- TRANSFER DATE / TIME --}}

                        <div>

                            <label
                                for="transferred_at"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Transfer Date & Time *
                            </label>

                            <input
                                id="transferred_at"
                                name="transferred_at"
                                type="datetime-local"
                                required
                                value="{{ old(
                                    'transferred_at',
                                    now()->format('Y-m-d\TH:i')
                                ) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        {{-- DESTINATION WARD --}}

                        <div>

                            <label
                                for="ward_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Destination Ward *
                            </label>

                            <select
                                id="ward_id"
                                name="ward_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select destination ward
                                </option>


                                @foreach ($wards as $ward)

                                    <option
                                        value="{{ $ward->id }}"
                                        @selected(
                                            old('ward_id')
                                            ==
                                            $ward->id
                                        )
                                    >
                                        {{ $ward->name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>



                        {{-- DESTINATION BED --}}

                        <div class="md:col-span-2">

                            <label
                                for="bed_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Destination Bed *
                            </label>

                            <select
                                id="bed_id"
                                name="bed_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select destination ward first
                                </option>


                                @foreach ($availableBeds as $bed)

                                    <option
                                        value="{{ $bed->id }}"
                                        data-ward-id="{{ $bed->ward_id }}"
                                        @selected(
                                            old('bed_id')
                                            ==
                                            $bed->id
                                        )
                                    >
                                        {{ $bed->ward?->name ?? 'Unknown Ward' }}
                                        — {{ $bed->bed_number }}

                                        @if ($bed->bed_type)
                                            — {{ ucfirst($bed->bed_type) }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>


                            <p class="mt-2 text-xs text-slate-500">
                                Only active beds currently marked available are shown.
                            </p>

                        </div>



                        {{-- REMARKS --}}

                        <div class="md:col-span-2">

                            <label
                                for="remarks"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Transfer Reason / Remarks
                            </label>

                            <textarea
                                id="remarks"
                                name="remarks"
                                rows="4"
                                maxlength="2000"
                                placeholder="Example: Shifted to ICU due to clinical deterioration..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('remarks') }}</textarea>

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- SAFETY NOTE --}}
                {{-- ===================================================== --}}

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                    <div class="font-semibold text-amber-800">
                        Bed transfer safety
                    </div>

                    <div class="mt-2 text-sm leading-6 text-amber-700">

                        The destination bed will be checked again when the
                        transfer is submitted.

                        If another user has already occupied that bed, the
                        transfer will stop and you will be asked to choose
                        another bed.

                        The patient's current bed will only be released after
                        the destination bed passes all checks.

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- ACTIONS --}}
                {{-- ===================================================== --}}

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('ipd.show', $admission) }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        onclick="return confirm('Transfer this patient to the selected bed?');"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        Transfer Patient
                    </button>

                </div>

            </form>


        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- WARD -> BED FILTER --}}
    {{-- ============================================================= --}}

    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const wardSelect =
                    document.getElementById(
                        'ward_id'
                    );


                const bedSelect =
                    document.getElementById(
                        'bed_id'
                    );


                if (
                    !wardSelect
                    ||
                    !bedSelect
                ) {
                    return;
                }


                const oldBedId =
                    @json(
                        (string) old(
                            'bed_id',
                            ''
                        )
                    );


                const allBeds =
                    Array.from(
                        bedSelect.querySelectorAll(
                            'option[data-ward-id]'
                        )
                    )
                    .map(
                        function (option) {

                            return {

                                value:
                                    String(
                                        option.value
                                    ),

                                text:
                                    option
                                        .textContent
                                        .trim(),

                                wardId:
                                    String(
                                        option.dataset.wardId
                                    ),

                            };

                        }
                    );


                function rebuildBedOptions()
                {
                    const selectedWardId =
                        String(
                            wardSelect.value
                            ||
                            ''
                        );


                    const currentBedId =
                        String(
                            bedSelect.value
                            ||
                            oldBedId
                            ||
                            ''
                        );


                    bedSelect.innerHTML =
                        '';


                    const placeholder =
                        document.createElement(
                            'option'
                        );


                    placeholder.value =
                        '';


                    if (!selectedWardId)
                    {
                        placeholder.textContent =
                            'Select destination ward first';


                        bedSelect.appendChild(
                            placeholder
                        );


                        bedSelect.disabled =
                            true;


                        return;
                    }


                    const matchingBeds =
                        allBeds.filter(
                            function (bed) {

                                return (
                                    bed.wardId
                                    ===
                                    selectedWardId
                                );

                            }
                        );


                    placeholder.textContent =
                        matchingBeds.length > 0
                            ? 'Select available bed'
                            : 'No available beds in this ward';


                    bedSelect.appendChild(
                        placeholder
                    );


                    matchingBeds.forEach(
                        function (bed) {

                            const option =
                                document.createElement(
                                    'option'
                                );


                            option.value =
                                bed.value;


                            option.textContent =
                                bed.text;


                            if (
                                currentBedId !== ''
                                &&
                                bed.value === currentBedId
                            ) {
                                option.selected =
                                    true;
                            }


                            bedSelect.appendChild(
                                option
                            );

                        }
                    );


                    bedSelect.disabled =
                        matchingBeds.length === 0;
                }


                wardSelect.addEventListener(
                    'change',
                    function () {

                        bedSelect.value =
                            '';


                        rebuildBedOptions();

                    }
                );


                rebuildBedOptions();

            }
        );

    </script>

</x-app-layout>