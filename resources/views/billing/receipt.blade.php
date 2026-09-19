<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Investigation Receipt
    </title>

    <style>

        @page {
            size: 80mm auto;
            margin: 4mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #111827;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            font-size: 12px;
        }

        .receipt {
            width: 72mm;
            margin: 0 auto;
        }

        .hospital {
            text-align: center;
            margin-bottom: 8px;
        }

        .hospital-name {
            font-size: 16px;
            font-weight: 700;
        }

        .receipt-title {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .divider {
            margin: 8px 0;
            border-top: 1px dashed #6b7280;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4px;
        }

        .label {
            color: #4b5563;
        }

        .value {
            text-align: right;
            font-weight: 600;
        }

        .patient-name {
            margin: 6px 0 2px;
            font-size: 13px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th {
            border-bottom: 1px solid #9ca3af;
            padding: 4px 0;
            font-size: 10px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            padding: 5px 0;
            vertical-align: top;
            border-bottom: 1px dotted #d1d5db;
        }

        .qty {
            width: 10%;
            text-align: center;
        }

        .rate,
        .amount {
            width: 22%;
            text-align: right;
            white-space: nowrap;
        }

        .service {
            width: 46%;
            padding-right: 4px;
        }

        .service-code {
            margin-top: 2px;
            color: #6b7280;
            font-size: 9px;
        }

        .total-section {
            margin-top: 8px;
        }

        .grand-total {
            font-size: 15px;
            font-weight: 700;
        }

        .footer {
            margin-top: 12px;
            text-align: center;
            color: #4b5563;
            font-size: 10px;
            line-height: 1.4;
        }

        .print-actions {
            margin-bottom: 12px;
            text-align: center;
        }

        .back-button {
            display: inline-block;
            margin-right: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            color: #334155;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .back-button:hover {
            background: #f1f5f9;
        }

        .print-button {
            cursor: pointer;
            border: none;
            border-radius: 6px;
            background: #0f172a;
            color: white;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
        }

        @media print {

            .print-actions {
                display: none;
            }

            body {
                width: 80mm;
            }

        }

    </style>

</head>


<body>

    @php
        $invoice = $payment->invoice;

        $serviceOrderItemIds = $invoice?->items
            ?->pluck('service_order_item_id')
            ->filter()
            ->values();

        $order = null;

        if (
            $serviceOrderItemIds &&
            $serviceOrderItemIds->isNotEmpty()
        ) {
            $orderItem = \App\Models\ServiceOrderItem::query()
                ->with('serviceOrder')
                ->whereIn(
                    'id',
                    $serviceOrderItemIds
                )
                ->first();

            $order = $orderItem?->serviceOrder;
        }
    @endphp


    <div class="receipt">


        <div class="print-actions">

            <a
                href="{{ route('billing.index') }}"
                class="back-button"
            >
                ← Back to Billing
            </a>

            <button
                type="button"
                class="print-button"
                onclick="window.print()"
            >
                Print Receipt
            </button>

        </div>


        <div class="hospital">

            <div class="hospital-name">
                TURA CHRISTIAN HOSPITAL
            </div>

            <div class="receipt-title">
                Investigation Payment Receipt
            </div>

        </div>


        <div class="divider"></div>


        <div class="row">

            <span class="label">
                Receipt No.
            </span>

            <span class="value">
                {{ $payment->receipt_no }}
            </span>

        </div>


        <div class="row">

            <span class="label">
                Invoice No.
            </span>

            <span class="value">
                {{ $invoice?->invoice_no ?? '—' }}
            </span>

        </div>


        @if ($order)

            <div class="row">

                <span class="label">
                    Order No.
                </span>

                <span class="value">
                    {{ $order->order_no }}
                </span>

            </div>

        @endif


        <div class="row">

            <span class="label">
                Date
            </span>

            <span class="value">
                {{ $payment->payment_date?->format('d M Y, h:i A') }}
            </span>

        </div>


        <div class="divider"></div>


        <div class="patient-name">
            {{ $payment->patient?->full_name ?? '—' }}
        </div>


        <div class="row">

            <span class="label">
                UHID
            </span>

            <span class="value">
                {{ $payment->patient?->uhid ?? '—' }}
            </span>

        </div>


        <div class="row">

            <span class="label">
                MRD
            </span>

            <span class="value">
                {{ $payment->patient?->mrd_number ?: '—' }}
            </span>

        </div>


        <div class="row">

            <span class="label">
                Department
            </span>

            <span class="value">
                {{ $payment->encounter?->department?->name ?? '—' }}
            </span>

        </div>


        <div class="row">

            <span class="label">
                Doctor
            </span>

            <span class="value">
                {{ $payment->encounter?->doctor?->full_name ?? '—' }}
            </span>

        </div>


        <div class="divider"></div>


        <table>

            <thead>

                <tr>

                    <th class="service">
                        Service
                    </th>

                    <th class="qty">
                        Qty
                    </th>

                    <th class="rate">
                        Rate
                    </th>

                    <th class="amount">
                        Amount
                    </th>

                </tr>

            </thead>


            <tbody>

                @foreach ($invoice?->items ?? [] as $item)

                    <tr>

                        <td class="service">

                            <div>
                                {{ $item->description }}
                            </div>

                            @if ($item->code)

                                <div class="service-code">
                                    {{ $item->code }}
                                </div>

                            @endif

                        </td>


                        <td class="qty">
                            {{ $item->quantity }}
                        </td>


                        <td class="rate">
                            ₹{{ number_format((float) $item->unit_price, 2) }}
                        </td>


                        <td class="amount">
                            ₹{{ number_format((float) $item->amount, 2) }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>


        <div class="total-section">


            <div class="row">

                <span class="label">
                    Bill Total
                </span>

                <span class="value">
                    ₹{{ number_format((float) ($invoice?->total_amount ?? 0), 2) }}
                </span>

            </div>


            <div class="row">

                <span class="label">
                    Amount Paid
                </span>

                <span class="value grand-total">
                    ₹{{ number_format((float) $payment->amount, 2) }}
                </span>

            </div>


            @if (
                $invoice &&
                (float) $invoice->balance_amount > 0
            )

                <div class="row">

                    <span class="label">
                        Balance Due
                    </span>

                    <span class="value">
                        ₹{{ number_format((float) $invoice->balance_amount, 2) }}
                    </span>

                </div>

            @endif


            <div class="row">

                <span class="label">
                    Payment Mode
                </span>

                <span class="value">
                    {{ strtoupper($payment->payment_mode) }}
                </span>

            </div>


            @if ($payment->transaction_reference)

                <div class="row">

                    <span class="label">
                        Reference
                    </span>

                    <span class="value">
                        {{ $payment->transaction_reference }}
                    </span>

                </div>

            @endif


            <div class="row">

                <span class="label">
                    Received By
                </span>

                <span class="value">
                    {{ $payment->receivedBy?->name ?? '—' }}
                </span>

            </div>


        </div>


        @if ($payment->remarks)

            <div class="divider"></div>

            <div style="font-size: 10px;">

                <strong>
                    Remarks:
                </strong>

                {{ $payment->remarks }}

            </div>

        @endif


        <div class="divider"></div>


        <div class="footer">

            Thank you.

            <br>

            Tura Christian Hospital

            <br>

            This is a computer-generated receipt.

        </div>


    </div>

</body>

</html>