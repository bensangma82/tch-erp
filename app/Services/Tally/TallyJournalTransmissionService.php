<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;

class TallyJournalTransmissionService
{
    public function __construct(
        private TallyJournalExportService $eligibilityService,
        private TallyJournalXmlBuilder $xmlBuilder
    ) {}

    public function validate(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        $result = $this->eligibilityService
            ->checkEligibility($journal);

        return [
            'eligible' => $result['eligible'],
            'errors' => $result['errors'],
        ];
    }

    public function preview(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        $validation = $this->validate($journal);

        if (! $validation['eligible']) {
            return $validation;
        }

        $voucherType = app(
            TallyVoucherClassificationService::class
        )->classify($journal);

        if ($voucherType === 'Sales'
            && $journal->source_type === 'ip_billing_account'
            && $journal->source_event === 'finalized') {

            app(TallyVoucherGlValidationService::class)
                ->validate($journal);

            $xml = app(TallySalesXmlBuilder::class)
                ->build($journal);

        } elseif ($voucherType === 'Journal') {

            $xml = $this->xmlBuilder->build($journal);

        } else {
            return [
                'eligible' => false,
                'journal_id' => $journal->id,
                'voucher_type' => $voucherType,
                'errors' => [
                    $voucherType.' XML export is not implemented yet.',
                ],
                'transmitted' => false,
            ];
        }

        return [
            'eligible' => true,
            'journal_id' => $journal->id,
            'journal_no' => $journal->journal_no,
            'xml_bytes' => strlen($xml),
            'transmitted' => false,
        ];
    }

    /**
     * Local IPD Sales XML preparation.
     *
     * Does not check live Tally synchronization freshness.
     * Does not authorize transmission.
     */

    /**
     * Read-only export history check.
     * Existing or uncertain exports are never treated as new.
     */
    private function checkLocalExportHistory(JournalEntry $journal): ?array
    {
        $exists = $journal->tallyExport()->exists();

        if (! $exists && $journal->finance_voucher_id !== null) {
            $exists = \App\Models\TallyExport::query()
                ->where('finance_voucher_id', $journal->finance_voucher_id)
                ->exists();
        }

        if (! $exists) {
            return null;
        }

        return [
            'eligible' => false,
            'journal_no' => $journal->journal_no,
            'errors' => [
                'Existing Tally export requires reconciliation.'
            ],
            'transmitted' => false,
        ];
    }

    public function previewLocalSales(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }


        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }


        $type = app(
            TallyVoucherClassificationService::class
        )->classify($journal);

        if (
            $type !== 'Sales'
            || $journal->source_type !== 'ip_billing_account'
            || $journal->source_event !== 'finalized'
        ) {
            return [
                'eligible' => false,
                'errors' => [
                    'Only IPD final-bill Sales vouchers are supported.'
                ],
                'transmitted' => false,
            ];
        }

        app(TallyVoucherGlValidationService::class)
            ->validate($journal);

        $xml = app(TallySalesXmlBuilder::class)
            ->build($journal);

        return [
            'eligible' => true,
            'voucher_type' => 'Sales',
            'journal_id' => $journal->id,
            'journal_no' => $journal->journal_no,
            'xml_bytes' => strlen($xml),
            'transmitted' => false,
            'export_ready' => false,
            'requires_tally_validation' => true,
        ];
    }


    /**
     * Read-only IPD Receipt voucher preview.
     * Does not authorize or transmit Tally exports.
     */
    public function previewLocalReceipt(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }


        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }


        if (
            $journal->source_type !== 'ip_billing_payment'
            || $journal->source_event !== 'received'
            || $journal->status !== 'posted'
        ) {
            return [
                'eligible' => false,
                'errors' => ['Only posted IPD receipts are supported.'],
                'transmitted' => false,
            ];
        }

        $guard = app(TallyCancelledReceiptGuard::class)
            ->check($journal);

        if (! $guard['allowed']) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$guard['reason']],
                'transmitted' => false,
            ];
        }

        $xml = app(TallyReceiptXmlBuilder::class)
            ->build($journal);

        return [
            'eligible' => true,
            'voucher_type' => 'Receipt',
            'journal_id' => $journal->id,
            'journal_no' => $journal->journal_no,
            'xml_bytes' => strlen($xml),
            'transmitted' => false,
            'export_ready' => false,
            'requires_tally_validation' => true,
        ];
    }


    /**
     * Read-only Pharmacy Purchase / Supplier Payment preview.
     * Does not create exports or transmit vouchers.
     */
    public function previewLocalSupplierVoucher(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($journal->status !== 'posted') {
            return [
                'eligible' => false,
                'errors' => ['Journal is not posted.'],
                'transmitted' => false,
            ];
        }

        $type = match (
            $journal->source_type.':'.$journal->source_event
        ) {
            'pharmacy_grn:received' => 'Purchase',
            'pharmacy_supplier_payment:paid' => 'Payment',
            default => null,
        };

        if ($type === null) {
            return [
                'eligible' => false,
                'errors' => ['Unsupported supplier voucher event.'],
                'transmitted' => false,
            ];
        }

        if (
            $journal->tallyExport()->exists()
            || (
                $journal->finance_voucher_id !== null
                && \App\Models\TallyExport::query()
                    ->where('finance_voucher_id', $journal->finance_voucher_id)
                    ->exists()
            )
        ) {
            return [
                'eligible' => false,
                'errors' => ['Existing Tally export requires reconciliation.'],
                'transmitted' => false,
            ];
        }

        try {
            $xml = $type === 'Purchase'
                ? app(TallyPurchaseXmlBuilder::class)->build($journal)
                : app(TallySupplierPaymentXmlBuilder::class)->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => $type,
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }


    /**
     * Read-only hospital expense accrual / supplier payment preview.
     */
    public function previewLocalHospitalVoucher(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($journal->status !== 'posted') {
            return [
                'eligible' => false,
                'errors' => ['Journal is not posted.'],
                'transmitted' => false,
            ];
        }

        $type = match (
            $journal->source_type.':'.$journal->source_event
        ) {
            'hospital_expense:accrual_posted' => 'Journal',
            'hospital_expense_payment:supplier_payment' => 'Payment',
            default => null,
        };

        if ($type === null) {
            return [
                'eligible' => false,
                'errors' => ['Unsupported hospital voucher event.'],
                'transmitted' => false,
            ];
        }

        if (
            $journal->tallyExport()->exists()
            || (
                $journal->finance_voucher_id !== null
                && \App\Models\TallyExport::query()
                    ->where('finance_voucher_id', $journal->finance_voucher_id)
                    ->exists()
            )
        ) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [
                    'Existing Tally export requires reconciliation.'
                ],
                'transmitted' => false,
            ];
        }

        try {
            $xml = $type === 'Journal'
                ? app(TallyHospitalExpenseXmlBuilder::class)->build($journal)
                : app(TallyHospitalSupplierPaymentXmlBuilder::class)->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => $type,
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }


    /**
     * Unified, read-only Tally voucher preparation.
     * Does not create exports or transmit XML.
     */
    public function previewLocalCreditNote(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }

        try {
            $xml = app(TallyVoucherXmlRouter::class)->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => 'Credit Note',
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }

    public function previewLocalVoucher(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        $event = $journal->source_type.':'.$journal->source_event;

        return match ($event) {
            'investigation_cancellation_request:revenue_reversed' =>
                $this->previewLocalCreditNote($journal),

            'ip_billing_account:finalized' =>
                $this->previewLocalSales($journal),

            'invoice:issued' =>
                $this->previewLocalOpdSales($journal),

            'pharmacy_sale:completed' =>
                $this->previewLocalPharmacySales($journal),

            'ip_billing_payment:received' =>
                $this->previewLocalReceipt($journal),

            'ip_billing_advance:received' =>
                $this->previewLocalAdvanceReceipt($journal),

            'ip_billing_refund:paid' =>
                $this->previewLocalIpRefund($journal),

            'payment:received' =>
                $this->previewLocalOpdReceipt($journal),

            'ip_billing_mhis_claim:approved',
            'mhis_settlement_adjustment:posted',
            'ip_billing_account:advance_applied',
            'pharmacy_sale:cogs' =>
                $this->previewLocalMhisJournal($journal),

            'ip_billing_mhis_receipt:received' =>
                $this->previewLocalMhisReceipt($journal),

            'pharmacy_grn:received',
            'pharmacy_supplier_payment:paid' =>
                $this->previewLocalSupplierVoucher($journal),

            'hospital_expense:accrual_posted',
            'hospital_expense_payment:supplier_payment' =>
                $this->previewLocalHospitalVoucher($journal),

            default => [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [
                    'Voucher preparation is not implemented for '
                    .$event.'.'
                ],
                'transmitted' => false,
            ],
        };
    }


    /**
     * Read-only MHIS reimbursement voucher preview.
     */
    public function previewLocalMhisReceipt(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }

        try {
            $type = app(TallyVoucherClassificationService::class)
                ->classify($journal);

            if ($type !== 'Receipt'
                || $journal->source_type !== 'ip_billing_mhis_receipt'
                || $journal->source_event !== 'received') {
                throw new \RuntimeException('Unsupported MHIS receipt.');
            }

            $xml = app(TallyMhisReceiptXmlBuilder::class)
                ->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => 'Receipt',
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }



    /**
     * Read-only MHIS accounting Journal preview.
     */
    public function previewLocalMhisJournal(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }

        try {
            $event = $journal->source_type.':'.$journal->source_event;

            if (! in_array($event, [
                'ip_billing_mhis_claim:approved',
                'mhis_settlement_adjustment:posted',
                'ip_billing_account:advance_applied',
                'pharmacy_sale:cogs',
            ], true)) {
                throw new \RuntimeException('Unsupported MHIS Journal event.');
            }

            $type = app(TallyVoucherClassificationService::class)
                ->classify($journal);

            if ($type !== 'Journal') {
                throw new \RuntimeException('Invalid voucher classification.');
            }

            $xml = app(TallyJournalXmlBuilder::class)->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => 'Journal',
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }


    /**
     * Read-only OPD / investigation Sales voucher preview.
     */
    public function previewLocalOpdSales(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }

        try {
            $type = app(TallyVoucherClassificationService::class)
                ->classify($journal);

            if (
                $type !== 'Sales'
                || $journal->source_type !== 'invoice'
                || $journal->source_event !== 'issued'
            ) {
                throw new \RuntimeException('Unsupported invoice Sales event.');
            }

            $xml = app(TallyOpdSalesXmlBuilder::class)
                ->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => 'Sales',
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }


    /**
     * Read-only OPD / investigation Receipt preview.
     */
    public function previewLocalOpdReceipt(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }

        try {
            $type = app(TallyVoucherClassificationService::class)
                ->classify($journal);

            if (
                $type !== 'Receipt'
                || $journal->source_type !== 'payment'
                || $journal->source_event !== 'received'
            ) {
                throw new \RuntimeException('Unsupported OPD receipt.');
            }

            $xml = app(TallyOpdReceiptXmlBuilder::class)
                ->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => 'Receipt',
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }


    public function previewLocalAdvanceReceipt(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }

        try {
            if (
                app(TallyVoucherClassificationService::class)
                    ->classify($journal) !== 'Receipt'
                || $journal->source_type !== 'ip_billing_advance'
                || $journal->source_event !== 'received'
            ) {
                throw new \RuntimeException('Invalid advance receipt.');
            }

            $xml = app(TallyAdvanceReceiptXmlBuilder::class)
                ->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => 'Receipt',
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }


    /**
     * Read-only OPD Pharmacy Sales preview.
     */
    public function previewLocalPharmacySales(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }

        try {
            if (
                app(TallyVoucherClassificationService::class)
                    ->classify($journal) !== 'Sales'
                || $journal->source_type !== 'pharmacy_sale'
                || $journal->source_event !== 'completed'
            ) {
                throw new \RuntimeException('Invalid Pharmacy Sales event.');
            }

            $xml = app(TallyPharmacySalesXmlBuilder::class)
                ->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => 'Sales',
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }


    /**
     * Read-only IPD advance refund Payment preview.
     */
    public function previewLocalIpRefund(JournalEntry $journal): array
    {
        if (LegacyMhisTallyExportPolicy::isBlockedSource($journal->source_type)) {
            return LegacyMhisTallyExportPolicy::ineligible();
        }

        if ($blocked = $this->checkLocalExportHistory($journal)) {
            return $blocked;
        }

        try {
            if (
                app(TallyVoucherClassificationService::class)
                    ->classify($journal) !== 'Payment'
                || $journal->source_type !== 'ip_billing_refund'
                || $journal->source_event !== 'paid'
            ) {
                throw new \RuntimeException('Invalid refund event.');
            }

            $xml = app(TallyIpRefundXmlBuilder::class)
                ->build($journal);

            return [
                'eligible' => true,
                'voucher_type' => 'Payment',
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no,
                'xml_bytes' => strlen($xml),
                'export_ready' => false,
                'requires_tally_validation' => true,
                'transmitted' => false,
            ];
        } catch (\Throwable $e) {
            return [
                'eligible' => false,
                'journal_no' => $journal->journal_no,
                'errors' => [$e->getMessage()],
                'transmitted' => false,
            ];
        }
    }

}
