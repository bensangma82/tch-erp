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
        $result = $this->eligibilityService
            ->checkEligibility($journal);

        return [
            'eligible' => $result['eligible'],
            'errors' => $result['errors'],
        ];
    }

    public function preview(JournalEntry $journal): array
    {
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
    public function previewLocalVoucher(JournalEntry $journal): array
    {
        $event = $journal->source_type.':'.$journal->source_event;

        return match ($event) {
            'ip_billing_account:finalized' =>
                $this->previewLocalSales($journal),

            'ip_billing_payment:received' =>
                $this->previewLocalReceipt($journal),

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

}
