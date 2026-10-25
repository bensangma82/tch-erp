<?php
namespace Tests\Feature;
use Tests\TestCase;
final class InvestigationRefundVerificationAccessTest extends TestCase {
    public function test_guest_cannot_view_refund_verification_queue(): void {
        $this->get(route('billing.investigation-refunds.verification'))->assertRedirect(route('login'));
    }
    public function test_guest_cannot_submit_refund_evidence():void {
        $this->post(route('billing.investigation-refunds.submit',2),[])->assertRedirect(route('login'));
    }
    public function test_guest_cannot_verify_a_refund():void {
        $this->post(route('billing.investigation-refunds.verify',1),[])->assertRedirect(route('login'));
    }
}
