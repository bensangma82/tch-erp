<?php

namespace App\Services\Mhis;

use App\Models\IpBillingAccount;
use App\Models\IpBillingMhisClaim;
use App\Models\IpBillingMhisReceipt;
use App\Models\MhisSettlementImportBatch;
use App\Models\MhisSettlementImportRow;
use App\Services\Accounting\MhisReceiptAccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MhisImportedSettlementPostingService
{
    public function postBatch(MhisSettlementImportBatch $batch, bool $bankVerified): array
    {
        Gate::authorize('finance.mhis-settlement-import.post');
        if (! $bankVerified) {
            throw ValidationException::withMessages(['bank_verified' => 'Verify the bank credits before posting.']);
        }
        if ($batch->status !== 'imported') {
            throw ValidationException::withMessages(['batch' => 'Only imported batches can be posted.']);
        }

        // Matching never posts. This explicit action matches first, then posts eligible rows.
        app(MhisSettlementMatchingService::class)->match($batch);
        $stats = ['posted' => 0, 'review_required' => 0];
        $ids = $batch->rows()->where('match_status', 'matched')->orderBy('id')->pluck('id');
        foreach ($ids as $id) {
            try {
                if ($this->postRow((int) $id)) {
                    $stats['posted']++;
                }
            } catch (RuntimeException|ValidationException $exception) {
                // Receipt, journal and claim changes have rolled back together.
                DB::transaction(function () use ($id, $exception) {
                    $row = MhisSettlementImportRow::query()->whereKey($id)->lockForUpdate()->firstOrFail();
                    if ($row->match_status === 'matched' && ! $row->posted_receipt_id) {
                        $message = $exception instanceof ValidationException
                            ? implode('; ', array_merge(...array_values($exception->errors())))
                            : $exception->getMessage();
                        $row->update(['match_status' => 'review_required', 'match_notes' => 'Posting blocked: '.$message]);
                    }
                });
                $stats['review_required']++;
            }
        }
        return $stats;
    }

    private function postRow(int $rowId): bool
    {
        Gate::authorize('finance.mhis-settlement-import.post');
        return DB::transaction(function () use ($rowId) {
            $snapshot = MhisSettlementImportRow::findOrFail($rowId);
            // Serialize with matching, then use existing account -> claim -> receipt lock order.
            $batch = MhisSettlementImportBatch::query()->whereKey($snapshot->batch_id)->lockForUpdate()->firstOrFail();
            if ($batch->status !== 'imported') {
                throw new RuntimeException('Batch is not eligible for posting.');
            }
            if ($snapshot->posted_receipt_id || $snapshot->match_status === 'posted') {
                return false;
            }
            $candidate = IpBillingMhisClaim::find($snapshot->matched_claim_id);
            if (! $candidate) {
                throw new RuntimeException('A matched ERP claim is required.');
            }
            $account = IpBillingAccount::query()->whereKey($candidate->ip_billing_account_id)->lockForUpdate()->firstOrFail();
            $claim = IpBillingMhisClaim::query()->whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            $row = MhisSettlementImportRow::query()->whereKey($rowId)->lockForUpdate()->firstOrFail();
            if ($row->posted_receipt_id || $row->match_status === 'posted') {
                return false;
            }
            if ($row->match_status !== 'matched' || (int) $row->matched_claim_id !== (int) $claim->id
                || (int) $claim->ip_billing_account_id !== (int) $account->id
                || (int) $claim->patient_id !== (int) $account->patient_id
                || (int) $claim->admission_id !== (int) $account->admission_id) {
                throw new RuntimeException('Claim references changed; rematching is required.');
            }
            $registration = trim((string) $row->registration_id);
            if ($registration === '' || $claim->mhis_registration_id !== $registration
                || IpBillingMhisClaim::where('mhis_registration_id', $registration)->count() !== 1) {
                throw new RuntimeException('Registration must identify exactly one ERP claim.');
            }
            if (strtolower(trim((string) $row->payment_status)) !== 'payment accomplished'
                || ! $row->payment_date || $row->payment_date->isAfter(today())
                || trim((string) $row->utr_reference) === '') {
                throw new RuntimeException('Payment Accomplished, a non-future payment date and UTR are required.');
            }
            $gross = self::paise($row->approved_amount);
            $tds = self::paise($row->tds_amount);
            $net = self::paise($row->net_amount);
            if ($gross <= 0 || $net <= 0 || $gross !== $net + $tds
                || $gross !== self::paise($claim->approved_amount)) {
                throw new RuntimeException('Gross approval must equal ERP approval and net payment plus TDS.');
            }
            if ($claim->status !== 'approved' || ! $claim->approval_date) {
                throw new RuntimeException('A formally approved, unsettled ERP claim is required.');
            }
            if ($batch->rows()->where('registration_id', $registration)->where('id', '!=', $row->id)->exists()
                || MhisSettlementImportRow::where('matched_claim_id', $claim->id)->where('id', '!=', $row->id)->exists()
                || MhisSettlementImportRow::where('batch_id', '!=', $batch->id)->where('registration_id', $registration)
                    ->where('utr_reference', $row->utr_reference)->where('approved_amount', $row->approved_amount)
                    ->where('tds_amount', $row->tds_amount)->where('net_amount', $row->net_amount)->exists()) {
                throw new RuntimeException('Duplicate settlement or claim link requires review.');
            }
            // Do not automatically reinterpret historical net/gross or partial receipts.
            if (IpBillingMhisReceipt::where('ip_billing_mhis_claim_id', $claim->id)->exists()) {
                throw new RuntimeException('Existing MHIS receipts require reconciliation before import posting.');
            }
            if (app(\App\Services\Accounting\MhisSettlementBalanceService::class)->postedAdjustmentsPaise((int) $claim->id) !== 0) {
                throw new RuntimeException('Existing settlement adjustments require review.');
            }
            $receipt = IpBillingMhisReceipt::create([
                'ip_billing_mhis_claim_id' => $claim->id,
                'ip_billing_account_id' => $account->id,
                'admission_id' => $claim->admission_id,
                'patient_id' => $claim->patient_id,
                'receipt_date' => $row->payment_date->toDateString(),
                // Existing receipt and Tally services require GROSS, not bank net.
                'amount' => $row->approved_amount,
                'tds_amount' => $row->tds_amount,
                'transaction_reference' => trim($row->utr_reference),
                'bank_reference' => trim($row->utr_reference),
                'payment_reference' => 'MHIS-IMPORT-'.$batch->id.'-ROW-'.$row->id,
                'remarks' => 'Bank-verified MHIS import batch '.$batch->id.', Excel row '.$row->excel_row_number,
                'status' => 'active',
                'received_by' => auth()->id(),
            ]);
            $claim->update([
                'settlement_amount' => $row->approved_amount,
                'settlement_date' => $row->payment_date->toDateString(),
                'status' => 'settled',
                'updated_by' => auth()->id(),
            ]);
            // Validates recognition, finalized bill, GL balance and posts the receipt journal.
            $journal = app(MhisReceiptAccountingService::class)->record($receipt);
            $row->update([
                'match_status' => 'posted',
                'posted_receipt_id' => $receipt->id,
                'posted_journal_id' => $journal->id,
                'posted_by' => auth()->id(),
                'bank_verified_by' => auth()->id(),
                'bank_verified_at' => now(),
                'posted_at' => now(),
                'match_notes' => 'Bank verified; receipt and journal posted. Use existing Tally export.',
            ]);
            return true;
        }, 3);
    }

    private static function paise($value): int
    {
        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', (string) $value)) {
            throw new RuntimeException('Invalid settlement amount.');
        }
        [$whole, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '0');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}
