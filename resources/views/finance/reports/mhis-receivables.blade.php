<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    MHIS Receivables Reconciliation
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Approved claims, reimbursements, TDS, deductions and GL 1210 reconciliation.
                </p>
            </div>

            <a href="{{ route('finance.reports.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium">
                Back to Finance Reports
            </a>
        </div>

        @php
            $totals = [
                'approved' => 0,
                'gross' => 0,
                'tds' => 0,
                'net_bank' => 0,
                'adjusted' => 0,
                'outstanding' => 0,
                'gl_balance' => 0,
                'difference' => 0,
            ];

            foreach ($rows as $row) {
                foreach ($totals as $key => $value) {
                    $totals[$key] += $row[$key];
                }
            }
        @endphp

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            @foreach ([
                'Approved Claims' => $totals['approved'],
                'Gross Reimbursements' => $totals['gross'],
                'Outstanding Receivables' => $totals['outstanding'],
            ] as $label => $amount)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        ₹{{ number_format($amount, 2) }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-[1300px] w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-600">
                    <tr>
                        <th class="px-3 py-3">Claim / Admission</th>
                        <th class="px-3 py-3">Status</th>
                        @foreach ([
                            'Approved',
                            'Gross Received',
                            'TDS',
                            'Net Bank',
                            'Adjustments',
                            'Outstanding',
                            'GL 1210',
                            'Difference',
                        ] as $heading)
                            <th class="px-3 py-3 text-right">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-3 py-3">
                                <div class="font-semibold">
                                    Claim #{{ $row['claim']->id }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $row['claim']->admission?->admission_no ?? '—' }}
                                </div>
                            </td>

                            <td class="px-3 py-3">
                                {{ ucfirst($row['claim']->status) }}
                            </td>

                            @foreach ([
                                'approved',
                                'gross',
                                'tds',
                                'net_bank',
                                'adjusted',
                                'outstanding',
                                'gl_balance',
                            ] as $key)
                                <td class="px-3 py-3 text-right tabular-nums">
                                    ₹{{ number_format($row[$key], 2) }}
                                </td>
                            @endforeach

                            <td class="px-3 py-3 text-right font-semibold">
                                @if (abs($row['difference']) < 0.005)
                                    <span class="text-emerald-700">₹0.00</span>
                                @else
                                    <span class="text-red-700">
                                        ₹{{ number_format($row['difference'], 2) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-slate-500">
                                No approved MHIS claims found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="bg-slate-50 font-semibold">
                    <tr>
                        <td colspan="2" class="px-3 py-3">TOTAL</td>
                        @foreach ([
                            'approved',
                            'gross',
                            'tds',
                            'net_bank',
                            'adjusted',
                            'outstanding',
                            'gl_balance',
                            'difference',
                        ] as $key)
                            <td class="px-3 py-3 text-right tabular-nums">
                                ₹{{ number_format($totals[$key], 2) }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Outstanding = Approved − Gross Reimbursements − Posted Adjustments.
            Reimbursement TDS is included in gross reimbursements and is not deducted twice.
            Historical settlement adjustments are retained.
        </p>
    </div>
</x-app-layout>
