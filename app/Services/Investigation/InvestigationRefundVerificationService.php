<?php

namespace App\Services\Investigation;

use App\Models\InvestigationCancellationRequest;
use App\Models\InvestigationCancellationRefund;
use App\Models\InvestigationRefundSettlementCheck;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Three-stage investigation refund workflow.
 *
 * 1. Initiate: create an unpaid refund request.
 * 2. Disburse: document actual payment by the cashier.
 * 3. Verify: independently confirm payment and post the journal.
 *
 * This service never transfers funds.
 */
final class InvestigationRefundVerificationService
{
    private function deny(string $message): never
    {
        throw ValidationException::withMessages([
            'refund' => $message,
        ]);
    }

    private function direct(User $user, string $permission): bool
    {
        return $user->permissions()
            ->where('permissions.name', $permission)
            ->exists();
    }

    private function cents($value): int
    {
        $value = (string) $value;

        if (!preg_match('/^[0-9]{1,10}\.[0-9]{2}$/D', $value)) {
            $this->deny('A valid monetary amount is required.');
        }

        [$whole, $fraction] = explode('.', $value);

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function validateEligibility(
        InvestigationCancellationRequest $c,
        Payment $p,
        string $amount,
        string $method
    ): void {
        if (
            $c->status !== 'executed'
            || !$c->executed_at
            || (int) $p->invoice_id !== (int) $c->invoice_id
        ) {
            $this->deny('Cancellation and payment mismatch.');
        }

        $originalMethod = strtolower((string) $p->payment_mode);

        if (
            !in_array($originalMethod, ['cash', 'upi', 'card'], true)
            || $method !== $originalMethod
        ) {
            $this->deny('Refund method must match original payment.');
        }

        $requested = $this->cents($amount);
        $approved = $this->cents($c->approved_amount);
        $paid = $this->cents($p->amount);

        if (
            $requested <= 0
            || $requested > $approved
            || $requested > $paid
        ) {
            $this->deny(
                'Refund exceeds the approved cancellation or original receipt.'
            );
        }

        if (
            InvestigationCancellationRefund::where(
                'investigation_cancellation_request_id',
                $c->id
            )->exists()
        ) {
            $this->deny(
                'An existing refund requires reconciliation.'
            );
        }
    }

    private function assertNoActiveSettlement(int $cancellationId): void
    {
        if (
            InvestigationRefundSettlementCheck::where(
                'cancellation_id',
                $cancellationId
            )
                ->whereIn('status', [
                    'initiated',
                    'disbursed',
                    'submitted',
                    'verified',
                    'completed',
                ])
                ->exists()
        ) {
            $this->deny(
                'An existing settlement requires reconciliation.'
            );
        }
    }

    /**
     * Stage 1: Request a refund.
     * No actual payment or journal is recorded.
     */
    public function initiate(
        InvestigationCancellationRequest $request,
        Payment $payment,
        User $initiator,
        array $data
    ): InvestigationRefundSettlementCheck {
        if (!$this->direct(
            $initiator,
            'billing.investigation-refund.process'
        )) {
            $this->deny('Explicit refund permission required.');
        }

        return DB::transaction(function () use (
            $request,
            $payment,
            $initiator,
            $data
        ) {
            $c = InvestigationCancellationRequest::whereKey(
                $request->id
            )->lockForUpdate()->firstOrFail();

            $p = Payment::whereKey(
                $payment->id
            )->lockForUpdate()->firstOrFail();

            $amount = (string) ($data['amount'] ?? '');

            $method = strtolower(
                trim((string) ($data['settlement_method'] ?? ''))
            );

            $this->validateEligibility($c, $p, $amount, $method);
            $this->assertNoActiveSettlement($c->id);

            $trackingReference = 'INIT-' . Str::uuid();

            return InvestigationRefundSettlementCheck::create([
                'cancellation_id' => $c->id,
                'payment_id' => $p->id,
                'amount' => $amount,
                'settlement_method' => $method,

                // Internal reference, NOT proof of payment.
                'settlement_reference' => $trackingReference,
                'evidence_description' =>
                    'Refund initiated. No disbursement recorded.',

                'status' => 'initiated',

                // Preserve compatibility with mandatory legacy field.
                'submitted_by' => $initiator->id,

                'initiated_by' => $initiator->id,
                'initiated_at' => now(),
            ]);
        }, 3);
    }

    /**
     * Stage 2: Confirm actual external disbursement.
     *
     * This does not transfer money and does not post a journal.
     */
    public function disburse(
        InvestigationRefundSettlementCheck $check,
        User $cashier,
        array $data
    ): InvestigationRefundSettlementCheck {
        if (!$this->direct(
            $cashier,
            'billing.investigation-refund.process'
        )) {
            $this->deny('Explicit cashier permission required.');
        }

        return DB::transaction(function () use (
            $check,
            $cashier,
            $data
        ) {
            $s = InvestigationRefundSettlementCheck::whereKey(
                $check->id
            )->lockForUpdate()->firstOrFail();

            if ($s->status !== 'initiated') {
                $this->deny(
                    'Only initiated refunds can be marked disbursed.'
                );
            }

            if (empty($data['actually_paid'])) {
                $this->deny(
                    'Actual external disbursement must be confirmed.'
                );
            }

            $reference = trim(
                (string) ($data['settlement_reference'] ?? '')
            );

            $evidence = trim(
                (string) ($data['evidence_description'] ?? '')
            );

            if (
                $reference === ''
                || strlen($reference) > 100
                || strlen($evidence) < 10
            ) {
                $this->deny(
                    'Valid disbursement reference and evidence required.'
                );
            }

            $c = InvestigationCancellationRequest::whereKey(
                $s->cancellation_id
            )->lockForUpdate()->firstOrFail();

            $p = Payment::whereKey(
                $s->payment_id
            )->lockForUpdate()->firstOrFail();

            $this->validateEligibility(
                $c,
                $p,
                (string) $s->amount,
                (string) $s->settlement_method
            );

            if (
                InvestigationRefundSettlementCheck::where(
                    'settlement_method',
                    $s->settlement_method
                )
                    ->where('settlement_reference', $reference)
                    ->whereKeyNot($s->id)
                    ->exists()
            ) {
                $this->deny(
                    'This external settlement reference already exists.'
                );
            }

            $s->forceFill([
                'settlement_reference' => $reference,
                'evidence_description' => $evidence,
                'disbursement_reference' => $reference,
                'disbursement_evidence' => $evidence,
                'disbursed_by' => $cashier->id,
                'disbursed_at' => now(),
                'submitted_by' => $cashier->id,
                'status' => 'disbursed',
            ])->save();

            return $s->refresh();
        }, 3);
    }

    /**
     * Legacy method: accepts documentation of an already-paid refund.
     *
     * Retained for existing controller compatibility.
     * New refund requests must use initiate() and disburse().
     */
    public function submit(
        InvestigationCancellationRequest $request,
        Payment $payment,
        User $cashier,
        array $data
    ): InvestigationRefundSettlementCheck {
        if (!$this->direct(
            $cashier,
            'billing.investigation-refund.process'
        )) {
            $this->deny('Explicit refund permission required.');
        }

        return DB::transaction(function () use (
            $request,
            $payment,
            $cashier,
            $data
        ) {
            $c = InvestigationCancellationRequest::whereKey(
                $request->id
            )->lockForUpdate()->firstOrFail();

            $p = Payment::whereKey(
                $payment->id
            )->lockForUpdate()->firstOrFail();

            $method = strtolower(
                (string) $p->payment_mode
            );

            $requestedMethod = strtolower(
                trim((string) ($data['settlement_method'] ?? ''))
            );

            $amount = (string) ($data['amount'] ?? '');

            $this->validateEligibility(
                $c,
                $p,
                $amount,
                $requestedMethod
            );

            if ($requestedMethod !== $method) {
                $this->deny('Refund payment method mismatch.');
            }

            if (empty($data['actually_paid'])) {
                $this->deny(
                    'External settlement must already be completed.'
                );
            }

            $reference = trim(
                (string) ($data['settlement_reference'] ?? '')
            );

            $evidence = trim(
                (string) ($data['evidence_description'] ?? '')
            );

            if (
                $reference === ''
                || strlen($reference) > 100
                || strlen($evidence) < 10
            ) {
                $this->deny(
                    'Valid external settlement evidence required.'
                );
            }

            $this->assertNoActiveSettlement($c->id);

            return InvestigationRefundSettlementCheck::create([
                'cancellation_id' => $c->id,
                'payment_id' => $p->id,
                'amount' => $amount,
                'settlement_method' => $method,
                'settlement_reference' => $reference,
                'evidence_description' => $evidence,
                'status' => 'submitted',
                'submitted_by' => $cashier->id,

                // Explicit audit fields for legacy submissions.
                'disbursed_by' => $cashier->id,
                'disbursed_at' => now(),
                'disbursement_reference' => $reference,
                'disbursement_evidence' => $evidence,
            ]);
        }, 3);
    }

    /**
     * Stage 3: Independently verify disbursement and post the journal.
     */
    public function verify(
        InvestigationRefundSettlementCheck $check,
        User $verifier,
        string $notes,
        InvestigationCancellationAccountingService $accounting
    ): InvestigationRefundSettlementCheck {
        if (!$this->direct(
            $verifier,
            'billing.investigation-refund.verify'
        )) {
            $this->deny(
                'Direct verifier permission required; admin override is not sufficient.'
            );
        }

        return DB::transaction(function () use (
            $check,
            $verifier,
            $notes,
            $accounting
        ) {
            $s = InvestigationRefundSettlementCheck::whereKey(
                $check->id
            )->lockForUpdate()->firstOrFail();

            if (!in_array(
                $s->status,
                ['disbursed', 'submitted'],
                true
            )) {
                $this->deny(
                    'Refund must be disbursed before verification.'
                );
            }

            $c = InvestigationCancellationRequest::whereKey(
                $s->cancellation_id
            )->lockForUpdate()->firstOrFail();

            if (
                (int) $s->submitted_by === (int) $verifier->id
                || (
                    $s->initiated_by !== null
                    && (int) $s->initiated_by === (int) $verifier->id
                )
                || (
                    $s->disbursed_by !== null
                    && (int) $s->disbursed_by === (int) $verifier->id
                )
                || (int) $c->requested_by === (int) $verifier->id
            ) {
                $this->deny('Independent verifier required.');
            }

            if (strlen(trim($notes)) < 10) {
                $this->deny(
                    'Evidence verification notes required.'
                );
            }

            if (
                !is_string($s->settlement_reference)
                || trim($s->settlement_reference) === ''
                || strlen(trim((string) $s->evidence_description)) < 10
            ) {
                $this->deny(
                    'External settlement evidence is incomplete.'
                );
            }

            // Fail closed for the new workflow when explicit
            // disbursement metadata is absent.
            if (
                $s->status === 'disbursed'
                && (
                    !$s->disbursed_by
                    || !$s->disbursed_at
                    || !$s->disbursement_reference
                    || !$s->disbursement_evidence
                )
            ) {
                $this->deny(
                    'Documented disbursement is required.'
                );
            }

            // The verifier must inspect genuine independent evidence.
            // Neither the service nor the ERP initiates a payment.
            $p = Payment::whereKey(
                $s->payment_id
            )->firstOrFail();

            $accounting->recordCompletedRefund(
                $c,
                $p,
                $verifier,
                $s->settlement_reference,
                (string) $s->amount
            );

            $refund = InvestigationCancellationRefund::where(
                'investigation_cancellation_request_id',
                $c->id
            )
                ->where('payment_id', $p->id)
                ->where(
                    'reference_no',
                    $s->settlement_reference
                )
                ->firstOrFail();

            $s->forceFill([
                'status' => 'completed',
                'verified_by' => $verifier->id,
                'verified_at' => now(),
                'verification_notes' => trim($notes),
                'refund_id' => $refund->id,
            ])->save();

            return $s->refresh();
        }, 3);
    }
}
