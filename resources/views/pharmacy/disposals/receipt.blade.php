<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Pharmacy Disposal Record - {{ $pharmacyDisposal->disposal_no }}
    </title>


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f8fafc;
            color: #111827;
            font-size: 13px;
        }

        .receipt {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border: 1px solid #d1d5db;
            padding: 28px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 18px;
        }

        .hospital {
            font-size: 22px;
            font-weight: 700;
        }

        .address {
            margin-top: 5px;
            color: #4b5563;
            font-size: 12px;
        }

        .title {
            margin-top: 14px;
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .number {
            margin-top: 5px;
            font-weight: 600;
        }

        .details {
            margin-top: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
        }

        .detail {
            display: flex;
            justify-content: space-between;
            gap: 12px;
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
            margin-top: 22px;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px;
        }

        th {
            background: #f3f4f6;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .03em;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .summary {
            width: 360px;
            margin-left: auto;
            margin-top: 20px;
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
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 12px;
        }

        .footer {
            margin-top: 28px;
            border-top: 1px solid #d1d5db;
            padding-top: 14px;
            display: flex;
            justify-content: space-between;
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
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .button-secondary {
            background: white;
            color: #111827;
            border: 1px solid #d1d5db;
        }

        .button-primary {
            border: 0;
            background: #111827;
            color: white;
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


    <div class="receipt">


        <div class="header">

            <div class="hospital">
                Tura Christian Hospital
            </div>

            <div class="address">
                Tura, West Garo Hills, Meghalaya
            </div>

            <div class="title">
                Pharmacy Disposal / Write-off Record
            </div>

            <div class="number">
                {{ $pharmacyDisposal->disposal_no }}
            </div>

        </div>



        <div class="details">

            <div class="detail">
                <span class="label">Date & Time</span>

                <span class="value">
                    {{ $pharmacyDisposal->disposed_at?->format('d M Y, h:i A') }}
                </span>
            </div>


            <div class="detail">
                <span class="label">Status</span>

                <span class="value">
                    {{ strtoupper($pharmacyDisposal->status) }}
                </span>
            </div>


            <div class="detail">
                <span class="label">Reason</span>

                <span class="value">
                    {{ ucwords(str_replace('_', ' ', $pharmacyDisposal->reason)) }}
                </span>
            </div>


            <div class="detail">
                <span class="label">Processed By</span>

                <span class="value">
                    {{ $pharmacyDisposal->createdBy?->name ?? '—' }}
                </span>
            </div>

        </div>



        <table>

            <thead>

                <tr>

                    <th>Medicine</th>
                    <th>Batch</th>
                    <th>Expiry</th>
                    <th class="right">Qty</th>
                    <th class="right">Purchase Price</th>
                    <th class="right">Write-off Value</th>

                </tr>

            </thead>


            <tbody>

                @foreach ($pharmacyDisposal->items as $item)

                    <tr>

                        <td>

                            <strong>
                                {{ $item->medicine_name }}
                            </strong>

                            @if ($item->brand_name)
                                <div>{{ $item->brand_name }}</div>
                            @endif

                            @if ($item->strength)
                                <div>{{ $item->strength }}</div>
                            @endif

                        </td>

                        <td>
                            {{ $item->batch_number }}
                        </td>

                        <td>
                            {{ $item->expiry_date?->format('d M Y') ?? '—' }}
                        </td>

                        <td class="right">
                            {{ number_format($item->quantity) }}
                        </td>

                        <td class="right">
                            ₹{{ number_format((float) $item->purchase_price, 2) }}
                        </td>

                        <td class="right">
                            ₹{{ number_format((float) $item->stock_value, 2) }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>



        @php
            $totalQuantity =
                $pharmacyDisposal->items->sum('quantity');

            $totalValue =
                $pharmacyDisposal->items->sum('stock_value');
        @endphp


        <div class="summary">

            <div class="summary-row">

                <span>Total Quantity</span>

                <strong>
                    {{ number_format($totalQuantity) }}
                </strong>

            </div>


            <div class="summary-row summary-total">

                <span>Total Write-off Value</span>

                <span>
                    ₹{{ number_format((float) $totalValue, 2) }}
                </span>

            </div>

        </div>



        @if ($pharmacyDisposal->remarks)

            <div class="note">

                <strong>Remarks:</strong>

                {{ $pharmacyDisposal->remarks }}

            </div>

        @endif



        <div class="footer">

            <div>
                Recorded by:
                <strong>
                    {{ $pharmacyDisposal->createdBy?->name ?? '—' }}
                </strong>
            </div>

            <div>
                Permanent pharmacy stock write-off record
            </div>

        </div>


    </div>



    <div class="actions">

        <a
            href="{{ route('pharmacy.stock-batches.index') }}"
            class="button button-secondary"
        >
            Back to Pharmacy Stock
        </a>

        <button
            type="button"
            onclick="window.print()"
            class="button button-primary"
        >
            Print Disposal Record
        </button>

    </div>


</body>

</html>