{{-- Read-only presentation of existing MIS amounts; no additional database queries or postings. --}}
@php
    $welfareRevenueBasis = (float) $financeRecognizedRevenue;
    $welfareCharityAmount = (float) $charityAppliedAmount;
    $welfareStaffAmount = (float) $staffBenefitNetUtilization;
    $welfareCharityPercent = $welfareRevenueBasis > 0
        ? round(($welfareCharityAmount / $welfareRevenueBasis) * 100, 2) : null;
    $welfareStaffPercent = $welfareRevenueBasis > 0
        ? round(($welfareStaffAmount / $welfareRevenueBasis) * 100, 2) : null;
@endphp
@once
<style>
.mis-welfare-share{margin:20px 0;color:#192d45}.mis-welfare-share *{box-sizing:border-box}.mis-welfare-share .mws-heading{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;margin-bottom:13px}.mis-welfare-share h4{font-size:15px;font-weight:750;margin:0}.mis-welfare-share p{margin:0}.mis-welfare-share .mws-muted{font-size:12px;line-height:1.6;color:#65778c}.mis-welfare-share .mws-period{font-size:11px;color:#687d94;background:#f1f5fa;border:1px solid #e3eaf2;padding:5px 9px;border-radius:7px}.mis-welfare-share .mws-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.mis-welfare-share .mws-card{position:relative;overflow:hidden;background:white;border:1px solid #e0e7f0;border-radius:15px;padding:21px 23px;box-shadow:0 2px 5px #172b4504}.mis-welfare-share .mws-card:before{content:'';position:absolute;top:0;left:0;bottom:0;width:4px;background:#7a56b0}.mis-welfare-share .mws-staff:before{background:#18877b}.mis-welfare-share .mws-label{font-size:12px;color:#665085;font-weight:700;line-height:1.5}.mis-welfare-share .mws-staff .mws-label{color:#226f67}.mis-welfare-share .mws-values{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-top:15px}.mis-welfare-share .mws-percent{font-size:32px;line-height:1.1;font-weight:750;letter-spacing:-.8px;font-variant-numeric:tabular-nums;color:#7045a8}.mis-welfare-share .mws-staff .mws-percent{color:#157c71}.mis-welfare-share .mws-amount{font-size:19px;line-height:1.3;font-weight:700;font-variant-numeric:tabular-nums;color:#273e59}.mis-welfare-share .mws-amount-wrap{text-align:right}.mis-welfare-share .mws-desc{font-size:11px;color:#7a8799;margin-top:7px;line-height:1.5}.mis-welfare-share .mws-basis{display:flex;gap:8px 20px;justify-content:space-between;flex-wrap:wrap;border:1px solid #e0e7f0;background:#f7f9fc;border-radius:9px;padding:11px 15px;margin-top:12px;font-size:12px;color:#5b718a}.mis-welfare-share .mws-basis strong{color:#2b4665;font-variant-numeric:tabular-nums}.mis-welfare-share .mws-na{color:#7c8795!important}.mis-welfare-share details{font-size:11px;color:#738297;margin-top:9px}.mis-welfare-share summary{cursor:pointer;font-weight:600}.mis-welfare-share details p{margin-top:7px;line-height:1.7}.mis-welfare-share .mws-note{font-size:11px;margin-top:8px;color:#82632a;line-height:1.6}@media(max-width:680px){.mis-welfare-share .mws-grid{grid-template-columns:1fr}.mis-welfare-share .mws-card{padding:19px}.mis-welfare-share .mws-percent{font-size:29px}.mis-welfare-share .mws-amount{font-size:17px}}
</style>
@endonce
<section class="mis-welfare-share" aria-label="Charity and staff medical benefit percentages">
    <div class="mws-heading"><div><h4>Charity &amp; staff benefits — share of revenue</h4><p class="mws-muted">Actual assistance and net benefits used in the selected period.</p></div><span class="mws-period">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} – {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</span></div>
    <div class="mws-grid">
        <article class="mws-card">
            <h5 class="mws-label">Charity % of revenue</h5>
            <div class="mws-values"><div><p class="mws-percent {{ $welfareCharityPercent === null ? 'mws-na' : '' }}">{{ $welfareCharityPercent === null ? 'N/A' : number_format($welfareCharityPercent, 2).'%' }}</p><p class="mws-desc">of recognized revenue</p></div><div class="mws-amount-wrap"><p class="mws-amount">₹{{ number_format($welfareCharityAmount, 2) }}</p><p class="mws-desc">Charity actually applied</p></div></div>
        </article>
        <article class="mws-card mws-staff">
            <h5 class="mws-label">Staff medical benefit % of revenue</h5>
            <div class="mws-values"><div><p class="mws-percent {{ $welfareStaffPercent === null ? 'mws-na' : '' }}">{{ $welfareStaffPercent === null ? 'N/A' : number_format($welfareStaffPercent, 2).'%' }}</p><p class="mws-desc">of recognized revenue</p></div><div class="mws-amount-wrap"><p class="mws-amount">₹{{ number_format($welfareStaffAmount, 2) }}</p><p class="mws-desc">Net benefit utilized after reversals</p></div></div>
            @if($welfareStaffAmount < 0)<p class="mws-note">Recorded reversals exceed utilization in this period.</p>@endif
        </article>
    </div>
    <div class="mws-basis"><span>Recognized revenue basis: <strong>₹{{ number_format($welfareRevenueBasis, 2) }}</strong></span><span>Percentage = amount ÷ recognized revenue × 100</span></div>
    @if($welfareRevenueBasis <= 0)<p class="mws-note">Percentages are N/A because recognized revenue is zero or negative. Assistance amounts remain visible.</p>@endif
    <details><summary>Calculation details</summary><p>Charity uses applied assistance, excluding pending and approved-but-unapplied requests. Staff benefits use active utilization less active reversals; unused entitlement is excluded. Charity uses application dates, staff benefits use transaction dates, and recognized revenue uses posted journal dates. These are period comparisons, not matched invoice cohorts or additional accounting entries. MHIS receivables and bank collections are not treated as fresh revenue by these indicators.</p></details>
</section>
