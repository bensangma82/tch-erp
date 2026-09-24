<x-app-layout>

    <x-slot name="header">
        <div class="screen-only flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Imaging Report
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Finalized imaging report.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('imaging.index') }}"
                    class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Back to Imaging
                </a>

                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Print Report
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $order = $serviceOrderItem->serviceOrder;
        $patient = $order?->patient;
        $encounter = $order?->encounter;
        $admission = $order?->admission;
        $report = $serviceOrderItem->diagnosticResult;

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

        $reportStatus =
            $report?->status === 'verified'
                ? 'Verified'
                : 'Final';

        $structured = is_array($report?->structured_data) ? $report->structured_data : [];

        $studyName = strtolower(trim((string) ($serviceOrderItem->service_name ?? '')));

        $isEcho =
            data_get($structured, 'modality') === 'echocardiography'
            || str_contains($studyName, 'echocardiography')
            || str_contains($studyName, '2d echo')
            || str_contains($studyName, '2-d echo');

        $reportTitle = $isEcho ? 'Echocardiography Report' : 'Imaging Report';
        $departmentTitle = $isEcho
            ? 'Department of Cardiac Imaging'
            : 'Department of Radiology & Imaging';

        $echo = fn (string $path, $default = '—') => data_get($structured, $path, $default);

        $displayEcho = function (string $path, string $unit = '') use ($echo) {
            $value = $echo($path, null);

            if ($value === null || $value === '') {
                return '—';
            }

            return trim((string) $value . ($unit !== '' ? ' ' . $unit : ''));
        };
    @endphp

    <style>
        .imaging-report-shell {
            max-width: 980px;
            margin: 0 auto;
        }

        .imaging-report {
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

        .report-inner {
            padding: 28px 32px 26px;
        }

        .report-brand {
            display: grid;
            grid-template-columns: 78px minmax(0, 1fr) auto;
            align-items: center;
            gap: 18px;
            padding-bottom: 18px;
            border-bottom: 3px solid #10213c;
        }

        .report-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .hospital-name {
            margin: 0;
            font-size: 28px;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #10213c;
        }

        .hospital-address {
            margin-top: 5px;
            font-size: 15px;
            color: #64748b;
        }

        .hospital-contact {
            margin-top: 4px;
            font-size: 12px;
            line-height: 1.3;
            font-weight: 600;
            color: #64748b;
        }

        .department-name {
            margin-top: 8px;
            font-size: 13px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #48617f;
        }

        .report-heading {
            text-align: right;
        }

        .report-heading-title {
            font-size: 25px;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #10213c;
        }

        .report-status {
            display: inline-flex;
            margin-top: 8px;
            border-radius: 999px;
            padding: 4px 10px;
            background: #eaf1f8;
            color: #16375d;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .report-time {
            margin-top: 6px;
            font-size: 13px;
            color: #64748b;
        }

        .meta-panel {
            margin-top: 15px;
            border: 1px solid #dce4ee;
            border-radius: 13px;
            overflow: hidden;
        }

        .meta-panel-title {
            padding: 8px 13px;
            background: #f6f8fb;
            border-bottom: 1px solid #dce4ee;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #4a607b;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .meta-item {
            min-width: 0;
            padding: 10px 12px;
            border-right: 1px solid #e5ebf2;
            border-bottom: 1px solid #e5ebf2;
        }

        .meta-item:nth-child(4n) {
            border-right: none;
        }

        .meta-item:nth-last-child(-n + 4) {
            border-bottom: none;
        }

        .meta-label {
            margin-bottom: 3px;
            font-size: 11px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: #8a9bb1;
        }

        .meta-value {
            font-size: 14px;
            line-height: 1.3;
            font-weight: 700;
            color: #172033;
            overflow-wrap: anywhere;
        }

        .report-section {
            margin-top: 15px;
            border: 1px solid #d8e1eb;
            border-radius: 13px;
            overflow: hidden;
        }

        .section-heading {
            padding: 8px 13px;
            background: #f6f8fb;
            border-bottom: 1px solid #d8e1eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #4a607b;
        }

        .section-body {
            padding: 14px 16px;
            white-space: pre-wrap;
            font-size: 15px;
            line-height: 1.6;
            color: #202d40;
        }

        .impression .section-body {
            font-weight: 650;
            color: #10213c;
        }

        .echo-clinical-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .echo-table-wrap {
            overflow-x: auto;
        }

        .echo-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .echo-table th,
        .echo-table td {
            padding: 7px 9px;
            border-right: 1px solid #e5ebf2;
            border-bottom: 1px solid #e5ebf2;
            text-align: left;
            vertical-align: top;
            font-size: 12px;
            line-height: 1.3;
        }

        .echo-table th:last-child,
        .echo-table td:last-child {
            border-right: none;
        }

        .echo-table tr:last-child td {
            border-bottom: none;
        }

        .echo-table th {
            background: #fbfcfe;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .echo-table td {
            color: #172033;
            font-weight: 600;
        }

        .echo-subgrid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .echo-finding {
            padding: 8px 11px;
            border-right: 1px solid #e5ebf2;
            border-bottom: 1px solid #e5ebf2;
        }

        .echo-finding:nth-child(2n) {
            border-right: none;
        }

        .echo-finding-label {
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #8a9bb1;
        }

        .echo-finding-value {
            margin-top: 2px;
            font-size: 11.5px;
            line-height: 1.35;
            color: #172033;
        }

        .report-footer {
            display: grid;
            grid-template-columns: 1fr 220px;
            gap: 30px;
            align-items: end;
            margin-top: 44px;
        }

        .reporter-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 24px;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 11px;
            line-height: 1.45;
            color: #7a8797;
        }

        .signature {
            padding-top: 28px;
            border-top: 1px solid #6b7d92;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #465870;
        }

        .screen-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        @page {
            size: A4 portrait;
            margin: 7mm;
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

            #imaging-report,
            #imaging-report * {
                visibility: visible;
            }

            #imaging-report {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .screen-only,
            .screen-actions,
            aside,
            nav,
            header {
                display: none !important;
            }

            .print-wrapper,
            .imaging-report-shell {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .imaging-report {
                width: 100% !important;
                min-height: 0 !important;
                height: auto !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                overflow: visible !important;
            }

            .report-inner {
                padding: 0 !important;
            }

            .report-brand {
                grid-template-columns: 56px minmax(0, 1fr) 190px !important;
                gap: 12px !important;
                padding-bottom: 9px !important;
                border-bottom-width: 2px !important;
            }

            .report-logo {
                width: 52px !important;
                height: 52px !important;
            }

            .hospital-name {
                font-size: 22px !important;
            }

            .hospital-address {
                margin-top: 2px !important;
                font-size: 12px !important;
            }

            .hospital-contact {
                margin-top: 2px !important;
                font-size: 9.5px !important;
            }

            .department-name {
                margin-top: 4px !important;
                font-size: 10.5px !important;
            }

            .report-heading-title {
                font-size: 19px !important;
            }

            .report-status {
                margin-top: 4px !important;
                padding: 2px 7px !important;
                font-size: 9px !important;
            }

            .report-time {
                margin-top: 3px !important;
                font-size: 10px !important;
            }

            .meta-panel {
                margin-top: 10px !important;
                border-radius: 8px !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .meta-panel-title {
                padding: 6px 9px !important;
                font-size: 9.5px !important;
            }

            .meta-item {
                padding: 6px 8px !important;
            }

            .meta-label {
                margin-bottom: 2px !important;
                font-size: 8.5px !important;
            }

            .meta-value {
                font-size: 10.5px !important;
                line-height: 1.2 !important;
            }

            .report-section {
                margin-top: 10px !important;
                border-radius: 8px !important;
                break-inside: auto !important;
                page-break-inside: auto !important;
            }

            .section-heading {
                padding: 6px 9px !important;
                font-size: 9.5px !important;
            }

            .section-body {
                padding: 9px 11px !important;
                font-size: 11.5px !important;
                line-height: 1.45 !important;
            }

            .impression {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .echo-clinical-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
            }

            .echo-table th,
            .echo-table td {
                padding: 4px 6px !important;
                font-size: 8.5px !important;
                line-height: 1.2 !important;
            }

            .echo-table th {
                font-size: 7.5px !important;
            }

            .echo-finding {
                padding: 5px 7px !important;
            }

            .echo-finding-label {
                font-size: 7.5px !important;
            }

            .echo-finding-value {
                font-size: 8.5px !important;
                line-height: 1.25 !important;
            }

            .report-footer {
                grid-template-columns: 1fr 180px !important;
                gap: 22px !important;
                margin-top: 32px !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .reporter-grid {
                gap: 6px 18px !important;
            }

            .footer-note {
                margin-top: 7px !important;
                font-size: 8.5px !important;
            }

            .signature {
                padding-top: 24px !important;
                font-size: 9.5px !important;
            }
        }
    </style>

    <div class="py-6 print-wrapper">
        <div class="imaging-report-shell px-4 sm:px-6 lg:px-8 print-wrapper">

            <section id="imaging-report" class="imaging-report">
                <div class="report-inner">

                    {{-- STANDARD TCH LETTERHEAD --}}
                    <div class="report-brand">
                        <img
                            src="{{ asset('images/TCH_favicon.png') }}"
                            alt="Tura Christian Hospital"
                            class="report-logo"
                        >

                        <div>
                            <h1 class="hospital-name">
                                Tura Christian Hospital
                            </h1>

                            <div class="hospital-address">
                                Tura, West Garo Hills, Meghalaya
                            </div>

                            <div class="hospital-contact">
                                Email: tchcare@yahoo.com
                                &nbsp;•&nbsp;
                                Website: www.turachristianhospital.org
                            </div>

                            <div class="department-name">
                                {{ $departmentTitle }}
                            </div>
                        </div>

                        <div class="report-heading">
                            <div class="report-heading-title">
                                {{ $reportTitle }}
                            </div>

                            <div class="report-status">
                                {{ $reportStatus }} Report
                            </div>

                            <div class="report-time">
                                {{ $report?->entered_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    {{-- PATIENT + IMAGING STUDY DETAILS --}}
                    <div class="meta-panel">
                        <div class="meta-panel-title">
                            Patient &amp; Imaging Study Details
                        </div>

                        <div class="meta-grid">
                            <div class="meta-item">
                                <div class="meta-label">Patient Name</div>
                                <div class="meta-value">
                                    {{ $patient?->full_name ?? '—' }}
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-label">UHID</div>
                                <div class="meta-value">
                                    {{ $patient?->uhid ?? '—' }}
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-label">MRD</div>
                                <div class="meta-value">
                                    {{ $patient?->mrd_number ?: '—' }}
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-label">Age / Sex</div>
                                <div class="meta-value">
                                    @if ($patient?->age !== null)
                                        {{ $patient->age }} yrs
                                    @else
                                        —
                                    @endif
                                    /
                                    {{ $patient?->sex ?: '—' }}
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-label">Imaging Study</div>
                                <div class="meta-value">
                                    {{ $serviceOrderItem->service_name }}
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-label">Order No.</div>
                                <div class="meta-value">
                                    {{ $order?->order_no ?? '—' }}
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-label">Department</div>
                                <div class="meta-value">
                                    {{ $departmentName }}
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-label">Referring Doctor</div>
                                <div class="meta-value">
                                    {{ $doctorName }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($isEcho)
                        {{-- ECHOCARDIOGRAPHY: CLINICAL DATA --}}
                        <div class="report-section">
                            <div class="section-heading">Clinical Data</div>
                            <div class="echo-clinical-grid">
                                @foreach ([
                                    ['BP', $displayEcho('clinical.bp')],
                                    ['HR', $displayEcho('clinical.hr', 'bpm')],
                                    ['Height', $displayEcho('clinical.height', 'cm')],
                                    ['Weight', $displayEcho('clinical.weight', 'kg')],
                                    ['BSA', $displayEcho('clinical.bsa', 'm²')],
                                    ['Indication', $displayEcho('clinical.indication')],
                                ] as [$label, $value])
                                    <div class="meta-item">
                                        <div class="meta-label">{{ $label }}</div>
                                        <div class="meta-value">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ECHOCARDIOGRAPHY: M-MODE / 2D MEASUREMENTS --}}
                        <div class="report-section">
                            <div class="section-heading">M-Mode / 2D Measurements</div>
                            <div class="echo-table-wrap">
                                <table class="echo-table">
                                    <thead>
                                        <tr>
                                            <th>Measurement</th>
                                            <th>Value</th>
                                            <th>Reference</th>
                                            <th>Measurement</th>
                                            <th>Value</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ([
                                            [['Aortic Root', 'm_mode.m_aod', 'mm', '20–37'], ['Left Atrium', 'm_mode.m_la', 'mm', '27–40']],
                                            [['LA/Ao Ratio', 'm_mode.m_la_ao', '', '<1.3'], ['LVID Diastole', 'm_mode.m_lvidd', 'mm', '35–55']],
                                            [['LVID Systole', 'm_mode.m_lvids', 'mm', '25–35'], ['IVS Thickness', 'm_mode.m_ivsd', 'mm', '6–11']],
                                            [['Post. Wall Thickness', 'm_mode.m_pwd', 'mm', '6–11'], ['Ejection Fraction', 'm_mode.m_ef', '%', '55–70']],
                                            [['Fractional Shortening', 'm_mode.m_fs', '%', '25–45'], ['RV Dimension', 'm_mode.m_rv', 'mm', '15–30']],
                                            [['TAPSE', 'm_mode.m_tapse', 'mm', '>17'], ['IVC Diameter', 'm_mode.m_ivc', 'cm', '<2.1']],
                                            [['Est. RA Pressure', 'm_mode.m_rap', 'mmHg', '3–15'], ['', '', '', '']],
                                        ] as $pair)
                                            <tr>
                                                @foreach ($pair as [$label, $path, $unit, $reference])
                                                    @if ($label !== '')
                                                        <td>{{ $label }}</td>
                                                        <td>{{ $displayEcho($path, $unit) }}</td>
                                                        <td>{{ $reference }}</td>
                                                    @else
                                                        <td>—</td><td>—</td><td>—</td>
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ECHOCARDIOGRAPHY: DOPPLER --}}
                        <div class="report-section">
                            <div class="section-heading">Doppler Measurements</div>
                            <div class="echo-table-wrap">
                                <table class="echo-table">
                                    <thead>
                                        <tr>
                                            <th>Measurement</th>
                                            <th>Value</th>
                                            <th>Reference</th>
                                            <th>Measurement</th>
                                            <th>Value</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ([
                                            [['Mitral E Velocity', 'doppler.d_e', 'm/s', '0.6–1.3'], ['Mitral A Velocity', 'doppler.d_a', 'm/s', '0.3–0.7']],
                                            [['E/A Ratio', 'doppler.d_ea', '', '0.8–2.0'], ['Deceleration Time', 'doppler.d_dt', 'ms', '160–240']],
                                            [["Septal e′", 'doppler.d_sepe', 'cm/s', '>7'], ["Lateral e′", 'doppler.d_late', 'cm/s', '>10']],
                                            [["E/e′ Ratio", 'doppler.d_ee', '', '<14'], ['TR Velocity', 'doppler.d_trv', 'm/s', '<2.8']],
                                            [['TR Gradient', 'doppler.d_trg', 'mmHg', '<30'], ['Estimated RVSP', 'doppler.d_rvsp', 'mmHg', '<35']],
                                            [['AV Peak Velocity', 'doppler.d_avv', 'm/s', '<1.7'], ['AV Peak Gradient', 'doppler.d_avg', 'mmHg', '<15']],
                                            [['LVOT Diameter', 'doppler.d_lvotd', 'cm', '1.8–2.2'], ['LVOT VTI', 'doppler.d_lvotvti', 'cm', '18–22']],
                                            [['Stroke Volume', 'doppler.d_sv', 'ml', '60–100'], ['Cardiac Output', 'doppler.d_co', 'L/min', '4.0–8.0']],
                                        ] as $pair)
                                            <tr>
                                                @foreach ($pair as [$label, $path, $unit, $reference])
                                                    <td>{{ $label }}</td>
                                                    <td>{{ $displayEcho($path, $unit) }}</td>
                                                    <td>{{ $reference }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ECHOCARDIOGRAPHY: STRUCTURED 2D / VALVE FINDINGS --}}
                        <div class="report-section">
                            <div class="section-heading">2D &amp; Valve Assessment</div>
                            <div class="echo-subgrid">
                                @foreach ([
                                    ['LV Size', 'findings.fLvSize'],
                                    ['LV Systolic Function', 'findings.fLvSys'],
                                    ['RWMA', 'findings.fRwma'],
                                    ['Diastolic Function', 'findings.fLvDia'],
                                    ['RV', 'findings.fRv'],
                                    ['LA', 'findings.fLa'],
                                    ['RA', 'findings.fRa'],
                                    ['Pericardial Effusion', 'findings.fPe'],
                                    ['Other Findings', 'findings.fOther'],
                                ] as [$label, $path])
                                    <div class="echo-finding">
                                        <div class="echo-finding-label">{{ $label }}</div>
                                        <div class="echo-finding-value">{{ $displayEcho($path) }}</div>
                                    </div>
                                @endforeach

                                @foreach ([
                                    ['Mitral Valve', 'valves.mit'],
                                    ['Aortic Valve', 'valves.aor'],
                                    ['Tricuspid Valve', 'valves.tri'],
                                    ['Pulmonary Valve', 'valves.pul'],
                                ] as [$label, $path])
                                    @php
                                        $structure = data_get($structured, $path . '.structure');
                                        $stenosis = data_get($structured, $path . '.stenosis');
                                        $regurgitation = data_get($structured, $path . '.regurgitation');

                                        $valveParts = array_values(array_filter([
                                            $structure,
                                            $stenosis !== null && $stenosis !== '' ? 'Stenosis: ' . $stenosis : null,
                                            $regurgitation !== null && $regurgitation !== '' ? 'Regurgitation: ' . $regurgitation : null,
                                        ]));

                                        $valveText = $valveParts ? implode('; ', $valveParts) : '—';
                                    @endphp

                                    <div class="echo-finding">
                                        <div class="echo-finding-label">{{ $label }}</div>
                                        <div class="echo-finding-value">{{ $valveText }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ECHOCARDIOGRAPHY: NARRATIVE FINDINGS --}}
                        <div class="report-section">
                            <div class="section-heading">Findings</div>
                            <div class="section-body">{{ $report?->findings ?: '—' }}</div>
                        </div>

                        {{-- ECHOCARDIOGRAPHY: IMPRESSION --}}
                        <div class="report-section impression">
                            <div class="section-heading">Impression</div>
                            <div class="section-body">{{ $report?->impression ?: '—' }}</div>
                        </div>
                    @else
                        {{-- STANDARD IMAGING FINDINGS --}}
                        <div class="report-section">
                            <div class="section-heading">Findings</div>
                            <div class="section-body">{{ $report?->findings ?: '—' }}</div>
                        </div>

                        {{-- STANDARD IMAGING IMPRESSION --}}
                        <div class="report-section impression">
                            <div class="section-heading">Impression</div>
                            <div class="section-body">{{ $report?->impression ?: '—' }}</div>
                        </div>
                    @endif

                    {{-- REPORT DETAILS + SIGNATURE --}}
                    <div class="report-footer">
                        <div>
                            <div class="reporter-grid">
                                <div>
                                    <div class="meta-label">Reported By</div>
                                    <div class="meta-value">
                                        {{ $report?->enteredBy?->name ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="meta-label">Reported At</div>
                                    <div class="meta-value">
                                        {{ $report?->entered_at?->format('d M Y, h:i A') ?? '—' }}
                                    </div>
                                </div>

                                @if ($report?->verified_at)
                                    <div>
                                        <div class="meta-label">Verified By</div>
                                        <div class="meta-value">
                                            {{ $report?->verifiedBy?->name ?? '—' }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="meta-label">Verified At</div>
                                        <div class="meta-value">
                                            {{ $report?->verified_at?->format('d M Y, h:i A') ?? '—' }}
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="footer-note">
                                Generated electronically from TCH Hospital ERP.
                                {{ $isEcho ? 'Echocardiographic findings should be interpreted in the appropriate clinical context.' : 'Imaging findings should be interpreted in the appropriate clinical context.' }}
                            </div>
                        </div>

                        <div class="signature">
                            {{ $isEcho ? 'Authorized Echocardiography Signatory' : 'Authorized Radiology Signatory' }}
                        </div>
                    </div>

                </div>

                <div class="screen-actions screen-only">
                    <a
                        href="{{ route('imaging.index') }}"
                        class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Back to Imaging
                    </a>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        {{ $isEcho ? 'Print Echo Report' : 'Print Imaging Report' }}
                    </button>
                </div>
            </section>

        </div>
    </div>

    @if (request()->boolean('print'))
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif

</x-app-layout>
