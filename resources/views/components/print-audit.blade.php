<div class="print-audit" style="font-size: 11px; line-height: 1.4; margin-top: 8px;">
    <div>
        Printed by: <strong>{{ auth()->user()?->name ?? 'System' }}</strong>
    </div>
    <div>
        Printed on: <strong>{{ now()->format('d M Y, h:i A') }}</strong>
    </div>
</div>