<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <h1 class="mb-2 text-2xl font-bold">Legacy MHIS Settlements</h1>
        <p class="mb-6 text-sm text-slate-600">Payments against receivables outstanding before 1 April 2027. Draft/verification only; no GL posting.</p>
        @if(session('success')) <div class="mb-4 rounded bg-green-50 p-3 text-green-800">{{ session('success') }}</div> @endif
        @if($errors->any()) <div class="mb-4 rounded bg-red-50 p-3 text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif

        @if(auth()->user()?->hasPermission('finance.mhis-legacy-settlement.create'))
            <section class="mb-6 rounded-lg border bg-white p-5 shadow-sm">
                <h2 class="mb-3 font-semibold">Record settlement as draft</h2>
                @if($openings->isEmpty())
                    <p>No approved opening receivable exists. Entry is disabled until opening reconciliation and independent approval.</p>
                @else
                    <form method="POST" action="{{ route('finance.mhis-legacy-settlements.store') }}" class="grid gap-4 md:grid-cols-2">
                        @csrf
                        <label class="text-sm">Approved opening balance
                            <select name="mhis_opening_receivable_id" required class="mt-1 block w-full rounded border-slate-300">
                                @foreach($openings as $opening)
                                    <option value="{{ $opening->id }}" @selected(old('mhis_opening_receivable_id') == $opening->id)>
                                        {{ $opening->opening_date->format('d-m-Y') }} — ₹{{ number_format((float) $opening->opening_amount, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm">Settlement date
                            <input type="date" name="settlement_date" value="{{ old('settlement_date') }}" required class="mt-1 block w-full rounded border-slate-300">
                        </label>
                        <label class="text-sm">Bank UTR / settlement reference
                            <input name="settlement_reference" value="{{ old('settlement_reference') }}" maxlength="255" required class="mt-1 block w-full rounded border-slate-300">
                        </label>
                        <label class="text-sm">Historical claim reference (optional)
                            <input name="historical_claim_reference" value="{{ old('historical_claim_reference') }}" maxlength="255" class="mt-1 block w-full rounded border-slate-300">
                        </label>
                        @foreach(['net_amount'=>'Net bank receipt (₹)','tds_amount'=>'TDS (₹)','approved_deduction'=>'Approved deduction (₹)'] as $field=>$label)
                            <label class="text-sm">{{ $label }}
                                <input type="number" min="0" step="0.01" name="{{ $field }}" value="{{ old($field, '0.00') }}" required class="mt-1 block w-full rounded border-slate-300">
                            </label>
                        @endforeach
                        <label class="text-sm md:col-span-2">Remarks
                            <textarea name="remarks" rows="2" class="mt-1 block w-full rounded border-slate-300">{{ old('remarks') }}</textarea>
                        </label>
                        <button type="submit" class="rounded bg-violet-700 px-4 py-2 font-semibold text-white">Save draft only</button>
                    </form>
                @endif
            </section>
        @endif

        <section class="rounded-lg border bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold">Settlement register</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="p-2">Date</th><th class="p-2">Reference</th><th class="p-2">Net</th><th class="p-2">TDS</th><th class="p-2">Deduction</th><th class="p-2">Gross</th><th class="p-2">Status</th><th class="p-2">Action</th></tr></thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                            <tr class="border-b">
                                <td class="p-2">{{ $settlement->settlement_date->format('d-m-Y') }}</td>
                                <td class="p-2">{{ $settlement->settlement_reference }}</td>
                                @foreach(['net_amount','tds_amount','approved_deduction','gross_settled_amount'] as $column)
                                    <td class="p-2">₹{{ number_format((float) $settlement->$column, 2) }}</td>
                                @endforeach
                                <td class="p-2">{{ ucfirst($settlement->status) }}</td>
                                <td class="p-2">
                                    @if($settlement->status === 'draft' && auth()->user()?->hasPermission('finance.mhis-legacy-settlement.verify') && (int)$settlement->created_by !== (int)auth()->id())
                                        <form method="POST" action="{{ route('finance.mhis-legacy-settlement.verify', $settlement) }}" onsubmit="return confirm('Verify this settlement without posting any journal?')">
                                            @csrf
                                            <button type="submit" class="rounded bg-violet-700 px-3 py-1 font-semibold text-white">Verify</button>
                                        </form>
                                    @else — @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-4 text-slate-500">No legacy settlements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $settlements->links() }}</div>
        </section>
    </div>
</x-app-layout>
