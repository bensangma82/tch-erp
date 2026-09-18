<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Pharmacy Receipt - {{ $sale->sale_no }}
    </title>


    <style>

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 24px;
            color: #111827;
            background: #f8fafc;
        }

        .receipt {
            max-width: 980px;
            margin: 0 auto;
            background: white;
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            padding: 28px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 22px;
        }

        .hospital-name {
            font-size: 24px;
            font-weight: 700;
        }

        .hospital-subtitle {
            margin-top: 5px;
            font-size: 13px;
            color: #475569;
        }

        .receipt-title {
            margin-top: 14px;
            font-size: 18px;
            font-weight: 700;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
            margin-bottom: 22px;
        }

        .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
        }

        .value {
            font-size: 14px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th {
            background: #f1f5f9;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #475569;
            padding: 9px 6px;
            border-bottom: 1px solid #cbd5e1;
            white-space: nowrap;
        }

        td {
            padding: 9px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .medicine-name {
            font-weight: 700;
        }

        .medicine-subtext {
            margin-top: 3px;
            font-size: 11px;
            color: #64748b;
        }

        .tax-note {
            margin-top: 4px;
            font-size: 10px;
            color: #64748b;
        }

        .summary {
            width: 420px;
            margin-left: auto;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 6px 0;
            font-size: 14px;
        }

        .summary-row.tax {
            font-size: 13px;
            color: #475569;
        }

        .summary-total {
            border-top: 2px solid #111827;
            margin-top: 8px;
            padding-top: 10px;
            font-size: 18px;
            font-weight: 700;
        }

        .status {
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 13px;
            line-height: 1.6;
        }

        .footer {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid #cbd5e1;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }

        .actions {
            max-width: 980px;
            margin: 16px auto 0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .button {
            text-decoration: none;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            background: white;
            cursor: pointer;
        }

        .button-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }

        .gst-box {
            margin-top: 18px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 11px;
            color: #475569;
        }


        @media print {

            body {
                background: white;
                padding: 0;
            }

            .receipt {
                border: none;
                border-radius: 0;
                max-width: none;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .gst-box {
                break-inside: avoid;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

        }

    </style>

</head>


<body>


    <div class="receipt">


        <div class="header">

            <div class="hospital-name">
                Tura Christian Hospital
            </div>

            <div class="hospital-subtitle">
                Tura, West Garo Hills, Meghalaya
            </div>

            <div class="receipt-title">
                Pharmacy Receipt
            </div>

        </div>



        <div class="grid">


            <div>

                <div class="label">
                    Receipt / Sale No.
                </div>

                <div class="value">
                    {{ $sale->sale_no }}
                </div>

            </div>


            <div>

                <div class="label">
                    Date & Time
                </div>

                <div class="value">
                    {{ $sale->sale_at?->format('d M Y, h:i A') }}
                </div>

            </div>



            <div>

                <div class="label">
                    Patient
                </div>

                <div class="value">
                    {{ $sale->patient->full_name }}
                </div>

            </div>


            <div>

                <div class="label">
                    UHID
                </div>

                <div class="value">
                    {{ $sale->patient->uhid }}
                </div>

            </div>



            @if ($sale->patient->mrd_number)

                <div>

                    <div class="label">
                        MRD Number
                    </div>

                    <div class="value">
                        {{ $sale->patient->mrd_number }}
                    </div>

                </div>

            @endif



            @if ($sale->encounter)

                <div>

                    <div class="label">
                        Encounter
                    </div>

                    <div class="value">
                        {{ $sale->encounter->encounter_no }}
                    </div>

                </div>


                <div>

                    <div class="label">
                        Department
                    </div>

                    <div class="value">
                        {{ $sale->encounter->department?->name ?? '—' }}
                    </div>

                </div>


                <div>

                    <div class="label">
                        Doctor
                    </div>

                    <div class="value">
                        {{ $sale->encounter->doctor?->name ?? '—' }}
                    </div>

                </div>

            @endif


        </div>



        <table>

            <thead>

                <tr>

                    <th>
                        Medicine
                    </th>

                    <th>
                        HSN
                    </th>

                    <th>
                        Batch
                    </th>

                    <th class="text-right">
                        Qty
                    </th>

                    <th class="text-right">
                        Rate
                    </th>

                    <th class="text-right">
                        GST
                    </th>

                    <th class="text-right">
                        Taxable
                    </th>

                    <th class="text-right">
                        Amount
                    </th>

                </tr>

            </thead>


            <tbody>

                @foreach ($sale->items as $item)

                    <tr>

                        <td>

                            <div class="medicine-name">
                                {{ $item->medicine_name }}
                            </div>


                            @if ($item->brand_name)

                                <div class="medicine-subtext">
                                    {{ $item->brand_name }}
                                </div>

                            @endif


                            @if ($item->strength)

                                <div class="medicine-subtext">
                                    {{ $item->strength }}
                                </div>

                            @endif

                        </td>


                        <td>
                            {{ $item->medicine?->hsn_code ?: '—' }}
                        </td>


                        <td>
                            {{ $item->batch_number }}
                        </td>


                        <td class="text-right">
                            {{ $item->quantity }}
                        </td>


                        <td class="text-right">
                            ₹{{ number_format((float) $item->unit_price, 2) }}

                            <div class="tax-note">
                                incl. GST
                            </div>
                        </td>


                        <td class="text-right">
                            {{ number_format((float) $item->gst_percent, 2) }}%
                        </td>


                        <td class="text-right">
                            ₹{{ number_format((float) $item->taxable_amount, 2) }}
                        </td>


                        <td class="text-right">
                            ₹{{ number_format((float) $item->amount, 2) }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>



        <div class="summary">


            <div class="summary-row">

                <span>
                    Gross Amount
                </span>

                <span>
                    ₹{{ number_format((float) $sale->subtotal, 2) }}
                </span>

            </div>


            <div class="summary-row">

                <span>
                    Discount
                </span>

                <span>
                    ₹{{ number_format((float) $sale->discount, 2) }}
                </span>

            </div>


            <div class="summary-row tax">

                <span>
                    Taxable Value
                </span>

                <span>
                    ₹{{ number_format((float) $sale->taxable_amount, 2) }}
                </span>

            </div>


            <div class="summary-row tax">

                <span>
                    CGST
                </span>

                <span>
                    ₹{{ number_format((float) $sale->cgst_amount, 2) }}
                </span>

            </div>


            <div class="summary-row tax">

                <span>
                    SGST
                </span>

                <span>
                    ₹{{ number_format((float) $sale->sgst_amount, 2) }}
                </span>

            </div>


            @if ((float) $sale->igst_amount > 0)

                <div class="summary-row tax">

                    <span>
                        IGST
                    </span>

                    <span>
                        ₹{{ number_format((float) $sale->igst_amount, 2) }}
                    </span>

                </div>

            @endif


            <div class="summary-row summary-total">

                <span>
                    Total Payable
                </span>

                <span>
                    ₹{{ number_format((float) $sale->total_amount, 2) }}
                </span>

            </div>


            <div class="summary-row">

                <span>
                    Paid
                </span>

                <span>
                    ₹{{ number_format((float) $sale->paid_amount, 2) }}
                </span>

            </div>


            @if ((float) $sale->balance_amount > 0)

                <div class="summary-row">

                    <span>
                        Balance
                    </span>

                    <span>
                        ₹{{ number_format((float) $sale->balance_amount, 2) }}
                    </span>

                </div>

            @endif


        </div>



        <div class="gst-box">

            <strong>
                Tax note:
            </strong>

            Selling prices are GST-inclusive. Taxable value and GST have been reverse-calculated from the GST-inclusive sale value after discount.

            @if (
                (float) $sale->cgst_amount > 0
                || (float) $sale->sgst_amount > 0
            )

                CGST and SGST are shown separately for this intra-state transaction.

            @endif

        </div>



        <div class="status">

            <strong>
                Payment Mode:
            </strong>

            {{ strtoupper($sale->payment_mode ?? '—') }}


            @if ($sale->transaction_reference)

                <br>

                <strong>
                    Transaction Reference:
                </strong>

                {{ $sale->transaction_reference }}

            @endif


            @if ($sale->remarks)

                <br>

                <strong>
                    Remarks:
                </strong>

                {{ $sale->remarks }}

            @endif

        </div>



        <div class="footer">

            Dispensed by:
            {{ $sale->createdBy?->name ?? 'Pharmacy' }}

            <br>

            This is a computer-generated pharmacy receipt.

        </div>


    </div>



    <div class="actions">

        <a
            href="{{ route('pharmacy.dispensing.index') }}"
            class="button"
        >
            Back to Dispensing
        </a>


        <button
            type="button"
            onclick="window.print()"
            class="button button-primary"
        >
            Print Receipt
        </button>

    </div>


</body>

</html>