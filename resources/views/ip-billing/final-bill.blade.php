<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Final IP Bill - {{ $account->final_bill_no }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --ink: #172033;
            --muted: #64748b;
            --line: #d9e1ea;
            --soft: #f7f9fc;
            --navy: #16263f;
            --blue: #2563eb;
            --green: #0f8a63;
            --amber: #b45309;
            --red: #b91c1c;
            --violet: #6d28d9;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #eef2f7;
            color: var(--ink);
            font-family:
                Inter,
                ui-sans-serif,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Arial,
                sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }

        body {
            padding: 24px;
        }

        .screen-toolbar {
            max-width: 1040px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .screen-toolbar a,
        .screen-toolbar button {
            appearance: none;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .screen-toolbar .primary {
            background: var(--navy);
            color: #fff;
            border-color: var(--navy);
        }

        .bill-sheet {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d7dee8;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.10);
        }

        .bill-inner {
            padding: 34px 38px 30px;
        }

        .header {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 28px;
            align-items: start;
            padding-bottom: 22px;
            border-bottom: 2px solid var(--navy);
        }

        .hospital-name {
            margin: 0;
            font-size: 28px;
            line-height: 1.12;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: var(--navy);
        }

        .hospital-location {
            margin-top: 7px;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
        }

        .document-title {
            display: inline-block;
            margin-top: 18px;
            padding: 6px 10px;
            background: #eef3f8;
            color: var(--navy);
            border-radius: 5px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.11em;
            text-transform: uppercase;
        }

        .bill-meta {
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .bill-meta-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px;
            padding: 9px 12px;
            border-bottom: 1px solid var(--line);
        }

        .bill-meta-row:last-child {
            border-bottom: 0;
        }

        .bill-meta dt {
            margin: 0;
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .bill-meta dd {
            margin: 0;
            text-align: right;
            font-size: 12px;
            font-weight: 700;
            color: var(--ink);
            word-break: break-word;
        }

        .bill-meta .bill-number {
            color: var(--navy);
            font-size: 15px;
            font-weight: 800;
        }

        .section {
            margin-top: 22px;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: 800;
            color: var(--navy);
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .details-panel {
            padding: 14px 16px 15px;
        }

        .details-panel + .details-panel {
            border-left: 1px solid var(--line);
        }

        .details-panel h3 {
            margin: 0 0 10px;
            font-size: 11px;
            font-weight: 800;
            color: #475569;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .detail-list {
            display: grid;
            grid-template-columns: 108px 1fr;
            column-gap: 12px;
            row-gap: 6px;
            margin: 0;
        }

        .detail-list dt {
            margin: 0;
            color: var(--muted);
            font-size: 11px;
            font-weight: 600;
        }

        .detail-list dd {
            margin: 0;
            color: var(--ink);
            font-size: 11px;
            font-weight: 700;
        }

        .patient-name {
            font-size: 13px !important;
            font-weight: 800 !important;
        }

        .charges-wrap {
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .charge-group + .charge-group {
            border-top: 2px solid #cbd5e1;
        }

        .group-header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            padding: 9px 12px;
            background: #f1f5f9;
            color: var(--navy);
            font-size: 11px;
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 8px 9px;
            background: #fafbfc;
            color: #64748b;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            text-align: left;
        }

        td {
            padding: 8px 9px;
            border-bottom: 1px solid #e9eef4;
            vertical-align: top;
            font-size: 10.5px;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .right {
            text-align: right;
            white-space: nowrap;
        }

        .code {
            color: #64748b;
            font-size: 9.5px;
            white-space: nowrap;
        }

        .description {
            font-weight: 650;
            color: #1f2937;
        }

        .group-total td {
            background: #fbfcfe;
            font-weight: 700;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
            align-items: start;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .card-title {
            padding: 9px 12px;
            background: #f8fafc;
            border-bottom: 1px solid var(--line);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #475569;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 18px;
            padding: 9px 12px;
            border-bottom: 1px solid #edf1f5;
            font-size: 11px;
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .summary-label {
            color: #475569;
            font-weight: 650;
        }

        .summary-value {
            color: #172033;
            font-weight: 800;
            white-space: nowrap;
        }

        .summary-row.net {
            background: #f8fafc;
        }

        .summary-row.net .summary-label,
        .summary-row.net .summary-value {
            color: var(--navy);
            font-size: 12px;
            font-weight: 800;
        }

        .summary-row.patient-balance {
            background: var(--navy);
            color: #fff;
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .summary-row.patient-balance .summary-label,
        .summary-row.patient-balance .summary-value {
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .mhis-box {
            border: 1px solid #ddd6fe;
            background: #faf8ff;
            border-radius: 10px;
            padding: 12px 14px;
        }

        .mhis-title {
            margin: 0 0 10px;
            color: var(--violet);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .mhis-mini-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 14px;
            margin-bottom: 10px;
        }

        .mini-label {
            color: #7c3aed;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .mini-value {
            margin-top: 2px;
            color: var(--ink);
            font-size: 10.5px;
            font-weight: 700;
        }

        .mhis-stat {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 6px 0;
            border-top: 1px solid #ede9fe;
            font-size: 10.5px;
        }

        .mhis-stat strong {
            font-weight: 800;
        }

        .green {
            color: var(--green);
        }

        .amber {
            color: var(--amber);
        }

        .violet {
            color: var(--violet);
        }

        .payment-table th,
        .payment-table td {
            font-size: 9.5px;
            padding: 7px 8px;
        }

        .receivable-note {
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px solid #f6c58a;
            background: #fff8ed;
            border-radius: 8px;
            color: #92400e;
            font-size: 10px;
            line-height: 1.4;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 28px;
            margin-top: 32px;
            padding-top: 10px;
        }

        .signature {
            text-align: center;
        }

        .signature-line {
            height: 34px;
            border-bottom: 1px solid #94a3b8;
        }

        .signature-label {
            padding-top: 6px;
            color: #475569;
            font-size: 9.5px;
            font-weight: 700;
        }

        .footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid var(--line);
            color: #7a8798;
            font-size: 9px;
            text-align: center;
        }

        .avoid-break {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .no-data {
            padding: 18px;
            color: var(--muted);
            text-align: center;
            font-size: 11px;
        }

        @page {
            size: A4;
            margin: 9mm 10mm 10mm;
        }

        @media print {
            html,
            body {
                background: #fff !important;
                font-size: 11px;
            }

            body {
                padding: 0;
            }

            .screen-toolbar {
                display: none !important;
            }

            .bill-sheet {
                max-width: none;
                border: 0;
                box-shadow: none;
            }

            .bill-inner {
                padding: 0;
            }

            .header {
                padding-bottom: 14px;
            }

            .hospital-name {
                font-size: 23px;
            }

            .section {
                margin-top: 16px;
            }

            .details-panel {
                padding: 10px 12px;
            }

            .summary-grid {
                gap: 12px;
            }

            .charge-group,
            .card,
            .mhis-box,
            .details-grid {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-row-group;
            }

            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body>

@php
    $patient = $admission->patient;

    $currentBed =
        $admission->currentBedAllocation?->bed
        ?? $admission->bed;

    $currentWard =
        $currentBed?->ward;

    $activeAdvances =
        $account->advances
            ->where('status', 'active');

    $activeMhisReceipts =
        $account->mhisReceipts
            ->where('status', 'active');

    $patientLiabilityBeforePayments =
        max(
            (float) $netAmount
            - (float) $mhisApprovedAmount,
            0
        );
@endphp


<div class="screen-toolbar">
    <a href="{{ route('ip-billing.show', $admission) }}">
        ← Back to IP Billing
    </a>

    <button
        type="button"
        class="primary"
        onclick="window.print()"
    >
        Print Final Bill
    </button>
</div>


<div class="bill-sheet">

    <div class="bill-inner">

        <header class="header">

            <div>
                <h1 class="hospital-name">
                    TURA CHRISTIAN HOSPITAL
                </h1>

                <div class="hospital-location">
                    Tura, West Garo Hills, Meghalaya
                </div>

                <div class="document-title">
                    Final Inpatient Bill
                </div>
            </div>


            <dl class="bill-meta">

                <div class="bill-meta-row">
                    <dt>Final Bill No.</dt>
                    <dd class="bill-number">
                        {{ $account->final_bill_no }}
                    </dd>
                </div>

                <div class="bill-meta-row">
                    <dt>Billing Account</dt>
                    <dd>
                        {{ $account->account_no }}
                    </dd>
                </div>

                <div class="bill-meta-row">
                    <dt>Bill Date</dt>
                    <dd>
                        {{ $account->finalized_at?->format('d M Y') ?? now()->format('d M Y') }}
                    </dd>
                </div>

                <div class="bill-meta-row">
                    <dt>Finalized At</dt>
                    <dd>
                        {{ $account->finalized_at?->format('d M Y, h:i A') ?? '—' }}
                    </dd>
                </div>

            </dl>

        </header>


        <section class="section">

            <div class="details-grid">

                <div class="details-panel">

                    <h3>Patient Details</h3>

                    <dl class="detail-list">

                        <dt>Name</dt>
                        <dd class="patient-name">
                            {{ $patient?->full_name ?? '—' }}
                        </dd>

                        <dt>UHID</dt>
                        <dd>
                            {{ $patient?->uhid ?? '—' }}
                        </dd>

                        <dt>MRD No.</dt>
                        <dd>
                            {{ $patient?->mrd_number ?: '—' }}
                        </dd>

                        <dt>Sex / Age</dt>
                        <dd>
                            {{ ucfirst($patient?->sex ?? '—') }}
                            @if (! is_null($patient?->age))
                                / {{ $patient->age }} years
                            @endif
                        </dd>

                        <dt>Phone</dt>
                        <dd>
                            {{ $patient?->phone ?: '—' }}
                        </dd>

                    </dl>

                </div>


                <div class="details-panel">

                    <h3>Admission Details</h3>

                    <dl class="detail-list">

                        <dt>Admission No.</dt>
                        <dd>
                            {{ $admission->admission_no }}
                        </dd>

                        <dt>Admitted</dt>
                        <dd>
                            {{ $admission->admitted_at?->format('d M Y, h:i A') ?? '—' }}
                        </dd>

                        <dt>Discharged</dt>
                        <dd>
                            {{ $admission->discharged_at?->format('d M Y, h:i A') ?? '—' }}
                        </dd>

                        <dt>Department</dt>
                        <dd>
                            {{ $admission->department?->name ?? '—' }}
                        </dd>

                        <dt>Consultant</dt>
                        <dd>
                            {{ $admission->consultant?->name ?? '—' }}
                        </dd>

                        <dt>Ward / Bed</dt>
                        <dd>
                            {{ $currentWard?->name ?? '—' }}
                            @if ($currentBed?->bed_number)
                                / {{ $currentBed->bed_number }}
                            @endif
                        </dd>

                    </dl>

                </div>

            </div>

        </section>


        <section class="section">

            <h2 class="section-title">
                Itemized Charges
            </h2>


            @if ($chargeGroups->isEmpty())

                <div class="charges-wrap">
                    <div class="no-data">
                        No active inpatient charges found.
                    </div>
                </div>

            @else

                <div class="charges-wrap">

                    @foreach ($chargeGroups as $groupName => $charges)

                        @php
                            $groupGross =
                                $charges->sum(
                                    fn ($charge) =>
                                        (float) $charge->quantity
                                        * (float) $charge->unit_price
                                );

                            $groupDiscount =
                                $charges->sum(
                                    fn ($charge) =>
                                        (float) $charge->discount
                                );

                            $groupNet =
                                $charges->sum(
                                    fn ($charge) =>
                                        (float) $charge->amount
                                );
                        @endphp


                        <div class="charge-group">

                            <div class="group-header">

                                <span>
                                    {{ $groupName }}
                                </span>

                                <span>
                                    ₹{{ number_format((float) $groupNet, 2) }}
                                </span>

                            </div>


                            <table>

                                <thead>
                                    <tr>
                                        <th style="width: 10%;">Date</th>
                                        <th style="width: 14%;">Code</th>
                                        <th>Description</th>
                                        <th class="right" style="width: 8%;">Qty</th>
                                        <th class="right" style="width: 12%;">Rate</th>
                                        <th class="right" style="width: 12%;">Discount</th>
                                        <th class="right" style="width: 13%;">Amount</th>
                                    </tr>
                                </thead>


                                <tbody>

                                    @foreach ($charges as $charge)

                                        <tr>

                                            <td>
                                                {{ $charge->charge_date?->format('d M Y') ?? '—' }}
                                            </td>

                                            <td class="code">
                                                {{ $charge->code ?: '—' }}
                                            </td>

                                            <td class="description">
                                                {{ $charge->description }}
                                            </td>

                                            <td class="right">
                                                {{ number_format((float) $charge->quantity, 2) }}
                                            </td>

                                            <td class="right">
                                                ₹{{ number_format((float) $charge->unit_price, 2) }}
                                            </td>

                                            <td class="right">
                                                ₹{{ number_format((float) $charge->discount, 2) }}
                                            </td>

                                            <td class="right">
                                                <strong>
                                                    ₹{{ number_format((float) $charge->amount, 2) }}
                                                </strong>
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>


                                <tfoot>

                                    <tr class="group-total">

                                        <td colspan="4" class="right">
                                            Group Total
                                        </td>

                                        <td class="right">
                                            ₹{{ number_format((float) $groupGross, 2) }}
                                        </td>

                                        <td class="right">
                                            ₹{{ number_format((float) $groupDiscount, 2) }}
                                        </td>

                                        <td class="right">
                                            ₹{{ number_format((float) $groupNet, 2) }}
                                        </td>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    @endforeach

                </div>

            @endif

        </section>


        <section class="section">

            <div class="summary-grid">

                <div>

                    <div class="card avoid-break">

                        <div class="card-title">
                            Patient Payments / Advances
                        </div>

                        @if ($activeAdvances->isEmpty() && (float) $paidAmount <= 0)

                            <div class="no-data">
                                No patient payment recorded.
                            </div>

                        @else

                            <table class="payment-table">

                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Receipt</th>
                                        <th>Mode</th>
                                        <th class="right">Amount</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($activeAdvances as $advance)

                                        <tr>
                                            <td>
                                                {{ $advance->payment_date?->format('d M Y') ?? '—' }}
                                            </td>

                                            <td>
                                                {{ $advance->receipt_no }}
                                            </td>

                                            <td>
                                                {{ strtoupper($advance->payment_mode) }}
                                            </td>

                                            <td class="right">
                                                <strong>
                                                    ₹{{ number_format((float) $advance->amount, 2) }}
                                                </strong>
                                            </td>
                                        </tr>

                                    @endforeach


                                    @if ((float) $paidAmount > 0)

                                        <tr>
                                            <td colspan="3">
                                                Other patient payments
                                            </td>

                                            <td class="right">
                                                <strong>
                                                    ₹{{ number_format((float) $paidAmount, 2) }}
                                                </strong>
                                            </td>
                                        </tr>

                                    @endif

                                </tbody>

                            </table>

                        @endif

                    </div>


                    @if ($mhisClaim)

                        <div class="mhis-box avoid-break" style="margin-top: 12px;">

                            <h3 class="mhis-title">
                                Meghalaya Health Insurance Scheme
                            </h3>

                            <div class="mhis-mini-grid">

                                <div>
                                    <div class="mini-label">
                                        Claim No.
                                    </div>

                                    <div class="mini-value">
                                        {{ $mhisClaim->claim_no ?: '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="mini-label">
                                        Authorization No.
                                    </div>

                                    <div class="mini-value">
                                        {{ $mhisClaim->authorization_no ?: '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="mini-label">
                                        Package
                                    </div>

                                    <div class="mini-value">
                                        {{ $mhisClaim->package_name ?: '—' }}
                                    </div>
                                </div>


                                <div>
                                    <div class="mini-label">
                                        Status
                                    </div>

                                    <div class="mini-value">
                                        {{ ucfirst($mhisClaim->status) }}
                                    </div>
                                </div>

                            </div>


                            <div class="mhis-stat">
                                <span>Claim Amount</span>

                                <strong>
                                    ₹{{ number_format((float) $mhisClaim->claim_amount, 2) }}
                                </strong>
                            </div>

                            <div class="mhis-stat">
                                <span>Approved Share</span>

                                <strong class="violet">
                                    ₹{{ number_format((float) $mhisApprovedAmount, 2) }}
                                </strong>
                            </div>

                            <div class="mhis-stat">
                                <span>Received by Hospital</span>

                                <strong class="green">
                                    ₹{{ number_format((float) $mhisReceivedAmount, 2) }}
                                </strong>
                            </div>

                            <div class="mhis-stat">
                                <span>Outstanding MHIS Receivable</span>

                                <strong class="amber">
                                    ₹{{ number_format((float) $mhisOutstandingAmount, 2) }}
                                </strong>
                            </div>

                        </div>

                    @endif

                </div>


                <div>

                    <div class="card avoid-break">

                        <div class="card-title">
                            Final Bill Summary
                        </div>

                        <div class="summary-row">
                            <span class="summary-label">
                                Gross Charges
                            </span>

                            <span class="summary-value">
                                ₹{{ number_format((float) $grossAmount, 2) }}
                            </span>
                        </div>

                        <div class="summary-row">
                            <span class="summary-label">
                                Less: Discounts
                            </span>

                            <span class="summary-value" style="color: var(--red);">
                                ₹{{ number_format((float) $discountAmount, 2) }}
                            </span>
                        </div>

                        <div class="summary-row net">
                            <span class="summary-label">
                                Net Bill
                            </span>

                            <span class="summary-value">
                                ₹{{ number_format((float) $netAmount, 2) }}
                            </span>
                        </div>


                        @if ((float) $mhisApprovedAmount > 0)

                            <div class="summary-row">
                                <span class="summary-label">
                                    Less: MHIS Approved Share
                                </span>

                                <span class="summary-value violet">
                                    ₹{{ number_format((float) $mhisApprovedAmount, 2) }}
                                </span>
                            </div>

                            <div class="summary-row">
                                <span class="summary-label">
                                    Patient Liability Before Payments
                                </span>

                                <span class="summary-value">
                                    ₹{{ number_format((float) $patientLiabilityBeforePayments, 2) }}
                                </span>
                            </div>

                        @endif


                        <div class="summary-row">
                            <span class="summary-label">
                                Less: Advance / Deposits
                            </span>

                            <span class="summary-value" style="color: var(--blue);">
                                ₹{{ number_format((float) $advanceAmount, 2) }}
                            </span>
                        </div>


                        @if ((float) $paidAmount > 0)

                            <div class="summary-row">
                                <span class="summary-label">
                                    Less: Other Patient Payments
                                </span>

                                <span class="summary-value" style="color: var(--blue);">
                                    ₹{{ number_format((float) $paidAmount, 2) }}
                                </span>
                            </div>

                        @endif


                        <div class="summary-row patient-balance">
                            <span class="summary-label">
                                Patient Balance Payable
                            </span>

                            <span class="summary-value">
                                ₹{{ number_format((float) $patientBalance, 2) }}
                            </span>
                        </div>

                    </div>


                    @if ((float) $mhisOutstandingAmount > 0)

                        <div class="receivable-note avoid-break">
                            <strong>
                                MHIS Receivable:
                                ₹{{ number_format((float) $mhisOutstandingAmount, 2) }}
                            </strong>
                            <br>
                            This amount is receivable from MHIS and is not part of the patient's outstanding balance.
                        </div>

                    @endif

                </div>

            </div>

        </section>


        @if ($mhisClaim && $activeMhisReceipts->isNotEmpty())

            <section class="section avoid-break">

                <h2 class="section-title">
                    MHIS Payment History
                </h2>

                <div class="card">

                    <table class="payment-table">

                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payment Ref.</th>
                                <th>Transaction Ref.</th>
                                <th>Bank Ref.</th>
                                <th class="right">Amount</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($activeMhisReceipts as $receipt)

                                <tr>
                                    <td>
                                        {{ $receipt->receipt_date?->format('d M Y') ?? '—' }}
                                    </td>

                                    <td>
                                        {{ $receipt->payment_reference ?: '—' }}
                                    </td>

                                    <td>
                                        {{ $receipt->transaction_reference ?: '—' }}
                                    </td>

                                    <td>
                                        {{ $receipt->bank_reference ?: '—' }}
                                    </td>

                                    <td class="right">
                                        <strong>
                                            ₹{{ number_format((float) $receipt->amount, 2) }}
                                        </strong>
                                    </td>
                                </tr>

                            @endforeach

                        </tbody>

                        <tfoot>

                            <tr class="group-total">
                                <td colspan="4" class="right">
                                    Total MHIS Received
                                </td>

                                <td class="right">
                                    ₹{{ number_format((float) $mhisReceivedAmount, 2) }}
                                </td>
                            </tr>

                        </tfoot>

                    </table>

                </div>

            </section>

        @endif


        <section class="signatures avoid-break">

            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-label">
                    Prepared By
                </div>
            </div>

            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-label">
                    Patient / Attendant
                </div>
            </div>

            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-label">
                    Authorized Billing Signatory
                </div>
            </div>

        </section>


        <footer class="footer">
            Finalized by
            {{ $account->finalizedBy?->name ?? '—' }}
            on
            {{ $account->finalized_at?->format('d M Y, h:i A') ?? '—' }}.
            This bill is generated from the Tura Christian Hospital ERP.
        </footer>

    </div>

</div>

</body>
</html>
