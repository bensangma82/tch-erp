<x-app-layout>
<div class="max-w-5xl mx-auto p-6 space-y-4">
<h1 class="text-2xl font-bold">Investigation cancellation reconciliation — {{ $invoice->invoice_no }}</h1>
<p class="text-amber-700">The original invoice and receipts remain unchanged. Figures below reflect executed cancellation journals and completed refund records.</p>
@if(session('success'))<p class="text-green-700" role="status">{{ session('success') }}</p>@endif
@if($errors->any())<p class="text-red-700" role="alert">{{ $errors->first() }}</p>@endif
@php $money = fn ($n) => '₹'.number_format($n / 100, 2); @endphp
<table class="w-full bg-white text-left border-collapse">
@foreach([
'Original invoice' => $summary['invoice_total'],
'Original receipts collected' => $summary['paid'],
'Executed cancellation amount' => $summary['executed_cancellations'],
'Revised net bill' => $summary['revised_net_bill'],
'Completed refunds' => $summary['completed_refunds'],
'Net cash retained' => $summary['net_paid'],
'Balance due' => $summary['balance_due'],
'Potential refundable overpayment' => $summary['potential_refund']
] as $label => $value)
<tr class="border-b"><th class="p-3">{{ $label }}</th><td class="p-3 text-right">{{ $money($value) }}</td></tr>
@endforeach
</table>
@if($summary['refund_hold'])<p class="text-red-700">Unresolved refund record exists. Further refund recording blocked until reconciliation.</p>@endif
<h2 class="font-semibold">Cancellation history</h2>
@foreach($summary['requests'] as $c)
<p>#{{ $c->id }} — {{ ucfirst($c->status) }} — {{ $money((int)round(((float)($c->approved_amount ?? $c->requested_amount))*100)) }}</p>
@endforeach
<h2 class="font-semibold">Recorded refunds</h2>
@forelse($summary['refunds'] as $r)
<p>#{{ $r->id }} — {{ $money((int)round(((float)$r->amount)*100)) }} — {{ $r->status }} — {{ $r->reference_no }}</p>
@empty<p>No refund recorded.</p>@endforelse
@php
    $refundEnabled = app()->environment('local')
        && config('investigation_cancellation.enable_verified_refunds', false);

    $canSubmitRefund = auth()->user()->permissions()
        ->where('permissions.name', 'billing.investigation-refund.process')
        ->exists();

    $paymentsForRefund = $refundEnabled && $canSubmitRefund
        ? \App\Models\Payment::where('invoice_id', $invoice->id)->get()
        : collect();
@endphp

<div class="border rounded-lg p-4 space-y-3">
    <h2 class="font-semibold">Refund settlement</h2>

    <p class="text-sm">
        Refund accounting requires proof of an already completed
        cash or electronic refund and independent verification.
    </p>

    @if($refundEnabled && $canSubmitRefund && $eligible
        && $summary['potential_refund'] > 0
        && !$summary['refund_hold'])

        <form method="POST"
              action="{{ route('billing.investigation-refunds.submit', $eligible) }}"
              class="space-y-3">
            @csrf

            <label class="block">Original receipt
                <select name="payment_id" required class="border p-2 w-full">
                    @foreach($paymentsForRefund as $payment)
                        <option value="{{ $payment->id }}">
                            {{ $payment->receipt_no }} —
                            ₹{{ $payment->amount }} —
                            {{ $payment->payment_mode }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">Refund amount (₹)
                <input name="amount" required
                       value="{{ number_format($summary['potential_refund'] / 100, 2, '.', '') }}"
                       class="border p-2 w-full">
            </label>

            <label class="block">Settlement method
                <select name="settlement_method" required class="border p-2 w-full">
                    <option value="cash">Cash</option>
                    <option value="upi">UPI</option>
                    <option value="card">Card</option>
                </select>
            </label>

            <label class="block">Settlement reference
                <input name="settlement_reference" required minlength="6"
                       class="border p-2 w-full">
            </label>

            <label class="block">Evidence description
                <textarea name="evidence_description" required minlength="15"
                          class="border p-2 w-full"></textarea>
            </label>

            <label class="block">
                <input type="checkbox" name="actually_paid" value="1" required>
                I confirm that the refund has actually been paid
                and documentary evidence is available.
            </label>

            <button type="submit" class="border rounded px-4 py-2">
                Submit refund for independent verification
            </button>
        </form>
    @else
        <p class="text-amber-700">
            Refund submission is currently disabled.
            The reconciliation details remain available for review.
        </p>
    @endif

    @if(auth()->user()->permissions()
        ->where('permissions.name', 'billing.investigation-refund.verify')
        ->exists())
        <a class="underline"
           href="{{ route('billing.investigation-refunds.verification') }}">
            Open independent refund verification queue
        </a>
    @endif
</div>
<a class="underline" href="{{ route('billing.investigation-cancellations.queue', ['status'=>'executed']) }}">Back to executed cancellations</a>
</div>
</x-app-layout>
