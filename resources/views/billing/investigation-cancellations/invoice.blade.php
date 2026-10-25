{{-- Item-level request screen; does not change invoice totals or financial/clinical state. --}}
<x-app-layout>
<div class="max-w-5xl mx-auto p-6">
    <h1 class="text-2xl font-bold">Investigation cancellation — {{ $invoice->invoice_no }}</h1>
    <p class="my-3 text-amber-700">Cancellation requests and approvals only. No refund or reversal is performed here.</p>
    <a class="underline" href="{{ route('billing.investigation-cancellations.queue') }}">Approval queue</a>
    @if(session('success')) <p role="status" class="my-4 text-green-700">{{ session('success') }}</p> @endif
    @if($errors->any()) <div role="alert" class="my-4 text-red-700">{{ $errors->first() }}</div> @endif
    @foreach($invoice->items as $item)
    @php
      $orderItem = $item->serviceOrderItem;
      $previous = $requestsByItem->get($item->id, collect());
      $blocked = $previous->contains(fn($r) => in_array($r->status, ['pending','approved','executed'], true));
    @endphp
    <section class="border rounded p-4 my-4">
        <h2 class="font-semibold">{{ $item->description ?? ('Item #'.$item->id) }} — ₹{{ number_format((float)$item->amount, 2) }}</h2>
        <p>Clinical status: {{ $orderItem?->status ?? 'Not linked' }}; specimen: {{ $orderItem?->diagnosticSample?->status ?? 'None recorded' }}; result: {{ $orderItem?->diagnosticResult ? 'Present' : 'Not recorded' }}</p>
        @foreach($previous as $c)
        <p>Request #{{ $c->id }}: <strong>{{ $c->status }}</strong> — {{ $c->reason }} @if($c->reviewed_at) (reviewed {{ $c->reviewed_at->format('d M Y H:i') }}) @endif</p>
        @endforeach
        @if($canRequest && $orderItem && !$blocked)
        <form method="POST" action="{{ route('billing.investigation-cancellations.request', $item) }}" class="mt-3">
            @csrf
            <label for="reason-{{ $item->id }}" class="block">Reason for cancellation (minimum 10 characters)</label>
            <textarea id="reason-{{ $item->id }}" name="reason" minlength="10" maxlength="2000" required class="border w-full p-2" rows="2"></textarea>
            <button type="submit" class="border p-2 mt-2">Submit cancellation request</button>
        </form>
        @elseif($blocked)
            <p class="text-sm">An active or executed cancellation exists. New request disabled.</p>
        @endif
    </section>
    @endforeach
</div>
</x-app-layout>
