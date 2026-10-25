<x-app-layout>
<div class="max-w-5xl mx-auto p-6 space-y-4">
<h1 class="text-xl font-bold">Investigation refund settlement verification</h1>
<p class="text-amber-800">Development only. This page cannot issue a bank transfer or hand over cash. An independent verifier must inspect actual settlement evidence before posting a refund journal.</p>
@if(session('success')) <p class="text-green-700">{{session('success')}}</p> @endif
@if($errors->any()) <p class="text-red-700">{{$errors->first()}}</p> @endif
@forelse($pending as $check)
<div class="bg-white p-4 border space-y-2">
<p>#{{$check->id}} — Request #{{$check->cancellation_id}} — ₹{{$check->amount}} — {{$check->settlement_method}} — {{$check->settlement_reference}}</p>
<p>Cashier: {{$check->submitted_by}} | Documentary evidence: {{$check->evidence_description}}</p>
@if(auth()->user()->permissions()->where('permissions.name','billing.investigation-refund.verify')->exists() && auth()->id() !== (int)$check->submitted_by)
<form method="POST" action="{{route('billing.investigation-refunds.verify',$check)}}" class="space-y-2">
@csrf
<textarea name="verification_notes" required minlength="10" class="border p-2 w-full" placeholder="Independent cash voucher/bank evidence verified; record reference and date"></textarea>
<label class="block"><input type="checkbox" required name="evidence_checked" value="1"/> I independently checked the original disbursement evidence</label>
<button type="submit" class="border p-2">Verify and post refund journal</button>
</form>
@endif
</div>
@empty<p>No pending settlement verifications.</p>
@endforelse
{{$pending->links()}}
</div>
</x-app-layout>
