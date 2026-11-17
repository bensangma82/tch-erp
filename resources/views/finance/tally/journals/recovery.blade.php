<x-app-layout>
<div class="container mx-auto max-w-3xl p-6 space-y-5">
    <h1 class="text-2xl font-bold">Tally Export Recovery</h1>
    <p><a class="underline" href="{{ route('finance.tally.journals.index') }}">Back to journal exports</a></p>
    <div class="rounded border p-4">
        <p>Company: <strong>{{ $company }}</strong></p>
        <p>Journal: <strong>{{ $export->journalEntry?->journal_no }}</strong> | Export #{{ $export->id }}</p>
        <p>Export status: <strong>{{ $export->status }}</strong></p>
        <p>Live checks replace manual evidence entry for new retries. Possible matches and uncertain transmission outcomes remain blocked.</p>
    </div>
    @if(session('success'))<div class="border p-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="border border-red-500 p-3">{{ $errors->first() }}</div>@endif
    <h2 class="font-bold">Audit history</h2>
    <div class="border p-3">
        @forelse($export->journalExportAttempts as $attempt)
            <p>Attempt {{ $attempt->attempt_number }} (#{{ $attempt->id }}): <strong>{{ $attempt->status }}</strong>; transmitted {{ $attempt->transmitted_at ?? 'never' }}</p><details><summary>Check evidence</summary><p>{{ $attempt->reconciliation_evidence }}</p></details>
        @empty
            <p>No recovery attempts</p>
        @endforelse
    </div>
    @php
        $last = $export->journalExportAttempts->sortByDesc('attempt_number')->first();
        $canResolve = $export->status === 'unknown' && $last && $last->status === 'unknown';
        $canApprove = $export->status === 'unknown' && (!$last || in_array($last->status, ['rejected','preparation_failed'], true));
        $canSend = $export->status === 'unknown' && $last && $last->status === 'authorized';
    @endphp
    @if($canResolve)
    <form method="POST" action="{{ route('finance.tally.journals.recovery.resolve', $export) }}" class="border rounded p-4 space-y-2">
        @csrf
        <h2 class="font-bold">1. Resolve explicit rejection (no transmission)</h2>
        <textarea name="evidence" required minlength="35" class="w-full border p-2" placeholder="Fresh TallyPrime -Test Day Book check: {{ $export->journalEntry?->journal_no }} absent, date/time, checked by..."></textarea>
        <label class="block"><input type="checkbox" name="absence_confirmed" value="1" required> I freshly verified absence in the correct live TallyPrime test-company Day Book</label>
        <button class="border rounded p-2 bg-amber-100" type="submit">Resolve rejected attempt</button>
    </form>
    @elseif($canApprove)
    <form method="POST" action="{{ route('finance.tally.journals.recovery.approve', $export) }}" class="border rounded p-4 space-y-2">
        @csrf
        <input type="hidden" name="simple_recovery" value="1">
        <h2 class="font-bold">Check before retrying</h2>
        <p>The ERP checks the live test company for an existing voucher and records the user, time and evidence automatically. This button does not send a voucher.</p>
        <button class="border rounded p-2 bg-blue-100" type="submit">Check and prepare retry</button>
    </form>
    @elseif($canSend)
    @if(str_starts_with((string) $last->reconciliation_evidence, '[AUTO-LIVE]'))
    <form method="POST" action="{{ route('finance.tally.journals.recovery.send', $export) }}" class="border rounded p-4 space-y-2" onsubmit="this.querySelector('button').disabled=true;">
        @csrf
        <input type="hidden" name="simple_recovery" value="1">
        <input type="hidden" name="send_once" value="1">
        <p>Ready for one retry. The ERP checks live Tally again before sending. Approval expires after 15 minutes.</p>
        <button class="border rounded p-2 bg-green-100" type="submit">Retry export</button>
    </form>
    @else
    <form method="POST" action="{{ route('finance.tally.journals.recovery.send', $export) }}" class="border rounded p-4 space-y-2" onsubmit="return confirm('SEND exactly ONE voucher to the TEST company? Never retry if result is unknown.');">
        @csrf
        <h2 class="font-bold">3. Send the one approved recovery attempt</h2>
        <label class="block"><input type="checkbox" name="absence_confirmed" value="1" required> Fresh live Day Book absence verified</label>
        <label class="block"><input type="checkbox" name="send_once" value="1" required> I authorize one transmission; I will not click twice</label>
        <button class="border rounded p-2 bg-red-100" type="submit">Resend once to Tally -Test</button>
    </form>

    @endif
    @else
        <p class="border p-3">This export cannot be resent: reconciliation or additional review is required.</p>
    @endif
</div>
</x-app-layout>
