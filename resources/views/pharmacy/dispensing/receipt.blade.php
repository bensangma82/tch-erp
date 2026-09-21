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

        :root {
            --navy: #111827;
            --muted: #64748b;
            --line: #cbd5e1;
            --soft-line: #e2e8f0;
            --soft-bg: #f8fafc;
            --table-bg: #f1f5f9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 24px;
            color: var(--navy);
            background: #f8fafc;
        }

        .receipt {
            max-width: 980px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            padding: 28px;
        }

        /* =========================================================
         * HOSPITAL LETTERHEAD
         * ========================================================= */

        .header {
            border-bottom: 2px solid var(--navy);
            padding-bottom: 18px;
            margin-bottom: 22px;
        }

        .hospital-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
        }

        .hospital-logo {
            width: 76px;
            height: 76px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .hospital-details {
            text-align: left;
        }

        .hospital-name {
            margin: 0;
            font-size: 26px;
            line-height: 1.1;
            font-weight: 800;
            color: var(--navy);
        }

        .hospital-subtitle {
            margin-top: 5px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        .hospital-contact {
            margin-top: 5px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.45;
            color: var(--muted);
        }

        .hospital-gstin {
            margin-top: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #334155;
        }

        .receipt-title {
            margin-top: 16px;
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: var(--navy);
        }

        /* =========================================================
         * RECEIPT DETAILS
         * ========================================================= */

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
            margin-bottom: 22px;
        }

        .label {
            margin-bottom: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: var(--muted);
        }

        .value {
            font-size: 14px;
            font-weight: 700;
            color: var(--navy);
        }

        /* =========================================================
         * ITEMS TABLE
         * ========================================================= */

        table {
            width: 100%;
            margin-top: 12px;
            border-collapse: collapse;
        }

        th {
            background: var(--table-bg);
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #475569;
            padding: 9px 6px;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        td {
            padding: 9px 6px;
            border-bottom: 1px solid var(--soft-line);
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
            color: var(--muted);
        }

        .tax-note {
            margin-top: 4px;
            font-size: 10px;
            color: var(--muted);
        }

        /* =========================================================
         * SUMMARY / TAX
         * ========================================================= */

        .summary {
            width: 420px;
            max-width: 100%;
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
            border-top: 2px solid var(--navy);
            margin-top: 8px;
            padding-top: 10px;
            font-size: 18px;
            font-weight: 800;
        }

        .gst-box {
            margin-top: 18px;
            padding: 12px;
            border: 1px solid var(--soft-line);
            border-radius: 8px;
            background: var(--soft-bg);
            font-size: 11px;
            line-height: 1.55;
            color: #475569;
        }

        .status {
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            background: var(--soft-bg);
            font-size: 13px;
            line-height: 1.6;
        }

        .footer {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            text-align: center;
            font-size: 11px;
            line-height: 1.5;
            color: var(--muted);
        }

        /* =========================================================
         * SCREEN ACTIONS
         * ========================================================= */

        .actions {
            max-width: 980px;
            margin: 16px auto 0;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .button {
            text-decoration: none;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            background: #ffffff;
            cursor: pointer;
        }

        .button-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        /* =========================================================
         * PRINT
         * ========================================================= */

        @media print {

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body {
                background: #ffffff;
                padding: 0;
            }

            .receipt {
                max-width: none;
                border: none;
                border-radius: 0;
                padding: 0;
            }

            .actions {
                display: none !important;
            }

            .hospital-logo {
                width: 62px;
                height: 62px;
            }

            .hospital-name {
                font-size: 22px;
            }

            .hospital-subtitle {
                font-size: 11px;
            }

            .hospital-contact,
            .hospital-gstin {
                font-size: 9px;
            }

            .receipt-title {
                margin-top: 11px;
                font-size: 15px;
            }

            .gst-box,
            .status,
            .summary,
            .footer {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            table {
                page-break-inside: auto;
            }

            thead {
                display: table-header-group;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }

        @media (max-width: 700px) {

            body {
                padding: 12px;
            }

            .receipt {
                padding: 18px;
            }

            .hospital-brand {
                align-items: flex-start;
                justify-content: flex-start;
            }

            .hospital-logo {
                width: 58px;
                height: 58px;
            }

            .hospital-name {
                font-size: 21px;
            }

            .hospital-contact {
                line-height: 1.5;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .summary {
                width: 100%;
            }
        }

    </style>

</head>


<body>


    <div class="receipt">


        <div class="header">

            <div class="hospital-brand">

                <img
                    src="{{ asset('images/TCH_favicon.png') }}"
                    alt="Tura Christian Hospital Logo"
                    class="hospital-logo"
                >

                <div class="hospital-details">

                    <div class="hospital-name">
                        TURA CHRISTIAN HOSPITAL
                    </div>

                    <div class="hospital-subtitle">
                        Tura, West Garo Hills, Meghalaya
                    </div>

                    <div class="hospital-contact">
                        Email: tchcare@yahoo.com
                        &nbsp;•&nbsp;
                        Website: www.turachristianhospital.org
                    </div>

                    <div class="hospital-gstin">
                        GSTIN: 17AAAAA0000A1Z5
                    </div>

                </div>

            </div>

            <div class="receipt-title">
                PHARMACY RECEIPT
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

            <br>

            tchcare@yahoo.com
            &nbsp;•&nbsp;
            www.turachristianhospital.org
            &nbsp;•&nbsp;
            GSTIN: 17AAAAA0000A1Z5

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