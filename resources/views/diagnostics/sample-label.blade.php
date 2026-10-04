@php
    $barcodeGenerator =
        new \Picqer\Barcode\BarcodeGeneratorSVG();

    $barcodeSvg =
        $barcodeGenerator->getBarcode(
            $firstSample->sample_no,
            $barcodeGenerator::TYPE_CODE_128,
            2,
            42
        );

    $patientName =
        $patient?->name
        ?? $patient?->full_name
        ?? 'Patient';

    $uhid =
        $patient?->uhid
        ?? '—';

    $specimenType =
        $firstSample->specimen_type
        ?? 'Specimen';

    $collectedAt =
        $firstSample->collected_at;
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
        {{ $firstSample->sample_no }} - Laboratory Label
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #ffffff;
        }

        body {
            padding: 3mm;
        }

        .label {
            width: 74mm;
            min-height: 44mm;
            border: 0.3mm solid #111827;
            padding: 2.5mm;
        }

        .hospital {
            text-align: center;
            font-size: 10pt;
            font-weight: 700;
            line-height: 1.1;
        }

        .department {
            margin-top: 0.5mm;
            text-align: center;
            font-size: 7pt;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .divider {
            border-top: 0.25mm solid #111827;
            margin: 1.5mm 0;
        }

        .patient {
            font-size: 9pt;
            font-weight: 700;
            line-height: 1.2;
        }

        .meta {
            margin-top: 0.7mm;
            font-size: 7.5pt;
            line-height: 1.25;
        }

        .specimen-row {
            margin-top: 1.5mm;
            display: flex;
            justify-content: space-between;
            gap: 3mm;
            font-size: 8pt;
            font-weight: 700;
        }

        .accession {
            font-family: monospace;
            white-space: nowrap;
        }

        .tests {
            margin-top: 1.5mm;
            font-size: 7pt;
            line-height: 1.25;
        }

        .tests strong {
            font-weight: 700;
        }

        .barcode {
            margin-top: 1.5mm;
            text-align: center;
        }

        .barcode svg {
            width: 100%;
            max-width: 66mm;
            height: 10mm;
        }

        .barcode-value {
            margin-top: 0.5mm;
            text-align: center;
            font-family: monospace;
            font-size: 7pt;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .footer {
            margin-top: 1mm;
            display: flex;
            justify-content: space-between;
            gap: 2mm;
            font-size: 6.5pt;
        }

        .print-actions {
            margin-top: 12px;
            text-align: center;
        }

        .print-button {
            border: 0;
            border-radius: 6px;
            background: #111827;
            color: white;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        @page {
            size: 80mm 50mm;
            margin: 0;
        }

        @media print {
            body {
                padding: 3mm;
            }

            .print-actions {
                display: none !important;
            }

            .label {
                border: none;
            }
        }
    </style>
</head>

<body>

<div class="label">

    <div class="hospital">
        TURA CHRISTIAN HOSPITAL
    </div>

    <div class="department">
        LABORATORY
    </div>

    <div class="divider"></div>

    <div class="patient">
        {{ $patientName }}
    </div>

    <div class="meta">
        UHID: {{ $uhid }}
        &nbsp; | &nbsp;
        {{ $orderSource }}
    </div>

    <div class="specimen-row">

        <span>
            {{ strtoupper($specimenType) }}
        </span>

        <span class="accession">
            {{ $firstSample->sample_no }}
        </span>

    </div>

    <div class="tests">
        <strong>Tests:</strong>
        {{ $investigations->join(', ') }}
    </div>

    <div class="barcode">
        {!! $barcodeSvg !!}
    </div>

    <div class="barcode-value">
        {{ $firstSample->sample_no }}
    </div>

    <div class="footer">

        <span>
            @if ($collectedAt)
                {{ $collectedAt->format('d-M-Y h:i A') }}
            @endif
        </span>

        <span>
            {{ $firstSample->collectedBy?->name ?? '' }}
        </span>

    </div>

</div>


<div class="print-actions">

    <button
        type="button"
        class="print-button"
        onclick="window.print()"
    >
        Print Label
    </button>

</div>

</body>
</html>