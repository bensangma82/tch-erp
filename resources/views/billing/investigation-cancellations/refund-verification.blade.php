<x-app-layout>
@include('billing.investigation-cancellations.refund-simple-styles')
<div class="refund-simple">
    <header class="rf-hero">
        <div><span class="rf-eyebrow">BILLING · PATIENT REFUNDS</span><h1>Refund desk</h1><p>Choose the receipt. Record payment. Independently verify.</p></div>
        <a class="rf-button rf-light" href="#refund-queue">Open refunds <span>{{ $pending->total() }}</span></a>
    </header>
    @if(session('success'))<div class="rf-alert rf-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="rf-alert rf-error" role="alert"><strong>Please check:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="rf-steps"><div><b>1</b><span><strong>Request</strong><small>Receipt and amount filled automatically</small></span></div><div><b>2</b><span><strong>Record payment</strong><small>Cashier confirms actual payment</small></span></div><div><b>3</b><span><strong>Verify & complete</strong><small>A different user checks payment evidence</small></span></div></div>
    <form class="rf-toolbar" method="GET" action="{{ route('billing.investigation-refunds.verification') }}">
        <div class="rf-search"><label for="rf-q">Find patient, hospital number or invoice</label><input id="rf-q" name="q" value="{{ $q }}" maxlength="100" placeholder="Patient name or invoice number"></div>
        <div><label for="rf-stage">Open refund stage</label><select id="rf-stage" name="stage"><option value="all" @selected($stage==='all')>All stages</option><option value="initiated" @selected($stage==='initiated')>Payment to record</option><option value="disbursed" @selected($stage==='disbursed')>Ready to verify</option><option value="submitted" @selected($stage==='submitted')>Older payments to verify</option></select></div>
        <button class="rf-button" type="submit">Search</button><a class="rf-reset" href="{{ route('billing.investigation-refunds.verification') }}">Reset</a>
    </form>

    @if($canProcess && $eligible)
    <section class="rf-section"><div class="rf-section-heading"><div><h2>Start a refund</h2><p>Executed cancellations only. The ERP calculates the refundable amount from posted records.</p></div></div>
        <div class="rf-new-list">
        @forelse($eligible as $cancellation)
            @php
                $invoice = $cancellation->invoice;
                $patient = $invoice?->patient;
                $preview = $previews[$cancellation->id];
            @endphp
            <article class="rf-new-row"><div><strong>{{ trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Patient record' }}</strong><small>{{ $patient?->uhid }} · {{ $invoice?->invoice_no }} · Cancellation #{{ $cancellation->id }}</small></div>
                <div class="rf-choices">
                @forelse($preview['choices'] as $choice)
                    <form method="POST" action="{{ route('billing.investigation-refunds.submit', $cancellation) }}">@csrf
                        <input type="hidden" name="simple_flow" value="1"><input type="hidden" name="payment_id" value="{{ $choice['payment']->id }}"><input type="hidden" name="expected_amount" value="{{ $choice['amount'] }}">
                        <small>{{ $choice['payment']->receipt_no }} · {{ strtoupper($choice['method']) }}</small>
                        <button type="submit" class="rf-button">Request ₹{{ number_format((float)$choice['amount'],2) }}</button>
                    </form>
                @empty <p class="rf-muted">{{ $preview['reason'] }}</p>
                @endforelse
                </div>
            </article>
        @empty <p class="rf-empty">No new executed cancellations awaiting a refund request.</p>
        @endforelse
        </div><div class="rf-pagination">{{ $eligible->links() }}</div>
    </section>
    @endif

    <section class="rf-section" id="refund-queue"><div class="rf-section-heading"><div><h2>Open refunds</h2><p>{{ $pending->total() }} matching records · Completed refunds leave this queue.</p></div><span class="rf-pill">Audit notes generated automatically</span></div>
    @forelse($pending as $check)
        @php
            $cancellation = $check->cancellation;
            $invoice = $cancellation?->invoice;
            $patient = $invoice?->patient;
            $payment = $check->payment;
            $mode = strtolower((string)$check->settlement_method);
            $legacy = $check->status === 'submitted';
            $ownRefund = in_array((int)auth()->id(), array_map('intval', array_filter([$check->submitted_by, $check->initiated_by, $check->disbursed_by, $cancellation?->requested_by], fn($id) => $id !== null)), true);
            $canVerify = $hasVerifyPermission && !$ownRefund;
            $proofOptions = $mode === 'cash' ? ['cash_voucher'=>'Signed cash refund voucher', 'cash_register'=>'Cash register + recipient acknowledgement'] : ($mode === 'upi' ? ['upi_confirmation'=>'UPI refund confirmation', 'bank_statement'=>'Bank statement'] : ['card_confirmation'=>'Card refund confirmation', 'bank_statement'=>'Bank statement']);
        @endphp
        <article class="rf-card">
            <div class="rf-card-heading"><div><small>REFUND #{{ $check->id }} · {{ $invoice?->invoice_no }}</small><h3>{{ trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Patient record' }}</h3><p>{{ $patient?->uhid }} · Cancellation #{{ $check->cancellation_id }}</p></div><span class="rf-pill {{ $check->status === 'initiated' ? 'rf-amber' : 'rf-teal' }}">{{ $check->status === 'initiated' ? 'Payment to record' : ($legacy ? 'Older payment · check details' : 'Ready to verify') }}</span></div>
            <div class="rf-facts"><div><small>REFUND AMOUNT</small><strong class="rf-amount">₹{{ number_format((float)$check->amount,2) }}</strong></div><div><small>ORIGINAL RECEIPT</small><strong>{{ $payment?->receipt_no ?? 'Missing receipt' }}</strong><span>₹{{ number_format((float)($payment?->amount ?? 0),2) }} received</span></div><div><small>REFUND METHOD</small><strong>{{ strtoupper($mode) }}</strong><span>Matches the original receipt</span></div></div>
            <details class="rf-details"><summary>Receipt, payment and audit details</summary><dl><dt>Requested by</dt><dd>{{ $check->initiatedBy?->name ?? $check->submittedBy?->name ?? 'Not recorded' }}</dd><dt>Payment recorded by</dt><dd>{{ $check->disbursedBy?->name ?? 'Not recorded' }}</dd><dt>Recorded payment time</dt><dd>{{ $check->disbursed_at?->format('d M Y, h:i A') ?? 'Not recorded' }}</dd><dt>Reference</dt><dd>{{ $check->settlement_reference }}</dd><dt>Recorded evidence</dt><dd>{{ $check->disbursement_evidence ?: $check->evidence_description }}</dd></dl><p>ERP references identify records. Independent payment evidence must still be examined.</p>
                @if($invoice && auth()->user()->hasPermission('billing.investigation-cancellation.approve'))<p><a class="rf-reset" href="{{ route('billing.investigation-cancellations.reconciliation',$invoice) }}">View invoice reconciliation →</a></p>@endif
            </details>

            @if($check->status === 'initiated')
                @if($canProcess)
                <form method="POST" action="{{ route('billing.investigation-refunds.disburse',$check) }}" class="rf-action">@csrf<input type="hidden" name="simple_flow" value="1">
                    <h4>Record payment to the patient</h4><p>This records a payment already made; it does not transfer money.</p>
                    @if($mode !== 'cash')
                        <label for="rf-reference-{{ $check->id }}">Actual {{ strtoupper($mode) }} refund reference</label><input id="rf-reference-{{ $check->id }}" name="settlement_reference" minlength="6" maxlength="100" required placeholder="Reference from the successful refund transaction">
                    @else
                        <p class="rf-hint">A unique internal cash voucher reference is assigned automatically. Keep the recipient acknowledgement for verification.</p>
                    @endif
                    <details class="rf-details"><summary>Add a note{{ $mode==='cash' ? ' or an existing voucher reference' : '' }} (optional)</summary>
                        @if($mode === 'cash')<label>Existing cash voucher reference<input name="settlement_reference" minlength="6" maxlength="100" placeholder="Leave blank for an internal ERP reference"></label>@endif
                        <label>Additional note<textarea name="notes" maxlength="500" rows="2"></textarea></label>
                    </details>
                    <label class="rf-check"><input type="checkbox" name="actually_paid" value="1" required><span>I actually paid ₹{{ number_format((float)$check->amount,2) }} to the patient using {{ strtoupper($mode) }}.</span></label>
                    <button class="rf-button" type="submit">Record payment</button><small class="rf-footnote">The ERP records your name and time. Accounting follows independent verification.</small>
                </form>
                @else <div class="rf-alert">Waiting for an authorized cashier to record payment.</div>
                @endif
            @else
                @if($canVerify)
                <form method="POST" action="{{ route('billing.investigation-refunds.verify',$check) }}" class="rf-action">@csrf<input type="hidden" name="simple_flow" value="1">
                    <h4>Independently verify payment</h4>
                    @if($legacy)
                        <div class="rf-alert rf-warning">This older record needs its actual payment reference and date from supporting evidence. The ERP cannot infer them.</div>
                        <div class="rf-fields"><div><label for="rf-legacy-ref-{{ $check->id }}">Actual payment / signed voucher reference</label><input id="rf-legacy-ref-{{ $check->id }}" name="legacy_payment_reference" minlength="6" maxlength="100" required></div><div><label for="rf-legacy-date-{{ $check->id }}">Actual payment date</label><input id="rf-legacy-date-{{ $check->id }}" type="date" name="legacy_payment_date" value="{{ $check->disbursed_at?->format('Y-m-d') }}" required></div></div>
                    @endif
                    <label for="rf-proof-{{ $check->id }}">Payment evidence examined</label><select id="rf-proof-{{ $check->id }}" name="proof_kind" required>@foreach($proofOptions as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                    <details class="rf-details"><summary>Add a verification note (optional)</summary><label>Additional note<textarea name="notes" maxlength="500" rows="2"></textarea></label></details>
                    <label class="rf-check"><input type="checkbox" name="evidence_checked" value="1" required><span>I independently checked the actual payment evidence, original receipt and cancellation, and confirm this refund was paid.</span></label>
                    <button class="rf-button rf-complete" type="submit">Verify & complete refund</button><small class="rf-footnote">Posts one refund journal. Does not send a voucher to Tally.</small>
                </form>
                @else
                    <div class="rf-alert rf-warning">{{ $ownRefund ? 'A different authorized user must verify this refund because you were involved in the cancellation or payment.' : 'Waiting for a user with the directly assigned refund verification permission.' }}</div>
                @endif
            @endif
        </article>
    @empty <div class="rf-empty">No open refunds match this search.</div>
    @endforelse
    <div class="rf-pagination">{{ $pending->links() }}</div></section>
    <p class="rf-footer">Original receipts are preserved. Duplicate requests and changed balances are checked again before each action.</p>
</div>
</x-app-layout>
