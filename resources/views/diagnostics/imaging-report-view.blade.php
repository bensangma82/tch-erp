<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between screen-only">

            <div>

                <h2 class="text-xl font-semibold text-gray-800">
                    Imaging Report
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Finalized imaging report.
                </p>

            </div>

            <a
                href="{{ route('imaging.index') }}"
                class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                Back to Imaging
            </a>

        </div>

    </x-slot>


    @php
        $order = $serviceOrderItem->serviceOrder;
        $patient = $order?->patient;
        $encounter = $order?->encounter;
        $report = $serviceOrderItem->diagnosticResult;
    @endphp


    <style>
        @media print {

            @page {
                size: A4;
                margin: 14mm;
            }

            body {
                background: white !important;
            }

            .screen-only {
                display: none !important;
            }

            aside,
            nav {
                display: none !important;
            }

            .print-wrapper {
                max-width: none !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-card {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .print-section {
                break-inside: avoid;
            }

            .print-header {
                display: block !important;
            }

            .print-footer {
                display: block !important;
            }
        }

        @media screen {
            .print-header {
                display: none;
            }

            .print-footer {
                display: none;
            }
        }
    </style>


    <div class="py-6 print-wrapper">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 print-wrapper">


            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm print-card">


                {{-- PRINT HEADER --}}
                <div class="print-header mb-6 border-b border-gray-900 pb-4 text-center">

                    <div class="text-2xl font-bold">
                        Tura Christian Hospital
                    </div>

                    <div class="mt-1 text-sm">
                        Tura, West Garo Hills, Meghalaya
                    </div>

                    <div class="mt-3 text-lg font-semibold uppercase tracking-wide">
                        Imaging Report
                    </div>

                </div>


                {{-- PATIENT / STUDY INFORMATION --}}
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-5 print-section">

                    <div class="grid gap-6 sm:grid-cols-2">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Patient
                            </div>

                            <div class="mt-1 text-lg font-semibold text-gray-900">
                                {{ $patient?->full_name ?? '—' }}
                            </div>

                            <div class="mt-2 text-sm text-gray-600">
                                UHID: {{ $patient?->uhid ?? '—' }}
                            </div>

                            <div class="text-sm text-gray-600">
                                MRD: {{ $patient?->mrd_number ?: '—' }}
                            </div>

                            <div class="text-sm text-gray-600">

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

                            <div class="mt-2 text-sm text-gray-600">
                                Code: {{ $serviceOrderItem->service_code }}
                            </div>

                            <div class="text-sm text-gray-600">
                                Order: {{ $order?->order_no ?? '—' }}
                            </div>

                            <div class="text-sm text-gray-600">
                                Department: {{ $encounter?->department?->name ?? '—' }}
                            </div>

                            <div class="text-sm text-gray-600">
                                Doctor: {{ $encounter?->doctor?->full_name ?? 'Unassigned' }}
                            </div>

                        </div>

                    </div>

                </div>


                {{-- REPORT BODY --}}
                <div class="px-6 py-6">


                    {{-- FINDINGS --}}
                    <div class="mb-6 print-section">

                        <div class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                            Findings
                        </div>

                        <div class="min-h-[150px] rounded-lg border border-gray-200 bg-gray-50 p-5">

                            <div class="whitespace-pre-wrap text-sm leading-7 text-gray-900">
                                {{ $report?->findings ?: '—' }}
                            </div>

                        </div>

                    </div>


                    {{-- IMPRESSION --}}
                    <div class="print-section">

                        <div class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">
                            Impression
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-5">

                            <div class="whitespace-pre-wrap text-sm font-medium leading-7 text-gray-900">
                                {{ $report?->impression ?: '—' }}
                            </div>

                        </div>

                    </div>


                    {{-- REPORT DETAILS --}}
                    <div class="mt-6 grid gap-4 border-t border-gray-200 pt-5 sm:grid-cols-2 print-section">

                        <div>

                            <div class="text-xs uppercase tracking-wide text-gray-500">
                                Reported By
                            </div>

                            <div class="mt-1 text-sm font-medium text-gray-800">
                                {{ $report?->enteredBy?->name ?? '—' }}
                            </div>

                        </div>


                        <div>

                            <div class="text-xs uppercase tracking-wide text-gray-500">
                                Reported At
                            </div>

                            <div class="mt-1 text-sm font-medium text-gray-800">
                                {{ $report?->entered_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>

                        </div>

                    </div>


                    @if ($report?->verified_at)

                        <div class="mt-5 grid gap-4 sm:grid-cols-2 print-section">

                            <div>

                                <div class="text-xs uppercase tracking-wide text-gray-500">
                                    Verified By
                                </div>

                                <div class="mt-1 text-sm font-medium text-gray-800">
                                    {{ $report?->verifiedBy?->name ?? '—' }}
                                </div>

                            </div>


                            <div>

                                <div class="text-xs uppercase tracking-wide text-gray-500">
                                    Verified At
                                </div>

                                <div class="mt-1 text-sm font-medium text-gray-800">
                                    {{ $report?->verified_at?->format('d M Y, h:i A') ?? '—' }}
                                </div>

                            </div>

                        </div>

                    @endif


                    {{-- PRINT FOOTER --}}
                    <div class="print-footer mt-12 border-t border-gray-300 pt-4 text-xs text-gray-600">

                        <div class="flex items-end justify-between">

                            <div>
                                Generated from TCH Hospital ERP
                            </div>

                            <div class="text-right">
                                <div>
                                    ______________________________
                                </div>

                                <div class="mt-1">
                                    Authorized Signature
                                </div>
                            </div>

                        </div>

                    </div>

                </div>


                {{-- SCREEN BUTTONS --}}
                <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 screen-only">

                    <a
                        href="{{ route('imaging.index') }}"
                        class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Back
                    </a>


                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Print Report
                    </button>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>