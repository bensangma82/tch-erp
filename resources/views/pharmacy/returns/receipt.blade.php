<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Pharmacy Return Receipt - {{ $pharmacyReturn->return_no }}
    </title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #f8fafc;
            font-size: 13px;
        }

        .receipt {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d1d5db;
            padding: 28px;
        }

        .header {
            text-align: center;
            padding-bottom: 18px;
            border-bottom: 2px solid #111827;
        }

        .hospital-name {
            font-size: 22px;
            font-weight: 700;
        }

        .hospital-address {
            margin-top: 5px;
            font-size: 12px;
            color: #4b5563;
        }

        .receipt-title {
            margin-top: 14px;
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .return-number {
            margin-top: 5px;
            font-size: 13px;
            font-weight: 600;
        }

        .section {
            margin-top: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 28px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .label {
            color: #6b7280;
        }

        .value {
            font-weight: 600;
            text-align: right;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        th {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .summary {
            width: 360px;
            margin-left: auto;
            margin-top: 18px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }

        .summary-total {
            border-top: 2px solid #111827;
            margin-top: 6px;
            padding-top: 10px;
            font-size: 16px;
            font-weight: 700;
        }

        .note {
            margin-top: 20px;
            padding: 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .footer {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px solid #d1d5db;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            color: #4b5563;
            font-size: 11px;
        }

        .actions {
            max-width: 900px;
            margin: 16px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: 0;
        }

        .button-primary {
            background: #111827;
            color: #ffffff;
        }

        .button-secondary {
            background: #ffffff;
            color: #111827;
            border: 1px solid #d1d5db;
        }

        @media print {

            body {
                padding: 0;
                background: white;
            }

            .receipt {
                max-width: none;
                border: none;
                padding: 0;
            }

            .actions {
                display: none;
            }
        }

    </style>

</head>


<body>

    @php
        $isInpatient = ! empty($pharmacyReturn->sale?->admission_id);
    @endphp



    <div class="receipt">


        <div class="header">

            <div class="hospital-name">
                Tura Christian Hospital
            </div>

            <div class="hospital-address">
                Tura, West Garo Hills, Meghalaya
            </div>

            <div class="receipt-title">
                {{ $isInpatient ? 'Pharmacy Return / IP Billing Reversal' : 'Pharmacy Return / Refund Receipt' }}
            </div>

            <div class="return-number">
                {{ $pharmacyReturn->return_no }}
            </div>

        </div>



        <div class="section details-grid">

            <div class="detail-row">
                <span class="label">Return Date</span>
                <span class="value">
                    {{ $pharmacyReturn->returned_at?->format('d M Y, h:i A') }}
                </span>
            </div>

            <div class="detail-row">
                <span class="label">Original Sale</span>
                <span class="value">
                    {{ $pharmacyReturn->sale?->sale_no ?? '—' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="label">Patient</span>
                <span class="value">
                    {{ $pharmacyReturn->patient?->full_name ?? '—' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="label">UHID</span>
                <span class="value">
                    {{ $pharmacyReturn->patient?->uhid ?? '—' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="label">MRD</span>
                <span class="value">
                    {{ $pharmacyReturn->patient?->mrd_number ?? '—' }}
                </span>
            </div>

            @if ($isInpatient)

                <div class="detail-row">
                    <span class="label">Admission No</span>
                    <span class="value">
                        {{ $pharmacyReturn->sale?->admission?->admission_no ?? '—' }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="label">Billing Status</span>
                    <span class="value" style="color:#047857;">
                        Reversed in IP Bill
                    </span>
                </div>

            @else

                <div class="detail-row">
                    <span class="label">Encounter</span>
                    <span class="value">
                        {{ $pharmacyReturn->encounter?->encounter_no ?? '—' }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="label">Department</span>
                    <span class="value">
                        {{ $pharmacyReturn->encounter?->department?->name ?? '—' }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="label">Doctor</span>
                    <span class="value">
                        {{ $pharmacyReturn->encounter?->doctor?->name ?? '—' }}
                    </span>
                </div>

            @endif

        </div>



        <table>

            <thead>

                <tr>

                    <th>Medicine</th>
                    <th>HSN</th>
                    <th>Batch</th>
                    <th class="text-right">Qty</th>

                    @unless ($isInpatient)
                        <th class="text-right">Rate</th>
                        <th class="text-right">GST %</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">Refund</th>
                    @endunless

                </tr>

            </thead>


            <tbody>

                @foreach ($pharmacyReturn->items as $item)

                    <tr>

                        <td>

                            <strong>
                                {{ $item->medicine_name }}
                            </strong>

                            @if ($item->brand_name)
                                <div>
                                    {{ $item->brand_name }}
                                </div>
                            @endif

                            @if ($item->strength)
                                <div>
                                    {{ $item->strength }}
                                </div>
                            @endif

                        </td>

                        <td>
                            {{ $item->hsn_code ?: '—' }}
                        </td>

                        <td>
                            {{ $item->batch_number }}
                        </td>

                        <td class="text-right">
                            {{ $item->quantity }}
                        </td>

                        @unless ($isInpatient)

                            <td class="text-right">
                                ₹{{ number_format((float) $item->unit_price, 2) }}
                            </td>

                            <td class="text-right">
                                {{ number_format((float) $item->gst_percent, 2) }}%
                            </td>

                            <td class="text-right">
                                ₹{{ number_format((float) $item->taxable_amount, 2) }}
                            </td>

                            <td class="text-right">
                                ₹{{ number_format((float) $item->refund_amount, 2) }}
                            </td>

                        @endunless

                    </tr>

                @endforeach

            </tbody>

        </table>



        @if ($isInpatient)

            <div class="note" style="background:#ecfdf5;border-color:#a7f3d0;">
                <strong style="color:#047857;">
                    IP Billing Reversal Completed
                </strong>

                <div style="margin-top:6px;color:#065f46;">
                    The returned medicines have been restored to pharmacy stock and the corresponding
                    amount has been reversed from the patient's running IP bill. No cash, UPI, card,
                    credit or MHIS refund was processed at the pharmacy.
                </div>
            </div>

        @else

            <div class="summary">

                <div class="summary-row">
                    <span>Gross Return</span>
                    <strong>
                        ₹{{ number_format((float) $pharmacyReturn->gross_amount, 2) }}
                    </strong>
                </div>

                <div class="summary-row">
                    <span>Taxable Value</span>
                    <strong>
                        ₹{{ number_format((float) $pharmacyReturn->taxable_amount, 2) }}
                    </strong>
                </div>

                <div class="summary-row">
                    <span>CGST Reversal</span>
                    <strong>
                        ₹{{ number_format((float) $pharmacyReturn->cgst_amount, 2) }}
                    </strong>
                </div>

                <div class="summary-row">
                    <span>SGST Reversal</span>
                    <strong>
                        ₹{{ number_format((float) $pharmacyReturn->sgst_amount, 2) }}
                    </strong>
                </div>

                @if ((float) $pharmacyReturn->igst_amount > 0)

                    <div class="summary-row">
                        <span>IGST Reversal</span>
                        <strong>
                            ₹{{ number_format((float) $pharmacyReturn->igst_amount, 2) }}
                        </strong>
                    </div>

                @endif


                <div class="summary-row summary-total">

                    <span>Refund Amount</span>

                    <span>
                        ₹{{ number_format((float) $pharmacyReturn->refund_amount, 2) }}
                    </span>

                </div>

            </div>

        @endif


        @if ($isInpatient)

            <div class="section details-grid">

                <div class="detail-row">
                    <span class="label">Transaction Type</span>
                    <span class="value">IP Billing Reversal</span>
                </div>

                <div class="detail-row">
                    <span class="label">Settlement</span>
                    <span class="value">No Pharmacy Refund</span>
                </div>

            </div>

        @else

            <div class="section details-grid">

                <div class="detail-row">
                    <span class="label">Refund Mode</span>
                    <span class="value">
                        {{ strtoupper($pharmacyReturn->refund_mode ?? 'NONE') }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="label">Reference</span>
                    <span class="value">
                        {{ $pharmacyReturn->transaction_reference ?: '—' }}
                    </span>
                </div>

            </div>

        @endif



        <div class="note">

            <strong>
                Reason:
            </strong>

            {{ $pharmacyReturn->reason }}

            @if ($pharmacyReturn->remarks)

                <div style="margin-top: 6px;">

                    <strong>
                        Remarks:
                    </strong>

                    {{ $pharmacyReturn->remarks }}

                </div>

            @endif

        </div>



        <div class="footer">

            <div>
                Processed by:
                <strong>
                    {{ $pharmacyReturn->createdBy?->name ?? '—' }}
                </strong>
            </div>

            <div>
                {{ $isInpatient
                    ? 'Original sale and IP billing charge retained for audit trail'
                    : 'Original sale retained for audit trail'
                }}
            </div>

        </div>


    </div>



    <div class="actions">

        <a
            href="{{ route('pharmacy.dispensing.index') }}"
            class="button button-secondary"
        >
            Back to Pharmacy
        </a>

        <button
            type="button"
            onclick="window.print()"
            class="button button-primary"
        >
            {{ $isInpatient ? 'Print IP Return Receipt' : 'Print Return Receipt' }}
        </button>

    </div>


</body>

</html>