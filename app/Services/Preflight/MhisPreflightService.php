<?php

namespace App\Services\Preflight;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;

final class MhisPreflightService
{
    public function checkApproval(
        IpBillingAccount $account,
        float $approvedAmount
    ): PreflightResult {
        $result = new PreflightResult();

        if ($account->status !== 'finalized' || ! $account->final_bill_no) {
            $result->add(new PreflightIssue(
                'BILL_NOT_FINALIZED',
                'blocking',
                'The IPD bill is not finalized.',
                'Complete IP Billing finalization before MHIS approval.',
                'Billing account #'.$account->id
            ));
        }

        if ($approvedAmount <= 0 || $approvedAmount > (float) $account->net_amount) {
            $result->add(new PreflightIssue(
                'INVALID_MHIS_APPROVAL',
                'blocking',
                'The MHIS approved amount must be greater than zero and must not exceed the eligible hospital bill.',
                'Verify the official MHIS approval against the hospital bill.',
                'Billing account #'.$account->id
            ));
        }

        $finalJournal = JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_id', (string) $account->id)
            ->where('source_event', 'finalized')
            ->where('status', 'posted')
            ->exists();

        if (! $finalJournal) {
            $result->add(new PreflightIssue(
                'FINAL_JOURNAL_NOT_POSTED',
                'blocking',
                'The finalized IPD bill has no posted final-bill accounting journal.',
                'Refer the bill for controlled journal reconciliation.',
                'Billing account #'.$account->id
            ));
        }

        $account->loadMissing([
            'mhisClaims',
            'advances',
            'refunds',
            'mhisReceipts',
            'mhisAdjustments',
            'staffMedicalBenefitTransactions',
            'charityAdjustments',
        ]);

        foreach ($account->mhisClaims as $claim) {
            if (JournalEntry::query()
                ->where('source_type', 'ip_billing_mhis_claim')
                ->where('source_id', (string) $claim->id)
                ->where('source_event', 'approved')
                ->exists()) {
                $result->add(new PreflightIssue(
                    'MHIS_ALREADY_RECOGNIZED',
                    'blocking',
                    'This MHIS claim already has a recognition journal.',
                    'Use the authorized adjustment workflow; do not recognize it twice.',
                    'MHIS claim #'.$claim->id
                ));
            }
        }

        if (
            ! $this->advancesReconciled($account)
            || $account->refunds->isNotEmpty()
            || $account->mhisReceipts->isNotEmpty()
            || $account->mhisAdjustments->isNotEmpty()
            || $account->staffMedicalBenefitTransactions->isNotEmpty()
            || $account->charityAdjustments->isNotEmpty()
            || $account->mhisClaims->count() > 1
        ) {
            $result->add(new PreflightIssue(
                'EXTENDED_MHIS_RECONCILIATION',
                'blocking',
                'Existing financial transactions require extended MHIS reconciliation.',
                'Review advances, refunds, receipts, adjustments and claim history with Finance.',
                'Billing account #'.$account->id
            ));
        }

        $account->loadMissing('payments');

        foreach ($account->payments as $payment) {
            $posted = JournalEntry::query()
                ->where('source_type', 'ip_billing_payment')
                ->where('source_id', (string) $payment->id)
                ->where('source_event', 'received')
                ->where('status', 'posted')
                ->exists();

            if ($payment->status !== 'active' || ! $posted) {
                $result->add(new PreflightIssue(
                    'PATIENT_PAYMENT_RECONCILIATION',
                    'blocking',
                    'Patient payment #'.$payment->id
                        .' requires accounting reconciliation.',
                    'Review payment status and its posted receipt journal with Finance.',
                    'Payment #'.$payment->id
                ));
            }
        }

        $otherJournals = JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_id', (string) $account->id)
            ->where('status', 'posted')
            ->whereNotIn('source_event', [
                    'finalized',
                    'advance_applied',
                ])
            ->exists();

        if ($otherJournals) {
            $result->add(new PreflightIssue(
                'IPD_ADJUSTMENT_RECONCILIATION',
                'blocking',
                'Other posted IPD accounting adjustments require reconciliation.',
                'Review the existing adjustment journals with Finance before MHIS approval.',
                'Billing account #'.$account->id
            ));
        }

        foreach (['1200', '1210'] as $code) {
            if (! GeneralLedgerAccount::query()
                ->where('account_code', $code)
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->exists()) {
                $result->add(new PreflightIssue(
                    'MHIS_GL_UNAVAILABLE',
                    'blocking',
                    'Required GL account '.$code.' is unavailable.',
                    'Ask Finance to verify and activate the correct GL account.',
                    'GL '.$code
                ));
            }
        }

        return $result;
    }

    // MHIS_RECONCILED_ADVANCES_V1
    private function advancesReconciled(
        IpBillingAccount $account
    ): bool {
        if ($account->advances->isEmpty()) {
            return true;
        }

        $totalPaise = 0;

        foreach ($account->advances as $advance) {
            if (
                $advance->status !== 'active'
                || $advance->source_type !== null
            ) {
                return false;
            }

            $posted = JournalEntry::query()
                ->where('source_type', 'ip_billing_advance')
                ->where('source_id', (string) $advance->id)
                ->where('source_event', 'received')
                ->where('status', 'posted')
                ->exists();

            if (! $posted) {
                return false;
            }

            $totalPaise += (int) round(
                (float) $advance->amount * 100
            );
        }

        $application = JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_id', (string) $account->id)
            ->where('source_event', 'advance_applied')
            ->where('status', 'posted')
            ->with('lines.generalLedgerAccount')
            ->first();

        if (! $application) {
            return false;
        }

        $debit2100 = 0;
        $credit1200 = 0;

        foreach ($application->lines as $line) {
            $code = $line->generalLedgerAccount?->account_code;

            if ($code === '2100') {
                $debit2100 += (int) round(
                    (float) $line->debit * 100
                );
            }

            if ($code === '1200') {
                $credit1200 += (int) round(
                    (float) $line->credit * 100
                );
            }
        }

        $expected = min(
            $totalPaise,
            (int) round((float) $account->net_amount * 100)
        );

        return $debit2100 === $expected
            && $credit1200 === $expected;
    }

}
