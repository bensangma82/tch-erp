<?php

namespace Tests\Feature;

use Tests\TestCase;

class InvestigationCancellationHttpTest extends TestCase
{
    public function test_guest_cannot_view_approval_queue(): void
    {
        $this->get(route('billing.investigation-cancellations.queue'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_view_invoice_cancellation(): void
    {
        $this->get(route('billing.investigation-cancellations.invoice', 1))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_request_cancellation(): void
    {
        $this->post(route('billing.investigation-cancellations.request', 1), [
            'reason' => 'Test cancellation request',
        ])->assertRedirect(route('login'));
    }

    public function test_guest_cannot_approve_cancellation(): void
    {
        $this->post(route('billing.investigation-cancellations.decide', 1), [
            'decision' => 'approve',
            'decision_reason' => 'Test approval decision',
        ])->assertRedirect(route('login'));
    }
}
