<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        IP Advance Receipt - {{ $ipBillingAdvance->receipt_no }}
    </title>


    <style>

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .receipt-wrapper {
            max-width: 760px;
            margin: 0 auto;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 16px;
        }

        .button {
            display: inline-block;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .button-primary {
            background: #0f172a;
            color: white;
        }

        .button-secondary {
            background: white;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .receipt {
            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            overflow: hidden;
        }

        .header {
            padding: 28px 32px 22px;
            text-align: center;
            border-bottom: 2px solid #0f172a;
        }

        .hospital-name {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .hospital-address {
            margin-top: 6px;
            font-size: 13px;
            color: #475569;
        }

        .receipt-title {
            margin-top: 18px;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .section {
            padding: 22px 32px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section:last-child {
            border-bottom: none;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 28px;
        }

        .field-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        .field-value {
            margin-top: 4px;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .amount-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 20px 22px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
        }

        .amount-label {
            font-size: 14px;
            font-weight: 700;
            color: #475569;
        }

        .amount {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
        }

        .footer {
            padding: 24px 32px 28px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            margin-top: 48px;
        }

        .signature {
            width: 220px;
            text-align: center;
            padding-top: 8px;
            border-top: 1px solid #64748b;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        .note {
            margin-top: 26px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
        }


        @media print {

            body {
                padding: 0;
                background: white;
            }

            .toolbar {
                display: none;
            }

            .receipt-wrapper {
                max-width: none;
            }

            .receipt {
                border: none;
                border-radius: 0;
            }

            @page {
                size: A4;
                margin: 12mm;
            }

        }


        @media (max-width: 640px) {

            body {
                padding: 12px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .header,
            .section,
            .footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .signature-row {
                flex-direction: column;
                gap: 36px;
            }

            .signature {
                width: 100%;
            }

        }

    </style>

</head>


<body>

    @php

        $currentAllocation =
            $admission->currentBedAllocation;

        $currentBed =
            $currentAllocation?->bed
            ?? $admission->bed;

        $ward =
            $currentBed?->ward;

    @endphp


    <div class="receipt-wrapper">

        <div class="toolbar">

            <a
                href="{{ route('ip-billing.show', $admission) }}"
                class="button button-secondary"
            >
                Back
            </a>


            <button
                type="button"
                onclick="window.print()"
                class="button button-primary"
            >
                Print Receipt
            </button>

        </div>


        <div class="receipt">

            <div class="header">

                <div class="hospital-name">
                    TURA CHRISTIAN HOSPITAL
                </div>

                <div class="hospital-address">
                    Tura, West Garo Hills, Meghalaya
                </div>


                <div class="receipt-title">
                    Inpatient Advance Receipt
                </div>

            </div>


            <div class="section">

                <div class="grid">

                    <div>

                        <div class="field-label">
                            Receipt No.
                        </div>

                        <div class="field-value">
                            {{ $ipBillingAdvance->receipt_no }}
                        </div>

                    </div>


                    <div>

                        <div class="field-label">
                            Receipt Date
                        </div>

                        <div class="field-value">
                            {{ $ipBillingAdvance->payment_date?->format('d M Y, h:i A') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="field-label">
                            IP Billing Account
                        </div>

                        <div class="field-value">
                            {{ $account->account_no }}
                        </div>

                    </div>


                    <div>

                        <div class="field-label">
                            Admission No.
                        </div>

                        <div class="field-value">
                            {{ $admission->admission_no }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="section">

                <div class="grid">

                    <div>

                        <div class="field-label">
                            Patient Name
                        </div>

                        <div class="field-value">
                            {{ $patient->full_name }}
                        </div>

                    </div>


                    <div>

                        <div class="field-label">
                            UHID
                        </div>

                        <div class="field-value">
                            {{ $patient->uhid ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="field-label">
                            MRD No.
                        </div>

                        <div class="field-value">
                            {{ $patient->mrd_number ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="field-label">
                            Ward / Bed
                        </div>

                        <div class="field-value">
                            {{ $ward?->name ?? '—' }}
                            @if ($currentBed?->bed_number)
                                / {{ $currentBed->bed_number }}
                            @endif
                        </div>

                    </div>

                </div>

            </div>


            <div class="section">

                <div class="amount-box">

                    <div>

                        <div class="amount-label">
                            Advance / Deposit Received
                        </div>

                        <div class="field-value">
                            Payment Mode:
                            {{ strtoupper($ipBillingAdvance->payment_mode) }}
                        </div>

                    </div>


                    <div class="amount">
                        ₹{{ number_format((float) $ipBillingAdvance->amount, 2) }}
                    </div>

                </div>

            </div>


            <div class="section">

                <div class="grid">

                    <div>

                        <div class="field-label">
                            Transaction Reference
                        </div>

                        <div class="field-value">
                            {{ $ipBillingAdvance->transaction_reference ?: '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="field-label">
                            Received By
                        </div>

                        <div class="field-value">
                            {{ $ipBillingAdvance->receivedBy?->name ?? '—' }}
                        </div>

                    </div>


                    <div style="grid-column: 1 / -1;">

                        <div class="field-label">
                            Remarks
                        </div>

                        <div class="field-value">
                            {{ $ipBillingAdvance->remarks ?: '—' }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="footer">

                <div class="signature-row">

                    <div class="signature">
                        Patient / Attendant
                    </div>

                    <div class="signature">
                        Billing Counter
                    </div>

                </div>


                <div class="note">
                    This receipt acknowledges advance payment received against the inpatient account.
                </div>

            </div>

        </div>

    </div>

</body>

</html>