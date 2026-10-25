<x-app-layout>
  <div class="max-w-4xl mx-auto p-6 space-y-4">
    <h1 class="text-2xl font-bold">Proposed investigation revenue reversal</h1>
    <p class="font-semibold text-amber-700">PREVIEW ONLY — no journal, refund, invoice, or clinical record is changed.</p>
    <p>Cancellation #{{ $cancellation->id }} · Invoice {{ $invoice->invoice_no }} · {{ $item->description }}</p>
    <p>Approved amount: ₹{{ number_format((float) $amount, 2) }} · Reviewer: {{ $cancellation->reviewer?->name ?? 'Unknown' }}</p>
    @if (! $mappingValid || ! $amountValid)
      <div class="border border-red-300 bg-red-50 text-red-800 p-3">
        Preview cannot be reconciled: revenue/receivable GL mapping is missing or ambiguous, or the approved amount differs from the original invoice item amount. No journal can be approved from this page.
      </div>
    @else
      <table class="w-full text-left border-collapse bg-white">
        <thead><tr class="border-b"><th class="p-3">GL account</th><th class="p-3 text-right">Debit</th><th class="p-3 text-right">Credit</th></tr></thead>
        <tbody>
          <tr class="border-b"><td class="p-3">{{ $revenue->first()->account_code }} — {{ $revenue->first()->account_name }}</td><td class="p-3 text-right">₹{{ number_format((float) $amount, 2) }}</td><td class="p-3 text-right">—</td></tr>
          <tr class="border-b"><td class="p-3">{{ $receivable->first()->account_code }} — {{ $receivable->first()->account_name }}</td><td class="p-3 text-right">—</td><td class="p-3 text-right">₹{{ number_format((float) $amount, 2) }}</td></tr>
        </tbody>
      </table>
    @endif
    <div class="text-sm text-slate-700 border p-3 space-y-1">
      <p>Clinical state now: {{ $clinical?->status ?? 'Unavailable' }}</p>
      <p>Sample: {{ $clinical?->diagnosticSample ? 'Recorded' : 'Not recorded' }} · Result: {{ $clinical?->diagnosticResult ? 'Recorded' : 'Not recorded' }}</p>
      <p>Important: this is a journal illustration, not clearance to execute. Current clinical eligibility, Tally linkage, receipt balances, and concurrent writes must still be validated by the execution service.</p>
    </div>
    @if(app()->environment('local')
        && config('investigation_cancellation.allow_dummy_execution') === true
        && (int) $cancellation->id === 2
        && (int) $cancellation->invoice_id === 29
        && $cancellation->status === 'approved'
        && (int) auth()->id() === 2
        && auth()->user()?->hasRole('admin')
        && auth()->user()?->hasPermission('billing.investigation-refund.process')
        && $mappingValid && $amountValid)
      <form method="POST" action="{{ route('billing.investigation-cancellations.execute', $cancellation) }}" class="border border-red-300 bg-red-50 p-4 space-y-2"
            onsubmit="return confirm('POST the ₹500 revenue reversal permanently in the local dummy ERP? NO refund will be paid.');">
        @csrf
        <label class="block font-semibold" for="confirmation">Confirm development-only reversal</label>
        <p class="text-sm">Only Request #2 / Invoice #29. This posts a journal and marks the cancellation executed. It does not change the invoice totals or refund the patient.</p>
        <input class="border p-2 w-full" id="confirmation" name="confirmation" required placeholder="EXECUTE TEST CANCELLATION 2" autocomplete="off" />
        <button class="bg-red-700 text-white px-4 py-2 rounded" type="submit">Execute dummy cancellation</button>
      </form>
    @endif
    @if(session('success')) <p role="status" class="text-green-700">{{ session('success') }}</p> @endif
    @if($errors->any()) <p role="alert" class="text-red-700">{{ $errors->first() }}</p> @endif
    <a class="underline" href="{{ route('billing.investigation-cancellations.queue', ['status' => 'approved']) }}">Back to approved requests</a>
  </div>
</x-app-layout>
