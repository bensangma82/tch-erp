<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>MHIS Package Variance Finance Review</title>
<style>body{font:15px system-ui,sans-serif;margin:2rem;max-width:1200px}table{border-collapse:collapse;width:100%}th,td{padding:.6rem;border-bottom:1px solid #ddd;text-align:left}input,textarea{display:block;width:100%;box-sizing:border-box;padding:.5rem;margin:.2rem 0 1rem}button{padding:.6rem 1rem;cursor:pointer}section{margin:2rem 0} .error{color:#9a1e1e}</style></head><body>
<h1>MHIS Package Variance — Finance Review</h1>
<p>Approved MHIS Free claims with non-zero package differences require independent Finance authorisation before GL posting.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(auth()->user()->can('finance.mhis-adjustment.post'))
<section><h2>Submit a claim variance</h2>
<form method="post" action="{{ route('finance.mhis-package-variance.store') }}">@csrf
<label>MHIS claim ID<input type="number" name="claim_id" min="1" required value="{{ old('claim_id') }}"></label>
<label>Approval / package supporting reference<input name="supporting_reference" maxlength="255" required value="{{ old('supporting_reference') }}"></label>
<label>Reason for the difference<textarea name="reason" required>{{ old('reason') }}</textarea></label>
<label>Evidence (approval number, document references, package terms)<textarea name="supporting_evidence" required>{{ old('supporting_evidence') }}</textarea></label>
<label><input style="display:inline;width:auto" type="checkbox" name="contractual_nonrecoverable" value="1" @checked(old('contractual_nonrecoverable'))> For a shortfall: confirm the amount is contractually non-recoverable (not merely pending/rejected).</label>
<button type="submit">Submit for Finance approval</button></form></section>@endif
<section><h2>Variance approval register</h2>
<table><thead><tr><th>Claim</th><th>Bill</th><th>Approved</th><th>Variance</th><th>Treatment</th><th>Reference</th><th>Maker</th><th>Checker</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($items as $a)<tr><td>{{ $a->ip_billing_mhis_claim_id }}</td><td>{{ $a->bill_amount }}</td><td>{{ $a->approved_amount }}</td><td>{{ $a->variance_amount }}</td><td>{{ $a->treatment }}</td><td>{{ $a->supporting_reference }}</td><td>{{ $a->created_by }}</td><td>{{ $a->approved_by ?? '—' }}</td><td>{{ $a->status }}</td><td>
@if($a->status === 'submitted' && auth()->user()->can('finance.mhis-adjustment.approve') && (int)auth()->id() !== (int)$a->created_by)
<form method="post" action="{{ route('finance.mhis-package-variance.approve', $a) }}">@csrf<button>Approve</button></form>
@endif</td></tr>@endforeach</tbody></table>{{ $items->links() }}</section></body></html>
