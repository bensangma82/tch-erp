@php
    $patient = $encounter->patient;

    $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();

    $barcodeSvg = $barcodeGenerator->getBarcode(
        $patient->uhid,
        $barcodeGenerator::TYPE_CODE_128,
        1.45,
        34
    );
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
        OPD Card - {{ $encounter->encounter_no }}
    </title>


    <style>

        :root {
            --ink: #111827;
            --muted: #64748b;
            --line: #cbd5e1;
            --soft-line: #e5e7eb;
            --soft-bg: #f8fafc;
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
            padding: 10mm 11mm 9mm;
        }

        /* =========================================================
         * HOSPITAL HEADER
         * ========================================================= */

        .hospital-header {
            display: grid;
            grid-template-columns: 64px 1fr 64px;
            align-items: center;
            gap: 12px;
            padding-bottom: 9px;
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
            font-size: 25px;
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: 0.01em;
        }

        .hospital-address {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        .hospital-contact {
            margin-top: 2px;
            font-size: 10.5px;
            color: var(--muted);
        }

        .document-title {
            margin-top: 6px;
            font-size: 15px;
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
            margin-top: 9px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 22px;
            font-size: 12.5px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 76px 1fr;
            gap: 7px;
            min-width: 0;
        }

        .detail-label {
            font-weight: 700;
            color: #1f2937;
        }

        .detail-value {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        /* =========================================================
         * QUEUE + BARCODE
         * ========================================================= */

        .identity-strip {
            margin-top: 9px;
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 12px;
            align-items: stretch;
        }

        .queue-box {
            border: 1.8px solid var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 6px 10px;
            min-height: 52px;
        }

        .queue-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            line-height: 1.2;
        }

        .queue-number {
            font-size: 32px;
            line-height: 1;
            font-weight: 800;
        }

        .barcode-box {
            border: 1px solid var(--line);
            display: grid;
            grid-template-columns: 1fr 126px;
            align-items: center;
            gap: 10px;
            padding: 5px 10px;
            min-height: 52px;
        }

        .barcode {
            text-align: center;
            min-width: 0;
        }

        .barcode svg {
            display: block;
            width: 100%;
            max-width: 330px;
            height: 31px;
            margin: 0 auto;
        }

        .barcode-value {
            margin-top: 2px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        .encounter-mini {
            border-left: 1px solid var(--soft-line);
            padding-left: 10px;
            font-size: 10.5px;
            line-height: 1.35;
        }

        .encounter-mini strong {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: var(--ink);
        }

        /* =========================================================
         * VITALS
         * ========================================================= */

        .section {
            margin-top: 9px;
        }

        .section-title {
            font-size: 12.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 3px;
        }

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin-top: 6px;
        }

        .vital-box {
            min-height: 34px;
            border: 1px solid #aeb7c3;
            padding: 5px 6px;
            font-size: 11px;
            font-weight: 700;
            color: #374151;
        }

        /* =========================================================
         * CLINICAL AREA
         * ========================================================= */

        .clinical-grid {
            margin-top: 9px;
            display: grid;
            grid-template-columns: 1.7fr 0.8fr;
            gap: 12px;
            align-items: stretch;
        }

        .clinical-left,
        .clinical-right {
            min-width: 0;
        }

        .clinical-right {
            border-left: 1px solid var(--line);
            padding-left: 12px;
        }

        .clinical-block + .clinical-block {
            margin-top: 8px;
        }

        .block-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 3px;
        }

        .writing-area {
            margin-top: 4px;
            border-bottom: 1px dotted #9ca3af;
            min-height: 42px;
        }

        .writing-area.large {
            min-height: 92px;
            background:
                repeating-linear-gradient(
                    to bottom,
                    transparent 0,
                    transparent 29px,
                    #d1d5db 30px
                );
            border-bottom: none;
        }

        .writing-area.medium {
            min-height: 66px;
            background:
                repeating-linear-gradient(
                    to bottom,
                    transparent 0,
                    transparent 29px,
                    #d1d5db 30px
                );
            border-bottom: none;
        }

        .writing-area.small {
            min-height: 36px;
        }

        .investigation-area {
            margin-top: 5px;
            min-height: 265px;
            background:
                repeating-linear-gradient(
                    to bottom,
                    transparent 0,
                    transparent 27px,
                    #d1d5db 28px
                );
        }

        .investigation-note {
            margin-top: 4px;
            font-size: 10px;
            color: var(--muted);
            line-height: 1.3;
        }


        .clinical-notes-grid {
            margin-top: 9px;
            display: grid;
            grid-template-columns: minmax(0, 4.2fr) minmax(145px, 1fr);
            gap: 12px;
            align-items: stretch;
        }

        .clinical-notes-main,
        .investigations-column {
            min-width: 0;
        }

        .investigations-column {
            border-left: 1px solid var(--line);
            padding-left: 10px;
        }

        .clinical-notes-writing,
        .investigations-writing {
            margin-top: 5px;
            min-height: 350px;
            background:
                repeating-linear-gradient(
                    to bottom,
                    transparent 0,
                    transparent 29px,
                    #d1d5db 30px
                );
        }

        /* =========================================================
         * FOOTER
         * ========================================================= */

        .footer-row {
            margin-top: 9px;
            padding-top: 6px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 16px;
            font-size: 10px;
            color: var(--muted);
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
         * PRINT — STRICT ONE PAGE A4
         * ========================================================= */

        @media print {

            @page {
                size: A4 portrait;
                margin: 7mm;
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
                width: 196mm;
                height: 283mm;
                min-height: 283mm;
                max-height: 283mm;
                margin: 0;
                padding: 7mm 8mm 6mm;
                border: none;
                border-radius: 0;
                overflow: hidden;
                page-break-after: avoid;
                break-after: avoid-page;
            }

            .hospital-header,
            .patient-section,
            .identity-strip,
            .vitals-grid,
            .clinical-grid,
            .footer-row {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .hospital-logo {
                width: 50px;
                height: 50px;
            }

            .hospital-name {
                font-size: 22px;
            }

            .hospital-address {
                font-size: 11px;
            }

            .hospital-contact {
                font-size: 9.5px;
            }

            .document-title {
                margin-top: 4px;
                font-size: 13.5px;
            }

            .patient-section {
                margin-top: 7px;
                gap: 4px 18px;
                font-size: 11.5px;
            }

            .identity-strip {
                margin-top: 7px;
                min-height: 48px;
            }

            .queue-box,
            .barcode-box {
                min-height: 46px;
            }

            .queue-number {
                font-size: 28px;
            }

            .section {
                margin-top: 7px;
            }

            .vital-box {
                min-height: 30px;
            }

            .clinical-grid {
                margin-top: 7px;
            }

            .writing-area.large {
                min-height: 78px;
            }

            .writing-area.medium {
                min-height: 56px;
            }

            .writing-area.small {
                min-height: 30px;
            }

            .investigation-area {
                min-height: 225px;
            }


            .clinical-notes-grid {
                grid-template-columns: minmax(0, 4.5fr) minmax(128px, 1fr);
                gap: 10px;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .clinical-notes-writing,
            .investigations-writing {
                min-height: 330px;
            }

            .footer-row {
                margin-top: 7px;
            }
        }

    </style>

</head>


<body>

    <div class="page-wrap">

        <div class="card">


            {{-- ===================================================== --}}
            {{-- HOSPITAL HEADER --}}
            {{-- ===================================================== --}}

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
                        Outpatient Department Card
                    </div>

                </div>

                <div class="header-spacer"></div>

            </div>


            {{-- ===================================================== --}}
            {{-- PATIENT / VISIT DETAILS --}}
            {{-- ===================================================== --}}

            <div class="patient-section">

                <div class="detail-row">
                    <div class="detail-label">Patient</div>
                    <div class="detail-value">
                        {{ $patient->full_name }}
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Date</div>
                    <div class="detail-value">
                        {{ $encounter->encounter_date?->format('d-m-Y') ?? '—' }}
                    </div>
                </div>


                <div class="detail-row">
                    <div class="detail-label">UHID</div>
                    <div class="detail-value">
                        {{ $patient->uhid }}
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">MRD</div>
                    <div class="detail-value">
                        {{ $patient->mrd_number ?: '—' }}
                    </div>
                </div>


                <div class="detail-row">
                    <div class="detail-label">Age / Sex</div>
                    <div class="detail-value">
                        {{ $patient->age !== null ? $patient->age . ' yrs' : '—' }}
                        /
                        {{ $patient->sex ?: '—' }}
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value">
                        {{ $patient->phone ?: '—' }}
                    </div>
                </div>


                <div class="detail-row">
                    <div class="detail-label">Department</div>
                    <div class="detail-value">
                        {{ $encounter->department?->name ?? '—' }}
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Doctor</div>
                    <div class="detail-value">
                        {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                    </div>
                </div>


                <div class="detail-row">
                    <div class="detail-label">Visit</div>
                    <div class="detail-value">
                        {{ ucwords(str_replace('_', ' ', $encounter->visit_type)) }}
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Encounter</div>
                    <div class="detail-value">
                        {{ $encounter->encounter_no }}
                    </div>
                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- QUEUE + BARCODE ON SAME LINE --}}
            {{-- ===================================================== --}}

            <div class="identity-strip">

                <div class="queue-box">

                    <div class="queue-label">
                        Queue<br>Number
                    </div>

                    <div class="queue-number">
                        {{ $encounter->queue_number }}
                    </div>

                </div>


                <div class="barcode-box">

                    <div class="barcode">

                        {!! $barcodeSvg !!}

                        <div class="barcode-value">
                            {{ $patient->uhid }}
                        </div>

                    </div>

                    <div class="encounter-mini">
                        Patient ID
                        <strong>{{ $patient->uhid }}</strong>

                        Encounter
                        <strong>{{ $encounter->encounter_no }}</strong>
                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- VITALS --}}
            {{-- ===================================================== --}}

            <div class="section">

                <div class="section-title">
                    Vitals
                </div>

                <div class="vitals-grid">

                    <div class="vital-box">
                        BP
                    </div>

                    <div class="vital-box">
                        Pulse
                    </div>

                    <div class="vital-box">
                        SpO₂
                    </div>

                    <div class="vital-box">
                        Temp
                    </div>

                    <div class="vital-box">
                        Weight
                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- CLINICAL NOTES + INVESTIGATIONS --}}
            {{-- ===================================================== --}}

            <div class="clinical-notes-grid">

                <div class="clinical-notes-main">

                    <div class="section-title">
                        Clinical Notes
                    </div>

                    <div class="clinical-notes-writing"></div>

                </div>

                <div class="investigations-column">

                    <div class="section-title">
                        Investigations
                    </div>

                    <div class="investigations-writing"></div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- FOOTER --}}
            {{-- ===================================================== --}}

            <div class="footer-row">

                <div>
                    Tura Christian Hospital • OPD Clinical Card
                </div>

                <div>
                    {{ $encounter->encounter_no }}
                </div>

            </div>


        </div>


        <div class="actions">

            <button
                type="button"
                onclick="window.print()"
            >
                Print OPD Card
            </button>

        </div>

    </div>

</body>

</html>
