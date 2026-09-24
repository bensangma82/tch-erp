<x-app-layout>
    <x-slot name="header">
        <div class="screen-only flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Combined Laboratory Report</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Completed laboratory results from a single order.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('laboratory.index') }}"
                   class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Back to Laboratory
                </a>

                <button type="button"
                        onclick="window.print()"
                        class="inline-flex rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Print Combined Report
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $patient = $order?->patient;
        $encounter = $order?->encounter;
        $admission = $order?->admission;

        $departmentName = $admission?->department?->name
            ?? $encounter?->department?->name
            ?? '—';

        $doctorName = $admission?->consultant?->full_name
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
        .group-report-shell{max-width:980px;margin:0 auto}
        .group-report{background:#fff;border:1px solid #dbe3ee;border-radius:18px;box-shadow:0 18px 45px rgba(15,23,42,.08);overflow:hidden;color:#0f172a;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
        .group-inner{padding:28px 32px 24px}
        .brand{display:grid;grid-template-columns:78px minmax(0,1fr) auto;align-items:center;gap:18px;padding-bottom:18px;border-bottom:3px solid #10213c}
        .logo{width:72px;height:72px;object-fit:contain}
        .hospital{margin:0;font-size:24pt;line-height:1.05;font-weight:800;color:#10213c}
        .sub{margin-top:5px;font-size:12.75pt;color:#64748b}
        .dept{margin-top:6px;font-size:12pt;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#334155}
        .heading{text-align:right}.heading-title{font-size:15.75pt;font-weight:800;color:#10213c}.heading-meta{margin-top:5px;font-size:11.25pt;color:#64748b}
        .meta{margin-top:18px;border:1px solid #dfe6ee;border-radius:12px;overflow:hidden}
        .meta-title{padding:7px 12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:10.5pt;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#64748b}
        .meta-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:0}
        .meta-cell{padding:9px 12px;border-right:1px solid #edf2f7;border-bottom:1px solid #edf2f7}
        .label{font-size:9.75pt;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8}
        .value{margin-top:2px;font-size:12pt;font-weight:650;color:#1e293b}
        .test{margin-top:18px;border:1px solid #dfe6ee;border-radius:12px;overflow:hidden;break-inside:avoid}
        .test-head{display:flex;justify-content:space-between;gap:16px;padding:9px 12px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
        .test-name{font-size:13.5pt;font-weight:800;color:#10213c}.test-code{font-size:10.5pt;color:#64748b}
        .sample{font-size:10.5pt;text-align:right;color:#64748b}
        table.results{width:100%;border-collapse:collapse}
        .results th{padding:7px 10px;background:#fbfdff;border-bottom:1px solid #e2e8f0;text-align:left;font-size:9.75pt;text-transform:uppercase;letter-spacing:.06em;color:#64748b}
        .results td{padding:7px 10px;border-bottom:1px solid #edf2f7;font-size:11.25pt;color:#334155;vertical-align:top}
        .results tr:last-child td{border-bottom:0}
        .result-value{font-weight:800;color:#0f172a}
        .abnormal{font-weight:800;color:#a16207}.critical{font-weight:800;color:#b91c1c}
        .comment{padding:8px 10px;background:#fbfcfe;border-top:1px solid #edf2f7;font-size:10.5pt;line-height:1.4;color:#475569;white-space:pre-wrap}
        .footer{display:grid;grid-template-columns:1fr 210px;gap:26px;align-items:end;margin-top:48px}
        .note{font-size:10.5pt;line-height:1.45;color:#7a8797}.signature{padding-top:22px;border-top:1px solid #6b7d92;text-align:center;font-size:11.25pt;font-weight:700;color:#465870}
        @page{size:A4 portrait;margin:7mm}
        @media print{
            html,body{margin:0!important;padding:0!important;background:#fff!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
            body *{visibility:hidden}
            #combined-laboratory-report,#combined-laboratory-report *{visibility:visible}
            #combined-laboratory-report{position:absolute;left:0;top:0;width:100%}
            .screen-only,aside,nav,header{display:none!important}
            .group-report-shell,.group-report{width:100%!important;max-width:none!important;margin:0!important;padding:0!important;box-shadow:none!important;border:0!important;border-radius:0!important}
            .group-inner{padding:0!important}
            .brand{grid-template-columns:58px minmax(0,1fr) auto!important;gap:12px!important;padding-bottom:9px!important}
            .logo{width:52px!important;height:52px!important}
            .hospital{font-size:18pt!important}.sub{font-size:9.75pt!important}.dept{font-size:9pt!important}.heading-title{font-size:12pt!important}.heading-meta{font-size:9pt!important}
            .meta{margin-top:10px!important}.meta-cell{padding:5px 7px!important}.label{font-size:7.875pt!important}.value{font-size:9.375pt!important}
            .test{margin-top:9px!important;border-radius:6px!important;break-inside:avoid!important;page-break-inside:avoid!important}
            .test-head{padding:5px 8px!important}.test-name{font-size:10.5pt!important}.test-code,.sample{font-size:8.25pt!important}
            .results th{padding:4px 6px!important;font-size:7.875pt!important}.results td{padding:4px 6px!important;font-size:9pt!important;line-height:1.15!important}
            .comment{padding:5px 7px!important;font-size:8.25pt!important}
            .footer{margin-top:38px!important;grid-template-columns:1fr 170px!important}.note{font-size:7.875pt!important}.signature{padding-top:15px!important;font-size:8.25pt!important}
        }
    </style>

    <div class="py-6">
        <div class="group-report-shell px-4 sm:px-6 lg:px-8">
            <section id="combined-laboratory-report" class="group-report">
                <div class="group-inner">

                    <div class="brand">
                        <img src="{{ asset('images/TCH_favicon.png') }}" alt="Tura Christian Hospital" class="logo">

                        <div>
                            <h1 class="hospital">Tura Christian Hospital</h1>
                            <div class="sub">Tura, West Garo Hills, Meghalaya</div>
                            <div class="sub">Email: tchcare@yahoo.com · Website: www.turachristianhospital.org</div>
                            <div class="dept">Department of Laboratory Medicine</div>
                        </div>

                        <div class="heading">
                            <div class="heading-title">Combined Laboratory Report</div>
                            <div class="heading-meta">{{ $items->count() }} completed {{ Str::plural('test', $items->count()) }}</div>
                            <div class="heading-meta">{{ $latestReportedAt?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>

                    <div class="meta">
                        <div class="meta-title">Patient & Order Details</div>
                        <div class="meta-grid">
                            <div class="meta-cell">
                                <div class="label">Patient Name</div>
                                <div class="value">{{ $patient?->full_name ?? '—' }}</div>
                            </div>
                            <div class="meta-cell">
                                <div class="label">UHID</div>
                                <div class="value">{{ $patient?->uhid ?? '—' }}</div>
                            </div>
                            <div class="meta-cell">
                                <div class="label">MRD</div>
                                <div class="value">{{ $patient?->mrd_number ?: '—' }}</div>
                            </div>
                            <div class="meta-cell">
                                <div class="label">Age / Sex</div>
                                <div class="value">{{ $patient?->age !== null ? $patient->age . ' yrs' : '—' }} / {{ $patient?->sex ?: '—' }}</div>
                            </div>

                            <div class="meta-cell">
                                <div class="label">Order No.</div>
                                <div class="value">{{ $order?->order_no ?? '—' }}</div>
                            </div>
                            <div class="meta-cell">
                                <div class="label">Department</div>
                                <div class="value">{{ $departmentName }}</div>
                            </div>
                            <div class="meta-cell">
                                <div class="label">Referring Doctor</div>
                                <div class="value">{{ $doctorName }}</div>
                            </div>
                            <div class="meta-cell">
                                <div class="label">Order Date</div>
                                <div class="value">{{ $order?->ordered_at?->format('d M Y, h:i A') ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    @foreach ($items as $serviceOrderItem)
                        @php
                            $result = $serviceOrderItem->diagnosticResult;
                            $sample = $serviceOrderItem->diagnosticSample;
                            $resultItems = $result?->items ?? collect();
                        @endphp

                        <section class="test">
                            <div class="test-head">
                                <div>
                                    <div class="test-name">{{ $serviceOrderItem->service_name }}</div>
                                    <div class="test-code">{{ $serviceOrderItem->service_code ?: '—' }}</div>
                                </div>

                                <div class="sample">
                                    @if ($serviceOrderItem->requires_sample)
                                        Sample: {{ $sample?->sample_no ?? '—' }}
                                        @if ($sample?->specimen_type)
                                            · {{ $sample->specimen_type }}
                                        @endif
                                        <br>
                                    @endif
                                    Reported: {{ $result?->entered_at?->format('d M Y, h:i A') ?? '—' }}
                                </div>
                            </div>

                            @if ($resultItems->count())
                                <table class="results">
                                    <thead>
                                        <tr>
                                            <th style="width:31%">Parameter</th>
                                            <th style="width:18%">Result</th>
                                            <th style="width:15%">Unit</th>
                                            <th style="width:25%">Reference Range</th>
                                            <th style="width:11%;text-align:center">Flag</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($resultItems as $resultItem)
                                            @php
                                                $flag = $resultItem->flag;

                                                if (! $flag) {
                                                    $rawResult = trim(str_replace(',', '', (string) $resultItem->result_value));
                                                    $range = trim(str_replace(['–', '—', '−'], '-', (string) $resultItem->reference_range));

                                                    if (preg_match('/^-?\d+(?:\.\d+)?$/', $rawResult)) {
                                                        $numericResult = (float) $rawResult;
                                                        $matches = [];

                                                        if (preg_match('/^<=\s*(-?\d+(?:\.\d+)?)$/', $range, $matches)) {
                                                            $flag = $numericResult > (float) $matches[1] ? 'high' : null;
                                                        } elseif (preg_match('/^<\s*(-?\d+(?:\.\d+)?)$/', $range, $matches)) {
                                                            $flag = $numericResult >= (float) $matches[1] ? 'high' : null;
                                                        } elseif (preg_match('/^>=\s*(-?\d+(?:\.\d+)?)$/', $range, $matches)) {
                                                            $flag = $numericResult < (float) $matches[1] ? 'low' : null;
                                                        } elseif (preg_match('/^>\s*(-?\d+(?:\.\d+)?)$/', $range, $matches)) {
                                                            $flag = $numericResult <= (float) $matches[1] ? 'low' : null;
                                                        } elseif (preg_match('/^(-?\d+(?:\.\d+)?)\s*-\s*(-?\d+(?:\.\d+)?)$/', $range, $matches)) {
                                                            $low = (float) $matches[1];
                                                            $high = (float) $matches[2];

                                                            if ($low > $high) {
                                                                [$low, $high] = [$high, $low];
                                                            }

                                                            $flag = $numericResult < $low
                                                                ? 'low'
                                                                : ($numericResult > $high ? 'high' : null);
                                                        }
                                                    }
                                                }
                                                $flagLabel = match ($flag) {
                                                    'low' => 'LOW',
                                                    'high' => 'HIGH',
                                                    'critical_low' => 'CRITICAL LOW',
                                                    'critical_high' => 'CRITICAL HIGH',
                                                    'abnormal' => 'ABNORMAL',
                                                    default => '—',
                                                };
                                                $valueClass = match ($flag) {
                                                    'critical_low', 'critical_high' => 'critical',
                                                    'low', 'high', 'abnormal' => 'abnormal',
                                                    default => '',
                                                };
                                            @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $resultItem->parameter_name }}</strong>
                                                    @if ($resultItem->remarks)
                                                        <div style="margin-top:2px;color:#7b8ba0;font-size:9.75pt">{{ $resultItem->remarks }}</div>
                                                    @endif
                                                </td>
                                                <td><span class="result-value {{ $valueClass }}">{{ $resultItem->result_value ?? '—' }}</span></td>
                                                <td>{{ $resultItem->unit ?: '—' }}</td>
                                                <td>{{ $resultItem->reference_range ?: '—' }}</td>
                                                <td style="text-align:center">{{ $flagLabel }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="comment">No structured result parameters are available.</div>
                            @endif

                            @if ($result?->result_text)
                                <div class="comment"><strong>Comment:</strong> {{ $result->result_text }}</div>
                            @endif
                        </section>
                    @endforeach

                    <div class="footer">
                        <div class="note">
                            This report contains only finalized/verified laboratory investigations from order
                            {{ $order?->order_no ?? '—' }}. Investigations still awaiting sample, in process, or in draft status are not included.
                        </div>
                        <div class="signature">Authorized Laboratory Signatory</div>
                    </div>
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
