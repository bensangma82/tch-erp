@php
    $patient = $encounter->patient;

    $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();

    $barcodeSvg = $barcodeGenerator->getBarcode(
        $patient->uhid,
        $barcodeGenerator::TYPE_CODE_128,
        1.6,
        45
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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }

        .card {
            width: 148mm;
            min-height: 200mm;
            margin: auto;
            background: #fff;
            border: 1px solid #222;
            padding: 14mm;
        }

        .hospital {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
        }

        .subtitle {
            margin-top: 3px;
            text-align: center;
            font-size: 12px;
            color: #555;
        }

        .divider {
            margin: 10px 0;
            border-top: 1px solid #999;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 20px;
            font-size: 11px;
        }

        .row {
            display: flex;
            gap: 6px;
        }

        .label {
            min-width: 80px;
            font-weight: 700;
        }

        .value {
            flex: 1;
        }

        .queue-box {
            text-align: center;
            border: 2px solid #111;
            padding: 8px;
            margin-top: 10px;
        }

        .queue-label {
            font-size: 10px;
            text-transform: uppercase;
        }

        .queue-number {
            font-size: 28px;
            font-weight: 700;
        }

        .barcode {
            margin-top: 10px;
            text-align: center;
        }

        .barcode svg {
            max-width: 100%;
            height: 38px;
        }

        .section {
            margin-top: 14px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
        }

        .writing-space {
            min-height: 58px;
            border-bottom: 1px dotted #aaa;
            margin-top: 6px;
        }

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-top: 8px;
            font-size: 10px;
        }

        .vital-box {
            border: 1px solid #aaa;
            min-height: 34px;
            padding: 4px;
        }

        .actions {
            margin-top: 20px;
            text-align: center;
        }

        button {
            padding: 9px 18px;
            font-size: 14px;
            cursor: pointer;
        }

        @media print {

            @page {
                size: A5 portrait;
                margin: 8mm;
            }

            body {
                padding: 0;
                background: white;
            }

            .actions {
                display: none;
            }

            .card {
                width: auto;
                min-height: auto;
                border: none;
                margin: 0;
                padding: 0;
            }
        }

    </style>

</head>

<body>

    <div class="card">

        <div class="hospital">
            TURA CHRISTIAN HOSPITAL
        </div>

        <div class="subtitle">
            Outpatient Department Card
        </div>


        <div class="divider"></div>


        <div class="grid">

            <div class="row">
                <div class="label">Patient</div>
                <div class="value">
                    {{ $patient->full_name }}
                </div>
            </div>

            <div class="row">
                <div class="label">Date</div>
                <div class="value">
                    {{ $encounter->encounter_date?->format('d-m-Y') ?? '—' }}
                </div>
            </div>

            <div class="row">
                <div class="label">UHID</div>
                <div class="value">
                    {{ $patient->uhid }}
                </div>
            </div>

            <div class="row">
                <div class="label">MRD</div>
                <div class="value">
                    {{ $patient->mrd_number ?: '—' }}
                </div>
            </div>

            <div class="row">
                <div class="label">Age / Sex</div>
                <div class="value">
                    {{ $patient->age !== null ? $patient->age . ' yrs' : '—' }}
                    /
                    {{ $patient->sex ?: '—' }}
                </div>
            </div>

            <div class="row">
                <div class="label">Phone</div>
                <div class="value">
                    {{ $patient->phone ?: '—' }}
                </div>
            </div>

            <div class="row">
                <div class="label">Department</div>
                <div class="value">
                    {{ $encounter->department?->name ?? '—' }}
                </div>
            </div>

            <div class="row">
                <div class="label">Doctor</div>
                <div class="value">
                    {{ $encounter->doctor?->full_name ?? 'Unassigned' }}
                </div>
            </div>

            <div class="row">
                <div class="label">Visit</div>
                <div class="value">
                    {{ ucwords(str_replace('_', ' ', $encounter->visit_type)) }}
                </div>
            </div>

            <div class="row">
                <div class="label">Encounter</div>
                <div class="value">
                    {{ $encounter->encounter_no }}
                </div>
            </div>

        </div>


        <div class="queue-box">

            <div class="queue-label">
                Queue Number
            </div>

            <div class="queue-number">
                {{ $encounter->queue_number }}
            </div>

        </div>


        <div class="barcode">

            {!! $barcodeSvg !!}

            <div style="margin-top: 3px; font-size: 9px;">
                {{ $patient->uhid }}
            </div>

        </div>


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


        <div class="section">

            <div class="section-title">
                Chief Complaints / Clinical Notes
            </div>

            <div class="writing-space"></div>
            <div class="writing-space"></div>

        </div>


        <div class="section">

            <div class="section-title">
                Diagnosis
            </div>

            <div class="writing-space"></div>

        </div>


        <div class="section">

            <div class="section-title">
                Investigations
            </div>

            <div class="writing-space"></div>

        </div>


        <div class="section">

            <div class="section-title">
                Prescription
            </div>

            <div class="writing-space"></div>
            <div class="writing-space"></div>

        </div>


        <div class="section">

            <div class="section-title">
                Follow-up
            </div>

            <div class="writing-space"></div>

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

</body>

</html>