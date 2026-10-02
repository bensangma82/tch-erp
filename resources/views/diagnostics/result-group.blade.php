<x-app-layout>

    <x-slot name="header">
        <div class="screen-only flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Combined Laboratory Report
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Completed laboratory results from a single order.
                </p>
            </div>

            <div class="flex items-center gap-3">

                <a
                    href="{{ route('laboratory.index') }}"
                    class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Back to Laboratory
                </a>

                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Print Combined Report
                </button>

            </div>

        </div>
    </x-slot>


    @php

        $patient = $order?->patient;

        $encounter = $order?->encounter;

        $admission = $order?->admission;


        $departmentName =
            $admission?->department?->name
            ?? $encounter?->department?->name
            ?? '—';


        $doctorName =
            $admission?->consultant?->full_name
            ?? $admission?->consultant?->name
            ?? $encounter?->doctor?->full_name
            ?? $encounter?->doctor?->name
            ?? 'Unassigned';


        $latestReportedAt = $items
            ->map(fn ($item) => $item->diagnosticResult?->entered_at)
            ->filter()
            ->sortDesc()
            ->first();

    @endphp


    <style>

        /*
        |--------------------------------------------------------------------------
        | Screen
        |--------------------------------------------------------------------------
        */

        .group-report-shell {
            max-width: 980px;
            margin: 0 auto;
        }

        .group-report {
            background: #ffffff;
            border: 1px solid #dbe3ee;
            border-radius: 18px;

            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.08),
                0 2px 8px rgba(15, 23, 42, 0.04);

            overflow: hidden;

            color: #0f172a;

            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .group-inner {
            padding: 28px 32px 24px;
        }


        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        .brand {
            display: grid;

            grid-template-columns:
                78px
                minmax(0, 1fr)
                auto;

            align-items: center;

            gap: 18px;

            padding-bottom: 18px;

            border-bottom: 3px solid #10213c;
        }

        .logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .hospital {
            margin: 0;

            font-size: 24pt;
            line-height: 1.05;

            font-weight: 800;

            color: #10213c;
        }

        .sub {
            margin-top: 5px;

            font-size: 12.75pt;

            color: #64748b;
        }

        .dept {
            margin-top: 6px;

            font-size: 12pt;

            font-weight: 800;

            letter-spacing: 0.08em;

            text-transform: uppercase;

            color: #334155;
        }

        .heading {
            text-align: right;
        }

        .heading-title {
            font-size: 15.75pt;

            font-weight: 800;

            color: #10213c;
        }

        .heading-meta {
            margin-top: 5px;

            font-size: 11.25pt;

            color: #64748b;
        }


        /*
        |--------------------------------------------------------------------------
        | Patient Details
        |--------------------------------------------------------------------------
        */

        .meta {
            margin-top: 18px;

            border: 1px solid #dfe6ee;

            border-radius: 12px;

            overflow: hidden;
        }

        .meta-title {
            padding: 7px 12px;

            background: #f8fafc;

            border-bottom: 1px solid #e2e8f0;

            font-size: 10.5pt;

            font-weight: 800;

            letter-spacing: 0.1em;

            text-transform: uppercase;

            color: #64748b;
        }

        .meta-grid {
            display: grid;

            grid-template-columns:
                2fr
                1fr
                1fr
                1fr;

            gap: 0;
        }

        .meta-cell {
            padding: 9px 12px;

            border-right: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .label {
            font-size: 9.75pt;

            font-weight: 800;

            letter-spacing: 0.08em;

            text-transform: uppercase;

            color: #94a3b8;
        }

        .value {
            margin-top: 2px;

            font-size: 12pt;

            font-weight: 650;

            color: #1e293b;
        }


        /*
        |--------------------------------------------------------------------------
        | Investigation
        |--------------------------------------------------------------------------
        */

        .test {
            margin-top: 18px;

            border: 1px solid #dfe6ee;

            border-radius: 12px;

            overflow: hidden;
        }

        .test-head {
            display: flex;

            justify-content: space-between;

            gap: 16px;

            padding: 9px 12px;

            background: #f8fafc;

            border-bottom: 1px solid #e2e8f0;
        }

        .test-name {
            font-size: 13.5pt;

            font-weight: 800;

            color: #10213c;
        }

        .test-code {
            font-size: 10.5pt;

            color: #64748b;
        }

        .sample {
            font-size: 10.5pt;

            text-align: right;

            color: #64748b;
        }


        /*
        |--------------------------------------------------------------------------
        | Results
        |--------------------------------------------------------------------------
        */

        table.results {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }

        .results th {
            padding: 7px 10px;

            background: #fbfdff;

            border-bottom: 1px solid #e2e8f0;

            text-align: left;

            font-size: 9.75pt;

            text-transform: uppercase;

            letter-spacing: 0.06em;

            color: #64748b;
        }

        .results td {
            padding: 7px 10px;

            border-bottom: 1px solid #edf2f7;

            font-size: 11.25pt;

            color: #334155;

            vertical-align: top;
        }

        .results tr:last-child td {
            border-bottom: 0;
        }

        .result-value {
            font-weight: 800;

            color: #0f172a;
        }

        .abnormal {
            font-weight: 800;

            color: #a16207;
        }

        .critical {
            font-weight: 800;

            color: #b91c1c;
        }

        .result-remark {
            margin-top: 2px;

            font-size: 9.75pt;

            color: #7b8ba0;
        }

        .comment {
            padding: 8px 10px;

            background: #fbfcfe;

            border-top: 1px solid #edf2f7;

            font-size: 10.5pt;

            line-height: 1.4;

            color: #475569;

            white-space: pre-wrap;
        }


        /*
        |--------------------------------------------------------------------------
        | Footer
        |--------------------------------------------------------------------------
        */

        .footer {
            display: grid;

            grid-template-columns:
                1fr
                210px;

            gap: 26px;

            align-items: end;

            margin-top: 20px;
        }

        .footer-left {
            min-width: 0;
        }

        .print-meta {
            margin-bottom: 7px;

            font-size: 10px;

            line-height: 1.4;

            color: #475569;
        }

        .print-meta strong {
            color: #172033;
        }

        .print-separator {
            padding: 0 7px;

            color: #94a3b8;
        }

        .note {
            font-size: 10.5pt;

            line-height: 1.45;

            color: #7a8797;
        }

        .signature {
    text-align: center;
    color: #465870;
}

.signature-space {
    height: 28px;
}


.signature-title {
    margin-top: 6px;

    font-size: 11.25pt;

    font-weight: 700;

    line-height: 1.25;
}

        /*
        |--------------------------------------------------------------------------
        | A4 Print
        |--------------------------------------------------------------------------
        */

        @page {
            size: A4 portrait;

            margin:
                7mm
                7mm
                7mm
                7mm;
        }


        @media print {

            html,
            body {
                margin: 0 !important;

                padding: 0 !important;

                background: #ffffff !important;

                -webkit-print-color-adjust: exact !important;

                print-color-adjust: exact !important;
            }


            body * {
                visibility: hidden;
            }


            #combined-laboratory-report,
            #combined-laboratory-report * {
                visibility: visible;
            }


            #combined-laboratory-report {
                position: absolute;

                left: 0;

                top: 0;

                width: 100%;
            }


            .screen-only,
            aside,
            nav,
            header {
                display: none !important;
            }


            .group-report-shell,
            .group-report {
                width: 100% !important;

                max-width: none !important;

                min-height: 0 !important;

                height: auto !important;

                margin: 0 !important;

                padding: 0 !important;

                border: 0 !important;

                border-radius: 0 !important;

                box-shadow: none !important;

                overflow: visible !important;
            }


            .group-inner {
                padding: 0 !important;

                min-height: 0 !important;

                height: auto !important;
            }


            /*
            |--------------------------------------------------------------------------
            | Compact print header
            |--------------------------------------------------------------------------
            */

            .brand {
                grid-template-columns:
                    58px
                    minmax(0, 1fr)
                    auto !important;

                gap: 12px !important;

                padding-bottom: 8px !important;
            }

            .logo {
                width: 52px !important;

                height: 52px !important;
            }

            .hospital {
                font-size: 18pt !important;
            }

            .sub {
                font-size: 9.75pt !important;
            }

            .dept {
                font-size: 9pt !important;
            }

            .heading-title {
                font-size: 12pt !important;
            }

            .heading-meta {
                font-size: 9pt !important;
            }


            /*
            |--------------------------------------------------------------------------
            | Compact patient section
            |--------------------------------------------------------------------------
            */

            .meta {
                margin-top: 8px !important;

                break-inside: avoid !important;

                page-break-inside: avoid !important;
            }

            .meta-title {
                padding: 4px 7px !important;

                font-size: 8pt !important;
            }

            .meta-cell {
                padding: 4px 7px !important;
            }

            .label {
                font-size: 7.5pt !important;
            }

            .value {
                font-size: 9pt !important;
            }


            /*
            |--------------------------------------------------------------------------
            | Test pagination
            |--------------------------------------------------------------------------
            */

            .test {
                margin-top: 7px !important;

                border-radius: 5px !important;

                overflow: visible !important;

                break-inside: auto !important;

                page-break-inside: auto !important;
            }

            .test-head {
                padding: 4px 7px !important;

                break-after: avoid !important;

                page-break-after: avoid !important;
            }

            .test-name {
                font-size: 10pt !important;
            }

            .test-code,
            .sample {
                font-size: 8pt !important;
            }


            /*
            |--------------------------------------------------------------------------
            | Table
            |--------------------------------------------------------------------------
            */

            table.results {
                width: 100% !important;

                border-collapse: collapse !important;

                table-layout: fixed !important;

                break-inside: auto !important;

                page-break-inside: auto !important;
            }

            .results thead {
                display: table-header-group !important;
            }

            .results th {
                padding: 3px 6px !important;

                font-size: 7.5pt !important;
            }

            .results td {
                padding: 3px 6px !important;

                font-size: 8.75pt !important;

                line-height: 1.15 !important;
            }

            .results tr {
                break-inside: avoid !important;

                page-break-inside: avoid !important;
            }

            .results td,
            .results th {
                break-inside: avoid !important;

                page-break-inside: avoid !important;
            }

            .result-remark {
                font-size: 7.25pt !important;
            }

            .abnormal,
            .critical {
                color: #000000 !important;

                font-weight: 800 !important;
            }

            .comment {
                padding: 4px 7px !important;

                font-size: 8pt !important;

                break-inside: avoid !important;

                page-break-inside: avoid !important;
            }


            /*
            |--------------------------------------------------------------------------
            | Balanced Footer
            |--------------------------------------------------------------------------
            |
            | We deliberately save vertical space above by making patient/test
            | spacing slightly tighter. That allows the signature to breathe
            | without pushing the footer to another page.
            |--------------------------------------------------------------------------
            */

            .footer {
                margin-top: 8px !important;

                grid-template-columns:
                    minmax(0, 1fr)
                    165px !important;

                gap: 16px !important;

                align-items: start !important;

                break-inside: avoid !important;

                page-break-inside: avoid !important;

                break-before: auto !important;

                page-break-before: auto !important;
            }

            .footer-left {
                min-width: 0 !important;
            }

            .print-meta {
                margin-bottom: 3px !important;

                font-size: 7pt !important;

                line-height: 1.2 !important;

                color: #374151 !important;
            }

            .print-meta strong {
                font-weight: 700 !important;

                color: #111827 !important;
            }

            .print-separator {
                padding: 0 4px !important;
            }

            .note {
                font-size: 6.5pt !important;

                line-height: 1.2 !important;

                color: #64748b !important;
            }


            /*
            |--------------------------------------------------------------------------
            | Signatory
            |--------------------------------------------------------------------------
            |
            | Enough gap below the signature line, while keeping the actual
            | block compact enough to remain on page 1.
            |--------------------------------------------------------------------------
            */

            .signature {
    padding: 0 !important;

    border: 0 !important;

    text-align: center !important;

    break-inside: avoid !important;

    page-break-inside: avoid !important;
}

.signature-space {
    height: 22px !important;
}

.signature-line {
    width: 100% !important;

    border-top: 1px solid #475569 !important;
}

.signature-title {
    margin-top: 5px !important;

    font-size: 7.5pt !important;

    line-height: 1.25 !important;

    font-weight: 700 !important;

    color: #334155 !important;
}


            thead {
                display: table-header-group !important;
            }

            tfoot {
                display: table-footer-group !important;
            }

        }

    </style>


    <div class="py-6">

        <div class="group-report-shell px-4 sm:px-6 lg:px-8">


            <section
                id="combined-laboratory-report"
                class="group-report"
            >


                <div class="group-inner">


                    {{-- HOSPITAL HEADER --}}
                    <div class="brand">


                        <img
                            src="{{ asset('images/TCH_favicon.png') }}"
                            alt="Tura Christian Hospital"
                            class="logo"
                        >


                        <div>

                            <h1 class="hospital">
                                Tura Christian Hospital
                            </h1>

                            <div class="sub">
                                Tura, West Garo Hills, Meghalaya
                            </div>

                            <div class="sub">
                                Email: tchcare@yahoo.com
                                ·
                                Website: www.turachristianhospital.org
                            </div>

                            <div class="dept">
                                Department of Laboratory Medicine
                            </div>

                        </div>


                        <div class="heading">

                            <div class="heading-title">
                                Combined Laboratory Report
                            </div>

                            <div class="heading-meta">

                                {{ $items->count() }}
                                completed
                                {{ Str::plural('test', $items->count()) }}

                            </div>

                            <div class="heading-meta">

                                {{
                                    $latestReportedAt?->format(
                                        'd M Y, h:i A'
                                    )
                                    ?? now()->format(
                                        'd M Y, h:i A'
                                    )
                                }}

                            </div>

                        </div>


                    </div>


                    {{-- PATIENT DETAILS --}}
                    <div class="meta">


                        <div class="meta-title">
                            Patient & Order Details
                        </div>


                        <div class="meta-grid">


                            <div class="meta-cell">

                                <div class="label">
                                    Patient Name
                                </div>

                                <div class="value">
                                    {{ $patient?->full_name ?? '—' }}
                                </div>

                            </div>


                            <div class="meta-cell">

                                <div class="label">
                                    UHID
                                </div>

                                <div class="value">
                                    {{ $patient?->uhid ?? '—' }}
                                </div>

                            </div>


                            <div class="meta-cell">

                                <div class="label">
                                    MRD
                                </div>

                                <div class="value">
                                    {{ $patient?->mrd_number ?: '—' }}
                                </div>

                            </div>


                            <div class="meta-cell">

                                <div class="label">
                                    Age / Sex
                                </div>

                                <div class="value">

                                    {{
                                        $patient?->age !== null
                                            ? $patient->age . ' yrs'
                                            : '—'
                                    }}

                                    /

                                    {{ $patient?->sex ?: '—' }}

                                </div>

                            </div>


                            <div class="meta-cell">

                                <div class="label">
                                    Order No.
                                </div>

                                <div class="value">
                                    {{ $order?->order_no ?? '—' }}
                                </div>

                            </div>


                            <div class="meta-cell">

                                <div class="label">
                                    Department
                                </div>

                                <div class="value">
                                    {{ $departmentName }}
                                </div>

                            </div>


                            <div class="meta-cell">

                                <div class="label">
                                    Referring Doctor
                                </div>

                                <div class="value">
                                    {{ $doctorName }}
                                </div>

                            </div>


                            <div class="meta-cell">

                                <div class="label">
                                    Order Date
                                </div>

                                <div class="value">

                                    {{
                                        $order?->ordered_at?->format(
                                            'd M Y, h:i A'
                                        )
                                        ?? '—'
                                    }}

                                </div>

                            </div>


                        </div>


                    </div>


                    {{-- INVESTIGATIONS --}}
                    @foreach ($items as $serviceOrderItem)


                        @php

                            $result =
                                $serviceOrderItem->diagnosticResult;

                            $sample =
                                $serviceOrderItem->diagnosticSample;

                            $resultItems =
                                $result?->items ?? collect();

                        @endphp


                        <section class="test">


                            <div class="test-head">


                                <div>

                                    <div class="test-name">
                                        {{ $serviceOrderItem->service_name }}
                                    </div>

                                    <div class="test-code">

                                        {{
                                            $serviceOrderItem->service_code
                                            ?: '—'
                                        }}

                                    </div>

                                </div>


                                <div class="sample">


                                    @if ($serviceOrderItem->requires_sample)

                                        Sample:

                                        {{
                                            $sample?->sample_no
                                            ?? '—'
                                        }}


                                        @if ($sample?->specimen_type)

                                            ·
                                            {{ $sample->specimen_type }}

                                        @endif


                                        <br>

                                    @endif


                                    Reported:

                                    {{
                                        $result?->entered_at?->format(
                                            'd M Y, h:i A'
                                        )
                                        ?? '—'
                                    }}


                                </div>


                            </div>


                            @if ($resultItems->count())


                                <table class="results">


                                    <thead>

                                        <tr>

                                            <th style="width:31%;">
                                                Parameter
                                            </th>

                                            <th style="width:18%;">
                                                Result
                                            </th>

                                            <th style="width:15%;">
                                                Unit
                                            </th>

                                            <th style="width:25%;">
                                                Reference Range
                                            </th>

                                            <th style="width:11%; text-align:center;">
                                                Flag
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                        @foreach ($resultItems as $resultItem)


                                            @php

                                                $flag =
                                                    $resultItem->flag;


                                                if (! $flag) {

                                                    $rawResult = trim(
                                                        str_replace(
                                                            ',',
                                                            '',
                                                            (string) $resultItem->result_value
                                                        )
                                                    );


                                                    $range = trim(
                                                        str_replace(
                                                            ['–', '—', '−'],
                                                            '-',
                                                            (string) $resultItem->reference_range
                                                        )
                                                    );


                                                    if (
                                                        preg_match(
                                                            '/^-?\d+(?:\.\d+)?$/',
                                                            $rawResult
                                                        )
                                                    ) {

                                                        $numericResult =
                                                            (float) $rawResult;


                                                        $matches = [];


                                                        if (
                                                            preg_match(
                                                                '/^<=\s*(-?\d+(?:\.\d+)?)$/',
                                                                $range,
                                                                $matches
                                                            )
                                                        ) {

                                                            $flag =
                                                                $numericResult > (float) $matches[1]
                                                                    ? 'high'
                                                                    : null;


                                                        } elseif (
                                                            preg_match(
                                                                '/^<\s*(-?\d+(?:\.\d+)?)$/',
                                                                $range,
                                                                $matches
                                                            )
                                                        ) {

                                                            $flag =
                                                                $numericResult >= (float) $matches[1]
                                                                    ? 'high'
                                                                    : null;


                                                        } elseif (
                                                            preg_match(
                                                                '/^>=\s*(-?\d+(?:\.\d+)?)$/',
                                                                $range,
                                                                $matches
                                                            )
                                                        ) {

                                                            $flag =
                                                                $numericResult < (float) $matches[1]
                                                                    ? 'low'
                                                                    : null;


                                                        } elseif (
                                                            preg_match(
                                                                '/^>\s*(-?\d+(?:\.\d+)?)$/',
                                                                $range,
                                                                $matches
                                                            )
                                                        ) {

                                                            $flag =
                                                                $numericResult <= (float) $matches[1]
                                                                    ? 'low'
                                                                    : null;


                                                        } elseif (
                                                            preg_match(
                                                                '/^(-?\d+(?:\.\d+)?)\s*-\s*(-?\d+(?:\.\d+)?)$/',
                                                                $range,
                                                                $matches
                                                            )
                                                        ) {

                                                            $low =
                                                                (float) $matches[1];

                                                            $high =
                                                                (float) $matches[2];


                                                            if ($low > $high) {

                                                                [
                                                                    $low,
                                                                    $high
                                                                ] = [
                                                                    $high,
                                                                    $low
                                                                ];

                                                            }


                                                            $flag =
                                                                $numericResult < $low
                                                                    ? 'low'
                                                                    : (
                                                                        $numericResult > $high
                                                                            ? 'high'
                                                                            : null
                                                                    );

                                                        }

                                                    }

                                                }


                                                $flagLabel =
                                                    match ($flag) {

                                                        'low' =>
                                                            'LOW',

                                                        'high' =>
                                                            'HIGH',

                                                        'critical_low' =>
                                                            'CRITICAL LOW',

                                                        'critical_high' =>
                                                            'CRITICAL HIGH',

                                                        'abnormal' =>
                                                            'ABNORMAL',

                                                        default =>
                                                            '—',

                                                    };


                                                $valueClass =
                                                    match ($flag) {

                                                        'critical_low',
                                                        'critical_high' =>
                                                            'critical',

                                                        'low',
                                                        'high',
                                                        'abnormal' =>
                                                            'abnormal',

                                                        default =>
                                                            '',

                                                    };

                                            @endphp


                                            <tr>


                                                <td>

                                                    <strong>
                                                        {{ $resultItem->parameter_name }}
                                                    </strong>


                                                    @if ($resultItem->remarks)

                                                        <div class="result-remark">
                                                            {{ $resultItem->remarks }}
                                                        </div>

                                                    @endif

                                                </td>


                                                <td>

                                                    <span class="result-value {{ $valueClass }}">

                                                        {{
                                                            $resultItem->result_value
                                                            ?? '—'
                                                        }}

                                                    </span>

                                                </td>


                                                <td>

                                                    {{
                                                        $resultItem->unit
                                                        ?: '—'
                                                    }}

                                                </td>


                                                <td>

                                                    {{
                                                        $resultItem->reference_range
                                                        ?: '—'
                                                    }}

                                                </td>


                                                <td style="text-align:center;">
                                                    {{ $flagLabel }}
                                                </td>


                                            </tr>


                                        @endforeach


                                    </tbody>


                                </table>


                            @else


                                <div class="comment">
                                    No structured result parameters are available.
                                </div>


                            @endif


                            @if ($result?->result_text)


                                <div class="comment">

                                    <strong>
                                        Comment:
                                    </strong>

                                    {{ $result->result_text }}

                                </div>


                            @endif


                        </section>


                    @endforeach


                    {{-- COMPACT FOOTER --}}
                    <div class="footer">


                        <div class="footer-left">


                            <div class="print-meta">

                                Printed by:

                                <strong>
                                    {{ auth()->user()?->name ?? 'System' }}
                                </strong>


                                <span class="print-separator">
                                    •
                                </span>


                                Printed on:

                                <strong>
                                    {{ now()->format('d M Y, h:i A') }}
                                </strong>

                            </div>


                            <div class="note">

                                This report contains only finalized/verified
                                laboratory investigations from order
                                {{ $order?->order_no ?? '—' }}.

                                Investigations still awaiting sample,
                                in process, or in draft status are not included.

                            </div>


                        </div>


                        <div class="signature">
    <div class="signature-space"></div>
    <div class="signature-title">
        Authorized Laboratory Signatory
    </div>
</div>


                    </div>


                </div>


            </section>


        </div>


    </div>


    @if (request()->boolean('print'))

        <script>

            window.addEventListener(
                'load',
                function () {
                    window.print();
                }
            );

        </script>

    @endif


</x-app-layout>