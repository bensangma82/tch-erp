{{-- Development review screen only: no financial execution actions. --}}
<x-app-layout>
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-3">Investigation cancellation approvals</h1>
    <p class="mb-4 text-amber-700">Approval only. This screen does not reverse journals or issue refunds.</p>
    @if(session('success')) <p role="status" class="mb-4 text-green-700">{{ session('success') }}</p> @endif
    @if($errors->any()) <div role="alert" class="text-red-700 mb-4">{{ $errors->first() }}</div> @endif
    <form method="GET" class="mb-4 flex gap-2 items-center">
        <label for="status">Status</label>
        <select id="status" name="status" class="border p-2">
            @foreach(['pending','approved','rejected','executed','all'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        <button class="border px-3 py-2" type="submit">Filter</button>
    </form>
    <div class="overflow-x-auto">
    <table class="w-full border-collapse text-left">
        <thead><tr><th class="p-2">Request</th><th class="p-2">Invoice</th><th class="p-2">Item</th><th class="p-2">Amount</th><th class="p-2">Clinical status</th><th class="p-2">MS required</th><th class="p-2">Status</th><th class="p-2">Preview</th><th class="p-2">Review</th></tr></thead>
        <tbody>
        @forelse($cancellations as $c)
        <tr class="border-t align-top">
            <td class="p-2">#{{ $c->id }}<div class="text-sm">{{ $c->requester?->name }}</div><div class="text-sm">{{ $c->reason }}</div></td>
            <td class="p-2"><a class="underline" href="{{ route('billing.investigation-cancellations.invoice', $c->invoice_id) }}">{{ $c->invoice?->invoice_no ?? $c->invoice_id }}</a><div class="text-xs mt-1"><a class="underline text-blue-700" href="{{ route('billing.investigation-cancellations.reconciliation', $c->invoice_id) }}">Reconciliation</a></div></td>
            <td class="p-2">{{ $c->invoiceItem?->description ?? $c->invoice_item_id }}</td>
            <td class="p-2">₹{{ number_format((float)$c->requested_amount, 2) }}</td>
            <td class="p-2">{{ $c->clinical_status_at_request }}</td>
            <td class="p-2">{{ $c->requires_ms_approval ? 'Yes' : 'No at request' }}</td>
            <td class="p-2">{{ $c->status }}</td>
            @if($c->status === 'approved' && $canDecide)
                <td class="p-2"><a class="underline text-blue-700" href="{{ route('billing.investigation-cancellations.preview', $c) }}">Preview reversal</a></td>
            @else
                <td class="p-2">—</td>
            @endif
            <td class="p-2">
            @if($c->status === 'pending' && $canDecide)
                <form method="POST" action="{{ route('billing.investigation-cancellations.decide', $c) }}" class="flex flex-col gap-2">
                    @csrf
                    <label for="decision-{{ $c->id }}">Decision</label>
                    <select id="decision-{{ $c->id }}" name="decision" required class="border p-1"><option value="approve">Approve</option><option value="reject">Reject</option></select>
                    <label for="reason-{{ $c->id }}">Decision reason (minimum 10 characters)</label>
                    <textarea id="reason-{{ $c->id }}" name="decision_reason" minlength="10" maxlength="2000" required class="border p-1" rows="2"></textarea>
                    <button type="submit" class="border p-2">Record decision</button>
                </form>
                <p class="text-xs">Server rechecks MS designation, clinical state and separation of duties.</p>
            @else
                {{ $c->reviewer?->name ?? '—' }}
            @endif
            </td>
        </tr>
        @empty <tr><td colspan="9" class="p-4">No requests found.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="mt-4">{{ $cancellations->links() }}</div>
</div>
</x-app-layout>
