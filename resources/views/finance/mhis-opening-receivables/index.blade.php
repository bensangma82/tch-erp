<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">
                MHIS Opening Receivables
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Historical MHIS receivables brought forward into FY 2027–28.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @can('finance.mhis-opening.create')
            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">
                    Create Draft Opening Balance
                </h2>

                <form method="POST"
                      action="{{ route('finance.mhis-opening-receivables.store') }}"
                      class="space-y-4">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Opening Date
                            </label>
                            <input type="date"
                                   name="opening_date"
                                   value="2027-04-01"
                                   readonly
                                   class="mt-1 block w-full rounded-lg border-slate-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                Opening MHIS Receivable (₹)
                            </label>
                            <input type="number"
                                   name="opening_amount"
                                   step="0.01"
                                   min="0.01"
                                   value="{{ old('opening_amount') }}"
                                   required
                                   class="mt-1 block w-full rounded-lg border-slate-300">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">
                                Supporting Statement Reference
                            </label>
                            <input type="text"
                                   name="statement_reference"
                                   value="{{ old('statement_reference') }}"
                                   maxlength="255"
                                   required
                                   class="mt-1 block w-full rounded-lg border-slate-300">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">
                                Remarks
                            </label>
                            <textarea name="remarks"
                                      rows="3"
                                      class="mt-1 block w-full rounded-lg border-slate-300">{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <button type="submit"
                            class="rounded-lg px-5 py-2.5 text-sm font-semibold"
                            style="background-color:#6d28d9;color:#ffffff;">
                        Save Draft Opening Balance
                    </button>
                </form>
            </div>
        @endcan

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">
                Opening Balance Register
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left">
                        <tr>
                            <th class="p-3">Opening Date</th>
                            <th class="p-3">Financial Year</th>
                            <th class="p-3">Opening Amount</th>
                            <th class="p-3">Statement Reference</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Journal</th>
                            <th class="p-3">Approval</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr class="border-t border-slate-200">
                                <td class="p-3">
                                    {{ $record->opening_date->format('d-m-Y') }}
                                </td>
                                <td class="p-3">{{ $record->financial_year }}</td>
                                <td class="p-3 font-semibold">
                                    ₹{{ number_format((float)$record->opening_amount, 2) }}
                                </td>
                                <td class="p-3">{{ $record->statement_reference }}</td>
                                <td class="p-3">{{ ucfirst($record->status) }}</td>
                                <td class="p-3">
                                    {{ $record->journal_entry_id ?? 'Not posted' }}
                                </td>
                                <td class="p-3">
                                    @if($record->status === 'draft'
                                        && auth()->user()?->hasPermission('finance.mhis-opening.approve')
                                        && $record->created_by !== auth()->id())
                                        <form method="POST"
                                              action="{{ route('finance.mhis-opening-receivables.approve', $record) }}">
                                            @csrf

                                            <label class="mb-2 flex items-start gap-2 text-xs text-slate-600">
                                                <input type="checkbox"
                                                       name="reconciliation_confirmed"
                                                       value="1"
                                                       required>
                                                <span>
                                                    I confirm reconciliation with the
                                                    31 March 2027 closing MHIS statement
                                                    and trial balance.
                                                </span>
                                            </label>

                                            <button type="submit"
                                                    class="rounded-lg px-3 py-2 text-xs font-semibold"
                                                    style="background-color:#6d28d9;color:#ffffff;">
                                                Approve
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-500">
                                            {{ ucfirst($record->status) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-500">
                                    No opening MHIS balances recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $records->links() }}</div>
        </div>
    </div>
</x-app-layout>
