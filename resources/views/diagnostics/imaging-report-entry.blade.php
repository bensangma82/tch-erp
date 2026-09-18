<x-app-layout>

    <x-slot name="header">

        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                Imaging Report Entry
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Enter findings and impression for the imaging study.
            </p>
        </div>

    </x-slot>


    @php
        $order = $serviceOrderItem->serviceOrder;
        $patient = $order?->patient;
        $encounter = $order?->encounter;
        $existingResult = $serviceOrderItem->diagnosticResult;
    @endphp


    <div class="py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <ul class="list-inside list-disc text-sm text-red-700">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


                {{-- PATIENT / STUDY INFO --}}
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">

                    <div class="grid gap-6 sm:grid-cols-2">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Patient
                            </div>

                            <div class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $patient?->full_name ?? '—' }}
                            </div>

                            <div class="mt-2 text-sm text-gray-500">
                                UHID: {{ $patient?->uhid ?? '—' }}
                            </div>

                            <div class="text-sm text-gray-500">
                                MRD: {{ $patient?->mrd_number ?: '—' }}
                            </div>

                            <div class="text-sm text-gray-500">

                                @if ($patient?->age !== null)
                                    {{ $patient->age }} yrs
                                @else
                                    Age —
                                @endif

                                /

                                {{ $patient?->sex ?: '—' }}

                            </div>

                        </div>


                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Imaging Study
                            </div>

                            <div class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $serviceOrderItem->service_name }}
                            </div>

                            <div class="mt-2 text-sm text-gray-500">
                                Code: {{ $serviceOrderItem->service_code }}
                            </div>

                            <div class="text-sm text-gray-500">
                                Order: {{ $order?->order_no ?? '—' }}
                            </div>

                            <div class="text-sm text-gray-500">
                                Department: {{ $encounter?->department?->name ?? '—' }}
                            </div>

                            <div class="text-sm text-gray-500">
                                Doctor: {{ $encounter?->doctor?->full_name ?? 'Unassigned' }}
                            </div>

                        </div>

                    </div>

                </div>


                {{-- DRAFT STATUS --}}
                @if ($existingResult?->status === 'draft')

                    <div class="border-b border-blue-200 bg-blue-50 px-6 py-4">

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                            <div>

                                <div class="text-sm font-semibold text-blue-800">
                                    Draft Report
                                </div>

                                <p class="mt-1 text-sm text-blue-700">
                                    This report has been saved as a draft and may still be edited.
                                </p>

                            </div>


                            @if ($existingResult?->entered_at)

                                <div class="text-xs text-blue-700">
                                    Last saved:
                                    {{ $existingResult->entered_at->format('d M Y, h:i A') }}
                                </div>

                            @endif

                        </div>

                    </div>

                @endif


                {{-- REPORT FORM --}}
                <form
                    method="POST"
                    action="{{ route('diagnostics.items.imaging-report.save', $serviceOrderItem) }}"
                    class="p-6"
                >

                    @csrf


                    {{-- FINDINGS --}}
                    <div>

                        <label
                            for="findings"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Findings
                        </label>

                        <p class="mt-1 text-xs text-gray-500">
                            Enter the descriptive findings of the imaging study.
                        </p>

                        <textarea
                            id="findings"
                            name="findings"
                            rows="10"
                            autofocus
                            class="mt-3 block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Enter imaging findings..."
                        >{{ old('findings', $existingResult?->findings) }}</textarea>

                    </div>


                    {{-- IMPRESSION --}}
                    <div class="mt-6">

                        <label
                            for="impression"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Impression
                        </label>

                        <p class="mt-1 text-xs text-gray-500">
                            Enter the conclusion or radiological impression.
                        </p>

                        <textarea
                            id="impression"
                            name="impression"
                            rows="5"
                            class="mt-3 block w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            placeholder="Enter impression / conclusion..."
                        >{{ old('impression', $existingResult?->impression) }}</textarea>

                    </div>


                    {{-- ACTION EXPLANATION --}}
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">

                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-4">

                            <div class="text-sm font-semibold text-blue-800">
                                Save Draft
                            </div>

                            <p class="mt-1 text-sm text-blue-700">
                                Saves the report without completing the imaging study.
                            </p>

                            <p class="mt-2 text-xs text-blue-600">
                                You can return later and continue editing.
                            </p>

                        </div>


                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4">

                            <div class="text-sm font-semibold text-amber-800">
                                Finalize Report
                            </div>

                            <p class="mt-1 text-sm text-amber-700">
                                Finalizing marks the imaging investigation as completed.
                            </p>

                            <p class="mt-2 text-xs text-amber-700">
                                Findings or impression must be entered before finalization.
                            </p>

                        </div>

                    </div>


                    {{-- ACTION BUTTONS --}}
                    <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:items-center sm:justify-between">

                        <a
                            href="{{ route('imaging.index') }}"
                            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Back to Imaging
                        </a>


                        <div class="flex flex-col gap-3 sm:flex-row">

                            <button
                                type="submit"
                                name="action"
                                value="save_draft"
                                class="inline-flex justify-center rounded-lg border border-blue-600 bg-white px-5 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50"
                            >
                                Save Draft
                            </button>


                            <button
                                type="submit"
                                name="action"
                                value="finalize"
                                onclick="return confirm('Finalize this imaging report? The imaging investigation will be marked completed.')"
                                class="inline-flex justify-center rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700"
                            >
                                Finalize Report
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>