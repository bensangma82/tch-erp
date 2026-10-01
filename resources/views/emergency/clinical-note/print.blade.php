@php
    $patient = $emergencyVisit->patient;
    $note = $emergencyVisit->clinicalNote;
    $triage = $emergencyVisit->latestTriage;

    $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();

    $barcodeSvg = $patient?->uhid
        ? $barcodeGenerator->getBarcode(
            $patient->uhid,
            $barcodeGenerator::TYPE_CODE_128,
            1.45,
            34
        )
        : null;

    $dispositionLabel = match ($note->disposition) {
        'discharged' => 'Discharged from Emergency',
        'admitted' => 'Admitted to IPD',
        'observation' => 'Emergency Observation',
        'referred' => 'Referred',
        'lama' => 'LAMA / DAMA',
        'absconded' => 'Absconded',
        'death' => 'Death in Emergency',
        default => '—',
    };
@endphp

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Emergency Visit Sheet - {{ $emergencyVisit->emergency_no }}
    </title>


    <style>

        :root {
            --ink: #111827;
            --muted: #64748b;
            --line: #cbd5e1;
            --soft-line: #e5e7eb;
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
            background: #eef2f7;
            font-family: Arial, Helvetica, sans-serif;
            color: var(--ink);
        }

        .page-wrap {
            padding: 18px;
        }

        .card {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 9mm 10mm 8mm;
        }


        /* =========================================================
         * HEADER
         * ========================================================= */

        .hospital-header {
            display: grid;
            grid-template-columns: 64px 1fr 64px;
            align-items: center;
            gap: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--ink);
        }

        .hospital-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .hospital-center {
            text-align: center;
        }

        .hospital-name {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.08;
        }

        .hospital-address {
            margin-top: 3px;
            font-size: 11.5px;
            font-weight: 600;
            color: #475569;
        }

        .hospital-contact {
            margin-top: 2px;
            font-size: 10px;
            color: var(--muted);
        }

        .document-title {
            margin-top: 5px;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .header-spacer {
            width: 58px;
        }


        /* =========================================================
         * PATIENT DETAILS
         * ========================================================= */

        .patient-section {
            margin-top: 8px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 20px;
            font-size: 11.5px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 88px 1fr;
            gap: 6px;
        }

        .detail-label {
            font-weight: 700;
        }

        .detail-value {
            overflow-wrap: anywhere;
        }


        /* =========================================================
         * EMERGENCY NUMBER / BARCODE
         * ========================================================= */

        .identity-strip {
            margin-top: 8px;
            display: grid;
            grid-template-columns: 190px 1fr;
            gap: 10px;
            align-items: stretch;
        }

        .emergency-box {
            border: 1.8px solid var(--ink);
            padding: 7px 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .emergency-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
        }

        .emergency-number {
            margin-top: 2px;
            font-size: 15px;
            font-weight: 800;
        }

        .barcode-box {
            border: 1px solid var(--line);
            padding: 5px 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
        }

        .barcode {
            text-align: center;
            width: 100%;
        }

        .barcode svg {
            display: block;
            width: 100%;
            max-width: 340px;
            height: 30px;
            margin: 0 auto;
        }

        .barcode-value {
            margin-top: 1px;
            font-size: 9.5px;
            font-weight: 600;
        }


        /* =========================================================
         * GENERAL SECTION
         * ========================================================= */

        .section {
            margin-top: 7px;
        }

        .section-title {
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 3px;
        }

        .text-block {
            margin-top: 4px;
            min-height: 28px;
            white-space: pre-line;
            font-size: 10.5px;
            line-height: 1.35;
        }


        /* =========================================================
         * VITALS
         * ========================================================= */

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 5px;
            margin-top: 5px;
        }

        .vital-box {
            border: 1px solid #aeb7c3;
            padding: 5px 6px;
            min-height: 38px;
        }

        .vital-label {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
        }

        .vital-value {
            margin-top: 2px;
            font-size: 11px;
            font-weight: 800;
        }


        /* =========================================================
         * PRESENTING COMPLAINTS + WRITING AREA
         * ========================================================= */

        .complaint-content {
            margin-top: 4px;
            font-size: 10.5px;
            font-weight: 600;
            line-height: 1.35;
            white-space: pre-line;
        }

        .writing-lines {
            margin-top: 9px;
            min-height: 420px;
            background:
                repeating-linear-gradient(
                    to bottom,
                    transparent 0,
                    transparent 21px,
                    #d1d5db 22px
                );
        }


        /* =========================================================
         * DIAGNOSIS
         * ========================================================= */

        .diagnosis-box {
            margin-top: 5px;
            min-height: 38px;
            padding: 5px 6px;
            border: 1px solid var(--soft-line);
            font-size: 10.5px;
            line-height: 1.35;
            white-space: pre-line;
        }


        /* =========================================================
         * DISPOSITION
         * ========================================================= */

        .disposition-box {
            margin-top: 5px;
            border: 1.5px solid #9ca3af;
            padding: 6px 7px;
        }

        .disposition-value {
            margin-top: 3px;
            font-size: 11px;
            font-weight: 700;
        }


        /* =========================================================
         * CLINICAL DETAILS
         * ========================================================= */

        .clinical-grid {
            margin-top: 6px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .clinical-box {
            border: 1px solid var(--soft-line);
            padding: 6px;
            min-height: 58px;
        }

        .box-title {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #374151;
            border-bottom: 1px solid var(--soft-line);
            padding-bottom: 3px;
        }

        .box-content {
            margin-top: 4px;
            white-space: pre-line;
            font-size: 10px;
            line-height: 1.3;
        }


        /* =========================================================
         * ADVICE
         * ========================================================= */

        .advice-box {
            margin-top: 5px;
            min-height: 42px;
            border: 1px solid var(--soft-line);
            padding: 6px;
            white-space: pre-line;
            font-size: 10px;
            line-height: 1.3;
        }


        /* =========================================================
         * FOOTER
         * ========================================================= */

        .footer-row {
            margin-top: 7px;
            padding-top: 6px;
            border-top: 1px solid var(--line);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            font-size: 9.5px;
        }

        .signature {
            text-align: right;
        }

        .actions {
            margin: 14px auto 22px;
            text-align: center;
        }

        button {
            padding: 9px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #111827;
            color: #ffffff;
        }


        /* =========================================================
         * PRINT
         * ========================================================= */

        @media print {

            @page {
                size: A4 portrait;
                margin: 6mm;
            }

            html,
            body {
                width: 210mm;
                height: 297mm;
                background: #ffffff;
            }

            .page-wrap {
                padding: 0;
            }

            .actions {
                display: none !important;
            }

            .card {
                width: 198mm;
                height: 285mm;
                min-height: 285mm;
                max-height: 285mm;
                margin: 0;
                padding: 5mm 7mm 4mm;
                border: none;
                border-radius: 0;
                overflow: hidden;
            }

            .hospital-header,
            .patient-section,
            .identity-strip,
            .vitals-grid,
            .writing-lines,
            .diagnosis-box,
            .disposition-box,
            .clinical-grid,
            .footer-row {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .hospital-logo {
                width: 46px;
                height: 46px;
            }

            .hospital-name {
                font-size: 20px;
            }

            .hospital-address {
                font-size: 10.5px;
            }

            .hospital-contact {
                font-size: 9px;
            }

            .document-title {
                font-size: 12px;
            }

            .patient-section {
                margin-top: 6px;
                font-size: 10px;
                gap: 3px 18px;
            }

            .identity-strip {
                margin-top: 6px;
            }

            .section {
                margin-top: 6px;
            }

            .section-title {
                font-size: 10.5px;
            }

            .vital-box {
                min-height: 34px;
            }

            .vital-value {
                font-size: 10.5px;
            }

            .writing-lines {
                min-height: 190px;
            }

            .clinical-box {
                min-height: 52px;
            }

            .box-content,
            .text-block,
            .complaint-content,
            .advice-box {
                font-size: 9.5px;
            }
        }

    </style>

</head>


<body>

<div class="page-wrap">

    <div class="card">


        {{-- ========================================================= --}}
        {{-- HOSPITAL HEADER --}}
        {{-- ========================================================= --}}

        <div class="hospital-header">

            <div>

                <img
                    src="{{ asset('images/TCH_favicon.png') }}"
                    alt="Tura Christian Hospital Logo"
                    class="hospital-logo"
                >

            </div>


            <div class="hospital-center">

                <div class="hospital-name">
                    TURA CHRISTIAN HOSPITAL
                </div>

                <div class="hospital-address">
                    Tura, West Garo Hills, Meghalaya
                </div>

                <div class="hospital-contact">
                    tchcare@yahoo.com
                    &nbsp;•&nbsp;
                    www.turachristianhospital.org
                </div>

                <div class="document-title">
                    Emergency Visit Sheet
                </div>

            </div>


            <div class="header-spacer"></div>

        </div>


        {{-- ========================================================= --}}
        {{-- PATIENT DETAILS --}}
        {{-- ========================================================= --}}

        <div class="patient-section">

            <div class="detail-row">

                <div class="detail-label">
                    Patient
                </div>

                <div class="detail-value">
                    {{ $patient?->full_name ?? '—' }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Arrival
                </div>

                <div class="detail-value">
                    {{ $emergencyVisit->arrival_at?->format('d-m-Y h:i A') ?? '—' }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    UHID
                </div>

                <div class="detail-value">
                    {{ $patient?->uhid ?? '—' }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    MRD
                </div>

                <div class="detail-value">
                    {{ $patient?->mrd_number ?: '—' }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Age / Sex
                </div>

                <div class="detail-value">

                    {{ $patient?->age !== null
                        ? $patient->age . ' yrs'
                        : '—'
                    }}

                    /

                    {{ $patient?->sex ?: '—' }}

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Phone
                </div>

                <div class="detail-value">
                    {{ $patient?->phone ?: '—' }}
                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Arrival Mode
                </div>

                <div class="detail-value">

                    {{
                        $emergencyVisit->arrival_mode
                            ? ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $emergencyVisit->arrival_mode
                                )
                            )
                            : '—'
                    }}

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">
                    Brought By
                </div>

                <div class="detail-value">
                    {{ $emergencyVisit->brought_by ?: '—' }}
                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- EMERGENCY NUMBER + BARCODE --}}
        {{-- ========================================================= --}}

        <div class="identity-strip">

            <div class="emergency-box">

                <div class="emergency-label">
                    Emergency Number
                </div>

                <div class="emergency-number">
                    {{ $emergencyVisit->emergency_no }}
                </div>

            </div>


            <div class="barcode-box">

                @if ($barcodeSvg)

                    <div class="barcode">

                        {!! $barcodeSvg !!}

                        <div class="barcode-value">
                            {{ $patient->uhid }}
                        </div>

                    </div>

                @else

                    <div class="barcode-value">
                        UHID unavailable
                    </div>

                @endif

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- VITAL SIGNS --}}
        {{-- ========================================================= --}}

        <div class="section">

            <div class="section-title">
                Clinical Vital Signs
            </div>


            <div class="vitals-grid">

                <div class="vital-box">

                    <div class="vital-label">
                        BP
                    </div>

                    <div class="vital-value">

                        {{ $note->blood_pressure_systolic ?? '—' }}
                        /
                        {{ $note->blood_pressure_diastolic ?? '—' }}
                        mmHg

                    </div>

                </div>


                <div class="vital-box">

                    <div class="vital-label">
                        Pulse
                    </div>

                    <div class="vital-value">
                        {{ $note->pulse ?? '—' }}/min
                    </div>

                </div>


                <div class="vital-box">

                    <div class="vital-label">
                        RR
                    </div>

                    <div class="vital-value">
                        {{ $note->respiratory_rate ?? '—' }}/min
                    </div>

                </div>


                <div class="vital-box">

                    <div class="vital-label">
                        SpO₂
                    </div>

                    <div class="vital-value">
                        {{ $note->spo2 ?? '—' }}%
                    </div>

                </div>


                <div class="vital-box">

                    <div class="vital-label">
                        Temperature
                    </div>

                    <div class="vital-value">
                        {{ $note->temperature ?? '—' }} °C
                    </div>

                </div>


                <div class="vital-box">

                    <div class="vital-label">
                        GCS
                    </div>

                    <div class="vital-value">
                        {{ $note->gcs ?? '—' }}
                    </div>

                </div>


                <div class="vital-box">

                    <div class="vital-label">
                        Pain Score
                    </div>

                    <div class="vital-value">
                        {{ $note->pain_score ?? '—' }}/10
                    </div>

                </div>


                <div
                    class="vital-box"
                    style="grid-column: span 3;"
                >

                    <div class="vital-label">
                        Oxygen Support
                    </div>

                    <div class="vital-value">
                        {{ $note->oxygen_support ?: '—' }}
                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- PRESENTING COMPLAINTS --}}
        {{-- ========================================================= --}}

        <div class="section">

            <div class="section-title">
                Presenting Complaints
            </div>

            <div class="complaint-content">

                {{
                    $note->presenting_complaints
                    ?: $emergencyVisit->chief_complaint
                    ?: '—'
                }}

            </div>

            {{-- Space for handwritten clinical notes --}}
            <div class="writing-lines"></div>

        </div>


        {{-- ========================================================= --}}
        {{-- PROVISIONAL DIAGNOSIS --}}
        {{-- ========================================================= --}}

        <div class="section">

            <div class="section-title">
                Provisional / Working Diagnosis
            </div>

            <div class="diagnosis-box">
                {{ $note->provisional_diagnosis ?: '—' }}
            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- DISPOSITION --}}
        {{-- ========================================================= --}}

        
        {{-- ========================================================= --}}
        {{-- DISCHARGE / REFERRAL ADVICE --}}
        {{-- ========================================================= --}}

        <div class="section">

            <div class="section-title">
                Discharge / Referral Advice
            </div>

            <div class="advice-box">
                {{ $note->discharge_advice ?: '—' }}
            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- FOOTER --}}
        {{-- ========================================================= --}}

        <div class="footer-row">

            <div>

                <strong>
                    Documented:
                </strong>

                {{ $note->documented_at?->format('d-m-Y h:i A') ?? '—' }}

            </div>


            <div class="signature">

                <strong>
                    Treating Clinician:
                </strong>

                {{ $note->doctor?->name ?? '—' }}

            </div>

        </div>


    </div>


    <div class="actions">

        <button
            type="button"
            onclick="window.print()"
        >
            Print Emergency Visit Sheet
        </button>

    </div>

</div>

</body>

</html>