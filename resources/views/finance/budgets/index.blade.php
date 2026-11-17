<x-app-layout>
@include('finance.budgets._styles')
@php
    $money = static fn ($paise) => number_format($paise / 100, 2);
    $totalBudget = array_sum(array_column($report['totals'], 'budget'));
    $totalActual = array_sum(array_column($report['totals'], 'actual'));
    $overCount = $report['rows']->filter(fn ($row) => $row['remaining'] < 0)->count();
@endphp
<div class="tch-budget">
    <header class="tb-hero">
        <div><div class="tb-eyebrow">Finance · Planning &amp; control</div><h1>Annual Budgets</h1><p class="tb-muted">Recurring expenses and capital investment, with a clear view of what remains.</p></div>
        <div class="tb-year">FY {{ $year }}–{{ substr((string) ($year + 1), -2) }}</div>
    </header>
    @if(session('status'))<div class="tb-alert" role="status"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg><p>{{ session('status') }}</p></div>@endif
    @if($errors->any())<div class="tb-alert tb-alert-error" role="alert"><div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div></div>@endif
    <div class="tb-toolbar tb-no-print">
        <form method="GET"><label for="budget-year" class="tb-muted">Financial year starts</label><input id="budget-year" type="number" min="2000" max="2100" name="year" value="{{ $year }}" required><button class="tb-btn">Show year</button><span class="tb-tiny">1 Apr {{ $year }} – 31 Mar {{ $year + 1 }}</span></form>
        <div class="tb-actions"><a class="tb-btn" href="{{ route('finance.budgets.csv', ['year' => $year]) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m-4-4 4 4 4-4M5 16v4h14v-4"/></svg>Download CSV</a>@if($canManage)<a class="tb-btn tb-btn-primary" href="#annual-allocations">+ Add allocation</a>@endif</div>
    </div>
    <div class="tb-stats" aria-label="Annual budget summary">
        <section class="tb-stat"><div class="tb-stat-label">Annual allocation</div><div class="tb-stat-value">₹{{ $money($totalBudget) }}</div><p class="tb-stat-note">{{ $report['rows']->count() }} budgeted accounts · recurring &amp; capital</p></section>
        <section class="tb-stat"><div class="tb-stat-label">Used this year</div><div class="tb-stat-value">₹{{ $money($totalActual) }}</div><p class="tb-stat-note">Net posted use of allocated accounts</p></section>
        <section class="tb-stat"><div class="tb-stat-label">Available balance</div><div class="tb-stat-value {{ $totalBudget - $totalActual < 0 ? 'tb-bad' : 'tb-good' }}">₹{{ $money($totalBudget - $totalActual) }}</div><p class="tb-stat-note">{{ $overCount ? $overCount.' account(s) over budget' : 'Annual allocation less net use' }}</p></section>
    </div>
    @foreach(['recurring' => 'Recurring / operating', 'capital' => 'Capital investment'] as $kind => $label)
    @php
            $groupRows = $report['rows']->where('line.category', $kind);
        @endphp
    <section class="tb-panel {{ $kind === 'capital' ? 'tb-capital' : '' }}">
        <div class="tb-panel-head"><div class="tb-title-wrap"><span class="tb-section-icon" aria-hidden="true">@if($kind === 'capital')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-7h6v7M8 10h1m6 0h1"/></svg>@else<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 20h16M7 16V9m5 7V4m5 12v-5"/></svg>@endif</span><div><h2>{{ $label }}</h2><p class="tb-tiny">{{ $groupRows->count() }} allocated accounts</p></div></div>
        <div class="tb-group-totals"><div><span>Annual budget</span><strong>₹{{ $money($report['totals'][$kind]['budget']) }}</strong></div><div><span>Used (net)</span><strong>₹{{ $money($report['totals'][$kind]['actual']) }}</strong></div><div><span>Available</span><strong class="{{ $report['totals'][$kind]['remaining'] < 0 ? 'tb-bad' : 'tb-good' }}">₹{{ $money($report['totals'][$kind]['remaining']) }}</strong></div></div></div>
        @if($groupRows->isEmpty())<div class="tb-empty">No {{ $kind }} allocations for this year.@if($canManage) <a class="tb-link" href="#annual-allocations">Add an annual allocation</a> to start tracking.@endif</div>
        @else
        <div class="tb-scroll"><table><caption class="sr-only">{{ $label }} budget utilisation for FY {{ $year }}</caption><thead><tr><th>Account / finance head</th><th class="tb-num">Annual budget ₹</th><th class="tb-num">Used ₹</th><th class="tb-num">Available ₹</th><th>Utilisation</th><th><span class="sr-only">Details</span></th></tr></thead><tbody>
        @foreach($groupRows as $row)
        @php
            $line = $row['line'];
            $percent = $row['budget'] > 0 ? $row['actual'] * 100 / $row['budget'] : null;
            $bar = $percent === null ? ($row['actual'] > 0 ? 100 : 0) : max(0, min(100, $percent));
            $over = $row['remaining'] < 0;
            $near = !$over && $percent !== null && $percent >= 80;
        @endphp
        <tr><td class="tb-account"><span class="tb-account-code">GL {{ $line->ledger->account_code }}</span><a class="tb-account-name tb-link" href="{{ route('finance.budgets.show', $line->id) }}">{{ $line->ledger->account_name }}</a>@if($line->ledger->financeHead?->name)<p class="tb-subline">{{ $line->ledger->financeHead->name }}</p>@endif @if(!$line->ledger->is_active)<span class="tb-badge tb-badge-warn">Inactive account</span>@endif</td>
            <td class="tb-num">{{ $money($row['budget']) }}@if($line->original_amount !== $line->current_amount)<div class="tb-subline">Original {{ $money(\App\Services\Budget\AnnualBudgetService::paise($line->original_amount)) }}</div>@endif</td><td class="tb-num">{{ $money($row['actual']) }}</td><td class="tb-num tb-available {{ $over ? 'tb-bad' : '' }}">{{ $money($row['remaining']) }}</td>
            <td class="tb-usage"><div class="tb-actions" style="justify-content:space-between;gap:5px"><span class="tb-tiny">{{ $percent === null ? 'No allocation value' : number_format($percent, 1).'% used' }}</span><span class="tb-badge {{ $over ? 'tb-badge-bad' : ($near ? 'tb-badge-warn' : '') }}">{{ $over ? 'Over budget' : ($near ? '80% or more used' : 'Within budget') }}</span></div><div class="tb-track" role="progressbar" aria-label="{{ $line->ledger->account_name }} budget used" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($bar) }}" aria-valuetext="{{ $percent === null ? 'Zero annual allocation' : number_format($percent, 1).'% used' }}"><div class="tb-fill {{ $over ? 'tb-fill-bad' : ($near ? 'tb-fill-warn' : '') }}" style="width:{{ number_format($bar, 2, '.', '') }}%"></div></div></td>
            <td class="tb-row-end"><a href="{{ route('finance.budgets.show', $line->id) }}" aria-label="View {{ $line->ledger->account_name }} budget details"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></a></td></tr>
        @endforeach
        </tbody></table></div>
        @endif
        <div class="tb-footnote">{{ $kind === 'recurring' ? 'Posted operating costs, net of credits and reversals.' : 'Posted movements in budgeted fixed-asset and capital-work accounts. Asset registration alone is excluded.' }}</div>
    </section>
    @endforeach
    @if($report['unbudgeted']->isNotEmpty())
    <section class="tb-panel tb-warning"><div class="tb-panel-head"><div><h2>Spending without an annual allocation</h2><p class="tb-muted">These accounts are outside the summary totals above.</p></div><span class="tb-badge tb-badge-warn">{{ $report['unbudgeted']->count() }} accounts to review</span></div><div class="tb-scroll"><table><thead><tr><th>Account</th><th>Budget category</th><th class="tb-num">Net used ₹</th></tr></thead><tbody>@foreach($report['unbudgeted'] as $row)<tr><td><span class="tb-account-code">GL {{ $row['ledger']->account_code }}</span><span class="tb-account-name">{{ $row['ledger']->account_name }}</span></td><td>{{ ucfirst($row['category']) }}</td><td class="tb-num">{{ $money($row['actual']) }}</td></tr>@endforeach</tbody></table></div></section>
    @endif
    <details class="tb-help"><summary>How budget usage is calculated</summary><div class="tb-help-body"><p>Used = posted debits less credits to each budgeted GL account, dated within 1 April {{ $year }}–31 March {{ $year + 1 }}. Existing and future-dated posted entries within the year are included. Drafts are excluded.</p><p>Supplier payments do not deduct an expense again. Credits and reversals restore availability in their accounting year. Purchase orders and other unposted commitments are excluded.</p><p>Capital use requires a posted fixed-asset or capital-work journal. Registering an item in Asset Management alone does not consume its budget.</p><p>Budgets are planning limits, not cash balances. Exceeding an allocation is highlighted; it does not block accounting.</p></div></details>
    @if($canManage)
    <section class="tb-panel tb-no-print" id="annual-allocations"><div class="tb-panel-head"><div><h2>Enter annual allocations</h2><p class="tb-muted">FY {{ $year }}–{{ $year + 1 }} · Set the approved amount for each account.</p></div><span class="tb-badge tb-badge-blue">{{ $available->count() }} accounts available</span></div>
        <form method="POST" action="{{ route('finance.budgets.store') }}">@csrf<input type="hidden" name="year" value="{{ $year }}"><div class="tb-allocation-table"><table><thead><tr><th>GL account / finance head</th><th>Category</th><th class="tb-num">Annual allocation ₹</th></tr></thead><tbody>
        @foreach(['recurring' => 'Recurring / operating', 'capital' => 'Capital investment'] as $category => $categoryLabel)
        @php
            $choices = $available->where('category', $category);
        @endphp
        @if($choices->isNotEmpty())<tr class="tb-section-label"><td colspan="3">{{ $categoryLabel }}</td></tr>@foreach($choices as $id => $item)<tr><td><label for="allocation-{{ $id }}"><span class="tb-account-code">GL {{ $item['ledger']->account_code }}</span><span class="tb-account-name">{{ $item['ledger']->account_name }}</span><span class="tb-subline">{{ $item['ledger']->financeHead?->name }}</span></label></td><td><span class="tb-badge {{ $category === 'capital' ? '' : 'tb-badge-blue' }}">{{ ucfirst($category) }}</span></td><td class="tb-num"><input class="tb-amount-input" id="allocation-{{ $id }}" name="allocations[{{ $id }}]" type="number" min="0" step="0.01" max="99999999999999.99" value="{{ old('allocations.'.$id) }}" placeholder="Enter annual amount" aria-label="Annual budget for {{ $item['ledger']->account_name }}"></td></tr>@endforeach @endif
        @endforeach
        @if($available->isEmpty())<tr><td colspan="3" class="tb-empty">All eligible accounts already have allocations. Open an account above to revise its budget.</td></tr>@endif
        </tbody></table></div><div class="tb-form-footer"><p class="tb-tiny">Leave unused rows blank. Existing budgets are revised from their account detail page.</p>@if($available->isNotEmpty())<button class="tb-btn tb-btn-teal">Save annual allocations</button>@endif</div></form>
        <div class="tb-allocation-note">Missing a capital account? Use an active posting Asset GL under <strong>Fixed Assets</strong> or <strong>Capital Work in Progress</strong>, including parent groups. Bank, receivables and inventory are excluded.</div>
    </section>
    @endif
    <div class="tb-toolbar tb-no-print"><span class="tb-tiny">TCH Finance · Annual budget monitoring</span><a class="tb-link" href="{{ route('finance.dashboard') }}">← Finance dashboard</a></div>
</div>
</x-app-layout>
