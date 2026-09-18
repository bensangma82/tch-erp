<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Reject Laboratory Sample
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Record the reason for rejecting the collected specimen.
                </p>
            </div>

            <a
                href="{{ route('laboratory.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back to Laboratory
            </a>

        </div>
    </x-slot>


    @php
        $order = $serviceOrderItem->serviceOrder;
        $patient = $order?->patient;
        $encounter = $order?->encounter;
    @endphp


    <div class="py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <ul class="list-inside list-disc text-sm text-red-700">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Sample Number
                            </div>

                            <div class="mt-1 text-xl font-bold text-slate-900">
                                {{ $sample->sample_no }}
                            </div>

                        </div>


                        <span class="inline-flex w-fit rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                            Reject Sample
                        </span>

                    </div>

                </div>


                <div class="grid gap-6 border-b border-slate-200 px-6 py-6 sm:grid-cols-2 lg:grid-cols-3">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Patient
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $patient?->full_name ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            UHID
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $patient?->uhid ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Investigation
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $serviceOrderItem->service_name }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Specimen
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $sample->specimen_type ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Collected At
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $sample->collected_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Collected By
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $sample->collectedBy?->name ?? '—' }}
                        </div>

                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('diagnostics.items.sample.reject', $serviceOrderItem) }}"
                    class="px-6 py-6"
                >

                    @csrf


                    <div>

                        <label
                            for="rejection_reason"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Reason for Rejection
                        </label>

                        <p class="mt-1 text-xs text-slate-500">
                            Examples: hemolysed sample, clotted specimen, insufficient volume, wrong container, labeling error or sample leakage.
                        </p>

                        <textarea
                            id="rejection_reason"
                            name="rejection_reason"
                            rows="5"
                            required
                            maxlength="2000"
                            class="mt-3 block w-full rounded-lg border-slate-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                            placeholder="Enter the reason for rejecting this sample..."
                        >{{ old('rejection_reason') }}</textarea>

                    </div>


                    <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">

                        <div class="text-sm font-semibold text-amber-800">
                            After rejection
                        </div>

                        <p class="mt-1 text-sm leading-6 text-amber-700">
                            This specimen will be marked rejected. The investigation will remain pending so that a replacement sample can be collected.
                        </p>

                    </div>


                    <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">

                        <a
                            href="{{ route('diagnostics.items.sample.create', $serviceOrderItem) }}"
                            class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            onclick="return confirm('Reject this laboratory sample? A new specimen will need to be collected.')"
                            class="inline-flex justify-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
                        >
                            Confirm Sample Rejection
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>