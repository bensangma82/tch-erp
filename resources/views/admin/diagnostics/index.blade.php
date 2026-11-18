<x-app-layout>
@include('admin.diagnostics.styles')
<div class="erp-diag">
    <header class="ed-hero"><div><span>SYSTEM OPERATIONS · ADMINISTRATION</span><h1>ERP Diagnostics</h1><p>Understand errors, run safe repairs and track the result.</p></div><a class="ed-button ed-light" href="{{ route('admin.erp-diagnostics.index') }}">Scan now</a></header>
    @if(session('success'))<div class="ed-alert ed-ok" role="status">{{ session('success') }}</div>@endif
    @if(session('diagnostic_warning'))<div class="ed-alert ed-warning" role="alert">{{ session('diagnostic_warning') }}</div>@endif
    @if($errors->any())<div class="ed-alert ed-error" role="alert"><strong>Please check</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @foreach($scan['warnings'] as $warning)<div class="ed-alert ed-warning">{{ $warning }}</div>@endforeach
    <div class="ed-stats"><article><small>OPEN INCIDENTS</small><strong>{{ $stats['open'] }}</strong><span>Grouped errors in the scanned window</span></article><article><small>SAFE MAINTENANCE AVAILABLE</small><strong>{{ $stats['safe'] }}</strong><span>Compiled view or route cache only</span></article><article><small>REVIEW REQUIRED</small><strong>{{ $stats['review'] }}</strong><span>Guided workflow or technical review</span></article><article><small>VERIFIED RESOLVED</small><strong>{{ $stats['resolved'] }}</strong><span>Confirmed by an administrator</span></article></div>
    <section class="ed-section"><div class="ed-heading"><div><h2>System health</h2><p>Current runtime checks. No financial transactions are changed.</p></div><form method="POST" action="{{ route('admin.erp-diagnostics.tally') }}">@csrf<button class="ed-button ed-secondary" type="submit">Check Tally connection</button></form></div>
        @if(session('tally_check'))<div class="ed-alert {{ session('tally_check.status')==='ok'?'ed-ok':'ed-warning' }}"><strong>Tally connection:</strong> {{ session('tally_check.detail') }}</div>@endif
        <div class="ed-health">@foreach($checks as $check)<article><div><strong>{{ $check['name'] }}</strong><span class="ed-badge ed-{{ $check['status'] }}">{{ strtoupper($check['status']) }}</span></div><p>{{ $check['detail'] }}</p></article>@endforeach</div>
        <p class="ed-help">The Tally check makes a connection request only. A response is not proof of the loaded company or a voucher’s presence.</p>
    </section>
    <section class="ed-section"><div class="ed-heading"><div><h2>Error desk</h2><p>Last {{ config('erp_diagnostics.lookback_days',7) }} days · {{ $scan['files'] }} log file(s) · {{ number_format($scan['bytes']/1024) }} KiB scanned. Counts cover this bounded scan, not the entire history.</p></div></div>
        <form method="GET" action="{{ route('admin.erp-diagnostics.index') }}" class="ed-toolbar"><div><label for="ed-q">Search error or module</label><input id="ed-q" name="q" value="{{ $q }}" maxlength="80" placeholder="Tally, database, view…"></div><div><label for="ed-status">Status</label><select id="ed-status" name="status"><option value="open" @selected($status==='open')>Open</option><option value="resolved" @selected($status==='resolved')>Verified resolved</option><option value="all" @selected($status==='all')>All</option></select></div><button class="ed-button" type="submit">Filter</button><a class="ed-reset" href="{{ route('admin.erp-diagnostics.index') }}">Reset</a></form>
        @if(!$scan['has_files'] && !$unavailable)<div class="ed-alert ed-warning">No supported local Laravel log files were found. If errors are sent to another logging service, they are outside this scan.</div>@endif
        @forelse($incidents as $incident)
        <article class="ed-incident"><div class="ed-incident-top"><div><small>{{ $incident['module'] }} · {{ $incident['code'] }}</small><h3>{{ $incident['title'] }}</h3></div><span class="ed-badge {{ $incident['resolved']?'ed-ok':($incident['action']?'ed-info':'ed-warning') }}">{{ $incident['resolved']?'Verified resolved':($incident['action']?'Safe maintenance available':'Review required') }}</span></div>
            <div class="ed-meta"><span><b>{{ $incident['count'] }}</b> occurrence(s) in scan</span><span>First: {{ $incident['first_seen'] }}</span><span>Last: {{ $incident['last_seen'] }}</span></div>
            <div class="ed-solution"><small>SUGGESTED SOLUTION</small><p>{{ $incident['solution'] }}</p></div>
            @if(!$incident['resolved'])
            <div class="ed-actions">
                @if($incident['action'])
                <form method="POST" action="{{ route('admin.erp-diagnostics.repair') }}">@csrf<input type="hidden" name="incident" value="{{ $incident['id'] }}"><input type="hidden" name="revision" value="{{ $incident['revision'] }}"><input type="hidden" name="operation_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><input type="hidden" name="action" value="{{ $incident['action'] }}"><button class="ed-button" type="submit">{{ $incident['action']==='views_clear'?'Clear compiled views':'Clear route cache' }}</button></form>
                @endif
                @if($incident['route'] && \Illuminate\Support\Facades\Route::has($incident['route']))<a class="ed-button ed-secondary" href="{{ route($incident['route']) }}">Open review screen →</a>@endif
                @if(!$incident['action'] && !$incident['route'])<span class="ed-manual">Technical or server review required</span>@endif
            </div>
            <details class="ed-details"><summary>Verify resolution / incident details</summary><p><strong>Incident:</strong> <code>{{ substr($incident['id'],0,16) }}</code></p><p><strong>Source:</strong> {{ $incident['file'] ?? 'Not identified in scanned log entry' }}{{ $incident['line']?':'.$incident['line']:'' }}</p><p>Raw messages, SQL values and request payloads are not displayed. Automatic maintenance does not close an incident.</p><form method="POST" action="{{ route('admin.erp-diagnostics.resolve') }}">@csrf<input type="hidden" name="incident" value="{{ $incident['id'] }}"><input type="hidden" name="revision" value="{{ $incident['revision'] }}"><input type="hidden" name="operation_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><label class="ed-check"><input type="checkbox" name="screen_checked" value="1" required><span>I reopened the affected screen and verified that the original problem is resolved.</span></label><button class="ed-button ed-secondary" type="submit">Mark verified resolved</button></form></details>
            @endif
        </article>
        @empty<div class="ed-empty">{{ $unavailable?'Diagnostics could not read its state. Review the storage warning above.':'No incidents match this filter in the scanned log window.' }}</div>
        @endforelse
    </section>
    <section class="ed-section"><div class="ed-heading"><div><h2>Action history</h2><p>Administrator identity, time and result are recorded before and after maintenance.</p></div></div><div class="ed-table-scroll"><table><thead><tr><th>Time</th><th>Administrator</th><th>Action</th><th>Result</th><th>Incident</th></tr></thead><tbody>@forelse($history as $event)<tr><td>{{ $event['time'] }}</td><td>User #{{ $event['actor_id'] ?? '—' }}</td><td>{{ str_replace('_',' ',$event['action']??'') }}</td><td>{{ str_replace('_',' ',$event['result']??'') }}</td><td>{{ $event['title'] ?? 'Connection check' }}</td></tr>@empty<tr><td colspan="5">No diagnostic actions have been recorded.</td></tr>@endforelse</tbody></table></div></section>
    <p class="ed-footer">Financial postings, refunds, permissions, migrations, queued jobs and Tally transmissions use their own controlled workflows. Logs are never deleted by this page.</p>
</div>
</x-app-layout>
