<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Pharmacy Medicine Movement Analytics
                </h1>
                <p class="text-sm text-slate-500">
                    Tura Christian Hospital |
                    {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
                </p>
            </div>
            <a href="{{ route('pharmacy.dashboard') }}"
               class="rounded-lg border px-4 py-2 text-sm font-semibold text-slate-700">
                ← Pharmacy Dashboard
            </a>
        </div>

        <form method="GET" class="flex items-center gap-3 rounded-xl border bg-white p-4">
            <label for="days" class="text-sm font-medium">Analysis period</label>
            <select id="days" name="days" class="rounded-lg border-gray-300">
                @foreach ([7, 30, 90] as $option)
                    <option value="{{ $option }}" @selected($days === $option)>
                        Last {{ $option }} days
                    </option>
                @endforeach
            </select>
            <button class="rounded-lg bg-indigo-700 px-4 py-2 text-sm text-white">
                Generate
            </button>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'Molecules' => $byMolecule->count(),
                'Brands' => $byBrand->count(),
                'Medicines Sold' => $items->where('net_qty', '>', 0)->count(),
                'Non-moving Stock Items' => $nonMoving->count()
            ] as $label => $value)
                <div class="rounded-xl border bg-white p-4">
                    <p class="text-sm text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        @foreach ([
            'Molecule-wise Performance' => $byMolecule,
            'Brand-wise Performance' => $byBrand
        ] as $heading => $rows)
            <section class="rounded-xl border bg-white p-5">
                <h2 class="mb-4 text-lg font-bold">{{ $heading }}</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-left">
                            <tr>
                                <th class="p-3">Name</th>
                                <th class="p-3 text-right">Net Units</th>
                                <th class="p-3 text-right">Net Sales</th>
                                <th class="p-3 text-right">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="p-3">{{ $row['name'] }}</td>
                                    <td class="p-3 text-right">{{ number_format($row['quantity']) }}</td>
                                    <td class="p-3 text-right">₹{{ number_format($row['sales'], 2) }}</td>
                                    <td class="p-3 text-right">{{ number_format($row['stock']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-5 text-center">No data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        @foreach ([
            'Fast-moving Medicines' => $fastMoving,
            'Slow-moving Medicines' => $slowMoving,
            'No Sales During Selected Period' => $nonMoving
        ] as $heading => $rows)
            <section class="rounded-xl border bg-white p-5">
                <h2 class="mb-2 text-lg font-bold">{{ $heading }}</h2>
                <p class="mb-4 text-xs text-slate-500">
                    Based on net units dispensed and current stock. Historical stock availability is not established.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-left">
                            <tr>
                                <th class="p-3">Brand / Molecule</th>
                                <th class="p-3 text-right">Net Units</th>
                                <th class="p-3 text-right">Stock</th>
                                <th class="p-3 text-right">Daily Consumption</th>
                                <th class="p-3 text-right">Coverage (days)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="p-3">
                                        <strong>{{ $row['brand'] }}</strong>
                                        <div class="text-xs text-slate-500">
                                            {{ $row['molecule'] }} {{ $row['strength'] }}
                                        </div>
                                    </td>
                                    <td class="p-3 text-right">{{ $row['net_qty'] }}</td>
                                    <td class="p-3 text-right">{{ $row['stock'] }}</td>
                                    <td class="p-3 text-right">{{ $row['daily_consumption'] }}</td>
                                    <td class="p-3 text-right">
                                        {{ $row['coverage_days'] ?? 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-5 text-center">No medicines found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach
    </div>
</x-app-layout>
