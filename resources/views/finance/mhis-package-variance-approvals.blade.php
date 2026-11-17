<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>MHIS Package Review</title>
<style>
*{box-sizing:border-box}
body{
  margin:0;padding:40px 32px 70px;
  background:#f1f5f9;color:#1e293b;
  font:14px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif
}
body>h1,body>p,body>section,body>.error,body>[role="status"]{
  max-width:1280px;margin-left:auto;margin-right:auto
}
h1{
  margin-top:0;margin-bottom:0;
  padding:37px 40px 8px;
  background:linear-gradient(115deg,#0f172a,#1e3a8a);
  border-radius:20px 20px 0 0;
  color:#fff;font-size:27px;font-weight:750;letter-spacing:-.7px
}
body>h1+p{
  margin-top:0;padding:0 40px 35px;
  background:linear-gradient(115deg,#0f172a,#1e3a8a);
  border-radius:0 0 20px 20px;
  color:#cbd5e1;font-size:14px;
  box-shadow:0 12px 28px rgba(15,23,42,.12)
}
section{
  background:#fff;border:1px solid #e2e8f0;
  border-radius:18px;padding:28px 32px;
  margin-top:25px;margin-bottom:25px;
  box-shadow:0 5px 22px rgba(15,23,42,.045)
}
h2{
  font-size:19px;font-weight:700;letter-spacing:-.3px;
  margin:0 0 22px;padding-bottom:16px;
  border-bottom:1px solid #e2e8f0;color:#0f172a
}
label{display:block;font-size:13px;font-weight:650;color:#334155}
input:not([type=checkbox]),textarea{
  display:block;width:100%;max-width:680px;
  border:1px solid #cbd5e1;border-radius:10px;
  background:#fff;padding:12px 14px;
  margin:7px 0 19px;color:#0f172a;
  font:14px system-ui,sans-serif;
  transition:border-color .2s,box-shadow .2s
}
input:focus,textarea:focus{
  outline:none;border-color:#6366f1;
  box-shadow:0 0 0 3px rgba(99,102,241,.12)
}
textarea{min-height:90px;resize:vertical}
input[type=checkbox]{
  width:17px;height:17px;vertical-align:middle;
  margin-right:9px;accent-color:#4f46e5
}
button{
  background:#4f46e5;border:0;border-radius:10px;
  color:#fff;padding:12px 22px;font-weight:700;
  font-size:13px;cursor:pointer;
  box-shadow:0 4px 10px rgba(79,70,229,.15);
  transition:background .2s,transform .2s
}
button:hover{background:#3730a3;transform:translateY(-1px)}
table{border-collapse:separate;border-spacing:0;width:100%}
thead{background:#f8fafc}
th{
  color:#64748b;font-size:11px;
  text-transform:uppercase;letter-spacing:.7px;
  font-weight:750;text-align:left;padding:14px 12px;
  border-bottom:1px solid #e2e8f0
}
td{
  padding:15px 12px;border-bottom:1px solid #f1f5f9;
  font-size:13px;vertical-align:middle
}
tbody tr:hover{background:#f8fafc}
td button{padding:8px 15px;background:#059669;box-shadow:none}
td button:hover{background:#047857}
.error,[role=status]{
  padding:15px 20px;border-radius:12px;margin-top:20px
}
.error{background:#fff1f2;color:#9f1239;border:1px solid #fecdd3}
[role=status]{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
@media(max-width:800px){
  body{padding:16px 12px}
  h1{padding:25px 20px 8px;font-size:23px}
  body>h1+p{padding:0 20px 25px}
  section{padding:20px 15px;overflow-x:auto}
  table{min-width:900px}
}
</style><style>select{padding:12px;width:100%;max-width:680px;border:1px solid #cbd5e1;border-radius:10px;margin:7px 0 19px}.amounts{display:flex;flex-wrap:wrap;gap:24px;margin-bottom:20px}.amounts strong{display:block;font-size:22px}a{color:#4338ca}details{margin:12px 0}small{color:#475569}.review-table{overflow-x:auto}</style>
</head><body>
<h1>MHIS Package Review</h1>
<p>Select a patient bill, check the difference and submit for independent Finance approval.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($selected)
<section>
    <h2>{{ trim(($selected->patient?->first_name ?? '').' '.($selected->patient?->last_name ?? '')) }} · {{ $selected->patient?->uhid }}</h2>
    <p>Bill account: {{ $selected->billingAccount?->account_no }} · MHIS registration: {{ $selected->mhis_registration_id ?: 'Not recorded' }} · Approval date: {{ $selected->approval_date?->format('d M Y') }}</p>
    <p>Package: {{ $selected->package_code }} {{ $selected->package_name }}</p>
    <a href="{{ route('ip-billing.show', $selected->admission_id) }}">Back to patient bill</a>
    @if($problem)<p class="error">{{ $problem }}</p>@endif
    @if($snapshot)
    <div class="amounts">
        <div>Hospital bill<strong>₹{{ number_format($snapshot['bill_amount'], 2) }}</strong></div>
        <div>MHIS approval<strong>₹{{ number_format($snapshot['approved_amount'], 2) }}</strong></div>
        <div>{{ $snapshot['treatment'] === 'contractual_allowance' ? 'Shortfall' : 'Additional reimbursement' }}<strong>₹{{ number_format(abs((float) $snapshot['variance_amount']), 2) }}</strong></div>
    </div>
    @endif
    @if($review)
        <p>Review status: <strong>{{ ucfirst($review->status) }}</strong> · Submitted by user #{{ $review->created_by }} · Approved by {{ $review->approved_by ?: 'Awaiting independent review' }}</p>
        @if($review->status === 'approved')<p>Approval is recorded. Finalization checks that the current bill and approval still match this snapshot.</p>@endif
        @foreach($events->where('action', 'returned') as $event)<p class="error">Returned for correction: {{ $event->notes }}</p>@endforeach
        <details><summary>Review evidence and history</summary>
            <p>{{ $review->reason }}</p><p>{{ $review->supporting_evidence }}</p>
            @foreach($events as $event)<p>{{ $event->created_at }} · {{ $event->action }} · user #{{ $event->actor_id }} · {{ $event->notes }}</p>@endforeach
        </details>
    @endif
    @if($snapshot && auth()->user()->hasPermission('finance.mhis-adjustment.post') && (!$review || ($review->status === 'returned' && (int) $review->created_by === (int) auth()->id())))
    <form method="post" action="{{ route('finance.mhis-package-variance.store') }}">
        @csrf
        <input type="hidden" name="claim_id" value="{{ $selected->id }}">
        <input type="hidden" name="expected_bill" value="{{ $snapshot['bill_amount'] }}">
        <input type="hidden" name="expected_approved" value="{{ $snapshot['approved_amount'] }}">
        @php($automaticReference = \App\Services\Accounting\MhisPackageVarianceApprovalService::recordedReference($selected))
        @if($automaticReference !== '')
        <p><strong>Reference:</strong> {{ $automaticReference }} <small>From the recorded authorisation, claim or MHIS registration ID.</small></p>
        @else
        <label>Missing claim reference<input name="supporting_reference" maxlength="255" required value="{{ old('supporting_reference') }}"></label>
        <small>No authorisation, claim or registration reference is recorded. Enter a real traceable reference or update the claim.</small>
        @endif
        <p><strong>Reason:</strong> Recorded MHIS approval differs from the hospital bill. The exact amounts are attached automatically.</p>
        <p><small>Evidence is generated from ERP records; this does not fetch or verify documents from the MHIS portal.</small></p>
        <label>Additional notes (optional)<textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
        @if($snapshot['treatment'] === 'contractual_allowance')
        <label><input type="checkbox" name="contractual_nonrecoverable" value="1" required @checked(old('contractual_nonrecoverable'))> I checked the package terms: this shortfall is contractually non-recoverable, not an unpaid instalment or pending claim.</label>
        @endif
        <p>Amounts, recorded claim details, submitter and time are attached automatically. Submission does not post an allowance.</p>
        <button type="submit">{{ $review ? 'Resubmit for Finance review' : 'Submit for Finance review' }}</button>
    </form>
    @endif
    @if($review && $review->status === 'submitted' && auth()->user()->hasPermission('finance.mhis-adjustment.approve'))
        @if((int) $review->created_by !== (int) auth()->id())
        <form method="post" action="{{ route('finance.mhis-package-variance.approve', $review) }}">@csrf
            <input type="hidden" name="decision" value="approve"><button type="submit">Approve package difference</button>
        </form>
        <details><summary>Return for correction</summary>
            <form method="post" action="{{ route('finance.mhis-package-variance.approve', $review) }}">@csrf
                <input type="hidden" name="decision" value="return">
                <label>What needs correction?<textarea name="return_notes" maxlength="2000" required></textarea></label>
                <button type="submit">Return to submitter</button>
            </form>
        </details>
        @else<p>A different authorised Finance user must review your submission.</p>@endif
    @endif
</section>
@endif
<section><h2>Patient bills with package differences</h2>
<form method="get"><label>Search patient name or hospital number<input name="q" value="{{ $q }}" maxlength="100"></label><button>Search</button></form>
<div class="review-table"><table><thead><tr><th>Patient</th><th>Hospital number</th><th>Bill account</th><th>Hospital bill</th><th>MHIS approval</th><th>Difference</th><th></th></tr></thead><tbody>
@forelse($claims as $claim)<tr>
<td>{{ trim(($claim->patient?->first_name ?? '').' '.($claim->patient?->last_name ?? '')) }}</td><td>{{ $claim->patient?->uhid }}</td><td>{{ $claim->billingAccount?->account_no }}</td>
<td>₹{{ number_format($claim->billingAccount?->net_amount ?? 0, 2) }}</td><td>₹{{ number_format($claim->approved_amount, 2) }}</td><td>₹{{ number_format(abs((float) $claim->approved_amount - (float) $claim->billingAccount?->net_amount), 2) }}</td>
<td><a href="{{ route('finance.mhis-package-variance.index', ['claim_id' => $claim->id]) }}">Review</a></td></tr>
@empty<tr><td colspan="7">No matching approved MHIS Free bills with a package difference.</td></tr>@endforelse
</tbody></table></div>{{ $claims->links() }}</section>
<section><h2>Finance review register</h2><div class="review-table"><table><thead><tr><th>Patient / claim</th><th>Bill</th><th>MHIS approved</th><th>Difference</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($items as $a)<tr><td>{{ $a->claim?->patient?->uhid }} / #{{ $a->ip_billing_mhis_claim_id }}</td><td>{{ $a->bill_amount }}</td><td>{{ $a->approved_amount }}</td><td>{{ $a->variance_amount }}</td><td>{{ ucfirst($a->status) }}</td><td><a href="{{ route('finance.mhis-package-variance.index', ['claim_id' => $a->ip_billing_mhis_claim_id]) }}">Open review</a></td></tr>@endforeach
</tbody></table></div>{{ $items->links() }}</section>
</body></html>
