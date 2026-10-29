<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            MHIS Procedure Details
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('mhis.procedures.index') }}"
               class="text-blue-700 hover:underline">
                ← Back to Package Master
            </a>

            <div class="bg-white shadow rounded-lg mt-4 p-6">

                <div class="border-b pb-4 mb-5">
                    <h3 class="text-2xl font-bold text-gray-900">
                        {{ $procedure->procedure_name }}
                    </h3>
                    <p class="text-gray-600 mt-2">
                        Code: {{ $procedure->procedure_code }}
                        | Specialty: {{ $procedure->specialty }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <p class="text-sm text-gray-500">Package</p>
                        <p class="font-semibold">{{ $procedure->package_name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Medical / Surgical</p>
                        <p>{{ $procedure->medical_surgical ?: 'Not specified' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Base Procedure Tariff</p>
                        <p class="text-xl font-bold text-green-700">
                            @if($procedure->procedure_price !== null)
                                ₹{{ number_format((float) $procedure->procedure_price, 2) }}
                            @else
                                {{ $procedure->source_price_text ?: 'Not specified' }}
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Average Length of Stay</p>
                        <p>{{ $procedure->average_length_of_stay ?: 'Not specified' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Stratification Options</p>
                        <p class="whitespace-pre-line">
                            {{ $procedure->stratification_options ?: 'Not applicable' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Stratification Rate</p>
                        <p class="whitespace-pre-line">
                            {{ $procedure->stratification_rate ?: 'Not applicable' }}
                        </p>
                    </div>

                </div>

                <div class="mt-6 border-t pt-5 space-y-5">

                    <div>
                        <h4 class="font-semibold mb-2">Implants</h4>
                        <p class="whitespace-pre-line text-gray-700">
                            {{ $procedure->implants ?: 'Not applicable' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="font-semibold mb-2">
                            Required Documents — Preauthorization
                        </h4>
                        <p class="whitespace-pre-line text-gray-700">
                            {{ $procedure->preauth_documents ?: 'Not specified' }}
                        </p>
                    </div>

                    <div>
                        <h4 class="font-semibold mb-2">
                            Required Documents — Claim Processing
                        </h4>
                        <p class="whitespace-pre-line text-gray-700">
                            {{ $procedure->claim_documents ?: 'Not specified' }}
                        </p>
                    </div>

                </div>

                <div class="mt-6 p-4 bg-gray-100 rounded-md text-sm text-gray-600">
                    HBP Master reference information only.
                    Final MHIS coverage, package eligibility, stratification
                    adjustments and approved claim amounts must be verified
                    before billing or claim submission.
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
