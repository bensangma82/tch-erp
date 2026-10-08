@php
    $barcodeGenerator =
        new \Picqer\Barcode\BarcodeGeneratorSVG();

    $barcodeSvg =
        $barcodeGenerator->getBarcode(
            $patient->uhid,
            $barcodeGenerator::TYPE_CODE_128,
            1.4,
            34
        );

    $copies = 10;
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
        Patient Stickers - {{ $patient->uhid }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            color: #111827;
        }

        .screen-toolbar {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 20px;
        }

        .screen-toolbar button {
            border: 0;
            border-radius: 8px;
            padding: 11px 20px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-close {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1 !important;
        }

        .btn-print {
            background: #0f172a;
            color: #ffffff;
        }

        .sheet-preview {
            padding: 10px 20px 30px;
        }

        .sticker-sheet {
            display: grid;
            grid-template-columns: repeat(2, 70mm);
            gap: 4mm;
            justify-content: center;
        }

        .sticker {
            width: 70mm;
            height: 30mm;
            overflow: hidden;
            background: #ffffff;
            border: 0.3mm solid #111827;
            padding: 2.1mm 2.7mm;
        }

        .hospital-name {
            text-align: center;
            font-size: 9pt;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: 0.15px;
        }

        .patient-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 2mm;
            margin-top: 1.2mm;
        }

        .patient-name {
            min-width: 0;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 10pt;
            line-height: 1.05;
            font-weight: 800;
        }

        .age-sex {
            flex-shrink: 0;
            white-space: nowrap;
            font-size: 8pt;
            font-weight: 700;
        }

        .numbers {
            display: flex;
            justify-content: space-between;
            gap: 2mm;
            margin-top: 0.8mm;
            font-size: 7.3pt;
            line-height: 1.05;
        }

        .numbers > div {
            min-width: 0;
        }

        .numbers strong {
            font-weight: 800;
        }

        .barcode {
            margin-top: 1mm;
            text-align: center;
        }

        .barcode svg {
            display: block;
            width: 100%;
            height: 8mm;
        }

        .barcode-text {
            margin-top: 0.2mm;
            font-family:
                "Courier New",
                monospace;
            font-size: 6.8pt;
            line-height: 1;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        @media print {

            html,
            body {
                margin: 0;
                padding: 0;
                background: #ffffff;
            }

            .screen-toolbar {
                display: none !important;
            }

            .sheet-preview {
                padding: 0;
                margin: 0;
            }

            .sticker-sheet {
                grid-template-columns: repeat(2, 70mm);
                gap: 4mm;
                justify-content: start;
            }

            .sticker {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    {{-- Screen controls --}}
    <div class="screen-toolbar">

        <button
            type="button"
            class="btn-close"
            onclick="window.close()"
        >
            Close
        </button>

        <button
            type="button"
            class="btn-print"
            onclick="window.print()"
        >
            Print 10 Patient Stickers
        </button>

    </div>


    {{-- Sticker sheet --}}
    <div class="sheet-preview">

        <div class="sticker-sheet">

            @for($i = 1; $i <= $copies; $i++)

                <div class="sticker">

                    <div class="hospital-name">
                        TURA CHRISTIAN HOSPITAL
                    </div>


                    <div class="patient-row">

                        <div class="patient-name">
                            {{ $patient->full_name }}
                        </div>

                        <div class="age-sex">
                            {{ $patient->age !== null
                                ? $patient->age . 'Y'
                                : '—'
                            }}
                            /
                            {{ $patient->sex ?: '—' }}
                        </div>

                    </div>


                    <div class="numbers">

                        <div>
                            <strong>UHID:</strong>
                            {{ $patient->uhid }}
                        </div>

                        <div>
                            <strong>MRD:</strong>
                            {{ $patient->mrd_number ?: '—' }}
                        </div>

                    </div>


                    <div class="barcode">

                        {!! $barcodeSvg !!}

                        <div class="barcode-text">
                            {{ $patient->uhid }}
                        </div>

                    </div>

                </div>

            @endfor

        </div>

    </div>

</body>
</html>