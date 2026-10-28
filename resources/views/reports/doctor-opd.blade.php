<x-app-layout>
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Doctor-wise OPD Patient Register
            </h1>
            <p class="text-sm text-gray-500">
                Tura Christian Hospital | {{ $from }} to {{ $to }}
            </p>
        </div>

        <button type="button"
                onclick="window.print()"
                class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white print:hidden">
            Print Report
        </button>
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-4 rounded-xl border bg-white p-4 print:hidden">
        <div>
            <label for="from" class="block text-sm font-medium">From</label>
            <input id="from" type="date" name="from" value="{{ $from }}"
                   class="rounded-lg border-gray-300">
        </div>
        <div>
            <label for="to" class="block text-sm font-medium">To</label>
            <input id="to" type="date" name="to" value="{{ $to }}"
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

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach (['registered' => 'Registered', 'seen' => 'Seen', 'pending' => 'Pending'] as $key => $label)
            <div class="rounded-xl border bg-white p-5">
                <div class="text-sm text-gray-500">{{ $label }}</div>
                <div class="mt-2 text-3xl font-bold">{{ $totals[$key] }}</div>
            </div>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left">
                <tr>
                    <th class="px-4 py-3">Doctor</th>
                    <th class="px-4 py-3 text-right">Registered</th>
                    <th class="px-4 py-3 text-right">Seen</th>
                    <th class="px-4 py-3 text-right">Pending</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($doctors as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $row['doctor']?->full_name ?? 'Unassigned' }}
                        </td>
                        <td class="px-4 py-3 text-right">{{ $row['registered'] }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['seen'] }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['pending'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                            No OPD encounters found for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @foreach ($doctors as $row)
        <section class="break-inside-avoid rounded-xl border bg-white p-4">
            <h2 class="mb-3 text-lg font-semibold">
                {{ $row['doctor']?->full_name ?? 'Unassigned' }}
            </h2>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-left">
                        <tr>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Patient</th>
                            <th class="px-3 py-2">UHID</th>
                            <th class="px-3 py-2">Department</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Seen At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($row['patients'] as $visit)
                            <tr>
                                <td class="px-3 py-2">
                                    {{ $visit->encounter_date?->format('d M Y') }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $visit->patient?->full_name ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $visit->patient?->uhid ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $visit->department?->name ?? '—' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $visit->status === 'completed' ? 'Seen' : 'Pending' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ $visit->seen_at?->format('d M Y, h:i A') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach

</div>
</x-app-layout>
