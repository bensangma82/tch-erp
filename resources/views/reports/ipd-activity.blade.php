<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    IPD Activity Report
                </h1>
                <p class="text-sm text-gray-500">
                    Tura Christian Hospital | {{ $from }} to {{ $to }}
                </p>
            </div>

            <button type="button" onclick="window.print()"
                class="rounded-lg bg-slate-800 px-4 py-2 text-white print:hidden">
                Print Report
            </button>
        </div>

        <form method="GET"
            class="flex flex-wrap items-end gap-4 rounded-xl border bg-white p-4 print:hidden">
            <div>
                <label class="block text-sm font-medium">From</label>
                <input type="date" name="from" value="{{ $from }}"
                    class="rounded-lg border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-medium">To</label>
                <input type="date" name="to" value="{{ $to }}"
                    class="rounded-lg border-gray-300">
            </div>
            <button class="rounded-lg bg-blue-700 px-4 py-2 text-white">
                Generate Report
            </button>
        </form>

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 p-4 text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                'admissions' => 'Admissions',
                'discharges' => 'Discharges',
                'referrals' => 'Referrals',
                'deaths' => 'Deaths',
                'inpatients' => 'Inpatient Census'
            ] as $key => $label)
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-gray-500">{{ $label }}</div>
                    <div class="mt-2 text-3xl font-bold">
                        {{ $totals[$key] }}
                    </div>
                </div>
            @endforeach
        </div>

        @foreach ([
            'Doctor-wise IPD' => $byDoctor,
            'Department-wise IPD' => $byDepartment
        ] as $heading => $groups)

            <section class="space-y-4">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ $heading }}
                </h2>

                <div class="overflow-x-auto rounded-xl border bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-left">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Admissions</th>
                                <th class="px-4 py-3">Discharges</th>
                                <th class="px-4 py-3">Referrals</th>
                                <th class="px-4 py-3">Deaths</th>
                                <th class="px-4 py-3">Census</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($groups as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium">
                                        {{ $row['name'] }}
                                    </td>
                                    @foreach ([
                                        'admissions', 'discharges',
                                        'referrals', 'deaths', 'inpatients'
                                    ] as $key)
                                        <td class="px-4 py-3">
                                            {{ $row[$key] }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="px-4 py-8 text-center text-gray-500">
                                        No IPD activity found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ($groups as $row)
                    @if ($row['patients']->isNotEmpty())
                        <details class="rounded-xl border bg-white p-4 print:break-inside-avoid">
                            <summary class="cursor-pointer font-semibold">
                                {{ $row['name'] }} — Patient Register
                                ({{ $row['patients']->count() }})
                            </summary>

                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100 text-left">
                                        <tr>
                                            <th class="px-3 py-2">Patient</th>
                                            <th class="px-3 py-2">Admission No.</th>
                                            <th class="px-3 py-2">Admitted</th>
                                            <th class="px-3 py-2">Closed</th>
                                            <th class="px-3 py-2">Status</th>
                                            <th class="px-3 py-2">LOS (days)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($row['patients'] as $a)
                                            @php
                                                $cutoff = \Illuminate\Support\Carbon::parse($to)->endOfDay();
                                                $actualExit = $a->closed_at ?? $a->discharged_at;
                                                $exitAt = $actualExit && $actualExit->lt($cutoff)
                                                    ? $actualExit
                                                    : $cutoff;
                                                $los = $a->admitted_at
                                                    ? max(0, (int) floor(
                                                        $a->admitted_at->diffInHours($exitAt, false) / 24
                                                    ))
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-2">
                                                    {{ $a->patient?->full_name ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $a->admission_no }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $a->admitted_at?->format('d M Y') }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ ($a->closed_at ?? $a->discharged_at)?->format('d M Y') ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ ucfirst($a->status) }}
                                                </td>
                                                <td class="px-3 py-2">{{ $los }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @endif
                @endforeach
            </section>

        @endforeach

    </div>
</x-app-layout>
