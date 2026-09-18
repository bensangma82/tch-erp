@php
    $patient = $payment->patient;
    $invoice = $payment->invoice;
    $encounter = $payment->encounter;

    $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();

    $receiptBarcode = $barcodeGenerator->getBarcode(
        $payment->receipt_no,
        $barcodeGenerator::TYPE_CODE_128,
        1.5,
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
        Receipt - {{ $payment->receipt_no }}
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

        .receipt {
            width: 80mm;
            margin: auto;
            background: white;
            border: 1px solid #222;
            padding: 12px;
        }

        .hospital {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
        }

        .subtitle {
            margin-top: 2px;
            text-align: center;
            font-size: 10px;
            color: #444;
        }

        .divider {
            margin: 8px 0;
            border-top: 1px solid #aaa;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 4px;
            font-size: 10px;
        }

        .label {
            color: #555;
        }

        .value {
            text-align: right;
            font-weight: 600;
        }

        .section-title {
            margin: 8px 0 5px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .amount-row.total {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid #aaa;
            font-size: 12px;
            font-weight: 700;
        }

        .barcode {
            margin-top: 10px;
            text-align: center;
        }

        .barcode svg {
            display: block;
            max-width: 100%;
            height: 38px;
            margin: auto;
        }

        .barcode-text {
            margin-top: 3px;
            font-size: 8px;
            letter-spacing: 1px;
        }

        .footer {
            margin-top: 10px;
            border-top: 1px dashed #aaa;
            padding-top: 6px;
            text-align: center;
            font-size: 8px;
            color: #555;
        }

        .actions {
            margin-top: 20px;
            text-align: center;
        }

        button {
            padding: 9px 18px;
            cursor: pointer;
            font-size: 14px;
        }

        @media print {

            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                padding: 0;
                background: white;
            }

            .actions {
                display: none;
            }

            .receipt {
                width: 80mm;
                margin: 0;
                border: none;
            }
        }

    </style>

</head>


<body>

    <div class="receipt">

        <div class="hospital">
            TURA CHRISTIAN HOSPITAL
        </div>

        <div class="subtitle">
            OPD Payment Receipt
        </div>


        <div class="divider"></div>


        <div class="row">

            <div class="label">
                Receipt No.
            </div>

            <div class="value">
                {{ $payment->receipt_no }}
            </div>

        </div>


        <div class="row">

            <div class="label">
                Date / Time
            </div>

            <div class="value">
                {{ $payment->payment_date?->format('d-m-Y h:i A') ?? '—' }}
            </div>

        </div>


        <div class="row">

            <div class="label">
                Invoice
            </div>

            <div class="value">
                {{ $invoice?->invoice_no ?? '—' }}
            </div>

        </div>


        <div class="divider"></div>


        <div class="section-title">
            Patient
        </div>


        <div class="row">

            <div class="label">
                Name
            </div>

            <div class="value">
                {{ $patient?->full_name ?? '—' }}
            </div>

        </div>


        <div class="row">

            <div class="label">
                UHID
            </div>

            <div class="value">
                {{ $patient?->uhid ?? '—' }}
            </div>

        </div>


        <div class="row">

            <div class="label">
                MRD
            </div>

            <div class="value">
                {{ $patient?->mrd_number ?? '—' }}
            </div>

        </div>


        <div class="row">

            <div class="label">
                Department
            </div>

            <div class="value">
                {{ $encounter?->department?->name ?? '—' }}
            </div>

        </div>


        <div class="row">

            <div class="label">
                Doctor
            </div>

            <div class="value">
                {{ $encounter?->doctor?->full_name ?? '—' }}
            </div>

        </div>


        <div class="row">

            <div class="label">
                Queue No.
            </div>

            <div class="value">
                {{ $encounter?->queue_number ?? '—' }}
            </div>

        </div>


        <div class="divider"></div>


        <div class="section-title">
            Payment Details
        </div>


        <div class="amount-row">

            <div>
                Invoice Amount
            </div>

            <div>
                ₹{{ number_format((float) ($invoice?->total_amount ?? 0), 2) }}
            </div>

        </div>


        <div class="amount-row">

            <div>
                Amount Paid
            </div>

            <div>
                ₹{{ number_format((float) $payment->amount, 2) }}
            </div>

        </div>


        <div class="amount-row">

            <div>
                Payment Mode
            </div>

            <div>
                {{ strtoupper($payment->payment_mode) }}
            </div>

        </div>


        @if ($payment->transaction_reference)

            <div class="amount-row">

                <div>
                    Reference No.
                </div>

                <div>
                    {{ $payment->transaction_reference }}
                </div>

            </div>

        @endif


        @if (
            $invoice &&
            (float) $invoice->balance_amount > 0
        )

            <div class="amount-row">

                <div>
                    Balance Due
                </div>

                <div>
                    ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                </div>

            </div>

        @endif


        <div class="amount-row total">

            <div>
                RECEIVED
            </div>

            <div>
                ₹{{ number_format((float) $payment->amount, 2) }}
            </div>

        </div>


        @if ($payment->remarks)

            <div style="margin-top: 7px; font-size: 9px; color: #555;">
                {{ $payment->remarks }}
            </div>

        @endif


        <div class="divider"></div>


        <div class="row">

            <div class="label">
                Received By
            </div>

            <div class="value">
                {{ $payment->receivedBy?->name ?? 'Reception' }}
            </div>

        </div>


        <div class="barcode">

            {!! $receiptBarcode !!}

            <div class="barcode-text">
                {{ $payment->receipt_no }}
            </div>

        </div>


        <div class="footer">
            Thank you. Please retain this receipt for hospital records.
        </div>

    </div>


    <div class="actions">

        <button
            type="button"
            onclick="window.print()"
        >
            Print Receipt
        </button>

    </div>

</body>

</html>