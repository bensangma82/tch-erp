<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>MHIS Package Variance Finance Review</title>

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
</style>
</head><body>
<h1>MHIS Package Variance — Finance Review</h1>
<p>Approved MHIS Free claims with non-zero package differences require independent Finance authorisation before GL posting.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(auth()->user()->hasPermission('finance.mhis-adjustment.post'))
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
@if($a->status === 'submitted' && auth()->user()->hasPermission('finance.mhis-adjustment.approve') && (int)auth()->id() !== (int)$a->created_by)
<form method="post" action="{{ route('finance.mhis-package-variance.approve', $a) }}">@csrf<button>Approve</button></form>
@endif</td></tr>@endforeach</tbody></table>{{ $items->links() }}</section></body></html>
