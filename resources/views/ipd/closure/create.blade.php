<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Close IPD Admission
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

        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">


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
                        Admission Summary
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Review the patient and current bed before closing the admission.
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

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Department
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $admission->department?->name ?? '—' }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Consultant
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $consultantName }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Current Bed
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $currentBed?->ward?->name ?? '—' }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            Bed:
                            {{ $currentBed?->bed_number ?? '—' }}
                        </div>

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- CLOSURE FORM --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('ipd.closure.store', $admission) }}"
                class="space-y-6"
            >

                @csrf


                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Close Admission
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Select the final inpatient disposition.
                        </p>

                    </div>


                    <div class="grid gap-6 p-6 md:grid-cols-2">


                        {{-- CLOSURE TYPE --}}

                        <div class="md:col-span-2">

                            <label
                                class="mb-3 block text-sm font-semibold text-slate-700"
                            >
                                Final Disposition *
                            </label>


                            <div class="grid gap-4 md:grid-cols-3">


                                <label
                                    class="cursor-pointer rounded-xl border border-slate-200 bg-white p-4 transition hover:border-emerald-300 hover:bg-emerald-50"
                                >

                                    <div class="flex items-start gap-3">

                                        <input
                                            type="radio"
                                            name="closure_type"
                                            value="discharged"
                                            required
                                            @checked(
                                                old('closure_type') === 'discharged'
                                            )
                                            class="mt-1 border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        >

                                        <div>

                                            <div class="font-semibold text-slate-900">
                                                Discharge
                                            </div>

                                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                                Patient is discharged from inpatient care.
                                            </div>

                                        </div>

                                    </div>

                                </label>



                                <label
                                    class="cursor-pointer rounded-xl border border-slate-200 bg-white p-4 transition hover:border-indigo-300 hover:bg-indigo-50"
                                >

                                    <div class="flex items-start gap-3">

                                        <input
                                            type="radio"
                                            name="closure_type"
                                            value="referred"
                                            required
                                            @checked(
                                                old('closure_type') === 'referred'
                                            )
                                            class="mt-1 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        >

                                        <div>

                                            <div class="font-semibold text-slate-900">
                                                Referral
                                            </div>

                                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                                Patient is referred to another facility.
                                            </div>

                                        </div>

                                    </div>

                                </label>



                                <label
                                    class="cursor-pointer rounded-xl border border-slate-200 bg-white p-4 transition hover:border-red-300 hover:bg-red-50"
                                >

                                    <div class="flex items-start gap-3">

                                        <input
                                            type="radio"
                                            name="closure_type"
                                            value="death"
                                            required
                                            @checked(
                                                old('closure_type') === 'death'
                                            )
                                            class="mt-1 border-slate-300 text-red-600 focus:ring-red-500"
                                        >

                                        <div>

                                            <div class="font-semibold text-slate-900">
                                                Death
                                            </div>

                                            <div class="mt-1 text-xs leading-5 text-slate-500">
                                                Close the admission following inpatient death.
                                            </div>

                                        </div>

                                    </div>

                                </label>


                            </div>

                        </div>



                        {{-- CLOSURE DATE / TIME --}}

                        <div>

                            <label
                                for="closed_at"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Closure Date & Time *
                            </label>

                            <input
                                id="closed_at"
                                name="closed_at"
                                type="datetime-local"
                                required
                                value="{{ old(
                                    'closed_at',
                                    now()->format('Y-m-d\TH:i')
                                ) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        {{-- REFERRAL DESTINATION --}}

                        <div
                            id="referral-destination-container"
                            class="hidden"
                        >

                            <label
                                for="referral_destination"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Referral Destination *
                            </label>

                            <input
                                id="referral_destination"
                                name="referral_destination"
                                type="text"
                                maxlength="255"
                                value="{{ old('referral_destination') }}"
                                placeholder="Hospital / medical centre..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        {{-- CLOSURE NOTES --}}

                        <div class="md:col-span-2">

                            <label
                                for="closure_notes"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Notes
                            </label>

                            <textarea
                                id="closure_notes"
                                name="closure_notes"
                                rows="5"
                                maxlength="5000"
                                placeholder="Disposition notes, clinical status, instructions, referral details or other relevant information..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('closure_notes') }}</textarea>

                        </div>


                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- SAFETY NOTE --}}
                {{-- ===================================================== --}}

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                    <div class="font-semibold text-amber-800">
                        Closing this admission
                    </div>

                    <div class="mt-2 text-sm leading-6 text-amber-700">

                        Closing the admission will release the patient's current
                        bed and make it available for another patient.

                        The active bed allocation will be closed automatically.

                        This operation should only be completed after the final
                        inpatient disposition has been confirmed.

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
                        onclick="return confirm('Close this IPD admission? The current bed will be released.');"
                        class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
                    >
                        Close Admission
                    </button>

                </div>

            </form>


        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- REFERRAL FIELD VISIBILITY --}}
    {{-- ============================================================= --}}

    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const closureInputs =
                    document.querySelectorAll(
                        'input[name="closure_type"]'
                    );


                const referralContainer =
                    document.getElementById(
                        'referral-destination-container'
                    );


                const referralInput =
                    document.getElementById(
                        'referral_destination'
                    );


                function updateReferralField()
                {
                    const selected =
                        document.querySelector(
                            'input[name="closure_type"]:checked'
                        );


                    const isReferral =
                        selected
                        &&
                        selected.value === 'referred';


                    if (isReferral)
                    {
                        referralContainer.classList.remove(
                            'hidden'
                        );

                        referralInput.required =
                            true;
                    }
                    else
                    {
                        referralContainer.classList.add(
                            'hidden'
                        );

                        referralInput.required =
                            false;
                    }
                }


                closureInputs.forEach(
                    function (input) {

                        input.addEventListener(
                            'change',
                            updateReferralField
                        );

                    }
                );


                updateReferralField();

            }
        );

    </script>

</x-app-layout>