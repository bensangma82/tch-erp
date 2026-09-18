@php
    $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();

    $barcodeSvg = $barcodeGenerator->getBarcode(
        $patient->uhid,
        $barcodeGenerator::TYPE_CODE_128,
        2,
        55
    );
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Patient Card - {{ $patient->uhid }}
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 24px;
            color: #111;
        }

        .card-wrapper {
            display: flex;
            justify-content: center;
        }

        .card {
            width: 86mm;
            min-height: 54mm;
            background: #fff;
            border: 1.2px solid #111;
            border-radius: 7px;
            padding: 9px 11px 8px;
            overflow: hidden;
        }

        .hospital {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .subtitle {
            text-align: center;
            margin-top: 1px;
            font-size: 9px;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .divider {
            border-top: 1px solid #aaa;
            margin: 6px 0;
        }

        .identity-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            margin-bottom: 6px;
        }

        .identity-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 5px;
        }

        .identity-label {
            font-size: 7px;
            color: #666;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .identity-value {
            margin-top: 1px;
            font-size: 10px;
            font-weight: 700;
        }

        .details {
            margin-top: 4px;
        }

        .row {
            display: grid;
            grid-template-columns: 28% 72%;
            margin-bottom: 3px;
            font-size: 9px;
            line-height: 1.2;
        }

        .label {
            font-weight: 700;
            color: #222;
        }

        .value {
            font-weight: 500;
        }

        .barcode {
            text-align: center;
            margin-top: 5px;
        }

        .barcode svg {
            display: block;
            width: 100%;
            max-width: 72mm;
            height: 33px;
            margin: 0 auto;
        }

        .barcode-text {
            margin-top: 2px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .footer {
            margin-top: 5px;
            padding-top: 4px;
            border-top: 1px dashed #bbb;
            text-align: center;
            font-size: 7px;
            color: #555;
        }

        .actions {
            margin-top: 22px;
            text-align: center;
        }

        .print-button {
            padding: 9px 18px;
            border: 1px solid #777;
            border-radius: 5px;
            background: #fff;
            font-size: 14px;
            cursor: pointer;
        }

        .print-button:hover {
            background: #f1f1f1;
        }

        @media print {

            @page {
                size: 86mm 54mm;
                margin: 0;
            }

            html,
            body {
                width: 86mm;
                height: 54mm;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .actions {
                display: none !important;
            }

            .card-wrapper {
                display: block;
            }

            .card {
                width: 86mm;
                height: 54mm;
                min-height: 54mm;
                border-radius: 0;
                margin: 0;
                padding: 8px 10px;
            }
        }

    </style>

</head>


<body>

    <div class="card-wrapper">

        <div class="card">

            <div class="hospital">
                TURA CHRISTIAN HOSPITAL
            </div>

            <div class="subtitle">
                Patient / Medical Record Card
            </div>


            <div class="divider"></div>


            <div class="identity-box">

                <div class="identity-item">

                    <div class="identity-label">
                        UHID
                    </div>

                    <div class="identity-value">
                        {{ $patient->uhid }}
                    </div>

                </div>


                <div class="identity-item">

                    <div class="identity-label">
                        MRD No.
                    </div>

                    <div class="identity-value">
                        {{ $patient->mrd_number ?: 'Not assigned' }}
                    </div>

                </div>

            </div>


            <div class="details">

                <div class="row">

                    <div class="label">
                        Patient
                    </div>

                    <div class="value">
                        {{ $patient->full_name }}
                    </div>

                </div>


                <div class="row">

                    <div class="label">
                        Age / Sex
                    </div>

                    <div class="value">
                        {{ $patient->age !== null ? $patient->age . ' yrs' : '—' }}
                        /
                        {{ $patient->sex ?: '—' }}
                    </div>

                </div>


                <div class="row">

                    <div class="label">
                        Phone
                    </div>

                    <div class="value">
                        {{ $patient->phone ?: '—' }}
                    </div>

                </div>


                <div class="row">

                    <div class="label">
                        Registered
                    </div>

                    <div class="value">
                        {{ $patient->created_at?->format('d-m-Y') ?? '—' }}
                    </div>

                </div>

            </div>


            <div class="barcode">

                {!! $barcodeSvg !!}

                <div class="barcode-text">
                    {{ $patient->uhid }}
                </div>

            </div>


            <div class="footer">
                Please bring this card on every hospital visit.
            </div>

        </div>

    </div>


    <div class="actions">

        <button
            type="button"
            class="print-button"
            onclick="window.print()"
        >
            Print Patient Card
        </button>

    </div>

</body>

</html>