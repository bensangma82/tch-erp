<x-app-layout>
    <x-slot name="header">
        <div class="flex w-full flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Laboratory Worklist</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Paid and authorized laboratory orders grouped by order number.
                </p>
            </div>

            <a href="{{ route('imaging.index') }}"
               class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                Imaging Worklist
            </a>
        </div>
    </x-slot>

    <div class="w-full py-6">
        <div class="w-full px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                    <ul class="list-inside list-disc text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">Laboratory Orders</h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            One row per order. Open an order to work with its individual investigations.
                        </p>
                    </div>
                    <div class="text-sm text-gray-600">
                        Orders: <span class="font-semibold text-gray-900">{{ $orders->count() }}</span>
                    </div>
                </div>

                <table class="w-full table-fixed border-collapse">
                    <colgroup>
                        <col style="width:15%">
                        <col style="width:19%">
                        <col style="width:23%">
                        <col style="width:18%">
                        <col style="width:17%">
                        <col style="width:8%">
                    </colgroup>
                    <thead class="bg-gray-50">
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Order</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Patient</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Doctor / Department</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tests</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Progress</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        </tr>
                    </thead>

                    @forelse ($orders as $group)
                        @php
                            $order = $group->order;
                            $orderItems = $group->items;
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

                            $speciality = $admission?->consultant?->speciality
                                ?? $encounter?->doctor?->speciality;

                            $totalTests = $orderItems->count();

                            $awaitingSample = $orderItems->filter(function ($item) {
                                if (! $item->requires_sample || $item->status !== 'ordered') return false;
                                $sample = $item->diagnosticSample;
                                return ! $sample || $sample->status !== 'collected';
                            })->count();

                            $ready = $orderItems->filter(function ($item) {
                                if ($item->status !== 'ordered') return false;
                                if (! $item->requires_sample) return true;
                                return $item->diagnosticSample
                                    && $item->diagnosticSample->status === 'collected';
                            })->count();

                            $inProcess = $orderItems->where('status', 'in_process')->count();
                            $completed = $orderItems->where('status', 'completed')->count();

                            $printableCompleted = $orderItems->filter(function ($item) {
                                return $item->status === 'completed'
                                    && $item->diagnosticResult
                                    && in_array($item->diagnosticResult->status, ['final', 'verified'], true);
                            })->count();

                            $detailsId = 'lab-order-' . ($order?->id ?? $loop->index);
                        @endphp

                        <tbody x-data="{ open: false }" class="border-b border-gray-200 last:border-b-0">
                            <tr class="align-top hover:bg-gray-50">
                                <td class="px-4 py-4">
                                    <div class="break-all font-mono text-xs font-semibold text-gray-900">
                                        {{ $order?->order_no ?? '—' }}
                                    </div>
                                    <div class="mt-1 text-[11px] leading-4 text-gray-400">
                                        {{ $order?->ordered_at?->format('d M Y') ?? '—' }}
                                    </div>
                                    <div class="text-[11px] leading-4 text-gray-400">
                                        {{ $order?->ordered_at?->format('h:i A') ?? '' }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-semibold text-gray-900">{{ $patient?->full_name ?? '—' }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500">
                                        {{ $patient?->age !== null ? $patient->age . ' yrs' : 'Age —' }}
                                        / {{ $patient?->sex ? ucfirst($patient->sex) : '—' }}
                                    </div>
                                    <div class="mt-0.5 break-all font-mono text-[11px] text-gray-500">{{ $patient?->uhid ?? '—' }}</div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-800">{{ $doctorName }}</div>
                                    @if ($speciality)
                                        <div class="mt-0.5 text-[11px] text-gray-400">{{ $speciality }}</div>
                                    @endif
                                    <div class="mt-1 text-xs font-semibold text-gray-600">{{ $departmentName }}</div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $totalTests }} {{ Str::plural('test', $totalTests) }}
                                    </div>
                                    <div class="mt-1 line-clamp-2 text-[11px] leading-4 text-gray-500">
                                        {{ $orderItems->pluck('service_name')->join(', ') }}
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($awaitingSample)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                                {{ $awaitingSample }} Awaiting
                                            </span>
                                        @endif
                                        @if ($ready)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                                                {{ $ready }} Ready
                                            </span>
                                        @endif
                                        @if ($inProcess)
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                                {{ $inProcess }} In Process
                                            </span>
                                        @endif
                                        @if ($completed)
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">
                                                {{ $completed }} Completed
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-right">
                                    <div class="flex flex-col items-end gap-2">
                                        @if ($printableCompleted > 0)
                                            <a href="{{ route('diagnostics.orders.results.group', $order->id) }}"
                                               class="inline-flex whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-slate-800">
                                                Print {{ $printableCompleted }}
                                            </a>
                                        @endif

                                        <button type="button"
                                                @click="open = !open"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800">
                                            <span x-text="open ? 'Close' : 'Open'">Open</span>
                                            <svg class="h-4 w-4 transition-transform"
                                                 :class="{ 'rotate-180': open }"
                                                 viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                      d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr x-show="open" x-cloak>
                                <td colspan="6" class="bg-gray-50 px-5 py-4">
                                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                        <table class="w-full table-fixed">
                                            <colgroup>
                                                <col style="width:38%">
                                                <col style="width:25%">
                                                <col style="width:17%">
                                                <col style="width:20%">
                                            </colgroup>
                                            <thead class="bg-gray-50">
                                                <tr class="border-b border-gray-200">
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">Investigation</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">Sample</th>
                                                    <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                                    <th class="px-4 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wide text-gray-500">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($orderItems as $item)
                                                    @php
                                                        $sample = $item->diagnosticSample;
                                                        $result = $item->diagnosticResult;
                                                        $requiresSample = (bool) $item->requires_sample;
                                                        $sampleCollected = $sample && $sample->status === 'collected';
                                                        $sampleRejected = $sample && $sample->status === 'rejected';
                                                    @endphp
                                                    <tr>
                                                        <td class="px-4 py-3">
                                                            <div class="text-sm font-semibold text-gray-900">{{ $item->service_name }}</div>
                                                            <div class="font-mono text-[11px] text-gray-500">{{ $item->service_code }}</div>
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            @if ($requiresSample)
                                                                @if ($sampleCollected)
                                                                    <div class="break-all text-[11px] font-semibold text-emerald-700">{{ $sample->sample_no }}</div>
                                                                    <div class="text-[11px] text-gray-500">{{ $sample->specimen_type ?? 'Collected' }}</div>
                                                                @elseif ($sampleRejected)
                                                                    <div class="text-[11px] font-semibold text-red-600">
                                                                        Rejected @if ($sample->sample_no) · {{ $sample->sample_no }} @endif
                                                                    </div>
                                                                @else
                                                                    <div class="text-[11px] font-semibold text-amber-600">Sample Required</div>
                                                                @endif
                                                            @else
                                                                <div class="text-[11px] text-gray-400">No sample required</div>
                                                            @endif
                                                        </td>

                                                        <td class="px-4 py-3">
                                                            @if ($item->status === 'ordered')
                                                                @if ($requiresSample && ! $sampleCollected)
                                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Awaiting Sample</span>
                                                                @else
                                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">Ready</span>
                                                                @endif
                                                            @elseif ($item->status === 'in_process')
                                                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">In Process</span>
                                                            @elseif ($item->status === 'completed')
                                                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">Completed</span>
                                                            @else
                                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold capitalize text-gray-700">
                                                                    {{ str_replace('_', ' ', $item->status) }}
                                                                </span>
                                                            @endif
                                                        </td>

                                                        <td class="px-4 py-3 text-right">
                                                            <div class="flex flex-col items-end gap-1">
                                                                @if ($item->status === 'ordered')
                                                                    @if ($requiresSample)
                                                                        @if (! $sample || $sampleRejected)
                                                                            <a href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                                               class="inline-flex rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                                                                                {{ $sampleRejected ? 'Recollect Sample' : 'Collect Sample' }}
                                                                            </a>
                                                                        @elseif ($sampleCollected)
                                                                            <form method="POST" action="{{ route('diagnostics.items.status', $item) }}">
                                                                                @csrf
                                                                                @method('PATCH')
                                                                                <input type="hidden" name="status" value="in_process">
                                                                                <button type="submit"
                                                                                        class="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                                                    Start Processing
                                                                                </button>
                                                                            </form>
                                                                            <a href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                                               class="text-[11px] font-semibold text-emerald-700 hover:text-emerald-900">
                                                                                View Sample
                                                                            </a>
                                                                        @else
                                                                            <a href="{{ route('diagnostics.items.sample.create', $item) }}"
                                                                               class="inline-flex rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                                                                                Manage Sample
                                                                            </a>
                                                                        @endif
                                                                    @else
                                                                        <form method="POST" action="{{ route('diagnostics.items.status', $item) }}">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <input type="hidden" name="status" value="in_process">
                                                                            <button type="submit"
                                                                                    class="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                                                Start Processing
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                @elseif ($item->status === 'in_process')
                                                                    <a href="{{ route('diagnostics.items.result.edit', $item) }}"
                                                                       class="inline-flex rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                                                        Enter Result
                                                                    </a>
                                                                @elseif ($item->status === 'completed')
                                                                    @if ($result)
                                                                        <div class="flex items-center justify-end gap-2">
                                                                            <a href="{{ route('diagnostics.items.result.show', $item) }}"
                                                                               class="inline-flex rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                                                View
                                                                            </a>
                                                                            <a href="{{ route('diagnostics.items.result.show', $item) }}?print=1"
                                                                               target="_blank"
                                                                               class="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                                                Print
                                                                            </a>
                                                                        </div>
                                                                    @else
                                                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">Completed</span>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <div class="text-sm font-medium text-gray-700">No laboratory investigations are waiting.</div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Paid or authorized laboratory orders will appear here automatically.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
