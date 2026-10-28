<x-app-layout>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900">
                Activity Reports
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                Tura Christian Hospital — Clinical Management Reporting
            </p>
        </div>

        @php
            $reports = [
                [
                    'title' => 'Doctor-wise OPD Report',
                    'description' => 'Registered patients, consultations completed, pending consultations and doctor-wise patient registers.',
                    'route' => 'reports.doctor-opd',
                ],
                [
                    'title' => 'IPD Activity Report',
                    'description' => 'Doctor-wise and department-wise admissions, discharges, referrals, deaths and inpatient census.',
                    'route' => 'reports.ipd-activity',
                ],
                [
                    'title' => 'Laboratory Activity Report',
                    'description' => 'Laboratory test volumes, test types, completion status, consultant-wise and department-wise utilisation.',
                    'route' => 'reports.laboratory-activity',
                ],
                [
                    'title' => 'Ultrasound, X-ray & ECG Report',
                    'description' => 'Diagnostic examination volumes, completion status and OPD/IPD utilisation.',
                    'route' => 'reports.diagnostic-activity',
                ],
            ];
        @endphp

        <div class="grid gap-5 md:grid-cols-2">
            @foreach ($reports as $report)
                <a href="{{ route($report['route']) }}"
                   class="block rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-300 hover:shadow-md">

                    <h2 class="text-lg font-bold text-slate-900">
                        {{ $report['title'] }}
                    </h2>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        {{ $report['description'] }}
                    </p>

                    <div class="mt-5 text-sm font-semibold text-indigo-700">
                        Open Report &rarr;
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
