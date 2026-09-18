@php
    $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();

    $barcodeSvg = $barcodeGenerator->getBarcode(
        $patient->uhid,
        $barcodeGenerator::TYPE_CODE_128,
        2,
        60
    );
@endphp


<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Patient Profile
                </h2>

                <div class="mt-1 space-y-1 text-sm text-gray-500">

                    <div>
                        UHID: {{ $patient->uhid }}
                    </div>

                    <div>
                        MRD: {{ $patient->mrd_number ?: 'Not assigned' }}
                    </div>

                </div>

            </div>

<a
    href="{{ route('patients.card', $patient) }}"
    target="_blank"
    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
>
    Print Patient Card
</a>
            <div class="flex flex-wrap gap-3">

                <a
                    href="{{ route('opd.create', ['patient_id' => $patient->id]) }}"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Register OPD
                </a>

                <a
                    href="{{ route('patients.index') }}"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Back to Patients
                </a>

            </div>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- SUCCESS MESSAGE --}}
            @if (session('success'))

                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- PATIENT SUMMARY --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-6 py-4">

                    <div class="flex items-start justify-between gap-4">

                        <div>

                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $patient->full_name }}
                            </h3>

                            <div class="mt-1 space-y-1 text-sm text-gray-500">

                                <div>
                                    UHID: {{ $patient->uhid }}
                                </div>

                                <div>
                                    MRD: {{ $patient->mrd_number ?: 'Not assigned' }}
                                </div>

                            </div>

                        </div>


                        @if ($patient->is_active)

                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                Active
                            </span>

                        @else

                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                                Inactive
                            </span>

                        @endif

                    </div>

                </div>


                <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-4">

                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Sex
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->sex }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Date of Birth
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->date_of_birth?->format('d-m-Y') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Age
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->age !== null ? $patient->age . ' years' : '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Blood Group
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->blood_group ?: 'Unknown' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Mobile
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->phone ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Alternate Mobile
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->alternate_phone ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Email
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->email ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Known Allergies
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->known_allergies ?: 'None recorded' }}
                        </div>

                    </div>

                </div>



                {{-- BARCODE --}}
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-5">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Patient Barcode
                            </div>

                            <div class="mt-1 text-sm text-gray-600">
                                Scan this barcode to retrieve the patient on the next visit.
                            </div>

                        </div>


                        <div class="inline-block rounded-lg border border-gray-200 bg-white p-3">

                            {!! $barcodeSvg !!}

                            <div class="mt-2 text-center text-xs font-medium text-gray-700">
                                {{ $patient->uhid }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>



            {{-- ADDRESS + EMERGENCY CONTACT --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">


                {{-- ADDRESS --}}
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            Address
                        </h3>

                    </div>


                    <div class="space-y-3 p-6 text-sm text-gray-700">

                        <div>
                            {{ $patient->address ?: 'No address recorded' }}
                        </div>


                        @php
                            $location = collect([
                                $patient->locality,
                                $patient->district,
                                $patient->state,
                                $patient->pin_code,
                            ])
                                ->filter()
                                ->implode(', ');
                        @endphp


                        <div>
                            {{ $location ?: '—' }}
                        </div>

                    </div>

                </div>



                {{-- EMERGENCY CONTACT --}}
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

                    <div class="border-b border-gray-200 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            Emergency Contact
                        </h3>

                    </div>


                    <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-3">

                        <div>

                            <div class="text-xs uppercase tracking-wide text-gray-500">
                                Name
                            </div>

                            <div class="mt-1 text-sm font-medium text-gray-900">
                                {{ $patient->emergency_contact_name ?: '—' }}
                            </div>

                        </div>


                        <div>

                            <div class="text-xs uppercase tracking-wide text-gray-500">
                                Phone
                            </div>

                            <div class="mt-1 text-sm font-medium text-gray-900">
                                {{ $patient->emergency_contact_phone ?: '—' }}
                            </div>

                        </div>


                        <div>

                            <div class="text-xs uppercase tracking-wide text-gray-500">
                                Relationship
                            </div>

                            <div class="mt-1 text-sm font-medium text-gray-900">
                                {{ $patient->emergency_contact_relation ?: '—' }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>



            {{-- IDENTIFICATION / INSURANCE --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-6 py-4">

                    <h3 class="font-semibold text-gray-800">
                        Identification / Insurance
                    </h3>

                </div>


                <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                    <div>

                        <div class="text-xs uppercase tracking-wide text-gray-500">
                            ABHA Number
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->abha_number ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs uppercase tracking-wide text-gray-500">
                            MHIS Number
                        </div>

                        <div class="mt-1 text-sm font-medium text-gray-900">
                            {{ $patient->mhis_number ?: '—' }}
                        </div>

                    </div>

                </div>

            </div>



            {{-- VISIT HISTORY --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-6 py-4">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="font-semibold text-gray-800">
                                Visit History
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                OPD, emergency, admission and other encounters will appear here.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="p-6">

                    @if ($patient->encounters()->exists())

                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-gray-200">

                                <thead class="bg-gray-50">

                                    <tr>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Date
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Encounter
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Type
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Department
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Doctor
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Status
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-gray-100">

                                    @foreach (
                                        $patient->encounters()
                                            ->with(['department', 'doctor'])
                                            ->latest('encounter_date')
                                            ->limit(10)
                                            ->get()
                                        as $encounter
                                    )

                                        <tr class="hover:bg-gray-50">

                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $encounter->encounter_date?->format('d-m-Y') ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                {{ $encounter->encounter_no }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $encounter->encounter_type }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $encounter->department?->name ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                                            </td>

                                            <td class="px-4 py-3">

                                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                                    {{ ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $encounter->status
                                                        )
                                                    ) }}
                                                </span>

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @else

                        <div class="py-8 text-center">

                            <div class="text-sm font-medium text-gray-700">
                                No visits recorded yet.
                            </div>

                            <p class="mt-1 text-sm text-gray-500">
                                Register this patient for OPD to create the first encounter.
                            </p>

                            <a
                                href="{{ route('opd.create', ['patient_id' => $patient->id]) }}"
                                class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                Register OPD
                            </a>

                        </div>

                    @endif

                </div>

            </div>


        </div>

    </div>

</x-app-layout>