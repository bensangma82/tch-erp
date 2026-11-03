<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use RuntimeException;

class TallyVoucherXmlRouter
{
    private const BUILDERS = [
        'ip_billing_account:finalized' => TallySalesXmlBuilder::class,
        'invoice:issued' => TallyOpdSalesXmlBuilder::class,
        'investigation_cancellation_request:revenue_reversed' => TallyInvestigationCreditNoteXmlBuilder::class,
        'pharmacy_sale:completed' => TallyPharmacySalesXmlBuilder::class,

        'ip_billing_payment:received' => TallyReceiptXmlBuilder::class,
        'payment:received' => TallyOpdReceiptXmlBuilder::class,
        'ip_billing_advance:received' => TallyAdvanceReceiptXmlBuilder::class,
        'ip_billing_mhis_receipt:received' => TallyMhisReceiptXmlBuilder::class,

        'ip_billing_refund:paid' => TallyIpRefundXmlBuilder::class,
        'finance_voucher:transfer_posted' => TallyContraXmlBuilder::class,
        'investigation_cancellation_refund:paid' => TallyInvestigationRefundXmlBuilder::class,
        'pharmacy_grn:received' => TallyPurchaseXmlBuilder::class,
        'pharmacy_supplier_payment:paid' => TallySupplierPaymentXmlBuilder::class,

        'hospital_expense:accrual_posted' => TallyHospitalExpenseXmlBuilder::class,
        'hospital_expense_payment:supplier_payment' => TallyHospitalSupplierPaymentXmlBuilder::class,

        'ip_billing_mhis_claim:approved' => TallyJournalXmlBuilder::class,
        'mhis_settlement_adjustment:posted' => TallyJournalXmlBuilder::class,
        'ip_billing_account:advance_applied' => TallyIpdAdvanceApplicationXmlBuilder::class,
        'pharmacy_sale:cogs' => TallyJournalXmlBuilder::class,
    ];

    public function build(
        JournalEntry $journal,
        ?string $remoteId = null,
        ?string $testDate = null
    ): string {
        LegacyMhisTallyExportPolicy::assertJournalAllowed($journal);

        if ($journal->source_type === 'ip_billing_payment') {
            $result = app(TallyCancelledReceiptGuard::class)
                ->check($journal);

            if (! $result['allowed']) {
                throw new RuntimeException($result['reason']);
            }
        }

        if (
            $journal->source_type === 'investigation_cancellation_request'
            && $journal->source_event === 'revenue_reversed'
        ) {
            app(TallyInvestigationCreditNoteGuard::class)->check($journal);
        }

        if (
            $journal->source_type === 'investigation_cancellation_refund'
            && $journal->source_event === 'paid'
        ) {
            app(TallyInvestigationRefundGuard::class)->check($journal);
        }

        $type = app(TallyVoucherClassificationService::class)
            ->classify($journal);

        $key = $journal->source_type.':'.$journal->source_event;
        $builder = self::BUILDERS[$key] ?? null;

        if ($builder === null) {
            throw new RuntimeException(
                'Tally export unsupported for '.$key
            );
        }

        if (
            $testDate !== null
            && $builder !== TallyJournalXmlBuilder::class
        ) {
            throw new RuntimeException(
                'Accounting date override unsupported for '.$type.' vouchers.'
            );
        }

        if ($builder === TallyJournalXmlBuilder::class) {
            return app($builder)->build($journal, $remoteId, $testDate);
        }

        return app($builder)->build($journal, $remoteId);
    }
}
