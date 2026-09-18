<x-app-layout>

    <x-slot name="header">
        <div class="screen-only flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Laboratory Result
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Finalized laboratory investigation report.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('laboratory.index') }}"
                    class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Back to Laboratory
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
    </x-slot>


    @php
        $order = $serviceOrderItem->serviceOrder;
        $patient = $order?->patient;
        $encounter = $order?->encounter;
        $result = $serviceOrderItem->diagnosticResult;
        $sample = $serviceOrderItem->diagnosticSample ?? null;
        $resultItems = $result?->items ?? collect();

        $reportStatus =
            $result?->status === 'verified'
                ? 'Verified'
                : 'Final';
    @endphp


    <style>
        /*
        |--------------------------------------------------------------------------
        | Screen report
        |--------------------------------------------------------------------------
        */

        .lab-report-shell {
            max-width: 980px;
            margin: 0 auto;
        }

        .lab-report {
            background: #ffffff;
            border: 1px solid #dbe3ee;
            border-radius: 18px;
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.08),
                0 2px 8px rgba(15, 23, 42, 0.04);
            overflow: hidden;
            color: #0f172a;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .lab-report-inner {
            padding: 28px 32px 24px;
        }

        .lab-brand {
            display: grid;
            grid-template-columns: 78px minmax(0, 1fr) auto;
            align-items: center;
            gap: 18px;
            padding-bottom: 18px;
            border-bottom: 3px solid #10213c;
        }

        .lab-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .hospital-name {
            margin: 0;
            font-size: 25px;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #10213c;
        }

        .hospital-address {
            margin-top: 5px;
            font-size: 12px;
            color: #64748b;
        }

        .department-name {
            margin-top: 8px;
            font-size: 10px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #48617f;
        }

        .report-heading {
            text-align: right;
        }

        .report-heading-title {
            font-size: 22px;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #10213c;
        }

        .report-status {
            display: inline-flex;
            margin-top: 8px;
            border-radius: 999px;
            padding: 4px 10px;
            background: #eaf1f8;
            color: #16375d;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .report-time {
            margin-top: 6px;
            font-size: 10px;
            color: #64748b;
        }

        .meta-panel {
            margin-top: 15px;
            border: 1px solid #dce4ee;
            border-radius: 13px;
            overflow: hidden;
        }

        .meta-panel-title {
            padding: 8px 13px;
            background: #f6f8fb;
            border-bottom: 1px solid #dce4ee;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #4a607b;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            column-gap: 18px;
            row-gap: 10px;
            padding: 12px 14px;
        }

        .meta-item-wide {
            grid-column: span 2;
        }

        .meta-label {
            margin-bottom: 2px;
            font-size: 8px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: #7b8ba0;
        }

        .meta-value {
            font-size: 11px;
            line-height: 1.3;
            font-weight: 650;
            color: #172033;
        }

        .sample-strip {
            display: grid;
            grid-template-columns: 1.25fr 1fr 1.25fr 1.15fr;
            gap: 16px;
            padding: 10px 14px;
            border-top: 1px solid #e3e9f0;
            background: #fbfcfe;
        }

        .result-panel {
            margin-top: 15px;
            border: 1px solid #d8e1eb;
            border-radius: 13px;
            overflow: hidden;
        }

        .result-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 13px;
            background:
                linear-gradient(
                    90deg,
                    #f5f8fb 0%,
                    #fbfcfe 100%
                );
            border-bottom: 1px solid #d8e1eb;
        }

        .result-panel-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #31465f;
        }

        .result-panel-status {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #16375d;
        }

        .result-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .result-table th {
            padding: 8px 11px;
            background: #fafbfd;
            border-bottom: 1px solid #dfe6ee;
            text-align: left;
            font-size: 8px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: #60738b;
        }

        .result-table td {
            padding: 8px 11px;
            border-bottom: 1px solid #edf1f5;
            font-size: 11px;
            line-height: 1.25;
            color: #253247;
            vertical-align: middle;
        }

        .result-table tbody tr:last-child td {
            border-bottom: none;
        }

        .parameter-name {
            font-weight: 700;
            color: #172033;
        }

        .parameter-result {
            font-weight: 800;
            color: #10213c;
        }

        .result-abnormal {
            color: #a16207;
        }

        .result-critical {
            color: #b91c1c;
        }

        .flag-badge {
            display: inline-flex;
            min-width: 27px;
            justify-content: center;
            border-radius: 999px;
            padding: 2px 7px;
            font-size: 8px;
            font-weight: 800;
        }

        .flag-normal {
            background: #f1f5f9;
            color: #64748b;
        }

        .flag-abnormal {
            background: #fef3c7;
            color: #a16207;
        }

        .flag-critical {
            background: #fee2e2;
            color: #b91c1c;
        }

        .comment-box {
            margin-top: 12px;
            padding: 10px 13px;
            border: 1px solid #dfe6ee;
            border-radius: 11px;
            background: #fbfcfe;
        }

        .comment-title {
            margin-bottom: 4px;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #65778e;
        }

        .comment-text {
            white-space: pre-wrap;
            font-size: 10px;
            line-height: 1.45;
            color: #2b3748;
        }

        .report-footer {
            display: grid;
            grid-template-columns: 1fr 210px;
            gap: 26px;
            align-items: end;
            margin-top: 18px;
        }

        .footer-note {
            font-size: 8px;
            line-height: 1.5;
            color: #7a8797;
        }

        .signature {
            padding-top: 22px;
            border-top: 1px solid #6b7d92;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            color: #465870;
        }

        .screen-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }


        /*
        |--------------------------------------------------------------------------
        | A4 print layout
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Do not give the report a 297mm min-height in print mode.
        | With browser print margins that alone can force a second page.
        |
        */

        @page {
            size: A4 portrait;
            margin: 7mm;
        }

        @media print {

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body * {
                visibility: hidden;
            }

            #laboratory-report,
            #laboratory-report * {
                visibility: visible;
            }

            #laboratory-report {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .screen-only,
            .screen-actions,
            aside,
            nav,
            header {
                display: none !important;
            }

            .print-wrapper,
            .lab-report-shell {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .lab-report {
                width: 100% !important;
                min-height: 0 !important;
                height: auto !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                overflow: visible !important;
            }

            .lab-report-inner {
                padding: 0 !important;
            }

            .lab-brand {
                grid-template-columns: 56px minmax(0, 1fr) 185px !important;
                gap: 12px !important;
                padding-bottom: 9px !important;
                border-bottom-width: 2px !important;
            }

            .lab-logo {
                width: 52px !important;
                height: 52px !important;
            }

            .hospital-name {
                font-size: 19px !important;
            }

            .hospital-address {
                margin-top: 2px !important;
                font-size: 9px !important;
            }

            .department-name {
                margin-top: 4px !important;
                font-size: 7.5px !important;
            }

            .report-heading-title {
                font-size: 16px !important;
            }

            .report-status {
                margin-top: 4px !important;
                padding: 2px 7px !important;
                font-size: 7px !important;
            }

            .report-time {
                margin-top: 3px !important;
                font-size: 7.5px !important;
            }

            .meta-panel {
                margin-top: 9px !important;
                border-radius: 8px !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .meta-panel-title {
                padding: 5px 9px !important;
                font-size: 7px !important;
            }

            .meta-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                gap: 5px 12px !important;
                padding: 7px 9px !important;
            }

            .meta-label {
                margin-bottom: 1px !important;
                font-size: 6.5px !important;
            }

            .meta-value {
                font-size: 8.5px !important;
                line-height: 1.2 !important;
            }

            .sample-strip {
                grid-template-columns: 1.25fr 1fr 1.25fr 1.15fr !important;
                gap: 10px !important;
                padding: 6px 9px !important;
            }

            .result-panel {
                margin-top: 9px !important;
                border-radius: 8px !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .result-panel-head {
                padding: 5px 9px !important;
            }

            .result-panel-title,
            .result-panel-status {
                font-size: 7px !important;
            }

            .result-table th {
                padding: 5px 8px !important;
                font-size: 6.5px !important;
            }

            .result-table td {
                padding: 5px 8px !important;
                font-size: 8.5px !important;
                line-height: 1.15 !important;
            }

            .flag-badge {
                min-width: 20px !important;
                padding: 1px 5px !important;
                font-size: 6.5px !important;
            }

            .comment-box {
                margin-top: 7px !important;
                padding: 6px 9px !important;
                border-radius: 7px !important;
                break-inside: avoid !important;
            }

            .comment-title {
                font-size: 6.5px !important;
            }

            .comment-text {
                font-size: 8px !important;
                line-height: 1.3 !important;
            }

            .report-footer {
                grid-template-columns: 1fr 170px !important;
                gap: 18px !important;
                margin-top: 10px !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .footer-note {
                font-size: 6.5px !important;
                line-height: 1.35 !important;
            }

            .signature {
                padding-top: 15px !important;
                font-size: 7px !important;
            }

            table,
            tr,
            td,
            th {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
        }
    </style>


    <div class="py-6 print-wrapper">

        <div class="lab-report-shell px-4 sm:px-6 lg:px-8 print-wrapper">

            <section
                id="laboratory-report"
                class="lab-report"
            >

                <div class="lab-report-inner">


                    {{-- PREMIUM LETTERHEAD --}}
                    <div class="lab-brand">

                        <img
                            src="{{ asset('images/TCH_favicon.png') }}"
                            alt="Tura Christian Hospital"
                            class="lab-logo"
                        >


                        <div>

                            <h1 class="hospital-name">
                                Tura Christian Hospital
                            </h1>

                            <div class="hospital-address">
                                Tura, West Garo Hills, Meghalaya
                            </div>

                            <div class="department-name">
                                Department of Laboratory Medicine
                            </div>

                        </div>


                        <div class="report-heading">

                            <div class="report-heading-title">
                                Laboratory Report
                            </div>

                            <div class="report-status">
                                {{ $reportStatus }} Report
                            </div>

                            <div class="report-time">
                                {{ $result?->entered_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}
                            </div>

                        </div>

                    </div>


                    {{-- PATIENT + INVESTIGATION DETAILS --}}
                    <div class="meta-panel">

                        <div class="meta-panel-title">
                            Patient & Investigation Details
                        </div>


                        <div class="meta-grid">

                            <div class="meta-item-wide">
                                <div class="meta-label">
                                    Patient Name
                                </div>

                                <div class="meta-value">
                                    {{ $patient?->full_name ?? '—' }}
                                </div>
                            </div>


                            <div>
                                <div class="meta-label">
                                    UHID
                                </div>

                                <div class="meta-value">
                                    {{ $patient?->uhid ?? '—' }}
                                </div>
                            </div>


                            <div>
                                <div class="meta-label">
                                    MRD
                                </div>

                                <div class="meta-value">
                                    {{ $patient?->mrd_number ?: '—' }}
                                </div>
                            </div>


                            <div>
                                <div class="meta-label">
                                    Age / Sex
                                </div>

                                <div class="meta-value">
                                    @if ($patient?->age !== null)
                                        {{ $patient->age }} yrs
                                    @else
                                        —
                                    @endif
                                    /
                                    {{ $patient?->sex ?: '—' }}
                                </div>
                            </div>


                            <div>
                                <div class="meta-label">
                                    Mobile
                                </div>

                                <div class="meta-value">
                                    {{ $patient?->phone ?: '—' }}
                                </div>
                            </div>


                            <div>
                                <div class="meta-label">
                                    Blood Group
                                </div>

                                <div class="meta-value">
                                    {{ $patient?->blood_group ?: '—' }}
                                </div>
                            </div>


                            <div>
                                <div class="meta-label">
                                    Order No.
                                </div>

                                <div class="meta-value">
                                    {{ $order?->order_no ?? '—' }}
                                </div>
                            </div>


                            <div class="meta-item-wide">
                                <div class="meta-label">
                                    Investigation
                                </div>

                                <div class="meta-value">
                                    {{ $serviceOrderItem->service_name }}
                                    @if ($serviceOrderItem->service_code)
                                        <span class="muted">
                                            · {{ $serviceOrderItem->service_code }}
                                        </span>
                                    @endif
                                </div>
                            </div>


                            <div>
                                <div class="meta-label">
                                    Department
                                </div>

                                <div class="meta-value">
                                    {{ $encounter?->department?->name ?? '—' }}
                                </div>
                            </div>


                            <div>
                                <div class="meta-label">
                                    Referring Doctor
                                </div>

                                <div class="meta-value">
                                    {{ $encounter?->doctor?->full_name ?? 'Unassigned' }}
                                </div>
                            </div>

                        </div>


                        @if ($serviceOrderItem->requires_sample)

                            <div class="sample-strip">

                                <div>
                                    <div class="meta-label">
                                        Sample No.
                                    </div>

                                    <div class="meta-value">
                                        {{ $sample?->sample_no ?? '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="meta-label">
                                        Specimen
                                    </div>

                                    <div class="meta-value">
                                        {{ $sample?->specimen_type ?? '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="meta-label">
                                        Collected At
                                    </div>

                                    <div class="meta-value">
                                        {{ $sample?->collected_at?->format('d M Y, h:i A') ?? '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="meta-label">
                                        Reported At
                                    </div>

                                    <div class="meta-value">
                                        {{ $result?->entered_at?->format('d M Y, h:i A') ?? '—' }}
                                    </div>
                                </div>

                            </div>

                        @endif

                    </div>


                    {{-- STRUCTURED RESULT TABLE --}}
                    <div class="result-panel">

                        <div class="result-panel-head">

                            <div class="result-panel-title">
                                Test Results
                            </div>

                            <div class="result-panel-status">
                                {{ $reportStatus }}
                            </div>

                        </div>


                        @if ($resultItems->count())

                            <table class="result-table">

                                <thead>
                                    <tr>
                                        <th style="width: 31%;">
                                            Parameter
                                        </th>

                                        <th style="width: 18%;">
                                            Result
                                        </th>

                                        <th style="width: 15%;">
                                            Unit
                                        </th>

                                        <th style="width: 25%;">
                                            Reference Range
                                        </th>

                                        <th style="width: 11%; text-align:center;">
                                            Flag
                                        </th>
                                    </tr>
                                </thead>


                                <tbody>

                                    @foreach ($resultItems as $item)

                                        @php
                                            $flag = $item->flag;

                                            $flagLabel = match ($flag) {
                                                'low' => 'L',
                                                'high' => 'H',
                                                'critical_low' => 'CL',
                                                'critical_high' => 'CH',
                                                'abnormal' => 'A',
                                                default => '—',
                                            };

                                            $flagClass = match ($flag) {
                                                'critical_low',
                                                'critical_high' =>
                                                    'flag-critical',

                                                'low',
                                                'high',
                                                'abnormal' =>
                                                    'flag-abnormal',

                                                default =>
                                                    'flag-normal',
                                            };

                                            $resultClass = match ($flag) {
                                                'critical_low',
                                                'critical_high' =>
                                                    'result-critical',

                                                'low',
                                                'high',
                                                'abnormal' =>
                                                    'result-abnormal',

                                                default =>
                                                    '',
                                            };
                                        @endphp


                                        <tr>

                                            <td>
                                                <div class="parameter-name">
                                                    {{ $item->parameter_name }}
                                                </div>

                                                @if ($item->remarks)

                                                    <div style="margin-top:2px; font-size:8px; color:#7b8ba0;">
                                                        {{ $item->remarks }}
                                                    </div>

                                                @endif
                                            </td>


                                            <td>
                                                <span class="parameter-result {{ $resultClass }}">
                                                    {{ $item->result_value ?? '—' }}
                                                </span>
                                            </td>


                                            <td>
                                                {{ $item->unit ?: '—' }}
                                            </td>


                                            <td>
                                                {{ $item->reference_range ?: '—' }}
                                            </td>


                                            <td style="text-align:center;">
                                                <span class="flag-badge {{ $flagClass }}">
                                                    {{ $flagLabel }}
                                                </span>
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>


                        @elseif ($result?->result_text)

                            <div style="padding:12px 14px; white-space:pre-wrap; font-size:11px; line-height:1.5;">
                                {{ $result->result_text }}
                            </div>


                        @else

                            <div style="padding:12px 14px; font-size:11px; color:#a16207;">
                                No laboratory result details are available.
                            </div>

                        @endif

                    </div>


                    {{-- COMMENT / INTERPRETATION --}}
                    @if ($result?->result_text && $resultItems->count())

                        <div class="comment-box">

                            <div class="comment-title">
                                Comments / Interpretation
                            </div>

                            <div class="comment-text">{{ $result->result_text }}</div>

                        </div>

                    @endif


                    {{-- REPORT FOOTER --}}
                    <div class="report-footer">

                        <div>

                            <div class="meta-label">
                                Reported By
                            </div>

                            <div class="meta-value" style="margin-bottom:6px;">
                                {{ $result?->enteredBy?->name ?? '—' }}
                            </div>


                            @if ($result?->verified_at)

                                <div class="meta-label">
                                    Verified By
                                </div>

                                <div class="meta-value">
                                    {{ $result?->verifiedBy?->name ?? '—' }}
                                    ·
                                    {{ $result?->verified_at?->format('d M Y, h:i A') }}
                                </div>

                            @endif


                            <div class="footer-note" style="margin-top:7px;">
                                Generated electronically from TCH Hospital ERP.
                                Laboratory values should be interpreted in the appropriate clinical context.
                            </div>

                        </div>


                        <div class="signature">
                            Authorized Signature
                        </div>

                    </div>

                </div>


                {{-- SCREEN ACTIONS --}}
                <div class="screen-actions screen-only">

                    <a
                        href="{{ route('laboratory.index') }}"
                        class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Back
                    </a>


                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Print Laboratory Report
                    </button>

                </div>

            </section>

        </div>

    </div>

</x-app-layout>
