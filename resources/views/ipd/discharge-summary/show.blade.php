<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Discharge Summary - {{ $admission->admission_no }}
    </title>


    <style>

        @page {
            size: A4 portrait;
            margin: 8mm;
        }


        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;
        }


        body {
            background: #edf1f5;
            color: #172033;

            font-family:
                Inter,
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;

            font-size: 11px;
            line-height: 1.35;
        }


        /*
        |--------------------------------------------------------------------------
        | TOOLBAR
        |--------------------------------------------------------------------------
        */

        .toolbar {
            width: 192mm;

            margin: 14px auto 9px auto;

            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }


        .toolbar-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 34px;

            padding: 0 14px;

            border: 1px solid #ccd5e0;
            border-radius: 7px;

            background: #ffffff;

            color: #334155;

            font-size: 12px;
            font-weight: 700;

            text-decoration: none;

            cursor: pointer;
        }


        .toolbar-button:hover {
            background: #f8fafc;
        }


        .toolbar-button-primary {
            border-color: #17233d;

            background: #17233d;

            color: #ffffff;
        }


        /*
        |--------------------------------------------------------------------------
        | SHEET
        |--------------------------------------------------------------------------
        */

        .sheet {
            width: 192mm;
            min-height: 270mm;

            margin: 0 auto 22px auto;

            overflow: hidden;

            background: #ffffff;

            border: 1px solid #d7dee8;
            border-radius: 3px;

            box-shadow:
                0 8px 28px rgba(15, 23, 42, 0.08);
        }


        .sheet-accent {
            height: 5px;

            background: #17233d;
        }


        .sheet-inner {
            padding: 7mm 9mm 7mm 9mm;
        }


        /*
        |--------------------------------------------------------------------------
        | LETTERHEAD
        |--------------------------------------------------------------------------
        */

        .letterhead {
            display: table;

            width: 100%;
            table-layout: fixed;

            padding-bottom: 7px;

            border-bottom: 1px solid #aeb9c8;
        }


        .letterhead-logo {
            display: table-cell;

            width: 68px;

            vertical-align: middle;
        }


        .letterhead-logo img {
            display: block;

            width: 59px;
            height: 59px;

            object-fit: contain;
        }


        .letterhead-main {
            display: table-cell;

            vertical-align: middle;
        }


        .hospital-name {
            color: #101a31;

            font-size: 23px;
            font-weight: 800;

            letter-spacing: 0.35px;
            line-height: 1.05;
        }


        .hospital-subtitle {
            margin-top: 4px;

            color: #58667a;

            font-size: 10px;
            font-weight: 500;
        }


        .letterhead-document {
            display: table-cell;

            width: 160px;

            vertical-align: middle;
            text-align: right;
        }


        .document-type {
            color: #17233d;

            font-size: 13px;
            font-weight: 800;

            line-height: 1.45;

            letter-spacing: 2px;
        }


        .document-number {
            margin-top: 4px;

            color: #64748b;

            font-size: 8px;
            font-weight: 700;
        }


        /*
        |--------------------------------------------------------------------------
        | PATIENT DETAILS
        |--------------------------------------------------------------------------
        */

        .identity-row {
            display: table;

            width: 100%;
            table-layout: fixed;

            margin-top: 8px;
        }


        .identity-details {
            display: table-cell;

            width: 73%;

            vertical-align: top;

            padding-right: 8px;
        }


        .identity-barcode {
            display: table-cell;

            width: 27%;

            vertical-align: top;
        }


        .identity-panel,
        .barcode-panel {
            overflow: hidden;

            border: 1px solid #d5dde7;
            border-radius: 6px;
        }


        .identity-title,
        .barcode-heading {
            padding: 5px 9px;

            background: #f3f6fa;

            border-bottom: 1px solid #dce3eb;

            color: #526074;

            font-size: 7.8px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 1px;
        }


        .identity-grid {
            display: table;

            width: 100%;
            table-layout: fixed;

            padding: 7px 9px;
        }


        .identity-column {
            display: table-cell;

            width: 33.333%;

            padding-right: 8px;

            vertical-align: top;
        }


        .identity-column:last-child {
            padding-right: 0;
        }


        .detail-item {
            margin-bottom: 5px;
        }


        .detail-item:last-child {
            margin-bottom: 0;
        }


        .detail-label {
            color: #768397;

            font-size: 7.2px;
            font-weight: 800;

            line-height: 1.1;

            text-transform: uppercase;
            letter-spacing: 0.55px;
        }


        .detail-value {
            margin-top: 1px;

            color: #172033;

            font-size: 9.7px;
            font-weight: 700;

            line-height: 1.22;

            overflow-wrap: anywhere;
        }


        /*
        |--------------------------------------------------------------------------
        | BARCODE
        |--------------------------------------------------------------------------
        */

        .barcode-panel {
            min-height: 132px;

            text-align: center;
        }


        .barcode-content {
            padding: 8px 8px 7px 8px;
        }


        .barcode-image {
            display: block;

            width: 100%;
            max-width: 175px;

            height: 36px;

            margin: 0 auto;

            object-fit: fill;
        }


        .barcode-value {
            margin-top: 4px;

            color: #172033;

            font-size: 8.2px;
            font-weight: 800;

            letter-spacing: 0.3px;
        }


        .barcode-fallback {
            padding: 10px 0;

            color: #94a3b8;

            font-size: 9px;
        }


        .barcode-consultant {
            margin-top: 6px;
            padding-top: 5px;

            border-top: 1px solid #e2e8f0;
        }


        .mini-label {
            color: #7a8799;

            font-size: 7px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 0.7px;
        }


        .mini-value {
            margin-top: 1px;

            color: #172033;

            font-size: 9.2px;
            font-weight: 800;
        }


        /*
        |--------------------------------------------------------------------------
        | FINAL DIAGNOSIS
        |--------------------------------------------------------------------------
        */

        .diagnosis-panel {
            margin-top: 8px;

            padding: 7px 10px 8px 10px;

            border-left: 4px solid #17233d;
            border-radius: 3px;

            background: #f4f7fa;
        }


        .diagnosis-heading {
            color: #536174;

            font-size: 7.8px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 0.9px;
        }


        .diagnosis-content {
            margin-top: 4px;

            color: #101827;

            font-size: 10.8px;
            font-weight: 700;

            line-height: 1.4;

            white-space: pre-line;
            text-align: left;
        }


        /*
        |--------------------------------------------------------------------------
        | CLINICAL CONTENT
        |--------------------------------------------------------------------------
        */

        .clinical-area {
            margin-top: 7px;
        }


        .clinical-section {
            padding: 6px 0;

            border-bottom: 1px solid #dfe5ec;

            page-break-inside: avoid;
        }


        .clinical-section:last-child {
            border-bottom: none;
        }


        .clinical-heading-row {
            display: flex;
            align-items: center;

            width: 100%;
        }


        .clinical-heading {
            flex: 0 0 auto;

            white-space: nowrap;

            color: #17233d;

            font-size: 8.5px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 0.8px;
        }


        .clinical-heading-line {
            flex: 1;

            margin-left: 8px;

            border-top: 1px solid #cbd5e1;
        }


        .clinical-content {
            margin-top: 5px;
            padding-left: 0;

            color: #263244;

            font-size: 10.3px;
            line-height: 1.4;

            white-space: pre-line;
            overflow-wrap: anywhere;

            text-align: left;
        }


        /*
        |--------------------------------------------------------------------------
        | CONDITION + MEDICATION
        |--------------------------------------------------------------------------
        */

        .clinical-two-column {
            display: table;

            width: 100%;
            table-layout: fixed;
        }


        .clinical-half {
            display: table-cell;

            width: 50%;

            vertical-align: top;
        }


        .clinical-half:first-child {
            padding-right: 12px;
        }


        .clinical-half:last-child {
            padding-left: 12px;

            border-left: 1px solid #e1e6ed;
        }


        .clinical-half .clinical-content {
            margin-top: 5px;
        }


        /*
        |--------------------------------------------------------------------------
        | REVIEW
        |--------------------------------------------------------------------------
        */

        .review-strip {
            display: table;

            width: 100%;
            table-layout: fixed;

            margin-top: 7px;

            overflow: hidden;

            border: 1px solid #d5dde7;
            border-radius: 6px;

            background: #fafbfc;
        }


        .review-cell {
            display: table-cell;

            width: 50%;

            padding: 6px 9px;

            vertical-align: middle;
        }


        .review-cell:first-child {
            border-right: 1px solid #dce3eb;
        }


        .review-label {
            color: #748195;

            font-size: 7.2px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 0.7px;
        }


        .review-value {
            margin-top: 2px;

            color: #172033;

            font-size: 9.8px;
            font-weight: 800;
        }


        .review-subtext {
            margin-top: 1px;

            color: #64748b;

            font-size: 7.8px;
        }


        /*
        |--------------------------------------------------------------------------
        | SIGNATURES
        |--------------------------------------------------------------------------
        */

        .signature-area {
            display: table;

            width: 100%;
            table-layout: fixed;

            margin-top: 24px;
        }


        .signature-box {
            display: table-cell;

            width: 50%;

            vertical-align: bottom;
        }


        .signature-box-right {
            text-align: right;
        }


        .signature-line {
            width: 175px;

            margin-bottom: 4px;

            border-top: 1px solid #596579;
        }


        .signature-box-right .signature-line {
            margin-left: auto;
        }


        .signature-name {
            color: #172033;

            font-size: 10px;
            font-weight: 800;
        }


        .signature-qualification {
            margin-top: 1px;

            color: #64748b;

            font-size: 7.8px;
        }


        .signature-designation {
            margin-top: 2px;

            color: #758297;

            font-size: 7.2px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: 0.65px;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .document-footer {
            margin-top: 12px;
            padding-top: 5px;

            border-top: 1px solid #e2e8f0;

            color: #8a96a8;

            font-size: 6.8px;

            text-align: center;
        }


        /*
        |--------------------------------------------------------------------------
        | PRINT
        |--------------------------------------------------------------------------
        */

        @media print {

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;

                background: #ffffff !important;
            }


            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }


            .no-print {
                display: none !important;
            }


            .sheet {
                width: 100% !important;
                min-height: auto !important;

                margin: 0 !important;

                border: none !important;
                border-radius: 0 !important;

                box-shadow: none !important;
            }


            .sheet-inner {
                padding:
                    4mm
                    5mm
                    4mm
                    5mm !important;
            }


            .clinical-section,
            .identity-row,
            .diagnosis-panel,
            .review-strip,
            .signature-area {
                break-inside: avoid;
                page-break-inside: avoid;
            }

        }

    </style>

</head>


<body>


@php

    $patient =
        $admission->patient;


    $consultant =
        $admission->consultant;


    $preparedBy =
        $summary->preparedBy;


    $consultantName =
        $consultant
            ? trim(
                ($consultant->title
                    ? $consultant->title . ' '
                    : '')
                .
                ($consultant->first_name ?? '')
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


    $barcodeValue =
        $patient?->uhid
        ??
        $admission->admission_no;


    $chiefComplaint =
        $admission->emergencyVisit?->chief_complaint
        ??
        $admission->admission_reason
        ??
        '—';


    $summaryBrief =
        $summary->hospital_course
        ?: '—';


    $medicationRecommendation =
        $summary->discharge_medications
        ?: '—';


    $reviewDate =
        $summary->review_date
            ? $summary->review_date->format('d M Y')
            : '—';


    $dischargeDate =
        $admission->discharged_at
        ??
        $admission->closed_at;


    $preparedByName =
        $preparedBy?->name
        ??
        auth()->user()?->name
        ??
        '—';


    $barcodeImage = null;


    if (
        $barcodeValue
        &&
        class_exists(
            \Milon\Barcode\Facades\DNS1DFacade::class
        )
    ) {

        try {

            $barcodeImage =
                \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG(
                    $barcodeValue,
                    'C128'
                );

        } catch (\Throwable $e) {

            $barcodeImage = null;

        }

    }

@endphp



{{-- ================================================================ --}}
{{-- SCREEN TOOLBAR --}}
{{-- ================================================================ --}}

<div class="toolbar no-print">

    <a
        href="{{ route('ipd.show', $admission) }}"
        class="toolbar-button"
    >
        Back to IPD
    </a>


    <a
        href="{{ route('ipd.discharge-summary.edit', $admission) }}"
        class="toolbar-button"
    >
        Edit Summary
    </a>


    <button
        type="button"
        class="toolbar-button toolbar-button-primary"
        onclick="window.print()"
    >
        Print Summary
    </button>

</div>



{{-- ================================================================ --}}
{{-- DOCUMENT --}}
{{-- ================================================================ --}}

<div class="sheet">


    <div class="sheet-accent"></div>


    <div class="sheet-inner">


        {{-- ======================================================== --}}
        {{-- LETTERHEAD --}}
        {{-- ======================================================== --}}

        <div class="letterhead">


            <div class="letterhead-logo">

                <img
                    src="{{ asset('images/TCH_favicon.png') }}"
                    alt="Tura Christian Hospital"
                >

            </div>


            <div class="letterhead-main">

                <div class="hospital-name">
                    TURA CHRISTIAN HOSPITAL
                </div>

                <div class="hospital-subtitle">
                    Tura, West Garo Hills, Meghalaya
                </div>

            </div>


            <div class="letterhead-document">

                <div class="document-type">
                    DISCHARGE<br>
                    SUMMARY
                </div>

                <div class="document-number">
                    {{ $admission->admission_no }}
                </div>

            </div>


        </div>



        {{-- ======================================================== --}}
        {{-- PATIENT INFO + BARCODE --}}
        {{-- ======================================================== --}}

        <div class="identity-row">


            <div class="identity-details">

                <div class="identity-panel">


                    <div class="identity-title">
                        Patient Information
                    </div>


                    <div class="identity-grid">


                        {{-- COLUMN 1 --}}

                        <div class="identity-column">


                            <div class="detail-item">

                                <div class="detail-label">
                                    Patient Name
                                </div>

                                <div class="detail-value">
                                    {{ $patient?->full_name ?? '—' }}
                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Age / Sex
                                </div>

                                <div class="detail-value">

                                    @if ($patient?->age !== null)

                                        {{ $patient->age }} yrs

                                    @elseif ($patient?->date_of_birth)

                                        {{ $patient->date_of_birth->age }} yrs

                                    @else

                                        —

                                    @endif

                                    /

                                    {{ $patient?->sex
                                        ? ucfirst($patient->sex)
                                        : '—'
                                    }}

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Mobile
                                </div>

                                <div class="detail-value">
                                    {{ $patient?->phone ?? '—' }}
                                </div>

                            </div>


                        </div>



                        {{-- COLUMN 2 --}}

                        <div class="identity-column">


                            <div class="detail-item">

                                <div class="detail-label">
                                    UHID
                                </div>

                                <div class="detail-value">
                                    {{ $patient?->uhid ?? '—' }}
                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    MRD
                                </div>

                                <div class="detail-value">
                                    {{ $patient?->mrd_number ?? '—' }}
                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Blood Group
                                </div>

                                <div class="detail-value">
                                    {{ $patient?->blood_group ?? '—' }}
                                </div>

                            </div>


                        </div>



                        {{-- COLUMN 3 --}}

                        <div class="identity-column">


                            <div class="detail-item">

                                <div class="detail-label">
                                    Department
                                </div>

                                <div class="detail-value">
                                    {{ $admission->department?->name ?? '—' }}
                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Date of Admission
                                </div>

                                <div class="detail-value">

                                    {{
                                        $admission->admitted_at
                                            ? $admission->admitted_at->format(
                                                'd M Y, h:i A'
                                            )
                                            : '—'
                                    }}

                                </div>

                            </div>


                            <div class="detail-item">

                                <div class="detail-label">
                                    Date of Discharge
                                </div>

                                <div class="detail-value">

                                    {{
                                        $dischargeDate
                                            ? $dischargeDate->format(
                                                'd M Y, h:i A'
                                            )
                                            : '—'
                                    }}

                                </div>

                            </div>


                        </div>


                    </div>


                </div>

            </div>



            {{-- BARCODE --}}

            <div class="identity-barcode">

                <div class="barcode-panel">


                    <div class="barcode-heading">
                        Patient Identification
                    </div>


                    <div class="barcode-content">


                        @if ($barcodeImage)

                            <img
                                src="data:image/png;base64,{{ $barcodeImage }}"
                                alt="Patient Barcode"
                                class="barcode-image"
                            >

                        @else

                            <div class="barcode-fallback">
                                Barcode unavailable
                            </div>

                        @endif


                        <div class="barcode-value">
                            {{ $barcodeValue }}
                        </div>


                        <div class="barcode-consultant">

                            <div class="mini-label">
                                Consultant
                            </div>

                            <div class="mini-value">
                                {{ $consultantName }}
                            </div>

                        </div>


                    </div>


                </div>

            </div>


        </div>



        {{-- ======================================================== --}}
        {{-- FINAL DIAGNOSIS --}}
        {{-- ======================================================== --}}

        <div class="diagnosis-panel">

            <div class="diagnosis-heading">
                Final Diagnosis
            </div>

            <div class="diagnosis-content">{{ $summary->final_diagnosis ?: '—' }}</div>

        </div>



        {{-- ======================================================== --}}
        {{-- CLINICAL CONTENT --}}
        {{-- ======================================================== --}}

        <div class="clinical-area">


            {{-- CHIEF COMPLAINT --}}

            <div class="clinical-section">

                <div class="clinical-heading-row">

                    <div class="clinical-heading">
                        Chief Complaint
                    </div>

                    <div class="clinical-heading-line"></div>

                </div>


                <div class="clinical-content">{{ $chiefComplaint }}</div>

            </div>



            {{-- SUMMARY IN BRIEF --}}

            <div class="clinical-section">

                <div class="clinical-heading-row">

                    <div class="clinical-heading">
                        Summary in Brief
                    </div>

                    <div class="clinical-heading-line"></div>

                </div>


                <div class="clinical-content">{{ $summaryBrief }}</div>

            </div>



            {{-- CONDITION + MEDICATION --}}

            <div class="clinical-section">

                <div class="clinical-two-column">


                    <div class="clinical-half">

                        <div class="clinical-heading-row">

                            <div class="clinical-heading">
                                Condition at Discharge
                            </div>

                        </div>


                        <div class="clinical-content">{{ $summary->condition_at_discharge ?: '—' }}</div>

                    </div>



                    <div class="clinical-half">

                        <div class="clinical-heading-row">

                            <div class="clinical-heading">
                                Medication Recommendation
                            </div>

                        </div>


                        <div class="clinical-content">{{ $medicationRecommendation }}</div>

                    </div>


                </div>

            </div>


        </div>



        {{-- ======================================================== --}}
        {{-- REVIEW --}}
        {{-- ======================================================== --}}

        <div class="review-strip">


            <div class="review-cell">

                <div class="review-label">
                    Review Date
                </div>

                <div class="review-value">
                    {{ $reviewDate }}
                </div>

            </div>



            <div class="review-cell">

                <div class="review-label">
                    Consultant
                </div>

                <div class="review-value">
                    {{ $consultantName }}
                </div>


                @if ($consultant?->speciality)

                    <div class="review-subtext">
                        {{ $consultant->speciality }}
                    </div>

                @endif

            </div>


        </div>



        {{-- ======================================================== --}}
        {{-- SIGNATURES --}}
        {{-- ======================================================== --}}

        <div class="signature-area">


            <div class="signature-box">

                <div class="signature-line"></div>

                <div class="signature-name">
                    {{ $preparedByName }}
                </div>

                <div class="signature-designation">
                    Prepared By
                </div>

            </div>



            <div class="signature-box signature-box-right">

                <div class="signature-line"></div>

                <div class="signature-name">
                    {{ $consultantName }}
                </div>


                @if ($consultant?->qualification)

                    <div class="signature-qualification">
                        {{ $consultant->qualification }}
                    </div>

                @endif


                <div class="signature-designation">
                    Consultant Signature
                </div>

            </div>


        </div>



        {{-- ======================================================== --}}
        {{-- FOOTER --}}
        {{-- ======================================================== --}}

        <div class="document-footer">

            Tura Christian Hospital
            &nbsp;•&nbsp;
            Discharge Summary
            &nbsp;•&nbsp;
            UHID {{ $patient?->uhid ?? '—' }}

        </div>


    </div>


</div>


</body>

</html>