<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payrollEntry->employee_name }} - {{ $payrollRun->payroll_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
        .toolbar {
            width: 210mm;
            max-width: calc(100% - 24px);
            margin: 18px auto 10px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: #fff;
            color: #334155;
            padding: 9px 14px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-primary {
            background: #0e7490;
            border-color: #0e7490;
            color: #fff;
        }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 24px;
            background: #fff;
            padding: 16mm;
            box-shadow: 0 2px 12px rgba(15, 23, 42, .12);
        }
        .hospital {
            display: grid;
            grid-template-columns: 92px 1fr 92px;
            align-items: center;
            gap: 14px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
        }
        .hospital-logo {
            width: 86px;
            height: 86px;
            object-fit: contain;
        }
        .hospital-center {
            text-align: center;
        }
        .hospital h1 {
            margin: 0;
            font-size: 21px;
            letter-spacing: .4px;
        }
        .hospital-tagline {
            margin: 4px 0 0;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #475569;
        }
        .hospital-address,
        .hospital-contact {
            margin: 5px 0 0;
            color: #475569;
            line-height: 1.45;
        }
        .hospital-contact {
            font-size: 10.5px;
        }
        .hospital-spacer {
            width: 86px;
        }
        .title {
            margin: 16px 0 4px;
            text-align: center;
            font-size: 17px;
            font-weight: 700;
        }
        .subtitle {
            text-align: center;
            color: #475569;
            margin-bottom: 16px;
        }
        .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .info td {
            width: 50%;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .label {
            display: inline-block;
            min-width: 105px;
            color: #64748b;
            font-weight: 700;
        }
        .section-title {
            margin: 15px 0 7px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        table.pay {
            width: 100%;
            border-collapse: collapse;
        }
        table.pay th,
        table.pay td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
        }
        table.pay th {
            background: #f8fafc;
            text-align: left;
        }
        .amount {
            text-align: right;
            white-space: nowrap;
        }
        .totals {
            width: 52%;
            margin-left: auto;
            margin-top: 16px;
            border-collapse: collapse;
        }
        .totals td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }
        .totals .net td {
            font-size: 14px;
            font-weight: 700;
            border-top: 2px solid #0f172a;
        }
        .note {
            margin-top: 28px;
            color: #64748b;
            font-size: 10px;
            text-align: center;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            gap: 40px;
        }
        .signature {
            width: 42%;
            border-top: 1px solid #64748b;
            padding-top: 6px;
            text-align: center;
        }
        @media print {
            @page { size: A4; margin: 0; }
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet {
                margin: 0;
                box-shadow: none;
                width: 210mm;
                min-height: 297mm;
            }
        }
    </style>
</head>
<body>
    @php
        $regularEarnings = $payrollEntry->items
            ->where('type', 'earning')
            ->sortBy('sort_order');

        $regularDeductions = $payrollEntry->items
            ->where('type', 'deduction')
            ->sortBy('sort_order');

        $earningAdjustments = $adjustments->where('type', 'earning');
        $deductionAdjustments = $adjustments->where('type', 'deduction');
        $lopAdjustments = $adjustments->where('type', 'lop');
    @endphp

    <div class="toolbar">
        <a
            class="btn"
            href="{{ route('admin.hr.payroll.runs.show', $payrollRun) }}"
        >
            Back to Payroll
        </a>

        <button class="btn btn-primary" type="button" onclick="window.print()">
            Print Payslip
        </button>
    </div>

    <main class="sheet">
        <header class="hospital">
            <img
                src="{{ asset('images/TCH_favicon.png') }}"
                alt="Tura Christian Hospital Logo"
                class="hospital-logo"
            >

            <div class="hospital-center">
                <h1>TURA CHRISTIAN HOSPITAL</h1>
                <div class="hospital-tagline">Love, Care and Healing</div>
                <p class="hospital-address">
                    Tura, West Garo Hills, Meghalaya
                </p>
                <p class="hospital-contact">
                    Email: tchcare@yahoo.com
                    &nbsp; | &nbsp;
                    Website: www.turachristianhospital.org
                </p>
            </div>

            <div class="hospital-spacer"></div>
        </header>

        <div class="title">SALARY PAYSLIP</div>
        <div class="subtitle">
            {{ \Illuminate\Support\Carbon::create(
                $payrollRun->year,
                $payrollRun->month,
                1
            )->format('F Y') }}
            &nbsp;·&nbsp;
            {{ $payrollRun->payroll_no }}
        </div>

        <table class="info">
            <tr>
                <td>
                    <span class="label">Employee:</span>
                    {{ $payrollEntry->employee_name }}
                </td>
                <td>
                    <span class="label">Employee Code:</span>
                    {{ $payrollEntry->employee_code ?: '—' }}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Designation:</span>
                    {{ $payrollEntry->designation ?: '—' }}
                </td>
                <td>
                    <span class="label">Department:</span>
                    {{ $payrollEntry->department_name ?: '—' }}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Employee Type:</span>
                    {{ $payrollEntry->employee_type
                        ? ucfirst(str_replace('_', ' ', $payrollEntry->employee_type))
                        : '—' }}
                </td>
                <td>
                    <span class="label">Payroll Status:</span>
                    {{ ucfirst($payrollEntry->status) }}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Calendar Days:</span>
                    {{ number_format((float) $payrollEntry->calendar_days, 0) }}
                </td>
                <td>
                    <span class="label">Payable Days:</span>
                    {{ number_format((float) $payrollEntry->payable_days, 2) }}
                    @if ((float) $payrollEntry->lop_days > 0)
                        (LOP: {{ number_format((float) $payrollEntry->lop_days, 2) }} day(s))
                    @endif
                </td>
            </tr>
        </table>

        <h2 class="section-title">Earnings</h2>
        <table class="pay">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="width: 34%;">Code</th>
                    <th class="amount" style="width: 26%;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($regularEarnings as $item)
                    <tr>
                        <td>{{ $item->component_name }}</td>
                        <td>{{ $item->component_code }}</td>
                        <td class="amount">{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                @endforeach

                @foreach ($earningAdjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->name }}</td>
                        <td>{{ $adjustment->code }}</td>
                        <td class="amount">{{ number_format((float) $adjustment->amount, 2) }}</td>
                    </tr>
                @endforeach

                <tr>
                    <th colspan="2">Gross Earnings</th>
                    <th class="amount">
                        ₹{{ number_format((float) $payrollEntry->gross_earnings, 2) }}
                    </th>
                </tr>
            </tbody>
        </table>

        <h2 class="section-title">Deductions</h2>
        <table class="pay">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="width: 34%;">Code</th>
                    <th class="amount" style="width: 26%;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($regularDeductions as $item)
                    <tr>
                        <td>{{ $item->component_name }}</td>
                        <td>{{ $item->component_code }}</td>
                        <td class="amount">{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                @endforeach

                @foreach ($deductionAdjustments as $adjustment)
                    <tr>
                        <td>{{ $adjustment->name }}</td>
                        <td>{{ $adjustment->code }}</td>
                        <td class="amount">{{ number_format((float) $adjustment->amount, 2) }}</td>
                    </tr>
                @endforeach

                @if ((float) $payrollEntry->lop_deduction > 0)
                    <tr>
                        <td>
                            Loss of Pay
                            @if ((float) $payrollEntry->lop_days > 0)
                                ({{ number_format((float) $payrollEntry->lop_days, 2) }} day(s))
                            @endif
                        </td>
                        <td>LOP</td>
                        <td class="amount">
                            {{ number_format((float) $payrollEntry->lop_deduction, 2) }}
                        </td>
                    </tr>
                @endif

                <tr>
                    <th colspan="2">Total Deductions</th>
                    <th class="amount">
                        ₹{{ number_format((float) $payrollEntry->total_deductions, 2) }}
                    </th>
                </tr>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Gross Earnings</td>
                <td class="amount">₹{{ number_format((float) $payrollEntry->gross_earnings, 2) }}</td>
            </tr>
            <tr>
                <td>Total Deductions</td>
                <td class="amount">₹{{ number_format((float) $payrollEntry->total_deductions, 2) }}</td>
            </tr>
            <tr class="net">
                <td>NET PAY</td>
                <td class="amount">₹{{ number_format((float) $payrollEntry->net_pay, 2) }}</td>
            </tr>
        </table>

        <div class="signatures">
            <div class="signature">Employee</div>
            <div class="signature">Authorised Signatory</div>
        </div>

        <p class="note">
            This payslip is generated from the approved payroll snapshot.
        </p>
    </main>
</body>
</html>
