<x-app-layout>
    @php
        $reports = [
            [
                'title' => 'OPD Activity',
                'subtitle' => 'Outpatient Services',
                'description' => 'Doctor-wise registrations, completed consultations, pending patients and consultation registers.',
                'route' => 'reports.doctor-opd',
                'icon' => 'opd',
                'color' => 'blue',
                'details' => ['Doctor-wise', 'Consultations', 'Patient Register'],
            ],
            [
                'title' => 'IPD Activity',
                'subtitle' => 'Inpatient Services',
                'description' => 'Monitor admissions, discharges, referrals, mortality and inpatient census across departments.',
                'route' => 'reports.ipd-activity',
                'icon' => 'ipd',
                'color' => 'emerald',
                'details' => ['Admissions', 'Discharges', 'Census'],
            ],
            [
                'title' => 'Laboratory Activity',
                'subtitle' => 'Pathology & Laboratory',
                'description' => 'Review test volumes, completion status and consultant-wise or department-wise utilisation.',
                'route' => 'reports.laboratory-activity',
                'icon' => 'lab',
                'color' => 'violet',
                'details' => ['Test Volumes', 'Status', 'Utilisation'],
            ],
            [
                'title' => 'Diagnostic Activity',
                'subtitle' => 'Imaging & ECG',
                'description' => 'Analyse ultrasound, X-ray and ECG examinations, completion status and OPD/IPD utilisation.',
                'route' => 'reports.diagnostic-activity',
                'icon' => 'diagnostic',
                'color' => 'amber',
                'details' => ['Ultrasound', 'X-ray', 'ECG'],
            ],
        ];

        $styles = [
            'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'violet' => 'bg-violet-50 text-violet-700 ring-violet-100',
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        ];
    @endphp

    <div class="min-h-screen bg-slate-50/80">
        <div class="mx-auto max-w-7xl space-y-7 px-4 py-8 sm:px-6 lg:px-8">

            {{-- Page heading --}}
            <div class="flex flex-col gap-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 3v18h18M7 16v-4m5 4V7m5 9v-6M6 7l5-3 4 3 5-3"/>
                        </svg>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-600">
                            Tura Christian Hospital
                        </p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                            Clinical Activity Reports
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            A central workspace for reviewing patient activity, clinical services,
                            diagnostic utilisation and departmental performance.
                        </p>
                    </div>
                </div>

                <div class="self-start rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-3 text-center sm:self-center">
                    <p class="text-2xl font-bold text-indigo-700">{{ count($reports) }}</p>
                    <p class="text-xs font-medium text-indigo-600">Report Modules</p>
                </div>
            </div>

            {{-- Reports heading --}}
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Available Reports</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Select a reporting module to view detailed activity and performance.
                    </p>
                </div>
                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-500">
                    Clinical Management
                </span>
            </div>

            {{-- Report cards --}}
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($reports as $report)
                    <a href="{{ route($report['route']) }}"
                       class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl ring-1 {{ $styles[$report['color']] }}">
                                @if ($report['icon'] === 'opd')
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9 3h6m-3 0v5m-7 2v3a7 7 0 0 0 14 0v-3M5 10a3 3 0 0 1 3-3m11 3a3 3 0 0 0-3-3M12 20v1m7-7h2m-2 0a2 2 0 1 0 0 4"/>
                                    </svg>
                                @elseif ($report['icon'] === 'ipd')
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 20V8m0 9h18M6 13h4a2 2 0 0 1 2 2v2m0-4h6a3 3 0 0 1 3 3v4M5 7h4v4H5z"/>
                                    </svg>
                                @elseif ($report['icon'] === 'lab')
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M9 3h6m-5 0v7L5 19a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3M8 16h8"/>
                                    </svg>
                                @else
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3 12h4l3-7 4 14 3-7h4M4 4h16v16H4z"/>
                                    </svg>
                                @endif
                            </div>

                            <svg class="h-5 w-5 text-slate-300 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-indigo-600"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 17 17 7M8 7h9v9"/>
                            </svg>
                        </div>

                        <div class="mt-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                {{ $report['subtitle'] }}
                            </p>
                            <h3 class="mt-2 text-xl font-bold text-slate-900 transition group-hover:text-indigo-700">
                                {{ $report['title'] }}
                            </h3>
                            <p class="mt-3 min-h-[72px] text-sm leading-6 text-slate-600">
                                {{ $report['description'] }}
                            </p>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($report['details'] as $detail)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                    {{ $detail }}
                                </span>
                            @endforeach
                        </div>

                        <div class="mt-auto pt-6">
                            <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                                <span class="text-sm font-semibold text-indigo-700">
                                    Open Report
                                </span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 transition group-hover:bg-indigo-600 group-hover:text-white">
                                    &rarr;
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <p class="text-center text-xs text-slate-400">
                TCH ERP &middot; Clinical Management Reporting
            </p>

        </div>
    </div>
</x-app-layout>
