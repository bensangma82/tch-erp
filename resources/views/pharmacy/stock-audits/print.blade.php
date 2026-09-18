<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        {{ $pharmacyStockAudit->audit_no }} - Stock Audit Report
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            padding: 30px;
            background: #ffffff;
            color: #111827;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            font-size: 12px;
        }


        .page {
            max-width: 1100px;
            margin: 0 auto;
        }


        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            padding-bottom: 18px;
            margin-bottom: 22px;
        }


        .hospital-name {
            font-size: 22px;
            font-weight: 700;
        }


        .hospital-subtitle {
            margin-top: 5px;
            color: #475569;
        }


        .report-title {
            text-align: right;
        }


        .report-title h1 {
            margin: 0;
            font-size: 20px;
        }


        .report-title div {
            margin-top: 5px;
            color: #475569;
        }


        .section {
            margin-top: 22px;
        }


        .section-title {
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }


        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }


        .info-box {
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 5px;
        }


        .location-box {
            border: 1px solid #93c5fd;
            background: #eff6ff;
            padding: 14px;
            border-radius: 6px;
            margin-top: 16px;
        }


        .location-title {
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }


        .location-name {
            margin-top: 5px;
            color: #1e3a8a;
            font-size: 17px;
            font-weight: 700;
        }


        .location-meta {
            margin-top: 4px;
            color: #1e40af;
            font-size: 11px;
        }


        .legacy-warning {
            border: 1px solid #fecaca;
            background: #fef2f2;
            padding: 12px;
            margin-top: 16px;
            color: #991b1b;
            border-radius: 5px;
            font-weight: 700;
        }


        .label {
            margin-bottom: 5px;
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }


        .value {
            font-size: 13px;
            font-weight: 700;
        }


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }


        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 7px 6px;
            vertical-align: top;
        }


        th {
            background: #f1f5f9;
            font-size: 10px;
            text-align: left;
            text-transform: uppercase;
        }


        .right {
            text-align: right;
        }


        .center {
            text-align: center;
        }


        .negative {
            color: #b91c1c;
            font-weight: 700;
        }


        .positive {
            color: #047857;
            font-weight: 700;
        }


        .zero {
            color: #475569;
        }


        .summary {
            width: 400px;
            margin-left: auto;
        }


        .summary-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 0;
        }


        .summary-row.total {
            border-top: 2px solid #111827;
            border-bottom: none;
            margin-top: 5px;
            padding-top: 10px;
            font-size: 14px;
            font-weight: 700;
        }


        .remarks {
            min-height: 60px;
            border: 1px solid #cbd5e1;
            padding: 10px;
            white-space: pre-line;
        }


        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 50px;
            margin-top: 70px;
        }


        .signature {
            border-top: 1px solid #111827;
            padding-top: 7px;
            text-align: center;
        }


        .footer {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            color: #64748b;
            font-size: 10px;
            text-align: center;
        }


        .no-print {
            margin-bottom: 20px;
            text-align: right;
        }


        .button {
            display: inline-block;
            border: 0;
            border-radius: 5px;
            background: #111827;
            color: #ffffff;
            cursor: pointer;
            padding: 9px 15px;
            font-size: 12px;
            font-weight: 700;
        }


        @page {
            size: A4 landscape;
            margin: 10mm;
        }


        @media print {

            body {
                padding: 0;
            }


            .no-print {
                display: none !important;
            }


            .page {
                max-width: none;
            }

        }

    </style>

</head>


<body>

@php

    /*
    |--------------------------------------------------------------------------
    | LOCATION
    |--------------------------------------------------------------------------
    */

    $auditLocation =
        $pharmacyStockAudit->location;

    $isLegacyAudit =
        empty(
            $pharmacyStockAudit->pharmacy_stock_location_id
        );


    /*
    |--------------------------------------------------------------------------
    | STATUS LABEL
    |--------------------------------------------------------------------------
    */

    $statusLabel =
        match ($pharmacyStockAudit->status) {

            'draft' =>
                'Draft',

            'counting' =>
                'Counting',

            'review' =>
                'Under Review',

            'approved' =>
                'Approved',

            'posted' =>
                'Posted',

            'cancelled' =>
                'Cancelled',

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $pharmacyStockAudit->status
                    )
                ),
        };

@endphp


<div class="page">


    {{-- ============================================================= --}}
    {{-- PRINT BUTTON --}}
    {{-- ============================================================= --}}

    <div class="no-print">

        <button
            type="button"
            class="button"
            onclick="window.print()"
        >
            Print Report
        </button>

    </div>



    {{-- ============================================================= --}}
    {{-- HEADER --}}
    {{-- ============================================================= --}}

    <div class="header">

        <div>

            <div class="hospital-name">
                Tura Christian Hospital
            </div>

            <div class="hospital-subtitle">
                Pharmacy Department · Physical Stock Verification
            </div>

        </div>


        <div class="report-title">

            <h1>
                STOCK AUDIT REPORT
            </h1>

            <div>
                {{ $pharmacyStockAudit->audit_no }}
            </div>

        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- AUDIT INFORMATION --}}
    {{-- ============================================================= --}}

    <div class="section">

        <div class="info-grid">


            <div class="info-box">

                <div class="label">
                    Stock Location
                </div>

                <div class="value">

                    @if ($auditLocation)

                        {{ $auditLocation->name }}

                    @else

                        Legacy / Unassigned

                    @endif

                </div>

                @if ($auditLocation)

                    <div style="margin-top: 4px; color: #64748b; font-size: 10px;">
                        {{ $auditLocation->code }}
                    </div>

                @endif

            </div>



            <div class="info-box">

                <div class="label">
                    Audit Date
                </div>

                <div class="value">
                    {{ $pharmacyStockAudit->audit_date?->format('d M Y') ?? '—' }}
                </div>

            </div>



            <div class="info-box">

                <div class="label">
                    Audit Type
                </div>

                <div class="value">
                    {{ ucwords(str_replace('_', ' ', $pharmacyStockAudit->audit_type)) }}
                </div>

            </div>



            <div class="info-box">

                <div class="label">
                    Status
                </div>

                <div class="value">
                    {{ $statusLabel }}
                </div>

            </div>



            <div class="info-box">

                <div class="label">
                    Total Batches
                </div>

                <div class="value">
                    {{ number_format($pharmacyStockAudit->items->count()) }}
                </div>

            </div>



            <div class="info-box">

                <div class="label">
                    Created By
                </div>

                <div class="value">
                    {{ $pharmacyStockAudit->createdBy?->name ?? 'System' }}
                </div>

            </div>



            <div class="info-box">

                <div class="label">
                    Approved By
                </div>

                <div class="value">
                    {{ $pharmacyStockAudit->approvedBy?->name ?? '—' }}
                </div>

            </div>



            <div class="info-box">

                <div class="label">
                    Posted By
                </div>

                <div class="value">
                    {{ $pharmacyStockAudit->postedBy?->name ?? '—' }}
                </div>

            </div>



            <div class="info-box">

                <div class="label">
                    Approved At
                </div>

                <div class="value">
                    {{ $pharmacyStockAudit->approved_at?->format('d M Y, h:i A') ?? '—' }}
                </div>

            </div>



            <div class="info-box">

                <div class="label">
                    Posted At
                </div>

                <div class="value">
                    {{ $pharmacyStockAudit->posted_at?->format('d M Y, h:i A') ?? '—' }}
                </div>

            </div>


        </div>


        @if ($auditLocation)

            <div class="location-box">

                <div class="location-title">
                    Physical Location Audited
                </div>

                <div class="location-name">
                    {{ $auditLocation->name }}
                </div>

                <div class="location-meta">

                    Location Code:
                    <strong>
                        {{ $auditLocation->code }}
                    </strong>

                    ·

                    System quantities in this report represent stock assigned
                    to this location when the audit snapshot was created.

                </div>

            </div>

        @else

            <div class="legacy-warning">

                Legacy audit: this record does not have a stock location assigned.
                It should be treated as a historical hospital-wide audit and should
                not be posted into the location-based inventory system.

            </div>

        @endif

    </div>



    {{-- ============================================================= --}}
    {{-- PHYSICAL STOCK VERIFICATION --}}
    {{-- ============================================================= --}}

    <div class="section">

        <div class="section-title">

            Physical Stock Verification

            @if ($auditLocation)

                —
                {{ $auditLocation->name }}

            @endif

        </div>


        <table>

            <thead>

                <tr>

                    <th>
                        Medicine
                    </th>

                    <th>
                        Batch
                    </th>

                    <th>
                        Expiry
                    </th>

                    <th class="right">
                        Location System Qty
                    </th>

                    <th class="right">
                        Physical Count
                    </th>

                    <th class="right">
                        Variance
                    </th>

                    <th class="right">
                        Purchase Price
                    </th>

                    <th class="right">
                        Variance Value
                    </th>

                    <th>
                        Reason
                    </th>

                    <th>
                        Remarks
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse ($pharmacyStockAudit->items as $item)

                    @php

                        $variance =
                            $item->variance_quantity;


                        $varianceClass =
                            $variance === null
                                ? 'zero'
                                : (
                                    $variance > 0
                                        ? 'positive'
                                        : (
                                            $variance < 0
                                                ? 'negative'
                                                : 'zero'
                                        )
                                );

                    @endphp


                    <tr>

                        <td>

                            <strong>
                                {{ $item->medicine_name }}
                            </strong>

                            @if ($item->brand_name || $item->strength)

                                <div style="margin-top: 3px; color: #64748b;">

                                    @if ($item->brand_name)
                                        {{ $item->brand_name }}
                                    @endif

                                    @if ($item->strength)
                                        · {{ $item->strength }}
                                    @endif

                                </div>

                            @endif

                        </td>



                        <td>
                            {{ $item->batch_number }}
                        </td>



                        <td>
                            {{ $item->expiry_date?->format('d M Y') ?? '—' }}
                        </td>



                        <td class="right">
                            {{ number_format($item->system_quantity) }}
                        </td>



                        <td class="right">

                            {{
                                $item->counted_quantity !== null
                                    ? number_format($item->counted_quantity)
                                    : '—'
                            }}

                        </td>



                        <td class="right {{ $varianceClass }}">

                            @if ($variance === null)

                                —

                            @else

                                {{ $variance > 0 ? '+' : '' }}{{ number_format($variance) }}

                            @endif

                        </td>



                        <td class="right">
                            ₹{{ number_format((float) $item->purchase_price, 2) }}
                        </td>



                        <td class="right {{ $varianceClass }}">

                            @if ($item->variance_value === null)

                                —

                            @else

                                ₹{{ number_format((float) $item->variance_value, 2) }}

                            @endif

                        </td>



                        <td>

                            {{
                                $item->variance_reason
                                    ? ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $item->variance_reason
                                        )
                                    )
                                    : '—'
                            }}

                        </td>



                        <td>
                            {{ $item->remarks ?: '—' }}
                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="10"
                            class="center"
                            style="padding: 20px;"
                        >
                            No stock batches were included in this audit.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    {{-- ============================================================= --}}
    {{-- SUMMARY --}}
    {{-- ============================================================= --}}

    <div class="section">

        <div class="summary">


            <div class="summary-row">

                <span>
                    Location System Quantity
                </span>

                <strong>
                    {{ number_format($totalSystemQty) }}
                </strong>

            </div>



            <div class="summary-row">

                <span>
                    Physical Quantity
                </span>

                <strong>
                    {{ number_format($totalCountedQty) }}
                </strong>

            </div>



            <div class="summary-row">

                <span>
                    Variance Batches
                </span>

                <strong>
                    {{ number_format($varianceItems) }}
                </strong>

            </div>



            <div class="summary-row">

                <span>
                    Net Quantity Variance
                </span>

                <strong
                    class="
                        {{
                            $netVarianceQty < 0
                                ? 'negative'
                                : (
                                    $netVarianceQty > 0
                                        ? 'positive'
                                        : ''
                                )
                        }}
                    "
                >
                    {{ $netVarianceQty > 0 ? '+' : '' }}{{ number_format($netVarianceQty) }}
                </strong>

            </div>



            <div class="summary-row total">

                <span>
                    Net Variance Value
                </span>

                <span
                    class="
                        {{
                            $netVarianceValue < 0
                                ? 'negative'
                                : (
                                    $netVarianceValue > 0
                                        ? 'positive'
                                        : ''
                                )
                        }}
                    "
                >
                    ₹{{ number_format((float) $netVarianceValue, 2) }}
                </span>

            </div>


        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- POSTING EFFECT --}}
    {{-- ============================================================= --}}

    @if ($pharmacyStockAudit->status === 'posted' && $auditLocation)

        <div class="section">

            <div class="section-title">
                Inventory Posting
            </div>

            <div
                style="
                    border: 1px solid #bbf7d0;
                    background: #f0fdf4;
                    padding: 12px;
                    color: #166534;
                "
            >

                This audit has been posted.

                Approved variances were applied to the stock balance of

                <strong>
                    {{ $auditLocation->name }}
                </strong>

                and to the corresponding hospital-wide stock batch balances.

            </div>

        </div>

    @endif



    {{-- ============================================================= --}}
    {{-- AUDIT REMARKS --}}
    {{-- ============================================================= --}}

    <div class="section">

        <div class="section-title">
            Audit Remarks
        </div>

        <div class="remarks">
            {{ $pharmacyStockAudit->remarks ?: 'No remarks.' }}
        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- SIGNATURES --}}
    {{-- ============================================================= --}}

    <div class="signatures">

        <div class="signature">

            Stock Counted By

            @if ($auditLocation)

                <div style="margin-top: 3px; color: #64748b; font-size: 10px;">
                    {{ $auditLocation->name }}
                </div>

            @endif

        </div>


        <div class="signature">
            Verified / Approved By
        </div>


        <div class="signature">
            Pharmacy In-Charge
        </div>

    </div>



    {{-- ============================================================= --}}
    {{-- FOOTER --}}
    {{-- ============================================================= --}}

    <div class="footer">

        Generated from Tura Christian Hospital ERP

        ·

        {{ now()->format('d M Y, h:i A') }}

        @if ($auditLocation)

            ·

            Location:
            {{ $auditLocation->name }}
            ({{ $auditLocation->code }})

        @endif

    </div>


</div>


</body>

</html>