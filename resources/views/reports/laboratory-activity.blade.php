<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6">
        <div class="print:hidden">
            <a href="{{ route('reports.activity.index') }}"
               class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                ← Back to Activity Reports
            </a>
        </div>


        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Laboratory Activity Report
                </h1>
                <p class="text-sm text-gray-500">
                    Tura Christian Hospital | {{ $from }} to {{ $to }}
                </p>
            </div>
            <button onclick="window.print()" type="button"
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
            <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                'tests' => 'Total Tests',
                'types' => 'Test Types',
                'completed' => 'Completed',
                'in_process' => 'In Process',
                'ordered' => 'Ordered'
            ] as $key => $label)
                <div class="rounded-xl border bg-white p-4">
                    <div class="text-sm text-gray-500">{{ $label }}</div>
                    <div class="mt-2 text-3xl font-bold">
                        {{ number_format($totals[$key]) }}
                    </div>
                </div>
            @endforeach
        </div>

        @foreach ([
            'Test-wise Laboratory Activity' => $byTest,
            'Doctor-wise Laboratory Orders' => $byDoctor,
            'Department-wise Laboratory Activity' => $byDepartment,
            'OPD / IPD Laboratory Activity' => $bySource
        ] as $heading => $groups)

            <section class="space-y-4">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ $heading }}
                </h2>

                <div class="overflow-x-auto rounded-xl border bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-left">
                            <tr>
                                <th class="px-4 py-3">Name / Category</th>
                                <th class="px-4 py-3 text-right">Tests</th>
                                <th class="px-4 py-3 text-right">Types</th>
                                <th class="px-4 py-3 text-right">Completed</th>
                                <th class="px-4 py-3 text-right">In Process</th>
                                <th class="px-4 py-3 text-right">Ordered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($groups as $key => $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium">
                                        {{ $row['name'] ?? $key }}
                                    </td>
                                    @foreach (['tests', 'types', 'completed', 'in_process', 'ordered'] as $field)
                                        <td class="px-4 py-3 text-right">
                                            {{ number_format($row[$field]) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="px-4 py-8 text-center text-gray-500">
                                        No laboratory tests found for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @foreach ($groups as $key => $row)
                    @if ($row['items']->isNotEmpty())
                        <details class="rounded-xl border bg-white p-4">
                            <summary class="cursor-pointer font-semibold">
                                {{ $row['name'] ?? $key }} — Test Register
                                ({{ $row['items']->count() }} items)
                            </summary>

                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100 text-left">
                                        <tr>
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Patient</th>
                                            <th class="px-3 py-2">Test</th>
                                            <th class="px-3 py-2">Qty</th>
                                            <th class="px-3 py-2">Status</th>
                                            <th class="px-3 py-2">Source</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($row['items'] as $item)
                                            @php
                                                $order = $item->serviceOrder;
                                                $source = $order?->admission_id
                                                    ? 'IPD'
                                                    : ($order?->encounter_id ? 'OPD' : 'Other');
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-2">
                                                    {{ $order?->ordered_at?->format('d M Y') ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $order?->patient?->full_name ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $item->service_name }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $item->quantity }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ ucwords(str_replace('_', ' ', $item->status)) }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $source }}
                                                </td>
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
