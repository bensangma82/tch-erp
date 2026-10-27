<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">
                Admission Clinical Record
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $admission->patient?->full_name }}
                · UHID: {{ $admission->patient?->uhid }}
                · Admission: {{ $admission->admission_no }}
            </p>
        </div>
    </x-slot>


    <style id="clinical-record-print-styles">
        @page {
            size: A4 portrait;
            margin: 12mm;
        }



        /* clinical-print-pagebreak-rules */
        @media print {
            #clinical-print-header {
                break-after: avoid;
            }

            .print-record h3,
            .print-record h4 {
                break-after: avoid;
                page-break-after: avoid;
            }

            .print-record thead {
                display: table-header-group;
            }

            .print-record tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .print-record section > div.border {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        @media screen {
            #clinical-print-header {
                display: none !important;
            }
        }

        @media print {
            #clinical-print-header {
                display: block !important;
                margin-bottom: 18px;
                padding-bottom: 12px;
                border-bottom: 2px solid #172033;
            }
        }

        @media print {
            body {
                background: white !important;
                color: black !important;
                font-size: 10pt;
            }

            nav, aside, header, .print-hidden {
                display: none !important;
            }

            main {
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-record {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-record section {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                break-inside: auto;
                margin-bottom: 12px;
                padding: 12px !important;
            }

            .print-record table {
                width: 100%;
                border-collapse: collapse;
            }

            .print-record th,
            .print-record td {
                border: 1px solid #ddd;
                padding: 4px;
            }

            .print-record tr {
                break-inside: avoid;
            }
        }
    </style>

    <div class="print-hidden px-6 pt-4 flex justify-end">
        <button
            type="button"
            onclick="window.print()"
            class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
        >
            Print Clinical Record
        </button>
    </div>

    <div class="py-6">
        <div class="print-record max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('medical-records.discharge-history.patient', $admission->patient_id) }}"
               class="print-hidden text-indigo-700 hover:underline text-sm">
                Back to Admission History
            </a>


            <div id="clinical-print-header" class="hidden">
                <div class="text-center">
                    <h1 class="text-xl font-bold">
                        TURA CHRISTIAN HOSPITAL
                    </h1>
                    <p class="text-sm">
                        Tura, West Garo Hills, Meghalaya
                    </p>
                    <h2 class="mt-3 text-lg font-semibold">
                        CONSOLIDATED CLINICAL RECORD
                    </h2>
                    <p class="text-sm">
                        Discharge Summary and Investigation Reports
                    </p>
                </div>

                <div class="mt-4 border-t border-b py-3 text-sm">
                    <strong>Patient:</strong>
                    {{ $admission->patient?->full_name }}
                    <br>
                    <strong>UHID:</strong>
                    {{ $admission->patient?->uhid }}
                    <br>
                    <strong>Admission No:</strong>
                    {{ $admission->admission_no }}
                </div>
            </div>

            <section class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-lg mb-4">
                    Admission Details
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>Admission:</strong>
                        {{ $admission->admission_no }}
                    </div>
                    <div>
                        <strong>Department:</strong>
                        {{ $admission->department?->name ?? '-' }}
                    </div>
                    <div>
                        <strong>Admitted:</strong>
                        {{ $admission->admitted_at?->format('d M Y H:i') ?? '-' }}
                    </div>
                    <div>
                        <strong>Discharged:</strong>
                        {{ $admission->discharged_at?->format('d M Y H:i') ?? '-' }}
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-lg mb-4">
                    Discharge Summary
                </h3>

                @if($admission->dischargeSummary)
                    <div class="space-y-3 text-sm">
                        <div>
                            <strong>Final Diagnosis</strong>
                            <p class="whitespace-pre-line">
                                {{ $admission->dischargeSummary->final_diagnosis }}
                            </p>
                        </div>

                        <div>
                            <strong>Hospital Course</strong>
                            <p class="whitespace-pre-line">
                                {{ $admission->dischargeSummary->hospital_course }}
                            </p>
                        </div>

                        <div>
                            <strong>Treatment Given</strong>
                            <p class="whitespace-pre-line">
                                {{ $admission->dischargeSummary->treatment_given }}
                            </p>
                        </div>

                        <div>
                            <strong>Discharge Medications</strong>
                            <p class="whitespace-pre-line">
                                {{ $admission->dischargeSummary->discharge_medications }}
                            </p>
                        </div>

                        <div>
                            <strong>Follow-up Advice</strong>
                            <p class="whitespace-pre-line">
                                {{ $admission->dischargeSummary->follow_up_advice }}
                            </p>
                        </div>

                        <a href="{{ route('ipd.discharge-summary.show', $admission) }}"
                           class="print-hidden inline-block text-indigo-700 font-medium hover:underline">
                            View Full Finalised Discharge Summary
                        </a>
                    </div>
                @else
                    <p class="text-slate-500 text-sm">
                        No finalised discharge summary is available.
                    </p>
                @endif
            </section>

            <section class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-lg mb-2">
                    Laboratory and Radiology Investigations
                </h3>

                <p class="text-xs text-amber-700 mb-5">
                    Only final reports are displayed.
                    Reports without a verification timestamp are not
                    represented as independently verified.
                </p>

                @forelse($investigations as $item)
                    @php
                        $result = $item->diagnosticResult;
                    @endphp

                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex flex-wrap justify-between gap-3 mb-3">
                            <div>
                                <h4 class="font-semibold">
                                    {{ $item->service_name }}
                                </h4>
                                <p class="text-xs text-slate-500">
                                    {{ ucfirst($item->category ?? 'Investigation') }}
                                    · Reported:
                                    {{ $result->entered_at?->format('d M Y H:i') ?? '-' }}
                                </p>
                            </div>

                            @if($result->verified_at)
                                <span class="text-xs text-emerald-700 font-medium">
                                    Verified
                                </span>
                            @else
                                <span class="text-xs text-amber-700 font-medium">
                                    Final — verification not recorded
                                </span>
                            @endif
                        </div>

                        @if($result->items->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm border-collapse">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="p-2 text-left">Parameter</th>
                                            <th class="p-2 text-left">Result</th>
                                            <th class="p-2 text-left">Unit</th>
                                            <th class="p-2 text-left">Reference</th>
                                            <th class="p-2 text-left">Flag</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($result->items as $parameter)
                                            <tr class="border-t">
                                                <td class="p-2">
                                                    {{ $parameter->parameter_name }}
                                                </td>
                                                <td class="p-2">
                                                    {{ $parameter->result_value }}
                                                </td>
                                                <td class="p-2">
                                                    {{ $parameter->unit }}
                                                </td>
                                                <td class="p-2">
                                                    {{ $parameter->reference_range }}
                                                </td>
                                                <td class="p-2">
                                                    {{ $parameter->flag }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($result->findings)
                            <div class="mt-3 text-sm">
                                <strong>Findings</strong>
                                <p class="whitespace-pre-line">
                                    {{ $result->findings }}
                                </p>
                            </div>
                        @endif

                        @if($result->impression)
                            <div class="mt-3 text-sm">
                                <strong>Impression</strong>
                                <p class="whitespace-pre-line">
                                    {{ $result->impression }}
                                </p>
                            </div>
                        @endif

                        @if($result->result_text)
                            <div class="mt-3 text-sm">
                                <strong>Report Comment</strong>
                                <p class="whitespace-pre-line">
                                    {{ $result->result_text }}
                                </p>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500 text-sm">
                        No final laboratory or radiology reports are
                        linked to this admission.
                    </p>
                @endforelse
            </section>

        </div>
    </div>
</x-app-layout>
