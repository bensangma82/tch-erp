<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    Laboratory Sample Collection
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Record specimen collection for this investigation.
                </p>
            </div>

            <a
                href="{{ route('laboratory.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back to Laboratory
            </a>
        </div>
    </x-slot>

    @php
        $order = $serviceOrderItem->serviceOrder;
        $patient = $order?->patient;
        $encounter = $order?->encounter;

        $defaultCollectedAt = old(
            'collected_at',
            now()->format('Y-m-d\TH:i')
        );
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                    <div class="font-semibold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Investigation
                            </div>

                            <div class="mt-1 text-xl font-bold text-slate-900">
                                {{ $serviceOrderItem->service_name }}
                            </div>

                            @if ($serviceOrderItem->service_code)
                                <div class="mt-1 text-sm text-slate-500">
                                    Code: {{ $serviceOrderItem->service_code }}
                                </div>
                            @endif
                        </div>

                        <div>
                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                                {{ ucfirst($order?->status ?? 'Unknown') }}
                            </span>
                        </div>

                    </div>
                </div>


                <div class="grid gap-6 border-b border-slate-200 px-6 py-6 md:grid-cols-2 lg:grid-cols-4">

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
                            MRD
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $patient?->mrd_number ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Age / Sex
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $patient?->age ?? '—' }}
                            /
                            {{ $patient?->sex ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Department
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $encounter?->department?->name ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Doctor
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ $encounter?->doctor?->full_name ?? '—' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Order Status
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ ucfirst($order?->status ?? '—') }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Test Status
                        </div>

                        <div class="mt-1 font-medium text-slate-900">
                            {{ ucfirst(str_replace('_', ' ', $serviceOrderItem->status)) }}
                        </div>
                    </div>

                </div>


                @if ($sample && $sample->status === 'collected')

                    <div class="px-6 py-6">

                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">

                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                                <div>
                                    <div class="text-sm font-semibold text-emerald-900">
                                        Sample already collected
                                    </div>

                                    <div class="mt-2 text-2xl font-bold tracking-wide text-emerald-950">
                                        {{ $sample->sample_no }}
                                    </div>
                                </div>

                                <span class="inline-flex w-fit rounded-full bg-emerald-200 px-3 py-1 text-xs font-semibold text-emerald-900">
                                    Collected
                                </span>

                            </div>


                            <div class="mt-5 grid gap-4 md:grid-cols-3">

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                                        Specimen
                                    </div>

                                    <div class="mt-1 font-medium text-emerald-950">
                                        {{ $sample->specimen_type ?? '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                                        Collected At
                                    </div>

                                    <div class="mt-1 font-medium text-emerald-950">
                                        {{ $sample->collected_at?->format('d M Y, h:i A') ?? '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                                        Collected By
                                    </div>

                                    <div class="mt-1 font-medium text-emerald-950">
                                        {{ $sample->collectedBy?->name ?? '—' }}
                                    </div>
                                </div>

                            </div>


                            @if ($sample->remarks)
                                <div class="mt-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                                        Remarks
                                    </div>

                                    <div class="mt-1 text-sm text-emerald-950">
                                        {{ $sample->remarks }}
                                    </div>
                                </div>
                            @endif


                            <div class="mt-5 flex flex-wrap gap-3">

                                <a
                                    href="{{ route('laboratory.index') }}"
                                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                >
                                    Return to Worklist
                                </a>

                                <a
                                    href="{{ route('diagnostics.items.sample.reject-form', $serviceOrderItem) }}"
                                    class="inline-flex items-center rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                                >
                                    Reject Sample
                                </a>

                            </div>

                        </div>

                    </div>

                @else

                    @if ($sample && $sample->status === 'rejected')

                        <div class="px-6 pt-6">
                            <div class="rounded-xl border border-red-200 bg-red-50 p-4">

                                <div class="font-semibold text-red-900">
                                    Previous sample rejected
                                </div>

                                <div class="mt-1 text-sm text-red-700">
                                    {{ $sample->sample_no }}
                                </div>

                                @if ($sample->rejection_reason)
                                    <div class="mt-2 text-sm text-red-800">
                                        Reason:
                                        {{ $sample->rejection_reason }}
                                    </div>
                                @endif

                                <div class="mt-2 text-sm text-red-700">
                                    A replacement sample may now be collected.
                                </div>

                            </div>
                        </div>

                    @endif


                    <form
                        method="POST"
                        action="{{ route('diagnostics.items.sample.store', $serviceOrderItem) }}"
                        class="px-6 py-6"
                    >
                        @csrf

                        <div class="grid gap-6 md:grid-cols-2">

                            <div>
                                <label
                                    for="specimen_type"
                                    class="block text-sm font-semibold text-slate-700"
                                >
                                    Specimen Type
                                </label>

                                <select
                                    id="specimen_type"
                                    name="specimen_type"
                                    required
                                    class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">
                                        Select specimen
                                    </option>

                                    @foreach ([
                                        'Whole Blood',
                                        'Serum',
                                        'Plasma',
                                        'EDTA Blood',
                                        'Citrated Blood',
                                        'Fluoride Blood',
                                        'Urine',
                                        '24 Hour Urine',
                                        'Stool',
                                        'Sputum',
                                        'CSF',
                                        'Pleural Fluid',
                                        'Ascitic Fluid',
                                        'Synovial Fluid',
                                        'Swab',
                                        'Tissue',
                                        'Other',
                                    ] as $specimenType)

                                        <option
                                            value="{{ $specimenType }}"
                                            @selected(old('specimen_type') === $specimenType)
                                        >
                                            {{ $specimenType }}
                                        </option>

                                    @endforeach
                                </select>
                            </div>


                            <div>
                                <label
                                    for="collected_at"
                                    class="block text-sm font-semibold text-slate-700"
                                >
                                    Collection Date & Time
                                </label>

                                <input
                                    type="datetime-local"
                                    id="collected_at"
                                    name="collected_at"
                                    value="{{ $defaultCollectedAt }}"
                                    required
                                    class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>


                            <div class="md:col-span-2">
                                <label
                                    for="remarks"
                                    class="block text-sm font-semibold text-slate-700"
                                >
                                    Remarks
                                    <span class="font-normal text-slate-400">
                                        (optional)
                                    </span>
                                </label>

                                <textarea
                                    id="remarks"
                                    name="remarks"
                                    rows="3"
                                    maxlength="2000"
                                    class="mt-2 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Optional sample collection remarks..."
                                >{{ old('remarks') }}</textarea>
                            </div>

                        </div>


                        <div class="mt-8 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-6">

                            <a
                                href="{{ route('laboratory.index') }}"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                            >
                                Record Sample Collection
                            </button>

                        </div>

                    </form>

                @endif

            </div>

        </div>
    </div>
</x-app-layout>