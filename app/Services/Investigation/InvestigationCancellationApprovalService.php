<?php

namespace App\Services\Investigation;

use App\Models\InvestigationCancellationRequest;
use App\Models\InvestigationMsApprover;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stage 2: records requests and approval decisions ONLY.
 * No invoices, diagnostic results, payments or accounting journals are modified.
 */
class InvestigationCancellationApprovalService
{
    public function request(InvoiceItem $item, User $actor, string $reason): InvestigationCancellationRequest
    {
        $this->requirePermission($actor, 'billing.investigation-cancellation.request');
        $reason = trim($reason);
        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['reason' => 'Provide a reason of at least 10 characters.']);
        }

        return DB::transaction(function () use ($item, $actor, $reason) {
            $item = InvoiceItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $invoice = $item->invoice()->lockForUpdate()->firstOrFail();
            if ($invoice->invoice_type !== 'investigation') {
                throw ValidationException::withMessages(['invoice' => 'Only investigation invoice items are eligible.']);
            }
            $orderItem = $item->serviceOrderItem()->with(['diagnosticSample', 'diagnosticResult'])->first();
            if (! $orderItem) {
                throw ValidationException::withMessages(['investigation' => 'A linked investigation is required.']);
            }
            if (InvestigationCancellationRequest::query()
                ->where('invoice_item_id', $item->id)
                ->whereIn('status', ['pending', 'approved', 'executed'])
                ->exists()) {
                throw ValidationException::withMessages(['investigation' => 'An active or completed cancellation already exists.']);
            }
            $status = (string) $orderItem->status;
            if (! in_array($status, ['ordered', 'in_process', 'completed'], true)) {
                throw ValidationException::withMessages(['investigation' => 'Unknown investigation status; cancellation blocked.']);
            }
            $sample = $orderItem->diagnosticSample;
            $collected = $sample && $sample->status === 'collected';
            $result = $orderItem->diagnosticResult;
            $requiresMs = $status !== 'ordered' || $collected || ($result !== null);
            $amount = (string) $item->amount;
            if (! is_numeric($amount) || (float) $amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Investigation amount must be positive.']);
            }
            return InvestigationCancellationRequest::query()->create([
                'invoice_id' => $invoice->id,
                'invoice_item_id' => $item->id,
                'service_order_item_id' => $orderItem->id,
                'clinical_status_at_request' => $status,
                'sample_collected_at_request' => (bool) $collected,
                'requires_ms_approval' => $requiresMs,
                'requested_amount' => $amount,
                'status' => 'pending',
                'reason' => $reason,
                'requested_by' => $actor->id,
            ]);
        }, 3);
    }

    public function decide(
        InvestigationCancellationRequest $request,
        User $actor,
        bool $approve,
        string $decisionReason
    ): InvestigationCancellationRequest {
        $decisionReason = trim($decisionReason);
        if (mb_strlen($decisionReason) < 10) {
            throw ValidationException::withMessages(['decision_reason' => 'Provide a decision reason of at least 10 characters.']);
        }
        return DB::transaction(function () use ($request, $actor, $approve, $decisionReason) {
            $request = InvestigationCancellationRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'This request has already been decided.']);
            }
            if ((int) $request->requested_by === (int) $actor->id) {
                throw ValidationException::withMessages(['reviewer' => 'Requester cannot approve or reject their own request.']);
            }
            // Reassess the live clinical state: a test may have progressed since request creation.
            $item = InvoiceItem::query()->whereKey($request->invoice_item_id)->lockForUpdate()->firstOrFail();
            $orderItem = $item->serviceOrderItem()->with(['diagnosticSample', 'diagnosticResult'])->first();
            if (! $orderItem || (int) $orderItem->id !== (int) $request->service_order_item_id) {
                throw ValidationException::withMessages(['investigation' => 'Investigation linkage changed; review blocked.']);
            }
            $status = (string) $orderItem->status;
            if (! in_array($status, ['ordered', 'in_process', 'completed'], true)) {
                throw ValidationException::withMessages(['investigation' => 'Unexpected clinical status; review blocked.']);
            }
            $requiresMs = $request->requires_ms_approval
                || $status !== 'ordered'
                || ($orderItem->diagnosticSample && $orderItem->diagnosticSample->status === 'collected')
                || $orderItem->diagnosticResult !== null;
            if ($requiresMs) {
                $this->requireDirectPermission($actor, 'billing.investigation-cancellation.approve');
                if (! InvestigationMsApprover::query()->where('user_id', $actor->id)->where('active', true)->exists()) {
                    throw ValidationException::withMessages(['reviewer' => 'Only an explicitly designated Medical Superintendent may review this request.']);
                }
            } else {
                $this->requirePermission($actor, 'billing.investigation-cancellation.approve');
            }
            $request->forceFill([
                'requires_ms_approval' => $requiresMs,
                'status' => $approve ? 'approved' : 'rejected',
                'approved_amount' => $approve ? $request->requested_amount : null,
                'decision_reason' => $decisionReason,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ])->save();
            return $request->refresh();
        }, 3);
    }

    private function requirePermission(User $actor, string $permission): void
    {
        if (! $actor->hasPermission($permission)) {
            throw ValidationException::withMessages(['authorization' => 'Permission denied.']);
        }
    }

    private function requireDirectPermission(User $actor, string $permission): void
    {
        // Deliberately bypass User::hasPermission(): admin/super_admin are implicit allow-all.
        if (! $actor->permissions()->where('permissions.name', $permission)->exists()) {
            throw ValidationException::withMessages(['authorization' => 'Direct Medical Superintendent approval permission required.']);
        }
    }
}
