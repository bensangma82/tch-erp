<?php

namespace App\Services\Mhis;

use App\Models\IpBillingMhisClaim;
use App\Models\MhisSettlementImportBatch;
use Illuminate\Support\Facades\DB;

class MhisSettlementMatchingService
{
    public function match(MhisSettlementImportBatch $batch): array
    {
        $stats = [
            'matched' => 0,
            'unmatched' => 0,
            'ambiguous' => 0,
            'review_required' => 0,
        ];

        DB::transaction(function () use ($batch, &$stats) {
            $batch = MhisSettlementImportBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            foreach ($batch->rows()->orderBy('id')->cursor() as $row) {
                // Do not alter previously reviewed or processed rows.
                if ($row->match_status !== 'unmatched') {
                    continue;
                }

                // Only payment-accomplished records qualify
                // for provisional claim matching.
                $paymentStatus = strtolower(trim(
                    (string) $row->payment_status
                ));

                if ($paymentStatus !== 'payment accomplished') {
                    $row->update([
                        'matched_claim_id' => null,
                        'match_status' => 'review_required',
                        'match_notes' =>
                            'Payment status is not Payment Accomplished. ' .
                            'Manual verification required.',
                    ]);

                    $stats['review_required']++;
                    continue;
                }

                $registration = trim((string) $row->registration_id);

                if ($registration === '') {
                    $row->update([
                        'match_status' => 'review_required',
                        'match_notes' => 'Missing MHIS registration ID.',
                    ]);
                    $stats['review_required']++;
                    continue;
                }

                // Cross-batch duplicate settlement check.
                // A shared UTR alone is not sufficient to identify a duplicate.
                $duplicateImport = \App\Models\MhisSettlementImportRow::query()
                    ->where('batch_id', '!=', $batch->id)
                    ->where('registration_id', $registration)
                    ->where('utr_reference', $row->utr_reference)
                    ->where('approved_amount', $row->approved_amount)
                    ->where('tds_amount', $row->tds_amount)
                    ->where('net_amount', $row->net_amount)
                    ->exists();

                if ($duplicateImport) {
                    $row->update([
                        'matched_claim_id' => null,
                        'match_status' => 'review_required',
                        'match_notes' => 'Possible duplicate settlement in another import batch.',
                    ]);

                    $stats['review_required']++;
                    continue;
                }

                $claims = IpBillingMhisClaim::query()
                    ->where('mhis_registration_id', $registration)
                    ->limit(2)
                    ->get();

                if ($claims->count() === 0) {
                    $stats['unmatched']++;
                    continue;
                }

                if ($claims->count() > 1) {
                    $row->update([
                        'matched_claim_id' => null,
                        'match_status' => 'ambiguous',
                        'match_notes' => 'Multiple ERP claims share this registration ID.',
                    ]);
                    $stats['ambiguous']++;
                    continue;
                }

                $claim = $claims->first();

                $duplicate = $batch->rows()
                    ->where('registration_id', $registration)
                    ->where('id', '!=', $row->id)
                    ->exists();

                if ($duplicate) {
                    $row->update([
                        'matched_claim_id' => null,
                        'match_status' => 'review_required',
                        'match_notes' => 'Repeated registration ID within import batch.',
                    ]);
                    $stats['review_required']++;
                    continue;
                }

                // Check whether another imported settlement is
                // already provisionally linked to this ERP claim.
                $existingLink = \App\Models\MhisSettlementImportRow::query()
                    ->where('matched_claim_id', $claim->id)
                    ->where('id', '!=', $row->id)
                    ->exists();

                if ($existingLink) {
                    $row->update([
                        'matched_claim_id' => null,
                        'match_status' => 'review_required',
                        'match_notes' =>
                            'ERP claim is already linked to another imported settlement. ' .
                            'Review possible partial payments or duplicate settlement.',
                    ]);

                    $stats['review_required']++;
                    continue;
                }

                // Existing receipts require financial review before matching.
                // Receipt amount is net bank payment; TDS is recorded separately.
                $receiptTotals = DB::table('ip_billing_mhis_receipts')
                    ->where('ip_billing_mhis_claim_id', $claim->id)
                    ->where('status', 'active')
                    ->selectRaw(
                        'COALESCE(SUM(amount), 0) AS net_received, ' .
                        'COALESCE(SUM(tds_amount), 0) AS tds_received'
                    )
                    ->first();

                $previousNet = (int) round(
                    (float) $receiptTotals->net_received * 100
                );

                $previousTds = (int) round(
                    (float) $receiptTotals->tds_received * 100
                );

                if ($previousNet + $previousTds > 0) {
                    $row->update([
                        'matched_claim_id' => $claim->id,
                        'match_status' => 'review_required',
                        'match_notes' =>
                            'Existing active MHIS receipts found. ' .
                            'Net received: ' .
                            number_format($previousNet / 100, 2, '.', '') .
                            '; TDS: ' .
                            number_format($previousTds / 100, 2, '.', '') .
                            '. Verify remaining claim balance and duplicates.',
                    ]);

                    $stats['review_required']++;
                    continue;
                }

                // ERP claim must have formal approval before
                // it can be classified as provisionally matched.
                if (
                    ! in_array($claim->status, ['approved', 'settled'], true)
                    || (float) $claim->approved_amount <= 0
                ) {
                    $row->update([
                        'matched_claim_id' => $claim->id,
                        'match_status' => 'review_required',
                        'match_notes' =>
                            'ERP claim is not formally approved. ' .
                            'Verify claim approval and approved amount.',
                    ]);

                    $stats['review_required']++;
                    continue;
                }

                $approved = (int) round((float) $row->approved_amount * 100);
                $claimAmount = (int) round(
                    (float) $claim->approved_amount * 100
                );

                if ($approved !== $claimAmount) {
                    $row->update([
                        'matched_claim_id' => $claim->id,
                        'match_status' => 'review_required',
                        'match_notes' => 'Approved settlement differs from ERP claim amount.',
                    ]);
                    $stats['review_required']++;
                    continue;
                }

                $row->update([
                    'matched_claim_id' => $claim->id,
                    'match_status' => 'matched',
                    'match_notes' => 'Provisional exact registration ID and amount match.',
                ]);

                $stats['matched']++;
            }
        });

        return $stats;
    }
}
