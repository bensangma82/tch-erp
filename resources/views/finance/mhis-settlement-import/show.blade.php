<x-app-layout>
<div class="container mx-auto px-4 py-6">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5">
        <a href="{{ route('finance.mhis-settlement-import.index') }}"
           class="text-blue-700 underline">
            Back to MHIS Imports
        </a>
    </div>

    <h1 class="text-2xl font-bold mb-2">
        MHIS Settlement Preview — Batch #{{ $batch->id }}
    </h1>

    <p class="text-sm text-gray-600 mb-6">
        {{ $batch->original_filename }}
    </p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white shadow rounded p-4">
            <div class="text-sm text-gray-500">Records</div>
            <div class="text-xl font-bold">{{ $batch->total_rows }}</div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-sm text-gray-500">Gross Approved</div>
            <div class="text-xl font-bold">
                ₹{{ number_format($batch->total_approved, 2) }}
            </div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-sm text-gray-500">TDS</div>
            <div class="text-xl font-bold">
                ₹{{ number_format($batch->total_tds, 2) }}
            </div>
        </div>

        <div class="bg-white shadow rounded p-4">
            <div class="text-sm text-gray-500">Net Payment</div>
            <div class="text-xl font-bold">
                ₹{{ number_format($batch->total_net, 2) }}
            </div>
        </div>
    </div>


    {{-- Read-only reconciliation summary --}}
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">
            MHIS Reconciliation Status
        </h2>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ([
                'unmatched' => 'Unmatched',
                'matched' => 'Provisionally Matched',
                'ambiguous' => 'Ambiguous',
                'review_required' => 'Review Required',
            ] as $status => $label)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-sm text-slate-600">
                        {{ $label }}
                    </div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">
                        {{ number_format((int) ($matchSummary[$status] ?? 0)) }}
                    </div>
                </div>
            @endforeach
        </div>


        @can('finance.mhis-settlement-import.match')
            <form
                method="POST"
                action="{{ route('finance.mhis-settlement-import.match', $batch) }}"
                class="mt-4"
            >
                @csrf

                <button
                    type="submit"
                    class="rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm"
                    style="background-color: #6d28d9; color: #ffffff;"
                >
                    Run Provisional Claim Matching
                </button>
            </form>
        @endcan

        <p class="mt-4 text-sm text-slate-500">
            Matching status reflects ERP claim identification only.
            It does not confirm bank receipt or authorize accounting posting.
        </p>
    </div>

    <div class="bg-white shadow rounded p-4">
        <h2 class="text-lg font-semibold mb-3">
            Imported Settlement Records
        </h2>

        <p class="mb-4 text-sm text-amber-700">
            Preview only. No claims have been reconciled,
            and no accounting transactions have been posted.
        </p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm whitespace-nowrap">
                <thead class="bg-gray-100">
                    <tr class="text-left">
                        <th class="p-2">Excel Row</th>
                        <th class="p-2">Registration ID</th>
                        <th class="p-2">Beneficiary</th>
                        <th class="p-2">Initiated</th>
                        <th class="p-2">Approved</th>
                        <th class="p-2">TDS</th>
                        <th class="p-2">Net Paid</th>
                        <th class="p-2">UTR</th>
                        <th class="p-2">Payment Date</th>
                        <th class="p-2">Match Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-b">
                            <td class="p-2">{{ $row->excel_row_number }}</td>
                            <td class="p-2">{{ $row->registration_id }}</td>
                            <td class="p-2">{{ $row->beneficiary_name }}</td>
                            <td class="p-2">{{ number_format($row->initiated_amount, 2) }}</td>
                            <td class="p-2">{{ number_format($row->approved_amount, 2) }}</td>
                            <td class="p-2">{{ number_format($row->tds_amount, 2) }}</td>
                            <td class="p-2">{{ number_format($row->net_amount, 2) }}</td>
                            <td class="p-2">{{ $row->utr_reference }}</td>
                            <td class="p-2">{{ $row->payment_date?->format('d-m-Y') }}</td>
                            <td class="p-2">{{ $row->match_status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-4 text-center text-gray-500">
                                No settlement records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rows->links() }}
        </div>
    </div>
</div>
</x-app-layout>
